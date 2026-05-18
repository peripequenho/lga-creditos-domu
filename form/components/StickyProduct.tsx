type Props = {
  title?: string;
  unitPriceArs?: number;
  quantity?: number;
  cartTotalArs: number;
  cartSummary?: string;
  source?: string;
};

function fmtMoney(v: number | undefined) {
  if (v == null || !Number.isFinite(v)) return '—';
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    maximumFractionDigits: 0,
  }).format(v);
}

export function StickyProduct({ title, unitPriceArs, quantity, cartTotalArs, cartSummary, source }: Props) {
  const multi = source === 'cart_multi';
  return (
    <aside className="rounded border border-border-color bg-gradient-to-br from-surface to-surface-raised p-3.5 flex items-center gap-3">
      <div className="h-12 w-12 rounded-lg bg-surface-raised flex items-center justify-center text-2xl flex-shrink-0">
        📦
      </div>
      <div className="flex-1 min-w-0">
        <p className="text-[10px] uppercase tracking-wider text-fg-muted font-semibold">Tu compra</p>
        <p className="text-sm font-medium text-fg-primary truncate">
          {multi && cartSummary ? cartSummary : title || 'Producto'}
        </p>
        <p className="text-xs text-fg-muted">
          {quantity && quantity > 1 && !multi ? `${quantity} unidades · ` : ''}
          Total <span className="font-semibold text-lga-primary">{fmtMoney(cartTotalArs)}</span>
        </p>
      </div>
    </aside>
  );
}
