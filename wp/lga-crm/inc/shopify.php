<?php
/**
 * Integración Shopify Admin GraphQL.
 *
 * Flujo:
 *   Form complete    → save_post_solicitud → draftOrderCreate     → Draft
 *   Convert solicitud→ no toca Shopify (copia meta a lead)         → Draft sigue
 *   Lead aprobado    → promote → draftOrderComplete (paymentPending) → Order "unfulfilled" / "No preparado"
 *   Lead rechazado/perdido (todavía draft) → draftOrderDelete       → eliminado
 *   Lead rechazado/perdido (ya order)     → orderCancel (reason=DECLINED) → Order cancelled
 *
 * Configuración requerida en wp-config.php:
 *   define( 'LGA_SHOPIFY_SHOP',          'mem1a9-ev.myshopify.com' );  // sin protocolo
 *   define( 'LGA_SHOPIFY_TOKEN',         'shpat_xxxxxxxxxxxxx' );       // Admin API access token
 *   define( 'LGA_SHOPIFY_API_VERSION',   '2025-01' );                   // opcional, default 2025-01
 *   define( 'LGA_SHOPIFY_ENABLED',       true );                        // master switch
 *
 * Si LGA_SHOPIFY_TOKEN no está definido o LGA_SHOPIFY_ENABLED es false, este módulo
 * queda inerte (no rompe, no llama a Shopify). Útil para staging / migración.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─── Helpers de configuración ──────────────────────────────────────────────
function lga_crm_shopify_enabled() {
    if ( defined( 'LGA_SHOPIFY_ENABLED' ) && ! LGA_SHOPIFY_ENABLED ) {
        return false;
    }
    return defined( 'LGA_SHOPIFY_TOKEN' ) && LGA_SHOPIFY_TOKEN && defined( 'LGA_SHOPIFY_SHOP' ) && LGA_SHOPIFY_SHOP;
}

function lga_crm_shopify_shop() {
    return defined( 'LGA_SHOPIFY_SHOP' ) ? LGA_SHOPIFY_SHOP : '';
}

function lga_crm_shopify_api_version() {
    return defined( 'LGA_SHOPIFY_API_VERSION' ) ? LGA_SHOPIFY_API_VERSION : '2025-01';
}

function lga_crm_shopify_admin_url( $path = '' ) {
    $shop = lga_crm_shopify_shop();
    if ( ! $shop ) return '';
    return 'https://' . $shop . '/admin' . ( $path ? '/' . ltrim( $path, '/' ) : '' );
}

// ─── Logging interno (post meta + Telegram opcional via n8n) ───────────────
function lga_crm_shopify_log( $post_id, $event, $payload = array(), $level = 'info' ) {
    $log = get_post_meta( $post_id, '_shopify_log', true );
    $log = is_array( $log ) ? $log : array();
    $log[] = array(
        'ts'      => current_time( 'mysql' ),
        'event'   => $event,
        'level'   => $level,
        'payload' => $payload,
    );
    // mantener máximo 50 entradas
    if ( count( $log ) > 50 ) {
        $log = array_slice( $log, -50 );
    }
    update_post_meta( $post_id, '_shopify_log', $log );
}

// ─── Llamada GraphQL base ──────────────────────────────────────────────────
/**
 * Ejecuta una mutation/query GraphQL contra Shopify Admin.
 * Retorna array con 'data' o 'errors' parseados, o WP_Error en caso de red.
 */
function lga_crm_shopify_graphql( $query, $variables = array() ) {
    if ( ! lga_crm_shopify_enabled() ) {
        return new WP_Error( 'shopify_disabled', 'Shopify integration no configurada (falta LGA_SHOPIFY_TOKEN).' );
    }

    $url = 'https://' . lga_crm_shopify_shop() . '/admin/api/' . lga_crm_shopify_api_version() . '/graphql.json';

    $response = wp_remote_post( $url, array(
        'timeout' => 30,
        'headers' => array(
            'X-Shopify-Access-Token' => LGA_SHOPIFY_TOKEN,
            'Content-Type'           => 'application/json',
            'Accept'                 => 'application/json',
        ),
        'body' => wp_json_encode( array(
            'query'     => $query,
            'variables' => (object) $variables,
        ) ),
    ) );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code( $response );
    $body = wp_remote_retrieve_body( $response );
    $parsed = json_decode( $body, true );

    if ( $code < 200 || $code >= 300 ) {
        return new WP_Error( 'shopify_http_' . $code, "Shopify HTTP $code: " . substr( $body, 0, 500 ) );
    }
    if ( ! is_array( $parsed ) ) {
        return new WP_Error( 'shopify_invalid_json', 'Respuesta no JSON: ' . substr( $body, 0, 500 ) );
    }
    return $parsed;
}

