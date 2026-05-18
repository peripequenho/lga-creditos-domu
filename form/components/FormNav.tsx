type Props = {
  onPrev?: () => void;
  onNext?: () => void;
  nextLabel?: string;
  nextDisabled?: boolean;
  prevDisabled?: boolean;
  loading?: boolean;
  isLastStep?: boolean;
};

export function FormNav({
  onPrev, onNext, nextLabel, nextDisabled, prevDisabled, loading, isLastStep,
}: Props) {
  return (
    <div className="sticky bottom-0 -mx-4 px-4 py-3 bg-surface/95 border-t border-border-color flex gap-3 items-center">
      <div className="max-w-3xl mx-auto w-full flex gap-3">
        <button
          type="button"
          onClick={onPrev}
          disabled={prevDisabled || loading}
          className="px-4 py-2.5 rounded-md border border-border-color text-fg-primary font-medium hover:bg-surface disabled:opacity-40 disabled:cursor-not-allowed"
        >
          ← Atrás
        </button>
        <button
          type="button"
          onClick={onNext}
          disabled={nextDisabled || loading}
          className={`flex-1 py-2.5 rounded-md font-semibold text-bg-base transition-colors ${
            isLastStep
              ? 'bg-state-ok hover:opacity-90'
              : 'bg-lga-primary hover:bg-lga-primaryHover'
          } disabled:opacity-60 disabled:cursor-not-allowed`}
        >
          {loading ? 'Enviando…' : nextLabel || (isLastStep ? 'Enviar solicitud' : 'Siguiente →')}
        </button>
      </div>
    </div>
  );
}
