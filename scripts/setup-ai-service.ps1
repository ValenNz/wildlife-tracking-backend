$ErrorActionPreference = 'Continue'
$root = Split-Path -Parent $PSScriptRoot
$svc = Join-Path $root 'ai-service'
Set-Location $svc

function Step($text) { Write-Host "`n== $text" -ForegroundColor Cyan }
function Fail($text) { Write-Host "`n[x] $text" -ForegroundColor Red; exit 1 }
function PyOk($code) { & $py -c $code *> $null; return ($LASTEXITCODE -eq 0) }

Step 'Python 3.12'
if (-not (Get-Command py -ErrorAction SilentlyContinue)) { Fail "Peluncur 'py' tidak ada. Pasang Python 3.12: winget install --id Python.Python.3.12 -e" }
& py -3.12 --version
if ($LASTEXITCODE -ne 0) { Fail 'Python 3.12 belum terpasang. Jalankan: winget install --id Python.Python.3.12 -e  lalu buka PowerShell baru.' }

$py = Join-Path $svc '.venv\Scripts\python.exe'
if (-not (Test-Path $py)) {
    Step 'Membuat .venv'
    & py -3.12 -m venv .venv
    if ($LASTEXITCODE -ne 0) { Fail 'Gagal membuat .venv' }
}
$ver = (& $py -c "import sys;print('%d.%d' % sys.version_info[:2])").Trim()
if ($ver -ne '3.12') { Fail ".venv memakai Python $ver, bukan 3.12. Hapus folder ai-service\.venv lalu jalankan skrip ini lagi." }

$site = Join-Path $svc '.venv\Lib\site-packages'
Get-ChildItem $site -Filter '~*' -Directory -ErrorAction SilentlyContinue | ForEach-Object {
    Write-Host "Menghapus sisa instalasi terputus: $($_.Name)" -ForegroundColor Yellow
    Remove-Item $_.FullName -Recurse -Force
}

Step 'Paket Python (bisa lama saat pertama kali, total 2-3 GB). Jangan tekan Ctrl+C.'
& $py -m pip --version
if ($LASTEXITCODE -ne 0) {
    & $py -m ensurepip --upgrade
    if ($LASTEXITCODE -ne 0) { Fail 'pip di .venv rusak. Hapus folder ai-service\.venv lalu jalankan skrip ini lagi.' }
}
if (-not (PyOk 'import torch')) {
    & $py -m pip install torch torchvision --index-url https://download.pytorch.org/whl/cpu
    if ($LASTEXITCODE -ne 0) { Fail 'Gagal memasang PyTorch CPU.' }
}
& $py -m pip install -r requirements.txt
if ($LASTEXITCODE -ne 0) { Fail 'pip install -r requirements.txt gagal (lihat pesan pip di atas).' }

if (-not (PyOk 'import reverse_geocoder')) {
    Write-Host '[!] reverse_geocoder gagal di-import. Memasang modulnya dari sdist resmi PyPI...' -ForegroundColor Yellow
    $rgUrl = 'https://files.pythonhosted.org/packages/0b/0f/b7d5d4b36553731f11983e19e1813a1059ad0732c5162c01b3220c927d31/reverse_geocoder-1.5.1.tar.gz'
    $rgSha = '2a2e781b5f69376d922b78fe8978f1350c84fce0ddb07e02c834ecf98b57c75c'
    $rgTmp = Join-Path $env:TEMP 'wildlife-rg-fix'
    New-Item -ItemType Directory -Force $rgTmp | Out-Null
    $rgTar = Join-Path $rgTmp 'reverse_geocoder-1.5.1.tar.gz'
    Invoke-WebRequest -Uri $rgUrl -OutFile $rgTar -UseBasicParsing
    if ((Get-FileHash $rgTar -Algorithm SHA256).Hash.ToLower() -ne $rgSha) { Fail 'Checksum reverse_geocoder tidak cocok dengan PyPI; unduhan dibatalkan.' }
    & tar -xzf $rgTar -C $rgTmp
    if ($LASTEXITCODE -ne 0) { Fail 'Gagal mengekstrak reverse_geocoder.' }
    Copy-Item -Recurse -Force (Join-Path $rgTmp 'reverse_geocoder-1.5.1\reverse_geocoder') $site
    if (-not (PyOk 'import reverse_geocoder')) {
        Write-Host 'Pesan error import:' -ForegroundColor Yellow
        & $py -c 'import reverse_geocoder'
        Fail 'reverse_geocoder masih gagal di-import. Kirim pesan error di atas.'
    }
    Write-Host 'reverse_geocoder OK.' -ForegroundColor Green
}

Step 'Unduh model (yang sudah ada dilewati)'
& $py download_model.py
if ($LASTEXITCODE -ne 0) { Fail 'Ada model yang gagal diunduh/dimuat (lihat daftar GAGAL di atas). Kirim pesan error-nya.' }

Step 'Cek akhir: memuat ketiga model'
$env:TF_CPP_MIN_LOG_LEVEL = '3'
& $py -c "import sys; sys.argv=['x']; import app; [app._load(n) for n in ('audio','vision','reid','faces')]; print('audio, vision, reid, faces: OK')"
if ($LASTEXITCODE -ne 0) { Fail 'Model belum bisa dimuat (lihat error di atas).' }

Write-Host "`nai-service siap. Tutup jendela uvicorn lama (bila ada), lalu jalankan dari root repo:" -ForegroundColor Green
Write-Host '  powershell -ExecutionPolicy Bypass -File scripts\start-server.ps1' -ForegroundColor Green
