param(
    [string]$PhpPath = "C:\xampp\php\php.exe"
)

$ErrorActionPreference = "Stop"

$ProjectRoot = Split-Path -Parent $PSScriptRoot
. (Join-Path $PSScriptRoot "windows-xampp-common.ps1")

Write-Host "Aptoria Windows/XAMPP setup" -ForegroundColor Cyan

Push-Location -LiteralPath $ProjectRoot

try {
    Assert-AptoriaProject -ProjectRoot $ProjectRoot
    Assert-PhpExecutable -PhpPath $PhpPath
    Ensure-LaravelDirectories -ProjectRoot $ProjectRoot
    Ensure-EnvironmentFile -ProjectRoot $ProjectRoot
    Remove-LegacyVersionOverride -ProjectRoot $ProjectRoot
    Backup-SqliteDatabase -ProjectRoot $ProjectRoot
    Ensure-SqliteDatabase -ProjectRoot $ProjectRoot
    Install-ComposerDependencies -PhpPath $PhpPath -ProjectRoot $ProjectRoot
    Ensure-ApplicationKey -PhpPath $PhpPath -ProjectRoot $ProjectRoot
    Invoke-Artisan -PhpPath $PhpPath -Arguments @("optimize:clear")
    Invoke-Artisan -PhpPath $PhpPath -Arguments @("migrate", "--seed", "--force")
    Invoke-Artisan -PhpPath $PhpPath -Arguments @("optimize:clear")
} finally {
    Pop-Location
}

Write-Host "Setup complete. Start with:" -ForegroundColor Green
Write-Host "$PhpPath artisan serve"
