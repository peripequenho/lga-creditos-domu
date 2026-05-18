import Link from 'next/link';

export default function HomePage() {
  return (
    <section className="prose max-w-none">
      <h1 className="text-2xl font-semibold">Crédito LGA para tu compra en Domu</h1>
      <p className="text-fg-secondary">
        Si llegaste sin pasar por una ficha de producto, abrí tu producto en{' '}
        <a className="underline" href="https://domuhogar.com">Domu</a> y tocá{' '}
        <strong>“Comprar con crédito LGA”</strong>.
      </p>
      <p>
        <Link className="inline-block rounded-md bg-lga-primary px-4 py-2 text-bg-base" href="/aplicar">
          Empezar solicitud manual
        </Link>
      </p>
    </section>
  );
}
