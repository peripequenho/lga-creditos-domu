<?php
/**
 * Panel cobrador: SUS clientes y créditos.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
require_once LGA_CRM_DIR . 'templates/_layout.php';

$clientes = lga_crm_get_clientes_for_user();
$creditos = lga_crm_get_creditos_for_user();

// Stats créditos
$grouped = array( 'activo' => 0, 'al_dia' => 0, 'en_mora' => 0, 'pagado' => 0, 'otro' => 0 );
$total_saldo = 0;
$total_proximas = 0;
foreach ( $creditos as $c ) {
    $st = get_field( 'credit_status', $c->ID );
    if ( isset( $grouped[ $st ] ) ) $grouped[ $st ]++; else $grouped['otro']++;
    $total_saldo += (float) get_field( 'saldo_ars', $c->ID );

    $px = get_field( 'proxima_fecha_pago', $c->ID );
    if ( $px && strtotime( $px ) >= strtotime( 'today' ) && strtotime( $px ) <= strtotime( '+7 days' ) ) {
        $total_proximas++;
    }
}

lga_crm_layout_open( 'Cobrador · Mis clientes' );
lga_crm_flash();
?>
<div class="flex items-start justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-900">Mis clientes y créditos</h1>
        <p class="mt-1 text-sm text-zinc-500"><?php echo count( $clientes ); ?> clientes · <?php echo count( $creditos ); ?> créditos · <?php echo $total_proximas; ?> con próximo pago en 7 días</p>
    </div>
</div>

<!-- KPI grid estilo Tremor / shadcn -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
    <div class="lga-kpi">
        <div class="lga-kpi-label">Activos</div>
        <div class="lga-kpi-value text-blue-700"><?php echo $grouped['activo'] + $grouped['al_dia']; ?></div>
        <div class="mt-1 text-xs text-zinc-500">al día + activos</div>
    </div>
    <div class="lga-kpi">
        <div class="lga-kpi-label">En mora</div>
        <div class="lga-kpi-value text-red-700"><?php echo $grouped['en_mora']; ?></div>
        <div class="mt-1 text-xs text-zinc-500"><?php echo $grouped['en_mora'] > 0 ? 'requiere acción' : 'sin atrasos'; ?></div>
    </div>
    <div class="lga-kpi">
        <div class="lga-kpi-label">Pagados</div>
        <div class="lga-kpi-value text-emerald-700"><?php echo $grouped['pagado']; ?></div>
        <div class="mt-1 text-xs text-zinc-500">cerrados al 100%</div>
    </div>
    <div class="lga-kpi">
        <div class="lga-kpi-label">Saldo total</div>
        <div class="lga-kpi-value text-zinc-900"><?php echo esc_html( lga_crm_money( $total_saldo ) ); ?></div>
        <div class="mt-1 text-xs text-zinc-500">capital pendiente</div>
    </div>
</div>

<!-- Lista de créditos -->
<div class="lga-card overflow-hidden">
    <div class="px-5 py-4 flex items-center justify-between border-b border-zinc-200">
        <div>
            <h2 class="text-base font-semibold text-zinc-900">Créditos a tu cargo</h2>
            <p class="text-sm text-zinc-500"><?php echo count( $creditos ); ?> totales</p>
        </div>
    </div>

    <?php if ( empty( $creditos ) ): ?>
        <div class="px-5 py-16 text-center">
            <div class="mx-auto w-12 h-12 rounded-full bg-zinc-100 flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </div>
            <p class="text-sm text-zinc-500">Sin créditos asignados todavía.</p>
            <p class="mt-1 text-xs text-zinc-400">Cuando el admin te asigne uno, va a aparecer acá.</p>
        </div>
    <?php else: ?>
        <?php // Bug fix v0.3.9: envolver función en function_exists para evitar fatal
        // "Cannot redeclare function" si el template se carga 2x (ESI, render parcial).
        if ( ! function_exists( 'lga_crm_panel_cobrador_origen_badge' ) ) {
            function lga_crm_panel_cobrador_origen_badge( $credito_id, $cliente_id ) {
                $origen = $cliente_id ? get_field( 'origen', $cliente_id ) : '';
                if ( ! $origen ) {
                    $sh = get_post_meta( $credito_id, 'shopify_status', true );
                    if ( $sh ) $origen = 'web';
                }
                if ( $origen === 'web' ) {
                    return '<span class="lga-badge bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10">Shopify</span>';
                } elseif ( $origen === 'manual' ) {
                    return '<span class="lga-badge bg-zinc-100 text-zinc-700 ring-1 ring-inset ring-zinc-600/10">Manual</span>';
                }
                return '<span class="text-zinc-400 text-xs">—</span>';
            }
        }
        ?>
        <div class="overflow-x-auto">
            <table class="lga-table">
                <thead>
                    <tr>
                        <th>Origen</th>
                        <th>Crédito</th>
                        <th>Cliente</th>
                        <th>Tel</th>
                        <th>Próx. pago</th>
                        <th class="text-right">Saldo</th>
                        <th>Cuotas</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $creditos as $p ):
                    $cliente_id = (int) get_field( 'cliente_ref', $p->ID );
                    $phone = $cliente_id ? get_field( 'phone', $cliente_id ) : '';
                    $cuotas = (int) get_field( 'cuotas_totales', $p->ID );
                    $pagadas = (int) get_field( 'cuotas_pagadas', $p->ID );
                    $saldo = (float) get_field( 'saldo_ars', $p->ID );
                    $status = get_field( 'credit_status', $p->ID );
                    $proxima = get_field( 'proxima_fecha_pago', $p->ID );
                    $pct = $cuotas > 0 ? min( 100, round( $pagadas / $cuotas * 100 ) ) : 0;
                ?>
                    <tr>
                        <td><?php echo lga_crm_panel_cobrador_origen_badge( $p->ID, $cliente_id ); ?></td>
                        <td>
                            <a class="lga-link font-mono text-xs" href="<?php echo esc_url( home_url( '/credito/' . $p->ID ) ); ?>"><?php echo esc_html( get_the_title( $p->ID ) ); ?></a>
                        </td>
                        <td>
                            <?php if ( $cliente_id ): ?>
                                <a class="lga-link font-medium" href="<?php echo esc_url( home_url( '/cliente/' . $cliente_id ) ); ?>"><?php echo esc_html( get_the_title( $cliente_id ) ); ?></a>
                            <?php else: ?>
                                <span class="text-zinc-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-zinc-600 tabular-nums"><?php echo esc_html( $phone ); ?></td>
                        <td>
                            <?php if ( $proxima ): ?>
                                <span class="text-zinc-900 tabular-nums"><?php echo esc_html( mysql2date( 'd/m/Y', $proxima ) ); ?></span>
                            <?php else: ?>
                                <span class="text-zinc-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right font-medium tabular-nums"><?php echo esc_html( lga_crm_money( $saldo ) ); ?></td>
                        <td>
                            <div class="flex items-center gap-2 min-w-[100px]">
                                <div class="flex-1 h-1.5 rounded-full bg-zinc-100 overflow-hidden">
                                    <div class="h-full bg-emerald-600" style="width: <?php echo (int) $pct; ?>%"></div>
                                </div>
                                <span class="text-xs text-zinc-500 tabular-nums whitespace-nowrap"><?php echo esc_html( $pagadas . '/' . $cuotas ); ?></span>
                            </div>
                        </td>
                        <td><?php echo lga_crm_badge( 'credit_status', $status ); ?></td>
                        <td class="text-right">
                            <a href="<?php echo esc_url( home_url( '/credito/' . $p->ID ) ); ?>" class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700 hover:text-emerald-800">
                                Abrir
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php lga_crm_layout_close();
