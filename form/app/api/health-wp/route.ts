import { NextResponse } from 'next/server';

export const runtime = 'nodejs';
export const dynamic = 'force-dynamic';

// Endpoint debug temporal. NO loggear los valores, solo si están seteadas.
export async function GET() {
  const WP_URL = process.env.WP_REST_URL;
  const WP_AUTH = process.env.WP_REST_AUTH;

  // Si tenemos creds, hacer un GET a WP para confirmar que llegamos
  let wpReachable: boolean | string = false;
  if (WP_URL && WP_AUTH) {
    try {
      const res = await fetch(WP_URL, {
        method: 'GET',
        headers: { Authorization: `Basic ${WP_AUTH}` },
        signal: AbortSignal.timeout(5_000),
      });
      wpReachable = res.ok ? `ok (${res.status})` : `non-ok (${res.status})`;
    } catch (e) {
      const err = e as { message?: string };
      wpReachable = `fetch_failed: ${err.message ?? 'unknown'}`;
    }
  }

  return NextResponse.json({
    hasUrl: !!WP_URL,
    urlPreview: WP_URL ? WP_URL.replace(/(.{40}).*/, '$1...') : null,
    hasAuth: !!WP_AUTH,
    authLength: WP_AUTH ? WP_AUTH.length : 0,
    wpReachable,
  });
}
