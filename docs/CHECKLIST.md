# Checklist de implementación — Fase 1

## Pre-flight (antes de tocar producción)

- [ ] **Backup theme Shopify**: Themes → "..." → Duplicate → renombrar `Backup pre-LGA <YYYY-MM-DD>`.
- [ ] **Cuenta Supabase** creada, proyecto nuevo en region `sa-east-1`, DB password guardada en password manager.
- [ ] **Dominio `creditos.domuhogar.com`** apuntando a Vercel (CNAME `cname.vercel-dns.com`).
- [ ] **Dominio `n8n.lga-arg.com`** apuntando vía Cloudflare Tunnel a `localhost:5678`.
- [ ] **cloudflared** instalado como servicio Windows: `cloudflared service install`.
- [ ] **Secret generado**: `openssl rand -hex 32` → guardar mismo valor en `.env.local` (Vercel) y `~/.n8n/.env` (n8n).

## Supabase

- [ ] Abrir SQL Editor del proyecto nuevo.
- [ ] Pegar `supabase/001_init_schema.sql` completo y ejecutar.
- [ ] Verificar tablas:
  ```sql
  select tablename from pg_tables
  where schemaname='public'
    and tablename in ('clients','credit_applications','operational_zones','application_events');
  ```
  → 4 filas.
- [ ] Verificar seed:
  ```sql
  select count(*) from operational_zones;
  ```
  → ≥ 11.
- [ ] Probar `check_zone`:
  ```sql
  select * from check_zone('T4000','San Miguel de Tucumán','Tucumán');  -- in_zone
  select * from check_zone('X9999','Marte','Tucumán');                  -- needs_review
  select * from check_zone('5000','Córdoba','Córdoba');                 -- out_of_zone
  ```
- [ ] Obtener **connection string** desde Project Settings → Database → URI mode → "Use a connection string" (NO el pooler para writes; sí pooler para reads futuros).

## n8n

- [ ] Cargar credencial **`Supabase LGA — Postgres`** con la connection string anterior.
- [ ] Editar `~/.n8n/.env` y agregar `LGA_WEBHOOK_SECRET=<el mismo de Vercel>`.
- [ ] Reiniciar n8n para que tome la env var.
- [ ] Crear workflow **`LGA - Nueva solicitud crédito Domu`** con los 10 nodos descritos en `n8n/README.md`.
- [ ] Validar con MCP: `mcp__n8n__n8n_validate_workflow` → sin errores.
- [ ] Crear workflow auxiliar **`LGA - Error handler`** y lincarlo en Settings del workflow principal.
- [ ] Activar workflow principal.
- [ ] Test con curl (ver `n8n/README.md` §6): respuesta 200 con `application_code`.
- [ ] Test HMAC inválido → 4xx + alerta Telegram al admin.
- [ ] Test doble submit con mismo `idempotency_key` → segunda no duplica.

## Next.js form

- [ ] `cd form && pnpm install` (o `npm install`).
- [ ] Copiar `.env.local.example` a `.env.local` y completar:
  - `N8N_WEBHOOK_URL=https://n8n.lga-arg.com/webhook/lga-new-credit-app`
  - `N8N_WEBHOOK_SECRET=<el mismo de n8n>`
  - `NEXT_PUBLIC_SITE_URL=https://creditos.domuhogar.com`
  - `NEXT_PUBLIC_LGA_WHATSAPP=+5493815551234`
- [ ] `pnpm dev` → abrir `http://localhost:3000/aplicar?shop=test&cart_total=35000000&title=Test&price=35000000&qty=1` → ver form prefill.
- [ ] `pnpm build` → sin errores TypeScript.
- [ ] Deploy a Vercel: `vercel --prod` (o conectar GitHub).
- [ ] Configurar env vars en Vercel (Production + Preview).
- [ ] Configurar dominio `creditos.domuhogar.com` en Vercel.
- [ ] Esperar emisión SSL (~minutos).

## Shopify (último paso, después de que n8n + form estén listos)

