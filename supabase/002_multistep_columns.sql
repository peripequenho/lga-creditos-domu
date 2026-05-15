-- ============================================================================
-- LGA Credit System — Multi-step form columns
-- Migration 002 - adds columns required by the new UX flow.
-- Idempotent. Safe to re-run.
-- ============================================================================

-- Payment frequency enum
do $$ begin
  create type payment_frequency as enum ('daily','weekly','monthly');
exception when duplicate_object then null; end $$;

-- Housing status enum
do $$ begin
  create type housing_status as enum ('owned','rented','family','other');
exception when duplicate_object then null; end $$;

-- Occupation enum
do $$ begin
  create type occupation_type as enum (
    'employed_registered','self_employed_registered','unregistered','retired',
    'homemaker','student','informal','other'
  );
exception when duplicate_object then null; end $$;

-- Add columns to credit_applications
alter table credit_applications
  add column if not exists payment_frequency payment_frequency,
  add column if not exists housing_status   housing_status,
  add column if not exists occupation       occupation_type,
  add column if not exists occupation_detail text,
  add column if not exists guarantor_name   text,
  add column if not exists guarantor_phone  text,
  add column if not exists guarantor_relation text,
  add column if not exists estimated_installment_ars numeric(14,2);

-- Storage bucket privado para documentos
insert into storage.buckets (id, name, public)
  values ('customer-documents', 'customer-documents', false)
  on conflict (id) do nothing;

-- Tabla documents para metadata de uploads
create table if not exists documents (
  id              uuid primary key default gen_random_uuid(),
  application_id  uuid not null references credit_applications(id) on delete cascade,
  client_id       uuid references clients(id),
  doc_type        text not null check (doc_type in ('dni_front','dni_back','selfie_dni','income_proof','other')),
  file_path       text not null,
  mime_type       text,
  size_bytes      integer,
  uploaded_at     timestamptz not null default now(),
  notes           text
);

create index if not exists ix_docs_application on documents (application_id);
create index if not exists ix_docs_client on documents (client_id);
create index if not exists ix_docs_type on documents (doc_type);

alter table documents enable row level security;
revoke all on documents from anon, authenticated;

comment on table documents is 'Metadata de documentos del cliente almacenados en Supabase Storage bucket customer-documents.';
