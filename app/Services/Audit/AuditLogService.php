<?php

namespace App\Services\Audit;

use App\Models\ApiMonitor;
use App\Models\AuditLog;
use App\Models\AuthProfile;
use App\Models\CompareRun;
use App\Models\ContractValidationRun;
use App\Models\Endpoint;
use App\Models\EndpointAssertionRule;
use App\Models\EndpointPathParameter;
use App\Models\EndpointBehaviorLink;
use App\Models\Environment;
use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\MonitorAlertEvent;
use App\Models\Project;
use App\Models\ProjectSetting;
use App\Models\ProjectMembership;
use App\Models\QaReleaseGate;
use App\Models\QaReleaseGateItem;
use App\Models\ReleaseDecision;
use App\Models\ReleaseWorkflow;
use App\Models\ReleaseWorkflowStep;
use App\Models\RiskAcceptance;
use App\Models\ScanResult;
use App\Models\ScanRun;
use App\Models\Setting;
use App\Models\Snapshot;
use App\Models\TestCase;
use App\Models\TestCaseResult;
use App\Models\TestSuite;
use App\Models\User;
use App\Services\Settings\SettingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class AuditLogService
{
    private static int $suppressionDepth = 0;

    /** @var array<int, string> */
    private array $sensitiveKeys = [
        'password',
        'token',
        'secret',
        'api_key',
        'access_token',
        'refresh_token',
        'authorization',
        'bearer_token',
        'basic_password',
        'value',
    ];

    public static function withoutRecording(callable $callback): mixed
    {
        self::$suppressionDepth++;

        try {
            return $callback();
        } finally {
            self::$suppressionDepth = max(0, self::$suppressionDepth - 1);
        }
    }

    public static function isSuppressed(): bool
    {
        return self::$suppressionDepth > 0;
    }

    public function recordModel(string $action, Model $model): void
    {
        if (! $this->shouldRecord() || ! $this->canRecordModel($model)) {
            return;
        }

        $before = null;
        $after = null;

        if ($action === AuditLog::ACTION_CREATED) {
            $after = $this->cleanAttributes($model->getAttributes());
        } elseif ($action === AuditLog::ACTION_UPDATED) {
            $changes = $this->meaningfulChanges($model->getChanges());
            if ($changes === []) {
                return;
            }

            $before = $this->originalValuesForChanges($model, $changes);
            $after = $this->cleanAttributes($changes);
        } elseif ($action === AuditLog::ACTION_DELETED) {
            $before = $this->cleanAttributes($model->getOriginal());
        }

        $this->record([
            'project_id' => $this->projectId($model),
            'event_type' => AuditLog::EVENT_MODEL,
            'action' => $action,
            'severity' => $this->severityFor($action, $model),
            'auditable_type' => $model::class,
            'auditable_id' => $this->subjectId($model),
            'subject_label' => $this->subjectLabel($model),
            'subject_name' => $this->subjectName($model),
            'summary' => $this->modelSummary($action, $model),
            'before_values' => $before,
            'after_values' => $after,
            'metadata' => [
                'model_table' => method_exists($model, 'getTable') ? $model->getTable() : null,
            ],
        ]);
    }

    /** @param array<string, mixed> $payload */
    public function record(array $payload): void
    {
        if (! $this->shouldRecord()) {
            return;
        }

        try {
            AuditLog::query()->create(array_merge([
                'project_id' => null,
                'user_id' => auth()->id(),
                'event_type' => AuditLog::EVENT_SYSTEM,
                'action' => AuditLog::ACTION_UPDATED,
                'severity' => AuditLog::SEVERITY_INFO,
                'summary' => null,
                'route_name' => request()?->route()?->getName(),
                'http_method' => request()?->method(),
                'url' => request()?->fullUrl(),
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'occurred_at' => now(),
            ], $payload));
        } catch (Throwable) {
            // Audit logging must never block the workflow it records.
        }
    }

    /** @return array<string, int> */
    public function summary(?Project $project = null): array
    {
        $query = AuditLog::query();
        if ($project) {
            $query->where('project_id', $project->id);
        }

        return [
            'total' => (clone $query)->count(),
            'today' => (clone $query)->whereDate('occurred_at', now()->toDateString())->count(),
            'warning' => (clone $query)->where('severity', AuditLog::SEVERITY_WARNING)->count(),
            'critical' => (clone $query)->where('severity', AuditLog::SEVERITY_CRITICAL)->count(),
        ];
    }

    private function shouldRecord(): bool
    {
        if (self::isSuppressed()) {
            return false;
        }

        try {
            if (! Schema::hasTable('audit_logs')) {
                return false;
            }

            if (Schema::hasTable('settings')) {
                return app(SettingService::class)->boolean('security.enable_audit_log', true);
            }
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    private function canRecordModel(Model $model): bool
    {
        if ($model instanceof AuditLog) {
            return false;
        }

        return true;
    }

    /** @param array<string, mixed> $changes */
    private function meaningfulChanges(array $changes): array
    {
        unset($changes['updated_at']);

        return $changes;
    }

    /** @param array<string, mixed> $changes */
    private function originalValuesForChanges(Model $model, array $changes): array
    {
        $original = [];
        foreach (array_keys($changes) as $key) {
            $original[$key] = $model->getOriginal($key);
        }

        return $this->cleanAttributes($original);
    }

    /** @param array<string, mixed> $attributes */
    private function cleanAttributes(array $attributes): array
    {
        unset($attributes['remember_token'], $attributes['created_at'], $attributes['updated_at']);

        foreach ($attributes as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                $attributes[$key] = '[masked]';
                continue;
            }

            if (is_string($value)) {
                $attributes[$key] = Str::limit($value, 1000, '...');
            }
        }

        return $attributes;
    }

    private function isSensitiveKey(string $key): bool
    {
        $lower = Str::lower($key);

        foreach ($this->sensitiveKeys as $needle) {
            if (str_contains($lower, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function subjectId(Model $model): ?int
    {
        $key = $model->getKey();

        return is_numeric($key) ? (int) $key : null;
    }

    private function subjectLabel(Model $model): string
    {
        return Str::of(class_basename($model))->headline()->lower()->toString();
    }

    private function subjectName(Model $model): string
    {
        foreach (['name', 'title', 'release_name', 'email', 'slug', 'key'] as $attribute) {
            $value = $model->getAttribute($attribute);
            if (is_string($value) && trim($value) !== '') {
                return (string) Str::limit(trim($value), 120);
            }
        }

        if ($model->getAttribute('method') || $model->getAttribute('path')) {
            return trim((string) $model->getAttribute('method').' '.(string) $model->getAttribute('path')) ?: '#'.$this->subjectId($model);
        }

        return '#'.($this->subjectId($model) ?? 'n/a');
    }

    private function modelSummary(string $action, Model $model): string
    {
        return Str::headline($action).' '.$this->subjectLabel($model).' '.$this->subjectName($model);
    }

    private function severityFor(string $action, Model $model): string
    {
        if ($action === AuditLog::ACTION_DELETED) {
            return AuditLog::SEVERITY_WARNING;
        }

        if ($model instanceof MonitorAlertEvent && in_array($model->severity, [AuditLog::SEVERITY_WARNING, AuditLog::SEVERITY_CRITICAL], true)) {
            return (string) $model->severity;
        }

        if ($model instanceof Finding && in_array($model->severity, ['critical', 'high'], true)) {
            return $model->severity === 'critical' ? AuditLog::SEVERITY_CRITICAL : AuditLog::SEVERITY_WARNING;
        }

        if ($model instanceof RiskAcceptance && ($model->accepted_until === null || $model->is_expired)) {
            return AuditLog::SEVERITY_WARNING;
        }

        if ($model instanceof ReleaseDecision && in_array($model->decision_status, [ReleaseDecision::STATUS_NO_GO, ReleaseDecision::STATUS_BLOCKED], true)) {
            return AuditLog::SEVERITY_WARNING;
        }

        if ($model instanceof ScanRun && in_array($model->status, ['failed', 'warning'], true)) {
            return $model->status === 'failed' ? AuditLog::SEVERITY_WARNING : AuditLog::SEVERITY_NOTICE;
        }

        return AuditLog::SEVERITY_INFO;
    }

    private function projectId(Model $model): ?int
    {
        if ($model instanceof Project) {
            return $this->subjectId($model);
        }

        $projectId = $model->getAttribute('project_id');
        if (is_numeric($projectId)) {
            return (int) $projectId;
        }

        if ($model instanceof Environment || $model instanceof ApiMonitor || $model instanceof Finding || $model instanceof QaReleaseGate || $model instanceof ReleaseDecision || $model instanceof RiskAcceptance || $model instanceof EndpointBehaviorLink || $model instanceof Snapshot || $model instanceof ScanRun || $model instanceof CompareRun || $model instanceof ContractValidationRun || $model instanceof TestSuite || $model instanceof MonitorAlertEvent) {
            return is_numeric($model->project_id) ? (int) $model->project_id : null;
        }

        if ($model instanceof Endpoint || $model instanceof EndpointAssertionRule || $model instanceof EndpointPathParameter || $model instanceof ScanResult) {
            $endpointId = $model instanceof Endpoint ? $model->id : $model->getAttribute('endpoint_id');
            $endpoint = $model instanceof Endpoint ? $model : Endpoint::query()->find($endpointId);

            return $endpoint && is_numeric($endpoint->project_id) ? (int) $endpoint->project_id : null;
        }

        if ($model instanceof FindingEvidence) {
            $finding = Finding::query()->find($model->getAttribute('finding_id'));

            return $finding && is_numeric($finding->project_id) ? (int) $finding->project_id : null;
        }

        if ($model instanceof TestCase) {
            $suite = TestSuite::query()->find($model->getAttribute('test_suite_id'));

            return $suite && is_numeric($suite->project_id) ? (int) $suite->project_id : null;
        }

        if ($model instanceof TestCaseResult) {
            $case = TestCase::query()->find($model->getAttribute('test_case_id'));
            if (! $case) {
                return null;
            }
            $suite = TestSuite::query()->find($case->test_suite_id);

            return $suite && is_numeric($suite->project_id) ? (int) $suite->project_id : null;
        }

        if ($model instanceof QaReleaseGateItem) {
            $gate = QaReleaseGate::query()->find($model->getAttribute('qa_release_gate_id'));

            return $gate && is_numeric($gate->project_id) ? (int) $gate->project_id : null;
        }

        if ($model instanceof ProjectSetting || $model instanceof ProjectMembership) {
            return is_numeric($model->project_id) ? (int) $model->project_id : null;
        }

        if ($model instanceof Setting || $model instanceof User) {
            return null;
        }

        return null;
    }
}
