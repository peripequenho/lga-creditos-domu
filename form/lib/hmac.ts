// ============================================================================
// HMAC SHA-256 helper (Edge runtime: Web Crypto API)
// ============================================================================

/**
 * Firma `ts.idempotency_key` con HMAC-SHA256 y devuelve hex.
 *
 * Nota: NO firmamos el body porque n8n parsea el JSON antes de pasarlo al
 * Code node, lo que rompe firmas basadas en bytes raw. Firmar solo
 * idempotency_key (UUID v4) + timestamp es suficiente para prevenir replay
 * (ventana 5 min) y manipulación de identidad de la solicitud.
 * Edge-runtime safe (Web Crypto, no Node crypto).
 */
export async function signHmac(secret: string, ts: string, idempotencyKey: string): Promise<string> {
  const enc = new TextEncoder();
  const key = await crypto.subtle.importKey(
    'raw',
    enc.encode(secret),
    { name: 'HMAC', hash: 'SHA-256' },
    false,
    ['sign'],
  );
  const sig = await crypto.subtle.sign('HMAC', key, enc.encode(ts + '.' + idempotencyKey));
  return Array.from(new Uint8Array(sig))
    .map((b) => b.toString(16).padStart(2, '0'))
    .join('');
}
