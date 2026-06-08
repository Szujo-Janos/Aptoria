param(
    [string]$PhpPath = "C:\xampp\php\php.exe",
    [switch]$ForceRebuild
)

$ErrorActionPreference = "Stop"

$ProjectRoot = Split-Path -Parent $PSScriptRoot
. (Join-Path $PSScriptRoot "windows-xampp-common.ps1")

Write-Host "Aptoria destructive SQLite database rebuild" -ForegroundColor Cyan

if (-not $ForceRebuild) {
    throw "This script replaces database\database.sqlite. Run repair-windows-xampp.ps1 first, or re-run this script with -ForceRebuild."
}

Push-Location -LiteralPath $ProjectRoot

try {
    Assert-AptoriaProject -ProjectRoot $ProjectRoot
    Assert-PhpExecutable -PhpPath $PhpPath
    Ensure-LaravelDirectories -ProjectRoot $ProjectRoot
    Ensure-EnvironmentFile -ProjectRoot $ProjectRoot
    Remove-LegacyVersionOverride -ProjectRoot $ProjectRoot
    Install-ComposerDependencies -PhpPath $PhpPath -ProjectRoot $ProjectRoot
    Ensure-ApplicationKey -PhpPath $PhpPath -ProjectRoot $ProjectRoot
    Backup-SqliteDatabase -ProjectRoot $ProjectRoot
    Invoke-Artisan -PhpPath $PhpPath -Arguments @("optimize:clear")

    $databasePath = Join-Path $ProjectRoot "database\database.sqlite"
    if (Test-Path -LiteralPath $databasePath -PathType Leaf) {
        Remove-Item -LiteralPath $databasePath -Force
    }

    Ensure-SqliteDatabase -ProjectRoot $ProjectRoot
    Invoke-Artisan -PhpPath $PhpPath -Arguments @("migrate:fresh", "--seed", "--force")
    Invoke-Artisan -PhpPath $PhpPath -Arguments @("optimize:clear")
} finally {
    Pop-Location
}

Write-Host "Database rebuilt and seeded." -ForegroundColor Green
Write-Host "Default login: admin@example.com / change-me-now" -ForegroundColor Green
