# Estado actual del sistema — 2026-05-15

## Arquitectura productiva (all-cloud, 0 dependencia de tu PC)

```
Cliente browser
   ↓ POST
https://creditos.domuhogar.com/aplicar  (Vercel)
   ↓ /api/submit-application (Node runtime)
   ↓ postgres-js sobre pooler aws-1-sa-east-1.pooler.supabase.com:6543
Supabase Postgres
   ↓ rows en clients + credit_applications + application_events
   ↓ (futuro: trigger / cron n8n / Supabase Realtime)
n8n local (opcional, para automatizaciones async: scoring, WhatsApp, mora)
```

**No hay tunnel. No hay n8n en el path crítico. No depende de tu PC encendida.**

## Verificaciones hechas

| Test | Resultado |
|---|---|
| Local: form local → Supabase directo | ✅ `LGA-260515-0003` |
| Producción: `creditos.domuhogar.com` → Supabase directo | ⏳ pending (esperando deploy con DATABASE_URL) |

## Componentes y IDs

| Recurso | Valor |
|---|---|
| GitHub repo | [`peripequenho/lga-creditos-domu`](https://github.com/peripequenho/lga-creditos-domu) privado |
| Vercel project | `lga-creditos-domu` en team `lga` |
| Vercel custom domain | `creditos.domuhogar.com` ✅ con SSL |
| Supabase project ref | `tbzlkrvmlfyqyzqkkerf` |
| Supabase URL | `https://tbzlkrvmlfyqyzqkkerf.supabase.co` |
| Supabase region | `sa-east-1` (São Paulo) |
| DB password | `24njWnOyeSeFdtd8` 🔒 **guardar en password manager** |
| Pooler host | `aws-1-sa-east-1.pooler.supabase.com:6543` |
| Pooler user | `postgres.tbzlkrvmlfyqyzqkkerf` |
| Hostinger CNAME `creditos` | → `cname.vercel-dns.com.` ✅ |
| Shopify store | `mem1a9-ev.myshopify.com` (dominio público `domuhogar.com`) |

## Vercel environment variables (productivas)

| Var | Scope | Valor |
|---|---|---|
| `DATABASE_URL` | Production + Preview | `postgresql://postgres.tbzlkrvmlfyqyzqkkerf:<DB_PASS>@aws-1-sa-east-1.pooler.supabase.com:6543/postgres` |
| `NEXT_PUBLIC_SITE_URL` | Production | `https://creditos.domuhogar.com` |
| `NEXT_PUBLIC_LGA_WHATSAPP` | Production | `+5493815551234` *(placeholder — cambiar cuando me pases el WA real)* |

> Las env vars `N8N_WEBHOOK_URL` y `N8N_WEBHOOK_SECRET` ya **no se usan** y pueden borrarse (deprecated por la nueva arquitectura).

## n8n local — qué hacer con él

Pasa a ser **opcional y solo para automatización async**. El workflow `LGA - Nueva solicitud crédito Domu` (id `faSy7peLy80VrKCi`) **ya no es path crítico**.

Opciones:
1. **Desactivarlo** desde la UI (no lo borres, queda como template) — el form sigue funcionando, simplemente el webhook deja de recibir nada.
2. **Repropósito**: cambiar el trigger a Postgres polling sobre `application_events` (no implementado todavía) → cuando llegue una nueva solicitud, n8n levanta y ejecuta scoring + WhatsApp.

La credencial Postgres `Supabase LGA — Postgres` (id `857MwnILNdTuy8gr`) queda y se usa para los workflows async futuros.

## Lo único que falta de tu lado

### 1. Snippets Shopify (5 min)

`https://admin.shopify.com/store/mem1a9-ev/themes` → Customize:
1. **Backup primero**: Themes → "..." → Duplicate → renombrar `Backup pre-LGA 2026-05-15`.
2. Templates → Product → Add block "Custom Liquid" → pegar contenido de [`shopify/snippet-pdp.liquid`](../shopify/snippet-pdp.liquid).
3. Templates → Cart → Add section "Custom Liquid" → pegar [`shopify/snippet-cart.liquid`](../shopify/snippet-cart.liquid).

⚠️ No puedo automatizarlo porque el Theme Editor de Shopify corre en iframe cross-origin protegido.

### 2. Pasame WhatsApp real

Actualmente está placeholder `+5493815551234` en Vercel env var `NEXT_PUBLIC_LGA_WHATSAPP`. Cambialo desde Vercel → Settings → Env Vars cuando me lo pases o me decís y lo cambio yo.

### 3. Vercel: agregar tarjeta antes del trial

El trial expira en 14 días. Después del trial sin tarjeta, el proyecto puede quedar suspendido.

## Verificar end-to-end

```powershell
# Test productivo desde cualquier máquina con internet:
$payload = [System.IO.File]::ReadAllText("C:\Users\Gero\PROYECTOS\lga-creditos-domu\n8n\test-e2e-productivo.payload.json", [System.Text.UTF8Encoding]::new($false)) | ConvertFrom-Json
$payload | Add-Member -NotePropertyName 'idempotency_key' -NotePropertyValue ([Guid]::NewGuid().ToString()) -Force
$body = $payload | ConvertTo-Json -Compress
Invoke-RestMethod -Method Post -Uri "https://creditos.domuhogar.com/api/submit-application" -ContentType 'application/json' -Body $body
```

Debe responder `application_code: "LGA-260515-XXXX"`.

## Verificar en Supabase

SQL Editor:
```sql
select a.application_code, a.status, a.zone_status,
       a.requested_amount_ars, a.requested_installments,
       c.dni, c.full_name, c.phone_e164,
       a.utm_source, a.utm_campaign, a.created_at
from credit_applications a
join clients c on c.id = a.client_id
order by a.created_at desc
limit 20;
```

## Cambios respecto a la versión anterior

| Antes | Ahora |
|---|---|
| Vercel Edge → HMAC sign → tunnel → n8n → Postgres | Vercel Node → postgres-js → Postgres |
| Dependencia de PC encendida 24/7 | Cero dependencia de tu PC |
| Tunnel temporal (URL cambia al reiniciar) | URL permanente `creditos.domuhogar.com` |
| HMAC compartido entre Vercel y n8n | DATABASE_URL en server env de Vercel, no se expone |
| Latencia: ~500ms (tunnel + n8n + Postgres) | Latencia: ~50ms (pooler directo) |
| 3 env vars (URL + SECRET + WhatsApp) | 2 env vars (DATABASE_URL + WhatsApp) |
| n8n requerido para procesar form | n8n opcional, solo async |

## Si querés tunnel permanente igual (para administración n8n remota)

Sigue siendo válido el plan: migrar NS `lga-arg.com` a Cloudflare + Cloudflare Tunnel. Eso te da `https://n8n.lga-arg.com` para acceder a la UI de n8n desde cualquier lado. Pero ya no afecta al form ni a la captura de solicitudes. Es para vos administrar.
