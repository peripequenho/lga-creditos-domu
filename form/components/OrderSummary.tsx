type Props = {
  productTitle?: string;
  unitPriceArs?: number;
  quantity?: number;
  cartTotalArs: number;
  cartSummary?: string;
  source?: string;
};

function fmtMoney(v: number | undefined) {
  if (v == null || !Number.isFinite(v)) return '—';
  return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 }).format(v);
}

export function OrderSummary({ productTitle, unitPriceArs, quantity, cartTotalArs, cartSummary, source }: Props) {
  const isCartMulti = source === 'cart_multi';
  return (
    <aside className="rounded-xl border border-zinc-200 bg-zinc-50 p-4">
      <h2 className="text-sm font-semibold uppercase tracking-wide text-zinc-600">Tu pedido</h2>

      {isCartMulti && cartSummary ? (
        <p className="mt-2 text-sm text-zinc-700">{cartSummary}</p>
      ) : (
        <div className="mt-2">
          <p className="text-base font-medium text-zinc-900">{productTitle || 'Producto'}</p>
          <p className="text-sm text-zinc-600">
            {fmtMoney(unitPriceArs)} {quantity && quantity > 1 ? `× ${quantity}` : ''}
          </p>
        </div>
      )}

      <hr className="my-3 border-zinc-200" />
      <div className="flex items-baseline justify-between">
        <span className="text-sm text-zinc-600">Total</span>
        <span className="text-xl font-semibold text-zinc-900">{fmtMoney(cartTotalArs)}</span>
      </div>
      <p className="mt-2 text-xs text-zinc-500">
        Solicitás financiar este monto con LGA. La aprobación final depende del análisis crediticio.
      </p>
    </aside>
  );
}
