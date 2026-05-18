<?php
/**
 * Handlers de form POST (admin-post.php).
 * Cada uno valida nonce + caps + datos, crea/actualiza posts y redirige.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Generador de application_code para clientes/créditos/leads.
 */
function lga_crm_next_code( $prefix ) {
    $today = wp_date( 'ymd' );
    $key = 'lga_crm_seq_' . $prefix . '_' . $today;
    $n = (int) get_option( $key, 0 ) + 1;
    update_option( $key, $n );
    return sprintf( '%s-%s-%04d', strtoupper( $prefix ), $today, $n );
}

// ─── Alta cliente manual ────────────────────────────────────────────
function lga_crm_handle_create_cliente() {
    if ( ! current_user_can( 'lga_create_cliente' ) ) {
        wp_die( 'Sin permisos.', 403 );
    }
    check_admin_referer( 'lga_create_cliente' );

    $first = sanitize_text_field( $_POST['first_name'] ?? '' );
    $last  = sanitize_text_field( $_POST['last_name'] ?? '' );
    $dni   = preg_replace( '/\D/', '', $_POST['dni'] ?? '' );
    $phone = sanitize_text_field( $_POST['phone'] ?? '' );

    if ( ! $first || ! $last || ! $dni ) {
        wp_safe_redirect( add_query_arg( 'err', 'missing_required', home_url( '/admin/nuevo-cliente' ) ) );
        exit;
    }

    // Verificar duplicado por DNI → redirect a LISTADO con flash
    $existing = get_posts( array(
        'post_type' => 'cliente',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => array( array( 'key' => 'dni', 'value' => $dni, 'compare' => '=' ) ),
    ) );
    if ( ! empty( $existing ) ) {
        wp_safe_redirect( home_url( '/panel/admin/?tab=clientes&new=' . $existing[0] . '&msg=existing' ) );
        exit;
    }

    $title = $last . ', ' . $first . ' · DNI ' . $dni;
    $post_id = wp_insert_post( array(
        'post_type'   => 'cliente',
        'post_title'  => $title,
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
    ) );

    if ( is_wp_error( $post_id ) || ! $post_id ) {
        wp_die( 'Error creando cliente.' );
    }

    // Meta
    update_field( 'first_name', $first, $post_id );
    update_field( 'last_name', $last, $post_id );
    update_field( 'dni', $dni, $post_id );
    update_field( 'phone', $phone, $post_id );
    update_field( 'birth_date', sanitize_text_field( $_POST['birth_date'] ?? '' ), $post_id );
    update_field( 'email', sanitize_email( $_POST['email'] ?? '' ), $post_id );
    update_field( 'address_line', sanitize_text_field( $_POST['address_line'] ?? '' ), $post_id );
    update_field( 'locality', sanitize_text_field( $_POST['locality'] ?? '' ), $post_id );
    update_field( 'province', sanitize_text_field( $_POST['province'] ?? 'Tucumán' ), $post_id );
    update_field( 'postal_code', sanitize_text_field( $_POST['postal_code'] ?? '' ), $post_id );
    update_field( 'occupation', sanitize_text_field( $_POST['occupation'] ?? '' ), $post_id );
    update_field( 'declared_income_ars', (int) ( $_POST['declared_income_ars'] ?? 0 ), $post_id );
    update_field( 'client_status', 'lead', $post_id );
    update_field( 'origen', 'manual', $post_id );
    update_field( 'internal_notes', sanitize_textarea_field( $_POST['internal_notes'] ?? '' ), $post_id );

    $cobrador = (int) ( $_POST['cobrador'] ?? 0 );
    if ( $cobrador ) {
        update_field( 'cobrador', $cobrador, $post_id );
    }

    // Redirect al LISTADO (no a la ficha) → browser back NO vuelve al form
    wp_safe_redirect( home_url( '/panel/admin/?tab=clientes&new=' . $post_id . '&msg=created' ) );
    exit;
}

