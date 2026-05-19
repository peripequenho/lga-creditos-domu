import { NextRequest, NextResponse } from 'next/server';
import { applicationSchema, type ApplicationInput } from '@/lib/schema';
import { sql } from '@/lib/db';

export const runtime = 'nodejs';
export const dynamic = 'force-dynamic';
export const maxDuration = 20;

/**
 * SIMPLIFICADO (2026-05-18 v2): Vercel/Next ya no es path crítico.
 * Este endpoint solo VALIDA con Zod y forward al webhook n8n all-in-one
 * (https://n8n.lga-arg.com/webhook/lga-shopify-form) que se encarga de:
 *   - Insert Supabase (clients + check_zone + credit_applications)
 *   - Telegram al equipo
 *   - Crear post WP solicitud (que a su vez auto-crea draft Shopify via plugin)
 *
 * Los uploads de docs (DNI / selfie / etc.) los seguimos insertando acá
 * porque el form Next.js ya los procesa con UploadField → Supabase Storage,
 * y solo necesitamos asociarlos a la application_id que n8n nos retorna.
 */

const N8N_WEBHOOK_URL = process.env.N8N_FORM_WEBHOOK_URL
  || 'https://n8n.lga-arg.com/webhook/lga-shopify-form';

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

  // Forward al webhook n8n (todo el trabajo lo hace n8n).
  let n8nResponse: { ok: boolean; application_code?: string; application_id?: string; zone_status?: string; status?: string };
  try {
    const resp = await fetch(N8N_WEBHOOK_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(p),
      signal: AbortSignal.timeout(20_000),
    });
    if (!resp.ok) {
      const body = await resp.text().catch(() => '');
      console.error('n8n_webhook_failed', resp.status, body.slice(0, 200));
      return NextResponse.json(
        { ok: false, error: 'upstream_error', message: 'No pudimos procesar tu solicitud. Reintentá en 1 minuto.' },
        { status: 502 },
      );
    }
    n8nResponse = await resp.json();
  } catch (e: unknown) {
    const err = e as { message?: string };
    console.error('n8n_webhook_exception', err.message);
    return NextResponse.json(
      { ok: false, error: 'upstream_timeout', message: 'No pudimos procesar tu solicitud. Reintentá en 1 minuto.' },
      { status: 502 },
    );
  }

  if (!n8nResponse?.ok || !n8nResponse.application_id) {
    return NextResponse.json(
      { ok: false, error: 'upstream_bad_response' },
      { status: 502 },
    );
  }

  // Insertar documents en background (paths en Supabase Storage). NO bloqueante.
  const docs: Array<{ doc_type: string; path: string | undefined }> = [
    { doc_type: 'dni_front',   path: p.doc_dni_front },
    { doc_type: 'dni_back',    path: p.doc_dni_back },
    { doc_type: 'selfie_dni',  path: p.doc_selfie_dni },
    { doc_type: 'income_proof',path: p.doc_income_proof },
  ];
  for (const d of docs) {
    if (!d.path) continue;
    sql`
      insert into documents (application_id, doc_type, file_path)
      values (${n8nResponse.application_id}::uuid, ${d.doc_type}, ${d.path})
      on conflict do nothing
    `.catch((e) => console.error('doc_insert_failed', d.doc_type, e?.message ?? e));
  }

  return NextResponse.json(
    {
      ok: true,
      application_code: n8nResponse.application_code,
      application_id: n8nResponse.application_id,
      status: 'submitted',
      zone_status: n8nResponse.zone_status,
      next_step: 'Te contactamos por WhatsApp en las próximas 24 hs hábiles.',
      received_at: new Date().toISOString(),
    },
    { status: 200 },
  );
}
