<?php
/**
 * Custom Post Types: cliente, credito, lead.
 * solicitud ya existe (creado vía CPT UI en F1), no se toca acá.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function lga_crm_register_cpts() {
    // ─── cliente ─────────────────────────────────────────────────────────
    register_post_type( 'cliente', array(
        'label'              => 'Clientes',
        'labels'             => array(
            'name'                  => 'Clientes',
            'singular_name'         => 'Cliente',
            'menu_name'             => 'Clientes',
            'name_admin_bar'        => 'Cliente',
            'add_new'               => 'Añadir nuevo',
            'add_new_item'          => 'Añadir nuevo cliente',
            'edit_item'             => 'Editar cliente',
            'new_item'              => 'Nuevo cliente',
            'view_item'             => 'Ver cliente',
            'all_items'             => 'Todos los clientes',
            'search_items'          => 'Buscar clientes',
        ),
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'rest_base'          => 'clientes',
        'menu_icon'          => 'dashicons-businessperson',
        'menu_position'      => 6,
        'supports'           => array( 'title', 'editor', 'author', 'revisions', 'custom-fields' ),
        'capability_type'    => array( 'cliente', 'clientes' ),
        'map_meta_cap'       => true,
        'has_archive'        => false,
        'rewrite'            => false,
    ) );

    // ─── credito ─────────────────────────────────────────────────────────
    register_post_type( 'credito', array(
        'label'              => 'Créditos',
        'labels'             => array(
            'name'                  => 'Créditos',
            'singular_name'         => 'Crédito',
            'menu_name'             => 'Créditos',
            'add_new'               => 'Añadir nuevo',
            'add_new_item'          => 'Añadir nuevo crédito',
            'edit_item'             => 'Editar crédito',
            'new_item'              => 'Nuevo crédito',
            'view_item'             => 'Ver crédito',
            'all_items'             => 'Todos los créditos',
            'search_items'          => 'Buscar créditos',
        ),
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'rest_base'          => 'creditos',
        'menu_icon'          => 'dashicons-money-alt',
        'menu_position'      => 7,
        'supports'           => array( 'title', 'editor', 'author', 'revisions', 'custom-fields' ),
        'capability_type'    => array( 'credito', 'creditos' ),
        'map_meta_cap'       => true,
        'has_archive'        => false,
        'rewrite'            => false,
    ) );

    // ─── lead ────────────────────────────────────────────────────────────
    register_post_type( 'lead', array(
        'label'              => 'Leads',
        'labels'             => array(
            'name'                  => 'Leads',
            'singular_name'         => 'Lead',
            'menu_name'             => 'Leads',
            'add_new'               => 'Añadir nuevo',
            'add_new_item'          => 'Añadir nuevo lead',
            'edit_item'             => 'Editar lead',
            'new_item'              => 'Nuevo lead',
            'view_item'             => 'Ver lead',
            'all_items'             => 'Todos los leads',
            'search_items'          => 'Buscar leads',
        ),
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'rest_base'          => 'leads',
        'menu_icon'          => 'dashicons-phone',
        'menu_position'      => 5,
        'supports'           => array( 'title', 'editor', 'author', 'revisions', 'custom-fields' ),
        'capability_type'    => array( 'lead', 'leads' ),
        'map_meta_cap'       => true,
        'has_archive'        => false,
        'rewrite'            => false,
    ) );
}
