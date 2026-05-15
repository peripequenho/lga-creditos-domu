import type { Metadata } from 'next';
import './globals.css';

export const metadata: Metadata = {
  title: 'LGA · Crédito para tu compra en Domu',
  description: 'Solicitá crédito LGA para comprar en Domu. Hasta 24 cuotas, solo Tucumán.',
  robots: { index: false, follow: false }, // no SEO mientras esté en MVP
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="es-AR">
      <body className="min-h-screen bg-white text-zinc-900">
        <header className="border-b border-zinc-200 bg-white">
          <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
            <div className="flex items-center gap-3">
              <div className="h-8 w-8 rounded-md bg-lga-primary" aria-hidden="true" />
              <div>
                <p className="text-sm font-semibold leading-none">LGA</p>
                <p className="text-xs text-zinc-500">Crédito para tu compra en Domu</p>
              </div>
            </div>
            <nav className="text-xs text-zinc-500">
              <a href="https://mem1a9-ev.myshopify.com" className="hover:text-zinc-800">← Volver a Domu</a>
            </nav>
          </div>
        </header>
        <main className="mx-auto max-w-5xl px-4 py-8">{children}</main>
        <footer className="mx-auto mt-10 max-w-5xl px-4 py-6 text-xs text-zinc-400">
          © {new Date().getFullYear()} LGA · Sistema de créditos para Domu
        </footer>
      </body>
    </html>
  );
}
