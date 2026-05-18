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
 * Helper: badge HTML estilo shadcn (con dot color + ring inset).
 */
function lga_crm_badge( $key, $value ) {
    // Estilo shadcn: bg/10 + text-color + ring-color/20
    $styles = array(
        'lead_status' => array(
            'nuevo'      => 'bg-blue-50 text-blue-700 ring-blue-700/10',
            'en_visita'  => 'bg-amber-50 text-amber-700 ring-amber-700/10',
            'aprobado'   => 'bg-emerald-50 text-emerald-700 ring-emerald-700/10',
            'rechazado'  => 'bg-red-50 text-red-700 ring-red-700/10',
            'perdido'    => 'bg-zinc-100 text-zinc-700 ring-zinc-600/10',
        ),
        'credit_status' => array(
            'pendiente_aprobacion' => 'bg-amber-50 text-amber-700 ring-amber-700/10',
            'activo'     => 'bg-blue-50 text-blue-700 ring-blue-700/10',
            'al_dia'     => 'bg-emerald-50 text-emerald-700 ring-emerald-700/10',
            'en_mora'    => 'bg-red-50 text-red-700 ring-red-700/10',
            'pagado'     => 'bg-teal-50 text-teal-700 ring-teal-700/10',
            'cancelado'  => 'bg-zinc-100 text-zinc-700 ring-zinc-600/10',
        ),
        'client_status' => array(
            'lead'       => 'bg-blue-50 text-blue-700 ring-blue-700/10',
            'activo'     => 'bg-emerald-50 text-emerald-700 ring-emerald-700/10',
            'bloqueado'  => 'bg-red-50 text-red-700 ring-red-700/10',
            'archivado'  => 'bg-zinc-100 text-zinc-700 ring-zinc-600/10',
        ),
        'zone_status' => array(
            'in_zone'      => 'bg-emerald-50 text-emerald-700 ring-emerald-700/10',
            'needs_review' => 'bg-amber-50 text-amber-700 ring-amber-700/10',
            'out_of_zone'  => 'bg-red-50 text-red-700 ring-red-700/10',
        ),
    );
    $cls = $styles[ $key ][ $value ] ?? 'bg-zinc-100 text-zinc-700 ring-zinc-600/10';
    $label = lga_crm_label( $key, $value );
    return '<span class="lga-badge ring-1 ring-inset ' . esc_attr( $cls ) . '">' . esc_html( $label ) . '</span>';
}
