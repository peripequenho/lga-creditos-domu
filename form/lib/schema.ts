import { z } from 'zod';
import { normalizePhoneAR, normalizeDni, normalizePostal, ageInYears } from './normalize';

// ============================================================================
// applicationSchema — validación end-to-end (client + server)
// ============================================================================

export const INSTALLMENTS_BY_FREQUENCY = {
  daily:   [30, 45, 60, 90, 120, 180] as const,         // días
  weekly:  [4, 8, 12, 16, 24, 36] as const,             // semanas
  monthly: [3, 6, 9, 12, 18, 24] as const,              // meses
} as const;

export const HOUSING_OPTIONS = [
  { value: 'owned',  label: 'Propia' },
  { value: 'rented', label: 'Alquilada' },
  { value: 'family', label: 'Familiar / Cedida' },
  { value: 'other',  label: 'Otra' },
] as const;

export const OCCUPATION_OPTIONS = [
  { value: 'employed_registered',      label: 'Empleado en relación de dependencia (en blanco)' },
  { value: 'self_employed_registered', label: 'Monotributista / Autónomo (en blanco)' },
  { value: 'unregistered',             label: 'Trabajador en negro / informal' },
  { value: 'retired',                  label: 'Jubilado / Pensionado' },
  { value: 'homemaker',                label: 'Ama de casa' },
  { value: 'student',                  label: 'Estudiante' },
  { value: 'informal',                 label: 'Changas / Trabajos por cuenta propia' },
  { value: 'other',                    label: 'Otra' },
] as const;

export const PAYMENT_FREQUENCY_OPTIONS = [
  { value: 'daily',   label: 'Diario',  description: 'Pagás todos los días (cuotas chicas)' },
  { value: 'weekly',  label: 'Semanal', description: 'Pagás una vez por semana' },
  { value: 'monthly', label: 'Mensual', description: 'Pagás una vez por mes (más común)' },
] as const;

// Tasa fija: 8% mensual (sistema francés). Equivalente diario / semanal aproximado.
export const INTEREST_RATES = {
  daily:   0.08 / 30,      // ~0.2667% diario
  weekly:  0.08 / 4.33,    // ~1.85% semanal
  monthly: 0.08,           // 8% mensual
} as const;

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
    }, 'Tenés que tener entre 18 y 90 años'),

  phone: z
    .string()
    .transform((s) => normalizePhoneAR(s))
    .pipe(z.string().regex(/^\+549\d{10}$/, 'Teléfono AR inválido (ej: 0381 555 1234)')),

  email: z.union([z.string().email('Email inválido'), z.literal('')]).optional(),

  // ---- Domicilio + situación ----
  address_line: z.string().trim().min(5, 'Mínimo 5 caracteres').max(140),
  locality:     z.string().trim().min(2).max(80),
  province:     z.string().trim().min(2).max(60),
  postal_code: z
    .string()
    .transform((s) => normalizePostal(s))
    .pipe(z.string().regex(/^[A-Z]?\d{4}[A-Z]{0,3}$/, 'Código postal inválido')),

  housing_status: z.enum(['owned', 'rented', 'family', 'other']),

  occupation: z.enum([
    'employed_registered', 'self_employed_registered', 'unregistered',
    'retired', 'homemaker', 'student', 'informal', 'other',
  ]),
  occupation_detail: z.string().trim().max(140).optional().or(z.literal('')),

  // Sin mínimo durante testing
  declared_income_ars: z.coerce.number().min(0).max(50_000_000),

  // ---- Garante (opcional) ----
  guarantor_name:     z.string().trim().max(120).optional().or(z.literal('')),
  guarantor_phone:    z.string().trim().max(20).optional().or(z.literal('')),
  guarantor_relation: z.string().trim().max(60).optional().or(z.literal('')),

  // ---- Solicitud crédito ----
  // Sin mínimo durante etapa de testing (se reintroduce cuando definamos política de costos)
  requested_amount_ars: z.coerce
    .number()
    .positive('El monto debe ser mayor a 0')
    .max(5_000_000, 'Máximo $5.000.000'),

  payment_frequency: z.enum(['daily', 'weekly', 'monthly']),
  requested_installments: z.coerce.number().int().min(3, 'Mínimo 3').max(180, 'Máximo 180'),
  estimated_installment_ars: z.coerce.number().min(0).optional(),

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

  // ---- Documentos (rutas en Supabase Storage, opcional al validar el JSON pero
  //      requerido por flujo del wizard antes de submit) ----
  doc_dni_front:    z.string().optional().or(z.literal('')),
  doc_dni_back:     z.string().optional().or(z.literal('')),
  doc_selfie_dni:   z.string().optional().or(z.literal('')),
  doc_income_proof: z.string().optional().or(z.literal('')),

  // ---- Anti-dupe ----
  idempotency_key: z.string().uuid(),
});

export type ApplicationInput = z.infer<typeof applicationSchema>;

// ============================================================================
// Calcular cuota estimada (sistema francés simplificado)
// ============================================================================

export function calcInstallment(
  amount: number,
  installments: number,
  frequency: 'daily' | 'weekly' | 'monthly',
): number {
  if (!amount || !installments || installments < 1) return 0;
  const r = INTEREST_RATES[frequency];
  if (r === 0) return amount / installments;
  // Sistema francés: cuota = P * r / (1 - (1+r)^-n)
  const cuota = (amount * r) / (1 - Math.pow(1 + r, -installments));
  return Math.round(cuota);
}

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
    return n == null ? undefined : Math.round(n) / 100;
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

// Frecuencias en lenguaje humano
export const FREQUENCY_NOUN = {
  daily:   { single: 'día',    plural: 'días' },
  weekly:  { single: 'semana', plural: 'semanas' },
  monthly: { single: 'mes',    plural: 'meses' },
} as const;
