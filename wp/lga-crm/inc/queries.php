<?php
/**
 * Query helpers + filtros automáticos por rol.
 *
 * pre_get_posts: en queries del frontend de los CPTs lead/cliente/credito,
 * filtra a sólo los items donde el current user es el responsable/cobrador.
 * admin ve todo.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function lga_crm_filter_by_role( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }
    if ( ! is_user_logged_in() ) {
        return;
    }
    if ( current_user_can( 'manage_options' ) ) {
        return; // admin ve todo
    }

    $post_type = $query->get( 'post_type' );
    $user_id = get_current_user_id();
    $role = lga_crm_current_role();

    if ( $post_type === 'lead' && $role === 'vendedor' ) {
        $meta_query = (array) $query->get( 'meta_query' );
        $meta_query[] = array(
            'key' => 'responsable',
            'value' => $user_id,
            'compare' => '=',
        );
        $query->set( 'meta_query', $meta_query );
    }

    if ( $post_type === 'cliente' && $role === 'cobrador' ) {
        $meta_query = (array) $query->get( 'meta_query' );
        $meta_query[] = array(
            'key' => 'cobrador',
            'value' => $user_id,
            'compare' => '=',
        );
        $query->set( 'meta_query', $meta_query );
    }

    if ( $post_type === 'credito' && $role === 'cobrador' ) {
        // Los créditos del cobrador se filtran por cliente_ref → cliente.cobrador = current user.
        // Eso es 2 niveles. Lo manejamos en queries explícitas (lga_crm_get_creditos_for_user)
        // en lugar de via meta_query nativa.
    }
}

/**
 * Listar leads del current user (o todos si admin).
 */
function lga_crm_get_leads_for_user( $user_id = null, $args = array() ) {
    if ( $user_id === null ) {
        $user_id = get_current_user_id();
    }
    $is_admin = user_can( $user_id, 'manage_options' );

    $defaults = array(
        'post_type'      => 'lead',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'post_status'    => array( 'publish', 'draft' ),
    );
    $q = wp_parse_args( $args, $defaults );

    if ( ! $is_admin ) {
        $q['meta_query'] = array(
            array( 'key' => 'responsable', 'value' => $user_id, 'compare' => '=' ),
        );
    }
    return get_posts( $q );
}

/**
 * Listar clientes del current user (o todos si admin).
 */
function lga_crm_get_clientes_for_user( $user_id = null, $args = array() ) {
    if ( $user_id === null ) {
        $user_id = get_current_user_id();
    }
    $is_admin = user_can( $user_id, 'manage_options' );

    $defaults = array(
        'post_type'      => 'cliente',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => array( 'publish', 'draft' ),
    );
    $q = wp_parse_args( $args, $defaults );

    if ( ! $is_admin ) {
        $q['meta_query'] = array(
            array( 'key' => 'cobrador', 'value' => $user_id, 'compare' => '=' ),
        );
    }
    return get_posts( $q );
}

/**
 * Listar créditos del current user (vía cliente_ref → cliente.cobrador).
 */
function lga_crm_get_creditos_for_user( $user_id = null, $args = array() ) {
    if ( $user_id === null ) {
        $user_id = get_current_user_id();
    }
    $is_admin = user_can( $user_id, 'manage_options' );

    if ( $is_admin ) {
        $defaults = array(
            'post_type'      => 'credito',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => array( 'publish', 'draft' ),
        );
        return get_posts( wp_parse_args( $args, $defaults ) );
    }

    // cobrador: primero traemos clientes, después créditos.
    // OJO: con 'fields' => 'ids', get_posts() ya devuelve array de IDs (no objetos),
    // así que NO hay que aplicar wp_list_pluck.
    $cliente_ids = lga_crm_get_clientes_for_user( $user_id, array( 'fields' => 'ids' ) );
    if ( empty( $cliente_ids ) ) {
        return array();
    }
    $defaults = array(
        'post_type'      => 'credito',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'post_status'    => array( 'publish', 'draft' ),
        'meta_query'     => array(
            array( 'key' => 'cliente_ref', 'value' => $cliente_ids, 'compare' => 'IN' ),
        ),
    );
    return get_posts( wp_parse_args( $args, $defaults ) );
}

