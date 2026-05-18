<?php
/**
 * Convertir una solicitud web (CPT solicitud) en lead.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
require_once LGA_CRM_DIR . 'templates/_layout.php';

$sol_id = (int) ( $GLOBALS['lga_id'] ?? 0 );
if ( ! $sol_id || get_post_type( $sol_id ) !== 'solicitud' ) {
    wp_safe_redirect( home_url( '/panel/admin' ) ); exit;
}
$f = function( $k ) use ( $sol_id ) { return get_field( $k, $sol_id ); };

$vendedores = get_users( array( 'role' => 'vendedor', 'orderby' => 'display_name', 'order' => 'ASC' ) );

lga_crm_layout_open( 'Convertir solicitud · ' . get_the_title( $sol_id ) );
?>
<div class="mb-4 text-sm"><a class="lga-link" href="<?php echo esc_url( home_url( '/panel/admin?tab=solicitudes' ) ); ?>">← Volver a solicitudes</a></div>
<h1 class="text-2xl font-bold mb-1">Convertir solicitud a lead</h1>
<p class="text-sm text-zinc-500 mb-6"><?php echo esc_html( get_the_title( $sol_id ) ); ?></p>

<div class="bg-zinc-50 rounded-lg border border-zinc-200 p-5 mb-6 text-sm">
    <h3 class="font-semibold mb-3">Datos del solicitante</h3>
    <dl class="grid grid-cols-2 gap-2">
        <div><dt class="text-zinc-500 text-xs">Cliente</dt><dd><?php echo esc_html( trim( $f('first_name') . ' ' . $f('last_name') ) ); ?></dd></div>
        <div><dt class="text-zinc-500 text-xs">DNI</dt><dd><?php echo esc_html( $f('dni') ); ?></dd></div>
        <div><dt class="text-zinc-500 text-xs">Tel</dt><dd><?php echo esc_html( $f('phone') ); ?></dd></div>
        <div><dt class="text-zinc-500 text-xs">Email</dt><dd><?php echo esc_html( $f('email') ?: '—' ); ?></dd></div>
        <div><dt class="text-zinc-500 text-xs">Localidad</dt><dd><?php echo esc_html( $f('locality') . ', ' . $f('province') ); ?></dd></div>
        <div><dt class="text-zinc-500 text-xs">Monto pedido</dt><dd><?php echo esc_html( lga_crm_money( $f('requested_amount_ars') ) . ' · ' . $f('requested_installments') . ' cuotas ' . $f('payment_frequency') ); ?></dd></div>
        <div><dt class="text-zinc-500 text-xs">Zona</dt><dd><?php echo lga_crm_badge( 'zone_status', $f('zone_status') ); ?></dd></div>
        <div><dt class="text-zinc-500 text-xs">Producto Shopify</dt><dd><?php echo esc_html( $f('product_title') ?: '—' ); ?></dd></div>
    </dl>
</div>

<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-4">
    <input type="hidden" name="action" value="lga_convert_solicitud">
    <input type="hidden" name="solicitud_id" value="<?php echo (int) $sol_id; ?>">
    <?php wp_nonce_field( 'lga_convert_solicitud_' . $sol_id ); ?>

    <div class="bg-white rounded-lg border border-zinc-200 p-5">
        <label class="block text-sm">
            <span class="block mb-1 text-xs text-zinc-600">Asignar a vendedor (opcional)</span>
            <select name="responsable" class="w-full border border-zinc-300 rounded p-2">
                <option value="">— Sin asignar todavía —</option>
                <?php foreach ( $vendedores as $u ): ?>
                    <option value="<?php echo (int) $u->ID; ?>"><?php echo esc_html( $u->display_name ); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <div class="flex justify-end gap-3">
        <a href="<?php echo esc_url( home_url( '/panel/admin?tab=solicitudes' ) ); ?>" class="px-4 py-2 rounded text-sm text-zinc-700 hover:bg-zinc-100">Cancelar</a>
        <button type="submit" class="px-5 py-2 rounded bg-emerald-700 text-white text-sm font-medium hover:bg-emerald-800">Convertir en lead →</button>
    </div>
</form>

<?php lga_crm_layout_close();