// ─── 1) Crear Draft Order desde una solicitud ──────────────────────────────
/**
 * Crea Draft Order en Shopify a partir de un post tipo 'solicitud'.
 * Idempotente: si ya tiene shopify_draft_order_id no hace nada.
 */
function lga_crm_shopify_create_draft_order( $sol_id ) {
    if ( ! lga_crm_shopify_enabled() ) return new WP_Error( 'disabled', 'disabled' );

    // Idempotencia
    $existing = get_post_meta( $sol_id, 'shopify_draft_order_id', true );
    if ( $existing ) {
        return array( 'skipped' => 'already_exists', 'draft_id' => $existing );
    }

    $variant_id = trim( (string) get_field( 'variant_id', $sol_id ) );
    if ( ! $variant_id ) {
        return new WP_Error( 'no_variant', 'Solicitud sin variant_id; no se puede crear draft order.' );
    }
    $quantity = max( 1, (int) get_field( 'quantity', $sol_id ) );

    $first   = (string) get_field( 'first_name', $sol_id );
    $last    = (string) get_field( 'last_name', $sol_id );
    $email   = (string) get_field( 'email', $sol_id );
    $phone   = (string) get_field( 'phone', $sol_id );
    $dni     = (string) get_field( 'dni', $sol_id );
    $addr    = (string) get_field( 'address_line', $sol_id );
    $loc     = (string) get_field( 'locality', $sol_id );
    $prov    = (string) get_field( 'province', $sol_id );
    $cp      = (string) get_field( 'postal_code', $sol_id );
    $monto   = (float)  get_field( 'requested_amount_ars', $sol_id );
    $cuotas  = (int)    get_field( 'requested_installments', $sol_id );
    $freq    = (string) get_field( 'payment_frequency', $sol_id );
    $code    = (string) get_field( 'application_code', $sol_id ) ?: get_the_title( $sol_id );

    $address = array(
        'firstName' => $first ?: '—',
        'lastName'  => $last  ?: '—',
        'address1'  => $addr  ?: '',
        'city'      => $loc   ?: '',
        'province'  => $prov  ?: '',
        'zip'       => $cp    ?: '',
        'country'   => 'Argentina',
        'phone'     => $phone ?: '',
    );

    $note  = sprintf(
        "Crédito LGA pendiente de aprobación.\nDNI %s · Monto pedido $%s ARS · %d cuotas %s.\nApplication code: %s",
        $dni, number_format( $monto, 0, ',', '.' ), $cuotas, $freq, $code
    );

    $mutation = 'mutation lgaDraftCreate($input: DraftOrderInput!) {
      draftOrderCreate(input: $input) {
        draftOrder { id name invoiceUrl status totalPrice }
        userErrors { field message }
      }
    }';

    $variables = array(
        'input' => array(
            'lineItems' => array(
                array(
                    'variantId' => 'gid://shopify/ProductVariant/' . preg_replace( '/\D/', '', $variant_id ),
                    'quantity'  => $quantity,
                ),
            ),
            'email'           => $email ?: null,
            'phone'           => $phone ?: null,
            'shippingAddress' => $address,
            'billingAddress'  => $address,
            'tags'            => array( 'lga-credit', 'lga-pending-approval' ),
            'note'            => $note,
            'customAttributes'=> array(
                array( 'key' => 'lga_application_code', 'value' => $code ),
                array( 'key' => 'lga_solicitud_id',     'value' => (string) $sol_id ),
                array( 'key' => 'lga_dni',              'value' => $dni ),
                array( 'key' => 'lga_monto_pedido_ars', 'value' => (string) $monto ),
                array( 'key' => 'lga_cuotas',           'value' => (string) $cuotas ),
            ),
        ),
    );

    $resp = lga_crm_shopify_graphql( $mutation, $variables );
    if ( is_wp_error( $resp ) ) {
        lga_crm_shopify_log( $sol_id, 'draft_create_http_error', array( 'error' => $resp->get_error_message() ), 'error' );
        return $resp;
    }
    $data   = $resp['data']['draftOrderCreate'] ?? array();
    $errs   = $data['userErrors'] ?? array();
    $draft  = $data['draftOrder']  ?? null;
    if ( ! empty( $errs ) || ! $draft ) {
        lga_crm_shopify_log( $sol_id, 'draft_create_user_errors', array( 'errors' => $errs, 'raw' => $resp ), 'error' );
        return new WP_Error( 'shopify_user_error', 'Errores Shopify: ' . wp_json_encode( $errs ) );
    }

    // Guardar meta
    $gid = $draft['id']; // gid://shopify/DraftOrder/12345
    preg_match( '/(\d+)$/', $gid, $m );
    $numeric_id = $m[1] ?? '';

    update_post_meta( $sol_id, 'shopify_draft_order_id',  $numeric_id );
    update_post_meta( $sol_id, 'shopify_draft_order_gid', $gid );
    update_post_meta( $sol_id, 'shopify_draft_order_name', $draft['name'] ?? '' );
    update_post_meta( $sol_id, 'shopify_invoice_url',     $draft['invoiceUrl'] ?? '' );
    update_post_meta( $sol_id, 'shopify_status',          'draft_created' );
    update_post_meta( $sol_id, 'shopify_last_sync_at',    current_time( 'mysql' ) );

    lga_crm_shopify_log( $sol_id, 'draft_created', array(
        'numeric_id' => $numeric_id,
        'name'       => $draft['name'] ?? '',
    ) );

    return array( 'draft_id' => $numeric_id, 'gid' => $gid, 'name' => $draft['name'] ?? '' );
}

