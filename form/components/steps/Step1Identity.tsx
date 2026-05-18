'use client';

import { useFormContext } from 'react-hook-form';
import type { ApplicationInput } from '@/lib/schema';
import { FieldError } from '../FieldError';

const input = 'w-full rounded-md border border-border-color px-3 py-2.5 text-fg-primary focus:border-lga-primary focus:ring-1 focus:ring-lga-primary outline-none text-base';
const label = 'block text-sm font-medium text-fg-primary mb-1';

export function Step1Identity() {
  const { register, formState: { errors }, watch } = useFormContext<ApplicationInput>();
  const bd = watch('birth_date');
  const age = bd && /^\d{4}-\d{2}-\d{2}$/.test(bd)
    ? Math.floor((Date.now() - new Date(bd).getTime()) / (365.25 * 24 * 3600 * 1000))
    : null;

  return (
    <div className="space-y-5">
      <header>
        <h2 className="text-xl font-semibold text-fg-primary">Tus datos</h2>
        <p className="text-sm text-fg-secondary mt-1">Solo lo necesario para identificarte. Tarda menos de un minuto.</p>
      </header>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label className={label}>Nombre</label>
          <input className={input} autoComplete="given-name" placeholder="Juan" {...register('first_name')} />
          <FieldError message={errors.first_name?.message} />
        </div>
        <div>
          <label className={label}>Apellido</label>
          <input className={input} autoComplete="family-name" placeholder="Pérez" {...register('last_name')} />
          <FieldError message={errors.last_name?.message} />
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label className={label}>DNI</label>
          <input
            className={input}
            inputMode="numeric"
            autoComplete="off"
            placeholder="30123456"
            {...register('dni')}
          />
          <FieldError message={errors.dni?.message} />
        </div>
        <div>
          <label className={label}>
            Fecha de nacimiento {age != null && (
              <span className="text-fg-muted font-normal">· {age} años</span>
            )}
          </label>
          <input className={input} type="date" autoComplete="bday" {...register('birth_date')} />
          <FieldError message={errors.birth_date?.message} />
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label className={label}>Teléfono (WhatsApp)</label>
          <input
            className={input}
            type="tel"
            inputMode="tel"
            autoComplete="tel"
            placeholder="0381 555 1234"
            {...register('phone')}
          />
          <FieldError message={errors.phone?.message} />
          <p className="text-xs text-fg-muted mt-1">Por acá te contactamos.</p>
        </div>
        <div>
          <label className={label}>
            Email <span className="text-fg-muted font-normal">(opcional)</span>
          </label>
          <input className={input} type="email" autoComplete="email" placeholder="juan@email.com" {...register('email')} />
          <FieldError message={errors.email?.message} />
        </div>
      </div>
    </div>
  );
}
