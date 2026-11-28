param(
    [int]$Port = 8000,
    [int]$AiPort = 8001,
    [switch]$NoTunnel,
    [switch]$NoAi
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

function Need($cmd, $hint) {
    if (-not (Get-Command $cmd -ErrorAction SilentlyContinue)) {
        Write-Host "[x] '$cmd' tidak ditemukan. $hint" -ForegroundColor Red
        exit 1
    }
}

Need php 'Pasang PHP 8.3+ dan tambahkan ke PATH.'
if (-not (Test-Path "$root\vendor\autoload.php")) {
    Write-Host '[x] vendor/ belum ada. Jalankan: composer install' -ForegroundColor Red
    exit 1
}

Write-Host "[1/4] Laravel di http://127.0.0.1:$Port" -ForegroundColor Cyan
Start-Process powershell -ArgumentList '-NoExit', '-Command', "Set-Location '$root'; php artisan serve --host=127.0.0.1 --port=$Port"

Write-Host '[2/4] Worker antrean AI (dipakai bila AI_QUEUE=true)' -ForegroundColor Cyan
Start-Process powershell -ArgumentList '-NoExit', '-Command', "Set-Location '$root'; php artisan queue:work --queue=ai,default --tries=2 --timeout=300"

if (-not $NoAi) {
    $venv = "$root\ai-service\.venv\Scripts\Activate.ps1"
    if (Test-Path $venv) {
        Write-Host "[3/4] ai-service (YAMNet) di http://127.0.0.1:$AiPort" -ForegroundColor Cyan
        Start-Process powershell -ArgumentList '-NoExit', '-Command', "Set-Location '$root\ai-service'; . '$venv'; uvicorn app:app --host 127.0.0.1 --port $AiPort"
    } else {
        Write-Host '[!] ai-service belum di-setup (lihat ai-service/README.md). Dilewati; backend memakai Groq/heuristik.' -ForegroundColor Yellow
    }
}

if (-not $NoTunnel) {
    $cloudflared = (Get-Command cloudflared -ErrorAction SilentlyContinue).Source
    if (-not $cloudflared) {
        $cloudflared = @(
            "${env:ProgramFiles(x86)}\cloudflared\cloudflared.exe",
            "$env:ProgramFiles\cloudflared\cloudflared.exe",
            "$env:LOCALAPPDATA\Microsoft\WinGet\Links\cloudflared.exe"
        ) | Where-Object { $_ -and (Test-Path $_) } | Select-Object -First 1
    }
    if (-not $cloudflared) {
        Write-Host "[x] 'cloudflared' tidak ditemukan. Pasang dengan: winget install --id Cloudflare.cloudflared" -ForegroundColor Red
        exit 1
    }
    Write-Host '[4/4] Cloudflare Tunnel. Salin URL https://....trycloudflare.com dari jendelanya dan bagikan ke tim.' -ForegroundColor Cyan
    Start-Process powershell -ArgumentList '-NoExit', '-Command', "& '$cloudflared' tunnel --url http://127.0.0.1:$Port"
}

Write-Host ''
Write-Host 'Selesai. Tutup jendela-jendela tersebut untuk menghentikan server.' -ForegroundColor Green
