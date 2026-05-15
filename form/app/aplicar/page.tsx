import { ApplicationForm } from '@/components/ApplicationForm';
import { parseDeepLinkParams } from '@/lib/schema';

type SearchParams = Record<string, string | string[] | undefined>;

function flatten(sp: SearchParams): URLSearchParams {
  const u = new URLSearchParams();
  Object.entries(sp).forEach(([k, v]) => {
    if (Array.isArray(v)) v.forEach((x) => u.append(k, x));
    else if (v != null) u.set(k, v);
  });
  return u;
}

export default function AplicarPage({ searchParams }: { searchParams: SearchParams }) {
  const sp = flatten(searchParams);
  const initial = parseDeepLinkParams(sp);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-zinc-900">Solicitá tu crédito LGA</h1>
        <p className="mt-1 text-sm text-zinc-600">
          Completá tus datos para evaluar la solicitud. Solo Tucumán por ahora. No cobramos nada acá:
          un asesor te contacta por WhatsApp en 24 hs hábiles.
        </p>
      </div>

      <ApplicationForm initial={initial} />
    </div>
  );
}
