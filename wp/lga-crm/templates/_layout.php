<?php
/**
 * Layout compartido para todos los templates LGA-CRM.
 * Uso: lga_crm_layout_open('Título de la página'); ... contenido ...; lga_crm_layout_close();
 *
 * v0.3.11 — Migrado al LGA Internal Command System.
 * Tokens OKLCH (graphite + bone + steel + olive), Geist Sans + Geist Mono,
 * dark-only, sin shadows ni glass. Tailwind CDN se mantiene para utility
 * classes; las clases hardcoded (emerald-*, zinc-*) se remapean via CSS
 * override a los tokens DS.
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
<html lang="es-AR" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html( $title ); ?> — LGA</title>

    <!-- Geist Sans + Geist Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">

    <!-- Tailwind CDN (utility classes only — tokens overridden below) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Tailwind config: bridge a tokens DS para los utility colors heredados -->
    <script>
      tailwind.config = {
        darkMode: 'class',
        theme: {
          extend: {
            fontFamily: {
              sans: ['Geist', 'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
              mono: ['Geist Mono', 'ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
            },
            colors: {
              // shadcn bridge → OKLCH tokens del DS
              border:      'var(--border-color)',
              input:       'var(--border-color)',
              ring:        'var(--accent-steel)',
              background:  'var(--bg-base)',
              foreground:  'var(--fg-primary)',
              primary: {
                DEFAULT:   'var(--accent-bone)',
                foreground:'var(--bg-base)',
              },
              secondary: {
                DEFAULT:   'var(--surface-raised)',
                foreground:'var(--fg-primary)',
              },
              muted: {
                DEFAULT:   'var(--surface)',
                foreground:'var(--fg-muted)',
              },
              accent: {
                DEFAULT:   'var(--surface-raised)',
                foreground:'var(--fg-primary)',
              },
              destructive: {
                DEFAULT:   'var(--state-risk)',
                foreground:'var(--fg-primary)',
              },
              card: {
                DEFAULT:   'var(--surface)',
                foreground:'var(--fg-primary)',
              },
            },
            borderRadius: {
              lg: '4px',
              md: '3px',
              sm: '2px',
            },
          },
        },
      }
    </script>

    <style>
      /* ============================================================
         LGA Internal Command System — Design tokens
         Restrained · institutional · command-center aesthetic
         No gold. No teal-fintech. No glassmorphism.
         ============================================================ */
      :root, .dark {
        /* Surfaces (5) — graphite ramp */
        --bg-base:        oklch(0.135 0.005 240);
        --bg-secondary:   oklch(0.165 0.006 240);
        --surface:        oklch(0.205 0.006 240);
        --surface-raised: oklch(0.235 0.006 240);
        --border-color:   oklch(0.295 0.007 240);

        /* Foreground (3) — bone/warm gray */
        --fg-primary:     oklch(0.945 0.012 85);
        --fg-secondary:   oklch(0.745 0.013 85);
        --fg-muted:       oklch(0.555 0.012 240);

        /* Accents (3) — restrained, technical */
        --accent-bone:    oklch(0.910 0.014 85);
        --accent-steel:   oklch(0.520 0.022 245);
        --accent-olive:   oklch(0.450 0.020 120);

        /* States (5) — functional only */
        --state-ok:       oklch(0.555 0.094 145);
        --state-warn:     oklch(0.640 0.115 70);
        --state-risk:     oklch(0.510 0.135 25);
        --state-crit:     oklch(0.385 0.130 25);
        --state-info:     oklch(0.520 0.022 245);

        --radius: 4px;
      }

      /* ============================================================
         Base
         ============================================================ */
      html, body {
        background: var(--bg-base);
        color: var(--fg-primary);
        font-family: 'Geist', system-ui, sans-serif;
        font-size: 13px;
        line-height: 1.45;
        -webkit-font-smoothing: antialiased;
        text-rendering: optimizeLegibility;
        font-feature-settings: "ss01","cv11";
      }
      h1, h2, h3, h4, h5, h6 {
        font-family: 'Geist', system-ui, sans-serif;
        letter-spacing: -0.01em;
      }
      .num, [data-num], .tabular-nums {
        font-family: 'Geist Mono', ui-monospace, monospace;
        font-variant-numeric: tabular-nums;
      }
      .eyebrow {
        font-family: 'Geist Mono', ui-monospace, monospace;
        font-size: 11px;
        line-height: 1;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--fg-secondary);
      }
      *:focus-visible {
        outline: 1px solid var(--accent-steel);
        outline-offset: 1px;
      }

      /* ============================================================
         Helper classes (LGA-specific, used by templates)
         ============================================================ */
      .lga-link {
        color: var(--accent-bone);
        text-decoration: none;
        transition: color 150ms cubic-bezier(0.2,0,0.2,1);
      }
      .lga-link:hover { color: var(--fg-primary); text-decoration: underline; text-underline-offset: 3px; }

      .lga-card {
        background: var(--surface);
        color: var(--fg-primary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
      }

      .lga-badge {
        display: inline-flex;
        align-items: center;
        height: 18px;
        padding: 0 6px;
        font-family: 'Geist Mono', ui-monospace, monospace;
        font-size: 11px;
        line-height: 1;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border: 1px solid var(--border-color);
        border-radius: 2px;
        background: var(--surface-raised);
        color: var(--fg-secondary);
        white-space: nowrap;
      }
      .lga-badge::before { content: none; }

      .lga-table { width: 100%; font-size: 13px; border-collapse: collapse; }
      .lga-table thead { background: var(--bg-secondary); }
      .lga-table thead th {
        text-align: left;
        padding: 0 12px;
        height: 32px;
        font-family: 'Geist Mono', ui-monospace, monospace;
        font-weight: 500;
        color: var(--fg-secondary);
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
      }
      .lga-table tbody tr { border-top: 1px solid color-mix(in oklch, var(--border-color) 60%, transparent); }
      .lga-table tbody tr:hover td { background: var(--surface-raised); }
      .lga-table tbody td {
        padding: 0 12px;
        height: 36px;
        vertical-align: middle;
        color: var(--fg-primary);
        white-space: nowrap;
      }

      .lga-kpi {
        background: var(--surface);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 8px;
      }
      .lga-kpi-label {
        font-family: 'Geist Mono', ui-monospace, monospace;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--fg-muted);
      }
      .lga-kpi-value {
        font-family: 'Geist Mono', ui-monospace, monospace;
        font-variant-numeric: tabular-nums;
        font-size: 24px;
        line-height: 1;
        color: var(--fg-primary);
      }

      /* ============================================================
         Tailwind utility overrides — remap clases hardcoded
         (emerald-*, zinc-*) a tokens LGA. Esto evita reescribir todos
         los templates uno por uno.
         ============================================================ */

      /* Backgrounds: emerald → bone primary */
      .bg-emerald-50  { background-color: color-mix(in oklch, var(--accent-bone) 12%, transparent) !important; }
      .bg-emerald-100 { background-color: color-mix(in oklch, var(--accent-bone) 20%, transparent) !important; }
      .bg-emerald-600,
      .bg-emerald-700,
      .bg-emerald-800 { background-color: var(--accent-bone) !important; color: var(--bg-base) !important; }
      .hover\:bg-emerald-800:hover,
      .hover\:bg-emerald-700:hover { background-color: var(--fg-primary) !important; }

      /* Text on emerald */
      .text-emerald-700,
      .text-emerald-800 { color: var(--accent-bone) !important; }
      .hover\:text-emerald-800:hover,
      .hover\:text-emerald-700:hover { color: var(--fg-primary) !important; }
      .text-emerald-400 { color: var(--accent-bone) !important; }

      /* Rings */
      .ring-emerald-700\/10,
      .ring-emerald-700\/20,
      .ring-emerald-500\/20 { --tw-ring-color: var(--accent-bone) !important; }

      /* Zinc / neutrals → graphite ramp */
      .bg-white,
      .bg-card,
      .bg-zinc-50  { background-color: var(--surface) !important; }
      .bg-zinc-100 { background-color: var(--surface-raised) !important; }

      .text-zinc-900, .text-foreground { color: var(--fg-primary) !important; }
      .text-zinc-700                   { color: color-mix(in oklch, var(--fg-primary) 90%, transparent) !important; }
      .text-zinc-600                   { color: var(--fg-secondary) !important; }
      .text-zinc-500                   { color: var(--fg-secondary) !important; }
      .text-zinc-400                   { color: var(--fg-muted) !important; }

      .border-zinc-200, .border-zinc-300, .border-border { border-color: var(--border-color) !important; }
      .divide-zinc-100 > :not([hidden]) ~ :not([hidden]) { border-color: var(--border-color) !important; }

      .hover\:bg-zinc-50:hover  { background-color: color-mix(in oklch, var(--surface-raised) 80%, transparent) !important; }
      .hover\:bg-zinc-100:hover { background-color: var(--surface-raised) !important; }
      .hover\:text-zinc-700:hover,
      .hover\:text-zinc-900:hover { color: var(--fg-primary) !important; }

      /* Colored tints (badge backgrounds) → muted state tints */
      .bg-blue-50    { background-color: color-mix(in oklch, var(--accent-steel) 18%, transparent) !important; }
      .text-blue-700 { color: var(--accent-steel) !important; }
      .ring-blue-700\/10 { --tw-ring-color: var(--accent-steel) !important; }

      .bg-amber-50   { background-color: color-mix(in oklch, var(--state-warn) 18%, transparent) !important; }
      .text-amber-700 { color: var(--state-warn) !important; }
      .text-amber-800 { color: var(--state-warn) !important; }
      .ring-amber-700\/10 { --tw-ring-color: var(--state-warn) !important; }

      .bg-red-50    { background-color: color-mix(in oklch, var(--state-risk) 14%, transparent) !important; }
      .text-red-700 { color: var(--state-risk) !important; }
      .text-red-600 { color: var(--state-risk) !important; }
      .text-red-800 { color: var(--state-risk) !important; }
      .ring-red-700\/10 { --tw-ring-color: var(--state-risk) !important; }
      .border-red-200 { border-color: color-mix(in oklch, var(--state-risk) 50%, transparent) !important; }

      .bg-teal-50    { background-color: color-mix(in oklch, var(--state-ok) 14%, transparent) !important; }
      .text-teal-700 { color: var(--state-ok) !important; }
      .ring-teal-700\/10 { --tw-ring-color: var(--state-ok) !important; }

      .ring-zinc-600\/10 { --tw-ring-color: var(--border-color) !important; }

      /* Borders verdes (lga aprobado) */
      .border-emerald-300 { border-color: color-mix(in oklch, var(--state-ok) 50%, transparent) !important; }

      /* Form inputs */
      input[type="text"],
      input[type="email"],
      input[type="tel"],
      input[type="number"],
      input[type="date"],
      input[type="password"],
      select,
      textarea {
        background: var(--bg-secondary) !important;
        color: var(--fg-primary) !important;
        border: 1px solid var(--border-color) !important;
        border-radius: var(--radius) !important;
        font-family: 'Geist', system-ui, sans-serif;
      }
      input:focus, select:focus, textarea:focus {
        outline: none;
        border-color: var(--accent-steel) !important;
        box-shadow: 0 0 0 1px var(--accent-steel);
      }
      input[type="number"], input[inputmode="numeric"], input[inputmode="tel"] {
        font-family: 'Geist Mono', ui-monospace, monospace !important;
        font-variant-numeric: tabular-nums;
      }

      /* Buttons emerald → primary bone */
      button[type="submit"], .lga-btn-primary {
        font-family: inherit;
        cursor: pointer;
      }

      /* Progress bars */
      .bg-emerald-600 { background: var(--accent-bone) !important; }

      /* Pre / code blocks (notas internas) */
      pre { font-family: 'Geist Mono', ui-monospace, monospace; color: var(--fg-secondary); }

      /* Mono utilities */
      .font-mono, .font-mono * { font-family: 'Geist Mono', ui-monospace, monospace; }
      .tabular-nums { font-variant-numeric: tabular-nums; }
    </style>
