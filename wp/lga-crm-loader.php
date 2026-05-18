<?php
/**
 * mu-plugin LOADER.
 *
 * Este archivo va en /wp-content/mu-plugins/lga-crm-loader.php
 * y carga el plugin real desde /wp-content/mu-plugins/lga-crm/lga-crm.php
 *
 * Hostinger no permite escribir mu-plugins desde el admin UI, sólo vía
 * File Manager o SFTP. Subir este loader + la carpeta `lga-crm/`.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$plugin_path = WPMU_PLUGIN_DIR . '/lga-crm/lga-crm.php';
if ( file_exists( $plugin_path ) ) {
    require_once $plugin_path;
}
