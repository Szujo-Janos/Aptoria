param(
    [string]$Version,
    [string]$OutputDirectory
)

$ErrorActionPreference = "Stop"
Set-StrictMode -Version Latest

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$ProjectRoot = (Resolve-Path -LiteralPath (Split-Path -Parent $PSScriptRoot)).Path
$VersionPath = Join-Path $ProjectRoot "VERSION"
$FileVersion = (Get-Content -LiteralPath $VersionPath -Raw).Trim()

if ([string]::IsNullOrWhiteSpace($Version)) {
    $Version = $FileVersion
}

if ($Version -ne $FileVersion) {
    throw "Requested version $Version does not match VERSION file $FileVersion."
}

if ($Version -notmatch "^\d+\.\d+\.\d+$") {
    throw "Invalid release version: $Version"
}

if ([string]::IsNullOrWhiteSpace($OutputDirectory)) {
    $OutputDirectory = Split-Path -Parent $ProjectRoot
}

New-Item -ItemType Directory -Path $OutputDirectory -Force | Out-Null
$OutputDirectory = (Resolve-Path -LiteralPath $OutputDirectory).Path

if ($OutputDirectory.Equals($ProjectRoot, [System.StringComparison]::OrdinalIgnoreCase) -or
    $OutputDirectory.StartsWith($ProjectRoot + "\", [System.StringComparison]::OrdinalIgnoreCase)) {
    throw "Output directory must be outside the project root."
}

$ReleaseRoot = "aptoria-$Version"
$OutputPath = Join-Path $OutputDirectory "$ReleaseRoot.zip"

function Test-ReleaseFileExcluded {
    param(
        [Parameter(Mandatory = $true)]
        [string]$RelativePath
    )

    $path = $RelativePath.Replace("\", "/")
    $exactExclusions = @(
        ".env",
        ".env.backup",
        ".phpunit.result.cache",
        "composer.phar",
        "composer-setup.php"
    )

    if ($exactExclusions -contains $path) {
        return $true
    }

    if ($path -match "^\.env(?:\..+)?$" -and $path -ne ".env.example" -and $path -ne ".env.production.example" -and $path -ne ".env.testing") {
        return $true
    }

    if ($path -match "^(?:\.git|\.phpunit\.cache|vendor|node_modules)/") {
        return $true
    }

    if ($path -match "^database/.*\.sqlite(?:3)?(?:[.-].*)?$") {
        return $true
    }

    if ($path -match "^bootstrap/cache/(?!\.gitignore$|\.gitkeep$)") {
        return $true
    }

    if ($path -match "^storage/(?!app/\.gitkeep$|app/private/\.gitkeep$|app/public/\.gitkeep$|framework/cache/\.gitkeep$|framework/cache/data/\.gitkeep$|framework/sessions/\.gitkeep$|framework/testing/\.gitkeep$|framework/views/\.gitkeep$|logs/\.gitkeep$)") {
        return $true
    }

    if ($path -match "^public/storage/") {
        return $true
    }

    return $false
}

$files = Get-ChildItem -LiteralPath $ProjectRoot -Recurse -Force -File |
    Where-Object {
        $relativePath = $_.FullName.Substring($ProjectRoot.Length).TrimStart("\", "/")
        -not (Test-ReleaseFileExcluded -RelativePath $relativePath)
    } |
    Sort-Object FullName

$zipStream = [System.IO.File]::Open($OutputPath, [System.IO.FileMode]::Create)
$archive = New-Object System.IO.Compression.ZipArchive(
    $zipStream,
    [System.IO.Compression.ZipArchiveMode]::Create,
    $false
)

try {
    [void] $archive.CreateEntry("$ReleaseRoot/")

    foreach ($file in $files) {
        $relativePath = $file.FullName.Substring($ProjectRoot.Length).TrimStart("\", "/").Replace("\", "/")
        $entryName = "$ReleaseRoot/$relativePath"

        [void] [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
            $archive,
            $file.FullName,
            $entryName,
            [System.IO.Compression.CompressionLevel]::Optimal
        )
    }
} finally {
    $archive.Dispose()
    $zipStream.Dispose()
}

$requiredEntries = @(
    "$ReleaseRoot/VERSION",
    "$ReleaseRoot/.env.example",
    "$ReleaseRoot/.env.production.example",
    "$ReleaseRoot/.env.testing",
    "$ReleaseRoot/artisan",
    "$ReleaseRoot/composer.json",
    "$ReleaseRoot/composer.lock",
    "$ReleaseRoot/config/aptoria.php",
    "$ReleaseRoot/docs/SYSTEM_AUDIT_v$Version.md",
    "$ReleaseRoot/public/assets/aptoria/img/aptoria-logo-horizontal.png",
    "$ReleaseRoot/public/assets/aptoria/img/aptoria-logo-icon.png",
    "$ReleaseRoot/app/Http/Controllers/EndpointController.php",
    "$ReleaseRoot/app/Http/Controllers/SettingsController.php",
    "$ReleaseRoot/app/Http/Controllers/SetupController.php",
    "$ReleaseRoot/app/Services/Setup/EnvironmentCheckService.php",
    "$ReleaseRoot/app/Services/Setup/SetupStateService.php",
    "$ReleaseRoot/app/Http/Controllers/AssertionRuleController.php",
    "$ReleaseRoot/app/Http/Controllers/ScanController.php",
    "$ReleaseRoot/app/Http/Controllers/SnapshotController.php",
    "$ReleaseRoot/app/Http/Controllers/ReportController.php",
    "$ReleaseRoot/app/Http/Controllers/QaEvidencePackController.php",
    "$ReleaseRoot/app/Http/Requests/AssertionRuleRequest.php",
    "$ReleaseRoot/app/Models/EndpointAssertionRule.php",
    "$ReleaseRoot/app/Services/AssertionEvaluationService.php",
    "$ReleaseRoot/app/Services/RegressionEvaluationService.php",
    "$ReleaseRoot/app/Services/Risk/RiskAnalyzer.php",
    "$ReleaseRoot/app/Services/Snapshots/SnapshotService.php",
    "$ReleaseRoot/app/Services/Reports/ReportExportService.php",
    "$ReleaseRoot/app/Services/Reports/QaEvidencePackService.php",
    "$ReleaseRoot/app/Services/SafeProbeService.php",
    "$ReleaseRoot/app/Http/Middleware/SecurityHeaders.php",
    "$ReleaseRoot/app/Services/Security/NetworkTargetGuard.php",
    "$ReleaseRoot/app/Services/Security/SensitiveValueMasker.php",
    "$ReleaseRoot/app/Services/Security/SetupAccessService.php",
    "$ReleaseRoot/app/Services/Security/SecurityStatusService.php",
    "$ReleaseRoot/app/Services/Settings/SettingService.php",
    "$ReleaseRoot/app/Services/Settings/SettingsRuntimeService.php",
    "$ReleaseRoot/app/Services/Settings/ProjectSettingService.php",
    "$ReleaseRoot/app/Mail/MonitorAlertMail.php",
    "$ReleaseRoot/app/Services/Monitors/MonitorAlertService.php",
    "$ReleaseRoot/app/Models/MonitorAlertEvent.php",
    "$ReleaseRoot/config/mail.php",
    "$ReleaseRoot/database/factories/UserFactory.php",
    "$ReleaseRoot/database/seeders/DatabaseSeeder.php",
    "$ReleaseRoot/database/migrations/2026_06_04_001300_create_endpoint_assertion_rules_table.php",
    "$ReleaseRoot/database/migrations/2026_06_04_001400_add_advanced_settings_metadata_to_settings_table.php",
    "$ReleaseRoot/database/migrations/2026_06_04_003000_add_acknowledgement_fields_to_monitor_alert_events_table.php",
    "$ReleaseRoot/docs/QA_CHECKLIST.md",
    "$ReleaseRoot/docs/INSTALLATION.md",
    "$ReleaseRoot/docs/SECURITY_HARDENING.md",
    "$ReleaseRoot/docs/DEPLOYMENT_SECURITY_CHECKLIST.md",
    "$ReleaseRoot/docs/SCHEDULED_MONITORING_OPERATIONS.md",
    "$ReleaseRoot/docs/MONITOR_ALERTING_OPERATIONS.md",
    "$ReleaseRoot/docs/MONITOR_EMAIL_DELIVERY_OPERATIONS.md",
    "$ReleaseRoot/docs/MONITOR_ALERT_TRIAGE_OPERATIONS.md",
    "$ReleaseRoot/docs/SYSTEM_AUDIT_v1.0.31.md",
    "$ReleaseRoot/docs/QA_OPERATIONS_CALENDAR.md",
    "$ReleaseRoot/docs/CALENDAR_EXPORT_OPERATIONS.md",
    "$ReleaseRoot/docs/SYSTEM_AUDIT_v1.0.32.md",
    "$ReleaseRoot/docs/SYSTEM_AUDIT_v1.0.33.md",
    "$ReleaseRoot/docs/SYSTEM_AUDIT_v1.0.34.md",
    "$ReleaseRoot/docs/SYSTEM_AUDIT_v1.0.35.md",
    "$ReleaseRoot/docs/CALENDAR_ACTIVITY_LOG_OPERATIONS.md",
    "$ReleaseRoot/docs/SYSTEM_AUDIT_v1.0.43.md",
    "$ReleaseRoot/docs/SETTINGS_FUNCTIONAL_AUDIT.md",
    "$ReleaseRoot/docs/SYSTEM_AUDIT_v1.0.72.md",
    "$ReleaseRoot/app/Services/Calendar/CalendarActivityLogger.php",
    "$ReleaseRoot/app/Observers/CalendarActivityObserver.php",
    "$ReleaseRoot/database/migrations/2026_06_04_003200_add_activity_log_fields_to_calendar_events_table.php",
    "$ReleaseRoot/docs/GITHUB_REPOSITORY_CHECKLIST.md",
    "$ReleaseRoot/LICENSE",
    "$ReleaseRoot/NOTICE.md",
    "$ReleaseRoot/CREDITS.md",
    "$ReleaseRoot/docs/PUBLIC_REPOSITORY_CHECKLIST.md",
    "$ReleaseRoot/docs/GITHUB_PUBLIC_READINESS.md",
    "$ReleaseRoot/docs/SYSTEM_AUDIT_v1.0.47.md",
    "$ReleaseRoot/docs/SYSTEM_AUDIT_v1.0.48.md",
    "$ReleaseRoot/docs/SYSTEM_AUDIT_v1.0.52.md",
    "$ReleaseRoot/docs/SYSTEM_AUDIT_v1.0.50.md",
    "$ReleaseRoot/docs/SYSTEM_AUDIT_v1.0.49.md",
    "$ReleaseRoot/.github/PULL_REQUEST_TEMPLATE.md",
    "$ReleaseRoot/.github/dependabot.yml",
    "$ReleaseRoot/THIRD_PARTY_NOTICES.md",
    "$ReleaseRoot/CONTRIBUTING.md",
    "$ReleaseRoot/SECURITY.md",
    "$ReleaseRoot/.github/workflows/php.yml",
    "$ReleaseRoot/.github/ISSUE_TEMPLATE/bug_report.md",
    "$ReleaseRoot/.github/ISSUE_TEMPLATE/feature_request.md",
    "$ReleaseRoot/public/assets/aptoria/js/app.js",
    "$ReleaseRoot/public/assets/aptoria-ui/vendor/bootstrap/js/bootstrap.min.js",
    "$ReleaseRoot/public/assets/aptoria-ui/vendor/jquery/jquery.min.js",
    "$ReleaseRoot/resources/views/endpoints/index.blade.php",
    "$ReleaseRoot/resources/views/endpoints/show.blade.php",
    "$ReleaseRoot/resources/views/assertion_rules/create.blade.php",
    "$ReleaseRoot/resources/views/assertion_rules/edit.blade.php",
    "$ReleaseRoot/resources/views/assertion_rules/_form.blade.php",
    "$ReleaseRoot/resources/views/scans/create.blade.php",
    "$ReleaseRoot/resources/views/scans/show.blade.php",
    "$ReleaseRoot/resources/views/snapshots/index.blade.php",
    "$ReleaseRoot/resources/views/snapshots/show.blade.php",
    "$ReleaseRoot/resources/views/snapshots/compare-show.blade.php",
    "$ReleaseRoot/resources/views/reports/index.blade.php",
    "$ReleaseRoot/resources/views/reports/project.blade.php",
    "$ReleaseRoot/resources/views/qa_evidence/index.blade.php",
    "$ReleaseRoot/resources/views/qa_evidence/partials/form-fields.blade.php",
    "$ReleaseRoot/resources/views/reports/release-readiness.blade.php",
    "$ReleaseRoot/resources/views/reports/release-readiness-index.blade.php",
    "$ReleaseRoot/resources/views/monitors/global-index.blade.php",
    "$ReleaseRoot/resources/views/monitors/alerts.blade.php",
    "$ReleaseRoot/resources/views/emails/monitors/alert.blade.php",
    "$ReleaseRoot/resources/views/emails/monitors/alert-text.blade.php",
    "$ReleaseRoot/resources/views/settings/index.blade.php",
    "$ReleaseRoot/resources/views/setup/index.blade.php",
    "$ReleaseRoot/scripts/install-windows-xampp.ps1",
    "$ReleaseRoot/scripts/install-linux.sh",
    "$ReleaseRoot/scripts/setup-windows-xampp.ps1",
    "$ReleaseRoot/scripts/update-windows-xampp.ps1",
    "$ReleaseRoot/scripts/repair-windows-xampp.ps1",
    "$ReleaseRoot/scripts/repair-database-windows-xampp.ps1",
    "$ReleaseRoot/scripts/build-release.ps1",
    "$ReleaseRoot/tests/Feature/SecurityHardeningTest.php",
    "$ReleaseRoot/tests/Feature/ScheduledMonitorCommandTest.php",
    "$ReleaseRoot/tests/Feature/MonitorAlertingTest.php",
    "$ReleaseRoot/tests/Feature/QaEvidencePackTest.php",
    "$ReleaseRoot/tests/Feature/SettingsFunctionalAuditTest.php",
    "$ReleaseRoot/tests/Feature/SettingsCenterTest.php",
    "$ReleaseRoot/tests/Feature/SettingsLocalizationTest.php",
    "$ReleaseRoot/tests/Feature/ProjectSettingsTest.php",
    "$ReleaseRoot/bootstrap/cache/.gitkeep",
    "$ReleaseRoot/database/backups/.gitkeep",
    "$ReleaseRoot/storage/app/.gitkeep",
    "$ReleaseRoot/storage/app/private/.gitkeep",
    "$ReleaseRoot/storage/app/public/.gitkeep",
    "$ReleaseRoot/storage/framework/cache/.gitkeep",
    "$ReleaseRoot/storage/framework/cache/data/.gitkeep",
    "$ReleaseRoot/storage/framework/sessions/.gitkeep",
    "$ReleaseRoot/storage/framework/testing/.gitkeep",
    "$ReleaseRoot/storage/framework/views/.gitkeep",
    "$ReleaseRoot/storage/logs/.gitkeep"
)

$validationArchive = [System.IO.Compression.ZipFile]::OpenRead($OutputPath)

try {
    $entryNames = @($validationArchive.Entries | ForEach-Object { $_.FullName })
    $missingEntries = @($requiredEntries | Where-Object { $entryNames -notcontains $_ })
    $forbiddenEntries = @($entryNames | Where-Object {
        if ($_ -eq "$ReleaseRoot/") {
            return $false
        }

        $relativeEntry = $_.Substring($ReleaseRoot.Length + 1)
        return Test-ReleaseFileExcluded -RelativePath $relativeEntry
    })

    if ($missingEntries.Count -gt 0) {
        throw "Release ZIP is missing required entries: $($missingEntries -join ', ')"
    }

    if ($forbiddenEntries.Count -gt 0) {
        throw "Release ZIP contains forbidden entries: $($forbiddenEntries -join ', ')"
    }
} finally {
    $validationArchive.Dispose()
}

Write-Host "Created release: $OutputPath" -ForegroundColor Green
