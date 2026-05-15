'use client';

import { useFormContext } from 'react-hook-form';
import type { ApplicationInput } from '@/lib/schema';
import { FREQUENCY_NOUN, HOUSING_OPTIONS, OCCUPATION_OPTIONS, calcInstallment } from '@/lib/schema';
import { FieldError } from '../FieldError';

function fmtMoney(v: number | undefined) {
  if (v == null || !Number.isFinite(v)) return '—';
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    maximumFractionDigits: 0,
  }).format(v);
}

function ageStr(d: string) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(d)) return '';
  const a = Math.floor((Date.now() - new Date(d).getTime()) / (365.25 * 24 * 3600 * 1000));
  return ` (${a} años)`;
}

type Props = { onEdit: (step: number) => void };

export function Step5Confirm({ onEdit }: Props) {
  const { register, formState: { errors }, watch } = useFormContext<ApplicationInput>();
  const v = watch();

  const housing  = HOUSING_OPTIONS.find((o) => o.value === v.housing_status)?.label ?? '—';
  const occ      = OCCUPATION_OPTIONS.find((o) => o.value === v.occupation)?.label ?? '—';
  const freq     = v.payment_frequency || 'monthly';
  const noun     = FREQUENCY_NOUN[freq];
  const cuota    = calcInstallment(v.requested_amount_ars || 0, v.requested_installments || 0, freq);
  const totalPagar = cuota * (v.requested_installments || 0);

  return (
    <div className="space-y-5">
      <header>
        <h2 className="text-xl font-semibold text-zinc-900">Repasá y confirmá</h2>
        <p className="text-sm text-zinc-600 mt-1">Si algo está mal, tocá el lápiz para volver.</p>
      </header>

      {/* Datos personales */}
      <SummaryCard title="Tus datos" onEdit={() => onEdit(0)}>
        <Row label="Nombre">{v.first_name} {v.last_name}</Row>
        <Row label="DNI">{v.dni}</Row>
        <Row label="Nacimiento">{v.birth_date}{ageStr(v.birth_date || '')}</Row>
        <Row label="Teléfono">{v.phone}</Row>
        {v.email && <Row label="Email">{v.email}</Row>}
      </SummaryCard>

      <SummaryCard title="Domicilio y situación" onEdit={() => onEdit(1)}>
        <Row label="Dirección">{v.address_line}, {v.locality}, {v.province} ({v.postal_code})</Row>
        <Row label="Vivienda">{housing}</Row>
        <Row label="Ocupación">{occ}{v.occupation_detail ? ` · ${v.occupation_detail}` : ''}</Row>
        <Row label="Ingreso mensual">{fmtMoney(v.declared_income_ars)}</Row>
        {v.guarantor_name && (
          <Row label="Garante">{v.guarantor_name} · {v.guarantor_phone}{v.guarantor_relation ? ` (${v.guarantor_relation})` : ''}</Row>
        )}
      </SummaryCard>

      <SummaryCard title="Crédito solicitado" onEdit={() => onEdit(2)}>
        <Row label="Monto">{fmtMoney(v.requested_amount_ars)}</Row>
        <Row label="Frecuencia">{({ daily: 'Diario', weekly: 'Semanal', monthly: 'Mensual' } as const)[freq]}</Row>
        <Row label="Cantidad de cuotas">{v.requested_installments} {v.requested_installments === 1 ? noun.single : noun.plural}</Row>
        <Row label="Cuota estimada">{fmtMoney(cuota)} / {noun.single}</Row>
        <Row label="Total a pagar (est.)">{fmtMoney(totalPagar)}</Row>
      </SummaryCard>

      <SummaryCard title="Documentos" onEdit={() => onEdit(3)}>
        <Row label="DNI frente">{v.doc_dni_front ? '✓ Cargado' : '✗ Falta'}</Row>
        <Row label="DNI dorso">{v.doc_dni_back ? '✓ Cargado' : '✗ Falta'}</Row>
        <Row label="Selfie con DNI">{v.doc_selfie_dni ? '✓ Cargado' : '✗ Falta'}</Row>
        <Row label="Comprobante ingresos">{v.doc_income_proof ? '✓ Cargado' : '✗ Falta'}</Row>
      </SummaryCard>

      <fieldset className="rounded-xl border border-zinc-200 p-4 space-y-3">
        <legend className="px-2 text-xs font-semibold text-zinc-500 uppercase tracking-wider">Términos</legend>
        <label className="flex items-start gap-3 text-sm text-zinc-700">
          <input type="checkbox" className="mt-1 h-4 w-4 accent-lga-primary" {...register('terms_accepted')} />
          <span>
            Acepto los <a className="underline text-lga-primary" href="/terminos" target="_blank" rel="noreferrer">términos y condiciones</a> y autorizo a LGA a contactarme para evaluar mi solicitud.
          </span>
        </label>
        <FieldError message={errors.terms_accepted?.message as string | undefined} />
        <label className="flex items-start gap-3 text-sm text-zinc-700">
          <input type="checkbox" className="mt-1 h-4 w-4 accent-lga-primary" {...register('marketing_consent')} />
          <span>Quiero recibir promociones y ofertas (opcional).</span>
        </label>
      </fieldset>
    </div>
  );
}

function SummaryCard({ title, onEdit, children }: { title: string; onEdit: () => void; children: React.ReactNode }) {
  return (
    <div className="rounded-xl border border-zinc-200 p-4">
      <header className="flex items-center justify-between mb-3">
        <h3 className="text-sm font-semibold text-zinc-900">{title}</h3>
        <button type="button" onClick={onEdit} className="text-xs text-lga-primary hover:underline flex items-center gap-1">
          ✎ Editar
        </button>
      </header>
      <dl className="space-y-1.5 text-sm">{children}</dl>
    </div>
  );
}

function Row({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div className="flex flex-col sm:flex-row sm:gap-3">
      <dt className="text-zinc-500 sm:w-44 flex-shrink-0">{label}</dt>
      <dd className="text-zinc-900 break-words">{children}</dd>
    </div>
  );
}