// ─── 2) Completar Draft Order → Order "unfulfilled" / "No preparado" ──────
/**
 * Llamar cuando el LEAD se aprueba → cliente + crédito creados.
 * $source_post puede ser el lead, el cliente o el credito (el que tenga el meta).
 * Toma el draft_order_id de cualquiera y lo completa con paymentPending=true.
 */
function lga_crm_shopify_complete_draft( $source_post_id, $propagate_to = array() ) {
    if ( ! lga_crm_shopify_enabled() ) return new WP_Error( 'disabled', 'disabled' );

    $draft_gid = get_post_meta( $source_post_id, 'shopify_draft_order_gid', true );
    if ( ! $draft_gid ) {
        // intentar reconstruir desde numeric
        $num = get_post_meta( $source_post_id, 'shopify_draft_order_id', true );
        if ( $num ) $draft_gid = 'gid://shopify/DraftOrder/' . preg_replace( '/\D/', '', $num );
    }
    if ( ! $draft_gid ) {
        return new WP_Error( 'no_draft', 'No hay shopify_draft_order_gid en el post fuente.' );
    }

    // Idempotencia
    $existing_order = get_post_meta( $source_post_id, 'shopify_order_id', true );
    if ( $existing_order ) {
        return array( 'skipped' => 'already_completed', 'order_id' => $existing_order );
    }

    $mutation = 'mutation lgaDraftComplete($id: ID!, $paymentPending: Boolean) {
      draftOrderComplete(id: $id, paymentPending: $paymentPending) {
        draftOrder {
          id
          order {
            id
            name
            displayFulfillmentStatus
            displayFinancialStatus
          }
        }
        userErrors { field message }
      }
    }';

    $resp = lga_crm_shopify_graphql( $mutation, array(
        'id'             => $draft_gid,
        'paymentPending' => true,
    ) );

    if ( is_wp_error( $resp ) ) {
        lga_crm_shopify_log( $source_post_id, 'draft_complete_http_error', array( 'error' => $resp->get_error_message() ), 'error' );
        return $resp;
    }
    $data = $resp['data']['draftOrderComplete'] ?? array();
    $errs = $data['userErrors'] ?? array();
    $order = $data['draftOrder']['order'] ?? null;
    if ( ! empty( $errs ) || ! $order ) {
        lga_crm_shopify_log( $source_post_id, 'draft_complete_user_errors', array( 'errors' => $errs, 'raw' => $resp ), 'error' );
        return new WP_Error( 'shopify_user_error', 'Errores Shopify: ' . wp_json_encode( $errs ) );
    }

    $order_gid = $order['id']; // gid://shopify/Order/123
    preg_match( '/(\d+)$/', $order_gid, $m );
    $order_numeric = $m[1] ?? '';

    $meta_to_set = array(
        'shopify_order_id'               => $order_numeric,
        'shopify_order_gid'              => $order_gid,
        'shopify_order_name'             => $order['name'] ?? '',
        'shopify_order_fulfillment_status'=> strtolower( (string) ( $order['displayFulfillmentStatus'] ?? '' ) ),
        'shopify_order_financial_status' => strtolower( (string) ( $order['displayFinancialStatus'] ?? '' ) ),
        'shopify_status'                 => 'order_unfulfilled',
        'shopify_last_sync_at'           => current_time( 'mysql' ),
    );

    // Setear en el post fuente
    foreach ( $meta_to_set as $k => $v ) {
        update_post_meta( $source_post_id, $k, $v );
    }
    lga_crm_shopify_log( $source_post_id, 'draft_completed', array(
        'order_numeric' => $order_numeric,
        'order_name'    => $order['name'] ?? '',
    ) );

    // Propagar a otros posts (cliente, crédito, solicitud)
    foreach ( $propagate_to as $other_id ) {
        if ( ! $other_id ) continue;
        // Copiar draft meta también para tener trazabilidad
        $draft_num = get_post_meta( $source_post_id, 'shopify_draft_order_id', true );
        if ( $draft_num ) {
            update_post_meta( $other_id, 'shopify_draft_order_id',  $draft_num );
            update_post_meta( $other_id, 'shopify_draft_order_gid', $draft_gid );
            update_post_meta( $other_id, 'shopify_draft_order_name', get_post_meta( $source_post_id, 'shopify_draft_order_name', true ) );
        }
        foreach ( $meta_to_set as $k => $v ) {
            update_post_meta( $other_id, $k, $v );
        }
    }

    return array( 'order_id' => $order_numeric, 'gid' => $order_gid, 'name' => $order['name'] ?? '' );
}

