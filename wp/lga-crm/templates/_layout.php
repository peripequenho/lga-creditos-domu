<?php
/**
 * Layout compartido para todos los templates LGA-CRM.
 * Uso: lga_crm_layout_open('Título de la página'); ... contenido ...; lga_crm_layout_close();
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function lga_crm_layout_open( $title = 'LGA · Panel' ) {
    $user = wp_get_current_user();
    $role = lga_crm_current_role();
    $role_label = array(
        'administrator' => 'Admin',
        'vendedor'      => 'Vendedor',
        'cobrador'      => 'Cobrador',
    )[ $role ] ?? $role;

    ?><!DOCTYPE html>
<html lang="es-AR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html( $title ); ?> — LGA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .lga-link { color: #0F766E; }
        .lga-link:hover { text-decoration: underline; }
    </style>
</head>
<body class="bg-zinc-50 min-h-screen text-zinc-900">
<header class="bg-white border-b border-zinc-200 sticky top-0 z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-6">
            <a href="<?php echo esc_url( home_url( '/panel' ) ); ?>" class="flex items-center gap-2 text-lg font-bold text-emerald-700">
                <span class="inline-block w-7 h-7 rounded bg-emerald-700"></span>
                <span>LGA · Panel</span>
            </a>
            <nav class="hidden sm:flex items-center gap-4 text-sm text-zinc-600">
                <?php if ( current_user_can( 'manage_options' ) ): ?>
                    <a href="<?php echo esc_url( home_url( '/panel/admin' ) ); ?>" class="hover:text-emerald-700">Admin</a>
                <?php endif; ?>
                <?php if ( current_user_can( 'read_lead' ) ): ?>
                    <a href="<?php echo esc_url( home_url( '/panel/vendedor' ) ); ?>" class="hover:text-emerald-700">Leads</a>
                <?php endif; ?>
                <?php if ( current_user_can( 'read_cliente' ) ): ?>
                    <a href="<?php echo esc_url( home_url( '/panel/cobrador' ) ); ?>" class="hover:text-emerald-700">Clientes / Créditos</a>
                <?php endif; ?>
            </nav>
        </div>
        <div class="flex items-center gap-3 text-sm">
            <span class="hidden sm:inline text-zinc-500">
                <?php echo esc_html( $user->display_name ); ?>
                <span class="ml-1 px-1.5 py-0.5 text-xs bg-emerald-100 text-emerald-800 rounded"><?php echo esc_html( $role_label ); ?></span>
            </span>
            <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="text-zinc-500 hover:text-zinc-900">Salir</a>
        </div>
    </div>
</header>
<main class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
<?php
}

function lga_crm_layout_close() {
    ?>
</main>
<footer class="mt-12 py-6 text-center text-xs text-zinc-400">
    LGA · Sistema de créditos · <?php echo esc_html( wp_date( 'Y' ) ); ?>
</footer>
</body>
</html>
<?php
}

/**
 * Flash messages helper (lee ?msg= y ?err= del query string).
 */
function lga_crm_flash() {
    $msg_codes = array(
        'created'         => array( 'green',  'Creado correctamente.' ),
        'updated'         => array( 'green',  'Actualizado.' ),
        'converted'       => array( 'green',  'Solicitud convertida en lead.' ),
        'promoted'        => array( 'green',  'Lead promovido a cliente con crédito.' ),
        'already_converted' => array( 'yellow', 'Esta solicitud ya estaba convertida.' ),
        'existing'        => array( 'yellow', 'Ya existe un cliente con ese DNI.' ),
    );
    $err_codes = array(
        'missing_required' => array( 'red', 'Faltan campos obligatorios.' ),
        'invalid_amounts'  => array( 'red', 'Montos o cuotas inválidos.' ),
    );

    $msg = sanitize_key( $_GET['msg'] ?? '' );
    $err = sanitize_key( $_GET['err'] ?? '' );

    if ( $msg && isset( $msg_codes[ $msg ] ) ) {
        list( $color, $text ) = $msg_codes[ $msg ];
        echo '<div class="mb-4 p-3 rounded-md bg-' . esc_attr( $color ) . '-50 text-' . esc_attr( $color ) . '-800 border border-' . esc_attr( $color ) . '-200">' . esc_html( $text ) . '</div>';
    }
    if ( $err && isset( $err_codes[ $err ] ) ) {
        list( $color, $text ) = $err_codes[ $err ];
        echo '<div class="mb-4 p-3 rounded-md bg-' . esc_attr( $color ) . '-50 text-' . esc_attr( $color ) . '-800 border border-' . esc_attr( $color ) . '-200">' . esc_html( $text ) . '</div>';
    }
}
