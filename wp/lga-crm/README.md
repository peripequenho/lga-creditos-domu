# LGA CRM · mu-plugin

Sistema operativo del dashboard LGA: 3 CPTs (`cliente`, `credito`, `lead`) + 3 roles + dashboards frontend en `/panel/*` + flujo de adquisición manual.

## Estructura

```
wp/
├── lga-crm-loader.php          # Loader que va en /wp-content/mu-plugins/
└── lga-crm/                    # La carpeta que va en /wp-content/mu-plugins/lga-crm/
    ├── lga-crm.php             # Bootstrap del plugin
    ├── README.md               # Este archivo
    ├── inc/
    │   ├── cpts.php            # CPTs cliente, credito, lead
    │   ├── roles.php           # Roles vendedor + cobrador + caps custom
    │   ├── acf.php             # Field groups ACF programáticos
    │   ├── routing.php         # Rewrites + template loader (/panel/*, /lead/*, etc)
    │   ├── login.php           # Redirect post-login por rol + branding wp-login
    │   ├── queries.php         # Helpers de queries filtradas por rol
    │   └── handlers.php        # POST handlers (admin-post.php)
    ├── templates/
    │   ├── _layout.php         # Header/nav/footer común
    │   ├── panel-router.php    # /panel → redirect según rol
    │   ├── panel-admin.php     # Dashboard admin (4 tabs)
    │   ├── panel-vendedor.php  # Dashboard vendedor (sus leads)
    │   ├── panel-cobrador.php  # Dashboard cobrador (sus clientes + créditos)
    │   ├── lead-detail.php     # Ficha lead
    │   ├── cliente-detail.php  # Ficha cliente con créditos asociados
    │   ├── credito-detail.php  # Ficha crédito
    │   ├── cliente-nuevo.php   # Form alta manual cliente (admin)
    │   ├── credito-asignar.php # Form alta crédito (admin)
    │   ├── aprobar-solicitud.php # Convertir solicitud web → lead
    │   └── promover-lead.php   # Lead aprobado → cliente + crédito
    ├── dev/
    │   └── seed-dummy.php      # 4 users dummy + 7 clientes + 9 créditos + 8 leads
    └── assets/
        └── style.css           # Estilos extra sobre Tailwind CDN
```

## Deploy al VPS Hostinger

### Opción A — File Manager (más fácil)

1. Login a hPanel → seleccionar el sitio `admin.lga-arg.com` → **Archivos → Administrador de archivos**.
2. Navegar a `/public_html/wp-content/`.
3. Crear carpeta `mu-plugins/` si no existe.
4. Dentro de `mu-plugins/`:
   - Subir `wp/lga-crm-loader.php` (un archivo suelto).
   - Subir la carpeta entera `wp/lga-crm/` (con todos sus subdirs).

### Opción B — SFTP

```bash
# Credenciales SFTP en hPanel → Avanzado → Detalles de conexión SSH / FTP
sftp -P <puerto> u<XXX>@<host>.hostinger.com
> cd public_html/wp-content/mu-plugins
> put /local/path/lga-crm-loader.php
> mkdir lga-crm
> put -r /local/path/lga-crm/*
```

### Opción C — Git pull desde el VPS (si tenés SSH)

Ya está versionado bajo `wp/lga-crm/` del repo `peripequenho/lga-creditos-domu`. Si el VPS tiene git:

```bash
ssh root@<vps>
cd /var/www/.../wp-content/mu-plugins
git clone https://github.com/peripequenho/lga-creditos-domu.git tmp
mv tmp/wp/lga-crm-loader.php .
mv tmp/wp/lga-crm .
rm -rf tmp
```

## Activación

mu-plugins se activan **solos** (no aparecen en Plugins → Plugins instalados como toggleables). Al subir los archivos, ya están activos.

Para verificar: ir a `/wp-admin/` → en el sidebar deberían aparecer **Clientes / Créditos / Leads** (admin only). Los CPTs estarán expuestos en wp-admin para edición técnica + en frontend `/panel/*` para uso operativo.

## Ejecutar el seed

Tres opciones:

### Opción 1 — WP-CLI (si el VPS lo tiene)
```bash
ssh root@<vps>
cd /var/www/.../wp-content/mu-plugins/lga-crm
wp eval-file dev/seed-dummy.php --user=gerolopezge@gmail.com
```

