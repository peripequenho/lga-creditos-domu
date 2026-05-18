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
foreach ( $creditos as $c ) {
    $st = get_field( 'credit_status', $c->ID );
    if ( isset( $grouped[ $st ] ) ) $grouped[ $st ]++; else $grouped['otro']++;
    $total_saldo += (float) get_field( 'saldo_ars', $c->ID );
}

lga_crm_layout_open( 'Cobrador · Mis clientes' );
lga_crm_flash();
?>
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold">Mis clientes y créditos</h1>
        <p class="text-sm text-zinc-500"><?php echo count( $clientes ); ?> clientes · <?php echo count( $creditos ); ?> créditos a tu cargo</p>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
    <div class="bg-white rounded-lg border border-zinc-200 p-4">
        <div class="text-xs text-zinc-500 uppercase tracking-wide">Activos</div>
        <div class="text-2xl font-bold text-blue-700"><?php echo $grouped['activo'] + $grouped['al_dia']; ?></div>
    </div>
    <div class="bg-white rounded-lg border border-zinc-200 p-4">
        <div class="text-xs text-zinc-500 uppercase tracking-wide">En mora</div>
        <div class="text-2xl font-bold text-red-700"><?php echo $grouped['en_mora']; ?></div>
    </div>
    <div class="bg-white rounded-lg border border-zinc-200 p-4">
        <div class="text-xs text-zinc-500 uppercase tracking-wide">Pagados</div>
        <div class="text-2xl font-bold text-emerald-700"><?php echo $grouped['pagado']; ?></div>
    </div>
    <div class="bg-white rounded-lg border border-zinc-200 p-4">
        <div class="text-xs text-zinc-500 uppercase tracking-wide">Saldo total</div>
        <div class="text-2xl font-bold text-zinc-700"><?php echo esc_html( lga_crm_money( $total_saldo ) ); ?></div>
    </div>
</div>

<h2 class="text-sm font-semibold text-zinc-700 uppercase tracking-wide mt-6 mb-2">Créditos activos</h2>
<div class="bg-white rounded-lg border border-zinc-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 text-zinc-600">
            <tr>
                <th class="text-left p-3">Crédito</th>
                <th class="text-left p-3">Cliente</th>
                <th class="text-left p-3">Tel</th>
                <th class="text-left p-3">Próx. pago</th>
                <th class="text-left p-3">Saldo</th>
                <th class="text-left p-3">Cuotas</th>
                <th class="text-left p-3">Estado</th>
                <th class="text-right p-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100">
        <?php if ( empty( $creditos ) ): ?>
            <tr><td colspan="8" class="p-6 text-center text-zinc-400">Sin créditos asignados todavía.</td></tr>
        <?php else: foreach ( $creditos as $p ):
            $cliente_id = (int) get_field( 'cliente_ref', $p->ID );
            $phone = $cliente_id ? get_field( 'phone', $cliente_id ) : '';
            $monto = get_field( 'monto_ars', $p->ID );
            $cuotas = get_field( 'cuotas_totales', $p->ID );
            $pagadas = (int) get_field( 'cuotas_pagadas', $p->ID );
            $saldo = get_field( 'saldo_ars', $p->ID );
            $status = get_field( 'credit_status', $p->ID );
            $proxima = get_field( 'proxima_fecha_pago', $p->ID );
        ?>
            <tr class="hover:bg-zinc-50">
                <td class="p-3"><a class="lga-link font-mono text-xs" href="<?php echo esc_url( home_url( '/credito/' . $p->ID ) ); ?>"><?php echo esc_html( get_the_title( $p->ID ) ); ?></a></td>
                <td class="p-3 text-xs"><?php echo $cliente_id ? '<a class="lga-link" href="' . esc_url( home_url( '/cliente/' . $cliente_id ) ) . '">' . esc_html( get_the_title( $cliente_id ) ) . '</a>' : '<span class="text-zinc-400">—</span>'; ?></td>
                <td class="p-3 text-xs text-zinc-600"><?php echo esc_html( $phone ); ?></td>
                <td class="p-3 text-xs"><?php echo $proxima ? esc_html( mysql2date( 'd/m/Y', $proxima ) ) : '<span class="text-zinc-400">—</span>'; ?></td>
                <td class="p-3"><?php echo esc_html( lga_crm_money( $saldo ) ); ?></td>
                <td class="p-3 text-xs"><?php echo esc_html( $pagadas . '/' . $cuotas ); ?></td>
                <td class="p-3"><?php echo lga_crm_badge( 'credit_status', $status ); ?></td>
                <td class="p-3 text-right">
                    <a href="<?php echo esc_url( home_url( '/credito/' . $p->ID ) ); ?>" class="text-emerald-700 hover:underline text-xs">Abrir →</a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php lga_crm_layout_close();
