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
        // Bug fix v0.3.9: sanitizar REQUEST_URI antes de meterlo en la URL de login.
        $req_uri = isset( $_SERVER['REQUEST_URI'] )
            ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) )
            : '/panel';
        wp_safe_redirect( wp_login_url( home_url( $req_uri ) ) );
        exit;
    }

    $id = (int) get_query_var( 'lga_id' );
    $role = lga_crm_current_role();

    // Routing tabla — se chequea por ROL (no por cap individual) para evitar
    // problemas de map_meta_cap con CPTs que tienen capability_type custom.
    // Caps granulares se verifican adentro de los templates si hace falta.
    $map = array(
        'panel'              => array( 'tpl' => 'panel-router.php',     'roles' => array( 'administrator', 'vendedor', 'cobrador' ) ),
        'panel-admin'        => array( 'tpl' => 'panel-admin.php',      'roles' => array( 'administrator' ) ),
        'panel-vendedor'     => array( 'tpl' => 'panel-vendedor.php',   'roles' => array( 'administrator', 'vendedor' ) ),
        'panel-cobrador'     => array( 'tpl' => 'panel-cobrador.php',   'roles' => array( 'administrator', 'cobrador' ) ),
        'lead-detail'        => array( 'tpl' => 'lead-detail.php',      'roles' => array( 'administrator', 'vendedor' ) ),
        'cliente-detail'     => array( 'tpl' => 'cliente-detail.php',   'roles' => array( 'administrator', 'cobrador' ) ),
        'credito-detail'     => array( 'tpl' => 'credito-detail.php',   'roles' => array( 'administrator', 'cobrador' ) ),
        'cliente-nuevo'      => array( 'tpl' => 'cliente-nuevo.php',    'roles' => array( 'administrator' ) ),
        'credito-asignar'    => array( 'tpl' => 'credito-asignar.php',  'roles' => array( 'administrator' ) ),
        'aprobar-solicitud'  => array( 'tpl' => 'aprobar-solicitud.php','roles' => array( 'administrator' ) ),
        'promover-lead'      => array( 'tpl' => 'promover-lead.php',    'roles' => array( 'administrator' ) ),
    );

    if ( ! isset( $map[ $route ] ) ) {
        return;
    }

    $entry = $map[ $route ];

    // Permission check by role (no map_meta_cap interference)
    $can = in_array( $role, $entry['roles'], true );
    if ( ! $can ) {
        // Bug fix v0.3.9: si el rol NO está en {administrator, vendedor, cobrador},
        // mostrar 403 en lugar de redirigir a /panel/admin (que rebota y crea loop infinito).
        $known_roles = array( 'administrator', 'vendedor', 'cobrador' );
        if ( ! in_array( $role, $known_roles, true ) ) {
            status_header( 403 );
            wp_die( 'Tu rol (' . esc_html( $role ?: 'sin rol' ) . ') no tiene acceso al panel LGA. Contactá al admin.', 'Acceso denegado', array( 'response' => 403 ) );
        }
        // No loop a /panel: si ya estaba intentando entrar a /panel y falló (no debería),
        // mostrar 403 directo. Si entró a otra ruta, llevarlo a su panel default.
        if ( $route === 'panel' ) {
            status_header( 403 );
            wp_die( 'No tenés permiso para acceder a esta sección.', 'Acceso denegado', array( 'response' => 403 ) );
        }
        // Mandar a su panel correspondiente sin pasar por /panel router (evita loop)
        $target = '/panel/admin';
        if ( $role === 'vendedor' ) $target = '/panel/vendedor';
        elseif ( $role === 'cobrador' ) $target = '/panel/cobrador';
        wp_safe_redirect( home_url( $target ) );
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
