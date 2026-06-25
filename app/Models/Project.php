<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    public const WORKSPACE_TYPE_LIVE = 'live';
    public const WORKSPACE_TYPE_SANDBOX = 'sandbox';

    protected $fillable = [
        'user_id', 'name', 'slug', 'description', 'base_url', 'environment_label',
        'status', 'workspace_type', 'qa_owner', 'release_goal', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function environments(): HasMany
    {
        return $this->hasMany(Environment::class);
    }

    public function authProfiles(): HasMany
    {
        return $this->hasMany(AuthProfile::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(ProjectSetting::class);
    }

    public function endpoints(): HasMany
    {
        return $this->hasMany(Endpoint::class);
    }

    public function assertionRules(): HasMany
    {
        return $this->hasMany(EndpointAssertionRule::class);
    }

    public function scanRuns(): HasMany
    {
        return $this->hasMany(ScanRun::class);
    }

    public function endpointTestRuns(): HasMany
    {
        return $this->hasMany(EndpointTestRun::class);
    }

    public function endpointTestBatches(): HasMany
    {
        return $this->hasMany(EndpointTestBatch::class);
    }

    public function testSuites(): HasMany
    {
        return $this->hasMany(TestSuite::class);
    }

    public function testCases(): HasMany
    {
        return $this->hasMany(TestCase::class);
    }

    public function testRuns(): HasMany
    {
        return $this->hasMany(TestRun::class);
    }

    public function scanResults(): HasMany
    {
        return $this->hasMany(ScanResult::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(FindingEvidence::class);
    }

    public function riskAcceptances(): HasMany
    {
        return $this->hasMany(RiskAcceptance::class);
    }

    public function releaseReadinessRuns(): HasMany
    {
        return $this->hasMany(ReleaseReadinessRun::class);
    }

    public function releaseReadinessRules(): HasMany
    {
        return $this->hasMany(ReleaseReadinessRule::class);
    }

    public function releaseDecisionSnapshots(): HasMany
    {
        return $this->hasMany(ReleaseDecisionSnapshot::class);
    }

    public function releaseGates(): HasMany
    {
        return $this->hasMany(ReleaseGate::class);
    }

    public function reportVersions(): HasMany
    {
        return $this->hasMany(ReportVersion::class);
    }

    public function endpointSnapshots(): HasMany
    {
        return $this->hasMany(EndpointSnapshot::class);
    }

    public function endpointSnapshotCompares(): HasMany
    {
        return $this->hasMany(EndpointSnapshotCompare::class);
    }

    public function clientPortalAccesses(): HasMany
    {
        return $this->hasMany(ClientPortalAccess::class);
    }

    public function clientPortalAcknowledgements(): HasMany
    {
        return $this->hasMany(ClientPortalAcknowledgement::class);
    }

    public function contractValidationRuns(): HasMany
    {
        return $this->hasMany(ContractValidationRun::class);
    }

    public function contractValidationResults(): HasMany
    {
        return $this->hasMany(ContractValidationResult::class);
    }

    public function externalImportRuns(): HasMany
    {
        return $this->hasMany(ExternalImportRun::class);
    }

    public function externalImportItems(): HasMany
    {
        return $this->hasMany(ExternalImportItem::class);
    }

    public function evidencePacks(): HasMany
    {
        return $this->hasMany(EvidencePack::class);
    }

    public function findingDuplicateCandidates(): HasMany
    {
        return $this->hasMany(FindingDuplicateCandidate::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }


    public function memberships(): HasMany
    {
        return $this->hasMany(ProjectMembership::class);
    }

    public function defaultEnvironment(): ?Environment
    {
        return $this->environments()->where('is_default', true)->first();
    }

    public function defaultAuthProfile(): ?AuthProfile
    {
        return $this->authProfiles()->where('is_default', true)->first();
    }

    public function isSandbox(): bool
    {
        return $this->workspace_type === self::WORKSPACE_TYPE_SANDBOX;
    }

    public function isLive(): bool
    {
        return ! $this->isSandbox();
    }

    public function getWorkspaceTypeLabelAttribute(): string
    {
        return __('messages.workspace_mode.'.($this->workspace_type ?: self::WORKSPACE_TYPE_LIVE));
    }

    public function getWorkspaceTypeToneAttribute(): string
    {
        return $this->isSandbox() ? 'warning' : 'success';
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.labels.project_statuses.'.($this->status ?: 'draft'));
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'paused' => 'warning',
            default => 'secondary',
        };
    }
}
