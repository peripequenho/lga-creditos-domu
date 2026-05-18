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

// Counts para badges en tabs
$count_sol = count( lga_crm_get_pending_solicitudes() );
$count_leads = lga_crm_count( 'lead' );
$count_clientes = lga_crm_count( 'cliente' );
$count_creditos = lga_crm_count( 'credito' );

lga_crm_layout_open( 'Admin · Panel' );
lga_crm_flash();
?>

<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold text-zinc-900">Panel administrativo</h1>
    <div class="flex gap-2">
        <a href="<?php echo esc_url( home_url( '/admin/nuevo-cliente' ) ); ?>"
           class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-emerald-700 text-white text-sm font-medium hover:bg-emerald-800">
            + Nuevo cliente manual
        </a>
    </div>
</div>

<!-- Tabs -->
<div class="border-b border-zinc-200 mb-6">
    <nav class="flex gap-6 -mb-px">
        <?php foreach ( $valid_tabs as $t ):
            $counts = array( 'solicitudes' => $count_sol, 'leads' => $count_leads, 'clientes' => $count_clientes, 'creditos' => $count_creditos );
            $labels = array( 'solicitudes' => 'Solicitudes pendientes', 'leads' => 'Leads', 'clientes' => 'Clientes', 'creditos' => 'Créditos' );
            $active = $tab === $t;
        ?>
        <a href="?tab=<?php echo esc_attr( $t ); ?>"
           class="<?php echo $active ? 'border-emerald-700 text-emerald-700' : 'border-transparent text-zinc-500 hover:text-zinc-700'; ?> border-b-2 py-3 px-1 text-sm font-medium">
            <?php echo esc_html( $labels[ $t ] ); ?>
            <span class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs bg-zinc-100 text-zinc-700 rounded-full"><?php echo esc_html( $counts[ $t ] ); ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
</div>

<?php if ( $tab === 'solicitudes' ): ?>
    <?php $items = lga_crm_get_pending_solicitudes(); ?>
    <div class="bg-white rounded-lg border border-zinc-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-zinc-600">
                <tr>
                    <th class="text-left p-3">Código</th>
                    <th class="text-left p-3">Cliente</th>
                    <th class="text-left p-3">DNI / Tel</th>
                    <th class="text-left p-3">Monto / cuotas</th>
                    <th class="text-left p-3">Zona</th>
                    <th class="text-left p-3">Fecha</th>
                    <th class="text-right p-3">Acción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
            <?php if ( empty( $items ) ): ?>
                <tr><td colspan="7" class="p-6 text-center text-zinc-400">No hay solicitudes pendientes.</td></tr>
            <?php else: foreach ( $items as $p ):
                $first = get_field( 'first_name', $p->ID );
                $last  = get_field( 'last_name', $p->ID );
                $dni   = get_field( 'dni', $p->ID );
                $phone = get_field( 'phone', $p->ID );
                $monto = get_field( 'requested_amount_ars', $p->ID );
                $cuotas = get_field( 'requested_installments', $p->ID );
                $freq  = get_field( 'payment_frequency', $p->ID );
                $zone  = get_field( 'zone_status', $p->ID );
            ?>
                <tr class="hover:bg-zinc-50">
                    <td class="p-3 font-mono text-xs"><?php echo esc_html( get_the_title( $p->ID ) ); ?></td>
                    <td class="p-3"><?php echo esc_html( trim( $first . ' ' . $last ) ); ?></td>
                    <td class="p-3 text-zinc-600"><?php echo esc_html( $dni ); ?><br><span class="text-xs"><?php echo esc_html( $phone ); ?></span></td>
                    <td class="p-3"><?php echo esc_html( lga_crm_money( $monto ) ); ?><br><span class="text-xs text-zinc-500"><?php echo esc_html( $cuotas ); ?> · <?php echo esc_html( $freq ); ?></span></td>
                    <td class="p-3"><?php echo lga_crm_badge( 'zone_status', $zone ); ?></td>
                    <td class="p-3 text-xs text-zinc-500"><?php echo esc_html( mysql2date( 'd/m/y H:i', $p->post_date ) ); ?></td>
                    <td class="p-3 text-right">
                        <a href="<?php echo esc_url( home_url( '/admin/aprobar-solicitud/' . $p->ID ) ); ?>"
                           class="inline-block px-3 py-1 rounded bg-emerald-700 text-white text-xs hover:bg-emerald-800">Convertir a lead →</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

