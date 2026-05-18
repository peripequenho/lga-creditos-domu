<?php
/**
 * /panel router: detecta rol y redirige al panel correspondiente.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$role = lga_crm_current_role();
$target = '/panel/admin';
if ( $role === 'vendedor' ) {
    $target = '/panel/vendedor';
} elseif ( $role === 'cobrador' ) {
    $target = '/panel/cobrador';
}
wp_safe_redirect( home_url( $target ) );
exit;