### Opción 2 — Trigger HTTP (más fácil, sin SSH)
Como admin logueado, abrir en el navegador:
```
https://admin.lga-arg.com/wp-content/mu-plugins/lga-crm/dev/seed-dummy.php
```
El script verifica `current_user_can('manage_options')`; si estás logueado como admin, corre.
(Si no es accesible directamente por permisos de Hostinger sobre PHP files en mu-plugins, usar la Opción 3.)

### Opción 3 — Endpoint admin custom
Crear una nota: el script `dev/seed-dummy.php` puede ser invocado vía wp-admin de varias formas. Si las opciones 1 y 2 fallan, copiar su contenido a un Code Snippet (plugin "WPCode") y ejecutar 1 vez.

## Verificación E2E

Después de subir + correr seed:

1. Login como admin → `https://admin.lga-arg.com/wp-login.php` → redirige a `/panel/admin` ✓
2. En `/panel/admin` ver tabs **Solicitudes pendientes / Leads / Clientes / Créditos** con counts > 0.
3. Logout → login como `vendedor-1` (pass `Vendedor1!2026`) → debería ir a `/panel/vendedor`.
4. Ver SOLO los 3 leads asignados a vendedor-1 (no los 8 totales).
5. Click en un lead → cambiar estado → save → ver el cambio reflejado.
6. Logout → login como `cobrador-1` (pass `Cobrador1!2026`) → `/panel/cobrador`.
7. Ver SOLO sus 4 clientes + 5 créditos. Click un crédito → ficha.
8. Test seguridad: como cobrador-1, intentar acceder a `/lead/<algun_lead_id>` → redirige a `/panel`.
9. Como admin, ir a `/admin/nuevo-cliente` → llenar form → save → redirige a `/cliente/<id>`.
10. Desde la ficha, click "Asignar nuevo crédito" → form → save → `/credito/<id>` activo.

## Roles + permisos resumen

| Rol           | /panel/admin | /panel/vendedor | /panel/cobrador | Ve leads     | Ve clientes  | Crear cliente | Aprobar     |
|---------------|--------------|-----------------|-----------------|--------------|--------------|---------------|-------------|
| administrator | ✓            | ✓               | ✓               | TODOS        | TODOS        | ✓             | ✓           |
| vendedor      | redirect     | ✓               | redirect        | sólo suyos   | —            | —             | —           |
| cobrador      | redirect     | redirect        | ✓               | —            | sólo suyos   | —             | —           |

Filtrado server-side via `pre_get_posts` + meta queries por `responsable` (lead) y `cobrador` (cliente). No es solo UI: es DB-level.

## Flujos

### Adquisición web (sin cambios)
Form Vercel → `n8n` VPS → crea CPT `solicitud`. El admin ve la solicitud en `/panel/admin → tab Solicitudes`, click "Convertir a lead" → asigna vendedor → `/lead/<id>`.

### Adquisición manual (nueva)
Admin → `/admin/nuevo-cliente` → form → save → `/cliente/<id>` → "Asignar nuevo crédito" → form → save → `/credito/<id>`. Permite cliente sin crédito (queda con `client_status=lead`).

### Aprobación
Vendedor cambia estado del lead a `aprobado` → admin lo ve en su panel → click "Promover a cliente+crédito" → genera CPT cliente + CPT credito en una sola acción, asigna cobrador.

## Limpieza (rollback)

Todo lo del seed tiene meta `_lga_dummy=1`. Para borrar:

```php
foreach ( get_posts( array( 'post_type' => array('cliente','credito','lead'), 'posts_per_page' => -1,
    'meta_query' => array( array( 'key' => '_lga_dummy', 'value' => '1' ) ),
) ) as $p ) { wp_delete_post( $p->ID, true ); }
foreach ( get_users( array( 'meta_key' => '_lga_dummy', 'meta_value' => '1' ) ) as $u ) { wp_delete_user( $u->ID ); }
```

Y para desactivar el plugin entero: borrar `/wp-content/mu-plugins/lga-crm-loader.php` (mantiene la carpeta `lga-crm/` por las dudas).
