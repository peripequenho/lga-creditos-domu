<?php
/**
 * Promover lead → cliente + crédito (acción admin).
 */
if ( ! defined( 'ABSPATH' ) ) exit;
require_once LGA_CRM_DIR . 'templates/_layout.php';

$lead_id = (int) ( $GLOBALS['lga_id'] ?? 0 );
if ( ! $lead_id || get_post_type( $lead_id ) !== 'lead' ) {
    wp_safe_redirect( home_url( '/panel/admin' ) ); exit;
}
$f = function( $k ) use ( $lead_id ) { return get_field( $k, $lead_id ); };
$cobradores = get_users( array( 'role' => 'cobrador', 'orderby' => 'display_name', 'order' => 'ASC' ) );

lga_crm_layout_open( 'Promover lead' );
?>
<div class="mb-4 text-sm"><a class="lga-link" href="<?php echo esc_url( home_url( '/lead/' . $lead_id ) ); ?>">← Volver al lead</a></div>
<h1 class="text-2xl font-bold mb-1">Promover lead a cliente + crédito</h1>
<p class="text-sm text-zinc-500 mb-6"><?php echo esc_html( $f('first_name') . ' ' . $f('last_name') ); ?> · DNI <?php echo esc_html( $f('dni') ); ?></p>

<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-4">
    <input type="hidden" name="action" value="lga_promote_lead">
    <input type="hidden" name="lead_id" value="<?php echo (int) $lead_id; ?>">
    <?php wp_nonce_field( 'lga_promote_lead_' . $lead_id ); ?>

    <div class="bg-white rounded-lg border border-zinc-200 p-5">
        <h3 class="text-sm font-semibold mb-3">Asignar cobrador</h3>
        <label class="block text-sm">
            <select name="cobrador" class="w-full border border-zinc-300 rounded p-2">
                <option value="">— Sin asignar todavía —</option>
                <?php foreach ( $cobradores as $u ): ?>
                    <option value="<?php echo (int) $u->ID; ?>"><?php echo esc_html( $u->display_name ); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <div class="bg-white rounded-lg border border-zinc-200 p-5">
        <h3 class="text-sm font-semibold mb-3">Confirmar términos del crédito</h3>
        <p class="text-xs text-zinc-500 mb-3">Por defecto se usan los valores del lead. Modificá si hace falta.</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <label class="block text-sm">
                <span class="block mb-1 text-xs text-zinc-600">Monto (ARS)</span>
                <input type="number" name="monto_ars" min="1" value="<?php echo esc_attr( $f('requested_amount_ars') ); ?>" class="w-full border border-zinc-300 rounded p-2">
            </label>
            <label class="block text-sm">
                <span class="block mb-1 text-xs text-zinc-600">Frecuencia</span>
                <select name="payment_frequency" class="w-full border border-zinc-300 rounded p-2">
                    <?php foreach ( array( 'monthly' => 'Mensual', 'weekly' => 'Semanal', 'daily' => 'Diaria' ) as $v => $l ): ?>
                    <option value="<?php echo esc_attr( $v ); ?>" <?php selected( $f('payment_frequency'), $v ); ?>><?php echo esc_html( $l ); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="block text-sm">
                <span class="block mb-1 text-xs text-zinc-600">Cuotas totales</span>
                <input type="number" name="cuotas_totales" min="1" value="<?php echo esc_attr( $f('requested_installments') ); ?>" class="w-full border border-zinc-300 rounded p-2">
            </label>
        </div>
    </div>

    <div class="flex justify-end gap-3">
        <a href="<?php echo esc_url( home_url( '/lead/' . $lead_id ) ); ?>" class="px-4 py-2 rounded text-sm text-zinc-700 hover:bg-zinc-100">Cancelar</a>
        <button type="submit" class="px-5 py-2 rounded bg-emerald-700 text-white text-sm font-medium hover:bg-emerald-800">Promover →</button>
    </div>
</form>

<?php lga_crm_layout_close();
