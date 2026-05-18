# LGA · Panel Administrativo (WordPress)

Dashboard interno para gestionar solicitudes de crédito. Vive en **`admin.lga-arg.com`**, separado del VPS donde corre n8n (Hostinger Web Hosting Business plan).

## Arquitectura

```
Form Next.js (creditos.domuhogar.com — Vercel)
   ↓ POST /api/submit-application
   ├── Insert credit_applications + clients + events en Supabase (sync)
   └── POST notify webhook → n8n VPS (fire-and-forget)
n8n VPS (n8n.lga-arg.com)
   Workflow yDWVVlQxopOrclT6 · "LGA - Notificar nueva solicitud (Telegram)"
   ├── Webhook nueva solicitud
   ├── Branch A: Formatear mensaje → Enviar Telegram → Respond OK
   └── Branch B: Mapear ACF → Crear post WP
WordPress (admin.lga-arg.com)
   └── CPT "solicitud" con 47 campos ACF
       Editable manualmente desde wp-admin
```

WordPress es la **vista editable** del equipo. Supabase queda como source-of-truth técnico; el equipo no técnico solo toca WP.

**Por qué n8n VPS hace el push (no Vercel directo):**
- Las env vars `WP_REST_URL` / `WP_REST_AUTH` en Vercel no se inyectaban al runtime de la Function por un bug raro (probablemente cache de dominio custom). Las funciones SÍ veían DATABASE_URL pero no las nuevas.
- Centralizando el push en n8n: cero env vars nuevas en Vercel, una sola edición en n8n para cambiar mapping/auth WP, el form Next.js no se entera de la existencia de WordPress.

## Stack instalado

| Componente | Versión | Rol |
|---|---|---|
| WordPress | 6.9 | CMS base |
| ACF (Advanced Custom Fields) Free | 6.8.1 | Custom fields del CPT |
| Custom Post Type UI | 1.19.2 | Registró el CPT "solicitud" |

## CPT "solicitud"

- **Slug**: `solicitud` (singular) · base REST `solicitudes` (plural)
- **Configuración**: `public: false`, `show_ui: true`, `show_in_rest: true`, `publicly_queryable: false`
- **Endpoint REST**: `https://admin.lga-arg.com/wp-json/wp/v2/solicitudes`
- **Icono sidebar**: `dashicons-id-alt`
- **Soportes**: title (acá va el `application_code` tipo `LGA-260517-0008`), editor, autor, revisiones, campos personalizados

## Field Group ACF (47 campos en 8 tabs)

Definición: [`acf-fields-solicitud.json`](./acf-fields-solicitud.json) — re-importable desde **ACF → Herramientas → Importar JSON**.

| Tab | Campos |
|---|---|
| Cliente | first_name, last_name, dni, birth_date, phone, email |
| Domicilio | address_line, locality, province, postal_code, housing_status |
| Ocupación / Ingreso | occupation, occupation_detail, declared_income_ars |
| Garante | guarantor_name, guarantor_phone, guarantor_relation |
| Crédito solicitado | requested_amount_ars, payment_frequency, requested_installments, estimated_installment_ars |
| Estado | application_status, zone_status, internal_notes |
| Origen Shopify | shop, source, product_id, variant_id, product_title, product_handle, unit_price_ars, quantity, cart_token, cart_total_ars, cart_summary |
| Marketing | utm_source, utm_medium, utm_campaign, utm_content, utm_term, referrer_url, landing_url |
| IDs / Sync | application_code, application_id, client_id, supabase_synced_at |

Cada field tiene `show_in_rest: 1`, expuesto en `GET /wp-json/wp/v2/solicitudes/{id}` bajo la clave `acf`.

## Integración n8n → WP REST API

### Endpoint
```
POST https://admin.lga-arg.com/wp-json/wp/v2/solicitudes
```

### Auth (Basic con Application Password)
```
Authorization: Basic base64(gerolopezge@gmail.com:<application_password>)
```

El header Authorization se setea **directamente en el nodo HTTP Request** de n8n (no usa credential).
La auth completa vive en el nodo "Crear post WP" del workflow `yDWVVlQxopOrclT6`.

### Nodos del workflow

