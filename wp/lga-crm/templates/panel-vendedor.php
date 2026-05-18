<?php
/**
 * Panel vendedor: SOLO sus leads.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
require_once LGA_CRM_DIR . 'templates/_layout.php';

$items = lga_crm_get_leads_for_user();
// Agrupados por estado
$grouped = array( 'nuevo' => array(), 'en_visita' => array(), 'aprobado' => array(), 'rechazado' => array(), 'perdido' => array() );
foreach ( $items as $p ) {
    $st = get_field( 'lead_status', $p->ID ) ?: 'nuevo';
    if ( isset( $grouped[ $st ] ) ) $grouped[ $st ][] = $p;
}

lga_crm_layout_open( 'Vendedor · Mis leads' );
lga_crm_flash();
?>
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold">Mis leads</h1>
        <p class="text-sm text-zinc-500"><?php echo count( $items ); ?> lead<?php echo count($items)===1?'':'s'; ?> a tu cargo</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg border border-zinc-200 p-4">
        <div class="text-xs text-zinc-500 uppercase tracking-wide">Nuevos</div>
        <div class="text-3xl font-bold text-blue-700"><?php echo count( $grouped['nuevo'] ); ?></div>
    </div>
    <div class="bg-white rounded-lg border border-zinc-200 p-4">
        <div class="text-xs text-zinc-500 uppercase tracking-wide">En visita</div>
        <div class="text-3xl font-bold text-yellow-700"><?php echo count( $grouped['en_visita'] ); ?></div>
    </div>
    <div class="bg-white rounded-lg border border-zinc-200 p-4">
        <div class="text-xs text-zinc-500 uppercase tracking-wide">Aprobados (esperando admin)</div>
        <div class="text-3xl font-bold text-green-700"><?php echo count( $grouped['aprobado'] ); ?></div>
    </div>
</div>

<?php foreach ( array( 'nuevo' => 'Nuevos · a visitar', 'en_visita' => 'En visita', 'aprobado' => 'Aprobados (esperando admin)', 'rechazado' => 'Rechazados', 'perdido' => 'Perdidos' ) as $st => $st_label ): ?>
    <?php if ( empty( $grouped[ $st ] ) ) continue; ?>
    <h2 class="text-sm font-semibold text-zinc-700 uppercase tracking-wide mt-6 mb-2"><?php echo esc_html( $st_label ); ?> <span class="text-zinc-400">(<?php echo count( $grouped[ $st ] ); ?>)</span></h2>
    <div class="bg-white rounded-lg border border-zinc-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-zinc-600">
                <tr>
                    <th class="text-left p-3">Cliente</th>
                    <th class="text-left p-3">DNI / Tel</th>
                    <th class="text-left p-3">Domicilio</th>
                    <th class="text-left p-3">Monto pretendido</th>
                    <th class="text-right p-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                <?php foreach ( $grouped[ $st ] as $p ):
                    $first = get_field( 'first_name', $p->ID );
                    $last  = get_field( 'last_name', $p->ID );
                    $dni   = get_field( 'dni', $p->ID );
                    $phone = get_field( 'phone', $p->ID );
                    $addr  = get_field( 'address_line', $p->ID );
                    $loc   = get_field( 'locality', $p->ID );
                    $monto = get_field( 'requested_amount_ars', $p->ID );
                ?>
                <tr class="hover:bg-zinc-50">
                    <td class="p-3"><a class="lga-link font-medium" href="<?php echo esc_url( home_url( '/lead/' . $p->ID ) ); ?>"><?php echo esc_html( $last . ', ' . $first ); ?></a></td>
                    <td class="p-3 text-zinc-600"><?php echo esc_html( $dni ); ?><br><span class="text-xs"><?php echo esc_html( $phone ); ?></span></td>
                    <td class="p-3 text-xs text-zinc-600"><?php echo esc_html( $addr ); ?><br><span class="text-zinc-400"><?php echo esc_html( $loc ); ?></span></td>
                    <td class="p-3"><?php echo esc_html( lga_crm_money( $monto ) ); ?></td>
                    <td class="p-3 text-right"><a href="<?php echo esc_url( home_url( '/lead/' . $p->ID ) ); ?>" class="text-emerald-700 hover:underline text-xs">Abrir ficha →</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>

<?php if ( empty( $items ) ): ?>
    <div class="bg-white rounded-lg border border-zinc-200 p-10 text-center text-zinc-400">
        Todavía no tenés leads asignados.<br><span class="text-xs">Cuando el admin te asigne uno, va a aparecer acá.</span>
    </div>
<?php endif; ?>

<?php lga_crm_layout_close();
