'use client';

import { useFormContext } from 'react-hook-form';
import type { ApplicationInput } from '@/lib/schema';
import { UploadField } from '../UploadField';

export function Step4Documents({ applicationCode }: { applicationCode: string }) {
  const { setValue, watch } = useFormContext<ApplicationInput>();
  const docFront  = watch('doc_dni_front');
  const docBack   = watch('doc_dni_back');
  const docSelfie = watch('doc_selfie_dni');
  const docIncome = watch('doc_income_proof');

  return (
    <div className="space-y-5">
      <header>
        <h2 className="text-xl font-semibold text-zinc-900">Documentación</h2>
        <p className="text-sm text-zinc-600 mt-1">
          Tomá fotos claras o subí PDFs. Tus documentos se guardan privados — solo el equipo de LGA accede.
        </p>
      </header>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <UploadField
          label="DNI — frente"
          hint="Foto donde se lea bien tu nombre, DNI y foto."
          required
          applicationCode={applicationCode}
          docType="dni_front"
          value={docFront}
          onUploaded={(path) => setValue('doc_dni_front', path, { shouldValidate: true })}
        />
        <UploadField
          label="DNI — dorso"
          hint="Donde están el domicilio y el ejemplar."
          required
          applicationCode={applicationCode}
          docType="dni_back"
          value={docBack}
          onUploaded={(path) => setValue('doc_dni_back', path, { shouldValidate: true })}
        />
      </div>

      <UploadField
        label="Selfie sosteniendo el DNI"
        hint="Foto tuya en la que se vea tu cara y tu DNI al mismo tiempo. Sirve para validar identidad."
        required
        accept="image/*"
        applicationCode={applicationCode}
        docType="selfie_dni"
        value={docSelfie}
        onUploaded={(path) => setValue('doc_selfie_dni', path, { shouldValidate: true })}
      />

      <UploadField
        label="Comprobante de ingresos"
        hint="Último recibo de sueldo, último pago de monotributo, o lo que mejor documente tus ingresos. PDF o foto."
        required
        applicationCode={applicationCode}
        docType="income_proof"
        value={docIncome}
        onUploaded={(path) => setValue('doc_income_proof', path, { shouldValidate: true })}
      />

      <div className="rounded-xl bg-zinc-50 border border-zinc-200 p-3">
        <p className="text-xs text-zinc-600 leading-relaxed">
          <strong>Privacidad:</strong> tus documentos se almacenan en Supabase Storage en un bucket privado.
          Solo personal autorizado de LGA puede acceder con credenciales internas.
          No los compartimos con terceros.
        </p>
      </div>
    </div>
  );
}
