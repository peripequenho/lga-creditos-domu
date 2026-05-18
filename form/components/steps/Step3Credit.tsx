'use client';

import { useEffect, useMemo } from 'react';
import { useFormContext } from 'react-hook-form';
import type { ApplicationInput } from '@/lib/schema';
import { INSTALLMENTS_BY_FREQUENCY, PAYMENT_FREQUENCY_OPTIONS, FREQUENCY_NOUN, calcInstallment } from '@/lib/schema';
import { FieldError } from '../FieldError';
import { Simulator } from '../Simulator';

const input = 'w-full rounded-md border border-border-color px-3 py-2.5 text-fg-primary focus:border-lga-primary focus:ring-1 focus:ring-lga-primary outline-none text-base';
const label = 'block text-sm font-medium text-fg-primary mb-1';

export function Step3Credit({ cartTotal }: { cartTotal: number }) {
  const { register, formState: { errors }, setValue, watch } = useFormContext<ApplicationInput>();

  const amount = watch('requested_amount_ars') || cartTotal;
  const freq = watch('payment_frequency') || 'monthly';
  const installments = watch('requested_installments') || INSTALLMENTS_BY_FREQUENCY[freq][2];

  const installmentsOptions = INSTALLMENTS_BY_FREQUENCY[freq];
  const noun = FREQUENCY_NOUN[freq];

  // Recalcular cuota estimada cuando cambia algo
  useEffect(() => {
    const cuota = calcInstallment(amount, installments, freq);
    setValue('estimated_installment_ars', cuota);
  }, [amount, installments, freq, setValue]);

  // Si el plazo de la frecuencia anterior no existe en la nueva, ajustar
  useEffect(() => {
    if (!installmentsOptions.includes(installments as never)) {
      setValue('requested_installments', installmentsOptions[2] as number);
    }
  }, [freq, installments, installmentsOptions, setValue]);

  const formattedAmount = useMemo(
    () => new Intl.NumberFormat('es-AR').format(amount || 0),
    [amount],
  );

  return (
    <div className="space-y-6">
      <header>
        <h2 className="text-xl font-semibold text-fg-primary">Tu crédito</h2>
        <p className="text-sm text-fg-secondary mt-1">Decidí cómo querés pagar. Podés simular la cuota ahora.</p>
      </header>

      {/* Monto */}
      <div className="rounded border border-border-color p-4 space-y-3">
        <div className="flex items-baseline justify-between">
          <label className={label + ' mb-0'}>Monto a financiar</label>
          <span className="text-xs text-fg-muted">Máximo: {new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 }).format(cartTotal)}</span>
        </div>
        <div className="relative">
          <span className="absolute left-3 top-1/2 -translate-y-1/2 text-fg-muted font-medium">$</span>
          <input
            className={input + ' pl-7 text-2xl font-semibold'}
            inputMode="numeric"
            value={formattedAmount}
            onChange={(e) => {
              const digits = e.target.value.replace(/\D/g, '');
              const n = digits ? parseInt(digits, 10) : 0;
              setValue('requested_amount_ars', Math.min(n, cartTotal), { shouldValidate: true });
            }}
          />
        </div>
        <FieldError message={errors.requested_amount_ars?.message} />
        <input
          type="range"
          min={1_000}
          max={Math.max(cartTotal, 1_000)}
          step={1_000}
          value={amount || 0}
          onChange={(e) => setValue('requested_amount_ars', parseInt(e.target.value, 10), { shouldValidate: true })}
          className="w-full accent-lga-primary"
        />
        <div className="flex justify-between text-[10px] text-fg-muted">
          <span>$1.000</span>
          <span>{new Intl.NumberFormat('es-AR').format(cartTotal)}</span>
        </div>
      </div>

      {/* Frecuencia */}
      <div className="space-y-2">
        <label className={label}>¿Cómo querés pagar?</label>
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-2">
          {PAYMENT_FREQUENCY_OPTIONS.map((o) => (
            <label
              key={o.value}
              className={`relative cursor-pointer rounded border-2 p-3 transition-all ${
                freq === o.value
                  ? 'border-lga-primary bg-surface'
                  : 'border-border-color hover:border-border-color'
              }`}
            >
              <input
                type="radio"
                value={o.value}
                {...register('payment_frequency')}
                className="sr-only"
              />
              <div className="text-sm font-semibold text-fg-primary">{o.label}</div>
              <div className="text-xs text-fg-muted mt-0.5">{o.description}</div>
              {freq === o.value && (
                <div className="absolute top-2 right-2 text-lga-primary">✓</div>
              )}
            </label>
          ))}
        </div>
        <FieldError message={errors.payment_frequency?.message} />
      </div>

      {/* Cuotas */}
      <div>
        <label className={label}>Cantidad de {noun.plural} a pagar</label>
        <div className="grid grid-cols-3 sm:grid-cols-6 gap-2">
          {installmentsOptions.map((n) => (
            <button
              key={n}
              type="button"
              onClick={() => setValue('requested_installments', n, { shouldValidate: true })}
              className={`py-2 px-2 rounded-md border-2 font-semibold text-sm transition-colors ${
                installments === n
                  ? 'border-lga-primary bg-lga-primary text-bg-base'
                  : 'border-border-color text-fg-primary hover:border-border-color'
              }`}
            >
              {n}
            </button>
          ))}
        </div>
        <FieldError message={errors.requested_installments?.message} />
      </div>

      {/* Simulador */}
      <Simulator amount={amount} installments={installments} frequency={freq} />
    </div>
  );
}
