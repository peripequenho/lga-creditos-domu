# Estado actual del sistema — 2026-05-15

## 🟢 FUNCIONANDO end-to-end EN PRODUCCIÓN

```
Cliente → https://creditos.domuhogar.com/aplicar → Vercel Edge route
   → HMAC sign(ts.idempotency_key)
   → POST https://<tunnel>.trycloudflare.com/webhook/lga-new-credit-app
   → n8n self-hosted local (workflow activo)
   → Postgres Supabase pooler aws-1-sa-east-1
   → INSERT clients + credit_applications + application_events
   → response JSON con application_code
```

**Test productivo confirmado:**
- `LGA-260515-0001` (test local)
- `LGA-260515-0002` (test productivo desde Vercel)
- Ambos visibles en Supabase `credit_applications`.

## ⚠️ El tunnel es TEMPORAL

Estoy usando **TryCloudflare quick tunnel** (URL random gratis). Cuando reinicies la PC o cierres `cloudflared`, el tunnel muere y la URL cambia.

**URL actual del tunnel:** `https://wiring-becoming-into-feof.trycloudflare.com`

**Vercel env var actualmente apunta a esa URL.**

## 🔴 Lo que falta para 100% producción

### 1. Tunnel permanente con `n8n.lga-arg.com`

El bloqueo: `lga-arg.com` tiene NS en Hostinger, no en Cloudflare. Para crear tunnel permanente con tu dominio:

**Paso a paso:**

1. **Crear cuenta Cloudflare** (gratis): https://dash.cloudflare.com/sign-up
2. **Add Site** → `lga-arg.com` → plan **Free**.
3. Cloudflare escanea DNS y te muestra los nameservers a usar (algo como `xxx.ns.cloudflare.com` + `yyy.ns.cloudflare.com`).
4. **Hostinger** → Dominios → `lga-arg.com` → Cambiar nameservers → pegar los 2 de CF.
5. Esperar 0-24 hs propagación. Cloudflare te avisa por email cuando está "Active".
6. Volver acá y avisame "lga-arg.com está active en CF". Yo retomo desde ahí.

Cuando esté active, ejecuto:
```powershell
& "C:\Users\Gero\.claude\scripts\cloudflared.exe" tunnel login          # browser auth
& "C:\Users\Gero\.claude\scripts\cloudflared.exe" tunnel create lga-n8n
& "C:\Users\Gero\.claude\scripts\cloudflared.exe" tunnel route dns lga-n8n n8n.lga-arg.com
& "C:\Users\Gero\.claude\scripts\cloudflared.exe" service install
```

Y actualizo la env var de Vercel a `https://n8n.lga-arg.com/webhook/lga-new-credit-app`.

### 2. Shopify snippets

Los archivos están en:
- `shopify/snippet-pdp.liquid`
- `shopify/snippet-cart.liquid`

**Pegarlos en Shopify** (5 min):
1. `https://admin.shopify.com/store/mem1a9-ev/themes` → Customize del theme publicado.
2. **Backup primero**: Themes → "..." → Duplicate → renombrar `Backup pre-LGA 2026-05-15`.
3. Template **Product → Default product** → Add block "Custom Liquid" → pegar contenido de `snippet-pdp.liquid` → Save.
4. Template **Cart → Default cart** → Add section "Custom Liquid" → pegar contenido de `snippet-cart.liquid` → Save.
5. Verificar en preview: abrir cualquier producto → botón "Comprar con crédito LGA" visible → click → debería ir a `creditos.domuhogar.com/aplicar?...`.

⚠️ **No puedo automatizar esto** porque el Theme Editor de Shopify corre en iframe cross-origin protegido.

### 3. Variables/config que vos quizás quieras cambiar

| Variable | Dónde | Valor actual | Tu valor |
|---|---|---|---|
| `NEXT_PUBLIC_LGA_WHATSAPP` | Vercel env vars | `+5493815551234` | El real |
| `LGA_WEBHOOK_SECRET` | Mismo en Vercel + `~/.claude/scripts/n8n-start.cmd` | hex64 generado | Rotar si querés. **No olvidar cambiar EN AMBOS lados** |

## 🛑 Si reiniciás la PC

El tunnel temporal va a morir. Para reactivarlo:

```powershell
& "C:\Users\Gero\.claude\scripts\cloudflared.exe" tunnel --url http://localhost:5678
```

Esperá la línea `https://<x>.trycloudflare.com` (toma ~5 seg). Después actualizá la env var `N8N_WEBHOOK_URL` en Vercel con esa URL nueva + Redeploy. Mientras no migres lga-arg.com a CF, este paso se repite cada vez.

Para evitar esto: **hacer el cambio de NS** (sección 1). Una vez hecho, el tunnel arranca como servicio Windows y nunca cambia.

## Componentes y IDs útiles

| Recurso | ID/URL | Notas |
|---|---|---|
| GitHub repo | `peripequenho/lga-creditos-domu` | privado |
| Vercel project | `lga-creditos-domu` en team `lga` | trial 14 días — agregar tarjeta antes |
| Vercel deploy | `lga-creditos-domu.vercel.app` | URL canónica |
| Custom domain | `creditos.domuhogar.com` | OK, SSL verificado |
| Supabase project | `lga-creditos` org `LGA` ref `tbzlkrvmlfyqyzqkkerf` | sa-east-1, Free tier |
| Supabase API URL | `https://tbzlkrvmlfyqyzqkkerf.supabase.co` | |
| Supabase DB password | `24njWnOyeSeFdtd8` | **Guardalo seguro** |
| n8n workflow | `LGA - Nueva solicitud crédito Domu` id `faSy7peLy80VrKCi` | activo |
| n8n credencial Postgres | `Supabase LGA — Postgres` id `857MwnILNdTuy8gr` | pooler aws-1-sa-east-1 |
| Shopify store | `mem1a9-ev.myshopify.com` (público `domuhogar.com`) | |
| Hostinger CNAME `creditos` | → `cname.vercel-dns.com.` | OK |
| Tunnel temporal (cambia) | `wiring-becoming-into-feof.trycloudflare.com` | NO persistente |

## Verificar que sigue funcionando

```powershell
# Test E2E productivo (desde cualquier máquina con internet)
$payload = [System.IO.File]::ReadAllText("C:\Users\Gero\PROYECTOS\lga-creditos-domu\n8n\test-e2e-productivo.payload.json", [System.Text.UTF8Encoding]::new($false)) | ConvertFrom-Json
$payload | Add-Member -NotePropertyName 'idempotency_key' -NotePropertyValue ([Guid]::NewGuid().ToString()) -Force
$body = $payload | ConvertTo-Json -Compress
Invoke-RestMethod -Method Post -Uri "https://creditos.domuhogar.com/api/submit-application" -ContentType 'application/json' -Body $body
```

Debe responder con `application_code: "LGA-260515-XXXX"`.

## Datos para verificar en Supabase

SQL Editor:
```sql
select a.application_code, a.status, a.zone_status, a.requested_amount_ars, a.requested_installments,
       c.dni, c.full_name, c.phone_e164, a.utm_source, a.utm_campaign, a.created_at
from credit_applications a
join clients c on c.id = a.client_id
order by a.created_at desc
limit 20;
```
