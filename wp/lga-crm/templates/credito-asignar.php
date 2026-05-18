<?php
/**
 * Form: asignar crédito a cliente existente. Step 2 del flujo alta manual (o standalone).
 */
if ( ! defined( 'ABSPATH' ) ) exit;
require_once LGA_CRM_DIR . 'templates/_layout.php';

$cliente_id = (int) ( $GLOBALS['lga_id'] ?? 0 );
if ( ! $cliente_id || get_post_type( $cliente_id ) !== 'cliente' ) {
    wp_safe_redirect( home_url( '/panel/admin' ) ); exit;
}
$f = function( $k ) use ( $cliente_id ) { return get_field( $k, $cliente_id ); };

lga_crm_layout_open( 'Nuevo crédito · ' . get_the_title( $cliente_id ) );
lga_crm_flash();
?>
<div class="mb-4 text-sm"><a class="lga-link" href="<?php echo esc_url( home_url( '/cliente/' . $cliente_id ) ); ?>">← Volver al cliente</a></div>
<h1 class="text-2xl font-bold mb-1">Asignar crédito</h1>
<p class="text-sm text-zinc-500 mb-6">
    Cliente: <a class="lga-link" href="<?php echo esc_url( home_url( '/cliente/' . $cliente_id ) ); ?>"><?php echo esc_html( get_the_title( $cliente_id ) ); ?></a>
    · DNI <?php echo esc_html( $f('dni') ); ?>
</p>

<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-6">
    <input type="hidden" name="action" value="lga_create_credito">
    <input type="hidden" name="cliente_id" value="<?php echo (int) $cliente_id; ?>">
    <?php wp_nonce_field( 'lga_create_credito' ); ?>

    <fieldset class="bg-white rounded-lg border border-zinc-200 p-5">
        <legend class="text-sm font-semibold px-2">Términos del crédito</legend>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <label class="block text-sm">
                <span class="block mb-1 text-xs text-zinc-600">Monto (ARS) *</span>
                <input type="number" name="monto_ars" required min="1" class="w-full border border-zinc-300 rounded p-2">
            </label>
            <label class="block text-sm">
                <span class="block mb-1 text-xs text-zinc-600">Frecuencia *</span>
                <select name="payment_frequency" class="w-full border border-zinc-300 rounded p-2">
                    <option value="monthly">Mensual</option>
                    <option value="weekly">Semanal</option>
                    <option value="daily">Diaria</option>
                </select>
            </label>
            <label class="block text-sm">
                <span class="block mb-1 text-xs text-zinc-600">Cuotas totales *</span>
                <input type="number" name="cuotas_totales" required min="1" class="w-full border border-zinc-300 rounded p-2">
            </label>
            <label class="block text-sm">
                <span class="block mb-1 text-xs text-zinc-600">Cuota estimada (ARS)</span>
                <input type="number" name="cuota_estimada_ars" min="0" class="w-full border border-zinc-300 rounded p-2">
            </label>
            <label class="block text-sm">
                <span class="block mb-1 text-xs text-zinc-600">Tasa aplicada (%)</span>
                <input type="number" name="tasa_aplicada" step="0.01" min="0" class="w-full border border-zinc-300 rounded p-2">
            </label>
        </div>
    </fieldset>

    <fieldset class="bg-white rounded-lg border border-zinc-200 p-5">
        <legend class="text-sm font-semibold px-2">Notas</legend>
        <label class="block text-sm">
            <span class="block mb-1 text-xs text-zinc-600">Notas internas</span>
            <textarea name="internal_notes" rows="3" class="w-full border border-zinc-300 rounded p-2" placeholder="Observaciones sobre este crédito..."></textarea>
        </label>
    </fieldset>

    <div class="flex justify-end gap-3">
        <a href="<?php echo esc_url( home_url( '/cliente/' . $cliente_id ) ); ?>" class="px-4 py-2 rounded text-sm text-zinc-700 hover:bg-zinc-100">Cancelar</a>
        <button type="submit" class="px-5 py-2 rounded bg-emerald-700 text-white text-sm font-medium hover:bg-emerald-800">
            Crear crédito →
        </button>
    </div>
</form>

<?php lga_crm_layout_close();
