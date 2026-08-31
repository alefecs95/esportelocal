param(
    [string]$ProjectRoot = (Split-Path -Parent $MyInvocation.MyCommand.Path)
)

$ErrorActionPreference = 'Stop'

$frontendPath = Join-Path $ProjectRoot 'frontend'
$backendPath = Join-Path $ProjectRoot 'backend'
$deployPath = Join-Path $ProjectRoot 'deploy'
$publicPath = Join-Path $deployPath 'public'
$deployBackendPath = Join-Path $deployPath 'backend'
$zipPath = Join-Path $ProjectRoot 'deploy.zip'

if (!(Test-Path (Join-Path $frontendPath 'package.json'))) {
    throw 'Frontend não encontrado.'
}

if (!(Test-Path (Join-Path $backendPath 'artisan'))) {
    throw 'Backend não encontrado.'
}

Push-Location $frontendPath
npm ci
npm run build
Pop-Location

if (Test-Path $deployPath) {
    Remove-Item $deployPath -Recurse -Force
}

New-Item -ItemType Directory -Path $publicPath -Force | Out-Null
New-Item -ItemType Directory -Path $deployBackendPath -Force | Out-Null

Copy-Item (Join-Path $frontendPath 'dist\*') $publicPath -Recurse -Force

$robocopyArgs = @(
    $backendPath,
    $deployBackendPath,
    '/E',
    '/R:2',
    '/W:2',
    '/NFL',
    '/NDL',
    '/NJH',
    '/NJS',
    '/NP',
    '/XD', 'node_modules', '.git', '.idea', '.vscode',
    '/XF', '.env', '.env.backup', 'debug_*.php'
)

$robocopyProcess = Start-Process -FilePath 'robocopy.exe' -ArgumentList $robocopyArgs -Wait -NoNewWindow -PassThru
if ($robocopyProcess.ExitCode -ge 8) {
    throw "Falha ao copiar backend com robocopy. Código: $($robocopyProcess.ExitCode)"
}

Copy-Item (Join-Path $backendPath '.env.production') (Join-Path $deployBackendPath '.env') -Force

$spaHtaccess = @"
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteRule ^backend/ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /index.html [L]
</IfModule>
"@

Set-Content -Path (Join-Path $publicPath '.htaccess') -Value $spaHtaccess -Encoding ASCII

if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

Remove-Item (Join-Path $deployBackendPath 'storage\logs\*.log') -Force -ErrorAction SilentlyContinue
Get-ChildItem -Path $deployBackendPath -Filter 'debug_*.php' -File -ErrorAction SilentlyContinue | Remove-Item -Force -ErrorAction SilentlyContinue
Get-ChildItem -Path $publicPath -Filter '*.php' -File -ErrorAction SilentlyContinue | Remove-Item -Force -ErrorAction SilentlyContinue

$zipCriado = $false
for ($tentativa = 1; $tentativa -le 3; $tentativa++) {
    try {
        Compress-Archive -Path (Join-Path $deployPath '*') -DestinationPath $zipPath -Force
        $zipCriado = $true
        break
    } catch {
        Start-Sleep -Seconds 2
    }
}

Write-Host "Deploy gerado em: $deployPath"
if ($zipCriado -and (Test-Path $zipPath)) {
    Write-Host "Arquivo zip gerado em: $zipPath"
} else {
    Write-Host "Zip não foi gerado. Use a pasta deploy para upload."
}
Write-Host "Migrações: execute 'php artisan migrate --force' no backend após configurar o .env."
