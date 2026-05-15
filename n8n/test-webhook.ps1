# ============================================================================
# test-webhook.ps1 — Firma HMAC y dispara curl al webhook n8n
# ----------------------------------------------------------------------------
# Uso:
#   $env:LGA_WEBHOOK_SECRET = "<hex64>"
#   $env:LGA_WEBHOOK_URL    = "https://n8n.lga-arg.com/webhook/lga-new-credit-app"
#   .\test-webhook.ps1
# ============================================================================

param(
  [string]$PayloadFile = "$PSScriptRoot\test-payload.json",
  [string]$Url    = $env:LGA_WEBHOOK_URL,
  [string]$Secret = $env:LGA_WEBHOOK_SECRET
)

if (-not $Secret) { Write-Error "Falta LGA_WEBHOOK_SECRET en env o como parámetro"; exit 1 }
if (-not $Url)    { Write-Error "Falta LGA_WEBHOOK_URL en env o como parámetro";    exit 1 }
if (-not (Test-Path $PayloadFile)) { Write-Error "No existe $PayloadFile"; exit 1 }

# Leer body como string (sin BOM)
$body = [System.IO.File]::ReadAllText($PayloadFile, [System.Text.UTF8Encoding]::new($false))

# Asegurar nuevo idempotency_key en cada corrida (para no chocar contra ON CONFLICT)
$payloadObj = $body | ConvertFrom-Json
$payloadObj.idempotency_key = [Guid]::NewGuid().ToString()
$body = $payloadObj | ConvertTo-Json -Compress
$idem = $payloadObj.idempotency_key

# Timestamp Unix (segundos)
$ts = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds().ToString()

# HMAC-SHA256(ts + "." + idempotency_key) en hex lowercase
# (NO firmamos el body crudo porque n8n parsea el JSON antes de pasarlo al
#  Code node, lo que rompería el match byte a byte. Firmar idem+ts es
#  suficiente para prevenir replay y tampering de identidad.)
$hmac = New-Object System.Security.Cryptography.HMACSHA256
$hmac.Key = [System.Text.Encoding]::UTF8.GetBytes($Secret)
$signedData = $ts + "." + $idem
$sigBytes = $hmac.ComputeHash([System.Text.Encoding]::UTF8.GetBytes($signedData))
$sig = (-join ($sigBytes | ForEach-Object { $_.ToString('x2') }))

Write-Host "POST $Url"
Write-Host "Signature: $($sig.Substring(0,16))..."
Write-Host "Timestamp: $ts"
Write-Host ""

# Disparar request
try {
  $resp = Invoke-RestMethod -Method Post -Uri $Url `
    -Headers @{
      'X-LGA-Signature' = $sig
      'X-LGA-Timestamp' = $ts
      'X-LGA-IP'        = '127.0.0.1'
      'X-LGA-UA'        = 'lga-test-webhook.ps1'
    } `
    -ContentType 'application/json' `
    -Body $body `
    -TimeoutSec 20
  Write-Host "OK"
  $resp | ConvertTo-Json -Depth 6
} catch {
  Write-Host "FAIL"
  Write-Host $_.Exception.Message
  if ($_.Exception.Response) {
    $stream = $_.Exception.Response.GetResponseStream()
    $reader = New-Object System.IO.StreamReader($stream)
    $errBody = $reader.ReadToEnd()
    Write-Host "Body: $errBody"
  }
  exit 1
}