</head>
<body class="min-h-screen antialiased">
<header style="border-bottom: 1px solid var(--border-color); background: var(--bg-base); position: sticky; top: 0; z-index: 10;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6" style="height: 56px; display: flex; align-items: center; justify-content: space-between; gap: 24px;">
        <div style="display: flex; align-items: center; gap: 28px;">
            <a href="<?php echo esc_url( home_url( '/panel' ) ); ?>" style="display: flex; align-items: baseline; gap: 10px; text-decoration: none;">
                <span style="font-family: 'Geist Mono', ui-monospace, monospace; font-size: 16px; font-weight: 600; letter-spacing: -0.01em; color: var(--accent-bone);">LGA</span>
                <span class="eyebrow" style="color: var(--fg-muted);">Internal · Panel</span>
            </a>
            <nav class="hidden sm:flex items-center" style="gap: 4px;">
                <?php if ( current_user_can( 'manage_options' ) ): ?>
                    <a href="<?php echo esc_url( home_url( '/panel/admin' ) ); ?>" class="lga-nav-link">Admin</a>
                <?php endif; ?>
                <?php if ( current_user_can( 'read_lead' ) ): ?>
                    <a href="<?php echo esc_url( home_url( '/panel/vendedor' ) ); ?>" class="lga-nav-link">Leads</a>
                <?php endif; ?>
                <?php if ( current_user_can( 'read_cliente' ) ): ?>
                    <a href="<?php echo esc_url( home_url( '/panel/cobrador' ) ); ?>" class="lga-nav-link">Clientes &amp; Créditos</a>
                <?php endif; ?>
            </nav>
        </div>
        <div style="display: flex; align-items: center; gap: 16px;">
            <span class="hidden sm:inline-flex items-center" style="gap: 10px;">
                <span style="font-size: 12px; color: var(--fg-secondary);"><?php echo esc_html( $user->display_name ); ?></span>
                <span class="lga-badge"><?php echo esc_html( $role_label ); ?></span>
            </span>
            <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" style="font-size: 12px; color: var(--fg-secondary); text-decoration: none;" onmouseover="this.style.color='var(--fg-primary)'" onmouseout="this.style.color='var(--fg-secondary)'">Salir</a>
        </div>
    </div>
