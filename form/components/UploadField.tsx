'use client';

import { useRef, useState } from 'react';

type Props = {
  label: string;
  hint?: string;
  accept?: string;
  required?: boolean;
  value?: string;
  applicationCode: string;
  docType: 'dni_front' | 'dni_back' | 'selfie_dni' | 'income_proof';
  onUploaded: (filePath: string, meta: { size: number; mime: string }) => void;
};

export function UploadField({
  label, hint, accept = 'image/*,application/pdf',
  required, value, applicationCode, docType, onUploaded,
}: Props) {
  const inputRef = useRef<HTMLInputElement>(null);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [progress, setProgress] = useState(0);

  async function handleFile(file: File) {
    setError(null);
    if (file.size > 10 * 1024 * 1024) {
      setError('Máximo 10MB por archivo');
      return;
    }
    setUploading(true);
    setProgress(10);
    try {
      const fd = new FormData();
      fd.append('file', file);
      fd.append('application_code', applicationCode);
      fd.append('doc_type', docType);

      const res = await fetch('/api/upload-document', { method: 'POST', body: fd });
      setProgress(80);
      const json = await res.json();
      if (!res.ok || !json.ok) {
        throw new Error(json.error || 'No pudimos subir el archivo. Reintentá.');
      }
      setProgress(100);
      onUploaded(json.file_path, { size: file.size, mime: file.type });
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setUploading(false);
    }
  }

  const done = !!value && !uploading && !error;

  return (
    <div className="space-y-2">
      <label className="block text-sm font-medium text-zinc-700">
        {label} {required && <span className="text-red-500">*</span>}
      </label>
      {hint && <p className="text-xs text-zinc-500 -mt-1">{hint}</p>}

      <div
        className={`relative rounded-xl border-2 border-dashed transition-colors ${
          done ? 'border-emerald-300 bg-emerald-50/40' :
          error ? 'border-red-300 bg-red-50/40' :
          'border-zinc-300 hover:border-lga-primary hover:bg-emerald-50/30'
        } p-4 text-center cursor-pointer`}
        onClick={() => !uploading && inputRef.current?.click()}
      >
        <input
          ref={inputRef}
          type="file"
          accept={accept}
          className="sr-only"
          onChange={(e) => {
            const f = e.target.files?.[0];
            if (f) handleFile(f);
          }}
        />
        {uploading ? (
          <div className="space-y-2">
            <p className="text-sm text-zinc-700 font-medium">Subiendo…</p>
            <div className="h-1.5 bg-zinc-200 rounded-full overflow-hidden">
              <div className="h-full bg-lga-primary transition-all" style={{ width: `${progress}%` }} />
            </div>
          </div>
        ) : done ? (
          <div className="flex items-center justify-center gap-2 text-emerald-700">
            <span className="text-xl">✓</span>
            <span className="text-sm font-medium">Archivo cargado</span>
            <span className="text-xs text-emerald-600">· Tocá para cambiar</span>
          </div>
        ) : (
          <div className="space-y-1">
            <p className="text-2xl">📎</p>
            <p className="text-sm text-zinc-700 font-medium">Tocá para subir</p>
            <p className="text-xs text-zinc-500">JPG, PNG o PDF · hasta 10MB</p>
          </div>
        )}
      </div>

      {error && <p className="text-sm text-red-600 mt-1">{error}</p>}
    </div>
  );
}
