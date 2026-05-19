<?php
/**
 * Dev: re-propagar meta shopify desde un lead-fuente a una lista de target IDs.
 *
 * Uso: visitar https://admin.lga-arg.com/wp-content/mu-plugins/lga-crm/dev/backfill-shopify-meta.php?key=lga-2026-kp4xnt&source=109&targets=110,111,112
 *
 * Solo accesible con el secret key. NO commitear con el key real público.
 */

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

if ( ( $_GET['key'] ?? '' ) !== 'lga-2026-kp4xnt' ) {
    http_response_code( 403 );
    exit( 'forbidden' );
}

$source  = (int) ( $_GET['source'] ?? 0 );
$targets = array_filter( array_map( 'intval', explode( ',', $_GET['targets'] ?? '' ) ) );

if ( ! $source || empty( $targets ) ) {
    http_response_code( 400 );
    exit( 'use ?source=<lead_id>&targets=<id1,id2,...>' );
}

header( 'Content-Type: text/plain; charset=utf-8' );

$keys = array(
    'shopify_draft_order_id',
    'shopify_draft_order_gid',
    'shopify_draft_order_name',
    'shopify_invoice_url',
    'shopify_order_id',
    'shopify_order_gid',
    'shopify_order_name',
    'shopify_order_fulfillment_status',
    'shopify_order_financial_status',
    'shopify_status',
    'shopify_last_sync_at',
);

echo "=== Source post #{$source} ===\n";
$src_meta = array();
foreach ( $keys as $k ) {
    $v = get_post_meta( $source, $k, true );
    $src_meta[ $k ] = $v;
    printf( "  %-40s = %s\n", $k, is_scalar( $v ) ? $v : json_encode( $v ) );
}

echo "\n=== Backfilling to targets ===\n";
foreach ( $targets as $tid ) {
    $ptype = get_post_type( $tid );
    echo "Target #{$tid} ({$ptype}):\n";
    if ( ! $ptype ) {
        echo "  (post not found, skipping)\n";
        continue;
    }
    $changes = 0;
    foreach ( $keys as $k ) {
        $v = $src_meta[ $k ];
        if ( $v === '' || $v === null ) continue;
        $current = get_post_meta( $tid, $k, true );
        if ( $current === $v ) {
            echo "  = {$k} (already same)\n";
            continue;
        }
        update_post_meta( $tid, $k, $v );
        echo "  ✓ {$k} = {$v}\n";
        $changes++;
    }
    echo "  >> {$changes} meta(s) updated\n\n";
}

echo "DONE.\n";
