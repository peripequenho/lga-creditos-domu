<?php
/**
 * Ficha lead: detalle + acciones (cambiar estado, agregar notas).
 */
if ( ! defined( 'ABSPATH' ) ) exit;
require_once LGA_CRM_DIR . 'templates/_layout.php';

$lead_id = (int) ( $GLOBALS['lga_id'] ?? 0 );
if ( ! $lead_id || get_post_type( $lead_id ) !== 'lead' ) {
    wp_safe_redirect( home_url( '/panel' ) ); exit;
}
// Permiso fino: si es vendedor, solo puede ver si es su lead
if ( ! current_user_can( 'manage_options' ) ) {
    $resp = (int) get_field( 'responsable', $lead_id );
    if ( $resp !== get_current_user_id() ) {
        wp_safe_redirect( home_url( '/panel' ) ); exit;
    }
}
$lead = get_post( $lead_id );
$f = function( $k ) use ( $lead_id ) { return get_field( $k, $lead_id ); };

lga_crm_layout_open( 'Lead · ' . $lead->post_title );
lga_crm_flash();
?>
<div class="mb-4 text-sm"><a class="lga-link" href="<?php echo esc_url( wp_get_referer() ?: home_url( '/panel' ) ); ?>">← Volver</a></div>
<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold"><?php echo esc_html( $f('last_name') . ', ' . $f('first_name') ); ?></h1>
        <p class="text-sm text-zinc-500"><?php echo esc_html( $lead->post_title ); ?> · <?php echo lga_crm_badge( 'lead_status', $f('lead_status') ); ?></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-4">
        <!-- Identidad -->
        <div class="bg-white rounded-lg border border-zinc-200 p-5">
            <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-3">Identidad</h3>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-zinc-500 text-xs">Nombre</dt><dd><?php echo esc_html( trim( $f('first_name') . ' ' . $f('last_name') ) ); ?></dd></div>
                <div><dt class="text-zinc-500 text-xs">DNI</dt><dd><?php echo esc_html( $f('dni') ); ?></dd></div>
                <div><dt class="text-zinc-500 text-xs">Teléfono</dt><dd><a href="tel:<?php echo esc_attr( $f('phone') ); ?>" class="lga-link"><?php echo esc_html( $f('phone') ); ?></a></dd></div>
                <div><dt class="text-zinc-500 text-xs">Email</dt><dd><?php echo esc_html( $f('email') ?: '—' ); ?></dd></div>
                <div><dt class="text-zinc-500 text-xs">Nacimiento</dt><dd><?php echo $f('birth_date') ? esc_html( mysql2date( 'd/m/Y', $f('birth_date') ) ) : '—'; ?></dd></div>
            </dl>
        </div>

        <!-- Domicilio -->
        <div class="bg-white rounded-lg border border-zinc-200 p-5">
            <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-3">Domicilio</h3>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div class="col-span-2"><dt class="text-zinc-500 text-xs">Dirección</dt><dd><?php echo esc_html( $f('address_line') ); ?></dd></div>
                <div><dt class="text-zinc-500 text-xs">Localidad</dt><dd><?php echo esc_html( $f('locality') ); ?></dd></div>
                <div><dt class="text-zinc-500 text-xs">Provincia / CP</dt><dd><?php echo esc_html( $f('province') . ' · ' . $f('postal_code') ); ?></dd></div>
                <div class="col-span-2"><dt class="text-zinc-500 text-xs">Zona</dt><dd><?php echo lga_crm_badge( 'zone_status', $f('zone_status') ); ?></dd></div>
            </dl>
        </div>

        <!-- Crédito pretendido -->
        <div class="bg-white rounded-lg border border-zinc-200 p-5">
            <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-3">Crédito pretendido</h3>
            <dl class="grid grid-cols-3 gap-3 text-sm">
                <div><dt class="text-zinc-500 text-xs">Monto</dt><dd class="font-semibold"><?php echo esc_html( lga_crm_money( $f('requested_amount_ars') ) ); ?></dd></div>
                <div><dt class="text-zinc-500 text-xs">Cuotas</dt><dd><?php echo esc_html( $f('requested_installments') ); ?></dd></div>
                <div><dt class="text-zinc-500 text-xs">Frecuencia</dt><dd><?php echo esc_html( $f('payment_frequency') ); ?></dd></div>
                <div class="col-span-3"><dt class="text-zinc-500 text-xs">Ingreso declarado</dt><dd><?php echo esc_html( lga_crm_money( $f('declared_income_ars') ) ); ?> / mes</dd></div>
            </dl>
        </div>

        <!-- Notas internas (historial) -->
        <?php $notes = $f('internal_notes'); if ( $notes ): ?>
        <div class="bg-zinc-100 rounded-lg border border-zinc-200 p-5">
            <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-2">Notas internas</h3>
            <pre class="text-xs text-zinc-700 whitespace-pre-wrap"><?php echo esc_html( $notes ); ?></pre>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar: cambio de estado -->
    <aside class="space-y-4">
        <div class="bg-white rounded-lg border border-zinc-200 p-5">
            <h3 class="text-sm font-semibold mb-3">Actualizar estado</h3>
            <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-3">
                <input type="hidden" name="action" value="lga_update_lead_status">
                <input type="hidden" name="lead_id" value="<?php echo (int) $lead_id; ?>">
                <?php wp_nonce_field( 'lga_update_lead_status_' . $lead_id ); ?>
                <label class="block text-xs text-zinc-500">Nuevo estado</label>
                <select name="lead_status" class="w-full border border-zinc-300 rounded p-2 text-sm">
                    <?php foreach ( array( 'nuevo','en_visita','aprobado','rechazado','perdido' ) as $st ): ?>
                    <option value="<?php echo esc_attr( $st ); ?>" <?php selected( $f('lead_status'), $st ); ?>><?php echo esc_html( lga_crm_label( 'lead_status', $st ) ); ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="block text-xs text-zinc-500">Agregar nota (opcional)</label>
                <textarea name="internal_notes" rows="3" class="w-full border border-zinc-300 rounded p-2 text-sm" placeholder="Visita realizada, observaciones…"></textarea>
                <button type="submit" class="w-full bg-emerald-700 text-white rounded p-2 text-sm font-medium hover:bg-emerald-800">Guardar</button>
            </form>
        </div>

        <?php if ( current_user_can( 'lga_approve_lead' ) && $f('lead_status') === 'aprobado' ): ?>
        <div class="bg-white rounded-lg border border-emerald-300 p-5">
            <h3 class="text-sm font-semibold mb-3 text-emerald-800">Listo para promover</h3>
            <p class="text-xs text-zinc-600 mb-3">El vendedor aprobó este lead. Ahora podés generar el cliente + crédito en una sola acción.</p>
            <a href="<?php echo esc_url( home_url( '/admin/promover-lead/' . $lead_id ) ); ?>"
               class="block text-center bg-emerald-700 text-white rounded p-2 text-sm font-medium hover:bg-emerald-800">Promover a cliente + crédito →</a>
        </div>
        <?php endif; ?>

        <div class="lga-card p-5 text-xs">
            <h3 class="text-sm font-semibold mb-3">Origen</h3>
            <?php $origen = $f('origen') ?: 'web'; ?>
            <div class="mb-3">
                <?php if ( $origen === 'web' ): ?>
                    <span class="lga-badge bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10">Shopify (Domu)</span>
                <?php else: ?>
                    <span class="lga-badge bg-zinc-100 text-zinc-700 ring-1 ring-inset ring-zinc-600/10">Manual (admin)</span>
                <?php endif; ?>
            </div>
            <dl class="space-y-1">
                <?php $sol = $f('solicitud_ref'); if ( $sol ): ?>
                <div><dt class="text-zinc-500">Solicitud</dt><dd><a class="lga-link font-mono text-xs" href="<?php echo esc_url( home_url( '/wp-admin/post.php?post=' . $sol . '&action=edit' ) ); ?>"><?php echo esc_html( get_the_title( $sol ) ); ?></a></dd></div>
                <?php endif; ?>
                <?php if ( $f('product_title') ): ?>
                <div><dt class="text-zinc-500">Producto</dt><dd class="text-xs"><?php echo esc_html( $f('product_title') ); ?></dd></div>
                <?php endif; ?>
                <?php if ( $f('utm_source') ): ?>
                <div><dt class="text-zinc-500">UTM</dt><dd><?php echo esc_html( $f('utm_source') . ' · ' . $f('utm_campaign') ); ?></dd></div>
                <?php endif; ?>
                <?php $resp = (int) $f('responsable'); if ( $resp ): $u = get_userdata( $resp ); ?>
                <div><dt class="text-zinc-500">Responsable</dt><dd><?php echo esc_html( $u->display_name ?? '—' ); ?></dd></div>
                <?php endif; ?>
            </dl>
        </div>

        <?php $shopify_post_id = $lead_id; include LGA_CRM_DIR . 'templates/_shopify-card.php'; ?>
    </aside>
</div>

<?php lga_crm_layout_close();
