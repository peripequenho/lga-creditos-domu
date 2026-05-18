<?php
/**
 * Routing: rewrites + template_redirect.
 * URLs:
 *   /panel                              → panel-{rol}.php
 *   /panel/admin                        → panel-admin.php (admin only)
 *   /panel/vendedor                     → panel-vendedor.php (vendedor/admin)
 *   /panel/cobrador                     → panel-cobrador.php (cobrador/admin)
 *   /lead/<id>                          → lead-detail.php
 *   /cliente/<id>                       → cliente-detail.php
 *   /credito/<id>                       → credito-detail.php
 *   /admin/nuevo-cliente                → cliente-nuevo.php (admin only)
 *   /admin/cliente/<id>/asignar-credito → credito-asignar.php (admin only)
 *   /admin/aprobar-solicitud/<id>       → aprobar-solicitud.php (admin only)
 *   /admin/promover-lead/<id>           → promover-lead.php (admin only)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function lga_crm_register_rewrites() {
    // /panel and /panel/{section}
    add_rewrite_rule( '^panel/?$', 'index.php?lga_route=panel', 'top' );
    add_rewrite_rule( '^panel/(admin|vendedor|cobrador)/?$', 'index.php?lga_route=panel-$matches[1]', 'top' );

    // Detail pages
    add_rewrite_rule( '^lead/([0-9]+)/?$', 'index.php?lga_route=lead-detail&lga_id=$matches[1]', 'top' );
    add_rewrite_rule( '^cliente/([0-9]+)/?$', 'index.php?lga_route=cliente-detail&lga_id=$matches[1]', 'top' );
    add_rewrite_rule( '^credito/([0-9]+)/?$', 'index.php?lga_route=credito-detail&lga_id=$matches[1]', 'top' );

    // Admin actions
    add_rewrite_rule( '^admin/nuevo-cliente/?$', 'index.php?lga_route=cliente-nuevo', 'top' );
    add_rewrite_rule( '^admin/cliente/([0-9]+)/asignar-credito/?$', 'index.php?lga_route=credito-asignar&lga_id=$matches[1]', 'top' );
    add_rewrite_rule( '^admin/aprobar-solicitud/([0-9]+)/?$', 'index.php?lga_route=aprobar-solicitud&lga_id=$matches[1]', 'top' );
    add_rewrite_rule( '^admin/promover-lead/([0-9]+)/?$', 'index.php?lga_route=promover-lead&lga_id=$matches[1]', 'top' );
}

/**
 * Template_redirect: si la query var lga_route está seteada, cargamos nuestro template
 * en vez del template del theme.
 */
function lga_crm_router() {
    $route = get_query_var( 'lga_route' );
    if ( empty( $route ) ) {
        return;
    }

    // Auth gate
    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( wp_login_url( home_url( $_SERVER['REQUEST_URI'] ?? '/panel' ) ) );
        exit;
    }

    $id = (int) get_query_var( 'lga_id' );
    $role = lga_crm_current_role();

    // Routing tabla
    $map = array(
        'panel'              => array( 'tpl' => 'panel-router.php',     'caps' => array( 'read' ) ),
        'panel-admin'        => array( 'tpl' => 'panel-admin.php',      'caps' => array( 'manage_options' ) ),
        'panel-vendedor'     => array( 'tpl' => 'panel-vendedor.php',   'caps' => array( 'read_lead' ) ),
        'panel-cobrador'     => array( 'tpl' => 'panel-cobrador.php',   'caps' => array( 'read_cliente' ) ),
        'lead-detail'        => array( 'tpl' => 'lead-detail.php',      'caps' => array( 'read_lead' ) ),
        'cliente-detail'     => array( 'tpl' => 'cliente-detail.php',   'caps' => array( 'read_cliente' ) ),
        'credito-detail'     => array( 'tpl' => 'credito-detail.php',   'caps' => array( 'read_credito' ) ),
        'cliente-nuevo'      => array( 'tpl' => 'cliente-nuevo.php',    'caps' => array( 'lga_create_cliente' ) ),
        'credito-asignar'    => array( 'tpl' => 'credito-asignar.php',  'caps' => array( 'lga_create_credito' ) ),
        'aprobar-solicitud'  => array( 'tpl' => 'aprobar-solicitud.php','caps' => array( 'lga_convert_solicitud' ) ),
        'promover-lead'      => array( 'tpl' => 'promover-lead.php',    'caps' => array( 'lga_approve_lead' ) ),
    );

    if ( ! isset( $map[ $route ] ) ) {
        return;
    }

    $entry = $map[ $route ];

    // Permission check (any-of)
    $can = false;
    foreach ( $entry['caps'] as $cap ) {
        if ( current_user_can( $cap ) ) { $can = true; break; }
    }
    if ( ! $can ) {
        wp_safe_redirect( home_url( '/panel' ) );
        exit;
    }

    // Pass id + route into template scope
    $GLOBALS['lga_route'] = $route;
    $GLOBALS['lga_id']    = $id;

    $tpl_path = LGA_CRM_DIR . 'templates/' . $entry['tpl'];
    if ( file_exists( $tpl_path ) ) {
        // Render
        status_header( 200 );
        nocache_headers();
        require $tpl_path;
        exit;
    }

    // Template missing — degradar a 404
    status_header( 404 );
    echo '<h1>404 — Template no encontrado: ' . esc_html( $entry['tpl'] ) . '</h1>';
    exit;
}