// ─── 3) Borrar Draft Order (rechazo antes de aprobar) ──────────────────────
function lga_crm_shopify_delete_draft( $source_post_id ) {
    if ( ! lga_crm_shopify_enabled() ) return new WP_Error( 'disabled', 'disabled' );

    $draft_gid = get_post_meta( $source_post_id, 'shopify_draft_order_gid', true );
    if ( ! $draft_gid ) {
        return array( 'skipped' => 'no_draft' );
    }

    $mutation = 'mutation lgaDraftDelete($input: DraftOrderDeleteInput!) {
      draftOrderDelete(input: $input) {
        deletedId
        userErrors { field message }
      }
    }';
    $resp = lga_crm_shopify_graphql( $mutation, array(
        'input' => array( 'id' => $draft_gid ),
    ) );
    if ( is_wp_error( $resp ) ) {
        lga_crm_shopify_log( $source_post_id, 'draft_delete_http_error', array( 'error' => $resp->get_error_message() ), 'error' );
        return $resp;
    }
    $data = $resp['data']['draftOrderDelete'] ?? array();
    $errs = $data['userErrors'] ?? array();
    if ( ! empty( $errs ) ) {
        lga_crm_shopify_log( $source_post_id, 'draft_delete_user_errors', array( 'errors' => $errs ), 'error' );
        return new WP_Error( 'shopify_user_error', wp_json_encode( $errs ) );
    }

    update_post_meta( $source_post_id, 'shopify_status', 'draft_deleted' );
    update_post_meta( $source_post_id, 'shopify_last_sync_at', current_time( 'mysql' ) );
    lga_crm_shopify_log( $source_post_id, 'draft_deleted', array( 'deletedId' => $data['deletedId'] ?? '' ) );
    return array( 'deleted' => true );
}

