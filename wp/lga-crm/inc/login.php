<?php
/**
 * Login: redirect post-login según rol.
 * admin → /panel/admin · vendedor → /panel/vendedor · cobrador → /panel/cobrador
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function lga_crm_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
    if ( is_wp_error( $user ) || ! $user instanceof WP_User ) {
        return $redirect_to;
    }

    // Bug fix: usar wp_validate_redirect para evitar open redirect a dominios externos.
    // El comportamiento previo (`strpos($url, '/wp-admin') === false`) aceptaba CUALQUIER
    // URL externa que no contuviera '/wp-admin', incluyendo phishing.
    if ( $requested_redirect_to && strpos( $requested_redirect_to, '/wp-admin' ) === false ) {
        $safe = wp_validate_redirect( $requested_redirect_to, '' );
        if ( $safe ) {
            return $safe;
        }
    }

    $roles = (array) $user->roles;
    if ( in_array( 'administrator', $roles, true ) ) {
        return home_url( '/panel/admin' );
    }
    if ( in_array( 'vendedor', $roles, true ) ) {
        return home_url( '/panel/vendedor' );
    }
    if ( in_array( 'cobrador', $roles, true ) ) {
        return home_url( '/panel/cobrador' );
    }
    return home_url( '/panel' );
}

/**
 * Style del wp-login.php con branding LGA.
 */
add_action( 'login_enqueue_scripts', function () {
    ?>
    <style>
        body.login { background: linear-gradient(135deg, #0F766E 0%, #134E4A 100%) !important; }
        body.login #login h1 a {
            background-image: none !important;
            color: #fff !important;
            font-size: 28px !important;
            font-weight: 700 !important;
            text-indent: 0 !important;
            width: auto !important; height: auto !important;
            text-decoration: none !important;
        }
        body.login #login h1 a::before { content: 'LGA · Panel'; }
        body.login .login form { border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,.25); }
        body.login .wp-core-ui .button-primary {
            background: #0F766E !important; border-color: #0F766E !important; box-shadow: none !important;
            text-shadow: none !important; border-radius: 8px !important;
        }
        body.login .wp-core-ui .button-primary:hover { background: #134E4A !important; border-color: #134E4A !important; }
    </style>
    <?php
} );

add_filter( 'login_headerurl', function () { return home_url( '/' ); } );
add_filter( 'login_headertext', function () { return 'LGA · Panel administrativo'; } );
