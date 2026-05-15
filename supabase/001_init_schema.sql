-- ============================================================================
-- LGA Credit System — Phase 1 Initial Schema
-- ----------------------------------------------------------------------------
-- Project: lga-creditos-domu
-- Date: 2026-05-14
-- Run as: postgres role (Supabase SQL Editor)
-- Idempotent: safe to re-run.
-- ============================================================================
-- Tabla: operational_zones, clients, credit_applications, application_events
-- Función: check_zone(postal, locality, province) -> (status, zone_id)
-- Seed: zonas Tucumán Gran San Miguel + capital
-- RLS: deny-by-default (solo service_role pasa)
-- ============================================================================

create extension if not exists pgcrypto;
create extension if not exists citext;

-- ---- ENUMS ----------------------------------------------------------------
do $$ begin
  create type client_status as enum ('lead','active','blocked','archived');
exception when duplicate_object then null; end $$;

do $$ begin
  create type application_status as enum (
    'submitted','validating','in_review','approved',
    'rejected','cancelled','expired'
  );
exception when duplicate_object then null; end $$;

do $$ begin
  create type zone_status as enum ('in_zone','out_of_zone','needs_review');
exception when duplicate_object then null; end $$;

do $$ begin
  create type event_actor as enum ('system','customer','operator','seller','collector','external');
exception when duplicate_object then null; end $$;

-- ---- TABLA: operational_zones --------------------------------------------
create table if not exists operational_zones (
  id           uuid primary key default gen_random_uuid(),
  country      text not null default 'AR',
  province     text not null,
  locality     text not null,
  postal_code  text,
  is_active    boolean not null default true,
  notes        text,
  created_at   timestamptz not null default now(),
  updated_at   timestamptz not null default now()
);

create unique index if not exists ux_zones_unique
  on operational_zones (country, lower(province), lower(locality), coalesce(postal_code,''));
create index if not exists ix_zones_postal   on operational_zones (postal_code)         where is_active;
create index if not exists ix_zones_locality on operational_zones (lower(locality))     where is_active;
create index if not exists ix_zones_province on operational_zones (lower(province))     where is_active;

comment on table operational_zones is 'Zonas donde LGA opera. F1: solo Tucumán Gran San Miguel.';

-- ---- TABLA: clients -------------------------------------------------------
create table if not exists clients (
  id                  uuid primary key default gen_random_uuid(),
  dni                 text not null,                   -- solo dígitos
  cuil                text,                            -- solo dígitos, 11 chars
  first_name          text not null,
  last_name           text not null,
  full_name           text generated always as (first_name || ' ' || last_name) stored,
  email               citext,
  phone_e164          text,                            -- '+5493815551234'
  birth_date          date,
  gender              text,
  address_line        text,
  locality            text,
  province            text,
  postal_code         text,
  country             text default 'AR',
  status              client_status not null default 'lead',
  source              text,                            -- 'domu_pdp','domu_cart','direct'
  marketing_consent   boolean not null default false,
  notes               text,
  external_refs       jsonb,                           -- {"shopify_customer_id": "..."}
  created_at          timestamptz not null default now(),
  updated_at          timestamptz not null default now(),

  constraint clients_dni_digits   check (dni ~ '^[0-9]{7,9}$'),
  constraint clients_cuil_digits  check (cuil is null or cuil ~ '^[0-9]{11}$'),
  constraint clients_phone_e164   check (phone_e164 is null or phone_e164 ~ '^\+\d{8,15}$'),
  constraint clients_gender_check check (gender is null or gender in ('M','F','X'))
);

create unique index if not exists ux_clients_dni     on clients (dni);
create unique index if not exists ux_clients_cuil    on clients (cuil) where cuil is not null;
create index        if not exists ix_clients_phone   on clients (phone_e164);
create index        if not exists ix_clients_email   on clients (email);
create index        if not exists ix_clients_status  on clients (status);
create index        if not exists ix_clients_created on clients (created_at desc);

comment on table clients is 'Personas físicas que entran al sistema LGA (lead → active → blocked).';

-- ---- SECUENCIA para application_code -------------------------------------
create sequence if not exists application_code_seq;

-- ---- TABLA: credit_applications ------------------------------------------
create table if not exists credit_applications (
  id                       uuid primary key default gen_random_uuid(),
  application_code         text not null unique
    default ('LGA-' || to_char(now() at time zone 'America/Argentina/Buenos_Aires','YYMMDD')
             || '-' || lpad((nextval('application_code_seq') % 10000)::text, 4, '0')),

  client_id                uuid not null references clients(id) on delete restrict,
  status                   application_status not null default 'submitted',
  zone_status              zone_status not null default 'needs_review',
  zone_id                  uuid references operational_zones(id),

  -- Origen y producto Shopify
  shop                     text,
  source                   text,
  product_id               text,
  variant_id               text,
  product_title            text,
  product_handle           text,
  product_url              text,
  unit_price_ars           numeric(14,2),
  quantity                 integer,
  cart_token               text,
  cart_total_ars           numeric(14,2) not null,
  cart_summary             text,

  -- Solicitud financiera
  requested_amount_ars     numeric(14,2) not null,
  requested_installments   integer       not null,
  declared_income_ars      numeric(14,2),

  -- Marketing / atribución
  utm_source               text,
  utm_medium               text,
  utm_campaign             text,
  utm_content              text,
  utm_term                 text,
  referrer_url             text,
  landing_url              text,
  ip                       text,
  user_agent               text,

  -- Idempotencia + auditoría
  idempotency_key          text unique,
  raw_payload              jsonb not null,

  submitted_at             timestamptz not null default now(),
  decided_at               timestamptz,
  created_at               timestamptz not null default now(),
  updated_at               timestamptz not null default now(),

  constraint apps_qty_pos       check (quantity is null or quantity >= 1),
  constraint apps_amount_pos    check (requested_amount_ars > 0),
  constraint apps_installments  check (requested_installments between 1 and 24),
  constraint apps_income_nn     check (declared_income_ars is null or declared_income_ars >= 0),
  constraint apps_cart_nn       check (cart_total_ars >= 0)
);

