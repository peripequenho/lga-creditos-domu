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
    <div className="sticky bottom-0 -mx-4 px-4 py-3 bg-white/95 backdrop-blur border-t border-zinc-200 flex gap-3 items-center">
      <div className="max-w-3xl mx-auto w-full flex gap-3">
        <button
          type="button"
          onClick={onPrev}
          disabled={prevDisabled || loading}
          className="px-4 py-2.5 rounded-md border border-zinc-300 text-zinc-700 font-medium hover:bg-zinc-50 disabled:opacity-40 disabled:cursor-not-allowed"
        >
          ← Atrás
        </button>
        <button
          type="button"
          onClick={onNext}
          disabled={nextDisabled || loading}
          className={`flex-1 py-2.5 rounded-md font-semibold text-white transition-colors ${
            isLastStep
              ? 'bg-emerald-600 hover:bg-emerald-700'
              : 'bg-lga-primary hover:bg-lga-primaryHover'
          } disabled:opacity-60 disabled:cursor-not-allowed`}
        >
          {loading ? 'Enviando…' : nextLabel || (isLastStep ? 'Enviar solicitud' : 'Siguiente →')}
        </button>
      </div>
    </div>
  );
}
