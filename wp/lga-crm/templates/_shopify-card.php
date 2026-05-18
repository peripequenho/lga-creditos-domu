<?php
/**
 * Partial: card Shopify reusable.
 * Uso:
 *   $shopify_post_id = $lead_id; // o $cli_id o $credito_id
 *   include LGA_CRM_DIR . 'templates/_shopify-card.php';
 *
 * Muestra: status badge, draft/order names, fulfillment, sync timestamp,
 * link "Abrir en Shopify Admin". Si no hay draft NI order, no renderiza nada.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( empty( $shopify_post_id ) ) return;

$sh_draft = get_post_meta( $shopify_post_id, 'shopify_draft_order_id', true );
$sh_order = get_post_meta( $shopify_post_id, 'shopify_order_id', true );
if ( ! $sh_draft && ! $sh_order ) return;
?>
<div class="lga-card p-5 text-xs">
    <h3 class="text-sm font-semibold mb-3 flex items-center gap-2">
        <svg class="w-4 h-4 text-emerald-700" viewBox="0 0 109 124" fill="currentColor" aria-hidden="true"><path d="M74.7 14.8c-.1-.4-.5-.7-.9-.7l-1.5-.1L66.4 6c-.2-.2-.5-.3-.7-.2L62.8 7C60.5 2.2 56.6 0 53 0c-.4 0-.7 0-1.1.1-.5-.7-1.2-1.4-2-1.9-2.2-1.4-4.8-1.3-7.5-.3C36.7.5 31.4 9.1 28.5 18.5l-6.1 1.9c-1.9.6-2 .7-2.2 2.5-.2 1.4-5.2 39.6-5.2 39.6l37.3 6.5 21.6-4.5s.1-49 .1-49.7c0-.1-.1-.1-.1 0z"/></svg>
        Shopify
    </h3>
    <?php echo lga_crm_shopify_status_badge( $shopify_post_id ); ?>
    <dl class="space-y-1 mt-3">
        <?php if ( $sh_draft ): ?>
        <div><dt class="text-zinc-500">Draft Order</dt><dd class="font-mono text-xs"><?php echo esc_html( get_post_meta( $shopify_post_id, 'shopify_draft_order_name', true ) ?: $sh_draft ); ?></dd></div>
        <?php endif; ?>
        <?php if ( $sh_order ): ?>
        <div><dt class="text-zinc-500">Order</dt><dd class="font-mono text-xs"><?php echo esc_html( get_post_meta( $shopify_post_id, 'shopify_order_name', true ) ?: $sh_order ); ?></dd></div>
        <div><dt class="text-zinc-500">Fulfillment</dt><dd class="text-xs"><?php echo esc_html( get_post_meta( $shopify_post_id, 'shopify_order_fulfillment_status', true ) ?: '—' ); ?></dd></div>
        <?php endif; ?>
        <?php $sync = get_post_meta( $shopify_post_id, 'shopify_last_sync_at', true ); if ( $sync ): ?>
        <div><dt class="text-zinc-500">Última sync</dt><dd class="tabular-nums text-xs"><?php echo esc_html( $sync ); ?></dd></div>
        <?php endif; ?>
        <?php $err = get_post_meta( $shopify_post_id, 'shopify_last_error', true ); if ( $err ): ?>
        <div><dt class="text-red-600">Error</dt><dd class="text-red-600 text-xs"><?php echo esc_html( $err ); ?></dd></div>
        <?php endif; ?>
    </dl>
    <?php $link = $sh_order ? lga_crm_shopify_admin_link_order( $shopify_post_id ) : lga_crm_shopify_admin_link_draft( $shopify_post_id ); ?>
    <?php if ( $link ): ?>
    <a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener" class="block mt-3 text-center text-xs font-medium text-emerald-700 hover:text-emerald-800 underline">Abrir en Shopify Admin ↗</a>
    <?php endif; ?>
</div>
