# Migración del form: Vercel → Shopify Liquid + n8n

> 2026-05-18 · Objetivo: sacar Vercel del path crítico. El form vive en Shopify (`domuhogar.com/pages/aplicar-credito`) y postea directo al webhook n8n que orquesta Supabase + Shopify draft + Telegram + WP post.

## Antes / Después

```
ANTES (Vercel en el path):
Form Next.js (creditos.domuhogar.com)
  → /api/submit-application (Vercel Edge)
     ├── insert Supabase
     ├── createDraftOrder Shopify
     └── notify webhook n8n
        → Telegram + crear WP post

DESPUÉS (Vercel fuera del path crítico):
Form Liquid (domuhogar.com/pages/aplicar-credito)
  → webhook /lga-shopify-form (n8n VPS)
     ├── PG: upsert client + check_zone + insert credit_application
     ├── Respond 200 + application_code al instante
     ├── (en background) Telegram
     ├── (en background) Shopify draftOrderCreate
     └── (en background) crear WP post con shopify_* meta incluido
```

El form Vercel sigue desplegado en `creditos.domuhogar.com` como fallback unos días. Apagar cuando se confirme que el nuevo flujo está estable.

## Archivos del paquete

| Archivo | Qué es | Dónde va |
|---|---|---|
| [`n8n/lga-shopify-form-all-in-one.json`](../n8n/lga-shopify-form-all-in-one.json) | Workflow n8n completo (12 nodos) | Importar en n8n VPS via UI |
| [`shopify/page.aplicar-credito.liquid`](../shopify/page.aplicar-credito.liquid) | Template Liquid de la página del form | Online Store → Themes → Edit code |
| [`shopify/snippet-pdp.liquid`](../shopify/snippet-pdp.liquid) | Botón "Pagá en cuotas con crédito LGA" en PDP — **actualizar URL** | Ya está pegado, solo cambiar el href del botón |
| [`shopify/snippet-cart.liquid`](../shopify/snippet-cart.liquid) | Botón en Cart — **actualizar URL** | Igual |

## Pasos de instalación (orden obligatorio)

### 1. Env vars en n8n VPS

SSH al VPS Hostinger 1483780 (o vía UI n8n → Settings → Environment) y agregar al archivo `~/.n8n/.env` (o equivalente del Docker compose):

```bash
# Shopify Admin API (client_credentials grant)
LGA_SHOPIFY_SHOP=mem1a9-ev.myshopify.com
LGA_SHOPIFY_CLIENT_ID=16422fb3e33239bf87b971793b5c405d
LGA_SHOPIFY_CLIENT_SECRET=shpss_xxxxxxxxxxxxx     # ← copiar del wp-config.php de admin.lga-arg.com
LGA_SHOPIFY_API_VERSION=2025-01

# Telegram bot (ya está hardcoded en el workflow viejo, lo mejoramos a env var)
LGA_TELEGRAM_BOT_TOKEN=8778587067:AAGf96IP-7zXjlhu0hZrxKNxBNOIBPCjukM

# WP REST API (Application Password "n8n LGA Push")
# Generar con: echo -n "gerolopezge@gmail.com:4YIx JTe9 AyWX 7jQN r06I LOR2" | base64
LGA_WP_BASIC_AUTH=Z2Vyb2xvcGV6Z2VAZ21haWwuY29tOjRZSXggSlRlOSBBeVdYIDdqUU4gcjA2SSBMT1Iy
```

Reiniciar n8n (`docker restart n8n` o lo que aplique en tu setup) para que tome las env vars.

### 2. Importar el workflow

1. UI de n8n: `https://n8n.lga-arg.com/`
2. Workflows → Import from file → seleccionar [`n8n/lga-shopify-form-all-in-one.json`](../n8n/lga-shopify-form-all-in-one.json)
3. **Conectar credenciales** en los 3 nodos que las usan:
   - Los 3 nodos `PG: Upsert client`, `PG: Check zone`, `PG: Insert credit_application` ya apuntan al credential ID `857MwnILNdTuy8gr` ("Supabase LGA — Postgres"). Si el ID en tu VPS es distinto, hay que reconectarlos manualmente.
4. **Activar el workflow** (toggle "Active" arriba a la derecha).
5. El webhook URL queda: `https://n8n.lga-arg.com/webhook/lga-shopify-form`

### 3. Smoke test del webhook (antes de tocar Shopify)

Desde tu terminal:

