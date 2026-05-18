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
      <label className="block text-sm font-medium text-fg-primary">
        {label} {required && <span className="text-state-risk">*</span>}
      </label>
      {hint && <p className="text-xs text-fg-muted -mt-1">{hint}</p>}

      <div
        className={`relative rounded border-2 border-dashed transition-colors ${
          done ? 'border-border-color bg-surface/40' :
          error ? 'border-state-risk/60 bg-surface/40' :
          'border-border-color hover:border-lga-primary hover:bg-surface/30'
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
            <p className="text-sm text-fg-primary font-medium">Subiendo…</p>
            <div className="h-1.5 bg-surface-raised rounded-full overflow-hidden">
              <div className="h-full bg-lga-primary transition-all" style={{ width: `${progress}%` }} />
            </div>
          </div>
        ) : done ? (
          <div className="flex items-center justify-center gap-2 text-state-ok">
            <span className="text-xl">✓</span>
            <span className="text-sm font-medium">Archivo cargado</span>
            <span className="text-xs text-state-ok">· Tocá para cambiar</span>
          </div>
        ) : (
          <div className="space-y-1">
            <p className="text-2xl">📎</p>
            <p className="text-sm text-fg-primary font-medium">Tocá para subir</p>
            <p className="text-xs text-fg-muted">JPG, PNG o PDF · hasta 10MB</p>
          </div>
        )}
      </div>

      {error && <p className="text-sm text-state-risk mt-1">{error}</p>}
    </div>
  );
}
