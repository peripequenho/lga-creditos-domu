type SearchParams = { code?: string; zone?: string };

const WA = process.env.NEXT_PUBLIC_LGA_WHATSAPP || '+5493815551234';

function copyByZone(zone?: string): { title: string; body: string; tone: 'ok' | 'warn' | 'cool' } {
  switch (zone) {
    case 'in_zone':
      return {
        tone: 'ok',
        title: '✅ Recibimos tu solicitud',
        body: `Un asesor de LGA te contacta por WhatsApp al ${WA} en las próximas 24 hs hábiles.`,
      };
    case 'needs_review':
      return {
        tone: 'cool',
        title: '✅ Recibimos tu solicitud',
        body: `Vamos a confirmar si tu zona es operable y te avisamos por WhatsApp al ${WA}.`,
      };
    case 'out_of_zone':
      return {
        tone: 'warn',
        title: '⚠️ Hoy LGA opera solo en Tucumán',
        body: 'Recibimos tus datos y te avisamos cuando lleguemos a tu zona.',
      };
    default:
      return {
        tone: 'cool',
        title: '✅ Recibimos tu solicitud',
        body: `Pronto te contactamos por WhatsApp al ${WA}.`,
      };
  }
}

export default function ConfirmacionPage({ searchParams }: { searchParams: SearchParams }) {
  const code = searchParams.code;
  const zone = searchParams.zone;
  const c = copyByZone(zone);

  const toneCls =
    c.tone === 'ok' ? 'border-emerald-200 bg-emerald-50 text-emerald-900' :
    c.tone === 'warn' ? 'border-amber-200 bg-amber-50 text-amber-900' :
    'border-zinc-200 bg-zinc-50 text-zinc-900';

  return (
    <section className="space-y-4">
      <div className={`rounded-xl border p-6 ${toneCls}`}>
        <h1 className="text-2xl font-semibold">{c.title}</h1>
        {code && (
          <p className="mt-2 text-sm">
            Tu número de solicitud es <strong className="font-mono">{code}</strong>.
          </p>
        )}
        <p className="mt-2">{c.body}</p>
      </div>
      <p className="text-xs text-zinc-500">
        Si no recibís contacto en 24 hs hábiles, escribinos al WhatsApp {WA}.
      </p>
      <p>
        <a href="https://domuhogar.com" className="text-sm text-lga-primary underline">
          ← Volver a Domu
        </a>
      </p>
    </section>
  );
}
