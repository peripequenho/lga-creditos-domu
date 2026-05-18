import { NextRequest, NextResponse } from 'next/server';
import { applicationSchema, type ApplicationInput } from '@/lib/schema';
import { sql } from '@/lib/db';

export const runtime = 'nodejs';
export const dynamic = 'force-dynamic';
export const maxDuration = 20;

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
    // 1) Upsert client
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

    // 3) Insert credit_application
    const appRows = (await sql<AppRow[]>`
      insert into credit_applications (
        client_id, status, zone_status, zone_id,
        shop, source, product_id, variant_id, product_title, product_handle, product_url,
        unit_price_ars, quantity, cart_token, cart_total_ars, cart_summary,
        requested_amount_ars, requested_installments, declared_income_ars,
        utm_source, utm_medium, utm_campaign, utm_content, utm_term,
        referrer_url, landing_url, ip, user_agent,
        idempotency_key, raw_payload,
        payment_frequency, housing_status, occupation, occupation_detail,
        guarantor_name, guarantor_phone, guarantor_relation,
        estimated_installment_ars
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
        ${p.idempotency_key}, ${JSON.stringify(p)}::jsonb,
        ${p.payment_frequency}::payment_frequency, ${p.housing_status}::housing_status,
        ${p.occupation}::occupation_type, ${p.occupation_detail ?? null},
        ${p.guarantor_name ?? null}, ${p.guarantor_phone ?? null}, ${p.guarantor_relation ?? null},
        ${p.estimated_installment_ars ?? null}
      )
      on conflict (idempotency_key) do update set
        raw_payload = credit_applications.raw_payload
      returning id, application_code, status::text as status, zone_status::text as zone_status, created_at
    `) as unknown as AppRow[];

    const app = appRows[0]!;

    // 4) Insert document metadata rows si vinieron (paths en Storage)
    const docs: Array<{ doc_type: string; path: string | undefined }> = [
      { doc_type: 'dni_front',   path: p.doc_dni_front },
      { doc_type: 'dni_back',    path: p.doc_dni_back },
      { doc_type: 'selfie_dni',  path: p.doc_selfie_dni },
      { doc_type: 'income_proof',path: p.doc_income_proof },
    ];
    for (const d of docs) {
      if (!d.path) continue;
      sql`
        insert into documents (application_id, client_id, doc_type, file_path)
        values (${app.id}::uuid, ${clientId}::uuid, ${d.doc_type}, ${d.path})
        on conflict do nothing
      `.catch((e) => console.error('doc_insert_failed', d.doc_type, e?.message ?? e));
    }

    // 5) Insert application_event (no bloqueante)
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
          payment_frequency: p.payment_frequency,
          housing_status: p.housing_status,
          occupation: p.occupation,
          docs_uploaded: docs.filter(d => d.path).length,
        })}::jsonb
      )
    `.catch((e) => console.error('event_insert_failed', e?.message ?? e));

    // 6) Notificar al equipo via n8n VPS (workflow dispara Telegram + push a WP dashboard).
    // Payload completo: el notify workflow ahora hace 2 cosas (notif Telegram + crear post en WP).
    const NOTIFY_URL = process.env.N8N_NOTIFY_URL || 'https://n8n.lga-arg.com/webhook/lga-new-application-notify';
    void fetch(NOTIFY_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        // IDs
        application_code: app.application_code,
        application_id: app.id,
        client_id: clientId,
        // Cliente
        first_name: p.first_name,
        last_name: p.last_name,
        dni: p.dni,
        birth_date: p.birth_date,
        phone: p.phone,
        email: p.email || '',
        // Domicilio
        address_line: p.address_line,
        locality: p.locality,
        province: p.province,
        postal_code: p.postal_code,
        housing_status: p.housing_status,
        // Ocupación
        occupation: p.occupation,
        occupation_detail: p.occupation_detail || '',
        declared_income_ars: p.declared_income_ars,
        // Garante
        guarantor_name: p.guarantor_name || '',
        guarantor_phone: p.guarantor_phone || '',
        guarantor_relation: p.guarantor_relation || '',
        // Crédito
        requested_amount_ars: p.requested_amount_ars,
        payment_frequency: p.payment_frequency,
        requested_installments: p.requested_installments,
        estimated_installment_ars: p.estimated_installment_ars || 0,
        // Estado
        application_status: 'submitted',
        zone_status: zone.status,
        // Shopify
        shop: p.shop,
        source: p.source || '',
        product_id: p.product_id || '',
        variant_id: p.variant_id || '',
        product_title: p.product_title || '',
        product_handle: p.product_handle || '',
        unit_price_ars: p.unit_price_ars || 0,
        quantity: p.quantity || 1,
        cart_token: p.cart_token || '',
        cart_total_ars: p.cart_total_ars,
        cart_summary: p.cart_summary || '',
        // Marketing
        utm_source: p.utm_source || '',
        utm_medium: p.utm_medium || '',
        utm_campaign: p.utm_campaign || '',
        utm_content: p.utm_content || '',
        utm_term: p.utm_term || '',
        referrer_url: p.referrer_url || '',
        landing_url: p.landing_url || '',
      }),
      signal: AbortSignal.timeout(5_000),
    }).catch((e) => console.error('notify_failed', e?.message ?? e));

    // 7) Push al dashboard WordPress: lo hace el workflow n8n VPS que recibe
    // el webhook lga-new-application-notify (mismo que dispara Telegram).
    // Antes había acá un fetch directo desde Vercel a WP REST, pero Vercel
    // tenía problemas inyectando las env vars al runtime. Centralizar todo
    // en n8n VPS es más mantenible y desacopla el form de WordPress.

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
