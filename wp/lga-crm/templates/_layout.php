<?php
/**
 * Layout compartido para todos los templates LGA-CRM.
 * Uso: lga_crm_layout_open('Título de la página'); ... contenido ...; lga_crm_layout_close();
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function lga_crm_layout_open( $title = 'LGA · Panel' ) {
    $user = wp_get_current_user();
    $role = lga_crm_current_role();
    $role_label = array(
        'administrator' => 'Admin',
        'vendedor'      => 'Vendedor',
        'cobrador'      => 'Cobrador',
    )[ $role ] ?? $role;

    ?><!DOCTYPE html>
<html lang="es-AR" data-theme="lga">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html( $title ); ?> — LGA</title>

    <!-- Dark mode pre-render (evita flash blanco) -->
    <script>
      (function() {
        try {
          var saved = localStorage.getItem('lga-theme');
          var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
          var dark = saved ? saved === 'dark' : prefersDark;
          if (dark) {
            document.documentElement.classList.add('dark');
            document.documentElement.setAttribute('data-theme', 'lga-dark');
          }
        } catch(e){}
      })();
    </script>

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- DaisyUI (CDN) — componentes drop-in -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />

    <!-- Inter font (igual que shadcn) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind config: extiende paleta con tokens shadcn (HSL) + darkMode class -->
    <script>
      tailwind.config = {
        darkMode: 'class',
        theme: {
          extend: {
            fontFamily: {
              sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
            },
            colors: {
              border:      'hsl(var(--border))',
              input:       'hsl(var(--input))',
              ring:        'hsl(var(--ring))',
              background:  'hsl(var(--background))',
              foreground:  'hsl(var(--foreground))',
              primary: {
                DEFAULT:   'hsl(var(--primary))',
                foreground:'hsl(var(--primary-foreground))',
              },
              secondary: {
                DEFAULT:   'hsl(var(--secondary))',
                foreground:'hsl(var(--secondary-foreground))',
              },
              muted: {
                DEFAULT:   'hsl(var(--muted))',
                foreground:'hsl(var(--muted-foreground))',
              },
              accent: {
                DEFAULT:   'hsl(var(--accent))',
                foreground:'hsl(var(--accent-foreground))',
              },
              destructive: {
                DEFAULT:   'hsl(var(--destructive))',
                foreground:'hsl(var(--destructive-foreground))',
              },
              card: {
                DEFAULT:   'hsl(var(--card))',
                foreground:'hsl(var(--card-foreground))',
              },
            },
            borderRadius: {
              lg: 'var(--radius)',
              md: 'calc(var(--radius) - 2px)',
              sm: 'calc(var(--radius) - 4px)',
            },
          },
        },
      }
    </script>

    <style>
      /* Design tokens shadcn/ui — LIGHT (default) */
      :root {
        --background:           0 0% 100%;
        --foreground:           240 10% 3.9%;
        --card:                 0 0% 100%;
        --card-foreground:      240 10% 3.9%;
        --popover:              0 0% 100%;
        --popover-foreground:   240 10% 3.9%;
        --primary:              173 80% 26%;   /* emerald-700 light */
        --primary-foreground:   0 0% 100%;
        --secondary:            240 4.8% 95.9%;
        --secondary-foreground: 240 5.9% 10%;
        --muted:                240 4.8% 95.9%;
        --muted-foreground:     240 3.8% 46.1%;
        --accent:               240 4.8% 95.9%;
        --accent-foreground:    240 5.9% 10%;
        --destructive:          0 84.2% 60.2%;
        --destructive-foreground: 0 0% 98%;
        --border:               240 5.9% 90%;
        --input:                240 5.9% 90%;
        --ring:                 173 80% 26%;
        --radius:               0.5rem;

        /* Hover/zebra/header bg semantics para que reaccionen en dark */
        --surface:              0 0% 100%;
        --surface-muted:        240 4.8% 95.9%;
      }

      /* Design tokens shadcn/ui — DARK (zinc-950 base + emerald-500 primary) */
      .dark {
        --background:           240 10% 3.9%;   /* zinc-950 */
        --foreground:           0 0% 98%;
        --card:                 240 6% 10%;     /* zinc-900 */
        --card-foreground:      0 0% 98%;
        --popover:              240 6% 10%;
        --popover-foreground:   0 0% 98%;
        --primary:              160 84% 39%;    /* emerald-500 (más claro en dark) */
        --primary-foreground:   0 0% 100%;
        --secondary:            240 3.7% 15.9%;
        --secondary-foreground: 0 0% 98%;
        --muted:                240 3.7% 15.9%;
        --muted-foreground:     240 5% 64.9%;
        --accent:               240 3.7% 15.9%;
        --accent-foreground:    0 0% 98%;
        --destructive:          0 62.8% 50%;
        --destructive-foreground: 0 0% 98%;
        --border:               240 3.7% 18%;
        --input:                240 3.7% 18%;
        --ring:                 160 84% 39%;

        --surface:              240 10% 3.9%;
        --surface-muted:        240 6% 10%;
      }

      body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-feature-settings: 'cv02','cv03','cv04','cv11';
        background: hsl(var(--background));
        color: hsl(var(--foreground));
      }

      /* Utilidades LGA legacy (compat con templates viejos) */
      .lga-link { color: hsl(var(--primary)); font-weight: 500; }
      .lga-link:hover { text-decoration: underline; }

      /* Shadcn-style focus ring */
      *:focus-visible {
        outline: 2px solid hsl(var(--ring));
        outline-offset: 2px;
      }

      /* DaisyUI theme override — LIGHT */
      [data-theme="lga"] {
        --p: 173 80% 26%;
        --pc: 0 0% 100%;
        --b1: 0 0% 100%;
        --b2: 240 4.8% 95.9%;
        --b3: 240 5.9% 90%;
        --bc: 240 10% 3.9%;
      }
      /* DaisyUI theme override — DARK */
      [data-theme="lga-dark"] {
        --p: 160 84% 39%;
        --pc: 0 0% 100%;
        --b1: 240 10% 3.9%;
        --b2: 240 6% 10%;
        --b3: 240 3.7% 18%;
        --bc: 0 0% 98%;
      }

      /* Cards estilo shadcn (sombra sutil, no border duro) */
      .lga-card {
        background: hsl(var(--card));
        color: hsl(var(--card-foreground));
        border: 1px solid hsl(var(--border));
        border-radius: var(--radius);
        box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
      }
      .dark .lga-card {
        box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.35);
      }

      /* ───────────────────────────────────────────────────────────────────
         DARK MODE — overrides para clases Tailwind hardcoded (zinc-*, white).
         Esto evita tener que editar todos los templates uno por uno.
         ─────────────────────────────────────────────────────────────────── */
      .dark .bg-white                { background-color: hsl(var(--card)) !important; }
      .dark .bg-zinc-50              { background-color: hsl(var(--background)) !important; }
      .dark .bg-zinc-100             { background-color: hsl(var(--muted)) !important; }

      .dark .text-zinc-900           { color: hsl(var(--foreground)) !important; }
      .dark .text-zinc-700           { color: hsl(var(--foreground) / 0.9) !important; }
      .dark .text-zinc-600           { color: hsl(var(--muted-foreground)) !important; }
      .dark .text-zinc-500           { color: hsl(var(--muted-foreground)) !important; }
      .dark .text-zinc-400           { color: hsl(var(--muted-foreground) / 0.7) !important; }

      .dark .border-zinc-200         { border-color: hsl(var(--border)) !important; }
      .dark .border-zinc-300         { border-color: hsl(var(--border)) !important; }
      .dark .divide-zinc-100 > :not([hidden]) ~ :not([hidden]) { border-color: hsl(var(--border)) !important; }

      .dark .hover\:bg-zinc-50:hover  { background-color: hsl(var(--muted) / 0.5) !important; }
      .dark .hover\:bg-zinc-100:hover { background-color: hsl(var(--muted)) !important; }
      .dark .hover\:text-zinc-700:hover { color: hsl(var(--foreground)) !important; }
      .dark .hover\:text-zinc-900:hover { color: hsl(var(--foreground)) !important; }

      /* Barra de progreso bg-zinc-100 en dark queda muy clara — usamos muted */
      .dark .bg-zinc-100             { background-color: hsl(var(--muted)) !important; }

      /* Colored badges/tints — en dark hay que abrir un poco la opacidad
         para que se vean sobre fondo zinc-900. Usamos backdrop /10 → /15. */
      .dark .bg-emerald-50           { background-color: hsl(160 84% 39% / 0.12) !important; }
      .dark .bg-blue-50              { background-color: hsl(217 91% 60% / 0.12) !important; }
      .dark .bg-amber-50             { background-color: hsl(38 92% 50% / 0.12) !important; }
      .dark .bg-red-50               { background-color: hsl(0 84% 60% / 0.12) !important; }
      .dark .bg-teal-50              { background-color: hsl(173 80% 40% / 0.12) !important; }

      .dark .text-emerald-700        { color: hsl(160 84% 60%) !important; }
      .dark .text-emerald-800        { color: hsl(160 84% 65%) !important; }
      .dark .text-blue-700           { color: hsl(217 91% 70%) !important; }
      .dark .text-amber-700          { color: hsl(38 92% 65%) !important; }
      .dark .text-red-700            { color: hsl(0 84% 70%) !important; }
      .dark .text-teal-700           { color: hsl(173 80% 60%) !important; }

      .dark .bg-emerald-100          { background-color: hsl(160 84% 39% / 0.2) !important; }
      .dark .bg-emerald-600          { background-color: hsl(160 84% 45%) !important; }
      .dark .bg-emerald-700          { background-color: hsl(160 84% 39%) !important; }
      .dark .hover\:bg-emerald-800:hover { background-color: hsl(160 84% 35%) !important; }

      /* Ring colors en dark más visibles */
      .dark .ring-emerald-700\/10    { --tw-ring-color: hsl(160 84% 39% / 0.25) !important; }
      .dark .ring-blue-700\/10       { --tw-ring-color: hsl(217 91% 60% / 0.25) !important; }
      .dark .ring-amber-700\/10      { --tw-ring-color: hsl(38 92% 50% / 0.25) !important; }
      .dark .ring-red-700\/10        { --tw-ring-color: hsl(0 84% 60% / 0.25) !important; }
      .dark .ring-teal-700\/10       { --tw-ring-color: hsl(173 80% 40% / 0.25) !important; }
      .dark .ring-zinc-600\/10       { --tw-ring-color: hsl(0 0% 100% / 0.1) !important; }

      .dark .bg-zinc-100.text-zinc-700 { /* badges "perdido" / neutro */
        background-color: hsl(0 0% 100% / 0.08) !important;
        color: hsl(0 0% 90%) !important;
      }

      /* Badge estilo shadcn */
      .lga-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.125rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        line-height: 1rem;
      }
      .lga-badge::before {
        content: '';
        width: 6px; height: 6px; border-radius: 9999px;
        background: currentColor; opacity: 0.7;
      }

      /* Tabla estilo shadcn (hover row con ring sutil) */
      .lga-table { width: 100%; font-size: 0.875rem; }
      .lga-table thead { background: hsl(var(--muted)); }
      .lga-table thead th {
        text-align: left; padding: 0.75rem 1rem;
        font-weight: 500; color: hsl(var(--muted-foreground));
        font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;
      }
      .lga-table tbody tr { border-top: 1px solid hsl(var(--border)); }
      .lga-table tbody tr:hover { background: hsl(var(--muted) / 0.5); }
      .lga-table tbody td { padding: 0.75rem 1rem; vertical-align: middle; }

      /* KPI tile estilo Tremor */
      .lga-kpi {
        background: hsl(var(--card));
        border: 1px solid hsl(var(--border));
        border-radius: var(--radius);
        padding: 1.25rem;
      }
      .lga-kpi-label {
        font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;
        color: hsl(var(--muted-foreground)); font-weight: 500;
      }
      .lga-kpi-value {
        margin-top: 0.5rem;
        font-size: 1.875rem; line-height: 2.25rem; font-weight: 700;
        color: hsl(var(--foreground));
        font-variant-numeric: tabular-nums;
      }
    </style>