// ─── 4) Cancelar Order (después de promote, si lead se cancela) ────────────
function lga_crm_shopify_cancel_order( $source_post_id, $reason = 'DECLINED' ) {
    if ( ! lga_crm_shopify_enabled() ) return new WP_Error( 'disabled', 'disabled' );

    $order_gid = get_post_meta( $source_post_id, 'shopify_order_gid', true );
    if ( ! $order_gid ) {
        return array( 'skipped' => 'no_order' );
    }

    // Reasons válidos: CUSTOMER, DECLINED, FRAUD, INVENTORY, STAFF, OTHER
    $valid = array( 'CUSTOMER', 'DECLINED', 'FRAUD', 'INVENTORY', 'STAFF', 'OTHER' );
    if ( ! in_array( $reason, $valid, true ) ) $reason = 'DECLINED';

    $mutation = 'mutation lgaOrderCancel($orderId: ID!, $reason: OrderCancelReason!, $refund: Boolean!, $restock: Boolean!, $notifyCustomer: Boolean) {
      orderCancel(orderId: $orderId, reason: $reason, refund: $refund, restock: $restock, notifyCustomer: $notifyCustomer) {
        job { id done }
        orderCancelUserErrors { field message }
      }
    }';
    $resp = lga_crm_shopify_graphql( $mutation, array(
        'orderId'        => $order_gid,
        'reason'         => $reason,
        'refund'         => true,   // no hay pago real (paymentPending), igual paso true por completitud
        'restock'        => true,
        'notifyCustomer' => false,  // silencioso
    ) );
    if ( is_wp_error( $resp ) ) {
        lga_crm_shopify_log( $source_post_id, 'order_cancel_http_error', array( 'error' => $resp->get_error_message() ), 'error' );
        return $resp;
    }
    $data = $resp['data']['orderCancel'] ?? array();
    $errs = $data['orderCancelUserErrors'] ?? array();
    if ( ! empty( $errs ) ) {
        lga_crm_shopify_log( $source_post_id, 'order_cancel_user_errors', array( 'errors' => $errs ), 'error' );
        return new WP_Error( 'shopify_user_error', wp_json_encode( $errs ) );
    }

    update_post_meta( $source_post_id, 'shopify_status', 'order_cancelled' );
    update_post_meta( $source_post_id, 'shopify_order_fulfillment_status', 'cancelled' );
    update_post_meta( $source_post_id, 'shopify_last_sync_at', current_time( 'mysql' ) );
    lga_crm_shopify_log( $source_post_id, 'order_cancelled', array( 'reason' => $reason, 'job' => $data['job'] ?? null ) );

    return array( 'cancelled' => true );
}

// ─── 5) Hook: nuevo CPT solicitud → auto-create draft ──────────────────────
add_action( 'save_post_solicitud', function ( $post_id, $post, $update ) {
    if ( $update ) return;
    if ( wp_is_post_revision( $post_id ) ) return;
    if ( get_post_status( $post_id ) !== 'publish' ) return;
    if ( ! lga_crm_shopify_enabled() ) return;

    // Pequeño delay: ACF puede no haber escrito todos los meta todavía.
    // Lo encolamos como single event en 5s.
    wp_schedule_single_event( time() + 5, 'lga_crm_shopify_draft_create_async', array( $post_id ) );
}, 50, 3 );

add_action( 'lga_crm_shopify_draft_create_async', function ( $post_id ) {
    if ( get_post_type( $post_id ) !== 'solicitud' ) return;
    lga_crm_shopify_create_draft_order( $post_id );
} );

// ─── 6) Helpers UI ─────────────────────────────────────────────────────────
function lga_crm_shopify_admin_link_draft( $post_id ) {
    $id = get_post_meta( $post_id, 'shopify_draft_order_id', true );
    if ( ! $id ) return '';
    return lga_crm_shopify_admin_url( 'draft_orders/' . preg_replace( '/\D/', '', $id ) );
}

function lga_crm_shopify_admin_link_order( $post_id ) {
    $id = get_post_meta( $post_id, 'shopify_order_id', true );
    if ( ! $id ) return '';
    return lga_crm_shopify_admin_url( 'orders/' . preg_replace( '/\D/', '', $id ) );
}

/**
 * Label legible del estado Shopify para mostrar al user.
 */
function lga_crm_shopify_status_label( $status ) {
    $labels = array(
        ''                  => 'Sin Shopify',
        'draft_created'     => 'Borrador',
        'draft_deleted'     => 'Borrador eliminado',
        'order_unfulfilled' => 'No preparado',
        'order_cancelled'   => 'Cancelado',
        'error'             => 'Error',
    );
    return $labels[ $status ] ?? $status;
}

function lga_crm_shopify_status_badge( $post_id ) {
    $status = get_post_meta( $post_id, 'shopify_status', true );
    if ( ! $status ) return '';
    $palette = array(
        'draft_created'     => 'bg-zinc-100 text-zinc-700 ring-zinc-600/10',
        'draft_deleted'     => 'bg-zinc-100 text-zinc-500 ring-zinc-600/10',
        'order_unfulfilled' => 'bg-amber-50 text-amber-700 ring-amber-700/10',
        'order_cancelled'   => 'bg-red-50 text-red-700 ring-red-700/10',
        'error'             => 'bg-red-50 text-red-700 ring-red-700/10',
    );
    $cls = $palette[ $status ] ?? 'bg-zinc-100 text-zinc-700 ring-zinc-600/10';
    return '<span class="lga-badge ring-1 ring-inset ' . esc_attr( $cls ) . '">' . esc_html( lga_crm_shopify_status_label( $status ) ) . '</span>';
}