// ─── Alta crédito manual (ligado a cliente existente) ──────────────
function lga_crm_handle_create_credito() {
    if ( ! current_user_can( 'lga_create_credito' ) ) {
        wp_die( 'Sin permisos.', 403 );
    }
    check_admin_referer( 'lga_create_credito' );

    $cliente_id = (int) ( $_POST['cliente_id'] ?? 0 );
    if ( ! $cliente_id || get_post_type( $cliente_id ) !== 'cliente' ) {
        wp_die( 'Cliente inválido.', 400 );
    }

    $monto = (float) ( $_POST['monto_ars'] ?? 0 );
    $cuotas = (int) ( $_POST['cuotas_totales'] ?? 0 );
    $freq = sanitize_text_field( $_POST['payment_frequency'] ?? 'monthly' );
    if ( $monto <= 0 || $cuotas <= 0 ) {
        wp_safe_redirect( add_query_arg( 'err', 'invalid_amounts', home_url( '/admin/cliente/' . $cliente_id . '/asignar-credito' ) ) );
        exit;
    }

    $code = lga_crm_next_code( 'CR' );

    $post_id = wp_insert_post( array(
        'post_type'   => 'credito',
        'post_title'  => $code,
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
    ) );
    if ( is_wp_error( $post_id ) || ! $post_id ) {
        wp_die( 'Error creando crédito.' );
    }

    update_field( 'cliente_ref', $cliente_id, $post_id );
    update_field( 'monto_ars', $monto, $post_id );
    update_field( 'cuotas_totales', $cuotas, $post_id );
    update_field( 'payment_frequency', $freq, $post_id );
    update_field( 'cuota_estimada_ars', (float) ( $_POST['cuota_estimada_ars'] ?? 0 ), $post_id );
    update_field( 'tasa_aplicada', (float) ( $_POST['tasa_aplicada'] ?? 0 ), $post_id );
    update_field( 'fecha_alta', wp_date( 'Y-m-d' ), $post_id );
    update_field( 'credit_status', 'pendiente_aprobacion', $post_id );
    update_field( 'cuotas_pagadas', 0, $post_id );
    update_field( 'saldo_ars', $monto, $post_id );
    update_field( 'internal_notes', sanitize_textarea_field( $_POST['internal_notes'] ?? '' ), $post_id );

    // Activar el cliente si estaba en estado lead
    if ( get_field( 'client_status', $cliente_id ) === 'lead' ) {
        update_field( 'client_status', 'activo', $cliente_id );
    }

    // Redirect al LISTADO de créditos
    wp_safe_redirect( home_url( '/panel/admin/?tab=creditos&new=' . $post_id . '&msg=created' ) );
    exit;
}

// ─── Cambiar estado lead (vendedor/admin) ──────────────────────────
// Si new_status === 'aprobado', AUTO-PROMUEVE a cliente + crédito.
function lga_crm_handle_update_lead_status() {
    $lead_id = (int) ( $_POST['lead_id'] ?? 0 );
    if ( ! $lead_id || get_post_type( $lead_id ) !== 'lead' ) {
        wp_die( 'Lead inválido.', 400 );
    }
    if ( ! current_user_can( 'edit_lead', $lead_id ) ) {
        // Vendedor: solo puede si es su lead
        $responsable = (int) get_field( 'responsable', $lead_id );
        if ( $responsable !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Sin permisos.', 403 );
        }
    }
    check_admin_referer( 'lga_update_lead_status_' . $lead_id );

    $new_status = sanitize_text_field( $_POST['lead_status'] ?? '' );
    $valid = array( 'nuevo', 'en_visita', 'aprobado', 'rechazado', 'perdido' );
    if ( ! in_array( $new_status, $valid, true ) ) {
        wp_die( 'Estado inválido.', 400 );
    }

    update_field( 'lead_status', $new_status, $lead_id );

    $notes = sanitize_textarea_field( $_POST['internal_notes'] ?? '' );
    if ( $notes ) {
        $current_notes = (string) get_field( 'internal_notes', $lead_id );
        $stamp = '[' . wp_date( 'd/m/Y H:i' ) . ' · ' . wp_get_current_user()->display_name . '] ';
        $new_notes = trim( $current_notes . "\n" . $stamp . $notes );
        update_field( 'internal_notes', $new_notes, $lead_id );
    }

    // Si el nuevo estado es 'aprobado', auto-promover a cliente + crédito.
    if ( $new_status === 'aprobado' ) {
        $result = lga_crm_promote_lead_to_client_credit( $lead_id, array() );
        if ( is_wp_error( $result ) ) {
            // No interrumpimos el flujo, dejamos el lead como aprobado y mensaje de error.
            wp_safe_redirect( add_query_arg( 'err', 'promote_failed', home_url( '/lead/' . $lead_id . '/' ) ) );
            exit;
        }
        // Redirect según rol al listado del rol
        $role = lga_crm_current_role();
        if ( $role === 'vendedor' ) {
            wp_safe_redirect( home_url( '/panel/vendedor/?msg=promoted&credit=' . $result['credito_id'] ) );
        } else {
            wp_safe_redirect( home_url( '/panel/admin/?tab=creditos&new=' . $result['credito_id'] . '&msg=promoted' ) );
        }
        exit;
    }

    // Rechazado / perdido / en_visita / nuevo → volver al listado del rol (no a la ficha)
    $role = lga_crm_current_role();
    if ( in_array( $new_status, array( 'rechazado', 'perdido' ), true ) ) {
        // Estados terminales: vuelve al listado
        $target = ( $role === 'vendedor' ) ? '/panel/vendedor/' : '/panel/admin/?tab=leads';
        wp_safe_redirect( home_url( $target . ( strpos( $target, '?' ) === false ? '?' : '&' ) . 'msg=updated' ) );
        exit;
    }
    // Estados intermedios (nuevo, en_visita): quedarse en la ficha del lead
    wp_safe_redirect( home_url( '/lead/' . $lead_id . '/?msg=updated' ) );
    exit;
}

