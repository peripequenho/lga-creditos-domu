<?php
/**
 * Plugin Name:       LGA CRM
 * Description:       Sistema operativo LGA: roles (admin/vendedor/cobrador), CPTs (cliente/credito/lead) y dashboards frontend (/panel).
 * Version:           0.1.0
 * Author:            LGA + Claude
 * Requires PHP:      7.4
 * Requires at least: 6.0
 *
 * Carga como mu-plugin desde /wp-content/mu-plugins/lga-crm.php (loader) o como plugin tradicional.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'LGA_CRM_VERSION', '0.3.1' );
define( 'LGA_CRM_DIR', plugin_dir_path( __FILE__ ) );
define( 'LGA_CRM_URL', plugin_dir_url( __FILE__ ) );

/**
 * Bootstrap del plugin.
 * El orden importa: primero CPTs/roles (registrados en `init`), después routing.
 */
function lga_crm_bootstrap() {
    require_once LGA_CRM_DIR . 'inc/cpts.php';
    require_once LGA_CRM_DIR . 'inc/roles.php';
    require_once LGA_CRM_DIR . 'inc/acf.php';
    require_once LGA_CRM_DIR . 'inc/routing.php';
    require_once LGA_CRM_DIR . 'inc/login.php';
    require_once LGA_CRM_DIR . 'inc/queries.php';
    require_once LGA_CRM_DIR . 'inc/handlers.php';
    require_once LGA_CRM_DIR . 'inc/shopify.php';

    // Hook order: roles primero, después CPTs (caps dependen de roles existir)
    add_action( 'init', 'lga_crm_register_roles', 5 );
    add_action( 'init', 'lga_crm_register_cpts', 10 );
    add_action( 'init', 'lga_crm_register_rewrites', 11 );
    add_action( 'acf/init', 'lga_crm_register_acf_fields' );
    add_action( 'template_redirect', 'lga_crm_router' );
    add_filter( 'login_redirect', 'lga_crm_login_redirect', 10, 3 );
    add_action( 'pre_get_posts', 'lga_crm_filter_by_role' );
    add_action( 'admin_post_lga_create_cliente', 'lga_crm_handle_create_cliente' );
    add_action( 'admin_post_lga_create_credito', 'lga_crm_handle_create_credito' );
    add_action( 'admin_post_lga_update_lead_status', 'lga_crm_handle_update_lead_status' );
    add_action( 'admin_post_lga_convert_solicitud', 'lga_crm_handle_convert_solicitud' );
    add_action( 'admin_post_lga_promote_lead', 'lga_crm_handle_promote_lead' );

    // Custom rewrite tags
    add_filter( 'query_vars', function ( $vars ) {
        $vars[] = 'lga_route';
        $vars[] = 'lga_id';
        return $vars;
    } );

    // Flush rewrites on activation/deactivation (versioned to avoid running every load)
    add_action( 'init', function () {
        $current = get_option( 'lga_crm_rewrite_v' );
        if ( $current !== LGA_CRM_VERSION ) {
            flush_rewrite_rules();
            update_option( 'lga_crm_rewrite_v', LGA_CRM_VERSION );
        }
    }, 99 );

    // Excluir todas las rutas LGA del LiteSpeed Cache.
    // Sin esto, el HTML de los paneles se cacheaba por URL y mostraba estados viejos
    // después de aprobar/promover/crear (y el toggle dark se "perdía" en algunas rutas).
    add_action( 'template_redirect', function () {
        if ( ! lga_crm_is_lga_page() ) return;
        // Filter API oficial de LiteSpeed Cache plugin
        add_filter( 'litespeed_control_no_cache', '__return_true' );
        // Header crudo para LSWS standalone (sin plugin)
        if ( ! headers_sent() ) {
            header( 'Cache-Control: private, no-cache, no-store, must-revalidate, max-age=0', true );
            header( 'X-LiteSpeed-Cache-Control: no-cache' );
            header( 'Pragma: no-cache' );
            header( 'Expires: 0' );
        }
    }, 1 );

    // Enqueue assets (Tailwind CDN para velocidad)
    add_action( 'wp_enqueue_scripts', function () {
        if ( ! lga_crm_is_lga_page() ) {
            return;
        }
        wp_register_script( 'lga-tailwind-cdn', 'https://cdn.tailwindcss.com', array(), null, false );
        wp_enqueue_script( 'lga-tailwind-cdn' );
        wp_register_style( 'lga-crm-style', LGA_CRM_URL . 'assets/style.css', array(), LGA_CRM_VERSION );
        wp_enqueue_style( 'lga-crm-style' );
    } );
}
lga_crm_bootstrap();

/**
 * Helper global: true si la request es para una página LGA-CRM.
 */
function lga_crm_is_lga_page() {
    $route = get_query_var( 'lga_route' );
    return ! empty( $route );
}