- [ ] Theme duplicado como backup, renombrado `Backup pre-LGA <fecha>`.
- [ ] **PDP**: Themes → Customize → Products → Default product → Add block "Custom Liquid" dentro de la sección Product information → pegar `shopify/snippet-pdp.liquid` → posicionar debajo del CTA "Add to cart" → Save.
- [ ] **Cart page**: Cart → Default cart → Add section "Custom Liquid" → pegar `shopify/snippet-cart.liquid` → posicionar antes del CTA "Checkout" → Save.
- [ ] **(Opcional) Cart drawer**: si el theme lo permite, mismo proceso. Sino dejar solo Cart page.
- [ ] Verificar en preview: producto cualquiera → botón visible → click → cae en `creditos.domuhogar.com/aplicar?...`.

## Verificación end-to-end (12 pasos)

1. [ ] Abrir incognito: `https://domuhogar.com/products/<producto>?utm_source=qa&utm_campaign=mvp`.
2. [ ] Botón "Comprar con crédito LGA" visible debajo del CTA principal.
3. [ ] Click → redirect a `creditos.domuhogar.com/aplicar?...` con todos los params.
4. [ ] Resumen del pedido muestra título, precio (en pesos enteros), qty correctos.
5. [ ] Completar form con DNI ficticio `99999999`, dirección `Av. Mate de Luna 1200`, postal `T4000`.
6. [ ] Submit → redirect a `/confirmacion?code=LGA-YYMMDD-NNNN&zone=in_zone`.
7. [ ] Supabase SQL Editor:
   ```sql
   select c.dni, c.first_name, c.last_name, c.phone_e164,
          a.application_code, a.status, a.zone_status, a.requested_amount_ars,
          a.utm_source, a.utm_campaign
   from credit_applications a
   join clients c on c.id = a.client_id
   order by a.created_at desc limit 5;
   ```
   → fila con `zone_status='in_zone'`, UTMs poblados, monto correcto.
8. [ ] Verificar evento `submitted` en `application_events`.
9. [ ] Telegram al admin con resumen de la nueva solicitud.
10. [ ] Re-submit con mismo `idempotency_key` (DevTools repeat XHR) → mismo `application_code`, no fila nueva.
11. [ ] Probar zona out: form con postal `5000` + province `Córdoba` → `zone_status='out_of_zone'`, mensaje UI correcto.
12. [ ] Curl directo al webhook con HMAC inválido → 4xx, no inserta nada en Supabase.

## Riesgos a verificar antes del lanzamiento

- [ ] DNS `creditos.domuhogar.com` propagado >48 hs antes del go-live (`dig` o `nslookup`).
- [ ] SSL Vercel emitido y válido (browser muestra candado verde).
- [ ] SSL `n8n.lga-arg.com` emitido por Cloudflare.
- [ ] Cloudflare Tunnel corriendo como servicio Windows (`Get-Service cloudflared`).
- [ ] n8n con backup reciente del SQLite (`bak-domu-*` en `~/.n8n/`).
- [ ] `LGA_WEBHOOK_SECRET` idéntico en Vercel env y `~/.n8n/.env` (un solo char distinto y todo falla).
- [ ] El botón LGA no rompe el botón "Add to cart" estándar.
- [ ] El checkout estándar de Shopify sigue funcionando con compra contado.

## Rollback rápido (si algo se rompe)

| Componente | Rollback |
|---|---|
| Shopify snippet rompe layout | Customize → borrar el bloque Custom Liquid → Save. |
| Theme entero raro | Themes → publicar `Backup pre-LGA <fecha>`. |
| n8n workflow falla | Desactivar el workflow desde la UI; form recibe 502 y muestra error al cliente. |
| Form Vercel cae | Revertir al deploy anterior desde Vercel UI. |
| Supabase schema malo | `drop function check_zone(...)`; `drop table application_events, credit_applications, clients, operational_zones`; re-correr SQL. (Solo si NO hay datos productivos.) |
| Secret comprometido | Generar nuevo `LGA_WEBHOOK_SECRET`, actualizar en Vercel y n8n, reiniciar ambos. |