</head>
<body class="min-h-screen antialiased text-foreground bg-background">
<header class="border-b border-border sticky top-0 z-10 backdrop-blur bg-card/85 supports-[backdrop-filter]:bg-card/70">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between gap-4">
        <div class="flex items-center gap-6">
            <a href="<?php echo esc_url( home_url( '/panel' ) ); ?>" class="flex items-center gap-2.5 text-base font-semibold tracking-tight">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-md bg-emerald-700 dark:bg-emerald-500 text-white text-xs font-bold">LGA</span>
                <span class="text-foreground">Panel</span>
            </a>
            <nav class="hidden sm:flex items-center gap-1 text-sm">
                <?php if ( current_user_can( 'manage_options' ) ): ?>
                    <a href="<?php echo esc_url( home_url( '/panel/admin' ) ); ?>" class="px-3 py-1.5 rounded-md text-muted-foreground hover:bg-muted hover:text-foreground transition-colors">Admin</a>
                <?php endif; ?>
                <?php if ( current_user_can( 'read_lead' ) ): ?>
                    <a href="<?php echo esc_url( home_url( '/panel/vendedor' ) ); ?>" class="px-3 py-1.5 rounded-md text-muted-foreground hover:bg-muted hover:text-foreground transition-colors">Leads</a>
                <?php endif; ?>
                <?php if ( current_user_can( 'read_cliente' ) ): ?>
                    <a href="<?php echo esc_url( home_url( '/panel/cobrador' ) ); ?>" class="px-3 py-1.5 rounded-md text-muted-foreground hover:bg-muted hover:text-foreground transition-colors">Clientes &amp; Créditos</a>
                <?php endif; ?>
            </nav>
        </div>
        <div class="flex items-center gap-3 text-sm">
            <!-- Theme toggle -->
            <button type="button" id="lga-theme-toggle" aria-label="Cambiar tema"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-muted hover:text-foreground transition-colors">
                <svg class="w-4 h-4 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <svg class="w-4 h-4 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
            </button>
            <span class="hidden sm:inline-flex items-center gap-2 text-muted-foreground">
                <span class="font-medium text-foreground"><?php echo esc_html( $user->display_name ); ?></span>
                <span class="lga-badge bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-700/10 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20"><?php echo esc_html( $role_label ); ?></span>
            </span>
            <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="text-muted-foreground hover:text-foreground transition-colors">Salir</a>
        </div>
    </div>
