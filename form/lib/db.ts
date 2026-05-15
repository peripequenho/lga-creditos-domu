import postgres from 'postgres';

// ============================================================================
// Cliente Postgres compartido — Supabase pooler aws-1-sa-east-1
// ----------------------------------------------------------------------------
// Edge runtime: postgres-js v3.x soporta Node y Edge (WebSocket-based bajo Edge).
// En Node usa TCP directo via el pooler en port 6543 (transaction mode).
// ============================================================================

declare global {
  // eslint-disable-next-line no-var
  var __pg: ReturnType<typeof postgres> | undefined;
}

const URL = process.env.DATABASE_URL;
if (!URL) throw new Error('DATABASE_URL is not set');

export const sql =
  global.__pg ??
  postgres(URL, {
    ssl: 'require',
    max: 3,                  // máximo 3 conexiones simultáneas via pooler
    idle_timeout: 20,
    connect_timeout: 10,
    prepare: false,          // transaction pooler NO soporta prepared statements
  });

if (process.env.NODE_ENV !== 'production') global.__pg = sql;
