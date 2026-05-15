import { NextRequest, NextResponse } from 'next/server';
import { uploadToStorage } from '@/lib/supabase-storage';

export const runtime = 'nodejs';
export const dynamic = 'force-dynamic';
export const maxDuration = 30;

const MAX_SIZE = 10 * 1024 * 1024; // 10 MB
const ALLOWED_MIME = new Set([
  'image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif',
  'application/pdf',
]);
const DOC_TYPES = ['dni_front', 'dni_back', 'selfie_dni', 'income_proof', 'other'] as const;

export async function POST(req: NextRequest) {
  try {
    const fd = await req.formData();
    const file = fd.get('file') as File | null;
    const applicationCode = (fd.get('application_code') ?? '').toString();
    const docType = (fd.get('doc_type') ?? '').toString();

    if (!file) return NextResponse.json({ ok: false, error: 'missing_file' }, { status: 400 });
    if (!applicationCode || applicationCode.length > 64)
      return NextResponse.json({ ok: false, error: 'invalid_application_code' }, { status: 400 });
    if (!DOC_TYPES.includes(docType as never))
      return NextResponse.json({ ok: false, error: 'invalid_doc_type' }, { status: 400 });

    if (file.size > MAX_SIZE)
      return NextResponse.json({ ok: false, error: 'file_too_large', max_mb: 10 }, { status: 413 });
    if (!ALLOWED_MIME.has(file.type))
      return NextResponse.json({ ok: false, error: 'invalid_mime', got: file.type }, { status: 415 });

    const safeCode = applicationCode.replace(/[^a-zA-Z0-9_-]/g, '_').slice(0, 64);
    const ext = file.name.split('.').pop()?.toLowerCase().replace(/[^a-z0-9]/g, '') || 'bin';
    const filePath = `${safeCode}/${docType}-${Date.now()}.${ext}`;

    const bytes = await file.arrayBuffer();
    const { path } = await uploadToStorage(filePath, bytes, file.type);

    return NextResponse.json({ ok: true, file_path: path, size: file.size, mime: file.type }, { status: 200 });
  } catch (e: unknown) {
    const msg = (e as Error)?.message || 'upload_failed';
    if (msg.startsWith('missing_env_')) {
      return NextResponse.json(
        { ok: false, error: 'storage_not_configured', detail: 'Falta SUPABASE_SERVICE_ROLE_KEY en Vercel env vars' },
        { status: 503 },
      );
    }
    console.error('upload_error', msg);
    return NextResponse.json({ ok: false, error: 'upload_failed', detail: msg.slice(0, 200) }, { status: 502 });
  }
}