```powershell
$payload = @{
  first_name = "Test"
  last_name = "Webhook"
  dni = "99999991"
  birth_date = "1990-01-01"
  phone = "+5493815551234"
  email = "test@webhook.com"
  address_line = "Av. Test 100"
  locality = "San Miguel de Tucumán"
  province = "Tucumán"
  postal_code = "T4000"
  housing_status = "rented"
  occupation = "employed_registered"
  occupation_detail = "Test"
  declared_income_ars = 500000
  requested_amount_ars = 100000
  requested_installments = 6
  payment_frequency = "monthly"
  marketing_consent = $true
  idempotency_key = [Guid]::NewGuid().ToString()
  shop = "domu"
  source = "shopify_pdp_test"
  product_id = "123"
  variant_id = "456"
  product_title = "Producto Test"
  unit_price_ars = 100000
  quantity = 1
  cart_total_ars = 100000
  utm_source = "qa"
  utm_campaign = "smoke_test"
} | ConvertTo-Json -Compress

Invoke-RestMethod -Method Post -Uri "https://n8n.lga-arg.com/webhook/lga-shopify-form" `
  -ContentType "application/json" -Body $payload
```

**Respuesta esperada** (~300ms):
```json
{
  "ok": true,
  "application_code": "LGA-260518-NNNN",
  "application_id": "uuid",
  "zone_status": "in_zone",
  "status": "submitted",
  "received_at": "2026-05-18T..."
}
```

Verificá en **paralelo** (todo debería estar en ≤3s):
- **Supabase SQL Editor**: `select * from credit_applications order by created_at desc limit 5;` → la nueva fila.
- **Shopify admin**: Draft orders → debería estar el draft con tag `lga-app-LGA-260518-NNNN`.
- **Telegram supergroup**: notificación con el resumen.
- **WP admin**: `https://admin.lga-arg.com/wp-admin/edit.php?post_type=solicitud` → la solicitud nueva con todos los meta (incluido `shopify_draft_order_id`).

Si **alguno falla**: abrí la ejecución en n8n (`Executions` tab del workflow) y mirá el error en el nodo correspondiente.

### 4. Subir el plugin WP actualizado (v0.3.8)

Antes de cambiar el form en Shopify, asegurate de que el plugin WP esté en versión `0.3.8` para que **NO** intente crear un draft duplicado:

Subí estos 2 archivos via hPanel → File Manager a `/public_html/wp-content/mu-plugins/lga-crm/`:
- `wp/lga-crm/inc/shopify.php` (hooks auto-create deshabilitados)
- `wp/lga-crm/lga-crm.php` (version 0.3.8)

Visitá `https://admin.lga-arg.com/wp-content/mu-plugins/lga-crm/dev/clear-opcache.php` (logueado como admin) para purgar OPcache.

### 5. Crear el template Liquid en Shopify

1. Shopify admin → Online Store → Themes → ... → Edit code
2. **Backup primero**: si no creaste el theme duplicado pre-LGA, hacelo ahora.
3. Templates → Add a new template → Page → name: `aplicar-credito` (alternativo).
4. Pegar contenido de [`shopify/page.aplicar-credito.liquid`](../shopify/page.aplicar-credito.liquid).
5. Save.

### 6. Crear la página visible

1. Shopify admin → Online Store → Pages → Add page
2. Title: `Aplicar crédito`
3. Theme template: `aplicar-credito` (el que acabás de crear)
4. Online store visibility: visible
5. Handle: `aplicar-credito` (automático del title)
6. Save.

URL final: `https://domuhogar.com/pages/aplicar-credito`.

### 7. Smoke test del form

Abrí en incognito:
```
https://domuhogar.com/pages/aplicar-credito?variant_id=44222111000&product_id=8123&title=Producto+Test&price=100000&qty=1&cart_total=100000&utm_source=qa&utm_campaign=smoke
```

- El resumen del pedido debería mostrar el título, qty y total ($100.000).
- El campo "Monto (ARS)" debería estar prellenado con 100000.
- Completar el resto con DNI ficticio `99999992`.
- Submit → ver pantalla de éxito con código `LGA-260518-NNNN`.

### 8. Actualizar los botones del PDP/Cart

Los snippets `shopify/snippet-pdp.liquid` y `shopify/snippet-cart.liquid` actualmente apuntan al form Vercel (`creditos.domuhogar.com/aplicar`). Hay que cambiar la URL a la nueva página:

```liquid
{%- comment -%} Cambiar este link en ambos snippets {%- endcomment -%}
<a href="https://creditos.domuhogar.com/aplicar?{{ params }}">...</a>
```

por:

```liquid
<a href="https://domuhogar.com/pages/aplicar-credito?{{ params }}">...</a>
```

Editar via Online Store → Themes → Customize → buscar los blocks "Custom Liquid" donde están pegados y editar el href.

