Set-StrictMode -Version Latest

function Assert-AptoriaProject {
    param(
        [Parameter(Mandatory = $true)]
        [string]$ProjectRoot
    )

    foreach ($requiredPath in @("artisan", "composer.json", ".env.example", "VERSION")) {
        $fullPath = Join-Path $ProjectRoot $requiredPath

        if (-not (Test-Path -LiteralPath $fullPath -PathType Leaf)) {
            throw "Required Aptoria file not found: $fullPath"
        }
    }
}

function Assert-PhpExecutable {
    param(
        [Parameter(Mandatory = $true)]
        [string]$PhpPath
    )

    if (-not (Test-Path -LiteralPath $PhpPath -PathType Leaf)) {
        throw "PHP executable not found: $PhpPath"
    }
}

function Invoke-PhpCommand {
    param(
        [Parameter(Mandatory = $true)]
        [string]$PhpPath,

        [Parameter(Mandatory = $true)]
        [string[]]$Arguments
    )

    & $PhpPath @Arguments

    if ($LASTEXITCODE -ne 0) {
        throw "PHP command failed with exit code ${LASTEXITCODE}: $($Arguments -join ' ')"
    }
}

function Invoke-Artisan {
    param(
        [Parameter(Mandatory = $true)]
        [string]$PhpPath,

        [Parameter(Mandatory = $true)]
        [string[]]$Arguments
    )

    Invoke-PhpCommand -PhpPath $PhpPath -Arguments (@("artisan") + $Arguments)
}

function Ensure-LaravelDirectories {
    param(
        [Parameter(Mandatory = $true)]
        [string]$ProjectRoot
    )

    $directories = @(
        "bootstrap\cache",
        "database\backups",
        "storage\app",
        "storage\app\private",
        "storage\app\public",
        "storage\framework\cache",
        "storage\framework\cache\data",
        "storage\framework\sessions",
        "storage\framework\testing",
        "storage\framework\views",
        "storage\logs"
    )

    foreach ($directory in $directories) {
        New-Item -ItemType Directory -Path (Join-Path $ProjectRoot $directory) -Force | Out-Null
    }
}

function Ensure-EnvironmentFile {
    param(
        [Parameter(Mandatory = $true)]
        [string]$ProjectRoot
    )

    $environmentPath = Join-Path $ProjectRoot ".env"

    if (-not (Test-Path -LiteralPath $environmentPath -PathType Leaf)) {
        Copy-Item -LiteralPath (Join-Path $ProjectRoot ".env.example") -Destination $environmentPath
        $content = Get-Content -LiteralPath $environmentPath -Raw
        $content = $content -replace "(?m)^APP_ENV=.*$", "APP_ENV=local"
        $content = $content -replace "(?m)^APP_DEBUG=.*$", "APP_DEBUG=true"
        $content = $content -replace "(?m)^LOG_LEVEL=.*$", "LOG_LEVEL=debug"
        $content = $content -replace "(?m)^APP_URL=.*$", "APP_URL=http://127.0.0.1:8000"
        $utf8WithoutBom = New-Object System.Text.UTF8Encoding($false)
        [System.IO.File]::WriteAllText($environmentPath, $content, $utf8WithoutBom)
        Write-Host "Created local .env from .env.example." -ForegroundColor Yellow
    } else {
        Write-Host "Keeping existing .env." -ForegroundColor DarkGray
    }
}

function Remove-LegacyVersionOverride {
    param(
        [Parameter(Mandatory = $true)]
        [string]$ProjectRoot
    )

    $environmentPath = Join-Path $ProjectRoot ".env"
    if (-not (Test-Path -LiteralPath $environmentPath -PathType Leaf)) {
        return
    }

    $lines = [System.IO.File]::ReadAllLines($environmentPath)
    $filteredLines = @($lines | Where-Object { $_ -notmatch "^\s*APTORIA_VERSION\s*=" })

    if ($filteredLines.Count -eq $lines.Count) {
        return
    }

    $utf8WithoutBom = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllLines($environmentPath, [string[]] $filteredLines, $utf8WithoutBom)
    Write-Host "Removed obsolete APTORIA_VERSION override from .env." -ForegroundColor Yellow
}

