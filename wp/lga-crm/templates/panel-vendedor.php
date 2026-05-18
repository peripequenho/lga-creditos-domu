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
<div class="flex items-start justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-900">Mis leads</h1>
        <p class="mt-1 text-sm text-zinc-500"><?php echo count( $items ); ?> lead<?php echo count($items)===1?'':'s'; ?> a tu cargo</p>
    </div>
</div>

<!-- KPI grid -->
<div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-8">
    <div class="lga-kpi">
        <div class="lga-kpi-label">Nuevos · a visitar</div>
        <div class="lga-kpi-value text-blue-700"><?php echo count( $grouped['nuevo'] ); ?></div>
        <div class="mt-1 text-xs text-zinc-500">primer contacto pendiente</div>
    </div>
    <div class="lga-kpi">
        <div class="lga-kpi-label">En visita</div>
        <div class="lga-kpi-value text-amber-700"><?php echo count( $grouped['en_visita'] ); ?></div>
        <div class="mt-1 text-xs text-zinc-500">en proceso de evaluación</div>
    </div>
    <div class="lga-kpi">
        <div class="lga-kpi-label">Aprobados</div>
        <div class="lga-kpi-value text-emerald-700"><?php echo count( $grouped['aprobado'] ); ?></div>
        <div class="mt-1 text-xs text-zinc-500">esperando admin</div>
    </div>
</div>

<?php
$sections = array(
    'nuevo'      => array( 'label' => 'Nuevos · a visitar', 'badge' => 'blue' ),
    'en_visita'  => array( 'label' => 'En visita',           'badge' => 'amber' ),
    'aprobado'   => array( 'label' => 'Aprobados',           'badge' => 'emerald' ),
    'rechazado'  => array( 'label' => 'Rechazados',          'badge' => 'red' ),
    'perdido'    => array( 'label' => 'Perdidos',            'badge' => 'zinc' ),
);
foreach ( $sections as $st => $cfg ):
    if ( empty( $grouped[ $st ] ) ) continue;
?>
    <div class="mt-6 mb-3 flex items-center gap-3">
        <h2 class="text-sm font-semibold text-zinc-900 tracking-tight"><?php echo esc_html( $cfg['label'] ); ?></h2>
        <span class="text-xs text-zinc-500 tabular-nums"><?php echo count( $grouped[ $st ] ); ?></span>
    </div>
    <div class="lga-card overflow-hidden mb-2">
        <div class="overflow-x-auto">
            <table class="lga-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>DNI / Tel</th>
                        <th>Domicilio</th>
                        <th class="text-right">Monto pretendido</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $grouped[ $st ] as $p ):
                    $first = get_field( 'first_name', $p->ID );
                    $last  = get_field( 'last_name', $p->ID );
                    $dni   = get_field( 'dni', $p->ID );
                    $phone = get_field( 'phone', $p->ID );
                    $addr  = get_field( 'address_line', $p->ID );
                    $loc   = get_field( 'locality', $p->ID );
                    $monto = get_field( 'requested_amount_ars', $p->ID );
                ?>
                    <tr>
                        <td>
                            <a class="lga-link font-medium" href="<?php echo esc_url( home_url( '/lead/' . $p->ID ) ); ?>"><?php echo esc_html( $last . ', ' . $first ); ?></a>
                        </td>
                        <td class="text-zinc-600">
                            <div class="tabular-nums"><?php echo esc_html( $dni ); ?></div>
                            <div class="text-xs text-zinc-400 tabular-nums"><?php echo esc_html( $phone ); ?></div>
                        </td>
                        <td class="text-xs text-zinc-600">
                            <div><?php echo esc_html( $addr ); ?></div>
                            <div class="text-zinc-400"><?php echo esc_html( $loc ); ?></div>
                        </td>
                        <td class="text-right font-medium tabular-nums"><?php echo esc_html( lga_crm_money( $monto ) ); ?></td>
                        <td class="text-right">
                            <a href="<?php echo esc_url( home_url( '/lead/' . $p->ID ) ); ?>" class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700 hover:text-emerald-800">
                                Abrir ficha
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>

<?php if ( empty( $items ) ): ?>
    <div class="lga-card p-12 text-center">
        <div class="mx-auto w-12 h-12 rounded-full bg-zinc-100 flex items-center justify-center mb-3">
            <svg class="w-6 h-6 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </div>
        <p class="text-sm text-zinc-500">Todavía no tenés leads asignados.</p>
        <p class="mt-1 text-xs text-zinc-400">Cuando el admin te asigne uno, va a aparecer acá.</p>
    </div>
<?php endif; ?>

<?php lga_crm_layout_close();