<?php elseif ( $tab === 'leads' ): ?>
    <?php $items = lga_crm_get_leads_for_user(); ?>
    <div class="bg-white rounded-lg border border-zinc-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-zinc-600">
                <tr>
                    <th class="text-left p-3">Lead</th>
                    <th class="text-left p-3">Cliente</th>
                    <th class="text-left p-3">Monto</th>
                    <th class="text-left p-3">Responsable</th>
                    <th class="text-left p-3">Estado</th>
                    <th class="text-left p-3">Zona</th>
                    <th class="text-right p-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
            <?php if ( empty( $items ) ): ?>
                <tr><td colspan="7" class="p-6 text-center text-zinc-400">No hay leads todavía.</td></tr>
            <?php else: foreach ( $items as $p ):
                $first = get_field( 'first_name', $p->ID );
                $last  = get_field( 'last_name', $p->ID );
                $dni   = get_field( 'dni', $p->ID );
                $monto = get_field( 'requested_amount_ars', $p->ID );
                $resp  = (int) get_field( 'responsable', $p->ID );
                $resp_user = $resp ? get_userdata( $resp ) : null;
                $status = get_field( 'lead_status', $p->ID );
                $zone   = get_field( 'zone_status', $p->ID );
            ?>
                <tr class="hover:bg-zinc-50">
                    <td class="p-3"><a class="lga-link font-mono text-xs" href="<?php echo esc_url( home_url( '/lead/' . $p->ID ) ); ?>"><?php echo esc_html( get_the_title( $p->ID ) ); ?></a></td>
                    <td class="p-3"><?php echo esc_html( trim( $first . ' ' . $last ) ); ?><br><span class="text-xs text-zinc-500">DNI <?php echo esc_html( $dni ); ?></span></td>
                    <td class="p-3"><?php echo esc_html( lga_crm_money( $monto ) ); ?></td>
                    <td class="p-3 text-xs"><?php echo $resp_user ? esc_html( $resp_user->display_name ) : '<span class="text-zinc-400">— Sin asignar —</span>'; ?></td>
                    <td class="p-3"><?php echo lga_crm_badge( 'lead_status', $status ); ?></td>
                    <td class="p-3"><?php echo lga_crm_badge( 'zone_status', $zone ); ?></td>
                    <td class="p-3 text-right">
                        <a href="<?php echo esc_url( home_url( '/lead/' . $p->ID ) ); ?>" class="text-emerald-700 hover:underline text-xs">Ver →</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

