<?php
/**
 * Roles custom: vendedor, cobrador.
 * + Caps custom para los CPTs cliente/credito/lead (capability_type custom).
 * El rol administrator built-in recibe TODAS las caps custom.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function lga_crm_register_roles() {
    // Capabilities granulares por CPT.
    $admin_caps = array(
        // cliente
        'read_cliente'              => true,
        'read_private_clientes'     => true,
        'edit_cliente'              => true,
        'edit_clientes'             => true,
        'edit_others_clientes'      => true,
        'edit_published_clientes'   => true,
        'publish_clientes'          => true,
        'delete_cliente'            => true,
        'delete_clientes'           => true,
        'delete_others_clientes'    => true,
        'delete_published_clientes' => true,
        // credito
        'read_credito'              => true,
        'read_private_creditos'     => true,
        'edit_credito'              => true,
        'edit_creditos'             => true,
        'edit_others_creditos'      => true,
        'edit_published_creditos'   => true,
        'publish_creditos'          => true,
        'delete_credito'            => true,
        'delete_creditos'           => true,
        'delete_others_creditos'    => true,
        'delete_published_creditos' => true,
        // lead
        'read_lead'                 => true,
        'read_private_leads'        => true,
        'edit_lead'                 => true,
        'edit_leads'                => true,
        'edit_others_leads'         => true,
        'edit_published_leads'      => true,
        'publish_leads'             => true,
        'delete_lead'               => true,
        'delete_leads'              => true,
        'delete_others_leads'       => true,
        'delete_published_leads'    => true,
        // acciones LGA
        'lga_approve_lead'          => true,
        'lga_create_cliente'        => true,
        'lga_create_credito'        => true,
        'lga_convert_solicitud'     => true,
    );

    // Vendedor: solo lee leads (filtrado a los suyos por pre_get_posts) y edita los suyos.
    $vendedor_caps = array(
        'read'                      => true,
        'read_lead'                 => true,
        'edit_lead'                 => true,
        'edit_leads'                => true,
        'edit_published_leads'      => true,
        // NO: edit_others_leads, NO: delete_*, NO: approve, NO: clientes/créditos
    );

    // Cobrador: lee clientes propios + créditos del cliente propio. Edita sólo notas/pagos.
    $cobrador_caps = array(
        'read'                      => true,
        'read_cliente'              => true,
        'edit_cliente'              => true,
        'edit_clientes'             => true,
        'edit_published_clientes'   => true,
        'read_credito'              => true,
        'edit_credito'              => true,
        'edit_creditos'             => true,
        'edit_published_creditos'   => true,
    );

    // ── Asignar caps al admin ──
    $admin = get_role( 'administrator' );
    if ( $admin ) {
        foreach ( $admin_caps as $cap => $grant ) {
            $admin->add_cap( $cap, $grant );
        }
    }

    // ── Crear/actualizar rol vendedor ──
    $existing_vend = get_role( 'vendedor' );
    if ( ! $existing_vend ) {
        add_role( 'vendedor', 'Vendedor LGA', $vendedor_caps );
    } else {
        foreach ( $vendedor_caps as $cap => $grant ) {
            $existing_vend->add_cap( $cap, $grant );
        }
    }

    // ── Crear/actualizar rol cobrador ──
    $existing_cob = get_role( 'cobrador' );
    if ( ! $existing_cob ) {
        add_role( 'cobrador', 'Cobrador LGA', $cobrador_caps );
    } else {
        foreach ( $cobrador_caps as $cap => $grant ) {
            $existing_cob->add_cap( $cap, $grant );
        }
    }
}

/**
 * Helper: rol primario del usuario actual ("administrator", "vendedor", "cobrador" o "" si no logueado).
 */
function lga_crm_current_role() {
    if ( ! is_user_logged_in() ) {
        return '';
    }
    $user = wp_get_current_user();
    $roles = (array) $user->roles;
    foreach ( array( 'administrator', 'vendedor', 'cobrador' ) as $r ) {
        if ( in_array( $r, $roles, true ) ) {
            return $r;
        }
    }
    return isset( $roles[0] ) ? $roles[0] : '';
}