function Ensure-SqliteDatabase {
    param(
        [Parameter(Mandatory = $true)]
        [string]$ProjectRoot
    )

    $databasePath = Join-Path $ProjectRoot "database\database.sqlite"

    if (-not (Test-Path -LiteralPath $databasePath -PathType Leaf)) {
        New-Item -ItemType File -Path $databasePath | Out-Null
        Write-Host "Created database\database.sqlite." -ForegroundColor Yellow
    } else {
        Write-Host "Keeping existing database\database.sqlite." -ForegroundColor DarkGray
    }
}

function Backup-SqliteDatabase {
    param(
        [Parameter(Mandatory = $true)]
        [string]$ProjectRoot
    )

    $databasePath = Join-Path $ProjectRoot "database\database.sqlite"

    if (-not (Test-Path -LiteralPath $databasePath -PathType Leaf)) {
        return
    }

    $backupDirectory = Join-Path $ProjectRoot "database\backups"
    New-Item -ItemType Directory -Path $backupDirectory -Force | Out-Null

    $timestamp = Get-Date -Format "yyyyMMdd-HHmmss-fff"
    $backupPath = Join-Path $backupDirectory "database-$timestamp.sqlite"
    Copy-Item -LiteralPath $databasePath -Destination $backupPath

    Write-Host "SQLite backup created: $backupPath" -ForegroundColor Yellow
}

function Test-EnvironmentHasAppKey {
    param(
        [Parameter(Mandatory = $true)]
        [string]$ProjectRoot
    )

    $environmentPath = Join-Path $ProjectRoot ".env"
    $keyLine = Get-Content -LiteralPath $environmentPath |
        Where-Object { $_ -match "^\s*APP_KEY\s*=" } |
        Select-Object -First 1

    if ($null -eq $keyLine) {
        return $false
    }

    $keyValue = ($keyLine -replace "^\s*APP_KEY\s*=\s*", "").Trim()
    $keyValue = $keyValue.Trim([char]34).Trim([char]39)

    return -not [string]::IsNullOrWhiteSpace($keyValue)
}

function Ensure-ApplicationKey {
    param(
        [Parameter(Mandatory = $true)]
        [string]$PhpPath,

        [Parameter(Mandatory = $true)]
        [string]$ProjectRoot
    )

    if (Test-EnvironmentHasAppKey -ProjectRoot $ProjectRoot) {
        Write-Host "Keeping existing APP_KEY." -ForegroundColor DarkGray
        return
    }

    Invoke-Artisan -PhpPath $PhpPath -Arguments @("key:generate", "--force")
}

function Install-ComposerDependencies {
    param(
        [Parameter(Mandatory = $true)]
        [string]$PhpPath,

        [Parameter(Mandatory = $true)]
        [string]$ProjectRoot
    )

    $composerArguments = @("install", "--no-interaction", "--prefer-dist")
    $localComposerPath = Join-Path $ProjectRoot "composer.phar"

    if (Test-Path -LiteralPath $localComposerPath -PathType Leaf) {
        Invoke-PhpCommand -PhpPath $PhpPath -Arguments (@("composer.phar") + $composerArguments)
        return
    }

    $composerCommand = Get-Command composer -ErrorAction SilentlyContinue | Select-Object -First 1

    if ($null -ne $composerCommand) {
        & $composerCommand.Source @composerArguments

        if ($LASTEXITCODE -ne 0) {
            throw "Composer install failed with exit code $LASTEXITCODE."
        }

        return
    }

    Write-Host "Composer not found. Downloading composer.phar..." -ForegroundColor Yellow

    $installerPath = Join-Path $ProjectRoot "composer-setup.php"

    try {
        Invoke-PhpCommand -PhpPath $PhpPath -Arguments @(
            "-r",
            "if (!copy('https://getcomposer.org/installer', 'composer-setup.php')) { exit(1); }"
        )
        Invoke-PhpCommand -PhpPath $PhpPath -Arguments @("composer-setup.php", "--quiet")
    } finally {
        if (Test-Path -LiteralPath $installerPath -PathType Leaf) {
            Remove-Item -LiteralPath $installerPath -Force
        }
    }

    Invoke-PhpCommand -PhpPath $PhpPath -Arguments (@("composer.phar") + $composerArguments)
}
