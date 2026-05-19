/**
 * Shopify Admin GraphQL helper para crear Draft Orders.
 *
 * Flow: client_credentials grant (Dev Dashboard, post-enero/2026).
 *   1. POST /admin/oauth/access_token con client_id + client_secret + grant_type=client_credentials
 *      → access_token shpat_xxx válido 24h
 *   2. Usar como X-Shopify-Access-Token contra /admin/api/{version}/graphql.json
 *
 * Token cache: in-memory por instancia del runtime. Vercel reusará la instancia
 * por minutos en runtime nodejs, así que la mayoría de requests no canjean otra
 * vez. Cuando expire (margen 1h antes), siguiente request canjea de nuevo.
 *
 * Esta lib se llama desde /api/submit-application en Promise.all junto al insert
 * Supabase, para que el draft se cree EN PARALELO sin sumar latencia al cliente.
 *
 * El draft incluye el application_code como tag (`lga-app-LGA-260518-0023`) y como
 * customAttribute (`lga_application_code`) para que WP pueda re-vincularlo después.
 */

import type { ApplicationInput } from './schema';

const SHOP          = process.env.LGA_SHOPIFY_SHOP || '';
const CLIENT_ID     = process.env.LGA_SHOPIFY_CLIENT_ID || '';
const CLIENT_SECRET = process.env.LGA_SHOPIFY_CLIENT_SECRET || '';
const API_VERSION   = process.env.LGA_SHOPIFY_API_VERSION || '2025-01';

export function shopifyEnabled(): boolean {
  return !!(SHOP && CLIENT_ID && CLIENT_SECRET);
}

// Token cache en memoria del runtime
let tokenCache: { token: string; expiresAt: number } | null = null;

async function getAccessToken(): Promise<string> {
  if (tokenCache && tokenCache.expiresAt > Date.now()) {
    return tokenCache.token;
  }
  const res = await fetch(`https://${SHOP}/admin/oauth/access_token`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      client_id: CLIENT_ID,
      client_secret: CLIENT_SECRET,
      grant_type: 'client_credentials',
    }),
    signal: AbortSignal.timeout(10_000),
  });
  if (!res.ok) {
    const body = await res.text().catch(() => '');
    throw new Error(`shopify_token_${res.status}: ${body.slice(0, 200)}`);
  }
  const data = (await res.json()) as { access_token: string; expires_in?: number };
  if (!data.access_token) {
    throw new Error('shopify_token_no_access_token');
  }
  const expires_in = data.expires_in ?? 86400;
  tokenCache = {
    token: data.access_token,
    expiresAt: Date.now() + Math.max(60, expires_in - 3600) * 1000,
  };
  return data.access_token;
}

export type DraftResult = {
  draft_id: string;       // gid numérico (sólo dígitos)
  draft_gid: string;      // gid://shopify/DraftOrder/xxx
  draft_name: string;     // #D1
  invoice_url: string;
  status: string;
};

const MUTATION = `
mutation lgaDraftCreate($input: DraftOrderInput!) {
  draftOrderCreate(input: $input) {
    draftOrder { id name invoiceUrl status }
    userErrors { field message }
  }
}
`;

/**
 * Crea un Draft Order en Shopify a partir del payload del form.
 * Retorna info del draft o lanza error.
 *
 * Idempotencia: no la maneja acá (Vercel garantiza una sola call por
 * `idempotency_key` que ya valida el insert Supabase).
 */
export async function createDraftOrder(
  p: ApplicationInput,
  applicationCode: string,
): Promise<DraftResult> {
  if (!shopifyEnabled()) throw new Error('shopify_disabled');
  if (!p.variant_id) throw new Error('no_variant');

  const variantNumeric = String(p.variant_id).replace(/\D/g, '');
  const quantity = Math.max(1, Math.floor(p.quantity ?? 1));

  const address = {
    firstName: p.first_name || '—',
    lastName:  p.last_name  || '—',
    address1:  p.address_line || '',
    city:      p.locality || '',
    province:  p.province || '',
    zip:       p.postal_code || '',
    country:   'Argentina',
    phone:     p.phone || '',
  };

  const monto = Math.round(p.requested_amount_ars).toLocaleString('es-AR');
  const note = [
    'Crédito LGA pendiente de aprobación.',
    `DNI ${p.dni} · Monto pedido $${monto} ARS · ${p.requested_installments} cuotas ${p.payment_frequency}.`,
    `Application code: ${applicationCode}`,
  ].join('\n');

  const variables = {
    input: {
      lineItems: [{ variantId: `gid://shopify/ProductVariant/${variantNumeric}`, quantity }],
      email: p.email || null,
      phone: p.phone || null,
      shippingAddress: address,
      billingAddress:  address,
      tags: [
        'lga-credit',
        'lga-pending-approval',
        // Tag específico por application_code → permite buscar el draft después
        `lga-app-${applicationCode}`,
      ],
      note,
      customAttributes: [
        { key: 'lga_application_code',  value: applicationCode },
        { key: 'lga_dni',               value: p.dni },
        { key: 'lga_monto_pedido_ars',  value: String(Math.round(p.requested_amount_ars)) },
        { key: 'lga_cuotas',            value: String(p.requested_installments) },
        { key: 'lga_payment_frequency', value: p.payment_frequency },
      ],
    },
  };

  const token = await getAccessToken();
  const res = await fetch(`https://${SHOP}/admin/api/${API_VERSION}/graphql.json`, {
    method: 'POST',
    headers: {
      'X-Shopify-Access-Token': token,
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify({ query: MUTATION, variables }),
    signal: AbortSignal.timeout(15_000),
  });

  if (!res.ok) {
    const body = await res.text().catch(() => '');
    throw new Error(`shopify_http_${res.status}: ${body.slice(0, 300)}`);
  }

  const json = (await res.json()) as {
    data?: {
      draftOrderCreate?: {
        draftOrder?: { id: string; name: string; invoiceUrl: string; status: string };
        userErrors?: Array<{ field?: string; message?: string }>;
      };
    };
    errors?: Array<{ message: string }>;
  };

  if (json.errors?.length) {
    throw new Error(`shopify_graphql_errors: ${JSON.stringify(json.errors)}`);
  }
  const draft = json.data?.draftOrderCreate?.draftOrder;
  const errs  = json.data?.draftOrderCreate?.userErrors ?? [];
  if (errs.length || !draft) {
    throw new Error(`shopify_user_errors: ${JSON.stringify(errs)}`);
  }

  const gid = draft.id; // gid://shopify/DraftOrder/12345
  const numericMatch = gid.match(/(\d+)$/);
  const numericId = numericMatch ? numericMatch[1] : '';

  return {
    draft_id:    numericId,
    draft_gid:   gid,
    draft_name:  draft.name,
    invoice_url: draft.invoiceUrl,
    status:      draft.status,
  };
}
