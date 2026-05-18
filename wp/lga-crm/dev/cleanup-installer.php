<?php
/**
 * Cleanup: elimina el installer público y se auto-elimina.
 *
 * Visitar: https://admin.lga-arg.com/wp-content/mu-plugins/lga-crm/dev/cleanup-installer.php
 * Requiere admin logueado.
 */

if ( ! defined( 'ABSPATH' ) ) {
    require_once dirname( __FILE__, 5 ) . '/wp-load.php';
}

if ( ! current_user_can( 'manage_options' ) && ! defined( 'WP_CLI' ) ) {
    wp_die( 'Solo admin.', 403 );
}

header( 'Content-Type: text/plain; charset=utf-8' );

$wp_content = WP_CONTENT_DIR;
$installer  = $wp_content . '/lga-crm-installer.php';

echo "wp_content: $wp_content\n";
echo "installer:  $installer\n";
echo "exists:     " . ( file_exists( $installer ) ? 'SI' : 'NO' ) . "\n";

if ( file_exists( $installer ) ) {
    if ( @unlink( $installer ) ) {
        echo "[OK]   Installer eliminado\n";
    } else {
        echo "[FAIL] No se pudo eliminar (revisar permisos del archivo)\n";
    }
} else {
    echo "[OK]   Installer ya no existe (nada que hacer)\n";
}

// Auto-eliminarse también
$self = __FILE__;
echo "\nself:       $self\n";
if ( @unlink( $self ) ) {
    echo "[OK]   cleanup-installer.php auto-eliminado\n";
} else {
    echo "[FAIL] No se pudo auto-eliminar (revisar permisos)\n";
}

echo "\n=== CLEANUP DONE ===\n";