<?php elseif ( $tab === 'clientes' ): ?>
    <?php $items = lga_crm_get_clientes_for_user(); ?>
    <div class="bg-white rounded-lg border border-zinc-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-zinc-600">
                <tr>
                    <th class="text-left p-3">Cliente</th>
                    <th class="text-left p-3">DNI / Tel</th>
                    <th class="text-left p-3">Estado</th>
                    <th class="text-left p-3">Origen</th>
                    <th class="text-left p-3">Cobrador</th>
                    <th class="text-right p-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
            <?php if ( empty( $items ) ): ?>
                <tr><td colspan="6" class="p-6 text-center text-zinc-400">No hay clientes todavía.</td></tr>
            <?php else: foreach ( $items as $p ):
                $first = get_field( 'first_name', $p->ID );
                $last  = get_field( 'last_name', $p->ID );
                $dni   = get_field( 'dni', $p->ID );
                $phone = get_field( 'phone', $p->ID );
                $status= get_field( 'client_status', $p->ID );
                $origen= get_field( 'origen', $p->ID );
                $cob   = (int) get_field( 'cobrador', $p->ID );
                $cob_user = $cob ? get_userdata( $cob ) : null;
            ?>
                <tr class="hover:bg-zinc-50">
                    <td class="p-3"><a class="lga-link" href="<?php echo esc_url( home_url( '/cliente/' . $p->ID ) ); ?>"><?php echo esc_html( $last . ', ' . $first ); ?></a></td>
                    <td class="p-3 text-zinc-600"><?php echo esc_html( $dni ); ?><br><span class="text-xs"><?php echo esc_html( $phone ); ?></span></td>
                    <td class="p-3"><?php echo lga_crm_badge( 'client_status', $status ); ?></td>
                    <td class="p-3 text-xs"><?php echo esc_html( lga_crm_label( 'origen', $origen ) ); ?></td>
                    <td class="p-3 text-xs"><?php echo $cob_user ? esc_html( $cob_user->display_name ) : '<span class="text-zinc-400">—</span>'; ?></td>
                    <td class="p-3 text-right"><a href="<?php echo esc_url( home_url( '/cliente/' . $p->ID ) ); ?>" class="text-emerald-700 hover:underline text-xs">Ver →</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

<?php elseif ( $tab === 'creditos' ): ?>
    <?php $items = lga_crm_get_creditos_for_user(); ?>
    <div class="bg-white rounded-lg border border-zinc-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-zinc-600">
                <tr>
                    <th class="text-left p-3">Código</th>
                    <th class="text-left p-3">Cliente</th>
                    <th class="text-left p-3">Monto / cuotas</th>
                    <th class="text-left p-3">Pagadas</th>
                    <th class="text-left p-3">Estado</th>
                    <th class="text-right p-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
            <?php if ( empty( $items ) ): ?>
                <tr><td colspan="6" class="p-6 text-center text-zinc-400">No hay créditos todavía.</td></tr>
            <?php else: foreach ( $items as $p ):
                $cliente_id = (int) get_field( 'cliente_ref', $p->ID );
                $monto = get_field( 'monto_ars', $p->ID );
                $cuotas = get_field( 'cuotas_totales', $p->ID );
                $pagadas = (int) get_field( 'cuotas_pagadas', $p->ID );
                $freq = get_field( 'payment_frequency', $p->ID );
                $status = get_field( 'credit_status', $p->ID );
            ?>
                <tr class="hover:bg-zinc-50">
                    <td class="p-3"><a class="lga-link font-mono text-xs" href="<?php echo esc_url( home_url( '/credito/' . $p->ID ) ); ?>"><?php echo esc_html( get_the_title( $p->ID ) ); ?></a></td>
                    <td class="p-3 text-xs"><?php echo $cliente_id ? '<a class="lga-link" href="' . esc_url( home_url( '/cliente/' . $cliente_id ) ) . '">' . esc_html( get_the_title( $cliente_id ) ) . '</a>' : '<span class="text-zinc-400">—</span>'; ?></td>
                    <td class="p-3"><?php echo esc_html( lga_crm_money( $monto ) ); ?><br><span class="text-xs text-zinc-500"><?php echo esc_html( $cuotas ); ?> · <?php echo esc_html( $freq ); ?></span></td>
                    <td class="p-3"><?php echo esc_html( $pagadas . '/' . $cuotas ); ?></td>
                    <td class="p-3"><?php echo lga_crm_badge( 'credit_status', $status ); ?></td>
                    <td class="p-3 text-right"><a href="<?php echo esc_url( home_url( '/credito/' . $p->ID ) ); ?>" class="text-emerald-700 hover:underline text-xs">Ver →</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php lga_crm_layout_close();
