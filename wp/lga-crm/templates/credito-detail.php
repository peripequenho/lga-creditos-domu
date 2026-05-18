<?php
/**
 * Ficha crédito.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
require_once LGA_CRM_DIR . 'templates/_layout.php';

$cr_id = (int) ( $GLOBALS['lga_id'] ?? 0 );
if ( ! $cr_id || get_post_type( $cr_id ) !== 'credito' ) {
    wp_safe_redirect( home_url( '/panel' ) ); exit;
}
$cliente_id = (int) get_field( 'cliente_ref', $cr_id );
// Cobrador: solo si el cliente vinculado le pertenece
if ( ! current_user_can( 'manage_options' ) && lga_crm_current_role() === 'cobrador' ) {
    $cob = $cliente_id ? (int) get_field( 'cobrador', $cliente_id ) : 0;
    if ( $cob !== get_current_user_id() ) {
        wp_safe_redirect( home_url( '/panel' ) ); exit;
    }
}
$cr = get_post( $cr_id );
$f = function( $k ) use ( $cr_id ) { return get_field( $k, $cr_id ); };

lga_crm_layout_open( 'Crédito · ' . $cr->post_title );
lga_crm_flash();
?>
<div class="mb-4 text-sm"><a class="lga-link" href="<?php echo esc_url( wp_get_referer() ?: home_url( '/panel' ) ); ?>">← Volver</a></div>
<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold font-mono"><?php echo esc_html( $cr->post_title ); ?></h1>
        <p class="text-sm text-zinc-500">
            <?php echo lga_crm_badge( 'credit_status', $f('credit_status') ); ?>
            <?php if ( $cliente_id ): ?>
                · <a class="lga-link" href="<?php echo esc_url( home_url( '/cliente/' . $cliente_id ) ); ?>"><?php echo esc_html( get_the_title( $cliente_id ) ); ?></a>
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-lg border border-zinc-200 p-5">
            <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-3">Términos del crédito</h3>
            <dl class="grid grid-cols-3 gap-3 text-sm">
                <div><dt class="text-zinc-500 text-xs">Monto</dt><dd class="font-semibold text-lg"><?php echo esc_html( lga_crm_money( $f('monto_ars') ) ); ?></dd></div>
                <div><dt class="text-zinc-500 text-xs">Cuotas</dt><dd class="font-semibold text-lg"><?php echo esc_html( $f('cuotas_totales') ); ?></dd></div>
                <div><dt class="text-zinc-500 text-xs">Frecuencia</dt><dd><?php echo esc_html( $f('payment_frequency') ); ?></dd></div>
                <div><dt class="text-zinc-500 text-xs">Cuota estimada</dt><dd><?php echo esc_html( lga_crm_money( $f('cuota_estimada_ars') ) ); ?></dd></div>
                <div><dt class="text-zinc-500 text-xs">Tasa</dt><dd><?php echo esc_html( $f('tasa_aplicada') ); ?>%</dd></div>
                <div><dt class="text-zinc-500 text-xs">Fecha alta</dt><dd><?php echo $f('fecha_alta') ? esc_html( mysql2date( 'd/m/Y', $f('fecha_alta') ) ) : '—'; ?></dd></div>
            </dl>
        </div>

        <div class="bg-white rounded-lg border border-zinc-200 p-5">
            <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-3">Estado de cobranza</h3>
            <dl class="grid grid-cols-3 gap-3 text-sm">
                <div><dt class="text-zinc-500 text-xs">Pagadas</dt><dd class="text-lg font-semibold"><?php echo esc_html( $f('cuotas_pagadas') . '/' . $f('cuotas_totales') ); ?></dd></div>
                <div><dt class="text-zinc-500 text-xs">Saldo</dt><dd class="text-lg font-semibold"><?php echo esc_html( lga_crm_money( $f('saldo_ars') ) ); ?></dd></div>
                <div><dt class="text-zinc-500 text-xs">Próx. pago</dt><dd><?php echo $f('proxima_fecha_pago') ? esc_html( mysql2date( 'd/m/Y', $f('proxima_fecha_pago') ) ) : '—'; ?></dd></div>
            </dl>
            <p class="text-xs text-zinc-400 mt-3 italic">Registro de pagos individual: a implementar en F4.</p>
        </div>

        <?php if ( $f('product_title') ): ?>
        <div class="bg-white rounded-lg border border-zinc-200 p-5">
            <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-3">Producto Shopify (origen del crédito)</h3>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div class="col-span-2"><dt class="text-zinc-500 text-xs">Producto</dt><dd><?php echo esc_html( $f('product_title') ); ?></dd></div>
                <div><dt class="text-zinc-500 text-xs">Total carrito</dt><dd><?php echo esc_html( lga_crm_money( $f('cart_total_ars') ) ); ?></dd></div>
                <div><dt class="text-zinc-500 text-xs">Source</dt><dd><?php echo esc_html( $f('source') ); ?></dd></div>
            </dl>
        </div>
        <?php endif; ?>

        <?php $notes = $f('internal_notes'); if ( $notes ): ?>
        <div class="bg-zinc-100 rounded-lg border border-zinc-200 p-5">
            <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-2">Notas internas</h3>
            <pre class="text-xs text-zinc-700 whitespace-pre-wrap"><?php echo esc_html( $notes ); ?></pre>
        </div>
        <?php endif; ?>
    </div>

    <aside class="space-y-4 text-sm">
        <?php if ( $cliente_id ): ?>
        <div class="bg-white rounded-lg border border-zinc-200 p-5">
            <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-3">Cliente</h3>
            <p class="font-medium"><?php echo esc_html( get_the_title( $cliente_id ) ); ?></p>
            <p class="text-zinc-500 text-xs mt-1"><?php echo esc_html( get_field( 'phone', $cliente_id ) ); ?></p>
            <a href="<?php echo esc_url( home_url( '/cliente/' . $cliente_id ) ); ?>" class="lga-link text-xs mt-3 inline-block">Ver ficha del cliente →</a>
        </div>
        <?php endif; ?>

        <?php $shopify_post_id = $cr_id; include LGA_CRM_DIR . 'templates/_shopify-card.php'; ?>

        <?php if ( current_user_can( 'edit_others_creditos' ) ): ?>
        <div class="lga-card p-5">
            <a href="<?php echo esc_url( get_edit_post_link( $cr_id ) ); ?>" class="lga-link text-xs">Editar en wp-admin →</a>
        </div>
        <?php endif; ?>
    </aside>
</div>

<?php lga_crm_layout_close();
