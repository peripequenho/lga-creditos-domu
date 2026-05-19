<?php
/**
 * Integración Shopify Admin GraphQL — flow client_credentials (Dev Dashboard, 2026+).
 *
 * Shopify cambió enero/2026: custom apps "legacy" desde admin ya no se crean.
 * El nuevo flow es:
 *   1. App en Dev Dashboard → genera Client ID + Client Secret
 *   2. POST /admin/oauth/access_token con grant_type=client_credentials
 *      → devuelve access_token shpat_xxx válido 24h
 *   3. Usar ese access_token como X-Shopify-Access-Token contra GraphQL
 *
 * Cacheamos el access_token con WP transient (auto-refresh cuando expira).
 *
 * Flujo de negocio LGA:
 *   Form complete    → save_post_solicitud → draftOrderCreate     → Draft
 *   Convert solicitud→ no toca Shopify (copia meta a lead)         → Draft sigue
 *   Lead aprobado    → promote → draftOrderComplete (paymentPending) → Order "unfulfilled"
 *   Lead rechazado/perdido (todavía draft) → draftOrderDelete       → eliminado
 *   Lead rechazado/perdido (ya order)     → orderCancel (reason=DECLINED) → Order cancelled
 *
 * Configuración requerida en wp-config.php:
 *   define( 'LGA_SHOPIFY_SHOP',          'mem1a9-ev.myshopify.com' );
 *   define( 'LGA_SHOPIFY_CLIENT_ID',     '16422fb3e33239bf87b971793b5c405d' );
 *   define( 'LGA_SHOPIFY_CLIENT_SECRET', 'shpss_xxxxxxxxxxxxx' );
 *   define( 'LGA_SHOPIFY_API_VERSION',   '2025-01' );  // opcional
 *   define( 'LGA_SHOPIFY_ENABLED',       true );
 *
 * COMPATIBILIDAD HACIA ATRÁS: si en lugar de CLIENT_ID/SECRET se define
 * LGA_SHOPIFY_TOKEN (formato viejo shpat_), se usa directo sin canjear.
 * Esto cubre las custom apps legacy que sigan funcionando.
 *
 * Si nada está configurado o LGA_SHOPIFY_ENABLED=false, el módulo queda inerte.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─── Helpers de configuración ──────────────────────────────────────────────
function lga_crm_shopify_enabled() {
    if ( defined( 'LGA_SHOPIFY_ENABLED' ) && ! LGA_SHOPIFY_ENABLED ) {
        return false;
    }
    if ( ! defined( 'LGA_SHOPIFY_SHOP' ) || ! LGA_SHOPIFY_SHOP ) {
        return false;
    }
    // Nuevo flow (client_credentials): CLIENT_ID + CLIENT_SECRET
    $has_credentials = defined( 'LGA_SHOPIFY_CLIENT_ID' ) && LGA_SHOPIFY_CLIENT_ID
                    && defined( 'LGA_SHOPIFY_CLIENT_SECRET' ) && LGA_SHOPIFY_CLIENT_SECRET;
    // Legacy fallback: TOKEN directo
    $has_legacy_token = defined( 'LGA_SHOPIFY_TOKEN' ) && LGA_SHOPIFY_TOKEN;
    return $has_credentials || $has_legacy_token;
}

/**
 * Obtiene un access_token Admin API válido.
 * Si hay LGA_SHOPIFY_TOKEN (legacy), lo retorna directo.
 * Si hay CLIENT_ID+SECRET, canjea por access_token via client_credentials grant
 * y lo cachea en WP transient (TTL = expires_in - 1h de margen).
 * Retorna string o WP_Error.
 */