### 9. (Opcional) Apagar el endpoint Vercel

Después de un par de días con el form nuevo estable, apagar el endpoint Vercel:

- Opción A (suave): dejar el dominio `creditos.domuhogar.com` apuntando a Vercel pero borrar las env vars de Shopify y WP REST para que NO genere drafts duplicados ni cree posts WP. El form Vercel sigue funcionando pero queda inerte.
- Opción B (clean cut): eliminar el endpoint `/api/submit-application` del repo, redeploy, y opcionalmente eliminar el proyecto Vercel completo. Esto cancela cualquier costo Vercel.

## Trade-offs y limitaciones

### ✅ Wins
- **0 dependencia de Vercel** (deja de cobrar functions al cancelar). El form HTML estático puede vivir en cualquier lugar (en este caso Shopify, gratis).
- **Latencia para el usuario**: ~300ms (antes ~4s con `await Promise.race`).
- **1 orquestador**: n8n hace todo, fácil de monitorear via executions UI.
- **Stock reservado al instante**: el draft Shopify se crea apenas el cliente submitea.

### ⚠️ Trade-offs
- **n8n single point of failure**: si el VPS se cae, el form también. Antes había 2 paths (Vercel + n8n).
- **Sin upload de docs en el form inicial**: el form Liquid no acepta archivos. Los docs (DNI, selfie, comprobante de ingreso) se piden por WhatsApp después del primer contacto.
- **n8n executionOrder v1**: los branches "paralelos" en realidad corren secuenciales (~3s total en background). Esto NO afecta al cliente (el respond a 200 sale apenas Postgres termina). Si en el futuro se necesita verdadero paralelismo, dividir en N webhooks separados.
- **CORS abierto**: el webhook acepta `Access-Control-Allow-Origin: *`. Si querés cerrarlo a `https://domuhogar.com`, editar el webhook node en n8n.

### 🐞 Edge cases conocidos
- **Idempotency key duplicada**: si por algún motivo el cliente hace submit 2 veces con la misma `idempotency_key` (UUID generado en el navegador), Postgres detecta el `ON CONFLICT` y retorna el `application_code` existente. ✓ OK.
- **Shopify token expira**: el workflow canjea el token en cada submit (no cachea). Es ~150ms extra. Aceptable para el volumen actual. Si crece, mover el token a un transient/cache de n8n.
- **WP REST falla pero Shopify draft creado**: el draft queda en Shopify sin solicitud asociada en WP. Buscar por tag `lga-app-LGA-...` para vincular manualmente.
- **Solicitud no llega a WP**: el admin puede generar la solicitud manualmente desde el panel admin (`/admin/nuevo-cliente`).

## Rollback

Si el nuevo flujo falla y querés volver al anterior:

1. En Shopify: cambiar los `href` de los snippets PDP/Cart de vuelta a `https://creditos.domuhogar.com/aplicar`.
2. Despublicar la página `aplicar-credito` desde Shopify Admin.
3. Re-habilitar los hooks en `wp/lga-crm/inc/shopify.php` (descomentar las líneas `add_action('rest_after_insert_solicitud', ...)`).
4. Bump version a `0.3.9` para forzar OPcache flush.
5. Subir a Hostinger.

El form Vercel sigue funcional mientras no toques nada de Vercel.

## Costos comparativos

| Servicio | Antes | Después |
|---|---|---|
| Vercel (Hobby + Functions) | $0-20/mes (Pro al consumir) | $0 (form estático en Shopify) |
| Hostinger Business shared (WP) | $0 (ya pago) | $0 (igual) |
| Hostinger VPS 1483780 (n8n) | $0 (ya pago) | $0 (igual) |
| Supabase Pro (sa-east-1) | $0-25/mes | $0-25/mes (sin cambio) |
| Shopify Domu | $0 (ya pago) | $0 (igual) |

**Ahorro estimado**: $0-20/mes (Vercel). Sin sumar costo nuevo.

## Próximos pasos sugeridos

1. Monitorear las primeras 10 solicitudes en `Executions` del workflow (revisar tiempos y errores).
2. Resolver los bugs del audit pendientes ([BUGS-AUDIT-2026-05-18.md](BUGS-AUDIT-2026-05-18.md)).
3. Considerar agregar el upload de docs DNI/selfie en una segunda página (`/pages/cargar-documentacion?code=LGA-...`) que el WhatsApp del equipo le mande al cliente después del primer contacto.
4. Si el volumen crece >100 solicitudes/día, evaluar mover Postgres queries del workflow a un endpoint dedicado para reducir latencia del Postgres connection setup.
