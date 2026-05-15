# form — Next.js (creditos.domuhogar.com)

Frontend del formulario de solicitud de crédito LGA para clientes de Domu.

## Desarrollo local

```powershell
# 1) Instalar dependencias
pnpm install
# o: npm install

# 2) Copiar variables de entorno
Copy-Item .env.local.example .env.local
# Editar .env.local con los valores reales

# 3) Levantar dev server
pnpm dev
# Abre http://localhost:3000/aplicar
```

## Test rápido con datos del deep link

```
http://localhost:3000/aplicar?shop=test.myshopify.com&source=pdp&product_id=1&variant_id=1&title=Heladera%20test&price=35000000&qty=1&cart_total=35000000&utm_source=qa
```

> `price` y `cart_total` van en **centavos** (formato Shopify). El parser los divide por 100.

## Build de producción

```powershell
pnpm build
pnpm start
```

## Deploy a Vercel

```powershell
vercel --prod
```

Configurar env vars en **Project Settings → Environment Variables**:

| Var | Scope | Value |
|---|---|---|
| `N8N_WEBHOOK_URL` | Production, Preview | `https://n8n.lga-arg.com/webhook/lga-new-credit-app` |
| `N8N_WEBHOOK_SECRET` | Production, Preview | mismo valor que en `~/.n8n/.env` |
| `NEXT_PUBLIC_SITE_URL` | Production | `https://creditos.domuhogar.com` |
| `NEXT_PUBLIC_LGA_WHATSAPP` | Production | `+5493815551234` |

## Estructura

```
app/
  aplicar/page.tsx              Página del form, parsea deep link
  confirmacion/page.tsx         Post-submit, copy según zone_status
  api/submit-application/route.ts   Edge route, valida + firma HMAC + reenvía a n8n
  layout.tsx, page.tsx, globals.css
components/
  ApplicationForm.tsx           Form con react-hook-form + Zod resolver
  OrderSummary.tsx              Resumen del pedido (sticky en desktop)
  FieldError.tsx
lib/
  schema.ts                     applicationSchema + parseDeepLinkParams
  normalize.ts                  normalizePhoneAR, normalizeDni, postal, titleCase, ageInYears
  hmac.ts                       signHmac (Web Crypto, Edge-safe)
```

## Notas de seguridad

- `N8N_WEBHOOK_SECRET` **nunca** se expone al cliente. Solo se usa en la Edge route (`app/api/submit-application/route.ts`).
- El form **no** habla directo con Supabase. Todo pasa por n8n.
- HMAC + timestamp (ventana de 5 minutos) en cada request al webhook.
- `idempotency_key` UUID generado en cliente para evitar dobles submits.
- Headers de seguridad básicos en `next.config.mjs` (X-Frame-Options, etc.).
