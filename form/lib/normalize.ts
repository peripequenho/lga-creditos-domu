// ============================================================================
// Normalizadores de datos antes de validar/persistir.
// Tucumán código de área principal: 381. Móviles AR siempre con prefijo 9.
// ============================================================================

/**
 * Normaliza teléfono AR a E.164 con prefijo móvil 9.
 * Acepta: 3815551234, 0381 415-1234, 0381 4151234, +5493815551234,
 *         +543814151234, 15-XXXXXXXX (formato viejo).
 * Devuelve '' si no llega a 10 dígitos significativos.
 */
export function normalizePhoneAR(raw: string): string {
  if (!raw) return '';
  let d = raw.replace(/\D/g, '');
  if (!d) return '';
  if (d.startsWith('00')) d = d.slice(2);
  if (d.startsWith('54')) d = d.slice(2);
  if (d.startsWith('0'))  d = d.slice(1);
  if (d.startsWith('15')) d = d.slice(2);
  if (d.length < 10) return '';
  if (d.length > 10) d = d.slice(-10);
  return '+549' + d;
}

/** Solo dígitos. */
export function normalizeDni(raw: string): string {
  return (raw || '').replace(/\D/g, '');
}

/** Title case Unicode-aware con locale es-AR. */
export function titleCase(s: string): string {
  return (s || '')
    .trim()
    .toLocaleLowerCase('es-AR')
    .replace(/\b\p{L}/gu, (c) => c.toLocaleUpperCase('es-AR'));
}

/** Postal AR uppercase, sin espacios. */
export function normalizePostal(s: string): string {
  return (s || '').trim().toUpperCase().replace(/\s+/g, '');
}

/** Edad en años cumplidos a hoy. */
export function ageInYears(isoDate: string): number {
  const dt = new Date(isoDate);
  if (isNaN(+dt)) return -1;
  const diffMs = Date.now() - +dt;
  return Math.floor(diffMs / (365.25 * 24 * 3600 * 1000));
}
