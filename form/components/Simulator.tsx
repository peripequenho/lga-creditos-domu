import { calcInstallment, FREQUENCY_NOUN } from '@/lib/schema';

type Props = {
  amount: number;
  installments: number;
  frequency: 'daily' | 'weekly' | 'monthly';
};

function fmtMoney(v: number) {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    maximumFractionDigits: 0,
  }).format(v);
}

export function Simulator({ amount, installments, frequency }: Props) {
  const cuota = calcInstallment(amount, installments, frequency);
  const total = cuota * installments;
  const interes = total - amount;
  const noun = FREQUENCY_NOUN[frequency];
  const label = installments === 1 ? noun.single : noun.plural;

  return (
    <div className="rounded border-2 border-dashed border-border-color bg-surface p-4 space-y-3">
      <div className="flex items-baseline justify-between">
        <span className="text-xs text-fg-secondary uppercase tracking-wider font-semibold">Cuota estimada</span>
        <span className="text-[10px] text-fg-muted">tasa 8% mensual</span>
      </div>
      <div className="flex items-baseline gap-2">
        <span className="text-3xl font-bold text-lga-primary">{fmtMoney(cuota)}</span>
        <span className="text-sm text-fg-secondary">/ por {noun.single}</span>
      </div>
      <div className="grid grid-cols-3 gap-3 text-xs pt-2 border-t border-border-color">
        <div>
          <p className="text-fg-muted uppercase tracking-wider font-semibold">Cuotas</p>
          <p className="text-fg-primary font-semibold">{installments} {label}</p>
        </div>
        <div>
          <p className="text-fg-muted uppercase tracking-wider font-semibold">Intereses</p>
          <p className="text-fg-primary font-semibold">{fmtMoney(interes)}</p>
        </div>
        <div>
          <p className="text-fg-muted uppercase tracking-wider font-semibold">Total a pagar</p>
          <p className="text-fg-primary font-semibold">{fmtMoney(total)}</p>
        </div>
      </div>
      <p className="text-[11px] text-fg-muted leading-snug">
        Es una estimación. El monto y la cuota final los confirma LGA al evaluar tu solicitud.
      </p>
    </div>
  );
}
