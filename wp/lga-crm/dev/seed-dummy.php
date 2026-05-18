<?php
/**
 * Seed dummy: crea 5 users + 8 leads + 7 clientes + 9 créditos.
 *
 * Cómo ejecutar:
 *   wp eval-file wp-content/mu-plugins/lga-crm/dev/seed-dummy.php
 * o desde un endpoint admin protegido (ver al final del archivo, hay un trigger HTTP).
 *
 * Idempotente: usa meta `_lga_dummy` para marcar items creados.
 * Re-ejecutar no duplica datos.
 */

if ( ! defined( 'ABSPATH' ) ) {
    // Si se ejecuta vía WP-CLI, ABSPATH está definido. Sino, salir.
    require_once dirname( __FILE__, 5 ) . '/wp-load.php';
}

if ( ! current_user_can( 'manage_options' ) && ! defined( 'WP_CLI' ) ) {
    wp_die( 'Solo admin puede correr el seed.', 403 );
}

if ( ! function_exists( 'lga_crm_dummy_user' ) ) :

function lga_crm_dummy_user( $login, $email, $pass, $role, $display_name ) {
    $existing = get_user_by( 'login', $login );
    if ( $existing ) {
        $existing->set_role( $role );
        update_user_meta( $existing->ID, '_lga_dummy', '1' );
        return $existing->ID;
    }
    $user_id = wp_insert_user( array(
        'user_login'   => $login,
        'user_email'   => $email,
        'user_pass'    => $pass,
        'display_name' => $display_name,
        'first_name'   => explode( ' ', $display_name )[0],
        'role'         => $role,
    ) );
    if ( is_wp_error( $user_id ) ) {
        echo 'ERROR creando user ' . $login . ': ' . $user_id->get_error_message() . "\n";
        return 0;
    }
    update_user_meta( $user_id, '_lga_dummy', '1' );
    return (int) $user_id;
}

function lga_crm_dummy_post( $type, $title, $fields ) {
    // Idempotencia: buscar por meta _lga_dummy_key
    $key = $fields['_lga_dummy_key'] ?? $title;
    $existing = get_posts( array(
        'post_type' => $type, 'posts_per_page' => 1, 'fields' => 'ids',
        'meta_query' => array( array( 'key' => '_lga_dummy_key', 'value' => $key, 'compare' => '=' ) ),
    ) );
    if ( ! empty( $existing ) ) {
        return $existing[0];
    }
    $post_id = wp_insert_post( array(
        'post_type' => $type, 'post_title' => $title, 'post_status' => 'publish', 'post_author' => 1,
    ) );
    if ( is_wp_error( $post_id ) ) return 0;
    update_post_meta( $post_id, '_lga_dummy', '1' );
    update_post_meta( $post_id, '_lga_dummy_key', $key );
    foreach ( $fields as $k => $v ) {
        if ( strpos( $k, '_lga_' ) === 0 ) continue;
        update_field( $k, $v, $post_id );
    }
    return (int) $post_id;
}

endif;

// ─── USERS ──────────────────────────────────────────────────────────
$vendedor_1 = lga_crm_dummy_user( 'vendedor-1', 'vendedor1@lga.local', 'Vendedor1!2026', 'vendedor', 'Vendedor Uno' );
$vendedor_2 = lga_crm_dummy_user( 'vendedor-2', 'vendedor2@lga.local', 'Vendedor2!2026', 'vendedor', 'Vendedor Dos' );
$cobrador_1 = lga_crm_dummy_user( 'cobrador-1', 'cobrador1@lga.local', 'Cobrador1!2026', 'cobrador', 'Cobrador Uno' );
$cobrador_2 = lga_crm_dummy_user( 'cobrador-2', 'cobrador2@lga.local', 'Cobrador2!2026', 'cobrador', 'Cobrador Dos' );

echo "Users dummy: v1=$vendedor_1 v2=$vendedor_2 c1=$cobrador_1 c2=$cobrador_2\n";

// ─── CLIENTES (7) ───────────────────────────────────────────────────
$clientes = array(
    array( 'first' => 'Mariana', 'last' => 'Pérez',     'dni' => '30111111', 'phone' => '+5493815550101', 'loc' => 'San Miguel de Tucumán', 'cob' => $cobrador_1, 'status' => 'activo' ),
    array( 'first' => 'Juan',    'last' => 'González',  'dni' => '28222222', 'phone' => '+5493815550102', 'loc' => 'Yerba Buena',           'cob' => $cobrador_1, 'status' => 'activo' ),
    array( 'first' => 'Laura',   'last' => 'Rodríguez', 'dni' => '32333333', 'phone' => '+5493815550103', 'loc' => 'San Miguel de Tucumán', 'cob' => $cobrador_1, 'status' => 'activo' ),
    array( 'first' => 'Carlos',  'last' => 'Suárez',    'dni' => '25444444', 'phone' => '+5493815550104', 'loc' => 'Tafí Viejo',            'cob' => $cobrador_1, 'status' => 'activo' ),
    array( 'first' => 'Patricia','last' => 'Sosa',      'dni' => '29555555', 'phone' => '+5493815550105', 'loc' => 'San Miguel de Tucumán', 'cob' => $cobrador_2, 'status' => 'activo' ),
    array( 'first' => 'Roberto', 'last' => 'Fernández', 'dni' => '27666666', 'phone' => '+5493815550106', 'loc' => 'Las Talitas',           'cob' => $cobrador_2, 'status' => 'activo' ),
    array( 'first' => 'Silvia',  'last' => 'Romero',    'dni' => '33777777', 'phone' => '+5493815550107', 'loc' => 'San Miguel de Tucumán', 'cob' => $cobrador_2, 'status' => 'lead' ),
);

