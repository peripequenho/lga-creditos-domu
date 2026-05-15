'use client';

import { useEffect, useState } from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useRouter } from 'next/navigation';
import { applicationSchema, type ApplicationInput } from '@/lib/schema';
import { StepProgress } from './StepProgress';
import { StickyProduct } from './StickyProduct';
import { FormNav } from './FormNav';
import { Step1Identity } from './steps/Step1Identity';
import { Step2Address } from './steps/Step2Address';
import { Step3Credit } from './steps/Step3Credit';
import { Step4Documents } from './steps/Step4Documents';
import { Step5Confirm } from './steps/Step5Confirm';

const STEPS = [
  { label: 'Identidad', fields: ['first_name','last_name','dni','birth_date','phone','email'] as const },
  { label: 'Domicilio', fields: ['address_line','locality','province','postal_code','housing_status','occupation','declared_income_ars'] as const },
  { label: 'Crédito',   fields: ['requested_amount_ars','payment_frequency','requested_installments'] as const },
  { label: 'Documentos',fields: ['doc_dni_front','doc_dni_back','doc_selfie_dni','doc_income_proof'] as const },
  { label: 'Confirmar', fields: ['terms_accepted'] as const },
];

function genUuid(): string {
  if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) return crypto.randomUUID();
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}

const LS_KEY = 'lga-form-draft-v2';