function lga_crm_shopify_get_access_token( $force_refresh = false ) {
    // Legacy: token directo configurado
    if ( defined( 'LGA_SHOPIFY_TOKEN' ) && LGA_SHOPIFY_TOKEN ) {
        return LGA_SHOPIFY_TOKEN;
    }

    if ( ! defined( 'LGA_SHOPIFY_CLIENT_ID' ) || ! defined( 'LGA_SHOPIFY_CLIENT_SECRET' ) ) {
        return new WP_Error( 'no_credentials', 'Falta CLIENT_ID o CLIENT_SECRET' );
    }

    $cache_key = 'lga_shopify_admin_token';
    if ( ! $force_refresh ) {
        $cached = get_transient( $cache_key );
        if ( $cached ) return $cached;
    }

    $resp = wp_remote_post(
        'https://' . LGA_SHOPIFY_SHOP . '/admin/oauth/access_token',
        array(
            'timeout' => 15,
            'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
            'body'    => array(
                'client_id'     => LGA_SHOPIFY_CLIENT_ID,
                'client_secret' => LGA_SHOPIFY_CLIENT_SECRET,
                'grant_type'    => 'client_credentials',
            ),
        )
    );

    if ( is_wp_error( $resp ) ) {
        return $resp;
    }
    $code = wp_remote_retrieve_response_code( $resp );
    $body = wp_remote_retrieve_body( $resp );
    if ( $code < 200 || $code >= 300 ) {
        return new WP_Error( 'token_http_' . $code, "Token exchange HTTP $code: " . substr( $body, 0, 300 ) );
    }
    $data = json_decode( $body, true );
    $token = $data['access_token'] ?? null;
    if ( ! $token ) {
        return new WP_Error( 'no_access_token', 'Respuesta sin access_token: ' . substr( $body, 0, 300 ) );
    }

    // Cachear con margen de 1h antes de expirar
    $expires_in = (int) ( $data['expires_in'] ?? 86400 );
    $ttl = max( 60, $expires_in - 3600 );
    set_transient( $cache_key, $token, $ttl );

    return $token;
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
 * Ejecuta mutation/query GraphQL contra Shopify Admin.
 * Maneja auto-refresh del access_token si el server responde 401 (token expirado).
 * Retorna array con 'data'/'errors', o WP_Error.
 */
function lga_crm_shopify_graphql( $query, $variables = array(), $retry = true ) {
    if ( ! lga_crm_shopify_enabled() ) {
        return new WP_Error( 'shopify_disabled', 'Shopify integration no configurada.' );
    }

    $token = lga_crm_shopify_get_access_token();
    if ( is_wp_error( $token ) ) return $token;

    $url = 'https://' . lga_crm_shopify_shop() . '/admin/api/' . lga_crm_shopify_api_version() . '/graphql.json';

    $response = wp_remote_post( $url, array(
        'timeout' => 30,
        'headers' => array(
            'X-Shopify-Access-Token' => $token,
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

    // Si 401 y todavía tenemos retry pendiente: invalidar cache y reintentar 1 vez
    if ( $code === 401 && $retry ) {
        delete_transient( 'lga_shopify_admin_token' );
        return lga_crm_shopify_graphql( $query, $variables, false );
    }

    if ( $code < 200 || $code >= 300 ) {
        return new WP_Error( 'shopify_http_' . $code, "Shopify HTTP $code: " . substr( $body, 0, 500 ) );
    }
    $parsed = json_decode( $body, true );
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

// ─── Helper: llamar al webhook n8n lifecycle ──────────────────────────────
// Desde v0.3.8: las mutaciones Shopify (complete/cancel/delete) se ejecutan
// en n8n, no en el plugin. Esto desacopla el plugin de los CLIENT_ID/SECRET
// Shopify y permite que la app "LGA CRM Integration" haga el trabajo via
// el access token guardado en el credential 'Shopify Domu Token' del VPS.
//
// El webhook devuelve { ok, action, new_meta: {...}, error? }.
// Las metas se aplican localmente en el plugin (este código).
function lga_crm_shopify_n8n_lifecycle( $action, $payload ) {
    $url = defined( 'LGA_N8N_LIFECYCLE_WEBHOOK' )
        ? LGA_N8N_LIFECYCLE_WEBHOOK
        : 'https://n8n.lga-arg.com/webhook/lga-shopify-lifecycle';

    $resp = wp_remote_post( $url, array(
        'timeout' => 25,
        'headers' => array( 'Content-Type' => 'application/json' ),
        'body'    => wp_json_encode( array_merge( array( 'action' => $action ), $payload ) ),
    ) );

    if ( is_wp_error( $resp ) ) return $resp;
    $code = wp_remote_retrieve_response_code( $resp );
    $body = wp_remote_retrieve_body( $resp );
    $data = json_decode( $body, true );

    if ( ! is_array( $data ) ) {
        return new WP_Error( 'n8n_invalid_response', 'Respuesta no JSON ' . $code . ': ' . substr( $body, 0, 200 ) );
    }
    if ( $code < 200 || $code >= 300 || ! $data['ok'] ) {
        return new WP_Error( 'n8n_failed', $data['error'] ?? ( 'HTTP ' . $code ) );
    }
    return $data;
}

// Aplica metas a varios posts (source + propagate_to)
function lga_crm_shopify_apply_meta( $source_post_id, $propagate_to, $meta_map ) {
    $ids = array_merge( array( $source_post_id ), (array) $propagate_to );
    foreach ( $ids as $id ) {
        if ( ! $id ) continue;
        foreach ( $meta_map as $k => $v ) {
            if ( $v !== '' && $v !== null ) update_post_meta( $id, $k, $v );
        }
    }
}

// ─── 2) Completar Draft Order → Order "unfulfilled" / "No preparado" ──────
/**
 * Llamar cuando el LEAD se aprueba → cliente + crédito creados.
 * Usa el webhook n8n /lga-shopify-lifecycle (action: complete_draft).
 */
function lga_crm_shopify_complete_draft( $source_post_id, $propagate_to = array() ) {
    $draft_id = preg_replace( '/\D/', '', (string) get_post_meta( $source_post_id, 'shopify_draft_order_id', true ) );
    if ( ! $draft_id ) {
        return new WP_Error( 'no_draft', 'No hay shopify_draft_order_id en el post fuente.' );
    }

    // Idempotencia: si ya existe order, propagar metas existentes y salir.
    $existing_order = get_post_meta( $source_post_id, 'shopify_order_id', true );
    if ( $existing_order ) {
        $idem_meta = array();
        foreach ( array(
            'shopify_draft_order_id','shopify_draft_order_gid','shopify_draft_order_name',
            'shopify_order_id','shopify_order_gid','shopify_order_name',
            'shopify_order_fulfillment_status','shopify_order_financial_status',
            'shopify_status','shopify_last_sync_at',
        ) as $k ) {
            $idem_meta[ $k ] = get_post_meta( $source_post_id, $k, true );
        }
        lga_crm_shopify_apply_meta( $source_post_id, $propagate_to, $idem_meta );
        return array( 'skipped' => 'already_completed', 'order_id' => $existing_order );
    }

    $app_code = (string) ( get_post_meta( $source_post_id, 'application_code', true ) ?: get_the_title( $source_post_id ) );

    $resp = lga_crm_shopify_n8n_lifecycle( 'complete_draft', array(
        'draft_order_id'        => $draft_id,
        'wp_post_id'            => $source_post_id,
        'application_code'      => $app_code,
        'propagate_to_post_ids' => array_values( array_filter( (array) $propagate_to ) ),
    ) );

    if ( is_wp_error( $resp ) ) {
        update_post_meta( $source_post_id, 'shopify_last_error', $resp->get_error_message() );
        lga_crm_shopify_log( $source_post_id, 'complete_draft_failed', array( 'error' => $resp->get_error_message() ), 'error' );
        return $resp;
    }

    $new_meta = $resp['new_meta'] ?? array();
    lga_crm_shopify_apply_meta( $source_post_id, $propagate_to, $new_meta );
    lga_crm_shopify_log( $source_post_id, 'draft_completed_via_n8n', array(
        'order_id'   => $new_meta['shopify_order_id'] ?? '',
        'order_name' => $new_meta['shopify_order_name'] ?? '',
    ) );

    return array(
        'order_id'   => $new_meta['shopify_order_id']   ?? '',
        'order_name' => $new_meta['shopify_order_name'] ?? '',
    );
}

// ─── 3) Borrar Draft Order (rechazo antes de aprobar) ──────────────────────
function lga_crm_shopify_delete_draft( $source_post_id ) {
    $draft_id = preg_replace( '/\D/', '', (string) get_post_meta( $source_post_id, 'shopify_draft_order_id', true ) );
    if ( ! $draft_id ) {
        return array( 'skipped' => 'no_draft' );
    }

    $app_code = (string) ( get_post_meta( $source_post_id, 'application_code', true ) ?: get_the_title( $source_post_id ) );

    $resp = lga_crm_shopify_n8n_lifecycle( 'cancel_draft', array(
        'draft_order_id'   => $draft_id,
        'wp_post_id'       => $source_post_id,
        'application_code' => $app_code,
    ) );

    if ( is_wp_error( $resp ) ) {
        update_post_meta( $source_post_id, 'shopify_last_error', $resp->get_error_message() );
        lga_crm_shopify_log( $source_post_id, 'delete_draft_failed', array( 'error' => $resp->get_error_message() ), 'error' );
        return $resp;
    }

    $new_meta = $resp['new_meta'] ?? array();
    lga_crm_shopify_apply_meta( $source_post_id, array(), $new_meta );
    lga_crm_shopify_log( $source_post_id, 'draft_deleted_via_n8n', array() );
    return array( 'deleted' => true );
}

// ─── 4) Cancelar Order (después de promote, si lead se cancela) ────────────
function lga_crm_shopify_cancel_order( $source_post_id, $reason = 'DECLINED' ) {
    $order_id = preg_replace( '/\D/', '', (string) get_post_meta( $source_post_id, 'shopify_order_id', true ) );
    if ( ! $order_id ) {
        return array( 'skipped' => 'no_order' );
    }

    $valid = array( 'CUSTOMER', 'DECLINED', 'FRAUD', 'INVENTORY', 'STAFF', 'OTHER' );
    if ( ! in_array( $reason, $valid, true ) ) $reason = 'DECLINED';

    $app_code = (string) ( get_post_meta( $source_post_id, 'application_code', true ) ?: get_the_title( $source_post_id ) );

    $resp = lga_crm_shopify_n8n_lifecycle( 'cancel_order', array(
        'order_id'         => $order_id,
        'wp_post_id'       => $source_post_id,
        'application_code' => $app_code,
        'reason'           => $reason,
    ) );

    if ( is_wp_error( $resp ) ) {
        update_post_meta( $source_post_id, 'shopify_last_error', $resp->get_error_message() );
        lga_crm_shopify_log( $source_post_id, 'cancel_order_failed', array( 'error' => $resp->get_error_message() ), 'error' );
        return $resp;
    }

    $new_meta = $resp['new_meta'] ?? array();
    lga_crm_shopify_apply_meta( $source_post_id, array(), $new_meta );
    lga_crm_shopify_log( $source_post_id, 'order_cancelled_via_n8n', array( 'reason' => $reason ) );
    return array( 'cancelled' => true );
}

// ─── 5) Hook: nuevo CPT solicitud → auto-create draft ──────────────────────
// Cubrimos dos vías de inserción:
//   a) WP REST API (n8n)        → rest_after_insert_solicitud
//   b) wp-admin / acf-form      → acf/save_post (después de que ACF persistió)
// Ambos llaman al mismo handler idempotente (skipea si draft ya existe).
function lga_crm_shopify_handle_new_solicitud( $post_id ) {
    if ( ! is_numeric( $post_id ) ) return;
    if ( get_post_type( $post_id ) !== 'solicitud' ) return;
    if ( wp_is_post_revision( $post_id ) ) return;
    if ( get_post_status( $post_id ) !== 'publish' ) return;
    if ( ! lga_crm_shopify_enabled() ) return;

    // Idempotente: si ya tiene draft, no hace nada (cubre rest_after_insert + acf/save_post)
    if ( get_post_meta( $post_id, 'shopify_draft_order_id', true ) ) return;

    $result = lga_crm_shopify_create_draft_order( $post_id );
    if ( is_wp_error( $result ) && $result->get_error_code() === 'no_variant' ) {
        // variant_id puede llegar después (n8n setea meta en pasos posteriores).
        // Re-schedule en 3s + disparar wp-cron en background para que NO sea lazy.
        wp_schedule_single_event( time() + 3, 'lga_crm_shopify_draft_create_async', array( $post_id ) );
        wp_remote_get( site_url( '/wp-cron.php?doing_wp_cron=1' ), array(
            'blocking' => false,
            'timeout'  => 0.5,
        ) );
    }
}

// PARALELIZACIÓN: desde v0.3.8 el draft Shopify lo crea VERCEL (fuente única,
// en paralelo con el notify a Telegram). Los hooks de auto-create están
// deshabilitados acá para evitar drafts duplicados. La función
// `lga_crm_shopify_handle_new_solicitud` y `lga_crm_shopify_create_draft_order`
// quedan disponibles como fallback: el admin puede llamar manualmente
// /wp-json/lga/v1/solicitud/{id}/finalize si Vercel falló y la solicitud
// quedó sin draft asociado.
//
// HOOKS DESHABILITADOS (intencionalmente comentados):
// add_action( 'rest_after_insert_solicitud', ... );
// add_action( 'acf/save_post', 'lga_crm_shopify_handle_new_solicitud', 20 );
// add_action( 'updated_post_meta', $lga_crm_meta_handler, 20, 4 );
// add_action( 'added_post_meta',   $lga_crm_meta_handler, 20, 4 );

// Solo dejamos el cron async (lo usa el endpoint /finalize de fallback).
add_action( 'lga_crm_shopify_draft_create_async', function ( $post_id ) {
    if ( get_post_type( $post_id ) !== 'solicitud' ) return;
    lga_crm_shopify_create_draft_order( $post_id );
} );

// ─── 6.5) REST endpoint: /wp-json/lga/v1/solicitud-by-code/{code}/shopify-meta
// Llamado por Vercel cuando el draft Shopify se termina de crear (async, en paralelo
// con el notify a n8n para Telegram). Busca la solicitud por application_code y
// setea los shopify_* meta. Si la solicitud todavía no existe (race con n8n que
// está creando el WP post), reintenta hasta 3 veces con backoff corto.
// Auth: shared secret en header X-LGA-Secret (LGA_CRM_FINALIZE_SECRET).
add_action( 'rest_api_init', function () {
    register_rest_route( 'lga/v1', '/solicitud-by-code/(?P<code>[A-Za-z0-9_\-]+)/shopify-meta', array(
        'methods'  => 'POST',
        'permission_callback' => function ( $request ) {
            $expected = defined( 'LGA_CRM_FINALIZE_SECRET' ) ? LGA_CRM_FINALIZE_SECRET : '';
            $got = $request->get_header( 'x-lga-secret' );
            if ( ! $expected || ! $got ) return false;
            return hash_equals( $expected, $got );
        },
        'callback' => function ( $request ) {
            $code = sanitize_text_field( $request['code'] );
            if ( ! $code ) {
                return new WP_REST_Response( array( 'ok' => false, 'error' => 'no_code' ), 400 );
            }

            // Buscar la solicitud por application_code (con retry porque n8n
            // puede estar todavía creando el post en paralelo).
            $post_id = 0;
            for ( $attempt = 0; $attempt < 4; $attempt++ ) {
                $found = get_posts( array(
                    'post_type'      => 'solicitud',
                    'posts_per_page' => 1,
                    'fields'         => 'ids',
                    'meta_query'     => array( array( 'key' => 'application_code', 'value' => $code, 'compare' => '=' ) ),
                ) );
                if ( ! empty( $found ) ) {
                    $post_id = (int) $found[0];
                    break;
                }
                // También probar por título (n8n setea title = application_code)
                $by_title = get_page_by_title( $code, OBJECT, 'solicitud' );
                if ( $by_title ) {
                    $post_id = (int) $by_title->ID;
                    break;
                }
                // Esperar 500ms antes del siguiente intento
                if ( $attempt < 3 ) usleep( 500000 );
            }

            if ( ! $post_id ) {
                return new WP_REST_Response( array( 'ok' => false, 'error' => 'solicitud_not_found', 'code' => $code ), 404 );
            }

            $body = $request->get_json_params() ?: array();
            $allowed = array(
                'shopify_draft_order_id',
                'shopify_draft_order_gid',
                'shopify_draft_order_name',
                'shopify_invoice_url',
                'shopify_status',
            );
            $set = array();
            foreach ( $allowed as $k ) {
                if ( isset( $body[ $k ] ) && $body[ $k ] !== '' ) {
                    update_post_meta( $post_id, $k, sanitize_text_field( $body[ $k ] ) );
                    $set[ $k ] = $body[ $k ];
                }
            }
            update_post_meta( $post_id, 'shopify_last_sync_at', current_time( 'mysql' ) );
            lga_crm_shopify_log( $post_id, 'shopify_meta_from_vercel', $set );

            return new WP_REST_Response( array(
                'ok' => true,
                'post_id' => $post_id,
                'set' => $set,
            ), 200 );
        },
    ) );
} );

// ─── 6) REST endpoint: /wp-json/lga/v1/solicitud/{id}/finalize ─────────────
// Llamado por n8n al final de su workflow (después de setear TODAS las metas)
// para forzar creación inmediata del draft (sin esperar wp-cron lazy).
// Auth: shared secret en header X-LGA-Secret (definido en wp-config como LGA_CRM_FINALIZE_SECRET)
add_action( 'rest_api_init', function () {
    register_rest_route( 'lga/v1', '/solicitud/(?P<id>\d+)/finalize', array(
        'methods'  => 'POST',
        'permission_callback' => function ( $request ) {
            $expected = defined( 'LGA_CRM_FINALIZE_SECRET' ) ? LGA_CRM_FINALIZE_SECRET : '';
            $got = $request->get_header( 'x-lga-secret' );
            if ( ! $expected || ! $got ) return false;
            return hash_equals( $expected, $got );
        },
        'callback' => function ( $request ) {
            $post_id = (int) $request['id'];
            if ( get_post_type( $post_id ) !== 'solicitud' ) {
                return new WP_REST_Response( array( 'ok' => false, 'error' => 'not_solicitud' ), 404 );
            }
            if ( get_post_meta( $post_id, 'shopify_draft_order_id', true ) ) {
                return new WP_REST_Response( array( 'ok' => true, 'skipped' => 'already_exists',
                    'draft_id' => get_post_meta( $post_id, 'shopify_draft_order_id', true ) ), 200 );
            }
            $result = lga_crm_shopify_create_draft_order( $post_id );
            if ( is_wp_error( $result ) ) {
                return new WP_REST_Response( array(
                    'ok' => false,
                    'error' => $result->get_error_code(),
                    'message' => $result->get_error_message(),
                ), 422 );
            }
            return new WP_REST_Response( array(
                'ok' => true,
                'draft_id' => $result['draft_id'] ?? '',
                'draft_name' => $result['name'] ?? '',
            ), 200 );
        },
    ) );
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
