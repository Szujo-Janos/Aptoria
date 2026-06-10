<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'base_url',
        'report_client_name',
        'report_organization',
        'report_prepared_by',
        'report_role_title',
        'report_confidentiality_label',
        'report_disclaimer',
        'report_logo_path',
        'report_logo_original_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Project $project): void {
            if (! $project->slug) {
                $project->slug = Str::slug($project->name).'-'.Str::lower(Str::random(6));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function environments(): HasMany
    {
        return $this->hasMany(Environment::class);
    }

    public function authProfiles(): HasMany
    {
        return $this->hasMany(AuthProfile::class);
    }

    public function endpoints(): HasMany
    {
        return $this->hasMany(Endpoint::class);
    }

    public function testSuites(): HasMany
    {
        return $this->hasMany(TestSuite::class);
    }

    public function testCases(): HasMany
    {
        return $this->hasMany(TestCase::class);
    }

    public function testCaseResults(): HasMany
    {
        return $this->hasMany(TestCaseResult::class);
    }

    public function contractValidationRuns(): HasMany
    {
        return $this->hasMany(ContractValidationRun::class);
    }

    public function contractValidationResults(): HasMany
    {
        return $this->hasMany(ContractValidationResult::class);
    }

    public function assertionRules(): HasMany
    {
        return $this->hasMany(EndpointAssertionRule::class);
    }

    public function pathParameters(): HasMany
    {
        return $this->hasMany(EndpointPathParameter::class);
    }

    public function scanRuns(): HasMany
    {
        return $this->hasMany(ScanRun::class);
    }

    public function scanResults(): HasManyThrough
    {
        return $this->hasManyThrough(ScanResult::class, ScanRun::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }

    public function findingEvidence(): HasMany
    {
        return $this->hasMany(FindingEvidence::class);
    }

    public function qaReleaseGates(): HasMany
    {
        return $this->hasMany(QaReleaseGate::class);
    }

    public function latestQaReleaseGate(): HasOne
    {
        return $this->hasOne(QaReleaseGate::class)->latestOfMany();
    }

    public function apiMonitors(): HasMany
    {
        return $this->hasMany(ApiMonitor::class);
    }

    public function monitorAlertEvents(): HasMany
    {
        return $this->hasMany(MonitorAlertEvent::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function latestScanRun(): HasOne
    {
        return $this->hasOne(ScanRun::class)->latestOfMany();
    }

    public function latestContractValidationRun(): HasOne
    {
        return $this->hasOne(ContractValidationRun::class)->latestOfMany();
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(Snapshot::class);
    }

    public function compareRuns(): HasMany
    {
        return $this->hasMany(CompareRun::class);
    }

    public function projectSettings(): HasMany
    {
        return $this->hasMany(ProjectSetting::class);
    }

    public function defaultEnvironment(): ?Environment
    {
        $configuredId = $this->projectSettings()->where('key', 'scan.default_environment_id')->value('value');

        if ($configuredId) {
            $environment = $this->environments()->whereKey((int) $configuredId)->first();
            if ($environment instanceof Environment) {
                return $environment;
            }
        }

        return $this->environments()->where('is_production', false)->first() ?: $this->environments()->first();
    }

    public function defaultAuthProfile(): ?AuthProfile
    {
        $configuredId = $this->projectSettings()->where('key', 'scan.default_auth_profile_id')->value('value');

        if ($configuredId) {
            $authProfile = $this->authProfiles()->whereKey((int) $configuredId)->first();
            if ($authProfile instanceof AuthProfile) {
                return $authProfile;
            }
        }

        return $this->authProfiles()->where('is_default', true)->first();
    }

    public function hasProjectReportBranding(): bool
    {
        foreach ([
            'report_client_name',
            'report_organization',
            'report_prepared_by',
            'report_role_title',
            'report_confidentiality_label',
            'report_disclaimer',
            'report_logo_path',
        ] as $field) {
            if (trim((string) $this->getAttribute($field)) !== '') {
                return true;
            }
        }

        return false;
    }

    public function getReportClientOrOrganizationAttribute(): string
    {
        return trim((string) ($this->report_client_name ?: $this->report_organization));
    }

    public function getDisplayBaseUrlAttribute(): string
    {
        return rtrim($this->base_url, '/');
    }
}
