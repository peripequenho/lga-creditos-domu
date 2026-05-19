<?php
/**
 * Panel del admin: ve TODO.
 * 4 tabs: Solicitudes pendientes · Leads · Clientes · Créditos.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once LGA_CRM_DIR . 'templates/_layout.php';

$tab = sanitize_key( $_GET['tab'] ?? 'leads' );
$valid_tabs = array( 'solicitudes', 'leads', 'clientes', 'creditos' );
if ( ! in_array( $tab, $valid_tabs, true ) ) $tab = 'leads';

// Counts para badges en tabs (alineados con lo que muestra cada tab)
$count_sol = count( lga_crm_get_pending_solicitudes() );
$count_leads = count( lga_crm_get_leads_for_user() );       // solo activos
$count_clientes = lga_crm_count( 'cliente' );
$count_creditos = lga_crm_count( 'credito' );

lga_crm_layout_open( 'Admin · Panel' );
lga_crm_flash();
?>

<div class="flex items-start justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-900">Panel administrativo</h1>
        <p class="mt-1 text-sm text-zinc-500">Inbox completo: solicitudes web, leads activos, clientes y créditos.</p>
    </div>
    <div class="flex gap-2">
        <a href="<?php echo esc_url( home_url( '/admin/nuevo-cliente' ) ); ?>"
           class="inline-flex items-center gap-2 px-3.5 py-2 rounded-md bg-emerald-700 text-white text-sm font-medium hover:bg-emerald-800 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nuevo cliente manual
        </a>
    </div>
</div>

<!-- KPI grid -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
    <a href="?tab=solicitudes" class="lga-kpi hover:ring-2 hover:ring-amber-700/20 transition-shadow">
        <div class="lga-kpi-label">Solicitudes pendientes</div>
        <div class="lga-kpi-value <?php echo $count_sol > 0 ? 'text-amber-700' : 'text-zinc-400'; ?>"><?php echo (int) $count_sol; ?></div>
        <div class="mt-1 text-xs text-zinc-500"><?php echo $count_sol > 0 ? 'requieren acción' : 'sin pendientes'; ?></div>
    </a>
    <a href="?tab=leads" class="lga-kpi hover:ring-2 hover:ring-blue-700/20 transition-shadow">
        <div class="lga-kpi-label">Leads activos</div>
        <div class="lga-kpi-value text-blue-700"><?php echo (int) $count_leads; ?></div>
        <div class="mt-1 text-xs text-zinc-500">nuevos + en visita</div>
    </a>
    <a href="?tab=clientes" class="lga-kpi hover:ring-2 hover:ring-emerald-700/20 transition-shadow">
        <div class="lga-kpi-label">Clientes</div>
        <div class="lga-kpi-value text-emerald-700"><?php echo (int) $count_clientes; ?></div>
        <div class="mt-1 text-xs text-zinc-500">activos en cartera</div>
    </a>
    <a href="?tab=creditos" class="lga-kpi hover:ring-2 hover:ring-zinc-700/20 transition-shadow">
        <div class="lga-kpi-label">Créditos</div>
        <div class="lga-kpi-value text-zinc-900"><?php echo (int) $count_creditos; ?></div>
        <div class="mt-1 text-xs text-zinc-500">otorgados</div>
    </a>
</div>

<!-- Tabs -->
<div class="border-b border-zinc-200 mb-6">
    <nav class="flex gap-6 -mb-px" aria-label="Tabs">
        <?php foreach ( $valid_tabs as $t ):
            $counts = array( 'solicitudes' => $count_sol, 'leads' => $count_leads, 'clientes' => $count_clientes, 'creditos' => $count_creditos );
            $labels = array( 'solicitudes' => 'Solicitudes pendientes', 'leads' => 'Leads', 'clientes' => 'Clientes', 'creditos' => 'Créditos' );
            $active = $tab === $t;
        ?>
        <a href="?tab=<?php echo esc_attr( $t ); ?>"
           class="<?php echo $active ? 'border-emerald-700 text-emerald-700' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300'; ?> inline-flex items-center gap-2 border-b-2 py-3 px-1 text-sm font-medium transition-colors">
            <?php echo esc_html( $labels[ $t ] ); ?>
            <span class="<?php echo $active ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-100 text-zinc-700'; ?> inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-semibold rounded-full tabular-nums"><?php echo esc_html( $counts[ $t ] ); ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
</div>

<?php if ( $tab === 'solicitudes' ): ?>
    <?php $items = lga_crm_get_pending_solicitudes(); ?>
    <div class="lga-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="lga-table">
                <thead>
                    <tr>
                        <th>Origen</th>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>DNI / Tel</th>
                        <th class="text-right">Monto / cuotas</th>
                        <th>Zona</th>
                        <th>Shopify</th>
                        <th>Fecha</th>
                        <th class="text-right">Acción</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $items ) ): ?>
                    <tr><td colspan="9" class="p-10 text-center text-zinc-400">No hay solicitudes pendientes.</td></tr>
                <?php else: foreach ( $items as $p ):
                    $first = get_field( 'first_name', $p->ID );
                    $last  = get_field( 'last_name', $p->ID );
                    $dni   = get_field( 'dni', $p->ID );
                    $phone = get_field( 'phone', $p->ID );
                    $monto = get_field( 'requested_amount_ars', $p->ID );
                    $cuotas = get_field( 'requested_installments', $p->ID );
                    $freq  = get_field( 'payment_frequency', $p->ID );
                    $zone  = get_field( 'zone_status', $p->ID );
                    $shopify_admin = lga_crm_shopify_admin_link_draft( $p->ID );
                ?>
                    <tr>
                        <td>
                            <span class="lga-badge bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10">Shopify</span>
                        </td>
                        <td><span class="font-mono text-xs text-zinc-700"><?php echo esc_html( get_the_title( $p->ID ) ); ?></span></td>
                        <td class="font-medium text-zinc-900"><?php echo esc_html( trim( $first . ' ' . $last ) ); ?></td>
                        <td class="text-zinc-600">
                            <div class="tabular-nums"><?php echo esc_html( $dni ); ?></div>
                            <div class="text-xs text-zinc-400 tabular-nums"><?php echo esc_html( $phone ); ?></div>
                        </td>
                        <td class="text-right">
                            <div class="font-medium tabular-nums"><?php echo esc_html( lga_crm_money( $monto ) ); ?></div>
                            <div class="text-xs text-zinc-500"><?php echo esc_html( $cuotas ); ?> × <?php echo esc_html( $freq ); ?></div>
                        </td>
                        <td><?php echo lga_crm_badge( 'zone_status', $zone ); ?></td>
                        <td>
                            <?php echo lga_crm_shopify_status_badge( $p->ID ); ?>
                            <?php if ( $shopify_admin ): ?>
                                <a href="<?php echo esc_url( $shopify_admin ); ?>" target="_blank" rel="noopener" class="block mt-1 text-[10px] text-zinc-500 hover:text-emerald-700 hover:underline">Ver en Shopify ↗</a>
                            <?php endif; ?>
                        </td>
                        <td class="text-xs text-zinc-500 tabular-nums"><?php echo esc_html( mysql2date( 'd/m/y H:i', $p->post_date ) ); ?></td>
                        <td class="text-right">
                            <a href="<?php echo esc_url( home_url( '/admin/aprobar-solicitud/' . $p->ID ) ); ?>"
                               class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md bg-emerald-700 text-white text-xs font-medium hover:bg-emerald-800 transition-colors">
                                Convertir a lead
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ( $tab === 'leads' ): ?>
    <?php $items = lga_crm_get_leads_for_user(); ?>
    <div class="lga-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="lga-table">
                <thead>
                    <tr>
                        <th>Origen</th>
                        <th>Lead</th>
                        <th>Cliente</th>
                        <th class="text-right">Monto</th>
                        <th>Responsable</th>
                        <th>Estado</th>
                        <th>Shopify</th>
                        <th>Zona</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $items ) ): ?>
                    <tr><td colspan="9" class="p-10 text-center text-zinc-400">No hay leads todavía.</td></tr>
                <?php else: foreach ( $items as $p ):
                    $first = get_field( 'first_name', $p->ID );
                    $last  = get_field( 'last_name', $p->ID );
                    $dni   = get_field( 'dni', $p->ID );
                    $monto = get_field( 'requested_amount_ars', $p->ID );
                    $resp  = (int) get_field( 'responsable', $p->ID );
                    $resp_user = $resp ? get_userdata( $resp ) : null;
                    $status = get_field( 'lead_status', $p->ID );
                    $zone   = get_field( 'zone_status', $p->ID );
                    $origen = get_field( 'origen', $p->ID ) ?: 'web';
                    $shopify_draft_admin = lga_crm_shopify_admin_link_draft( $p->ID );
                    $shopify_order_admin = lga_crm_shopify_admin_link_order( $p->ID );
                ?>
                    <tr>
                        <td>
                            <?php if ( $origen === 'web' ): ?>
                                <span class="lga-badge bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10">Shopify</span>
                            <?php else: ?>
                                <span class="lga-badge bg-zinc-100 text-zinc-700 ring-1 ring-inset ring-zinc-600/10">Manual</span>
                            <?php endif; ?>
                        </td>
                        <td><a class="lga-link font-mono text-xs" href="<?php echo esc_url( home_url( '/lead/' . $p->ID ) ); ?>"><?php echo esc_html( get_the_title( $p->ID ) ); ?></a></td>
                        <td>
                            <div class="font-medium text-zinc-900"><?php echo esc_html( trim( $first . ' ' . $last ) ); ?></div>
                            <div class="text-xs text-zinc-500 tabular-nums">DNI <?php echo esc_html( $dni ); ?></div>
                        </td>
                        <td class="text-right font-medium tabular-nums"><?php echo esc_html( lga_crm_money( $monto ) ); ?></td>
                        <td class="text-sm">
                            <?php if ( $resp_user ): ?>
                                <span class="text-zinc-900"><?php echo esc_html( $resp_user->display_name ); ?></span>
                            <?php else: ?>
                                <span class="text-zinc-400 italic">Sin asignar</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo lga_crm_badge( 'lead_status', $status ); ?></td>
                        <td>
                            <?php echo lga_crm_shopify_status_badge( $p->ID ); ?>
                            <?php $link = $shopify_order_admin ?: $shopify_draft_admin; if ( $link ): ?>
                                <a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener" class="block mt-1 text-[10px] text-zinc-500 hover:text-emerald-700 hover:underline">Ver en Shopify ↗</a>
                            <?php endif; ?>
                        </td>
                        <td><?php echo lga_crm_badge( 'zone_status', $zone ); ?></td>
                        <td class="text-right">
                            <a href="<?php echo esc_url( home_url( '/lead/' . $p->ID ) ); ?>" class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700 hover:text-emerald-800">
                                Ver
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ( $tab === 'clientes' ): ?>
    <?php $items = lga_crm_get_clientes_for_user(); ?>
    <div class="lga-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="lga-table">
                <thead>
                    <tr>
                        <th>Origen</th>
                        <th>Cliente</th>
                        <th>DNI / Tel</th>
                        <th>Estado</th>
                        <th>Shopify</th>
                        <th>Cobrador</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $items ) ): ?>
                    <tr><td colspan="7" class="p-10 text-center text-zinc-400">No hay clientes todavía.</td></tr>
                <?php else: foreach ( $items as $p ):
                    $first = get_field( 'first_name', $p->ID );
                    $last  = get_field( 'last_name', $p->ID );
                    $dni   = get_field( 'dni', $p->ID );
                    $phone = get_field( 'phone', $p->ID );
                    $status= get_field( 'client_status', $p->ID );
                    $origen= get_field( 'origen', $p->ID );
                    $cob   = (int) get_field( 'cobrador', $p->ID );
                    $cob_user = $cob ? get_userdata( $cob ) : null;
                    $shopify_link = lga_crm_shopify_admin_link_order( $p->ID ) ?: lga_crm_shopify_admin_link_draft( $p->ID );
                ?>
                    <tr>
                        <td>
                            <?php if ( $origen === 'web' ): ?>
                                <span class="lga-badge bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10">Shopify</span>
                            <?php elseif ( $origen === 'manual' ): ?>
                                <span class="lga-badge bg-zinc-100 text-zinc-700 ring-1 ring-inset ring-zinc-600/10">Manual</span>
                            <?php else: ?>
                                <span class="lga-badge bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-700/10"><?php echo esc_html( lga_crm_label( 'origen', $origen ) ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><a class="lga-link" href="<?php echo esc_url( home_url( '/cliente/' . $p->ID ) ); ?>"><?php echo esc_html( $last . ', ' . $first ); ?></a></td>
                        <td class="text-zinc-600">
                            <div class="tabular-nums"><?php echo esc_html( $dni ); ?></div>
                            <div class="text-xs text-zinc-400 tabular-nums"><?php echo esc_html( $phone ); ?></div>
                        </td>
                        <td><?php echo lga_crm_badge( 'client_status', $status ); ?></td>
                        <td>
                            <?php echo lga_crm_shopify_status_badge( $p->ID ); ?>
                            <?php if ( $shopify_link ): ?>
                                <a href="<?php echo esc_url( $shopify_link ); ?>" target="_blank" rel="noopener" class="block mt-1 text-[10px] text-zinc-500 hover:text-emerald-700 hover:underline">Ver en Shopify ↗</a>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm">
                            <?php if ( $cob_user ): ?>
                                <span class="text-zinc-900"><?php echo esc_html( $cob_user->display_name ); ?></span>
                            <?php else: ?>
                                <span class="text-zinc-400 italic">Sin asignar</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <a href="<?php echo esc_url( home_url( '/cliente/' . $p->ID ) ); ?>" class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700 hover:text-emerald-800">
                                Ver
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ( $tab === 'creditos' ): ?>
    <?php $items = lga_crm_get_creditos_for_user(); ?>
    <div class="lga-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="lga-table">
                <thead>
                    <tr>
                        <th>Origen</th>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th class="text-right">Monto / cuotas</th>
                        <th>Progreso</th>
                        <th>Estado</th>
                        <th>Shopify</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $items ) ): ?>
                    <tr><td colspan="8" class="p-10 text-center text-zinc-400">No hay créditos todavía.</td></tr>
                <?php else: foreach ( $items as $p ):
                    $cliente_id = (int) get_field( 'cliente_ref', $p->ID );
                    $monto = get_field( 'monto_ars', $p->ID );
                    $cuotas = (int) get_field( 'cuotas_totales', $p->ID );
                    $pagadas = (int) get_field( 'cuotas_pagadas', $p->ID );
                    $freq = get_field( 'payment_frequency', $p->ID );
                    $status = get_field( 'credit_status', $p->ID );
                    $pct = $cuotas > 0 ? min( 100, round( $pagadas / $cuotas * 100 ) ) : 0;
                    $sh_status = get_post_meta( $p->ID, 'shopify_status', true );
                    $sh_link = lga_crm_shopify_admin_link_order( $p->ID ) ?: lga_crm_shopify_admin_link_draft( $p->ID );
                    // Heredar origen del cliente si el crédito no lo tiene
                    $origen = $cliente_id ? ( get_field( 'origen', $cliente_id ) ?: '' ) : '';
                    if ( ! $origen && $sh_status ) $origen = 'web';
                ?>
                    <tr>
                        <td>
                            <?php if ( $origen === 'web' ): ?>
                                <span class="lga-badge bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10">Shopify</span>
                            <?php elseif ( $origen === 'manual' ): ?>
                                <span class="lga-badge bg-zinc-100 text-zinc-700 ring-1 ring-inset ring-zinc-600/10">Manual</span>
                            <?php else: ?>
                                <span class="text-zinc-400 text-xs">—</span>
                            <?php endif; ?>
                        </td>
                        <td><a class="lga-link font-mono text-xs" href="<?php echo esc_url( home_url( '/credito/' . $p->ID ) ); ?>"><?php echo esc_html( get_the_title( $p->ID ) ); ?></a></td>
                        <td class="text-sm">
                            <?php if ( $cliente_id ): ?>
                                <a class="lga-link font-medium" href="<?php echo esc_url( home_url( '/cliente/' . $cliente_id ) ); ?>"><?php echo esc_html( get_the_title( $cliente_id ) ); ?></a>
                            <?php else: ?>
                                <span class="text-zinc-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <div class="font-medium tabular-nums"><?php echo esc_html( lga_crm_money( $monto ) ); ?></div>
                            <div class="text-xs text-zinc-500"><?php echo esc_html( $cuotas ); ?> × <?php echo esc_html( $freq ); ?></div>
                        </td>
                        <td>
                            <div class="flex items-center gap-2 min-w-[120px]">
                                <div class="flex-1 h-1.5 rounded-full bg-zinc-100 overflow-hidden">
                                    <div class="h-full bg-emerald-600" style="width: <?php echo (int) $pct; ?>%"></div>
                                </div>
                                <span class="text-xs text-zinc-500 tabular-nums whitespace-nowrap"><?php echo esc_html( $pagadas . '/' . $cuotas ); ?></span>
                            </div>
                        </td>
                        <td><?php echo lga_crm_badge( 'credit_status', $status ); ?></td>
                        <td>
                            <?php echo lga_crm_shopify_status_badge( $p->ID ); ?>
                            <?php if ( $sh_link ): ?>
                                <a href="<?php echo esc_url( $sh_link ); ?>" target="_blank" rel="noopener" class="block mt-1 text-[10px] text-zinc-500 hover:text-emerald-700 hover:underline">Ver en Shopify ↗</a>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <a href="<?php echo esc_url( home_url( '/credito/' . $p->ID ) ); ?>" class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700 hover:text-emerald-800">
                                Ver
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php lga_crm_layout_close();