/**
 * Crea cliente + crédito a partir de un lead.
 * Idempotente: si ya existe cliente con ese DNI, lo reutiliza.
 * Returns array('cliente_id'=>X, 'credito_id'=>Y) o WP_Error.
 */
function lga_crm_promote_lead_to_client_credit( $lead_id, $args = array() ) {
    $dni = preg_replace( '/\D/', '', (string) get_field( 'dni', $lead_id ) );
    if ( ! $dni ) {
        return new WP_Error( 'no_dni', 'Lead sin DNI, no se puede promover.' );
    }

    // 1) Buscar cliente existente por DNI
    $existing = get_posts( array(
        'post_type' => 'cliente', 'posts_per_page' => 1, 'fields' => 'ids',
        'meta_query' => array( array( 'key' => 'dni', 'value' => $dni, 'compare' => '=' ) ),
    ) );

    if ( ! empty( $existing ) ) {
        $cliente_id = $existing[0];
    } else {
        $first = get_field( 'first_name', $lead_id );
        $last  = get_field( 'last_name', $lead_id );
        $title = $last . ', ' . $first . ' · DNI ' . $dni;
        $cliente_id = wp_insert_post( array(
            'post_type' => 'cliente',
            'post_title' => $title,
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ) );
        if ( is_wp_error( $cliente_id ) || ! $cliente_id ) {
            return new WP_Error( 'cliente_insert_failed', 'No se pudo crear cliente.' );
        }
        $copy = array( 'first_name', 'last_name', 'dni', 'birth_date', 'phone', 'email',
                       'address_line', 'locality', 'province', 'postal_code',
                       'declared_income_ars' );
        foreach ( $copy as $f ) {
            $v = get_field( $f, $lead_id );
            if ( $v !== false && $v !== null ) update_field( $f, $v, $cliente_id );
        }
        update_field( 'client_status', 'activo', $cliente_id );
        update_field( 'origen', get_field( 'origen', $lead_id ) ?: 'web', $cliente_id );
        update_field( 'lead_ref', $lead_id, $cliente_id );
    }

    // 2) Cobrador (si vino en args o en el lead via meta)
    $cobrador = (int) ( $args['cobrador'] ?? 0 );
    if ( ! $cobrador ) {
        $cobrador = (int) get_field( 'cobrador_sugerido', $lead_id );
    }
    if ( $cobrador ) update_field( 'cobrador', $cobrador, $cliente_id );

    // 3) Crear crédito
    $monto  = (float) ( $args['monto'] ?? get_field( 'requested_amount_ars', $lead_id ) );
    $cuotas = (int) ( $args['cuotas'] ?? get_field( 'requested_installments', $lead_id ) );
    $freq   = sanitize_text_field( $args['freq'] ?? ( get_field( 'payment_frequency', $lead_id ) ?: 'monthly' ) );

    $code = lga_crm_next_code( 'CR' );
    $credito_id = wp_insert_post( array(
        'post_type' => 'credito',
        'post_title' => $code,
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
    ) );
    if ( is_wp_error( $credito_id ) || ! $credito_id ) {
        return new WP_Error( 'credito_insert_failed', 'No se pudo crear crédito.' );
    }
    update_field( 'cliente_ref', $cliente_id, $credito_id );
    update_field( 'monto_ars', $monto, $credito_id );
    update_field( 'cuotas_totales', $cuotas, $credito_id );
    update_field( 'payment_frequency', $freq, $credito_id );
    update_field( 'credit_status', 'activo', $credito_id );
    update_field( 'fecha_alta', wp_date( 'Y-m-d' ), $credito_id );
    update_field( 'saldo_ars', $monto, $credito_id );
    update_field( 'cuotas_pagadas', 0, $credito_id );
    update_field( 'lead_ref', $lead_id, $credito_id );

    // 4) Asegurar que el lead queda en 'aprobado' para que el filtro lo oculte
    update_field( 'lead_status', 'aprobado', $lead_id );
    // Guardar refs en el lead
    update_field( 'cliente_ref', $cliente_id, $lead_id );
    update_field( 'credito_ref', $credito_id, $lead_id );

    return array( 'cliente_id' => $cliente_id, 'credito_id' => $credito_id );
}

