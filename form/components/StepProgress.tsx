type Props = {
  steps: { label: string }[];
  current: number; // 0-based
};

export function StepProgress({ steps, current }: Props) {
  return (
    <div className="sticky top-0 z-10 -mx-4 px-4 py-3 bg-surface/95 border-b border-border-color">
      <div className="max-w-3xl mx-auto">
        <div className="flex items-center justify-between text-[11px] font-medium uppercase tracking-wider text-fg-muted">
          <span>Paso {current + 1} de {steps.length}</span>
          <span className="text-lga-primary">{steps[current]?.label}</span>
        </div>
        <div className="mt-2 flex gap-1.5">
          {steps.map((_, i) => (
            <div
              key={i}
              className={`h-1.5 flex-1 rounded-full transition-colors ${
                i < current ? 'bg-lga-primary' : i === current ? 'bg-lga-primary' : 'bg-surface-raised'
              }`}
            />
          ))}
        </div>
      </div>
    </div>
  );
}
