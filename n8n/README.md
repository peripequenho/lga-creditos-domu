# Workflow n8n — `LGA - Nueva solicitud crédito Domu`

Workflow que recibe el POST del formulario Next.js y persiste la solicitud en Supabase.

> No se incluye el JSON exportable porque las versiones de schema de n8n cambian con frecuencia. Construilo desde la UI siguiendo esta guía, después usá `mcp__n8n__n8n_validate_workflow` para validarlo.

---

## 1. Credenciales requeridas

| Credencial | Tipo | Cómo crearla |
|---|---|---|
| `Supabase LGA — Postgres` | Postgres | n8n → Credentials → New → Postgres. Host: `db.<project-ref>.supabase.co`, Port: `5432` (o `6543` con pooler), Database: `postgres`, User: `postgres`, Password: la DB password del proyecto Supabase (NO la `service_role` JWT). SSL: `require`. |
| `LGA Webhook Secret` (env var) | — | Agregar a `~/.n8n/.env` la línea `LGA_WEBHOOK_SECRET=<la_misma_string_que_vercel>`. Reiniciar n8n. |
| `Telegram — Admin LGA` | Telegram | (ya configurada en n8n del usuario, reusar). |

---

## 2. Workflow paso a paso (10 nodos + error handler)

### Nodo 1 — Webhook (trigger)

- **Type:** `n8n-nodes-base.webhook`
- **HTTP Method:** `POST`
- **Path:** `lga-new-credit-app`
- **Authentication:** `None` (HMAC en el nodo siguiente)
- **Response Mode:** `Using Respond to Webhook Node`
- **Options → Raw Body:** `true`

Producción quedará accesible en `https://n8n.lga-arg.com/webhook/lga-new-credit-app` vía Cloudflare Tunnel.
Test mode: `https://n8n.lga-arg.com/webhook-test/lga-new-credit-app` (solo cuando "Listen for test event" está activo).

---

### Nodo 2 — Code: Validate HMAC + timestamp

- **Type:** `n8n-nodes-base.code`
- **Mode:** `Run Once for All Items`
- **Language:** JavaScript

```javascript
const crypto = require('crypto');
const SECRET = $env.LGA_WEBHOOK_SECRET;
if (!SECRET) throw new Error('missing_env_LGA_WEBHOOK_SECRET');

const item    = $input.first().json;
const headers = item.headers || {};
const sigGot  = headers['x-lga-signature'];
const tsStr   = headers['x-lga-timestamp'];
const rawBody = item.body;
const bodyStr = typeof rawBody === 'string' ? rawBody : JSON.stringify(rawBody);

if (!sigGot || !tsStr) throw new Error('missing_signature_headers');

const ts = parseInt(tsStr, 10);
if (Number.isNaN(ts) || Math.abs(Date.now()/1000 - ts) > 300) {
  throw new Error('timestamp_out_of_window');
}

const expected = crypto.createHmac('sha256', SECRET).update(ts + '.' + bodyStr).digest('hex');
const a = Buffer.from(expected, 'hex');
const b = Buffer.from(sigGot, 'hex');
if (a.length !== b.length || !crypto.timingSafeEqual(a, b)) {
  throw new Error('invalid_signature');
}

return [{ json: JSON.parse(bodyStr), headers }];
```

---

### Nodo 3 — Code: Validate schema

```javascript
const p = $json;
const errs = [];

function req(field, cond, msg) { if (!cond) errs.push({ field, msg: msg || ('invalid_' + field) }); }
function isStr(v, max=255, min=1) { return typeof v === 'string' && v.length >= min && v.length <= max; }
function isNum(v, min=0, max=Infinity) { return typeof v === 'number' && v >= min && v <= max; }

req('idempotency_key', /^[0-9a-f-]{36}$/i.test(p.idempotency_key || ''));
req('first_name', isStr(p.first_name, 60, 2));
req('last_name',  isStr(p.last_name, 80, 2));
req('dni',        /^[0-9]{7,9}$/.test(p.dni || ''));
req('birth_date', /^\d{4}-\d{2}-\d{2}$/.test(p.birth_date || ''));
req('phone',      /^\+549\d{10}$/.test(p.phone || ''));
req('address_line', isStr(p.address_line, 140, 5));
req('locality',   isStr(p.locality, 80, 2));
req('province',   isStr(p.province, 60, 2));
req('postal_code',/^[A-Z]?\d{4}[A-Z]{0,3}$/i.test(p.postal_code || ''));
req('requested_amount_ars',   isNum(+p.requested_amount_ars,   50_000, 5_000_000));
req('requested_installments', [3,6,9,12,18,24].includes(+p.requested_installments));
req('cart_total_ars',         isNum(+p.cart_total_ars, 0));
req('terms_accepted',         p.terms_accepted === true);

if (errs.length) {
  const err = new Error('validation_failed');
  err.context = { issues: errs };
  throw err;
}
return [{ json: p }];
```

