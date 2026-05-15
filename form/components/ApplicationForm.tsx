'use client';

import { useForm, FormProvider } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { applicationSchema, INSTALLMENTS_ALLOWED, type ApplicationInput, parseDeepLinkParams } from '@/lib/schema';
import { FieldError } from './FieldError';
import { OrderSummary } from './OrderSummary';

const PROVINCES = ['Tucumán'];

function genIdempotencyKey(): string {
  if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) return crypto.randomUUID();
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}

export function ApplicationForm({ initial }: { initial: Partial<ApplicationInput> }) {
  const router = useRouter();
  const [submitting, setSubmitting] = useState(false);
  const [serverError, setServerError] = useState<string | null>(null);

  const defaultValues: Partial<ApplicationInput> = {
    province: 'Tucumán',
    requested_installments: 12,
    marketing_consent: false,
    terms_accepted: undefined as unknown as true,
    idempotency_key: genIdempotencyKey(),
    requested_amount_ars: initial.cart_total_ars && initial.cart_total_ars > 0 ? initial.cart_total_ars : undefined,
    ...initial,
  };

  const methods = useForm<ApplicationInput>({
    resolver: zodResolver(applicationSchema),
    mode: 'onBlur',
    defaultValues,
  });
  const { register, handleSubmit, formState: { errors }, watch, setValue } = methods;

  // Si cartTotal cambia (cliente edita), seguimos respetando.
  useEffect(() => {
    if (initial.cart_total_ars && !methods.getValues('requested_amount_ars')) {
      setValue('requested_amount_ars', initial.cart_total_ars);
    }
    setValue('landing_url', typeof window !== 'undefined' ? window.location.href : '');
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const onSubmit = async (data: ApplicationInput) => {
    setServerError(null);
    setSubmitting(true);
    try {
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
      const code = encodeURIComponent(json.application_code || '');
      const zone = encodeURIComponent(json.zone_status || '');
      router.push(`/confirmacion?code=${code}&zone=${zone}`);
    } catch (err) {
      setServerError('Sin conexión. Verificá internet y reintentá.');
      setSubmitting(false);
    }
  };

  const labelCls = 'block text-sm font-medium text-zinc-700 mb-1';
  const inputCls = 'w-full rounded-md border border-zinc-300 px-3 py-2 text-zinc-900 shadow-sm focus:border-lga-primary focus:ring-1 focus:ring-lga-primary outline-none';

  return (
    <FormProvider {...methods}>
      <form onSubmit={handleSubmit(onSubmit)} className="grid gap-6 md:grid-cols-3">

        {/* ---- Resumen producto (col derecha en desktop) ---- */}
        <div className="md:col-span-1 md:order-2">
          <OrderSummary
            productTitle={initial.product_title}
            unitPriceArs={initial.unit_price_ars}
            quantity={initial.quantity}
            cartTotalArs={initial.cart_total_ars || 0}
            cartSummary={initial.cart_summary}
            source={initial.source}
          />
        </div>

        {/* ---- Campos visibles ---- */}
        <div className="md:col-span-2 md:order-1 space-y-5">

          <fieldset className="space-y-4 rounded-xl border border-zinc-200 p-4">
            <legend className="px-2 text-sm font-semibold text-zinc-700">Tus datos</legend>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label className={labelCls}>Nombre</label>
                <input className={inputCls} autoComplete="given-name" {...register('first_name')} />
                <FieldError message={errors.first_name?.message} />
              </div>
              <div>
                <label className={labelCls}>Apellido</label>
                <input className={inputCls} autoComplete="family-name" {...register('last_name')} />
                <FieldError message={errors.last_name?.message} />
              </div>
              <div>
                <label className={labelCls}>DNI</label>
                <input className={inputCls} inputMode="numeric" autoComplete="off" placeholder="30123456" {...register('dni')} />
                <FieldError message={errors.dni?.message} />
              </div>
              <div>
                <label className={labelCls}>Fecha de nacimiento</label>
                <input className={inputCls} type="date" autoComplete="bday" {...register('birth_date')} />
                <FieldError message={errors.birth_date?.message} />
              </div>
              <div>
                <label className={labelCls}>Teléfono (WhatsApp)</label>
                <input className={inputCls} type="tel" inputMode="tel" autoComplete="tel" placeholder="0381 555 1234" {...register('phone')} />
                <FieldError message={errors.phone?.message} />
              </div>
              <div>
                <label className={labelCls}>Email <span className="text-zinc-400 font-normal">(opcional)</span></label>
                <input className={inputCls} type="email" autoComplete="email" {...register('email')} />
                <FieldError message={errors.email?.message} />
              </div>
            </div>
          </fieldset>

          <fieldset className="space-y-4 rounded-xl border border-zinc-200 p-4">
            <legend className="px-2 text-sm font-semibold text-zinc-700">Domicilio</legend>
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div className="sm:col-span-2">
                <label className={labelCls}>Dirección</label>
                <input className={inputCls} autoComplete="street-address" placeholder="Av. Mate de Luna 1200" {...register('address_line')} />
                <FieldError message={errors.address_line?.message} />
              </div>
              <div>
                <label className={labelCls}>Localidad</label>
                <input className={inputCls} autoComplete="address-level2" placeholder="San Miguel de Tucumán" {...register('locality')} />
                <FieldError message={errors.locality?.message} />
              </div>
              <div>
                <label className={labelCls}>Provincia</label>
                <select className={inputCls} {...register('province')}>
                  {PROVINCES.map((p) => <option key={p} value={p}>{p}</option>)}
                </select>
                <FieldError message={errors.province?.message} />
              </div>
              <div>
                <label className={labelCls}>Código postal</label>
                <input className={inputCls} autoComplete="postal-code" placeholder="T4000" {...register('postal_code')} />
                <FieldError message={errors.postal_code?.message} />
              </div>
              <div>
                <label className={labelCls}>Ingreso mensual aprox. (AR$)</label>
                <input className={inputCls} type="number" inputMode="numeric" min={0} {...register('declared_income_ars')} />
                <FieldError message={errors.declared_income_ars?.message} />
              </div>
            </div>
          </fieldset>

          <fieldset className="space-y-4 rounded-xl border border-zinc-200 p-4">
            <legend className="px-2 text-sm font-semibold text-zinc-700">Tu solicitud</legend>
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label className={labelCls}>Monto a financiar (AR$)</label>
                <input className={inputCls} type="number" inputMode="numeric" min={50000} max={5000000} step={1000} {...register('requested_amount_ars')} />
                <FieldError message={errors.requested_amount_ars?.message} />
              </div>
              <div>
                <label className={labelCls}>Cantidad de cuotas</label>
                <select className={inputCls} {...register('requested_installments')}>
                  {INSTALLMENTS_ALLOWED.map((n) => <option key={n} value={n}>{n} cuotas</option>)}
                </select>
                <FieldError message={errors.requested_installments?.message} />
              </div>
            </div>
          </fieldset>

          <div className="rounded-xl border border-zinc-200 p-4 space-y-3">
            <label className="flex items-start gap-3 text-sm text-zinc-700">
              <input type="checkbox" className="mt-1 h-4 w-4" {...register('terms_accepted')} />
              <span>Acepto los <a className="underline" href="/terminos" target="_blank" rel="noreferrer">términos y condiciones</a> y autorizo a LGA a contactarme para evaluar mi solicitud.</span>
            </label>
            <FieldError message={errors.terms_accepted?.message as string | undefined} />
            <label className="flex items-start gap-3 text-sm text-zinc-700">
              <input type="checkbox" className="mt-1 h-4 w-4" {...register('marketing_consent')} />
              <span>Quiero recibir promociones y ofertas (opcional).</span>
            </label>
          </div>

          {/* ---- Hidden fields ---- */}
          <input type="hidden" {...register('shop')} />
          <input type="hidden" {...register('source')} />
          <input type="hidden" {...register('product_id')} />
          <input type="hidden" {...register('variant_id')} />
          <input type="hidden" {...register('product_title')} />
          <input type="hidden" {...register('product_handle')} />
          <input type="hidden" {...register('unit_price_ars')} />
          <input type="hidden" {...register('quantity')} />
          <input type="hidden" {...register('cart_token')} />
          <input type="hidden" {...register('cart_summary')} />
          <input type="hidden" {...register('cart_total_ars')} />
          <input type="hidden" {...register('utm_source')} />
          <input type="hidden" {...register('utm_medium')} />
          <input type="hidden" {...register('utm_campaign')} />
          <input type="hidden" {...register('utm_content')} />
          <input type="hidden" {...register('utm_term')} />
          <input type="hidden" {...register('referrer_url')} />
          <input type="hidden" {...register('landing_url')} />
          <input type="hidden" {...register('idempotency_key')} />

          {serverError && (
            <p role="alert" className="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">{serverError}</p>
          )}

          <button
            type="submit"
            disabled={submitting}
            className="w-full rounded-md bg-lga-primary px-4 py-3 text-base font-semibold text-white hover:bg-lga-primaryHover disabled:opacity-60"
          >
            {submitting ? 'Enviando…' : 'Enviar solicitud'}
          </button>
        </div>
      </form>
    </FormProvider>
  );
}
