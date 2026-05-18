<?php
/**
 * Ficha cliente: datos + créditos asociados.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
require_once LGA_CRM_DIR . 'templates/_layout.php';

$cli_id = (int) ( $GLOBALS['lga_id'] ?? 0 );
if ( ! $cli_id || get_post_type( $cli_id ) !== 'cliente' ) {
    wp_safe_redirect( home_url( '/panel' ) ); exit;
}
// Cobrador: solo si es su cliente
if ( ! current_user_can( 'manage_options' ) && lga_crm_current_role() === 'cobrador' ) {
    $cob = (int) get_field( 'cobrador', $cli_id );
    if ( $cob !== get_current_user_id() ) {
        wp_safe_redirect( home_url( '/panel' ) ); exit;
    }
}
$cliente = get_post( $cli_id );
$f = function( $k ) use ( $cli_id ) { return get_field( $k, $cli_id ); };

// Créditos del cliente
$creditos = get_posts( array(
    'post_type' => 'credito',
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC',
    'meta_query' => array( array( 'key' => 'cliente_ref', 'value' => $cli_id, 'compare' => '=' ) ),
) );

lga_crm_layout_open( 'Cliente · ' . $cliente->post_title );
lga_crm_flash();
?>
<div class="mb-4 text-sm"><a class="lga-link" href="<?php echo esc_url( wp_get_referer() ?: home_url( '/panel' ) ); ?>">← Volver</a></div>
<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold"><?php echo esc_html( $f('last_name') . ', ' . $f('first_name') ); ?></h1>
        <p class="text-sm text-zinc-500">DNI <?php echo esc_html( $f('dni') ); ?> · <?php echo lga_crm_badge( 'client_status', $f('client_status') ); ?> · <?php echo esc_html( lga_crm_label( 'origen', $f('origen') ) ); ?></p>
    </div>
    <?php if ( current_user_can( 'lga_create_credito' ) ): ?>
    <a href="<?php echo esc_url( home_url( '/admin/cliente/' . $cli_id . '/asignar-credito' ) ); ?>"
       class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-emerald-700 text-white text-sm font-medium hover:bg-emerald-800">
        + Asignar nuevo crédito
    </a>
    <?php endif; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-lg border border-zinc-200 p-5">
            <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-3">Identidad</h3>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-zinc-500 text-xs">Nombre completo</dt><dd><?php echo esc_html( trim( $f('first_name') . ' ' . $f('last_name') ) ); ?></dd></div>
                <div><dt class="text-zinc-500 text-xs">DNI</dt><dd><?php echo esc_html( $f('dni') ); ?></dd></div>
                <div><dt class="text-zinc-500 text-xs">Teléfono</dt><dd><a class="lga-link" href="tel:<?php echo esc_attr( $f('phone') ); ?>"><?php echo esc_html( $f('phone') ); ?></a></dd></div>
                <div><dt class="text-zinc-500 text-xs">Email</dt><dd><?php echo esc_html( $f('email') ?: '—' ); ?></dd></div>
                <div><dt class="text-zinc-500 text-xs">Nacimiento</dt><dd><?php echo $f('birth_date') ? esc_html( mysql2date( 'd/m/Y', $f('birth_date') ) ) : '—'; ?></dd></div>
            </dl>
        </div>

        <div class="bg-white rounded-lg border border-zinc-200 p-5">
            <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-3">Domicilio + Ocupación</h3>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div class="col-span-2"><dt class="text-zinc-500 text-xs">Dirección</dt><dd><?php echo esc_html( $f('address_line') ); ?>, <?php echo esc_html( $f('locality') ); ?>, <?php echo esc_html( $f('province') ); ?> (<?php echo esc_html( $f('postal_code') ); ?>)</dd></div>
                <div><dt class="text-zinc-500 text-xs">Ocupación</dt><dd><?php echo esc_html( $f('occupation') ); ?></dd></div>
                <div><dt class="text-zinc-500 text-xs">Ingreso declarado</dt><dd><?php echo esc_html( lga_crm_money( $f('declared_income_ars') ) ); ?> / mes</dd></div>
            </dl>
        </div>

        <!-- Créditos del cliente -->
        <div class="bg-white rounded-lg border border-zinc-200 overflow-hidden">
            <div class="p-5 pb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold">Créditos (<?php echo count( $creditos ); ?>)</h3>
            </div>
            <?php if ( empty( $creditos ) ): ?>
                <div class="p-5 text-center text-zinc-400 text-sm">Este cliente todavía no tiene créditos asignados.</div>
            <?php else: ?>
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-zinc-600">
                    <tr>
                        <th class="text-left p-3">Código</th>
                        <th class="text-left p-3">Monto / cuotas</th>
                        <th class="text-left p-3">Cuotas pagadas</th>
                        <th class="text-left p-3">Saldo</th>
                        <th class="text-left p-3">Estado</th>
                        <th class="text-right p-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                <?php foreach ( $creditos as $cr ):
                    $monto = get_field( 'monto_ars', $cr->ID );
                    $cuotas = get_field( 'cuotas_totales', $cr->ID );
                    $pagadas = (int) get_field( 'cuotas_pagadas', $cr->ID );
                    $saldo = get_field( 'saldo_ars', $cr->ID );
                    $status = get_field( 'credit_status', $cr->ID );
                ?>
                    <tr class="hover:bg-zinc-50">
                        <td class="p-3 font-mono text-xs"><a class="lga-link" href="<?php echo esc_url( home_url( '/credito/' . $cr->ID ) ); ?>"><?php echo esc_html( get_the_title( $cr->ID ) ); ?></a></td>
                        <td class="p-3"><?php echo esc_html( lga_crm_money( $monto ) . ' · ' . $cuotas ); ?></td>
                        <td class="p-3"><?php echo esc_html( $pagadas . '/' . $cuotas ); ?></td>
                        <td class="p-3"><?php echo esc_html( lga_crm_money( $saldo ) ); ?></td>
                        <td class="p-3"><?php echo lga_crm_badge( 'credit_status', $status ); ?></td>
                        <td class="p-3 text-right"><a href="<?php echo esc_url( home_url( '/credito/' . $cr->ID ) ); ?>" class="text-emerald-700 hover:underline text-xs">Abrir →</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <?php $notes = $f('internal_notes'); if ( $notes ): ?>
        <div class="bg-zinc-100 rounded-lg border border-zinc-200 p-5">
            <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-2">Notas internas</h3>
            <pre class="text-xs text-zinc-700 whitespace-pre-wrap"><?php echo esc_html( $notes ); ?></pre>
        </div>
        <?php endif; ?>
    </div>

    <aside class="space-y-4 text-sm">
        <div class="lga-card p-5">
            <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-3">Origen</h3>
            <?php $origen = $f('origen') ?: ''; ?>
            <?php if ( $origen === 'web' ): ?>
                <span class="lga-badge bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10">Shopify (Domu)</span>
            <?php elseif ( $origen === 'manual' ): ?>
                <span class="lga-badge bg-zinc-100 text-zinc-700 ring-1 ring-inset ring-zinc-600/10">Manual (admin)</span>
            <?php else: ?>
                <span class="text-zinc-400 text-xs">— sin definir —</span>
            <?php endif; ?>
        </div>

        <div class="lga-card p-5">
            <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-3">Asignación</h3>
            <?php $cob_id = (int) $f('cobrador'); $cob_user = $cob_id ? get_userdata( $cob_id ) : null; ?>
            <p><span class="text-zinc-500">Cobrador:</span> <?php echo $cob_user ? esc_html( $cob_user->display_name ) : '<span class="text-zinc-400">Sin asignar</span>'; ?></p>
            <p><span class="text-zinc-500">Zona:</span> <?php echo esc_html( $f('zona') ?: '—' ); ?></p>
            <?php if ( current_user_can( 'edit_others_clientes' ) ): ?>
            <a href="<?php echo esc_url( get_edit_post_link( $cli_id ) ); ?>" class="mt-3 inline-block text-emerald-700 hover:underline text-xs">Editar en wp-admin →</a>
            <?php endif; ?>
        </div>

        <?php $shopify_post_id = $cli_id; include LGA_CRM_DIR . 'templates/_shopify-card.php'; ?>
    </aside>
</div>

<?php lga_crm_layout_close();
