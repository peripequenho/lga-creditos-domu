'use client';

import { useState } from 'react';
import { useFormContext } from 'react-hook-form';
import type { ApplicationInput } from '@/lib/schema';
import { HOUSING_OPTIONS, OCCUPATION_OPTIONS } from '@/lib/schema';
import { FieldError } from '../FieldError';

const input = 'w-full rounded-md border border-border-color px-3 py-2.5 text-fg-primary focus:border-lga-primary focus:ring-1 focus:ring-lga-primary outline-none text-base';
const label = 'block text-sm font-medium text-fg-primary mb-1';

function fmtMoneyInput(v: string): string {
  const digits = v.replace(/\D/g, '');
  if (!digits) return '';
  return new Intl.NumberFormat('es-AR').format(parseInt(digits, 10));
}

export function Step2Address() {
  const { register, formState: { errors }, setValue, watch } = useFormContext<ApplicationInput>();
  const [garante, setGarante] = useState(false);
  const income = watch('declared_income_ars');
  const incomeFormatted = income ? new Intl.NumberFormat('es-AR').format(income) : '';

  return (
    <div className="space-y-5">
      <header>
        <h2 className="text-xl font-semibold text-fg-primary">Domicilio y situación</h2>
        <p className="text-sm text-fg-secondary mt-1">Dónde vivís y qué hacés. Lo usamos para evaluar tu solicitud.</p>
      </header>

      <fieldset className="space-y-4 rounded border border-border-color p-4">
        <legend className="px-2 text-xs font-semibold text-fg-muted uppercase tracking-wider">Dirección</legend>

        <div>
          <label className={label}>Calle y número</label>
          <input
            className={input}
            autoComplete="street-address"
            placeholder="Av. Mate de Luna 1200"
            {...register('address_line')}
          />
          <FieldError message={errors.address_line?.message} />
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label className={label}>Localidad</label>
            <input
              className={input}
              autoComplete="address-level2"
              placeholder="San Miguel de Tucumán"
              {...register('locality')}
            />
            <FieldError message={errors.locality?.message} />
          </div>
          <div>
            <label className={label}>Código postal</label>
            <input
              className={input}
              autoComplete="postal-code"
              placeholder="T4000"
              {...register('postal_code')}
            />
            <FieldError message={errors.postal_code?.message} />
          </div>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label className={label}>Provincia</label>
            <input className={input} value="Tucumán" readOnly {...register('province')} />
          </div>
          <div>
            <label className={label}>Tipo de vivienda</label>
            <select className={input} {...register('housing_status')}>
              <option value="">Elegí…</option>
              {HOUSING_OPTIONS.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
            </select>
            <FieldError message={errors.housing_status?.message} />
          </div>
        </div>
      </fieldset>

      <fieldset className="space-y-4 rounded border border-border-color p-4">
        <legend className="px-2 text-xs font-semibold text-fg-muted uppercase tracking-wider">Ocupación e ingresos</legend>

        <div>
          <label className={label}>¿A qué te dedicás?</label>
          <select className={input} {...register('occupation')}>
            <option value="">Elegí…</option>
            {OCCUPATION_OPTIONS.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
          </select>
          <FieldError message={errors.occupation?.message} />
        </div>

        <div>
          <label className={label}>
            Detalle de la actividad <span className="text-fg-muted font-normal">(opcional)</span>
          </label>
          <input
            className={input}
            placeholder="Ej: Empleado en supermercado / Pinto casas / etc."
            {...register('occupation_detail')}
          />
          <FieldError message={errors.occupation_detail?.message} />
        </div>

        <div>
          <label className={label}>Ingreso mensual aproximado (AR$)</label>
          <div className="relative">
            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-fg-muted">$</span>
            <input
              className={input + ' pl-7'}
              inputMode="numeric"
              value={incomeFormatted}
              onChange={(e) => {
                const digits = e.target.value.replace(/\D/g, '');
                setValue('declared_income_ars', digits ? parseInt(digits, 10) : 0, { shouldValidate: true });
              }}
              placeholder="450.000"
            />
          </div>
          <FieldError message={errors.declared_income_ars?.message} />
          <p className="text-xs text-fg-muted mt-1">Es lo que entra a casa entre todos los ingresos. Estimado, no hace falta exacto.</p>
        </div>
      </fieldset>

      {/* Garante opcional, colapsable */}
      <fieldset className="rounded border border-border-color p-4">
        <button
          type="button"
          onClick={() => setGarante(!garante)}
          className="w-full flex items-center justify-between text-sm font-medium text-fg-primary"
        >
          <span>
            Garante o referencia personal <span className="text-fg-muted font-normal">(opcional)</span>
          </span>
          <span className="text-fg-muted">{garante ? '−' : '+'}</span>
        </button>

        {garante && (
          <div className="mt-4 space-y-4">
            <p className="text-xs text-fg-muted -mt-1">
              Si tenés alguien que puede dar referencias tuyas (familiar, empleador, conocido), cargá sus datos. Acelera la aprobación.
            </p>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className={label}>Nombre completo</label>
                <input className={input} placeholder="María González" {...register('guarantor_name')} />
              </div>
              <div>
                <label className={label}>Teléfono</label>
                <input className={input} type="tel" inputMode="tel" placeholder="0381 555 5678" {...register('guarantor_phone')} />
              </div>
            </div>
            <div>
              <label className={label}>Relación</label>
              <input className={input} placeholder="Hermana / Vecino / Jefe / etc." {...register('guarantor_relation')} />
            </div>
          </div>
        )}
      </fieldset>
    </div>
  );
}
