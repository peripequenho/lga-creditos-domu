# UX/UI Research · LGA CRM

> Investigación de repos, design systems, referentes y skills para mejorar el diseño del CRM operativo de LGA. Generado 2026-05-18.

## Contexto

CRM interno de financiera no bancaria de Tucumán. 3 roles (admin/vendedor/cobrador) sobre 4 entidades (solicitud/lead/cliente/credito). Stack actual: WordPress mu-plugin + templates PHP + Tailwind CDN (estilo "minimal funcional"). Users no técnicos usándolo 8h/día.

---

## 1. Repos open-source de admin dashboards / CRMs

| Repo | Stars | Stack | Licencia | Fuerte | Falta |
|---|---|---|---|---|---|
| **[satnaing/shadcn-admin](https://github.com/satnaing/shadcn-admin)** ⭐ | ~12k | Vite + React + shadcn/ui + TanStack Router | MIT | 10+ pages, sidebar collapsible, Cmd+K, dark mode, RTL. El más copiado en 2026. | No es Next.js. Sin backend. |
| **[Kiranism/next-shadcn-dashboard-starter](https://github.com/Kiranism/next-shadcn-dashboard-starter)** ⭐ | ~6k | Next.js 16 + shadcn/ui + TanStack Tables + Kanban | MIT | App Router, server actions, feature-based structure, auth incluido. | Más opinionado. |
| **[twentyhq/twenty](https://github.com/twentyhq/twenty)** ⭐ | ~44k | React + NestJS + PostgreSQL + GraphQL | AGPL-3.0 | UI "Figma-quality", modelo de datos flexible. Referente moderno de CRM open-source. | AGPL contagia. |
| [refinedev/refine](https://github.com/refinedev/refine) | ~32k | React meta-framework headless | MIT | Auth providers, access control, real-time. Ejemplo `examples/app-crm` oficial. | Curva de aprendizaje. |
| [TailAdmin/free-nextjs-admin-dashboard](https://github.com/TailAdmin/free-nextjs-admin-dashboard) | ~5k | Next.js 16 + Tailwind v4 | MIT | 500+ UI components, variantes CRM/Sales/Finance. | Estilo corporativo genérico. |
| [tremorlabs/tremor](https://github.com/tremorlabs/tremor) | ~16k | React + Tailwind + Radix | Apache-2.0 | 35+ componentes data viz. Adquirido por Vercel. [300+ blocks](https://blocks.tremor.so/). | Solo visualización. |
| [shadcn-ui/ui](https://ui.shadcn.com) | ~85k+ | Copy-paste primitivos | MIT | Estándar de facto Next + Tailwind. | Tenés que armar la app vos. |
| [Mantine admin](https://github.com/reboottime/mantine-dashboard) | ~700 | Next.js 16 + Mantine 8 | MIT | DataTable, dnd-kit, Tiptap, ApexCharts, NextAuth. Producción listo. | No mezcla bien con Tailwind. |
| [EspoCRM](https://github.com/espocrm/espocrm) | ~3.4k | PHP + Backbone | GPL-3.0 | Lightweight, "vecino cultural" para WordPress/PHP. | Stack viejo. |
| [SuiteCRM](https://suitecrm.com/) | ~5k | PHP (SugarCRM fork) | AGPL-3.0 | Feature-completo nivel Salesforce. | UI "outdated" 2026. |

---

## 2. Design systems / component libraries

| Sistema | Cuándo conviene | Esfuerzo | Link |
|---|---|---|---|
| **shadcn/ui** ⭐ | Estándar Next + Tailwind. Copy-paste, sin runtime. | Bajo | [ui.shadcn.com](https://ui.shadcn.com) |
| **Tremor** | KPIs, charts, métricas. | Bajo | [tremor.so](https://www.tremor.so/) |
| Tailwind UI / Plus (USD ~$299) | Components premium probados. | Bajo | [tailwindcss.com/plus](https://tailwindcss.com/plus) |
| **DaisyUI** ⭐ | Tailwind con semantic class names, sin JS extra. Drop-in en Tailwind CDN actual. | Muy bajo | [daisyui.com](https://daisyui.com) |
| Flowbite | Componentes Tailwind con JS interactivo. | Bajo | [flowbite.com](https://flowbite.com) |
| Preline | 300+ components + 160 starter pages. | Bajo | [preline.co](https://preline.co) |
| Radix UI | Primitivos accesibles (base de shadcn). | Medio | [radix-ui.com](https://www.radix-ui.com) |
| Mantine | Opinionado, completo. | Alto | [mantine.dev](https://mantine.dev) |
| GitHub Primer / Atlassian / Adobe Spectrum / SLDS | Estudio de patterns enterprise. | n/a | varios |

**Combo ganador 2026 Next.js**: shadcn/ui + Tremor (charts) + TanStack Table (data grids) + Tailwind v4.
**Combo in-place PHP**: DaisyUI + tokens shadcn copiados al Tailwind config.

---

## 3. Referentes fintech AR / LATAM

| Empresa | Patrones destacables | Recursos |
|---|---|---|
| **Naranja X** ⭐ | Paleta naranja saturada + neutros, tipografía sans humanista. UX team publica activamente. | [Medium ux-naranjax](https://medium.com/ux-naranjax) · [Behance](https://www.behance.net/NaranjaX) · [Dribbble](https://dribbble.com/ux_naranjax) |
| Ualá | Violeta corporativo, jerarquía del balance. "Tech & touch" (digital + soporte humano). | [Google Cloud case](https://cloud.google.com/customers/uala) · [INSEAD case](https://publishing.insead.edu/case/uala) |
| Mercado Pago Empresas | Sky/cyan + amarillo. Density media-alta sin abrumar. | [Behance Dashboard](https://www.behance.net/gallery/91536533/Dashboard-Mercado-Pago) · [Dribbble](https://dribbble.com/tags/mercado-pago) |
| Modo Business | Naranja/negro, mobile-first incluso panel merchant. | (verificar Dribbble) |
| Cuenta DNI | UI estatal-amigable, focus en accesibilidad senior. | (poca doc pública) |
| Pomelo | Dashboard issuers BaaS. Density alta + métricas card-program. | [pomelo.la](https://pomelo.la/en) |

**Insight**: NaranjaX y Ualá publican procesos. Si copiás *un solo* approach AR → UX NaranjaX (blog Medium + Behance activos).

---

## 4. Referentes SaaS modernos

| App | Lo que hacen bien | Recursos |
|---|---|---|
| **Linear** ⭐ | Cmd+K command palette, atajos G-then, "details matter" como cultura. | [Atajos changelog](https://linear.app/changelog/2021-03-25-keyboard-shortcuts-help) · [Invisible details](https://medium.com/linear-app/invisible-details-2ca718b41a44) · [Delightful Patterns](https://gunpowderlabs.com/2024/12/22/linear-delightful-patterns) |
| **Vercel Dashboard** | Sidebar 240-280px + KPI strip + content grid. Rediseño feb-2026. | [New dashboard](https://vercel.com/try/new-dashboard) · [Redesign changelog](https://vercel.com/changelog/dashboard-navigation-redesign-rollout) |
| Stripe Dashboard | Filtros server-side con URL state, tablas financieras state-of-the-art. | [stripe.dev](https://stripe.dev) |
| Supabase | Sidebar contextual por proyecto, query editors inline. | [supabase.com](https://supabase.com) |
| Resend | Logs/eventos como timeline (replicable para auditoría crédito). | [resend.com](https://resend.com) |
| **Mercury Bank** ⭐ | "Financial data como elemento tipográfico de primera clase". Verde/rojo solo para débito/crédito. | [Demo dashboard](https://demo.mercury.com/dashboard) · [Blake Crosley guide](https://blakecrosley.com/guides/design/mercury) |
| Plaid Dashboard | Form-heavy + state machine bien visualizado. | [plaid.com](https://plaid.com) |

---

## 5. Referentes operativos (8h cargando datos)

| App | Patterns clave |
|---|---|
| **Salesforce Lightning** | Inline edit, bulk actions, validaciones contextuales, atajos, SLDS open-source. [Design System React](https://github.com/salesforce/design-system-react) |
| HubSpot CRM | Activity timeline lateral, vista 360 contacto, undo en bulk imports. |
| Zoho CRM | Multi-módulo con switcher, layouts custom por user. |
| Pipedrive | Kanban-first, cards limpias, reportes 1-card-1-métrica. |
| Front (helpdesk) | Inbox unificada estilo Gmail, keyboard shortcuts profundos. |
| Intercom Inbox | Conversaciones + reglas + macros. Patrón para cobradores siguiendo cuentas. |

**Patterns aplicables a LGA**:
- Cmd+K para buscar cliente / crear lead / ir a cobranza.
- Atajos numéricos para cambiar estado lead (1=visitado, 2=interesado, 3=rechazado).
- Inline edit en columna de cuotas (cobrador edita sin abrir modal).
- Bulk actions con snackbar undo 5s.
- Optimistic UI (el cambio aparece ya, sync detrás).
- Vista 360 cliente en drawer lateral (créditos + cuotas + visitas en timeline).

Material denso: [NN/g - Reduce Cognitive Load](https://www.nngroup.com/articles/4-principles-reduce-cognitive-load/) · [NN/g - EAS framework](https://www.nngroup.com/articles/eas-framework-simplify-forms/).

---

## 6. Skills / roles UX-UI

### Diferencias por rol

| Rol | Qué hace | Mejor si... |
|---|---|---|
| UX Designer | Research, wireframes, flows, journey maps. | Querés re-pensar flows con users reales. |
| UI Designer | Mockups Figma, design tokens, visual. | Solo querés rediseñar apariencia. |
| **Product Designer** ⭐ | UX + UI + estrategia de producto. Rol completo. | Necesitás 1 sola persona para todo el ciclo. |
| UI Engineer | Implementa mockups en código (Figma → React). | Dev team no sabe Tailwind/shadcn. |
| **Design Engineer** ⭐ | Diseña Y codea. Más senior y caro. Linear/Vercel/Stripe lo usan. | Querés iterar directo en código sin Figma intermedio. **Ideal CRM interno.** |
| Front-end con sensibilidad UI | Codea sin diseñar pero buen ojo. | Budget limitado, design lo curás vos. |

### Skills a buscar

**Hard**: Figma (auto-layout, components, variables, dev mode) · Design tokens · WCAG 2.2 AA · React + Tailwind + shadcn · TanStack Table · Tremor/Recharts.

**Soft**: Entrevistas usuario · Customer journey mapping · Heuristic evaluation Nielsen · Session replay (Hotjar/PostHog).

### Dónde buscar (AR / LATAM)

| Plataforma | Notas |
|---|---|
| [Workana](https://www.workana.com/en/freelancers/argentina/user-experience-design) | AR/LATAM, filtrá UX/Product, reviews fiables. |
| [Get on Board](https://www.getonbrd.com) | LATAM, talento mid-senior. |
| LinkedIn AR | Buscar ex-Naranja X, ex-Mercado Pago, ex-Ualá. |
| Behance / Dribbble (filtrar país) | Portfolios reales. |
| Friends of Figma Argentina (Slack) | Comunidad activa. |

### Honorarios 2026 AR (USD)

| Nivel | Freelance hora | Full-time mensual |
|---|---|---|
| Junior UX/UI | $10-25 | $800-1.500 |
| Mid Product Designer | $25-40 | $1.500-3.000 |
| Senior Product Designer | $40-70 | $3.000-5.000 |
| Design Engineer | $50-90 | $4.500-7.000 |

Sources: [Workana benchmark](https://www.workana.com/en/freelancers/argentina/user-experience-design) · [Saldo blog](https://blog.saldo.com.ar/cuanto-cobra-un-freelancer-en-latam-en-2025-y-como-calcular-tu-tarifa-ideal-en-3-pasos/).

---

## 7. Recursos de aprendizaje

### Cursos

| Recurso | Costo | Por qué |
|---|---|---|
| **Refactoring UI** ⭐ (Wathan + Schoger) | USD ~$149-249 one-time | El libro/curso definitivo para devs que diseñan. [refactoringui.com](https://refactoringui.com/) |
| Designcode.io | USD $20/mes | Cursos React/Figma con foco "design engineer". |
| Frontend Mentor | Free + Pro $8/mes | Challenges con Figma → buildés. [frontendmentor.io](https://frontendmentor.io) |
| Google UX Design Cert (Coursera) | USD $39-49/mes × 3-6 meses | Estructurado, beginner-friendly. [Coursera](https://www.coursera.org/professional-certificates/google-ux-design) |
| Uxcel | Free + Pro | Lessons cortas tipo Duolingo. |

### Libros
- **Refactoring UI** (Wathan & Schoger) — indispensable.
- **Don't Make Me Think** (Krug) — 2 horas, mucho ROI.
- **The Design of Everyday Things** (Norman) — fundamentos.
- **Atomic Design** (Frost) — [online gratis](https://atomicdesign.bradfrost.com).

### Newsletters / blogs
- [Smashing Magazine](https://www.smashingmagazine.com)
- [Nielsen Norman Group](https://www.nngroup.com) — research enterprise serio
- [UX Collective](https://uxdesign.cc)
- [A11y Project](https://www.a11yproject.com)
- [UX NaranjaX (Medium)](https://medium.com/ux-naranjax) — proceso AR real

### YouTube
AJ&Smart · Flux Academy · Femke · Juxtopposed

### Comunidades
Designer Hangout (Slack) · Friends of Figma Argentina · shadcn Discord · r/UXDesign

---

## RECOMENDACIONES ACCIONABLES

### Path A — Mejora in-place (mantener PHP + Tailwind CDN)

Esfuerzo: **2-4 semanas part-time**. Resultado: CRM 5x mejor sin tocar arquitectura.

1. **Adoptar DaisyUI sobre Tailwind CDN actual**. 1 línea, 50+ componentes con class names semánticos (`btn`, `card`, `badge`, `table`). Cero JS.
2. **Copiar tokens shadcn/ui Default theme** a `tailwind.config.js`: colores HSL, radius. Alinea con ecosistema y prepara terreno para Path B.
3. **Inspirarse en [satnaing/shadcn-admin](https://github.com/satnaing/shadcn-admin)**: clonar local, copiar specs (sidebar 240-260px, badges outline + bg/10, status pills con dot, tablas con hover ring).
4. **Sumar [Tremor blocks](https://blocks.tremor.so/)** para una pantalla — dashboard admin con métricas (otorgado/mora/montos).
5. **Patterns operativos a meter ya**:
   - Cmd+K modal para buscar cliente
   - Inline edit en cuotas con `<input>` que guarda on blur
   - Snackbar undo 5s para acciones destructivas
   - Drawer lateral con `<dialog>` HTML5 nativo
6. **Comprar Refactoring UI** USD $149 y leer en 1 fin de semana. Más ROI que cualquier contratación.

### Path B — Migración a Next.js + shadcn

Esfuerzo: **3-6 meses para 1 dev senior**. Recomendado si proyectás >50 vendedores/cobradores.

1. **Base**: [Kiranism/next-shadcn-dashboard-starter](https://github.com/Kiranism/next-shadcn-dashboard-starter).
2. **Data**: TanStack Table v8 con server-side filtering/sorting/pagination.
3. **Charts**: [Tremor](https://www.tremor.so) para dashboard admin (KPIs mora/otorgado/recuperado).
4. **Forms**: shadcn `Form` + `react-hook-form` + `zod`. Aplicar [EAS framework](https://www.nngroup.com/articles/eas-framework-simplify-forms/).
5. **Auth + roles**: NextAuth + middleware. Scope por rol.
6. **Alt**: [Refine](https://github.com/refinedev/refine) con adaptador shadcn. [examples/app-crm](https://github.com/refinedev/refine/tree/master/examples/app-crm) es referente.
7. **Estética**: estudiar [Mercury](https://demo.mercury.com/dashboard) y [Linear](https://linear.app) — financial data first-class, density media, Cmd+K, atajos.

### Path C — Contratar

**Perfil recomendado**: **Product Designer mid-senior con front-end skills (HTML/CSS/Tailwind mínimo)**, AR.

NO contratar UX puro (deja Figmas sin implementar). NO contratar UI Engineer sin sensibilidad UX (linda apariencia pero sin pensar flow).

Si budget permite (USD 4-5k/mes): **Design Engineer** es el unicornio que diseña Y codea en React/Tailwind. Ideal para CRM interno donde velocidad de iteración > deliverable Figma.

**Evaluación en entrevista**:
- Portfolio con admin dashboard o CRM real (no consumer).
- Loom de 5 min: "qué cambiarías de esta pantalla del CRM LGA y por qué".
- Test técnico: rediseñar "Cuotas pendientes" en Figma + opcional implementación. 3 hs máx.

**Dónde**:
1. LinkedIn AR (ex-Naranja X, ex-MP, ex-Ualá, ex-Pomelo) — mensaje directo.
2. [Get on Board](https://www.getonbrd.com) — mid-senior LATAM.
3. [Workana](https://www.workana.com/en/freelancers/argentina/user-experience-design) — freelance proyecto cerrado.
4. Friends of Figma AR (Slack) — post de búsqueda.
5. Bootcamps AR (Coderhouse, Egg, Plataforma 5) — junior barato.

**Modelo de contratación**: arrancar freelance proyecto 1-2 meses scope acotado ("rediseño pantallas cobrador + design tokens + entregable Figma + componentes shadcn implementados"). Si funciona, convertir a part-time/full-time.

**Rangos a ofrecer 2026**:
- Freelance proyecto cerrado: USD $3-6k para 8-12 pantallas + tokens.
- Part-time 20hs/sem mid: USD $1.5-2.5k/mes.
- Full-time senior product designer AR: USD $3-4k/mes.
- Design engineer senior: USD $4.5-6.5k/mes.
