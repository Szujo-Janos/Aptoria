param(
    [string]$PhpPath = "C:\xampp\php\php.exe",
    [switch]$NoSeed,
    [switch]$ProductionComposer
)

$ErrorActionPreference = "Stop"

$ProjectRoot = Split-Path -Parent $PSScriptRoot
. (Join-Path $PSScriptRoot "windows-xampp-common.ps1")

function Write-AptoriaStep {
    param([string]$Message)
    Write-Host "`n==> $Message" -ForegroundColor Cyan
}

function Ensure-InstalledLock {
    param([string]$ProjectRoot)

    $lockPath = Join-Path $ProjectRoot "storage\app\installed.lock"
    $lockDirectory = Split-Path -Parent $lockPath
    New-Item -ItemType Directory -Path $lockDirectory -Force | Out-Null

    $version = (Get-Content -LiteralPath (Join-Path $ProjectRoot "VERSION") -Raw).Trim()
    $payload = @{
        installed_at = (Get-Date).ToString("o")
        created_by = "install-windows-xampp.ps1"
        version = $version
        app_url = "http://127.0.0.1:8000"
    } | ConvertTo-Json

    Set-Content -LiteralPath $lockPath -Value $payload -Encoding UTF8
    Write-Host "Created storage\app\installed.lock." -ForegroundColor Green
}

function Test-PhpExtension {
    param(
        [string]$PhpPath,
        [string]$Extension
    )

    $loaded = & $PhpPath -r "exit(extension_loaded('$Extension') ? 0 : 1);"
    return $LASTEXITCODE -eq 0
}

function Assert-RequiredPhpExtensions {
    param([string]$PhpPath)

    $requiredExtensions = @("ctype", "curl", "fileinfo", "json", "mbstring", "openssl", "pdo", "pdo_sqlite", "tokenizer", "xml")
    $missing = @()

    foreach ($extension in $requiredExtensions) {
        if (-not (Test-PhpExtension -PhpPath $PhpPath -Extension $extension)) {
            $missing += $extension
        }
    }

    if ($missing.Count -gt 0) {
        throw "Missing required PHP extensions: $($missing -join ', ')"
    }

    Write-Host "Required PHP extensions are available." -ForegroundColor Green
}

Push-Location -LiteralPath $ProjectRoot

try {
    Write-Host "Aptoria self installer for Windows/XAMPP" -ForegroundColor Cyan
    Write-Host "Project root: $ProjectRoot" -ForegroundColor DarkGray

    Write-AptoriaStep "Checking project and PHP"
    Assert-AptoriaProject -ProjectRoot $ProjectRoot
    Assert-PhpExecutable -PhpPath $PhpPath
    Assert-RequiredPhpExtensions -PhpPath $PhpPath

    Write-AptoriaStep "Preparing Laravel runtime directories"
    Ensure-LaravelDirectories -ProjectRoot $ProjectRoot

    Write-AptoriaStep "Preparing environment file"
    Ensure-EnvironmentFile -ProjectRoot $ProjectRoot
    Remove-LegacyVersionOverride -ProjectRoot $ProjectRoot

    Write-AptoriaStep "Preparing SQLite database"
    Ensure-SqliteDatabase -ProjectRoot $ProjectRoot

    Write-AptoriaStep "Installing Composer dependencies"
    Install-ComposerDependencies -PhpPath $PhpPath -ProjectRoot $ProjectRoot

    Write-AptoriaStep "Generating application key if missing"
    Ensure-ApplicationKey -PhpPath $PhpPath -ProjectRoot $ProjectRoot

    Write-AptoriaStep "Clearing caches"
    Invoke-Artisan -PhpPath $PhpPath -Arguments @("optimize:clear")

    Write-AptoriaStep "Running migrations"
    if ($NoSeed) {
        Invoke-Artisan -PhpPath $PhpPath -Arguments @("migrate", "--force")
    } else {
        Invoke-Artisan -PhpPath $PhpPath -Arguments @("migrate", "--seed", "--force")
    }

    Write-AptoriaStep "Locking setup"
    Ensure-InstalledLock -ProjectRoot $ProjectRoot

    Write-AptoriaStep "Final cache clear"
    Invoke-Artisan -PhpPath $PhpPath -Arguments @("optimize:clear")
} finally {
    Pop-Location
}

Write-Host "`nInstallation complete." -ForegroundColor Green
Write-Host "Start the local server with:" -ForegroundColor Green
Write-Host "$PhpPath artisan serve"
Write-Host "Then open: http://127.0.0.1:8000" -ForegroundColor Green
