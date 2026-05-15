# LGA Crédito ↔ Domu — Sistema de captación de solicitudes

Sistema de crédito propio de LGA integrado con la tienda Shopify Domu. **Fase 1** entregada en este repo: captura de solicitud desde Domu → formulario Next.js → n8n → Supabase.

> Plan completo en [`docs/PLAN.md`](docs/PLAN.md) (link al plan archivado en `~/.claude/plans/`). Checklist accionable en [`docs/CHECKLIST.md`](docs/CHECKLIST.md).

## Stack Fase 1

- **Shopify** Domu (`mem1a9-ev.myshopify.com`) — bloques Custom Liquid pegados desde Theme Editor.
- **Next.js 14** App Router + Tailwind + Zod + react-hook-form, deploy Vercel, dominio `creditos.domu.com.ar`.
- **Supabase** Postgres (proyecto nuevo, region `sa-east-1`).
- **n8n** self-hosted ya existente, expuesto a internet con **Cloudflare Tunnel** en `n8n.lga.com.ar`.

## Estructura

```
lga-creditos-domu/
├─ supabase/
│  └─ 001_init_schema.sql       Schema completo + enums + función check_zone + seed Tucumán + RLS
├─ shopify/
│  ├─ snippet-pdp.liquid        Botón "Comprar con crédito LGA" para Product template
│  └─ snippet-cart.liquid       Botón para Cart template / drawer
├─ n8n/
│  └─ README.md                 Guía paso a paso para construir el workflow en la UI
├─ form/                        App Next.js (subdominio creditos.domu.com.ar)
│  ├─ app/
│  │  ├─ aplicar/page.tsx       Formulario público
│  │  ├─ confirmacion/page.tsx  Pantalla post-submit
│  │  ├─ api/submit-application/route.ts   Edge route → firma HMAC → POST a n8n
│  │  ├─ layout.tsx, page.tsx, globals.css
│  ├─ components/
│  │  ├─ ApplicationForm.tsx    Form con react-hook-form + Zod
│  │  ├─ OrderSummary.tsx       Resumen del pedido (prefill desde deep link)
│  │  └─ FieldError.tsx
│  ├─ lib/
│  │  ├─ schema.ts              Zod + parser de query string
│  │  ├─ normalize.ts           DNI / teléfono AR / postal / title-case / edad
│  │  └─ hmac.ts                HMAC SHA-256 vía Web Crypto (Edge runtime)
│  ├─ package.json, tsconfig.json, next.config.mjs, tailwind.config.ts
│  └─ .env.local.example        Plantilla de variables — copiar a .env.local
└─ docs/
   └─ CHECKLIST.md              Checklist de implementación + verificación E2E
```

## Quick start (orden recomendado)

1. **Supabase** — crear proyecto, ejecutar `supabase/001_init_schema.sql` en SQL Editor.
2. **Cloudflare Tunnel** — exponer `localhost:5678` (n8n) como `n8n.lga.com.ar`.
3. **n8n workflow** — construir `LGA - Nueva solicitud crédito Domu` siguiendo `n8n/README.md`.
4. **Next.js form** — `cd form && pnpm install && pnpm dev`. Deploy Vercel con dominio `creditos.domu.com.ar`.
5. **Shopify** — duplicate del theme (backup) + pegar snippets como Custom Liquid en Theme Editor.

Detalles en `docs/CHECKLIST.md`.

## Restricciones de diseño

- Shopify NO contiene lógica financiera.
- Scoring, cuotas, pagos, mora y paneles **NO están en F1** (diseñados en `~/.claude/plans/`).
- La IA generativa **NUNCA aprueba créditos**. Solo asiste (parseo de docs, redacción).
- Vendedores y cobradores solo verán lo asignado (RLS, F4+).