$cliente_ids = array();
foreach ( $clientes as $c ) {
    $title = $c['last'] . ', ' . $c['first'] . ' · DNI ' . $c['dni'];
    $id = lga_crm_dummy_post( 'cliente', $title, array(
        '_lga_dummy_key'      => 'cli-' . $c['dni'],
        'first_name'          => $c['first'],
        'last_name'           => $c['last'],
        'dni'                 => $c['dni'],
        'phone'               => $c['phone'],
        'email'               => strtolower( $c['first'] ) . '@example.com',
        'address_line'        => 'Av. Test ' . rand( 100, 9999 ),
        'locality'            => $c['loc'],
        'province'            => 'Tucumán',
        'postal_code'         => 'T4000',
        'occupation'          => 'employed_registered',
        'declared_income_ars' => rand( 300, 900 ) * 1000,
        'client_status'       => $c['status'],
        'origen'              => 'manual',
        'cobrador'            => $c['cob'],
        'zona'                => $c['loc'],
    ) );
    $cliente_ids[] = $id;
    echo "Cliente $id: $title\n";
}

// ─── CRÉDITOS (9) ───────────────────────────────────────────────────
$creditos = array(
    array( 'cli' => 0, 'monto' => 150000, 'cuotas' => 12, 'freq' => 'monthly', 'pagadas' => 4, 'st' => 'al_dia' ),
    array( 'cli' => 0, 'monto' => 85000,  'cuotas' => 6,  'freq' => 'monthly', 'pagadas' => 6, 'st' => 'pagado' ),
    array( 'cli' => 1, 'monto' => 220000, 'cuotas' => 18, 'freq' => 'monthly', 'pagadas' => 8, 'st' => 'al_dia' ),
    array( 'cli' => 2, 'monto' => 95000,  'cuotas' => 12, 'freq' => 'weekly',  'pagadas' => 3, 'st' => 'activo' ),
    array( 'cli' => 3, 'monto' => 175000, 'cuotas' => 9,  'freq' => 'monthly', 'pagadas' => 2, 'st' => 'en_mora' ),
    array( 'cli' => 4, 'monto' => 120000, 'cuotas' => 24, 'freq' => 'monthly', 'pagadas' => 12,'st' => 'al_dia' ),
    array( 'cli' => 5, 'monto' => 65000,  'cuotas' => 16, 'freq' => 'weekly',  'pagadas' => 6, 'st' => 'al_dia' ),
    array( 'cli' => 5, 'monto' => 45000,  'cuotas' => 8,  'freq' => 'monthly', 'pagadas' => 1, 'st' => 'en_mora' ),
    array( 'cli' => 4, 'monto' => 200000, 'cuotas' => 12, 'freq' => 'monthly', 'pagadas' => 0, 'st' => 'pendiente_aprobacion' ),
);

foreach ( $creditos as $i => $cr ) {
    $cliente_id = $cliente_ids[ $cr['cli'] ];
    $code = sprintf( 'CR-DUMMY-%03d', $i + 1 );
    $cuota_estimada = (int) round( $cr['monto'] / $cr['cuotas'] * 1.1 );
    $saldo = (int) round( ( ( $cr['cuotas'] - $cr['pagadas'] ) / $cr['cuotas'] ) * $cr['monto'] );

    $id = lga_crm_dummy_post( 'credito', $code, array(
        '_lga_dummy_key'      => $code,
        'cliente_ref'         => $cliente_id,
        'monto_ars'           => $cr['monto'],
        'cuotas_totales'      => $cr['cuotas'],
        'payment_frequency'   => $cr['freq'],
        'cuota_estimada_ars'  => $cuota_estimada,
        'tasa_aplicada'       => 8.0,
        'fecha_alta'          => wp_date( 'Y-m-d', strtotime( '-' . rand( 30, 180 ) . ' days' ) ),
        'credit_status'       => $cr['st'],
        'cuotas_pagadas'      => $cr['pagadas'],
        'saldo_ars'           => $saldo,
        'proxima_fecha_pago'  => wp_date( 'Y-m-d', strtotime( '+' . rand( 1, 15 ) . ' days' ) ),
    ) );
    echo "Crédito $id: $code → cliente $cliente_id\n";
}