// ─── Convertir solicitud → lead (admin) ────────────────────────────
function lga_crm_handle_convert_solicitud() {
    if ( ! current_user_can( 'lga_convert_solicitud' ) ) {
        wp_die( 'Sin permisos.', 403 );
    }
    $sol_id = (int) ( $_POST['solicitud_id'] ?? 0 );
    if ( ! $sol_id || get_post_type( $sol_id ) !== 'solicitud' ) {
        wp_die( 'Solicitud inválida.', 400 );
    }
    check_admin_referer( 'lga_convert_solicitud_' . $sol_id );

    // Idempotente: si ya hay un lead vinculado, redirigir al listado de leads
    $existing_lead = get_posts( array(
        'post_type' => 'lead', 'posts_per_page' => 1, 'fields' => 'ids',
        'meta_query' => array( array( 'key' => 'solicitud_ref', 'value' => $sol_id, 'compare' => '=' ) ),
    ) );
    if ( ! empty( $existing_lead ) ) {
        // Asegurar que la solicitud quede marcada como convertida (por si quedó vieja)
        update_field( 'application_status', 'in_review', $sol_id );
        wp_safe_redirect( home_url( '/panel/admin/?tab=leads&new=' . $existing_lead[0] . '&msg=already_converted' ) );
        exit;
    }

    $code = get_field( 'application_code', $sol_id );
    if ( ! $code ) {
        $code = get_the_title( $sol_id );
    }
    $lead_title = 'LEAD · ' . ( $code ?: 'sol-' . $sol_id );

    $lead_id = wp_insert_post( array(
        'post_type' => 'lead',
        'post_title' => $lead_title,
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
    ) );

    // Copiar campos de solicitud → lead
    $copy_fields = array(
        'first_name', 'last_name', 'dni', 'birth_date', 'phone', 'email',
        'address_line', 'locality', 'province', 'postal_code',
        'requested_amount_ars', 'payment_frequency', 'requested_installments', 'declared_income_ars',
        'zone_status', 'utm_source', 'utm_campaign', 'product_title',
    );
    foreach ( $copy_fields as $f ) {
        $val = get_field( $f, $sol_id );
        if ( $val !== false && $val !== null ) {
            update_field( $f, $val, $lead_id );
        }
    }
    update_field( 'lead_status', 'nuevo', $lead_id );
    update_field( 'origen', 'web', $lead_id );
    update_field( 'solicitud_ref', $sol_id, $lead_id );

    // Asignar responsable (vendedor) si vino en el POST
    $responsable = (int) ( $_POST['responsable'] ?? 0 );
    if ( $responsable ) {
        update_field( 'responsable', $responsable, $lead_id );
    }

    // CLAVE: marcar la solicitud como 'in_review' para que se OCULTE del tab pendientes
    update_field( 'application_status', 'in_review', $sol_id );
    update_field( 'lead_ref', $lead_id, $sol_id );

    // Redirect al LISTADO de leads (no a la ficha) → browser back NO vuelve al modal
    wp_safe_redirect( home_url( '/panel/admin/?tab=leads&new=' . $lead_id . '&msg=converted' ) );
    exit;
}

// ─── Promover lead → cliente + crédito (admin, manual desde form) ──
function lga_crm_handle_promote_lead() {
    if ( ! current_user_can( 'lga_approve_lead' ) ) {
        wp_die( 'Sin permisos.', 403 );
    }
    $lead_id = (int) ( $_POST['lead_id'] ?? 0 );
    if ( ! $lead_id || get_post_type( $lead_id ) !== 'lead' ) {
        wp_die( 'Lead inválido.', 400 );
    }
    check_admin_referer( 'lga_promote_lead_' . $lead_id );

    $args = array(
        'cobrador' => (int) ( $_POST['cobrador'] ?? 0 ),
        'monto'    => (float) ( $_POST['monto_ars'] ?? 0 ),
        'cuotas'   => (int) ( $_POST['cuotas_totales'] ?? 0 ),
        'freq'     => sanitize_text_field( $_POST['payment_frequency'] ?? '' ),
    );

    $result = lga_crm_promote_lead_to_client_credit( $lead_id, $args );
    if ( is_wp_error( $result ) ) {
        wp_die( $result->get_error_message() );
    }

    // Redirect al LISTADO de créditos → browser back NO vuelve al form de promoción
    wp_safe_redirect( home_url( '/panel/admin/?tab=creditos&new=' . $result['credito_id'] . '&msg=promoted' ) );
    exit;
}
