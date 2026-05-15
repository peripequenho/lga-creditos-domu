import { NextRequest, NextResponse } from 'next/server';
import { applicationSchema } from '@/lib/schema';
import { signHmac } from '@/lib/hmac';

export const runtime = 'edge';
export const dynamic = 'force-dynamic';

const N8N_URL    = process.env.N8N_WEBHOOK_URL;
const N8N_SECRET = process.env.N8N_WEBHOOK_SECRET;

export async function POST(req: NextRequest) {
  if (!N8N_URL || !N8N_SECRET) {
    return NextResponse.json({ ok: false, error: 'missing_env' }, { status: 500 });
  }

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

  const body = JSON.stringify(parsed.data);
  const ts = String(Math.floor(Date.now() / 1000));
  const sig = await signHmac(N8N_SECRET, ts, parsed.data.idempotency_key);

  const ip = req.headers.get('x-forwarded-for')?.split(',')[0]?.trim() ?? '';
  const ua = req.headers.get('user-agent') ?? '';

  let upstream: Response | null = null;
  try {
    upstream = await fetch(N8N_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-LGA-Signature': sig,
        'X-LGA-Timestamp': ts,
        'X-LGA-IP': ip,
        'X-LGA-UA': ua,
      },
      body,
      signal: AbortSignal.timeout(15_000),
    });
  } catch {
    return NextResponse.json({ ok: false, error: 'upstream_unreachable' }, { status: 502 });
  }

  const data = await upstream.json().catch(() => ({}));
  if (!upstream.ok) {
    return NextResponse.json(
      {
        ok: false,
        error: (data as any)?.error ?? 'upstream_error',
        message: (data as any)?.message ?? 'No pudimos procesar tu solicitud. Reintentá en 1 minuto.',
        trace_id: (data as any)?.trace_id,
      },
      { status: upstream.status },
    );
  }

  return NextResponse.json(data, { status: 200 });
}
