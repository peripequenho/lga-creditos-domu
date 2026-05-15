type Props = {
  steps: { label: string }[];
  current: number; // 0-based
};

export function StepProgress({ steps, current }: Props) {
  return (
    <div className="sticky top-0 z-10 -mx-4 px-4 py-3 bg-white/95 backdrop-blur border-b border-zinc-200">
      <div className="max-w-3xl mx-auto">
        <div className="flex items-center justify-between text-[11px] font-medium uppercase tracking-wider text-zinc-500">
          <span>Paso {current + 1} de {steps.length}</span>
          <span className="text-lga-primary">{steps[current]?.label}</span>
        </div>
        <div className="mt-2 flex gap-1.5">
          {steps.map((_, i) => (
            <div
              key={i}
              className={`h-1.5 flex-1 rounded-full transition-colors ${
                i < current ? 'bg-lga-primary' : i === current ? 'bg-lga-primary' : 'bg-zinc-200'
              }`}
            />
          ))}
        </div>
      </div>
    </div>
  );
}
