import { z } from 'zod';
import { normalizePhoneAR, normalizeDni, normalizePostal, ageInYears } from './normalize';

// ============================================================================
// applicationSchema — validación end-to-end (client + server)
// ============================================================================

export const INSTALLMENTS_ALLOWED = [3, 6, 9, 12, 18, 24] as const;

export const applicationSchema = z.object({
  // ---- Cliente ----
  first_name: z.string().trim().min(2, 'Mínimo 2 caracteres').max(60),
  last_name:  z.string().trim().min(2, 'Mínimo 2 caracteres').max(80),

  dni: z
    .string()
    .transform((s) => normalizeDni(s))
    .pipe(z.string().regex(/^[0-9]{7,9}$/, 'DNI inválido (7 a 9 dígitos)')),

  birth_date: z
    .string()
    .regex(/^\d{4}-\d{2}-\d{2}$/, 'Fecha inválida')
    .refine((d) => {
      const age = ageInYears(d);
      return age >= 18 && age <= 90;
    }, 'Edad fuera de rango (18 a 90)'),

  phone: z
    .string()
    .transform((s) => normalizePhoneAR(s))
    .pipe(z.string().regex(/^\+549\d{10}$/, 'Teléfono AR inválido')),

  email: z.union([z.string().email(), z.literal('')]).optional(),

  address_line: z.string().trim().min(5, 'Mínimo 5 caracteres').max(140),
  locality:     z.string().trim().min(2).max(80),
  province:     z.string().trim().min(2).max(60),
  postal_code: z
    .string()
    .transform((s) => normalizePostal(s))
    .pipe(z.string().regex(/^[A-Z]?\d{4}[A-Z]{0,3}$/, 'Código postal inválido')),

  declared_income_ars: z.coerce.number().min(0).max(50_000_000),

  // ---- Solicitud crédito ----
  requested_amount_ars: z.coerce
    .number()
    .min(50_000, 'Mínimo $50.000')
    .max(5_000_000, 'Máximo $5.000.000'),

  requested_installments: z.coerce
    .number()
    .refine((v) => (INSTALLMENTS_ALLOWED as readonly number[]).includes(v), 'Cuotas no válidas'),

  // ---- Producto (hidden, vienen del deep link) ----
  shop: z.string().min(3),
  source: z.string().optional().default('direct'),
  product_id:     z.string().optional(),
  variant_id:     z.string().optional(),
  product_title:  z.string().optional(),
  product_handle: z.string().optional(),
  unit_price_ars: z.coerce.number().optional(),
  quantity:       z.coerce.number().int().min(1).optional(),
  cart_token:     z.string().optional(),
  cart_summary:   z.string().optional(),
  cart_total_ars: z.coerce.number().min(0),

  // ---- Marketing (hidden) ----
  utm_source:   z.string().optional(),
  utm_medium:   z.string().optional(),
  utm_campaign: z.string().optional(),
  utm_content:  z.string().optional(),
  utm_term:     z.string().optional(),
  referrer_url: z.string().optional(),
  landing_url:  z.string().optional(),

  // ---- Consents ----
  terms_accepted:    z.literal(true, { errorMap: () => ({ message: 'Tenés que aceptar los términos' }) }),
  marketing_consent: z.boolean().default(false),

  // ---- Anti-dupe ----
  idempotency_key: z.string().uuid(),
});

export type ApplicationInput = z.infer<typeof applicationSchema>;

// ============================================================================
// Helpers de parseo de query string (cliente)
// ============================================================================

export function parseDeepLinkParams(sp: URLSearchParams) {
  const num = (k: string) => {
    const v = sp.get(k);
    if (v == null || v === '') return undefined;
    const n = Number(v);
    return Number.isFinite(n) ? n : undefined;
  };
  const cents = (k: string) => {
    const n = num(k);
    return n == null ? undefined : Math.round(n) / 100; // centavos → pesos
  };
  return {
    shop:           sp.get('shop') ?? '',
    source:         sp.get('source') ?? 'direct',
    product_id:     sp.get('product_id') ?? undefined,
    variant_id:     sp.get('variant_id') ?? undefined,
    product_title:  sp.get('title') ?? undefined,
    product_handle: sp.get('handle') ?? undefined,
    unit_price_ars: cents('price'),
    quantity:       num('qty'),
    cart_token:     sp.get('cart_token') ?? undefined,
    cart_summary:   sp.get('cart_summary') ?? undefined,
    cart_total_ars: cents('cart_total') ?? 0,
    utm_source:     sp.get('utm_source') ?? undefined,
    utm_medium:     sp.get('utm_medium') ?? undefined,
    utm_campaign:   sp.get('utm_campaign') ?? undefined,
    utm_content:    sp.get('utm_content') ?? undefined,
    utm_term:       sp.get('utm_term') ?? undefined,
    referrer_url:   sp.get('ref') ?? undefined,
  };
}