// ─── LEADS (8) ──────────────────────────────────────────────────────
$leads = array(
    array( 'first' => 'Diego',    'last' => 'Castro',   'dni' => '38001001', 'phone' => '+5493815551001', 'loc' => 'Yerba Buena', 'monto' => 80000,  'cuotas' => 12, 'freq' => 'monthly', 'st' => 'nuevo',     'resp' => $vendedor_1, 'origen' => 'manual' ),
    array( 'first' => 'Andrea',   'last' => 'Vega',     'dni' => '37001002', 'phone' => '+5493815551002', 'loc' => 'San Miguel',  'monto' => 110000, 'cuotas' => 18, 'freq' => 'monthly', 'st' => 'nuevo',     'resp' => $vendedor_1, 'origen' => 'web' ),
    array( 'first' => 'Hugo',     'last' => 'Morales',  'dni' => '36001003', 'phone' => '+5493815551003', 'loc' => 'Tafí Viejo',  'monto' => 60000,  'cuotas' => 8,  'freq' => 'weekly',  'st' => 'en_visita', 'resp' => $vendedor_1, 'origen' => 'manual' ),
    array( 'first' => 'Marcela',  'last' => 'Núñez',    'dni' => '35001004', 'phone' => '+5493815551004', 'loc' => 'Banda del Río Salí', 'monto' => 95000, 'cuotas' => 12, 'freq' => 'monthly', 'st' => 'aprobado', 'resp' => $vendedor_2, 'origen' => 'web' ),
    array( 'first' => 'Federico', 'last' => 'Ríos',     'dni' => '34001005', 'phone' => '+5493815551005', 'loc' => 'Las Talitas', 'monto' => 130000, 'cuotas' => 24, 'freq' => 'monthly', 'st' => 'nuevo',     'resp' => $vendedor_2, 'origen' => 'web' ),
    array( 'first' => 'Verónica', 'last' => 'Quiroga',  'dni' => '33001006', 'phone' => '+5493815551006', 'loc' => 'Alderetes',   'monto' => 75000,  'cuotas' => 16, 'freq' => 'weekly',  'st' => 'rechazado', 'resp' => $vendedor_2, 'origen' => 'manual' ),
    array( 'first' => 'Ramiro',   'last' => 'Bravo',    'dni' => '32001007', 'phone' => '+5493815551007', 'loc' => 'San Miguel',  'monto' => 200000, 'cuotas' => 12, 'freq' => 'monthly', 'st' => 'nuevo',     'resp' => 0,           'origen' => 'web' ),
    array( 'first' => 'Cecilia',  'last' => 'Lobo',     'dni' => '31001008', 'phone' => '+5493815551008', 'loc' => 'Famaillá',    'monto' => 50000,  'cuotas' => 10, 'freq' => 'weekly',  'st' => 'nuevo',     'resp' => 0,           'origen' => 'web' ),
);

foreach ( $leads as $i => $l ) {
    $code = sprintf( 'LEAD-DUMMY-%03d', $i + 1 );
    $id = lga_crm_dummy_post( 'lead', $code, array(
        '_lga_dummy_key'        => $code,
        'first_name'            => $l['first'],
        'last_name'             => $l['last'],
        'dni'                   => $l['dni'],
        'phone'                 => $l['phone'],
        'email'                 => strtolower( $l['first'] ) . '@example.com',
        'address_line'          => 'Av. Test ' . rand( 100, 9999 ),
        'locality'              => $l['loc'],
        'province'              => 'Tucumán',
        'postal_code'           => 'T4000',
        'requested_amount_ars'  => $l['monto'],
        'payment_frequency'     => $l['freq'],
        'requested_installments'=> $l['cuotas'],
        'declared_income_ars'   => rand( 200, 800 ) * 1000,
        'lead_status'           => $l['st'],
        'zone_status'           => 'in_zone',
        'origen'                => $l['origen'],
        'responsable'           => $l['resp'],
        'zona'                  => $l['loc'],
    ) );
    echo "Lead $id: $code → resp " . ( $l['resp'] ?: '(sin asignar)' ) . "\n";
}

echo "\n=== SEED OK ===\n";
echo "Users: 4 nuevos (vendedor-1, vendedor-2, cobrador-1, cobrador-2)\n";
echo "Clientes: " . count( $clientes ) . " dummy\n";
echo "Créditos: " . count( $creditos ) . " dummy\n";
echo "Leads: " . count( $leads ) . " dummy\n";
echo "\nLogin con cualquiera de los users en https://admin.lga-arg.com/wp-login.php\n";
echo "Passwords: Vendedor1!2026, Vendedor2!2026, Cobrador1!2026, Cobrador2!2026\n";