| Nodo | Tipo | Rol |
|---|---|---|
| Webhook nueva solicitud | webhook | Recibe POST del form |
| Formatear mensaje | code | Arma el texto MarkdownV2 para Telegram |
| Enviar Telegram | httpRequest | POST a Telegram Bot API (supergroup `-1003766295782`) |
| Respond OK | respondToWebhook | 200 al form Next.js |
| Mapear ACF | code | Transforma el payload del webhook a `{ title, status, acf: {...47 campos} }` |
| Crear post WP | httpRequest | POST al endpoint REST del CPT `solicitud` |

### Payload de ejemplo
```json
{
  "title": "LGA-260517-0008",
  "status": "publish",
  "acf": {
    "first_name": "Test",
    "last_name": "E2E Claude",
    "dni": "99999999",
    "phone": "+5493815550000",
    "email": "test@example.com",
    "address_line": "Av. Test 123",
    "locality": "San Miguel de Tucumán",
    "province": "Tucumán",
    "postal_code": "T4000",
    "housing_status": "rented",
    "occupation": "employed_registered",
    "declared_income_ars": 500000,
    "requested_amount_ars": 18135,
    "payment_frequency": "monthly",
    "requested_installments": 12,
    "application_status": "submitted",
    "zone_status": "in_zone",
    "shop": "mem1a9-ev.myshopify.com",
    "source": "cart_single",
    "product_title": "Lentes Bolduke ZLE3601",
    "cart_total_ars": 18135,
    "application_code": "LGA-260517-0008",
    "application_id": "0ebf9b6e-9bd1-4603-bc31-e2c45e700df9"
  }
}
```

### Response (201)
```json
{
  "id": 67,
  "title": { "rendered": "LGA-260517-0008" },
  "status": "publish",
  "link": "https://admin.lga-arg.com/solicitud/lga-260517-0008/",
  "acf": { /* todos los campos persistidos */ }
}
```

### Validación end-to-end
Probado el 2026-05-17:
- Submit del form Next.js productivo (LGA-260517-0013) → fila Supabase ✓ + post WP creado automático ✓ + Telegram al supergroup ✓
- Workflow exec 30 status: success
- Latencia agregada: ~1.5s (n8n VPS responde rápido)

## Operación manual

El equipo entra a https://admin.lga-arg.com/wp-admin → **Solicitudes** en sidebar → puede:
- Ver lista de todas
- Click una solicitud → ver todos los datos en 8 tabs
- Cambiar `application_status` (en review → approved / rejected)
- Agregar `internal_notes`
- Asignar autor (vendedor responsable)
- Ver historial de cambios (revisiones)

## CRM extendido (mu-plugin `lga-crm`, versión 0.1.1)

Encima del CPT `solicitud` (que sigue intacto), se desplegó un mu-plugin que agrega:
- **3 CPTs nuevos**: `cliente`, `credito`, `lead` con field groups ACF programáticos.
- **2 roles nuevos**: `vendedor` y `cobrador` con capabilities filtradas.
- **Frontend custom** en `/panel/...` (no wp-admin) con Tailwind CDN.
- **Adquisición manual** del admin: alta de cliente en 2 pasos (cliente primero, crédito después).
- **Aprobación de solicitudes web**: convertir CPT `solicitud` → `lead` → `cliente` + `credito`.

### Estructura del mu-plugin

```
wp-content/mu-plugins/
├─ lga-crm-loader.php            # carga lga-crm/lga-crm.php
└─ lga-crm/
   ├─ lga-crm.php                # bootstrap + hooks
   ├─ inc/
   │  ├─ cpts.php                # register_post_type x3 (cliente/credito/lead)
   │  ├─ roles.php               # vendedor + cobrador + caps custom
   │  ├─ acf.php                 # acf_add_local_field_group x3
   │  ├─ routing.php             # rewrites + template_redirect router (chequeo por rol)
   │  ├─ login.php               # redirect post-login por rol + styling wp-login
   │  ├─ queries.php             # pre_get_posts filter + helpers UI
   │  └─ handlers.php            # admin-post.php POST handlers
   ├─ templates/
   │  ├─ _layout.php             # header/nav/footer común
   │  ├─ panel-router.php        # /panel → redirige al panel del rol
   │  ├─ panel-admin.php         # /panel/admin (4 tabs)
   │  ├─ panel-vendedor.php      # /panel/vendedor (sus leads)
   │  ├─ panel-cobrador.php      # /panel/cobrador (sus clientes/créditos)
   │  ├─ lead-detail.php
   │  ├─ cliente-detail.php
   │  ├─ credito-detail.php
   │  ├─ cliente-nuevo.php       # form admin alta cliente
   │  ├─ credito-asignar.php     # form admin alta crédito (con cliente_ref)
   │  ├─ aprobar-solicitud.php   # convertir solicitud → lead
   │  └─ promover-lead.php       # convertir lead → cliente + crédito
   └─ dev/
      ├─ seed-dummy.php          # crea 4 users + 7 cli + 9 cred + 8 lead
      └─ cleanup-installer.php   # borra installer público + se auto-elimina
```