---

### Nodo 4 — Code: Normalize

```javascript
const p = { ...$json };

p.dni = String(p.dni).replace(/\D/g, '');
p.phone = String(p.phone).replace(/[\s\-()]/g, '');

function titleCase(s) {
  return String(s||'').trim().toLocaleLowerCase('es-AR')
    .replace(/\b\p{L}/gu, c => c.toLocaleUpperCase('es-AR'));
}
p.first_name = titleCase(p.first_name);
p.last_name  = titleCase(p.last_name);

if (p.email) p.email = String(p.email).trim().toLowerCase();
if (p.postal_code) p.postal_code = String(p.postal_code).toUpperCase();

p.requested_amount_ars   = Number(p.requested_amount_ars);
p.requested_installments = Number(p.requested_installments);
p.cart_total_ars         = Number(p.cart_total_ars);
if (p.unit_price_ars      !== undefined && p.unit_price_ars      !== null) p.unit_price_ars      = Number(p.unit_price_ars);
if (p.quantity            !== undefined && p.quantity            !== null) p.quantity            = Number(p.quantity);
if (p.declared_income_ars !== undefined && p.declared_income_ars !== null) p.declared_income_ars = Number(p.declared_income_ars);

return [{ json: p }];
```

---

### Nodo 5 — Postgres: Upsert client

- **Type:** `n8n-nodes-base.postgres`
- **Credential:** `Supabase LGA — Postgres`
- **Operation:** `Execute Query`
- **Retry on Fail:** ON, 3 attempts, 5s backoff

**Query:**

```sql
insert into clients (
  dni, first_name, last_name, email, phone_e164, birth_date,
  address_line, locality, province, postal_code,
  source, marketing_consent
) values (
  $1, $2, $3, nullif($4,''), $5, $6::date,
  $7, $8, $9, $10,
  $11, $12::boolean
)
on conflict (dni) do update set
  first_name        = excluded.first_name,
  last_name         = excluded.last_name,
  email             = coalesce(excluded.email, clients.email),
  phone_e164        = coalesce(excluded.phone_e164, clients.phone_e164),
  birth_date        = coalesce(excluded.birth_date, clients.birth_date),
  address_line      = coalesce(excluded.address_line, clients.address_line),
  locality          = coalesce(excluded.locality, clients.locality),
  province          = coalesce(excluded.province, clients.province),
  postal_code       = coalesce(excluded.postal_code, clients.postal_code),
  source            = coalesce(clients.source, excluded.source),
  marketing_consent = clients.marketing_consent or excluded.marketing_consent,
  updated_at        = now()
returning id;
```

**Parameters (orden importa):**

| # | Expresión |
|---|---|
| $1 | `{{ $json.dni }}` |
| $2 | `{{ $json.first_name }}` |
| $3 | `{{ $json.last_name }}` |
| $4 | `{{ $json.email || '' }}` |
| $5 | `{{ $json.phone }}` |
| $6 | `{{ $json.birth_date }}` |
| $7 | `{{ $json.address_line }}` |
| $8 | `{{ $json.locality }}` |
| $9 | `{{ $json.province }}` |
| $10 | `{{ $json.postal_code }}` |
| $11 | `{{ 'domu_' + ($json.source || 'unknown') }}` |
| $12 | `{{ $json.marketing_consent ? 'true' : 'false' }}` |

---

### Nodo 6 — Postgres: check_zone

- **Operation:** `Execute Query`

```sql
select status, zone_id from check_zone($1, $2, $3);
```

