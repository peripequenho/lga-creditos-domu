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

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- DaisyUI (CDN) — componentes drop-in -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />

    <!-- Inter font (igual que shadcn) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind config: extiende paleta con tokens shadcn (HSL) -->
    <script>
      tailwind.config = {
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
      /* Design tokens shadcn/ui (Default + LGA green primary) */
      :root {
        --background:           0 0% 100%;
        --foreground:           240 10% 3.9%;
        --card:                 0 0% 100%;
        --card-foreground:      240 10% 3.9%;
        --popover:              0 0% 100%;
        --popover-foreground:   240 10% 3.9%;
        --primary:              173 80% 26%;   /* emerald-700 #0F766E */
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
      }

      body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-feature-settings: 'cv02','cv03','cv04','cv11';
      }

      /* Utilidades LGA legacy (compat con templates viejos) */
      .lga-link { color: hsl(var(--primary)); font-weight: 500; }
      .lga-link:hover { text-decoration: underline; }

      /* Shadcn-style focus ring */
      *:focus-visible {
        outline: 2px solid hsl(var(--ring));
        outline-offset: 2px;
      }

      /* DaisyUI theme override → LGA */
      [data-theme="lga"] {
        --p: 173 80% 26%;
        --pc: 0 0% 100%;
        --b1: 0 0% 100%;
        --b2: 240 4.8% 95.9%;
        --b3: 240 5.9% 90%;
        --bc: 240 10% 3.9%;
      }

      /* Cards estilo shadcn (sombra sutil, no border duro) */
      .lga-card {
        background: hsl(var(--card));
        color: hsl(var(--card-foreground));
        border: 1px solid hsl(var(--border));
        border-radius: var(--radius);
        box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
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
<body class="bg-zinc-50 min-h-screen text-zinc-900 antialiased">
<header class="bg-white border-b border-zinc-200 sticky top-0 z-10 backdrop-blur supports-[backdrop-filter]:bg-white/85">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between gap-4">
        <div class="flex items-center gap-6">
            <a href="<?php echo esc_url( home_url( '/panel' ) ); ?>" class="flex items-center gap-2.5 text-base font-semibold tracking-tight">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-md bg-emerald-700 text-white text-xs font-bold">LGA</span>
                <span class="text-zinc-900">Panel</span>
            </a>
            <nav class="hidden sm:flex items-center gap-1 text-sm">
                <?php if ( current_user_can( 'manage_options' ) ): ?>
                    <a href="<?php echo esc_url( home_url( '/panel/admin' ) ); ?>" class="px-3 py-1.5 rounded-md text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 transition-colors">Admin</a>
                <?php endif; ?>
                <?php if ( current_user_can( 'read_lead' ) ): ?>
                    <a href="<?php echo esc_url( home_url( '/panel/vendedor' ) ); ?>" class="px-3 py-1.5 rounded-md text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 transition-colors">Leads</a>
                <?php endif; ?>
                <?php if ( current_user_can( 'read_cliente' ) ): ?>
                    <a href="<?php echo esc_url( home_url( '/panel/cobrador' ) ); ?>" class="px-3 py-1.5 rounded-md text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 transition-colors">Clientes &amp; Créditos</a>
                <?php endif; ?>
            </nav>
        </div>
        <div class="flex items-center gap-3 text-sm">
            <span class="hidden sm:inline-flex items-center gap-2 text-zinc-600">
                <span class="font-medium text-zinc-900"><?php echo esc_html( $user->display_name ); ?></span>
                <span class="lga-badge bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-700/10"><?php echo esc_html( $role_label ); ?></span>
            </span>
            <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="text-zinc-500 hover:text-zinc-900 transition-colors">Salir</a>
        </div>
    </div>
</header>
<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
<?php
}

function lga_crm_layout_close() {
    ?>
</main>
<footer class="mt-12 py-6 text-center text-xs text-zinc-400">
    LGA · Sistema de créditos · <?php echo esc_html( wp_date( 'Y' ) ); ?>
</footer>
</body>
</html>
<?php
}

/**
 * Flash messages helper (lee ?msg= y ?err= del query string).
 */
function lga_crm_flash() {
    $msg_codes = array(
        'created'         => array( 'green',  'Creado correctamente.' ),
        'updated'         => array( 'green',  'Actualizado.' ),
        'converted'       => array( 'green',  'Solicitud convertida en lead.' ),
        'promoted'        => array( 'green',  'Lead promovido a cliente con crédito.' ),
        'already_converted' => array( 'yellow', 'Esta solicitud ya estaba convertida.' ),
        'existing'        => array( 'yellow', 'Ya existe un cliente con ese DNI.' ),
    );
    $err_codes = array(
        'missing_required' => array( 'red', 'Faltan campos obligatorios.' ),
        'invalid_amounts'  => array( 'red', 'Montos o cuotas inválidos.' ),
    );

    $msg = sanitize_key( $_GET['msg'] ?? '' );
    $err = sanitize_key( $_GET['err'] ?? '' );

    if ( $msg && isset( $msg_codes[ $msg ] ) ) {
        list( $color, $text ) = $msg_codes[ $msg ];
        echo '<div class="mb-4 p-3 rounded-md bg-' . esc_attr( $color ) . '-50 text-' . esc_attr( $color ) . '-800 border border-' . esc_attr( $color ) . '-200">' . esc_html( $text ) . '</div>';
    }
    if ( $err && isset( $err_codes[ $err ] ) ) {
        list( $color, $text ) = $err_codes[ $err ];
        echo '<div class="mb-4 p-3 rounded-md bg-' . esc_attr( $color ) . '-50 text-' . esc_attr( $color ) . '-800 border border-' . esc_attr( $color ) . '-200">' . esc_html( $text ) . '</div>';
    }
}
