<?php
/**
 * ACF Field Groups para los 3 CPTs nuevos (cliente, credito, lead).
 * Se registran programáticamente con acf_add_local_field_group() — no hace falta importar JSON.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function lga_crm_register_acf_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    // ──────────────────────────────────────────────────────────────────
    // CLIENTE
    // ──────────────────────────────────────────────────────────────────
    acf_add_local_field_group( array(
        'key'      => 'group_lga_cliente',
        'title'    => 'Datos del cliente',
        'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'cliente' ) ) ),
        'menu_order' => 0,
        'position'   => 'normal',
        'style'      => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen'        => array( 'discussion', 'comments' ),
        'active'        => true,
        'show_in_rest'  => 1,
        'fields'   => array(
            // tab Identidad
            array( 'key' => 'cli_tab_id', 'label' => 'Identidad', 'name' => '', 'type' => 'tab', 'placement' => 'top' ),
            array( 'key' => 'cli_first_name', 'label' => 'Nombre', 'name' => 'first_name', 'type' => 'text', 'required' => 1, 'show_in_rest' => 1, 'wrapper' => array( 'width' => '50' ) ),
            array( 'key' => 'cli_last_name', 'label' => 'Apellido', 'name' => 'last_name', 'type' => 'text', 'required' => 1, 'show_in_rest' => 1, 'wrapper' => array( 'width' => '50' ) ),
            array( 'key' => 'cli_dni', 'label' => 'DNI', 'name' => 'dni', 'type' => 'text', 'required' => 1, 'show_in_rest' => 1, 'wrapper' => array( 'width' => '33' ) ),
            array( 'key' => 'cli_birth_date', 'label' => 'Fecha nacimiento', 'name' => 'birth_date', 'type' => 'date_picker', 'display_format' => 'd/m/Y', 'return_format' => 'Y-m-d', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '33' ) ),
            array( 'key' => 'cli_phone', 'label' => 'Teléfono', 'name' => 'phone', 'type' => 'text', 'required' => 1, 'show_in_rest' => 1, 'wrapper' => array( 'width' => '34' ) ),
            array( 'key' => 'cli_email', 'label' => 'Email', 'name' => 'email', 'type' => 'email', 'show_in_rest' => 1 ),

            // tab Domicilio
            array( 'key' => 'cli_tab_dom', 'label' => 'Domicilio', 'name' => '', 'type' => 'tab', 'placement' => 'top' ),
            array( 'key' => 'cli_addr', 'label' => 'Dirección', 'name' => 'address_line', 'type' => 'text', 'show_in_rest' => 1 ),
            array( 'key' => 'cli_loc', 'label' => 'Localidad', 'name' => 'locality', 'type' => 'text', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '40' ) ),
            array( 'key' => 'cli_prov', 'label' => 'Provincia', 'name' => 'province', 'type' => 'text', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '40' ) ),
            array( 'key' => 'cli_postal', 'label' => 'CP', 'name' => 'postal_code', 'type' => 'text', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '20' ) ),

            // tab Ocupación
            array( 'key' => 'cli_tab_occ', 'label' => 'Ocupación', 'name' => '', 'type' => 'tab', 'placement' => 'top' ),
            array(
                'key' => 'cli_occupation', 'label' => 'Ocupación', 'name' => 'occupation', 'type' => 'select',
                'choices' => array(
                    'employed_registered' => 'Empleado registrado',
                    'self_employed_registered' => 'Monotributista / Autónomo',
                    'unregistered' => 'Trabajo informal',
                    'retired' => 'Jubilado',
                    'homemaker' => 'Ama de casa',
                    'student' => 'Estudiante',
                    'informal' => 'Changas',
                    'other' => 'Otra',
                ),
                'allow_null' => 1, 'show_in_rest' => 1,
            ),
            array( 'key' => 'cli_income', 'label' => 'Ingreso declarado (ARS)', 'name' => 'declared_income_ars', 'type' => 'number', 'min' => 0, 'show_in_rest' => 1 ),

            // tab Estado
            array( 'key' => 'cli_tab_st', 'label' => 'Estado', 'name' => '', 'type' => 'tab', 'placement' => 'top' ),
            array(
                'key' => 'cli_status', 'label' => 'Estado cliente', 'name' => 'client_status', 'type' => 'select',
                'choices' => array(
                    'lead' => 'Lead',
                    'activo' => 'Activo',
                    'bloqueado' => 'Bloqueado',
                    'archivado' => 'Archivado',
                ),
                'default_value' => 'lead', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '50' ),
            ),
            array(
                'key' => 'cli_origen', 'label' => 'Origen', 'name' => 'origen', 'type' => 'select',
                'choices' => array( 'web' => 'Web Shopify', 'manual' => 'Manual (admin)', 'migracion' => 'Migración histórica' ),
                'default_value' => 'manual', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '50' ),
            ),
            array( 'key' => 'cli_lead_ref', 'label' => 'Lead origen', 'name' => 'lead_ref', 'type' => 'post_object', 'post_type' => array( 'lead' ), 'allow_null' => 1, 'return_format' => 'id', 'show_in_rest' => 1 ),

            // tab Asignación
            array( 'key' => 'cli_tab_asg', 'label' => 'Asignación', 'name' => '', 'type' => 'tab', 'placement' => 'top' ),
            array(
                'key' => 'cli_cobrador', 'label' => 'Cobrador asignado', 'name' => 'cobrador', 'type' => 'user',
                'role' => array( 'cobrador' ), 'allow_null' => 1, 'multiple' => 0, 'return_format' => 'id', 'show_in_rest' => 1,
            ),
            array( 'key' => 'cli_zona', 'label' => 'Zona', 'name' => 'zona', 'type' => 'text', 'instructions' => 'Texto libre (futuro: relación a CPT zonas).', 'show_in_rest' => 1 ),
            array( 'key' => 'cli_notes', 'label' => 'Notas internas', 'name' => 'internal_notes', 'type' => 'textarea', 'rows' => 4, 'show_in_rest' => 1 ),
        ),
    ) );

    // ──────────────────────────────────────────────────────────────────
    // CREDITO
    // ──────────────────────────────────────────────────────────────────
    acf_add_local_field_group( array(
        'key'      => 'group_lga_credito',
        'title'    => 'Datos del crédito',
        'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'credito' ) ) ),
        'menu_order' => 0,
        'active'   => true,
        'show_in_rest' => 1,
        'fields'   => array(
            array( 'key' => 'cr_tab_link', 'label' => 'Vinculación', 'name' => '', 'type' => 'tab', 'placement' => 'top' ),
            array( 'key' => 'cr_cliente_ref', 'label' => 'Cliente', 'name' => 'cliente_ref', 'type' => 'post_object', 'post_type' => array( 'cliente' ), 'required' => 1, 'return_format' => 'id', 'show_in_rest' => 1 ),

            array( 'key' => 'cr_tab_terms', 'label' => 'Términos', 'name' => '', 'type' => 'tab', 'placement' => 'top' ),
            array( 'key' => 'cr_monto', 'label' => 'Monto (ARS)', 'name' => 'monto_ars', 'type' => 'number', 'required' => 1, 'min' => 0, 'show_in_rest' => 1, 'wrapper' => array( 'width' => '33' ) ),
            array(
                'key' => 'cr_freq', 'label' => 'Frecuencia', 'name' => 'payment_frequency', 'type' => 'select',
                'choices' => array( 'daily' => 'Diario', 'weekly' => 'Semanal', 'monthly' => 'Mensual' ),
                'default_value' => 'monthly', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '33' ),
            ),
            array( 'key' => 'cr_cuotas', 'label' => 'Cuotas totales', 'name' => 'cuotas_totales', 'type' => 'number', 'required' => 1, 'min' => 1, 'show_in_rest' => 1, 'wrapper' => array( 'width' => '34' ) ),
            array( 'key' => 'cr_cuota_estimada', 'label' => 'Cuota estimada (ARS)', 'name' => 'cuota_estimada_ars', 'type' => 'number', 'min' => 0, 'show_in_rest' => 1, 'wrapper' => array( 'width' => '50' ) ),
            array( 'key' => 'cr_tasa', 'label' => 'Tasa aplicada (%)', 'name' => 'tasa_aplicada', 'type' => 'number', 'min' => 0, 'show_in_rest' => 1, 'wrapper' => array( 'width' => '50' ) ),
            array( 'key' => 'cr_fecha_alta', 'label' => 'Fecha alta', 'name' => 'fecha_alta', 'type' => 'date_picker', 'display_format' => 'd/m/Y', 'return_format' => 'Y-m-d', 'show_in_rest' => 1 ),

            array( 'key' => 'cr_tab_state', 'label' => 'Estado', 'name' => '', 'type' => 'tab', 'placement' => 'top' ),
            array(
                'key' => 'cr_status', 'label' => 'Estado crédito', 'name' => 'credit_status', 'type' => 'select',
                'choices' => array(
                    'pendiente_aprobacion' => 'Pendiente aprobación',
                    'activo' => 'Activo',
                    'al_dia' => 'Al día',
                    'en_mora' => 'En mora',
                    'pagado' => 'Pagado',
                    'cancelado' => 'Cancelado',
                ),
                'default_value' => 'pendiente_aprobacion', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '50' ),
            ),
            array( 'key' => 'cr_cuotas_pagadas', 'label' => 'Cuotas pagadas', 'name' => 'cuotas_pagadas', 'type' => 'number', 'min' => 0, 'default_value' => 0, 'show_in_rest' => 1, 'wrapper' => array( 'width' => '25' ) ),
            array( 'key' => 'cr_saldo', 'label' => 'Saldo (ARS)', 'name' => 'saldo_ars', 'type' => 'number', 'min' => 0, 'show_in_rest' => 1, 'wrapper' => array( 'width' => '25' ) ),
            array( 'key' => 'cr_proxima', 'label' => 'Próxima fecha pago', 'name' => 'proxima_fecha_pago', 'type' => 'date_picker', 'display_format' => 'd/m/Y', 'return_format' => 'Y-m-d', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '50' ) ),

            array( 'key' => 'cr_tab_shop', 'label' => 'Producto Shopify', 'name' => '', 'type' => 'tab', 'placement' => 'top' ),
            array( 'key' => 'cr_product', 'label' => 'Producto', 'name' => 'product_title', 'type' => 'text', 'show_in_rest' => 1 ),
            array( 'key' => 'cr_cart_total', 'label' => 'Total carrito (ARS)', 'name' => 'cart_total_ars', 'type' => 'number', 'min' => 0, 'show_in_rest' => 1, 'wrapper' => array( 'width' => '50' ) ),
            array( 'key' => 'cr_source', 'label' => 'Source', 'name' => 'source', 'type' => 'text', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '50' ) ),

            array( 'key' => 'cr_notes', 'label' => 'Notas internas', 'name' => 'internal_notes', 'type' => 'textarea', 'rows' => 4, 'show_in_rest' => 1 ),
        ),
    ) );

    // ──────────────────────────────────────────────────────────────────
    // LEAD
    // ──────────────────────────────────────────────────────────────────
    acf_add_local_field_group( array(
        'key'      => 'group_lga_lead',
        'title'    => 'Datos del lead',
        'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'lead' ) ) ),
        'menu_order' => 0,
        'active'   => true,
        'show_in_rest' => 1,
        'fields'   => array(
            array( 'key' => 'ld_tab_id', 'label' => 'Identidad', 'name' => '', 'type' => 'tab', 'placement' => 'top' ),
            array( 'key' => 'ld_first', 'label' => 'Nombre', 'name' => 'first_name', 'type' => 'text', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '50' ) ),
            array( 'key' => 'ld_last', 'label' => 'Apellido', 'name' => 'last_name', 'type' => 'text', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '50' ) ),
            array( 'key' => 'ld_dni', 'label' => 'DNI', 'name' => 'dni', 'type' => 'text', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '33' ) ),
            array( 'key' => 'ld_birth', 'label' => 'Nacimiento', 'name' => 'birth_date', 'type' => 'date_picker', 'display_format' => 'd/m/Y', 'return_format' => 'Y-m-d', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '33' ) ),
            array( 'key' => 'ld_phone', 'label' => 'Teléfono', 'name' => 'phone', 'type' => 'text', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '34' ) ),
            array( 'key' => 'ld_email', 'label' => 'Email', 'name' => 'email', 'type' => 'email', 'show_in_rest' => 1 ),

            array( 'key' => 'ld_tab_dom', 'label' => 'Domicilio', 'name' => '', 'type' => 'tab', 'placement' => 'top' ),
            array( 'key' => 'ld_addr', 'label' => 'Dirección', 'name' => 'address_line', 'type' => 'text', 'show_in_rest' => 1 ),
            array( 'key' => 'ld_loc', 'label' => 'Localidad', 'name' => 'locality', 'type' => 'text', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '40' ) ),
            array( 'key' => 'ld_prov', 'label' => 'Provincia', 'name' => 'province', 'type' => 'text', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '40' ) ),
            array( 'key' => 'ld_postal', 'label' => 'CP', 'name' => 'postal_code', 'type' => 'text', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '20' ) ),

            array( 'key' => 'ld_tab_cr', 'label' => 'Crédito pretendido', 'name' => '', 'type' => 'tab', 'placement' => 'top' ),
            array( 'key' => 'ld_amount', 'label' => 'Monto solicitado (ARS)', 'name' => 'requested_amount_ars', 'type' => 'number', 'min' => 0, 'show_in_rest' => 1, 'wrapper' => array( 'width' => '33' ) ),
            array(
                'key' => 'ld_freq', 'label' => 'Frecuencia', 'name' => 'payment_frequency', 'type' => 'select',
                'choices' => array( 'daily' => 'Diario', 'weekly' => 'Semanal', 'monthly' => 'Mensual' ),
                'show_in_rest' => 1, 'wrapper' => array( 'width' => '33' ),
            ),
            array( 'key' => 'ld_cuotas', 'label' => 'Cuotas', 'name' => 'requested_installments', 'type' => 'number', 'min' => 1, 'show_in_rest' => 1, 'wrapper' => array( 'width' => '34' ) ),
            array( 'key' => 'ld_income', 'label' => 'Ingreso declarado (ARS)', 'name' => 'declared_income_ars', 'type' => 'number', 'min' => 0, 'show_in_rest' => 1 ),

            array( 'key' => 'ld_tab_st', 'label' => 'Estado', 'name' => '', 'type' => 'tab', 'placement' => 'top' ),
            array(
                'key' => 'ld_status', 'label' => 'Estado del lead', 'name' => 'lead_status', 'type' => 'select',
                'choices' => array(
                    'nuevo' => 'Nuevo',
                    'en_visita' => 'En visita',
                    'aprobado' => 'Aprobado (listo para promover)',
                    'rechazado' => 'Rechazado',
                    'perdido' => 'Perdido',
                ),
                'default_value' => 'nuevo', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '50' ),
            ),
            array(
                'key' => 'ld_zone', 'label' => 'Estado de zona', 'name' => 'zone_status', 'type' => 'select',
                'choices' => array( 'in_zone' => 'En zona', 'needs_review' => 'Revisar', 'out_of_zone' => 'Fuera de zona' ),
                'allow_null' => 1, 'show_in_rest' => 1, 'wrapper' => array( 'width' => '50' ),
            ),
            array( 'key' => 'ld_notes', 'label' => 'Notas internas', 'name' => 'internal_notes', 'type' => 'textarea', 'rows' => 4, 'show_in_rest' => 1 ),

            array( 'key' => 'ld_tab_org', 'label' => 'Origen', 'name' => '', 'type' => 'tab', 'placement' => 'top' ),
            array(
                'key' => 'ld_origen', 'label' => 'Origen', 'name' => 'origen', 'type' => 'select',
                'choices' => array( 'web' => 'Web Shopify', 'manual' => 'Manual (admin)' ),
                'default_value' => 'web', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '50' ),
            ),
            array( 'key' => 'ld_sol_ref', 'label' => 'Solicitud original', 'name' => 'solicitud_ref', 'type' => 'post_object', 'post_type' => array( 'solicitud' ), 'allow_null' => 1, 'return_format' => 'id', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '50' ) ),
            array( 'key' => 'ld_product', 'label' => 'Producto Shopify', 'name' => 'product_title', 'type' => 'text', 'show_in_rest' => 1 ),
            array( 'key' => 'ld_utm_source', 'label' => 'utm_source', 'name' => 'utm_source', 'type' => 'text', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '50' ) ),
            array( 'key' => 'ld_utm_campaign', 'label' => 'utm_campaign', 'name' => 'utm_campaign', 'type' => 'text', 'show_in_rest' => 1, 'wrapper' => array( 'width' => '50' ) ),

            array( 'key' => 'ld_tab_asg', 'label' => 'Asignación', 'name' => '', 'type' => 'tab', 'placement' => 'top' ),
            array(
                'key' => 'ld_resp', 'label' => 'Responsable (vendedor)', 'name' => 'responsable', 'type' => 'user',
                'role' => array( 'vendedor', 'administrator' ), 'allow_null' => 1, 'multiple' => 0, 'return_format' => 'id', 'show_in_rest' => 1,
            ),
            array( 'key' => 'ld_zona', 'label' => 'Zona', 'name' => 'zona', 'type' => 'text', 'show_in_rest' => 1 ),
        ),
    ) );

    // ──────────────────────────────────────────────────────────────────
    // SHOPIFY (compartido entre solicitud, lead, cliente, credito)
    // ──────────────────────────────────────────────────────────────────
    acf_add_local_field_group( array(
        'key'      => 'group_lga_shopify',
        'title'    => 'Shopify (draft / order / sync)',
        'location' => array(
            array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'solicitud' ) ),
            array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'lead'      ) ),
            array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'cliente'   ) ),
            array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'credito'   ) ),
        ),
        'menu_order' => 50,
        'position'   => 'side',
        'style'      => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'        => true,
        'show_in_rest'  => 1,
        'fields'   => array(
            array(
                'key' => 'sh_status', 'label' => 'Estado Shopify', 'name' => 'shopify_status', 'type' => 'select',
                'choices' => array(
                    ''                  => '— Sin Shopify —',
                    'draft_created'     => 'Borrador creado',
                    'draft_deleted'     => 'Borrador eliminado',
                    'order_unfulfilled' => 'Pedido — No preparado',
                    'order_cancelled'   => 'Pedido cancelado',
                    'error'             => 'Error',
                ),
                'allow_null' => 1, 'show_in_rest' => 1, 'wrapper' => array( 'class' => 'lga-readonly' ),
            ),
            array( 'key' => 'sh_draft_id',   'label' => 'Draft Order ID',   'name' => 'shopify_draft_order_id',   'type' => 'text', 'readonly' => 1, 'show_in_rest' => 1 ),
            array( 'key' => 'sh_draft_name', 'label' => 'Draft Order Nº',   'name' => 'shopify_draft_order_name', 'type' => 'text', 'readonly' => 1, 'show_in_rest' => 1 ),
            array( 'key' => 'sh_order_id',   'label' => 'Order ID',         'name' => 'shopify_order_id',         'type' => 'text', 'readonly' => 1, 'show_in_rest' => 1 ),
            array( 'key' => 'sh_order_name', 'label' => 'Order Nº',         'name' => 'shopify_order_name',       'type' => 'text', 'readonly' => 1, 'show_in_rest' => 1 ),
            array( 'key' => 'sh_fulfill',    'label' => 'Fulfillment',      'name' => 'shopify_order_fulfillment_status', 'type' => 'text', 'readonly' => 1, 'show_in_rest' => 1 ),
            array( 'key' => 'sh_invoice',    'label' => 'Invoice URL',      'name' => 'shopify_invoice_url',      'type' => 'url',  'readonly' => 1, 'show_in_rest' => 1 ),
            array( 'key' => 'sh_synced',     'label' => 'Última sync',      'name' => 'shopify_last_sync_at',     'type' => 'text', 'readonly' => 1, 'show_in_rest' => 1 ),
            array( 'key' => 'sh_last_err',   'label' => 'Último error',     'name' => 'shopify_last_error',       'type' => 'textarea', 'rows' => 2, 'readonly' => 1, 'show_in_rest' => 1 ),
        ),
    ) );
}