| # | Expresión |
|---|---|
| $1 | `{{ $('Normalize').item.json.postal_code }}` |
| $2 | `{{ $('Normalize').item.json.locality }}` |
| $3 | `{{ $('Normalize').item.json.province }}` |

---

### Nodo 7 — Set: Merge contexto

- **Type:** `n8n-nodes-base.set`
- **Mode:** `Keep Only Set: false`
- Define manualmente:
  - `client_id` = `{{ $('Upsert client').item.json.id }}`
  - `zone_status` = `{{ $('check_zone').item.json.status }}`
  - `zone_id` = `{{ $('check_zone').item.json.zone_id }}`

El resto de campos viene heredado de `$('Normalize').item.json`. Si en el set node no podés pasar todos los campos automáticamente, usá un **Code** node con:

```javascript
const n = $('Normalize').item.json;
const c = $('Upsert client').item.json;
const z = $('check_zone').item.json;
return [{ json: { ...n, client_id: c.id, zone_status: z.status, zone_id: z.zone_id } }];
```

---

### Nodo 8 — Postgres: Insert credit_application

- **Operation:** `Execute Query`
- **Retry on Fail:** ON, 3 attempts, 5s backoff

```sql
insert into credit_applications (
  client_id, status, zone_status, zone_id,
  shop, source, product_id, variant_id, product_title, product_handle, product_url,
  unit_price_ars, quantity, cart_token, cart_total_ars, cart_summary,
  requested_amount_ars, requested_installments, declared_income_ars,
  utm_source, utm_medium, utm_campaign, utm_content, utm_term,
  referrer_url, landing_url, ip, user_agent,
  idempotency_key, raw_payload
) values (
  $1, 'submitted', $2::zone_status, $3,
  $4, $5, $6, $7, $8, $9, $10,
  $11::numeric, $12::int, $13, $14::numeric, $15,
  $16::numeric, $17::int, $18::numeric,
  $19, $20, $21, $22, $23,
  $24, $25, $26, $27,
  $28, $29::jsonb
)
on conflict (idempotency_key) do update set
  raw_payload = credit_applications.raw_payload
returning id, application_code, status, zone_status, created_at;
```

**Parameters:**

| # | Expresión |
|---|---|
| $1 | `{{ $json.client_id }}` |
| $2 | `{{ $json.zone_status }}` |
| $3 | `{{ $json.zone_id }}` |
| $4 | `{{ $json.shop }}` |
| $5 | `{{ $json.source }}` |
| $6 | `{{ $json.product_id }}` |
| $7 | `{{ $json.variant_id }}` |
| $8 | `{{ $json.product_title }}` |
| $9 | `{{ $json.product_handle }}` |
| $10 | `{{ $json.referrer_url }}` |
| $11 | `{{ $json.unit_price_ars }}` |
| $12 | `{{ $json.quantity }}` |
| $13 | `{{ $json.cart_token }}` |
| $14 | `{{ $json.cart_total_ars }}` |
| $15 | `{{ $json.cart_summary }}` |
| $16 | `{{ $json.requested_amount_ars }}` |
| $17 | `{{ $json.requested_installments }}` |
| $18 | `{{ $json.declared_income_ars }}` |
| $19 | `{{ $json.utm_source }}` |
| $20 | `{{ $json.utm_medium }}` |
| $21 | `{{ $json.utm_campaign }}` |
| $22 | `{{ $json.utm_content }}` |
| $23 | `{{ $json.utm_term }}` |
| $24 | `{{ $json.referrer_url }}` |
| $25 | `{{ $json.landing_url }}` |
| $26 | `{{ $('Webhook').item.json.headers['x-lga-ip'] }}` |
| $27 | `{{ $('Webhook').item.json.headers['x-lga-ua'] }}` |
| $28 | `{{ $json.idempotency_key }}` |
| $29 | `{{ JSON.stringify($json) }}` |

---

### Nodo 9 — Postgres: Insert application_event

- **Operation:** `Execute Query`
- **Retry on Fail:** OFF (no es bloqueante)

```sql
insert into application_events (
  application_id, actor, actor_label, event_type, to_status, detail
) values (
  $1, 'external', 'domu_form', 'submitted', 'submitted',
  jsonb_build_object(
    'ip', $2::text,
    'user_agent', $3::text,
    'shop', $4::text,
    'utm_source', $5::text,
    'utm_campaign', $6::text,
    'zone_status', $7::text
  )
);
```

