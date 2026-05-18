import type { Metadata } from 'next';
import { GeistSans } from 'geist/font/sans';
import { GeistMono } from 'geist/font/mono';
import './globals.css';

export const metadata: Metadata = {
  title: 'LGA · Crédito para tu compra en Domu',
  description: 'Solicitá crédito LGA para comprar en Domu. Hasta 24 cuotas, solo Tucumán.',
  robots: { index: false, follow: false },
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html
      lang="es-AR"
      className={`${GeistSans.variable} ${GeistMono.variable} dark`}
    >
      <body className="min-h-screen bg-bg-base text-fg-primary">
        <header className="border-b border-border-color bg-bg-secondary">
          <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
            <div className="flex items-center gap-3">
              <div
                className="h-8 w-8 rounded-sm border border-border-color bg-accent-bone"
                aria-hidden="true"
              />
              <div>
                <p className="text-sm font-semibold leading-none text-fg-primary">LGA</p>
                <p className="text-xs text-fg-muted">Crédito para tu compra en Domu</p>
              </div>
            </div>
            <nav className="text-xs text-fg-muted">
              <a href="https://domuhogar.com" className="hover:text-fg-primary transition-colors">
                ← Volver a Domu
              </a>
            </nav>
          </div>
        </header>
        <main className="mx-auto max-w-5xl px-4 py-8">{children}</main>
        <footer className="mx-auto mt-10 max-w-5xl px-4 py-6 text-xs text-fg-muted">
          © {new Date().getFullYear()} LGA · Sistema de créditos para Domu
        </footer>
      </body>
    </html>
  );
}
