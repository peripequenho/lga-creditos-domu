import type { Metadata } from 'next';
import Image from 'next/image';
import './globals.css';

export const metadata: Metadata = {
  title: 'Domu · Crédito para tu compra',
  description:
    'Solicitá crédito para tu compra en Domu. Hasta 24 cuotas, solo Tucumán.',
  robots: { index: false, follow: false },
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="es-AR">
      <body className="min-h-screen bg-bg-base text-fg-primary">
        <header className="border-b border-border-color bg-bg-base">
          <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
            <a href="https://domuhogar.com" className="flex items-center gap-3" aria-label="Volver a Domu">
              <Image
                src="/domu-logo.svg"
                alt="Domu"
                width={120}
                height={28}
                priority
                className="h-7 w-auto"
              />
            </a>
            <nav className="text-xs text-fg-muted">
              <a
                href="https://domuhogar.com"
                className="hover:text-accent-green transition-colors"
              >
                ← Volver a Domu
              </a>
            </nav>
          </div>
        </header>
        <main className="mx-auto max-w-5xl px-4 py-8">{children}</main>
        <footer className="mx-auto mt-10 max-w-5xl px-4 py-6 text-xs text-fg-muted">
          © {new Date().getFullYear()} Domu Hogar · Crédito por LGA
        </footer>
      </body>
    </html>
  );
}
