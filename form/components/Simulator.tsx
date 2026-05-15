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
    <div className="rounded-xl border-2 border-dashed border-emerald-300 bg-emerald-50/50 p-4 space-y-3">
      <div className="flex items-baseline justify-between">
        <span className="text-xs text-zinc-600 uppercase tracking-wider font-semibold">Cuota estimada</span>
        <span className="text-[10px] text-zinc-500">tasa 8% mensual</span>
      </div>
      <div className="flex items-baseline gap-2">
        <span className="text-3xl font-bold text-lga-primary">{fmtMoney(cuota)}</span>
        <span className="text-sm text-zinc-600">/ por {noun.single}</span>
      </div>
      <div className="grid grid-cols-3 gap-3 text-xs pt-2 border-t border-emerald-200">
        <div>
          <p className="text-zinc-500 uppercase tracking-wider font-semibold">Cuotas</p>
          <p className="text-zinc-900 font-semibold">{installments} {label}</p>
        </div>
        <div>
          <p className="text-zinc-500 uppercase tracking-wider font-semibold">Intereses</p>
          <p className="text-zinc-900 font-semibold">{fmtMoney(interes)}</p>
        </div>
        <div>
          <p className="text-zinc-500 uppercase tracking-wider font-semibold">Total a pagar</p>
          <p className="text-zinc-900 font-semibold">{fmtMoney(total)}</p>
        </div>
      </div>
      <p className="text-[11px] text-zinc-500 leading-snug">
        Es una estimación. El monto y la cuota final los confirma LGA al evaluar tu solicitud.
      </p>
    </div>
  );
}