| # | Expresión |
|---|---|
| $1 | `{{ $('Insert credit_application').item.json.id }}` |
| $2 | `{{ $('Webhook').item.json.headers['x-lga-ip'] }}` |
| $3 | `{{ $('Webhook').item.json.headers['x-lga-ua'] }}` |
| $4 | `{{ $('Normalize').item.json.shop }}` |
| $5 | `{{ $('Normalize').item.json.utm_source }}` |
| $6 | `{{ $('Normalize').item.json.utm_campaign }}` |
| $7 | `{{ $('Merge contexto').item.json.zone_status }}` |

---

### Nodo 10 — Respond to Webhook (success)

- **Type:** `n8n-nodes-base.respondToWebhook`
- **Response Code:** `200`
- **Response Body:** `JSON`

```json
{
  "ok": true,
  "application_code": "={{ $('Insert credit_application').item.json.application_code }}",
  "application_id":   "={{ $('Insert credit_application').item.json.id }}",
  "status":           "submitted",
  "zone_status":      "={{ $('Merge contexto').item.json.zone_status }}",
  "next_step":        "Te contactamos por WhatsApp en las próximas 24 hs hábiles.",
  "received_at":      "={{ $now.toISO() }}"
}
```

---

## 3. Error handler (workflow separado)

Crear workflow **`LGA - Error handler`**:

1. **Error Trigger** node.
2. **Code:** extraer `error.message`, `error.context.issues`, `executionId`, `workflowName`.
3. **Telegram** → "❌ LGA workflow error: `{{workflowName}}` exec `{{executionId}}` — `{{message}}`".
4. **Respond to Webhook** con código `400` si `error.message === 'validation_failed'`, código `502` para el resto:

```json
{
  "ok": false,
  "error": "={{ $json.error.message || 'internal_error' }}",
  "message": "No pudimos procesar tu solicitud. Reintentá en 1 minuto.",
  "trace_id": "={{ $execution.id }}",
  "issues": "={{ $json.error.context && $json.error.context.issues }}"
}
```

Asociar este workflow al principal: en **Settings del workflow principal → Error Workflow → seleccionar `LGA - Error handler`**.

---

## 4. Validación con n8n MCP

Antes de activar el workflow productivo:

```
mcp__n8n__n8n_validate_workflow(id: "<workflow_id>")
mcp__n8n__validate_workflow(workflow: <json>)
```

---

## 5. Exponer al público con Cloudflare Tunnel

```powershell
# Instalar cloudflared (una sola vez)
winget install --id Cloudflare.cloudflared

# Login
cloudflared tunnel login

# Crear tunnel
cloudflared tunnel create lga-n8n

# Asociar dominio
cloudflared tunnel route dns lga-n8n n8n.lga-arg.com

# Config: C:\Users\Gero\.cloudflared\config.yml
#   tunnel: <id>
#   credentials-file: C:\Users\Gero\.cloudflared\<id>.json
#   ingress:
#     - hostname: n8n.lga-arg.com
#       service: http://localhost:5678
#     - service: http_status:404

# Correr como servicio Windows
cloudflared service install
```

---

## 6. Test manual con curl

Generar firma HMAC en PowerShell:

```powershell
$secret = $env:LGA_WEBHOOK_SECRET
$body   = Get-Content -Raw .\test-payload.json
$ts     = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
$hmac   = New-Object System.Security.Cryptography.HMACSHA256
$hmac.Key = [System.Text.Encoding]::UTF8.GetBytes($secret)
$sig = ([System.BitConverter]::ToString(
  $hmac.ComputeHash([System.Text.Encoding]::UTF8.GetBytes("$ts.$body"))
) -replace '-','').ToLower()

curl.exe -X POST "https://n8n.lga-arg.com/webhook/lga-new-credit-app" `
  -H "Content-Type: application/json" `
  -H "X-LGA-Signature: $sig" `
  -H "X-LGA-Timestamp: $ts" `
  -H "X-LGA-IP: 127.0.0.1" `
  -H "X-LGA-UA: curl/test" `
  --data $body
```

Respuesta esperada: `200` con `application_code`.
