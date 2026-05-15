import { NextRequest, NextResponse } from 'next/server';
import { applicationSchema, type ApplicationInput } from '@/lib/schema';
import { sql } from '@/lib/db';

// ============================================================================
// Edge route — escribe directo a Supabase (sin tunnel, sin n8n)
// ----------------------------------------------------------------------------
// Antes pasaba por: Vercel Edge → tunnel → n8n local → Supabase
// Ahora: Vercel Node → Supabase Postgres pooler directo
// runtime: 'nodejs' porque postgres-js requiere TCP (no Edge runtime).
// ============================================================================

export const runtime = 'nodejs';
export const dynamic = 'force-dynamic';
export const maxDuration = 15;

type ZoneRow = { status: 'in_zone' | 'out_of_zone' | 'needs_review'; zone_id: string | null };
type ClientRow = { id: string };
type AppRow = {
  id: string;
  application_code: string;
  status: string;
  zone_status: string;
  created_at: string;
};

export async function POST(req: NextRequest) {
  let json: unknown;
  try {
    json = await req.json();
  } catch {
    return NextResponse.json({ ok: false, error: 'invalid_json' }, { status: 400 });
  }

  const parsed = applicationSchema.safeParse(json);
  if (!parsed.success) {
    return NextResponse.json(
      { ok: false, error: 'validation_failed', issues: parsed.error.issues },
      { status: 422 },
    );
  }
  const p: ApplicationInput = parsed.data;

  const ip = req.headers.get('x-forwarded-for')?.split(',')[0]?.trim() ?? '';
  const ua = req.headers.get('user-agent') ?? '';

  try {
    // 1) Upsert client por DNI
    const clientRows = (await sql<ClientRow[]>`
      insert into clients (
        dni, first_name, last_name, email, phone_e164, birth_date,
        address_line, locality, province, postal_code, source, marketing_consent
      ) values (
        ${p.dni}, ${p.first_name}, ${p.last_name},
        ${p.email || null}, ${p.phone}, ${p.birth_date}::date,
        ${p.address_line}, ${p.locality}, ${p.province}, ${p.postal_code},
        ${'domu_' + (p.source || 'unknown')}, ${p.marketing_consent}
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
      returning id
    `) as unknown as ClientRow[];

    const clientId = clientRows[0]!.id;

    // 2) Check zone
    const zoneRows = (await sql<ZoneRow[]>`
      select status, zone_id
      from check_zone(${p.postal_code}, ${p.locality}, ${p.province})
    `) as unknown as ZoneRow[];

    const zone = zoneRows[0] ?? { status: 'needs_review' as const, zone_id: null };

    // 3) Insert credit_application (idempotente por idempotency_key)
    const appRows = (await sql<AppRow[]>`
      insert into credit_applications (
        client_id, status, zone_status, zone_id,
        shop, source, product_id, variant_id, product_title, product_handle, product_url,
        unit_price_ars, quantity, cart_token, cart_total_ars, cart_summary,
        requested_amount_ars, requested_installments, declared_income_ars,
        utm_source, utm_medium, utm_campaign, utm_content, utm_term,
        referrer_url, landing_url, ip, user_agent,
        idempotency_key, raw_payload
      ) values (
        ${clientId}, 'submitted', ${zone.status}::zone_status, ${zone.zone_id},
        ${p.shop}, ${p.source ?? null}, ${p.product_id ?? null}, ${p.variant_id ?? null},
        ${p.product_title ?? null}, ${p.product_handle ?? null}, ${p.referrer_url ?? null},
        ${p.unit_price_ars ?? null}, ${p.quantity ?? null}, ${p.cart_token ?? null},
        ${p.cart_total_ars}, ${p.cart_summary ?? null},
        ${p.requested_amount_ars}, ${p.requested_installments}, ${p.declared_income_ars ?? null},
        ${p.utm_source ?? null}, ${p.utm_medium ?? null}, ${p.utm_campaign ?? null},
        ${p.utm_content ?? null}, ${p.utm_term ?? null},
        ${p.referrer_url ?? null}, ${p.landing_url ?? null}, ${ip}, ${ua},
        ${p.idempotency_key}, ${JSON.stringify(p)}::jsonb
      )
      on conflict (idempotency_key) do update set
        raw_payload = credit_applications.raw_payload
      returning id, application_code, status::text as status, zone_status::text as zone_status, created_at
    `) as unknown as AppRow[];

    const app = appRows[0]!;

    // 4) Insert application_event (no bloqueante)
    sql`
      insert into application_events (
        application_id, actor, actor_label, event_type, to_status, detail
      ) values (
        ${app.id}::uuid, 'external', 'domu_form', 'submitted', 'submitted',
        ${JSON.stringify({
          ip,
          user_agent: ua,
          shop: p.shop,
          utm_source: p.utm_source ?? null,
          utm_campaign: p.utm_campaign ?? null,
          zone_status: zone.status,
        })}::jsonb
      )
    `.catch((e) => {
      console.error('event_insert_failed', e?.message ?? e);
    });

    return NextResponse.json(
      {
        ok: true,
        application_code: app.application_code,
        application_id: app.id,
        status: 'submitted',
        zone_status: zone.status,
        next_step: 'Te contactamos por WhatsApp en las próximas 24 hs hábiles.',
        received_at: new Date().toISOString(),
      },
      { status: 200 },
    );
  } catch (e: unknown) {
    const err = e as { message?: string; code?: string };
    console.error('submit_failed', err.message, err.code);
    return NextResponse.json(
      {
        ok: false,
        error: 'database_error',
        message: 'No pudimos procesar tu solicitud. Reintentá en 1 minuto.',
        code: err.code ?? null,
      },
      { status: 502 },
    );
  }
}