/**
 * Listar solicitudes pendientes (no convertidas todavía).
 */
function lga_crm_get_pending_solicitudes( $args = array() ) {
    $defaults = array(
        'post_type'      => 'solicitud',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'post_status'    => array( 'publish' ),
        'meta_query'     => array(
            'relation' => 'OR',
            array( 'key' => 'application_status', 'value' => array( 'submitted', 'in_review', 'validating' ), 'compare' => 'IN' ),
            array( 'key' => 'application_status', 'compare' => 'NOT EXISTS' ),
        ),
    );
    return get_posts( wp_parse_args( $args, $defaults ) );
}

/**
 * Helper: count.
 */
function lga_crm_count( $post_type, $extra = array() ) {
    $args = wp_parse_args( $extra, array(
        'post_type'      => $post_type,
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
    ) );
    $q = new WP_Query( $args );
    return (int) $q->found_posts;
}

/**
 * Helper: formato money ARS.
 */
function lga_crm_money( $value ) {
    $n = (float) $value;
    return '$' . number_format( $n, 0, ',', '.' );
}

/**
 * Helper: label de estados para mostrar.
 */
function lga_crm_label( $key, $value ) {
    $labels = array(
        'lead_status' => array(
            'nuevo' => 'Nuevo', 'en_visita' => 'En visita',
            'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado', 'perdido' => 'Perdido',
        ),
        'credit_status' => array(
            'pendiente_aprobacion' => 'Pendiente', 'activo' => 'Activo', 'al_dia' => 'Al día',
            'en_mora' => 'En mora', 'pagado' => 'Pagado', 'cancelado' => 'Cancelado',
        ),
        'client_status' => array(
            'lead' => 'Lead', 'activo' => 'Activo', 'bloqueado' => 'Bloqueado', 'archivado' => 'Archivado',
        ),
        'zone_status' => array(
            'in_zone' => 'En zona', 'needs_review' => 'A revisar', 'out_of_zone' => 'Fuera de zona',
        ),
        'origen' => array(
            'web' => 'Web Shopify', 'manual' => 'Manual', 'migracion' => 'Migración',
        ),
    );
    if ( isset( $labels[ $key ][ $value ] ) ) {
        return $labels[ $key ][ $value ];
    }
    return $value ?: '—';
}

/**
 * Helper: badge HTML coloreado según status.
 */
function lga_crm_badge( $key, $value ) {
    $colors = array(
        'lead_status' => array(
            'nuevo' => 'bg-blue-100 text-blue-800', 'en_visita' => 'bg-yellow-100 text-yellow-800',
            'aprobado' => 'bg-green-100 text-green-800', 'rechazado' => 'bg-red-100 text-red-800',
            'perdido' => 'bg-gray-100 text-gray-800',
        ),
        'credit_status' => array(
            'pendiente_aprobacion' => 'bg-yellow-100 text-yellow-800', 'activo' => 'bg-blue-100 text-blue-800',
            'al_dia' => 'bg-green-100 text-green-800', 'en_mora' => 'bg-red-100 text-red-800',
            'pagado' => 'bg-emerald-100 text-emerald-800', 'cancelado' => 'bg-gray-100 text-gray-800',
        ),
        'client_status' => array(
            'lead' => 'bg-blue-100 text-blue-800', 'activo' => 'bg-green-100 text-green-800',
            'bloqueado' => 'bg-red-100 text-red-800', 'archivado' => 'bg-gray-100 text-gray-800',
        ),
        'zone_status' => array(
            'in_zone' => 'bg-green-100 text-green-800', 'needs_review' => 'bg-yellow-100 text-yellow-800',
            'out_of_zone' => 'bg-red-100 text-red-800',
        ),
    );
    $color = $colors[ $key ][ $value ] ?? 'bg-gray-100 text-gray-800';
    $label = lga_crm_label( $key, $value );
    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ' . esc_attr( $color ) . '">' . esc_html( $label ) . '</span>';
}