### Roles y capabilities

| Rol | URL destino post-login | Puede ver | Puede editar |
|---|---|---|---|
| `administrator` | `/panel/admin` | TODO | TODO (aprobar, promover, asignar, alta manual) |
| `vendedor` | `/panel/vendedor` | Solo sus leads (`responsable = current_user`) | `lead_status`, notas |
| `cobrador` | `/panel/cobrador` | Solo sus clientes (`cobrador = current_user`) y sus créditos | Notas + pagos (futuro F4) |

Las queries se filtran server-side con `pre_get_posts` por user_id en `responsable` / `cobrador`. El routing chequea por **rol** (no por cap) para evitar interferencia con `map_meta_cap=true` en CPTs custom.

### URLs del panel

| Ruta | Rol | Función |
|---|---|---|
| `/panel` | cualquiera | Redirige al panel del rol |
| `/panel/admin` | admin | Inbox completo (Solicitudes / Leads / Clientes / Créditos) |
| `/panel/vendedor` | admin + vendedor | Sus leads agrupados por estado |
| `/panel/cobrador` | admin + cobrador | Sus clientes + créditos con saldo |
| `/lead/<id>` | admin + vendedor asignado | Ficha lead editable |
| `/cliente/<id>` | admin + cobrador asignado | Ficha cliente con créditos asociados |
| `/credito/<id>` | admin + cobrador del cliente | Ficha crédito |
| `/admin/nuevo-cliente` | admin | Form alta manual cliente |
| `/admin/cliente/<id>/asignar-credito` | admin | Form alta crédito (cliente pre-poblado) |
| `/admin/aprobar-solicitud/<id>` | admin | Convertir `solicitud` web → `lead` |
| `/admin/promover-lead/<id>` | admin | Convertir `lead` aprobado → `cliente` + `credito` |

### Despliegue

El plugin se distribuye como `wp/lga-crm-bundle.zip` (loader + plugin) descargado por `wp/lga-crm-installer.php` desde GitHub raw → extraído a `/wp-content/mu-plugins/`. Una vez desplegado:

1. Visitar `/wp-content/mu-plugins/lga-crm/dev/seed-dummy.php` como admin → crea users + datos dummy.
2. Visitar `/wp-content/mu-plugins/lga-crm/dev/cleanup-installer.php` como admin → borra el installer público + se auto-elimina.

Ambos scripts son idempotentes y requieren `manage_options` cap.

### Users dummy creados por el seed

| Username | Password | Rol | Datos asignados |
|---|---|---|---|
| `vendedor-1` | `Vendedor1!2026` | vendedor | 3 leads |
| `vendedor-2` | `Vendedor2!2026` | vendedor | 3 leads |
| `cobrador-1` | `Cobrador1!2026` | cobrador | 4 clientes + 5 créditos |
| `cobrador-2` | `Cobrador2!2026` | cobrador | 3 clientes + 4 créditos |

Cada user dummy tiene meta `_lga_dummy=1` para borrado en bulk futuro.

## Credenciales

Ver `wp/credentials.local.md` (NO commitear, ya está en .gitignore).

## Limpieza / Bloatware

Hostinger preinstala 4 plugins propios:
- Hostinger AI
- Hostinger Easy Onboarding
- Hostinger Reach
- Hostinger Tools

No rompen nada, pero agregan menús al admin. Se pueden desactivar desde Plugins → Plugins instalados sin afectar la funcionalidad del dashboard.

## Renovación

⚠️ Plan Business Hostinger caduca **2026-05-24**. Renovar para evitar caída del subdominio.
