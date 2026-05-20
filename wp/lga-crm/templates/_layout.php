<?php
/**
 * Layout compartido para todos los templates LGA-CRM.
 * v0.3.12 — LGA Internal Command System (full chrome).
 *
 * Aplica el design system institucional del branding LGA:
 *   - Surfaces: graphite ramp #080A0C → #232932
 *   - Foreground: bone #F4F1EA / #E8E3D8 / muted #7E8790
 *   - Accents: bone (logo), steel #5E7184 (technical), olive #5F6B58 (operational)
 *   - States: ok / warn / risk / crit / info con bg+border+fg tinted
 *   - Fonts: Inter (UI) + IBM Plex Mono (números, IDs, eyebrows)
 *   - App shell: sidebar fixed left 232px + topbar 48px + page well
 *   - Components: .panel / .kpi / .btn / .badge / .tbl / .chip
 *
 * Override de classes Tailwind hardcoded en templates legacy (emerald-*,
 * zinc-*, blue-*, amber-*, red-*, teal-*) al token correspondiente del
 * branding, para no reescribir los templates uno por uno.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function lga_crm_layout_open( $title = 'Panel' ) {
    $user  = wp_get_current_user();
    $role  = lga_crm_current_role();
    $role_label = array(
        'administrator' => 'Admin',
        'vendedor'      => 'Vendedor',
        'cobrador'      => 'Cobrador',
    )[ $role ] ?? $role;
    $initials = strtoupper( substr( $user->display_name, 0, 1 ) . substr( strrchr( $user->display_name, ' ' ) ?: $user->display_name, 1, 1 ) );
    $eyebrow = 'Panel · ' . $role_label;

    ?><!DOCTYPE html>
<html lang="es-AR" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html( $title ); ?> — LGA</title>

    <!-- Inter + IBM Plex Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@300;400;500;600&display=swap">

    <!-- Tailwind CDN (utility classes; tokens overridden below) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        darkMode: 'class',
        theme: { extend: {
          fontFamily: {
            sans: ['Inter','Neue Haas Grotesk','Helvetica Neue','system-ui','sans-serif'],
            mono: ['IBM Plex Mono','JetBrains Mono','ui-monospace','monospace'],
          },
          colors: {
            border: 'var(--border)', input: 'var(--border)', ring: 'var(--accent-steel)',
            background: 'var(--bg-0)', foreground: 'var(--fg-0)',
            primary:   { DEFAULT: 'var(--accent-bone)',  foreground: 'var(--fg-on-accent)' },
            secondary: { DEFAULT: 'var(--bg-3)',         foreground: 'var(--fg-0)' },
            muted:     { DEFAULT: 'var(--bg-2)',         foreground: 'var(--fg-3)' },
            accent:    { DEFAULT: 'var(--bg-3)',         foreground: 'var(--fg-0)' },
            destructive: { DEFAULT: 'var(--risk)',       foreground: 'var(--fg-0)' },
            card:        { DEFAULT: 'var(--bg-2)',       foreground: 'var(--fg-0)' },
          },
          borderRadius: { lg: '4px', md: '3px', sm: '2px' },
        } },
      }
    </script>

    <style>
      /* ============================================================
         LGA Internal Command System — tokens (from branding/tokens)
         ============================================================ */
      :root, .dark {
        /* Surfaces */
        --bg-0:        #080A0C;
        --bg-1:        #0E1114;
        --bg-2:        #15191E;
        --bg-3:        #1B2026;
        --bg-4:        #232932;
        --bg-inset:    #060709;

        --border:        #2A3038;
        --border-soft:   #20262D;
        --border-strong: #3A424C;
        --rule:          #1E232A;

        /* Foreground */
        --fg-0: #F4F1EA;
        --fg-1: #E8E3D8;
        --fg-2: #B8B2A7;
        --fg-3: #7E8790;
        --fg-4: #5A626B;
        --fg-on-accent: #0B0D10;

        /* Accents */
        --accent-bone:    #E8E3D8;
        --accent-steel:   #5E7184;
        --accent-steel-2: #7A8B9D;
        --accent-olive:   #5F6B58;
        --accent-olive-2: #7A8771;

        /* States */
        --ok:        #4F8A5B;
        --ok-bg:     #15241A;
        --ok-border: #2C4A33;
        --warn:        #C1843A;
        --warn-bg:     #2A1F12;
        --warn-border: #4A361F;
        --risk:        #A6403A;
        --risk-bg:     #2A1715;
        --risk-border: #4A2622;
        --crit:        #7E2525;
        --crit-bg:     #1F0E0D;
        --crit-border: #3C1817;
        --info:        #5E7184;
        --info-bg:     #16202A;
        --info-border: #283744;
        --neutral-state-bg:     #1B2026;
        --neutral-state-border: #2A3038;

        /* Fonts */
        --font-ui:      "Inter","Neue Haas Grotesk","Helvetica Neue",system-ui,sans-serif;
        --font-mono:    "IBM Plex Mono","JetBrains Mono","Roboto Mono",ui-monospace,Menlo,monospace;
        --font-display: "Inter","Neue Haas Grotesk Display",system-ui,sans-serif;

        /* Type scale */
        --t-xs: 11px; --t-sm: 12px; --t-base: 13px; --t-md: 14px; --t-lg: 16px;
        --t-xl: 18px; --t-2xl: 22px; --t-3xl: 28px; --t-4xl: 36px;

        --lh-tight: 1.15; --lh-snug: 1.3; --lh-normal: 1.45;
        --tracking-tight: -0.01em; --tracking-caps: 0.12em;

        /* Radii */
        --r-0: 0; --r-1: 2px; --r-2: 3px; --r-3: 4px; --r-4: 6px; --r-pill: 999px;
        --radius: 4px;

        /* Elevation */
        --shadow-1: 0 1px 0 rgba(0,0,0,0.4), 0 0 0 1px var(--border-soft);
        --shadow-2: 0 8px 24px -8px rgba(0,0,0,0.6), 0 0 0 1px var(--border);
        --shadow-3: 0 24px 60px -16px rgba(0,0,0,0.7), 0 0 0 1px var(--border);

        /* Layout */
        --sidebar-w: 232px;
        --topbar-h: 48px;

        /* Motion */
        --ease-out: cubic-bezier(0.2,0.7,0.2,1);
        --dur-1: 90ms; --dur-2: 160ms;
      }

      * { box-sizing: border-box; }
      html, body { margin: 0; height: 100%; }
      body {
        background: var(--bg-0);
        color: var(--fg-0);
        font-family: var(--font-ui);
        font-size: var(--t-base);
        line-height: var(--lh-normal);
        font-feature-settings: "ss01","cv11","cv02";
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
      }
      ::selection { background: rgba(94,113,132,0.45); color: var(--fg-0); }
      *:focus-visible { outline: 1px solid var(--accent-steel); outline-offset: 1px; }

      h1,h2,h3,h4,h5,h6 { font-family: var(--font-ui); font-weight: 600; color: var(--fg-1); letter-spacing: var(--tracking-tight); margin: 0; }

      /* Custom scrollbar */
      *::-webkit-scrollbar { width: 10px; height: 10px; }
      *::-webkit-scrollbar-track { background: transparent; }
      *::-webkit-scrollbar-thumb { background: var(--border); border-radius: 999px; border: 2px solid var(--bg-0); }
      *::-webkit-scrollbar-thumb:hover { background: var(--border-strong); }

      /* ============================================================
         App shell — sidebar + main column
         ============================================================ */
      .app {
        display: grid;
        grid-template-columns: var(--sidebar-w) 1fr;
        min-height: 100vh;
        background: var(--bg-0);
        color: var(--fg-0);
      }
      @media (max-width: 720px) {
        .app { grid-template-columns: 1fr; }
        .sidebar { display: none; }
      }

      /* Sidebar */
      .sidebar {
        background: var(--bg-1);
        border-right: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        position: sticky;
        top: 0;
        height: 100vh;
        overflow: hidden;
      }
      .sidebar .brand {
        padding: 14px 18px;
        border-bottom: 1px solid var(--border);
        display: flex; align-items: center; gap: 10px;
      }
      .sidebar .brand img { height: 32px; width: auto; display: block; }
      .sidebar .brand .stack { display: flex; flex-direction: column; gap: 2px; }
      .sidebar .brand .mark { font-family: var(--font-ui); font-size: 13px; font-weight: 600; color: var(--accent-bone); letter-spacing: 0.18em; line-height: 1; }
      .sidebar .brand .cap { font-family: var(--font-mono); font-size: 9px; text-transform: uppercase; letter-spacing: 0.18em; color: var(--fg-3); line-height: 1; }

      .sidebar .nav { flex: 1; overflow-y: auto; padding: 8px 0 16px; }
      .sidebar .grp { font-family: var(--font-mono); font-size: 9px; text-transform: uppercase; letter-spacing: 0.16em; color: var(--fg-4); padding: 14px 18px 4px; }
      .sidebar a.nav-item {
        display: flex; align-items: center; gap: 10px;
        padding: 6px 18px;
        font-family: var(--font-ui); font-size: 12.5px;
        color: var(--fg-2); text-decoration: none;
        border-left: 2px solid transparent;
        cursor: pointer;
        transition: background var(--dur-1) var(--ease-out), color var(--dur-1) var(--ease-out);
      }
      .sidebar a.nav-item .ic { color: var(--fg-3); display: flex; width: 16px; height: 16px; align-items: center; justify-content: center; }
      .sidebar a.nav-item:hover { background: var(--bg-2); color: var(--fg-0); }
      .sidebar a.nav-item:hover .ic { color: var(--fg-2); }
      .sidebar a.nav-item.active { background: var(--bg-2); color: var(--fg-0); border-left-color: var(--accent-steel); }
      .sidebar a.nav-item.active .ic { color: var(--accent-steel-2); }
      .sidebar a.nav-item .ct { margin-left: auto; font-family: var(--font-mono); font-size: 10px; color: var(--fg-3); }
      .sidebar .foot { padding: 10px 18px; border-top: 1px solid var(--border); display: flex; flex-direction: column; gap: 4px; }
      .sidebar .foot .who { font-family: var(--font-ui); font-size: 12px; color: var(--fg-1); }
      .sidebar .foot .meta { font-family: var(--font-mono); font-size: 10px; color: var(--fg-4); text-transform: uppercase; letter-spacing: 0.12em; }
      .sidebar .foot .logout { font-family: var(--font-mono); font-size: 10px; color: var(--fg-3); text-decoration: none; text-transform: uppercase; letter-spacing: 0.12em; margin-top: 4px; }
      .sidebar .foot .logout:hover { color: var(--fg-0); }

      /* Main */
      .main { display: flex; flex-direction: column; min-width: 0; }

      /* Topbar */
      .topbar {
        height: var(--topbar-h);
        border-bottom: 1px solid var(--border);
        background: var(--bg-1);
        display: flex; align-items: center; padding: 0 16px;
        gap: 10px; flex-shrink: 0;
        position: sticky; top: 0; z-index: 10;
      }
      .topbar .search {
        flex: 1; max-width: 460px; height: 30px;
        background: var(--bg-inset);
        border: 1px solid var(--border); border-radius: var(--r-2);
        display: flex; align-items: center; padding: 0 10px; gap: 8px;
        font-family: var(--font-mono); font-size: 11px; color: var(--fg-3);
      }
      .topbar .search input { flex: 1; background: transparent; border: 0; outline: none; color: var(--fg-0); font-family: var(--font-mono); font-size: 11px; }
      .topbar .search input::placeholder { color: var(--fg-4); }
      .topbar .spacer { flex: 1; }
      .kbd { font-family: var(--font-mono); font-size: 10px; color: var(--fg-3); border: 1px solid var(--border); padding: 1px 5px; border-radius: var(--r-1); background: var(--bg-2); }
      .topbar .pill {
        font-family: var(--font-mono); font-size: 11px;
        color: var(--fg-2); background: var(--bg-2);
        border: 1px solid var(--border); border-radius: var(--r-2);
        padding: 5px 10px;
        display: inline-flex; align-items: center; gap: 6px;
      }
      .topbar .pill .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--ok); }
      .topbar .sep { width: 1px; height: 22px; background: var(--border); }
      .topbar .me {
        display: flex; align-items: center; gap: 8px;
        font-family: var(--font-ui); font-size: 12px; color: var(--fg-0);
        padding: 4px 6px; border-radius: var(--r-2);
        text-decoration: none;
      }
      .topbar .me:hover { background: var(--bg-2); }
      .topbar .me .av {
        width: 26px; height: 26px; border-radius: 50%;
        background: var(--bg-3); border: 1px solid var(--border);
        font-family: var(--font-mono); font-size: 10px;
        color: var(--accent-bone);
        display: flex; align-items: center; justify-content: center;
        letter-spacing: 0.04em;
      }
      .topbar .me .role-tag { font-family: var(--font-mono); font-size: 9px; color: var(--fg-3); text-transform: uppercase; letter-spacing: 0.14em; }

      /* Page well */
      .page { flex: 1; padding: 22px 24px 48px; background: var(--bg-0); }
      .page-head { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 22px; gap: 16px; flex-wrap: wrap; }
      .page-head .titles { display: flex; flex-direction: column; gap: 4px; }
      .page-head .eyebrow { font-family: var(--font-mono); font-size: var(--t-xs); text-transform: uppercase; letter-spacing: var(--tracking-caps); color: var(--fg-3); }
      .page-head .ti { font-family: var(--font-display); font-size: var(--t-2xl); letter-spacing: -0.01em; color: var(--fg-1); font-weight: 600; line-height: 1.1; }
      .page-head .acts { display: flex; gap: 6px; }

      /* ============================================================
         Components — botones, badges, tablas, KPI, panels
         ============================================================ */
      .lga-link, a.lga-link { color: var(--accent-bone); text-decoration: none; transition: color var(--dur-1) var(--ease-out); }
      .lga-link:hover { color: var(--fg-0); text-decoration: underline; text-underline-offset: 3px; }

      .lga-card, .panel {
        background: var(--bg-2);
        color: var(--fg-0);
        border: 1px solid var(--border);
        border-radius: var(--r-3);
      }
      .panel-hd {
        padding: 12px 14px;
        border-bottom: 1px solid var(--rule);
        display: flex; align-items: center; justify-content: space-between;
      }
      .panel-hd .ti { font-family: var(--font-ui); font-size: 13px; font-weight: 600; color: var(--fg-0); }
      .panel-bd { padding: 14px; }

      .btn {
        font-family: var(--font-ui); font-size: 12px; font-weight: 500;
        height: 30px; padding: 0 12px;
        border-radius: var(--r-2);
        border: 1px solid transparent;
        cursor: pointer;
        display: inline-flex; align-items: center; gap: 6px;
        transition: background var(--dur-1) var(--ease-out), border-color var(--dur-1) var(--ease-out);
        white-space: nowrap;
      }
      .btn-primary { background: var(--accent-bone); color: var(--fg-on-accent); border-color: var(--accent-bone); }
      .btn-primary:hover { background: #F4EFE4; }
      .btn-secondary { background: var(--bg-3); color: var(--fg-0); border-color: var(--border); }
      .btn-secondary:hover { background: var(--bg-4); border-color: var(--border-strong); }
      .btn-ghost { background: transparent; color: var(--fg-2); }
      .btn-ghost:hover { background: var(--bg-2); color: var(--fg-0); }

      /* Badges */
      .lga-badge, .badge {
        font-family: var(--font-mono); font-size: 11px;
        padding: 2px 8px;
        border-radius: var(--r-1);
        border: 1px solid var(--border);
        background: var(--neutral-state-bg);
        color: var(--fg-2);
        display: inline-flex; align-items: center; gap: 6px;
        line-height: 1.2; white-space: nowrap;
      }
      .lga-badge::before, .badge::before { content: "●"; font-size: 7px; line-height: 1; }
      .lga-badge.no-dot::before, .badge.no-dot::before { content: ""; }

      /* Tables — .lga-table (legacy) + .tbl (DS) */
      .lga-table, .tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
      .lga-table thead, .tbl thead { background: var(--bg-1); }
      .lga-table thead th, .tbl thead th {
        text-align: left; padding: 10px 14px;
        font-family: var(--font-mono); font-size: 10px;
        text-transform: uppercase; letter-spacing: 0.12em;
        color: var(--fg-3); font-weight: 500;
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
      }
      .lga-table tbody tr, .tbl tbody tr { border-top: 1px solid var(--rule); cursor: default; }
      .lga-table tbody tr:hover td, .tbl tbody tr:hover td { background: var(--bg-3); }
      .lga-table tbody td, .tbl tbody td {
        padding: 10px 14px;
        font-family: var(--font-ui); font-size: 12px;
        color: var(--fg-0);
        vertical-align: middle;
        border-bottom: 1px solid var(--rule);
        white-space: nowrap;
      }

      /* KPI */
      .lga-kpi, .kpi {
        background: var(--bg-2);
        border: 1px solid var(--border);
        border-radius: var(--r-3);
        padding: 14px 16px;
        display: flex; flex-direction: column; gap: 6px;
        min-height: 96px;
      }
      .lga-kpi-label, .kpi .eye {
        font-family: var(--font-mono); font-size: 10px;
        text-transform: uppercase; letter-spacing: 0.12em;
        color: var(--fg-3);
      }
      .lga-kpi-value, .kpi .val {
        font-family: var(--font-mono); font-variant-numeric: tabular-nums;
        font-size: 24px; line-height: 1.1;
        letter-spacing: -0.02em;
        color: var(--fg-1); font-weight: 500;
      }

      /* ============================================================
         Tailwind utility overrides — remap clases hardcoded a tokens DS
         ============================================================ */
      .bg-emerald-50  { background-color: var(--ok-bg) !important; }
      .bg-emerald-100 { background-color: var(--ok-bg) !important; }
      .bg-emerald-600, .bg-emerald-700, .bg-emerald-800 { background-color: var(--accent-bone) !important; color: var(--fg-on-accent) !important; }
      .hover\:bg-emerald-800:hover, .hover\:bg-emerald-700:hover { background-color: #F4EFE4 !important; }
      .text-emerald-700, .text-emerald-800 { color: var(--accent-bone) !important; }
      .text-emerald-400                    { color: var(--accent-bone) !important; }
      .hover\:text-emerald-800:hover, .hover\:text-emerald-700:hover { color: var(--fg-0) !important; }
      .ring-emerald-700\/10, .ring-emerald-700\/20, .ring-emerald-500\/20 { --tw-ring-color: var(--ok-border) !important; }
      .border-emerald-300 { border-color: var(--ok-border) !important; }
      .bg-emerald-500\/10 { background-color: var(--ok-bg) !important; }
      .dark\:bg-emerald-500\/10 { background-color: var(--ok-bg) !important; }
      .dark\:text-emerald-400 { color: #7BB28A !important; }
      .dark\:bg-emerald-500 { background-color: var(--accent-bone) !important; }
      .dark\:ring-emerald-500\/20 { --tw-ring-color: var(--ok-border) !important; }

      .bg-white, .bg-card             { background-color: var(--bg-2) !important; }
      .bg-zinc-50                     { background-color: var(--bg-1) !important; }
      .bg-zinc-100                    { background-color: var(--bg-3) !important; }
      .text-zinc-900, .text-foreground { color: var(--fg-0) !important; }
      .text-zinc-700                   { color: var(--fg-1) !important; }
      .text-zinc-600                   { color: var(--fg-2) !important; }
      .text-zinc-500                   { color: var(--fg-3) !important; }
      .text-zinc-400                   { color: var(--fg-4) !important; }
      .border-zinc-200, .border-zinc-300, .border-border { border-color: var(--border) !important; }
      .divide-zinc-100 > :not([hidden]) ~ :not([hidden]) { border-color: var(--rule) !important; }
      .hover\:bg-zinc-50:hover  { background-color: var(--bg-2) !important; }
      .hover\:bg-zinc-100:hover { background-color: var(--bg-3) !important; }
      .hover\:text-zinc-700:hover, .hover\:text-zinc-900:hover { color: var(--fg-0) !important; }

      .bg-blue-50    { background-color: var(--info-bg) !important; }
      .text-blue-700 { color: var(--accent-steel-2) !important; }
      .ring-blue-700\/10 { --tw-ring-color: var(--info-border) !important; }

      .bg-amber-50    { background-color: var(--warn-bg) !important; }
      .text-amber-700 { color: #D6A668 !important; }
      .text-amber-800 { color: #D6A668 !important; }
      .ring-amber-700\/10 { --tw-ring-color: var(--warn-border) !important; }

      .bg-red-50    { background-color: var(--risk-bg) !important; }
      .text-red-700 { color: #C56961 !important; }
      .text-red-600 { color: #C56961 !important; }
      .text-red-800 { color: #C56961 !important; }
      .ring-red-700\/10 { --tw-ring-color: var(--risk-border) !important; }
      .border-red-200 { border-color: var(--risk-border) !important; }

      .bg-teal-50    { background-color: var(--ok-bg) !important; }
      .text-teal-700 { color: #7BB28A !important; }
      .ring-teal-700\/10 { --tw-ring-color: var(--ok-border) !important; }

      .ring-zinc-600\/10 { --tw-ring-color: var(--border) !important; }

      /* Form inputs */
      input[type="text"], input[type="email"], input[type="tel"], input[type="number"],
      input[type="date"], input[type="password"], select, textarea {
        background: var(--bg-inset) !important;
        color: var(--fg-0) !important;
        border: 1px solid var(--border) !important;
        border-radius: var(--r-2) !important;
        font-family: var(--font-ui) !important;
      }
      input:focus, select:focus, textarea:focus {
        outline: none;
        border-color: var(--accent-steel) !important;
        box-shadow: 0 0 0 1px var(--accent-steel);
      }
      input[type="number"], input[inputmode="numeric"], input[inputmode="tel"] {
        font-family: var(--font-mono) !important; font-variant-numeric: tabular-nums;
      }

      /* Progress bars (panel-cobrador créditos) */
      .bg-emerald-600 { background: var(--accent-steel) !important; }

      /* Pre / code (notas internas) */
      pre { font-family: var(--font-mono); color: var(--fg-2); background: var(--bg-inset); border: 1px solid var(--border-soft); border-radius: var(--r-1); padding: 8px 10px; }

      /* Mono utilities */
      .font-mono, .font-mono * { font-family: var(--font-mono); }
      .tabular-nums            { font-variant-numeric: tabular-nums; }

      /* Eyebrow helper */
      .eyebrow { font-family: var(--font-mono); font-size: var(--t-xs); text-transform: uppercase; letter-spacing: var(--tracking-caps); color: var(--fg-3); line-height: 1; }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand">
            <img src="<?php echo esc_url( plugins_url( 'assets/lga-iso.webp', dirname( __FILE__ ) . '/lga-crm.php' ) ); ?>" alt="LGA" />
            <div class="stack">
                <div class="mark">LGA</div>
                <div class="cap">Internal · CRM</div>
            </div>
        </div>
        <nav class="nav">
            <div class="grp">Operaciones</div>
            <?php
            $req = $_SERVER['REQUEST_URI'] ?? '';
            $is_active = function( $path ) use ( $req ) {
                return ( strpos( $req, $path ) === 0 ) ? 'active' : '';
            };
            if ( current_user_can( 'manage_options' ) ): ?>
                <a href="<?php echo esc_url( home_url( '/panel/admin' ) ); ?>" class="nav-item <?php echo $is_active( '/panel/admin' ); ?>">
                    <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></span>
                    <span>Panel admin</span>
                </a>
            <?php endif; ?>
            <?php if ( current_user_can( 'read_lead' ) ): ?>
                <a href="<?php echo esc_url( home_url( '/panel/vendedor' ) ); ?>" class="nav-item <?php echo $is_active( '/panel/vendedor' ); ?>">
                    <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg></span>
                    <span>Leads</span>
                </a>
            <?php endif; ?>
            <?php if ( current_user_can( 'read_cliente' ) ): ?>
                <a href="<?php echo esc_url( home_url( '/panel/cobrador' ) ); ?>" class="nav-item <?php echo $is_active( '/panel/cobrador' ); ?>">
                    <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                    <span>Clientes &amp; Créditos</span>
                </a>
            <?php endif; ?>

            <?php if ( current_user_can( 'manage_options' ) ): ?>
                <div class="grp">Acciones</div>
                <a href="<?php echo esc_url( home_url( '/admin/nuevo-cliente' ) ); ?>" class="nav-item <?php echo $is_active( '/admin/nuevo-cliente' ); ?>">
                    <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg></span>
                    <span>Nuevo cliente</span>
                </a>
            <?php endif; ?>

            <div class="grp">Sistema</div>
            <a href="<?php echo esc_url( admin_url() ); ?>" class="nav-item">
                <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>
                <span>wp-admin</span>
            </a>
        </nav>
        <div class="foot">
            <div class="who"><?php echo esc_html( $user->display_name ); ?></div>
            <div class="meta"><?php echo esc_html( $role_label ); ?> · <?php echo esc_html( $user->user_email ); ?></div>
            <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="logout">Cerrar sesión →</a>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div class="search">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" placeholder="Buscar cliente, lead, crédito… (próximamente)" disabled>
                <span class="kbd">⌘ K</span>
            </div>
            <div class="spacer"></div>
            <span class="pill"><span class="dot"></span><span>PROD</span></span>
            <span class="pill" title="domuhogar.com">DOMU</span>
            <span class="sep"></span>
            <a href="<?php echo esc_url( home_url( '/panel' ) ); ?>" class="me">
                <span class="av"><?php echo esc_html( $initials ?: 'LG' ); ?></span>
                <span><?php echo esc_html( $user->display_name ); ?></span>
                <span class="role-tag"><?php echo esc_html( $role_label ); ?></span>
            </a>
        </header>

        <main class="page">
            <div class="page-head">
                <div class="titles">
                    <div class="eyebrow"><?php echo esc_html( $eyebrow ); ?></div>
                    <div class="ti"><?php echo esc_html( $title ); ?></div>
                </div>
            </div>
<?php
}

function lga_crm_layout_close() {
    ?>
        </main>
    </div>
</div>
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

    $colors = array(
        'ok'   => array( '#7BB28A', '#15241A', '#2C4A33', 'OK' ),
        'warn' => array( '#D6A668', '#2A1F12', '#4A361F', 'WARN' ),
        'risk' => array( '#C56961', '#2A1715', '#4A2622', 'ERROR' ),
    );

    $render = function( $tone, $text, $extra = '' ) use ( $colors ) {
        list( $fg, $bg, $border, $label ) = $colors[ $tone ];
        echo '<div style="margin-bottom: 20px; padding: 12px 16px; border: 1px solid ' . esc_attr( $border ) . '; border-radius: 4px; background: ' . esc_attr( $bg ) . '; display: flex; align-items: center; gap: 12px; font-size: 13px;">';
        echo '<span style="font-family: \'IBM Plex Mono\', monospace; font-size: 10px; text-transform: uppercase; letter-spacing: 0.12em; color: ' . esc_attr( $fg ) . ';">' . esc_html( $label ) . '</span>';
        echo '<span style="color: var(--fg-0);">' . esc_html( $text ) . '</span>';
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
                $link = ' <a href="' . esc_url( $url ) . '" class="lga-link" style="margin-left: 8px; font-family: \'IBM Plex Mono\', monospace; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em;">Ver →</a>';
            }
        }
        $render( $tone, $text, $link );
    }
    if ( $err && isset( $err_codes[ $err ] ) ) {
        list( $tone, $text ) = $err_codes[ $err ];
        $render( $tone, $text );
    }
}