</header>
<style>
  .lga-nav-link {
    padding: 6px 10px;
    font-family: 'Geist Mono', ui-monospace, monospace;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--fg-secondary);
    border-radius: 3px;
    text-decoration: none;
    transition: background 150ms cubic-bezier(0.2,0,0.2,1), color 150ms cubic-bezier(0.2,0,0.2,1);
  }
  .lga-nav-link:hover { background: var(--surface); color: var(--fg-primary); }
</style>
<main class="max-w-7xl mx-auto px-4 sm:px-6" style="padding-top: 32px; padding-bottom: 64px;">
<?php
}

function lga_crm_layout_close() {
    ?>
</main>
<footer style="margin-top: 48px; padding: 24px 0; text-align: center; font-family: 'Geist Mono', ui-monospace, monospace; font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--fg-muted); border-top: 1px solid var(--border-color);">
    LGA · Internal Command System · <?php echo esc_html( wp_date( 'Y' ) ); ?>
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
        'created'           => array( 'ok',   'Creado correctamente.' ),
        'updated'           => array( 'ok',   'Actualizado.' ),
        'converted'         => array( 'ok',   'Solicitud convertida a lead.' ),
        'promoted'          => array( 'ok',   'Lead aprobado — cliente y crédito creados.' ),
        'already_converted' => array( 'warn', 'Esta solicitud ya estaba convertida (te llevamos al lead existente).' ),
        'existing'          => array( 'warn', 'Ya existe un cliente con ese DNI.' ),
    );
    $err_codes = array(
        'missing_required' => array( 'risk', 'Faltan campos obligatorios.' ),
        'invalid_amounts'  => array( 'risk', 'Montos o cuotas inválidos.' ),
        'promote_failed'   => array( 'risk', 'No se pudo promover el lead (revisar DNI y campos).' ),
    );

    $msg = sanitize_key( $_GET['msg'] ?? '' );
    $err = sanitize_key( $_GET['err'] ?? '' );
    $new = (int) ( $_GET['new'] ?? 0 );

    $tones = array(
        'ok'   => array( 'state-ok',   'OK' ),
        'warn' => array( 'state-warn', 'WARN' ),
        'risk' => array( 'state-risk', 'ERROR' ),
    );

    $render = function( $tone, $text, $extra = '' ) use ( $tones ) {
        list( $token, $label ) = $tones[ $tone ];
        echo '<div style="margin-bottom: 20px; padding: 12px 16px; border: 1px solid color-mix(in oklch, var(--' . esc_attr( $token ) . ') 50%, transparent); border-radius: var(--radius); background: color-mix(in oklch, var(--' . esc_attr( $token ) . ') 10%, var(--surface)); display: flex; align-items: center; gap: 12px; font-size: 13px;">';
        echo '<span style="font-family: \'Geist Mono\', monospace; font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--' . esc_attr( $token ) . ');">' . esc_html( $label ) . '</span>';
        echo '<span style="color: var(--fg-primary);">' . esc_html( $text ) . '</span>';
        if ( $extra ) echo $extra;
        echo '</div>';
    };

    if ( $msg && isset( $msg_codes[ $msg ] ) ) {
        list( $tone, $text ) = $msg_codes[ $msg ];
        $link = '';
        if ( $new > 0 ) {
            $pt = get_post_type( $new );
            if ( $pt ) {
                $url = home_url( '/' . $pt . '/' . $new . '/' );
                $link = ' <a href="' . esc_url( $url ) . '" class="lga-link" style="margin-left: 8px; font-family: \'Geist Mono\', monospace; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em;">Ver →</a>';
            }
        }
        $render( $tone, $text, $link );
    }
    if ( $err && isset( $err_codes[ $err ] ) ) {
        list( $tone, $text ) = $err_codes[ $err ];
        $render( $tone, $text );
    }
}
