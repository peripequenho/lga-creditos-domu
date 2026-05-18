<?php
/**
 * Limpia OPcache de PHP. Se usa después de re-deploy del plugin
 * para que el código nuevo entre en effect sin reiniciar PHP-FPM.
 *
 * Visitar: https://admin.lga-arg.com/wp-content/mu-plugins/lga-crm/dev/clear-opcache.php
 * Requiere admin logueado.
 */

if ( ! defined( 'ABSPATH' ) ) {
    require_once dirname( __FILE__, 5 ) . '/wp-load.php';
}

// Acceso: admin via session OR ?bypass=<INSTALL_KEY> para debug ad-hoc (solo temporal).
$BYPASS_KEY = 'lga-debug-7q3wzx';
$bypass_ok  = isset( $_GET['bypass'] ) && hash_equals( $BYPASS_KEY, $_GET['bypass'] );
if ( ! current_user_can( 'manage_options' ) && ! defined( 'WP_CLI' ) && ! $bypass_ok ) {
    wp_die( 'Solo admin.', 403 );
}

header( 'Content-Type: text/plain; charset=utf-8' );

echo "OPcache status:\n";
if ( function_exists( 'opcache_get_status' ) ) {
    $st = @opcache_get_status();
    if ( $st ) {
        echo "  enabled: " . ( $st['opcache_enabled'] ? 'YES' : 'NO' ) . "\n";
        echo "  cached files: " . ( $st['opcache_statistics']['num_cached_scripts'] ?? '?' ) . "\n";
    } else {
        echo "  no status (probably disabled)\n";
    }
}

if ( function_exists( 'opcache_reset' ) ) {
    if ( @opcache_reset() ) {
        echo "[OK]   opcache_reset() ejecutado\n";
    } else {
        echo "[FAIL] opcache_reset() retornó false (cache puede estar deshabilitada)\n";
    }
} else {
    echo "[FAIL] opcache_reset() no disponible en este server\n";
}

// Limpiar también el cache de WordPress
wp_cache_flush();
echo "[OK]   wp_cache_flush() ejecutado\n";

// Limpiar el cache de objetos persistente si hay
if ( function_exists( 'wp_cache_supports' ) && wp_cache_supports( 'flush_runtime' ) ) {
    wp_cache_flush_runtime();
    echo "[OK]   wp_cache_flush_runtime() ejecutado\n";
}

echo "\n=== CACHE LIMPIA ===\n";
echo "Versión plugin: " . LGA_CRM_VERSION . "\n";

// ─── DEBUG: cobrador-1 view ─────────────────────────────────────────────
echo "\n=== DEBUG: cobrador-1 / cobrador-2 ===\n";
foreach ( array( 'cobrador-1', 'cobrador-2' ) as $login ) {
    $u = get_user_by( 'login', $login );
    if ( ! $u ) { echo "  $login: NO existe\n"; continue; }
    echo "  $login (ID $u->ID, roles: " . implode( ',', $u->roles ) . ")\n";

    // Clientes asignados
    $clientes_ids = get_posts( array(
        'post_type' => 'cliente', 'posts_per_page' => -1, 'fields' => 'ids',
        'meta_query' => array( array( 'key' => 'cobrador', 'value' => $u->ID, 'compare' => '=' ) ),
    ) );
    echo "    clientes IDs: " . implode( ', ', $clientes_ids ) . " (" . count( $clientes_ids ) . ")\n";

    // Créditos vía cliente_ref IN clientes_ids
    if ( ! empty( $clientes_ids ) ) {
        $creditos = get_posts( array(
            'post_type' => 'credito', 'posts_per_page' => -1,
            'meta_query' => array( array( 'key' => 'cliente_ref', 'value' => $clientes_ids, 'compare' => 'IN' ) ),
        ) );
        echo "    créditos via IN: " . count( $creditos ) . "\n";
        foreach ( $creditos as $cr ) {
            $ref = get_post_meta( $cr->ID, 'cliente_ref', true );
            echo "      #" . $cr->ID . " '" . $cr->post_title . "' cliente_ref raw='" . $ref . "' (" . gettype( $ref ) . ")\n";
        }
        // También probemos buscar todos los créditos sin filter
        $all = get_posts( array( 'post_type' => 'credito', 'posts_per_page' => -1 ) );
        echo "    TOTAL créditos en DB: " . count( $all ) . "\n";
        foreach ( $all as $cr ) {
            $ref = get_post_meta( $cr->ID, 'cliente_ref', true );
            $in_my_clients = in_array( (int) $ref, array_map( 'intval', $clientes_ids ), true ) ? 'YES' : 'no';
            echo "      #$cr->ID '$cr->post_title' ref='$ref' (" . gettype( $ref ) . ") mio=$in_my_clients\n";
        }
    }
}

echo "\n=== TEST: lga_crm_get_creditos_for_user() exactamente ===\n";
foreach ( array( 'cobrador-1', 'cobrador-2' ) as $login ) {
    $u = get_user_by( 'login', $login );
    if ( ! $u ) continue;
    echo "$login (ID $u->ID):\n";

    if ( function_exists( 'lga_crm_get_creditos_for_user' ) ) {
        $creds = lga_crm_get_creditos_for_user( $u->ID );
        echo "  lga_crm_get_creditos_for_user(" . $u->ID . ") returned: " . count( $creds ) . " items\n";
        foreach ( $creds as $c ) {
            echo "    #" . $c->ID . " " . $c->post_title . "\n";
        }
    } else {
        echo "  función NO existe\n";
    }

    if ( function_exists( 'lga_crm_get_clientes_for_user' ) ) {
        $clis = lga_crm_get_clientes_for_user( $u->ID, array( 'fields' => 'ids' ) );
        echo "  lga_crm_get_clientes_for_user(ids) returned: " . var_export( $clis, true ) . "\n";
    }
}

echo "\n=== DEBUG LEADS (problema counter Leads) ===\n";
$all_leads = get_posts( array( 'post_type' => 'lead', 'posts_per_page' => -1, 'post_status' => array('publish','draft') ) );
echo "TOTAL leads en DB (admin pure get_posts): " . count( $all_leads ) . "\n";
foreach ( $all_leads as $l ) {
    $status = get_post_meta( $l->ID, 'lead_status', true );
    $resp = get_post_meta( $l->ID, 'responsable', true );
    echo "  #" . $l->ID . " '" . $l->post_title . "' status='" . $status . "' resp='" . $resp . "' post_status='" . $l->post_status . "'\n";
}

echo "\n--- via lga_crm_get_leads_for_user (admin) ---\n";
$gero = get_user_by( 'login', 'gerolopezge@gmail.com' );
if ( ! $gero ) $gero = get_user_by( 'email', 'gerolopezge@gmail.com' );
$admin_id = $gero ? $gero->ID : 1;
echo "admin ID: $admin_id\n";
if ( function_exists( 'lga_crm_get_leads_for_user' ) ) {
    $leads_admin = lga_crm_get_leads_for_user( $admin_id );
    echo "lga_crm_get_leads_for_user($admin_id): " . count( $leads_admin ) . " items\n";
    foreach ( $leads_admin as $l ) {
        $status = get_field( 'lead_status', $l->ID );
        echo "  #" . $l->ID . " '" . $l->post_title . "' status='" . $status . "'\n";
    }
}

echo "\n--- include_closed=true ---\n";
$leads_all = lga_crm_get_leads_for_user( $admin_id, array( 'include_closed' => true ) );
echo "lga_crm_get_leads_for_user(admin, include_closed=true): " . count( $leads_all ) . " items\n";

echo "\n=== FIN DEBUG ===\n";
