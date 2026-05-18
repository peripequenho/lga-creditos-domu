<?php
/**
 * LGA CRM Installer
 *
 * Sube este archivo a /public_html/wp-content/lga-crm-installer.php
 * Visitá: https://admin.lga-arg.com/wp-content/lga-crm-installer.php?key=<INSTALL_KEY>
 *
 * Hace:
 *   1. Descarga el bundle ZIP del repo GitHub (rama main)
 *   2. Lo extrae en wp-content/mu-plugins/
 *   3. Borra el ZIP temporal y se auto-elimina
 *
 * Tiene un INSTALL_KEY hardcodeado para evitar que cualquiera lo dispare.
 * Rotar antes de subir si vas a publicarlo en el repo.
 */

// ────────────────────────────────────────────────────────────────────
// 1. Auth: requiere ?key=<INSTALL_KEY> en URL
// ────────────────────────────────────────────────────────────────────
$INSTALL_KEY = 'lga-2026-instal-7q3wzx';
$BUNDLE_URL  = 'https://raw.githubusercontent.com/peripequenho/lga-creditos-domu/main/wp/lga-crm-bundle.zip';

header( 'Content-Type: text/plain; charset=utf-8' );

if ( ! isset( $_GET['key'] ) || $_GET['key'] !== $INSTALL_KEY ) {
    http_response_code( 403 );
    echo "Forbidden. Falta ?key=<INSTALL_KEY>.\n";
    exit;
}

function step( $msg, $ok = true ) {
    echo ( $ok ? "[OK]   " : "[FAIL] " ) . $msg . "\n";
    flush();
}

// ────────────────────────────────────────────────────────────────────
// 2. Resolver paths
// ────────────────────────────────────────────────────────────────────
$wp_content = realpath( __DIR__ );
if ( basename( $wp_content ) !== 'wp-content' ) {
    step( "El archivo no está en /wp-content/. Está en: " . $wp_content, false );
    exit;
}
$mu_dir = $wp_content . '/mu-plugins';
step( "wp-content: $wp_content" );
step( "mu-plugins: $mu_dir" );

if ( ! is_dir( $mu_dir ) ) {
    if ( mkdir( $mu_dir, 0755, true ) ) step( "Creado mu-plugins/" );
    else { step( "No pude crear mu-plugins/", false ); exit; }
}

// ────────────────────────────────────────────────────────────────────
// 3. Descargar ZIP
// ────────────────────────────────────────────────────────────────────
step( "Descargando bundle desde GitHub..." );
$ctx = stream_context_create( array( 'http' => array( 'timeout' => 30, 'user_agent' => 'lga-crm-installer/1.0' ) ) );
$zip_bytes = @file_get_contents( $BUNDLE_URL, false, $ctx );
if ( $zip_bytes === false ) {
    step( "Falló el download del ZIP. URL: $BUNDLE_URL", false );
    exit;
}
step( "Descargado: " . strlen( $zip_bytes ) . " bytes" );

$tmp_zip = $wp_content . '/lga-crm-tmp.zip';
file_put_contents( $tmp_zip, $zip_bytes );

// ────────────────────────────────────────────────────────────────────
// 4. Extraer en mu-plugins/
// ────────────────────────────────────────────────────────────────────
$zip = new ZipArchive();
if ( $zip->open( $tmp_zip ) !== true ) {
    step( "No pude abrir el ZIP.", false );
    @unlink( $tmp_zip );
    exit;
}
$count = $zip->numFiles;
if ( ! $zip->extractTo( $mu_dir ) ) {
    step( "Falló extractTo.", false );
    $zip->close();
    @unlink( $tmp_zip );
    exit;
}
$zip->close();
step( "Extraídos $count archivos a $mu_dir" );

// ────────────────────────────────────────────────────────────────────
// 5. Cleanup
// ────────────────────────────────────────────────────────────────────
@unlink( $tmp_zip );
step( "Borrado ZIP temporal" );

// Verificar que el loader esté presente
$loader = $mu_dir . '/lga-crm-loader.php';
$plugin = $mu_dir . '/lga-crm/lga-crm.php';
step( "Loader existe: " . ( file_exists( $loader ) ? "SI" : "NO" ), file_exists( $loader ) );
step( "Plugin existe: " . ( file_exists( $plugin ) ? "SI" : "NO" ), file_exists( $plugin ) );

echo "\n=== INSTALACIÓN COMPLETA ===\n";
echo "El mu-plugin LGA CRM está activo.\n";
echo "Ir a: https://admin.lga-arg.com/wp-login.php → login → /panel/admin\n";
echo "\nNo te olvides de borrar este installer (lga-crm-installer.php) por seguridad.\n";

// Auto-self-delete (opcional, descomentar si querés)
// @unlink( __FILE__ );
// step( "Installer auto-eliminado." );
