// ============================================================================
// HMAC SHA-256 helper (Edge runtime: Web Crypto API)
// ============================================================================

/**
 * Genera firma `ts.body` con HMAC-SHA256 y devuelve hex.
 * Edge-runtime safe (no usa `crypto` de Node).
 */
export async function signHmac(secret: string, ts: string, body: string): Promise<string> {
  const enc = new TextEncoder();
  const key = await crypto.subtle.importKey(
    'raw',
    enc.encode(secret),
    { name: 'HMAC', hash: 'SHA-256' },
    false,
    ['sign'],
  );
  const sig = await crypto.subtle.sign('HMAC', key, enc.encode(ts + '.' + body));
  return Array.from(new Uint8Array(sig))
    .map((b) => b.toString(16).padStart(2, '0'))
    .join('');
}
