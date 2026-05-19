# LGA CRM — Auditoría de bugs (2026-05-18)

> Auditoría general del mu-plugin `lga-crm` v0.3.7 corriendo en `admin.lga-arg.com` (Hostinger VPS). Pendiente de resolver más adelante; este doc queda como pendientes mientras Gero trabaja en otros temas operativos (paralelización del flujo form→Shopify→Telegram, etc).

## 🔴 Críticos / Seguridad

### 1. Open redirect en login post-auth
**Archivo**: `inc/login.php:17`
**Problema**: cualquier `redirect_to` que NO contenga `/wp-admin` se respeta tal cual.
```php
if ( $requested_redirect_to && strpos( $requested_redirect_to, '/wp-admin' ) === false ) {
    return $requested_redirect_to;
}
```
**Ataque**: `https://admin.lga-arg.com/wp-login.php?redirect_to=https://evil.com/phish` — al loguearse el usuario va a `evil.com`.
**Fix**: usar `wp_validate_redirect( $requested_redirect_to, home_url('/panel') )` que valida contra allowlist de hosts y cae a default si no matchea.

### 2. Cobrador puede ver créditos ajenos vía wp-admin
**Archivo**: `inc/queries.php:63-67`
**Problema**: el filtro `pre_get_posts` está implementado para `lead` (vendedor) y `cliente` (cobrador) pero NO para `credito`. El comentario lo admite:
```php
if ( $post_type === 'credito' && $role === 'cobrador' ) {
    // Los créditos del cobrador se filtran por cliente_ref → cliente.cobrador = current user.
    // Eso es 2 niveles. Lo manejamos en queries explícitas (lga_crm_get_creditos_for_user)
    // en lugar de via meta_query nativa.
}
```
**Riesgo**: el cobrador tiene `read_credito` + `edit_credito`. Si navega a `/wp-admin/edit.php?post_type=credito` ve **todos** los créditos del sistema. El frontend filtra OK, pero wp-admin no.
**Fix**: o agregar pre-query que resuelva el join (traer cliente_ids del cobrador y filtrar credito por `meta_query cliente_ref IN cliente_ids`), o quitar capabilities `edit_creditos` del cobrador (que solo edite vía frontend dedicado).

### 3. `$_SERVER['REQUEST_URI']` sin sanitizar en redirect
**Archivo**: `inc/routing.php:51`
```php
wp_safe_redirect( wp_login_url( home_url( $_SERVER['REQUEST_URI'] ?? '/panel' ) ) );
```
**Riesgo**: bajo. WP `wp_safe_redirect` valida el host, pero conviene pasar `esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) )` antes para evitar issues con caracteres especiales.

### 4. Loop potencial para usuarios sin rol válido
**Archivo**: `inc/routing.php:91-94`
```php
$target = '/panel/admin';
if ( $role === 'vendedor' ) $target = '/panel/vendedor';
elseif ( $role === 'cobrador' ) $target = '/panel/cobrador';
wp_safe_redirect( home_url( $target ) );
```
**Problema**: si role es `subscriber` o vacío, `$target` queda en `/panel/admin`. Pero panel-admin requiere `administrator`, rebota → loop infinito.
**Fix**: si role no está en lista válida, mandar `wp_die` 403 o forzar logout.

## 🟠 Funcionales / Lógica

### 5. `promote_lead_to_client_credit` no idempotente
**Archivo**: `inc/handlers.php:241-329`
**Problema**: si se llama 2 veces sobre el mismo lead (doble-click "Promover", re-aprobar lead ya aprobado, etc.), **crea 2 créditos**. Reutiliza el cliente por DNI pero el chequeo de "lead ya promovido" no existe.
**Fix**: al inicio de la función chequear `get_field('credito_ref', $lead_id)`. Si existe y el credito sigue vivo, retornar early con los IDs existentes.

### 6. Auto-promover falla silenciosamente y deja lead en limbo
**Archivo**: `inc/handlers.php:177-192`
**Problema**: `update_field('lead_status', 'aprobado', $lead_id)` se ejecuta en línea 166, ANTES de intentar promote. Si promote falla (ej. lead sin DNI), el lead queda con status `aprobado` pero sin `cliente_ref` ni `credito_ref`. Se muestra como aprobado pero está roto.
**Fix**: transaccionar — si promote retorna WP_Error, revertir `lead_status` al estado previo (capturar antes de updatear) y mostrar error visible. O cambiar el orden: primero `promote`, luego `update_field` solo si OK.

### 7. Función declarada dentro del template
**Archivo**: `templates/panel-cobrador.php:79-91`
**Problema**: `lga_crm_panel_cobrador_origen_badge()` se define dentro del `else` del foreach. Si el template se incluye dos veces (ESI, parcial render, fragment) → fatal `Cannot redeclare function`.
**Fix**: mover a `inc/queries.php` o envolver en `if ( ! function_exists() )`.

### 8. Tailwind CDN se carga 2 veces
**Archivos**: `lga-crm.php:86-87` (enqueue) + `templates/_layout.php:41` (inline)
**Problema**: ambos cargan `https://cdn.tailwindcss.com`. El segundo gana porque está después en el DOM, pero el primer GET es un round-trip desperdiciado.
**Fix**: eliminar el `wp_enqueue_script` (queda solo el inline del layout) o viceversa.

### 9. Versión inconsistente
**Archivo**: `lga-crm.php`
- Header (línea 6): `Version: 0.1.0`
- Constante (línea 17): `LGA_CRM_VERSION = '0.3.7'`
**Fix**: bump del header a `0.3.7` para que coincidan.

## 🟡 UX / Semántica

### 10. KPI "Leads totales" miente
**Archivo**: `templates/panel-admin.php:17,47-48`
**Problema**: el card dice "Leads totales" + "en evaluación" pero `lga_crm_get_leads_for_user()` retorna SOLO activos (`nuevo` + `en_visita`). El número no es el total real.
**Fix**: cambiar label a "Leads activos" o cambiar el query a `lga_crm_count('lead')`.

### 11. Forms pierden datos al fallar validación
**Archivos**: `inc/handlers.php:35-37` (cliente), `inc/handlers.php:106-108` (crédito)
**Problema**: si faltan campos requeridos, `wp_safe_redirect( add_query_arg('err','missing_required', ...) )` devuelve al form **vacío**. El usuario tiene que retipear todo.
**Fix**: usar transient temporal con los datos del POST y rehidratar en el render del form. O passar datos como query string base64.

### 12. `add_action()` antes del check `defined('ABSPATH')`
**Archivo**: `inc/queries.php:7-14`
**Problema**: el `add_action('save_post_lead', ...)` está en línea 7, antes del `if (!defined('ABSPATH')) exit;` en línea 24. No rompe en práctica (WP siempre define ABSPATH primero), pero es contrario a convención y olor de código.
**Fix**: mover el `add_action` después del check.

## ⚠️ Operativo (no es bug pero atención)

### 13. Plan Hostinger caduca 2026-05-24
**Archivo**: `wp/credentials.local.md:22`
**Plazo**: 6 días desde la auditoría. Sin renovación, dashboard se cae.

---

## Cómo retomar este audit

Cuando vuelvas a estos, hacelo en este orden:
1. **1, 2, 4** — seguridad pura, deberían ir primero.
2. **5, 6** — lógica de promote (afecta datos en prod).
3. **7, 8, 9** — limpieza/performance, rápidos.
4. **10, 11, 12** — UX/orden.
5. **13** — renovar Hostinger antes de la fecha.

Cada bug está aislado, podés hacer un commit por fix.
