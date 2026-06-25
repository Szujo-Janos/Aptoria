$ErrorActionPreference = "Stop"

if (-not (Test-Path ".env") -and (Test-Path ".env.example")) {
    Copy-Item ".env.example" ".env"
}

if (-not (Test-Path "database")) {
    New-Item -ItemType Directory -Path "database" | Out-Null
}

New-Item -ItemType Directory -Path "bootstrap\cache" -Force | Out-Null
New-Item -ItemType Directory -Path "storage\app" -Force | Out-Null
New-Item -ItemType Directory -Path "storage\framework\cache\data" -Force | Out-Null
New-Item -ItemType Directory -Path "storage\framework\sessions" -Force | Out-Null
New-Item -ItemType Directory -Path "storage\framework\views" -Force | Out-Null
New-Item -ItemType Directory -Path "storage\logs" -Force | Out-Null


# Remove stale migration files from earlier prototype builds.
# Copy-Item -Recurse -Force overwrites files but does not delete files that are no longer in the ZIP,
# so old create_users_table migrations can remain in C:\xampp\htdocs\aptoria and fail with
# SQLSTATE[HY000]: table "users" already exists.
$CanonicalUsersMigration = "database\migrations\0001_01_01_000000_create_users_table.php"
Get-ChildItem "database\migrations" -Filter "*create_users_table.php" -ErrorAction SilentlyContinue | ForEach-Object {
    $RelativeMigrationPath = $_.FullName.Substring((Get-Location).Path.Length + 1)
    if ($RelativeMigrationPath -ne $CanonicalUsersMigration) {
        Remove-Item $_.FullName -Force -ErrorAction SilentlyContinue
    }
}

if (-not (Test-Path "database\database.sqlite")) {
    New-Item -ItemType File -Path "database\database.sqlite" | Out-Null
}

# Optional Tabler icon font integration.
# Font files are not included in the release ZIP. If you own them locally, place them here:
# E:\GitHub projects\Aptoria\_local-fonts\tabler\
# Expected files:
# - tabler-icons8aff.woff2
# - tabler-iconsd41d.woff
# - tabler-icons8aff.ttf
$ProjectRoot = (Get-Location).Path
$LocalTablerFontPath = "E:\GitHub projects\Aptoria\_local-fonts\tabler"
$ProjectTablerFontPath = Join-Path $ProjectRoot "public\assets\aptoria-ui\assets\fonts\tabler"

if (Test-Path $LocalTablerFontPath) {
    New-Item -ItemType Directory -Path $ProjectTablerFontPath -Force | Out-Null
    Copy-Item (Join-Path $LocalTablerFontPath "tabler-icons8aff.woff2") $ProjectTablerFontPath -Force -ErrorAction SilentlyContinue
    Copy-Item (Join-Path $LocalTablerFontPath "tabler-iconsd41d.woff") $ProjectTablerFontPath -Force -ErrorAction SilentlyContinue
    Copy-Item (Join-Path $LocalTablerFontPath "tabler-icons8aff.ttf") $ProjectTablerFontPath -Force -ErrorAction SilentlyContinue

    if (Test-Path (Join-Path $ProjectTablerFontPath "tabler-icons8aff.woff2")) {
        Write-Host "Tabler icon fonts copied from local font directory."
    } else {
        Write-Host "Local Tabler font directory found, but expected files were missing. SVG fallback will be used."
    }
} else {
    Write-Host "Tabler icon fonts not found locally. SVG fallback will be used."
}

if (-not (Test-Path "vendor\autoload.php")) {
    composer install
}

C:\xampp\php\php.exe artisan key:generate --force
C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan migrate --force
C:\xampp\php\php.exe artisan aptoria:health