create index if not exists ix_apps_client     on credit_applications (client_id);
create index if not exists ix_apps_status     on credit_applications (status);
create index if not exists ix_apps_zone       on credit_applications (zone_status);
create index if not exists ix_apps_created    on credit_applications (created_at desc);
create index if not exists ix_apps_shop       on credit_applications (shop);
create index if not exists ix_apps_utm        on credit_applications (utm_source, utm_campaign);

comment on table credit_applications is 'Solicitudes de crédito captadas desde Domu y otros canales.';

-- ---- TABLA: application_events -------------------------------------------
create table if not exists application_events (
  id              uuid primary key default gen_random_uuid(),
  application_id  uuid not null references credit_applications(id) on delete cascade,
  actor           event_actor not null,
  actor_id        uuid,
  actor_label     text,
  event_type      text not null,
  from_status     application_status,
  to_status       application_status,
  detail          jsonb,
  created_at      timestamptz not null default now()
);

create index if not exists ix_events_app  on application_events (application_id, created_at desc);
create index if not exists ix_events_type on application_events (event_type);

comment on table application_events is 'Auditoría inmutable de cada cambio o evento sobre una solicitud.';

-- ---- TRIGGERS updated_at -------------------------------------------------
create or replace function set_updated_at() returns trigger language plpgsql as $$
begin new.updated_at = now(); return new; end $$;

drop trigger if exists tg_zones_updated   on operational_zones;
create trigger tg_zones_updated   before update on operational_zones
for each row execute function set_updated_at();

drop trigger if exists tg_clients_updated on clients;
create trigger tg_clients_updated before update on clients
for each row execute function set_updated_at();

drop trigger if exists tg_apps_updated    on credit_applications;
create trigger tg_apps_updated    before update on credit_applications
for each row execute function set_updated_at();

-- ---- FUNCIÓN: check_zone --------------------------------------------------
create or replace function check_zone(
  p_postal_code text,
  p_locality    text,
  p_province    text
) returns table (status zone_status, zone_id uuid)
language plpgsql stable as $$
declare v_zone_id uuid;
begin
  -- 1) Match por postal_code exacto
  if p_postal_code is not null and length(p_postal_code) > 0 then
    select id into v_zone_id
      from operational_zones
     where is_active and postal_code = p_postal_code
     limit 1;
    if v_zone_id is not null then
      return query select 'in_zone'::zone_status, v_zone_id; return;
    end if;
  end if;

  -- 2) Match por locality + province (case-insensitive)
  if p_locality is not null and p_province is not null then
    select id into v_zone_id
      from operational_zones
     where is_active
       and lower(locality) = lower(p_locality)
       and lower(province) = lower(p_province)
     limit 1;
    if v_zone_id is not null then
      return query select 'in_zone'::zone_status, v_zone_id; return;
    end if;
  end if;

  -- 3) Misma provincia pero localidad/postal desconocido → revisar
  if p_province is not null and lower(p_province) in ('tucumán','tucuman') then
    return query select 'needs_review'::zone_status, null::uuid; return;
  end if;

  -- 4) Fuera de zona
  return query select 'out_of_zone'::zone_status, null::uuid;
end $$;

comment on function check_zone(text,text,text) is
  'Devuelve si una dirección está dentro de operational_zones. Prioriza postal_code, luego locality+province, fallback needs_review para Tucumán.';

-- ---- SEED zonas Tucumán Gran San Miguel + capital -----------------------
insert into operational_zones (province, locality, postal_code) values
  ('Tucumán','San Miguel de Tucumán','T4000'),
  ('Tucumán','San Miguel de Tucumán','T4001'),
  ('Tucumán','San Miguel de Tucumán','T4002'),
  ('Tucumán','San Miguel de Tucumán','T4003'),
  ('Tucumán','Yerba Buena','T4107'),
  ('Tucumán','Tafí Viejo','T4103'),
  ('Tucumán','Banda del Río Salí','T4109'),
  ('Tucumán','Las Talitas','T4101'),
  ('Tucumán','Alderetes','T4178'),
  ('Tucumán','Lules','T4129'),
  ('Tucumán','Famaillá','T4132')
on conflict do nothing;

-- ---- RLS (deny-by-default, solo service_role pasa) -----------------------
alter table operational_zones    enable row level security;
alter table clients              enable row level security;
alter table credit_applications  enable row level security;
alter table application_events   enable row level security;

-- No creamos policies para anon/authenticated en F1.
-- service_role bypassea RLS por diseño de Supabase.
-- F2+ se agregan policies granulares por rol.

revoke all on operational_zones, clients, credit_applications, application_events from anon, authenticated;

-- ============================================================================
-- QUERIES DE VERIFICACIÓN (correr después del schema)
-- ============================================================================
-- select count(*) from operational_zones;                             -- esperado: >= 11
-- select * from check_zone('T4000','San Miguel de Tucumán','Tucumán'); -- esperado: in_zone, uuid
-- select * from check_zone('X9999','Marte','Tucumán');                 -- esperado: needs_review
-- select * from check_zone('5000','Córdoba','Córdoba');                -- esperado: out_of_zone
-- ============================================================================
-- FIN
-- ============================================================================
