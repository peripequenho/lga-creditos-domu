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

if ( ! current_user_can( 'manage_options' ) && ! defined( 'WP_CLI' ) ) {
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
echo "Recargá las páginas del panel — el código v" . LGA_CRM_VERSION . " ya está activo.\n";