export function MultiStepForm({ initial }: { initial: Partial<ApplicationInput> }) {
  const router = useRouter();
  const [step, setStep] = useState(0);
  const [submitting, setSubmitting] = useState(false);
  const [serverError, setServerError] = useState<string | null>(null);
  // application_code provisorio para etiquetar uploads antes del submit final
  const [applicationCode] = useState(() => `DRAFT-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 6)}`);

  const defaults: Partial<ApplicationInput> = {
    province: 'Tucumán',
    payment_frequency: 'monthly',
    requested_installments: 12,
    marketing_consent: false,
    terms_accepted: undefined as unknown as true,
    idempotency_key: genUuid(),
    requested_amount_ars: initial.cart_total_ars && initial.cart_total_ars > 0 ? initial.cart_total_ars : undefined,
    ...initial,
  };

  const methods = useForm<ApplicationInput>({
    resolver: zodResolver(applicationSchema),
    mode: 'onBlur',
    defaultValues: defaults,
  });

  // Persistir draft en localStorage (sin documentos, son URLs Storage)
  useEffect(() => {
    try {
      const raw = localStorage.getItem(LS_KEY);
      if (raw) {
        const saved = JSON.parse(raw);
        Object.entries(saved).forEach(([k, v]) => {
          if (v != null && !(k in initial && initial[k as keyof typeof initial] != null)) {
            methods.setValue(k as keyof ApplicationInput, v as never);
          }
        });
      }
    } catch {}
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    const sub = methods.watch((data) => {
      try {
        const safe = { ...data };
        // No persistir uploads ni terms (cada sesión re-acepta)
        delete (safe as Record<string, unknown>).doc_dni_front;
        delete (safe as Record<string, unknown>).doc_dni_back;
        delete (safe as Record<string, unknown>).doc_selfie_dni;
        delete (safe as Record<string, unknown>).doc_income_proof;
        delete (safe as Record<string, unknown>).terms_accepted;
        localStorage.setItem(LS_KEY, JSON.stringify(safe));
      } catch {}
    });
    return () => sub.unsubscribe();
  }, [methods]);

  useEffect(() => {
    methods.setValue('landing_url', typeof window !== 'undefined' ? window.location.href : '');
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  async function next() {
    setServerError(null);
    const fields = STEPS[step].fields as readonly (keyof ApplicationInput)[];
    const valid = await methods.trigger(fields as (keyof ApplicationInput)[]);
    if (!valid) return;

    // Validación extra: paso documentos requiere los 4
    if (step === 3) {
      const v = methods.getValues();
      if (!v.doc_dni_front || !v.doc_dni_back || !v.doc_selfie_dni || !v.doc_income_proof) {
        setServerError('Tenés que subir los 4 documentos antes de continuar.');
        return;
      }
    }

    if (step < STEPS.length - 1) {
      setStep(step + 1);
      window.scrollTo({ top: 0, behavior: 'smooth' });
      return;
    }

    // Último paso → submit
    await submit();
  }

  function prev() {
    setServerError(null);
    if (step > 0) {
      setStep(step - 1);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }

  async function submit() {
    setSubmitting(true);
    setServerError(null);
    try {
      const data = methods.getValues();
      const res = await fetch('/api/submit-application', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      });
      const json = await res.json().catch(() => ({}));
      if (!res.ok || !json.ok) {
        setServerError(json.message || 'No pudimos enviar tu solicitud. Reintentá en un minuto.');
        setSubmitting(false);
        return;
      }
      // Limpio draft localStorage
      try { localStorage.removeItem(LS_KEY); } catch {}
      const code = encodeURIComponent(json.application_code || '');
      const zone = encodeURIComponent(json.zone_status || '');
      router.push(`/confirmacion?code=${code}&zone=${zone}`);
    } catch {
      setServerError('Sin conexión. Verificá internet y reintentá.');
      setSubmitting(false);
    }
  }

  const isLast = step === STEPS.length - 1;

  return (
    <FormProvider {...methods}>
      <StepProgress steps={STEPS} current={step} />

      <div className="max-w-3xl mx-auto py-6 space-y-6">
        <StickyProduct
          title={initial.product_title}
          unitPriceArs={initial.unit_price_ars}
          quantity={initial.quantity}
          cartTotalArs={initial.cart_total_ars || 0}
          cartSummary={initial.cart_summary}
          source={initial.source}
        />

        {/* Hidden product fields */}
        <input type="hidden" {...methods.register('shop')} defaultValue={initial.shop} />
        <input type="hidden" {...methods.register('source')} defaultValue={initial.source} />
        <input type="hidden" {...methods.register('product_id')} defaultValue={initial.product_id} />
        <input type="hidden" {...methods.register('variant_id')} defaultValue={initial.variant_id} />
        <input type="hidden" {...methods.register('product_title')} defaultValue={initial.product_title} />
        <input type="hidden" {...methods.register('product_handle')} defaultValue={initial.product_handle} />
        <input type="hidden" {...methods.register('unit_price_ars')} defaultValue={initial.unit_price_ars} />
        <input type="hidden" {...methods.register('quantity')} defaultValue={initial.quantity} />
        <input type="hidden" {...methods.register('cart_token')} defaultValue={initial.cart_token} />
        <input type="hidden" {...methods.register('cart_summary')} defaultValue={initial.cart_summary} />
        <input type="hidden" {...methods.register('cart_total_ars')} defaultValue={initial.cart_total_ars} />
        <input type="hidden" {...methods.register('utm_source')} defaultValue={initial.utm_source} />
        <input type="hidden" {...methods.register('utm_medium')} defaultValue={initial.utm_medium} />
        <input type="hidden" {...methods.register('utm_campaign')} defaultValue={initial.utm_campaign} />
        <input type="hidden" {...methods.register('utm_content')} defaultValue={initial.utm_content} />
        <input type="hidden" {...methods.register('utm_term')} defaultValue={initial.utm_term} />
        <input type="hidden" {...methods.register('referrer_url')} defaultValue={initial.referrer_url} />
        <input type="hidden" {...methods.register('landing_url')} />
        <input type="hidden" {...methods.register('idempotency_key')} />

        {step === 0 && <Step1Identity />}
        {step === 1 && <Step2Address />}
        {step === 2 && <Step3Credit cartTotal={initial.cart_total_ars || 0} />}
        {step === 3 && <Step4Documents applicationCode={applicationCode} />}
        {step === 4 && <Step5Confirm onEdit={(s) => setStep(s)} />}

        {serverError && (
          <p role="alert" className="rounded-md bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700">
            {serverError}
          </p>
        )}
      </div>

      <FormNav
        onPrev={prev}
        onNext={next}
        prevDisabled={step === 0}
        loading={submitting}
        isLastStep={isLast}
      />
    </FormProvider>
  );
}
