// ============================================================================
// Supabase Storage REST client (server-side only)
// ----------------------------------------------------------------------------
// Sube archivos al bucket privado `customer-documents` usando service_role.
// Edge/Node compatible (usa fetch nativo).
// ============================================================================

const SUPABASE_URL  = process.env.NEXT_PUBLIC_SUPABASE_URL;
const SERVICE_ROLE  = process.env.SUPABASE_SERVICE_ROLE_KEY;

export const BUCKET = 'customer-documents';

export function assertStorageConfig(): { url: string; key: string } {
  if (!SUPABASE_URL) throw new Error('missing_env_NEXT_PUBLIC_SUPABASE_URL');
  if (!SERVICE_ROLE) throw new Error('missing_env_SUPABASE_SERVICE_ROLE_KEY');
  return { url: SUPABASE_URL, key: SERVICE_ROLE };
}

export async function uploadToStorage(
  filePath: string,
  fileBytes: ArrayBuffer,
  contentType: string,
): Promise<{ path: string }> {
  const { url, key } = assertStorageConfig();
  const endpoint = `${url}/storage/v1/object/${BUCKET}/${filePath}`;
  const res = await fetch(endpoint, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${key}`,
      'apikey': key,
      'Content-Type': contentType || 'application/octet-stream',
      'x-upsert': 'true',
    },
    body: fileBytes,
  });
  if (!res.ok) {
    const txt = await res.text().catch(() => '');
    throw new Error(`storage_upload_failed_${res.status}: ${txt.slice(0, 200)}`);
  }
  return { path: filePath };
}
