import { MultiStepForm } from '@/components/MultiStepForm';
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
    <div className="min-h-[calc(100vh-4rem)] -mx-4 -mt-8">
      <MultiStepForm initial={initial} />
    </div>
  );
}