</header>
<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
<?php
}

function lga_crm_layout_close() {
    ?>
</main>
<footer class="mt-12 py-6 text-center text-xs text-muted-foreground">
    LGA · Sistema de créditos · <?php echo esc_html( wp_date( 'Y' ) ); ?>
</footer>
<script>
  // Theme toggle (persistente en localStorage)
  (function() {
    var btn = document.getElementById('lga-theme-toggle');
    if (!btn) return;
    btn.addEventListener('click', function() {
      var html = document.documentElement;
      var isDark = html.classList.toggle('dark');
      html.setAttribute('data-theme', isDark ? 'lga-dark' : 'lga');
      try { localStorage.setItem('lga-theme', isDark ? 'dark' : 'light'); } catch(e){}
    });
  })();
</script>
</body>
</html>
<?php
}

/**
 * Flash messages helper (lee ?msg= y ?err= del query string).
 */
function lga_crm_flash() {
    $msg_codes = array(
        'created'           => array( 'green',  'Creado correctamente.' ),
        'updated'           => array( 'green',  'Actualizado.' ),
        'converted'         => array( 'green',  'Solicitud convertida a lead.' ),
        'promoted'          => array( 'green',  'Lead aprobado — cliente y crédito creados.' ),
        'already_converted' => array( 'yellow', 'Esta solicitud ya estaba convertida (te llevamos al lead existente).' ),
        'existing'          => array( 'yellow', 'Ya existe un cliente con ese DNI.' ),
    );
    $err_codes = array(
        'missing_required' => array( 'red', 'Faltan campos obligatorios.' ),
        'invalid_amounts'  => array( 'red', 'Montos o cuotas inválidos.' ),
        'promote_failed'   => array( 'red', 'No se pudo promover el lead (revisar DNI y campos).' ),
    );

    $msg = sanitize_key( $_GET['msg'] ?? '' );
    $err = sanitize_key( $_GET['err'] ?? '' );
    $new = (int) ( $_GET['new'] ?? 0 );

    $tone_classes = array(
        'green'  => 'bg-emerald-50 text-emerald-800 ring-emerald-700/10 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20',
        'yellow' => 'bg-amber-50 text-amber-800 ring-amber-700/10 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20',
        'red'    => 'bg-red-50 text-red-800 ring-red-700/10 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20',
    );

    if ( $msg && isset( $msg_codes[ $msg ] ) ) {
        list( $color, $text ) = $msg_codes[ $msg ];
        $cls = $tone_classes[ $color ] ?? '';
        $link = '';
        if ( $new > 0 ) {
            $pt = get_post_type( $new );
            if ( $pt ) {
                $url = home_url( '/' . $pt . '/' . $new . '/' );
                $link = ' <a href="' . esc_url( $url ) . '" class="font-semibold underline ml-2">Ver →</a>';
            }
        }
        echo '<div class="mb-6 p-3 rounded-md ring-1 ring-inset ' . esc_attr( $cls ) . '">' . esc_html( $text ) . $link . '</div>';
    }
    if ( $err && isset( $err_codes[ $err ] ) ) {
        list( $color, $text ) = $err_codes[ $err ];
        $cls = $tone_classes[ $color ] ?? '';
        echo '<div class="mb-6 p-3 rounded-md ring-1 ring-inset ' . esc_attr( $cls ) . '">' . esc_html( $text ) . '</div>';
    }
}
