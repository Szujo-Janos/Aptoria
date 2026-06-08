<?php

namespace App\Services\Calendar;

use App\Models\ApiMonitor;
use App\Models\CalendarEvent;
use App\Models\Endpoint;
use App\Models\EndpointAssertionRule;
use App\Models\EndpointPathParameter;
use App\Models\Environment;
use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\MonitorAlertEvent;
use App\Models\Project;
use App\Models\QaReleaseGate;
use App\Models\QaReleaseGateItem;
use App\Models\ScanRun;
use App\Models\Snapshot;
use App\Models\TestCase;
use App\Models\TestCaseResult;
use App\Models\TestSuite;
use Illuminate\Database\Eloquent\Model;
use App\Services\Settings\SettingService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class CalendarActivityLogger
{
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';

    private static int $suppressionDepth = 0;

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

    public function record(string $action, Model $model): void
    {
        if (! app(SettingService::class)->boolean('security.enable_audit_log', true)) {
            return;
        }

        if (self::isSuppressed() || ! $this->canRecord($model)) {
            return;
        }

        try {
            CalendarEvent::withoutEvents(function () use ($action, $model): void {
                CalendarEvent::query()->create([
                    'project_id' => $action === self::ACTION_DELETED && $model instanceof Project ? null : $this->projectId($model),
                    'created_by' => auth()->id(),
                    'title' => $this->title($action, $model),
                    'description' => $this->description($action, $model),
                    'event_type' => CalendarEvent::TYPE_ACTIVITY_LOG,
                    'status' => CalendarEvent::STATUS_COMPLETED,
                    'priority' => CalendarEvent::PRIORITY_LOW,
                    'starts_at' => now(),
                    'ends_at' => null,
                    'all_day' => false,
                    'completed_at' => now(),
                    'is_system_locked' => true,
                    'activity_action' => $action,
                    'activity_subject_type' => $model::class,
                    'activity_subject_id' => $this->subjectId($model),
                    'activity_route' => $this->currentRoute(),
                    'activity_payload' => $this->payload($model),
                ]);
            });
        } catch (Throwable) {
            // Calendar activity logging must never block the business action that triggered it.
        }
    }

    private function canRecord(Model $model): bool
    {
        if ($model instanceof CalendarEvent && ($model->is_system_locked || $model->event_type === CalendarEvent::TYPE_ACTIVITY_LOG)) {
            return false;
        }

        try {
            return Schema::hasTable('calendar_events') && Schema::hasColumn('calendar_events', 'is_system_locked');
        } catch (Throwable) {
            return false;
        }
    }

    private function title(string $action, Model $model): string
    {
        return trans('messages.calendar.activity_title', [
            'action' => trans('messages.calendar.activity_actions.'.$action),
            'subject' => $this->subjectLabel($model),
            'name' => $this->subjectName($model),
        ]);
    }

    private function description(string $action, Model $model): string
    {
        return trans('messages.calendar.activity_description', [
            'action' => trans('messages.calendar.activity_actions.'.$action),
            'subject' => $this->subjectLabel($model),
            'id' => (string) ($this->subjectId($model) ?? 'n/a'),
        ]);
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
                return Str::limit(trim($value), 90);
            }
        }

        if ($model->getAttribute('method') || $model->getAttribute('path')) {
            return trim((string) $model->getAttribute('method').' '.(string) $model->getAttribute('path')) ?: '#'.$this->subjectId($model);
        }

        if ($model instanceof EndpointAssertionRule) {
            return Str::limit((string) ($model->name ?: $model->assertion_type ?: 'assertion rule'), 90);
        }

        return '#'.($this->subjectId($model) ?? 'n/a');
    }

    private function subjectId(Model $model): ?int
    {
        $key = $model->getKey();

        return is_numeric($key) ? (int) $key : null;
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

        if ($model instanceof Environment || $model instanceof ApiMonitor || $model instanceof Finding || $model instanceof QaReleaseGate || $model instanceof Snapshot || $model instanceof ScanRun || $model instanceof TestSuite || $model instanceof MonitorAlertEvent) {
            return is_numeric($model->project_id) ? (int) $model->project_id : null;
        }

        if ($model instanceof Endpoint || $model instanceof EndpointAssertionRule || $model instanceof EndpointPathParameter) {
            $endpoint = $model instanceof Endpoint ? $model : Endpoint::query()->find($model->getAttribute('endpoint_id'));

            return $endpoint && is_numeric($endpoint->project_id) ? (int) $endpoint->project_id : null;
        }

        if ($model instanceof TestCase) {
            $suite = TestSuite::query()->find($model->getAttribute('test_suite_id'));

            return $suite && is_numeric($suite->project_id) ? (int) $suite->project_id : null;
        }

        if ($model instanceof TestCaseResult) {
            $case = TestCase::query()->find($model->getAttribute('test_case_id'));
            if ($case) {
                $suite = TestSuite::query()->find($case->test_suite_id);

                return $suite && is_numeric($suite->project_id) ? (int) $suite->project_id : null;
            }
        }

        if ($model instanceof FindingEvidence) {
            $finding = Finding::query()->find($model->getAttribute('finding_id'));

            return $finding && is_numeric($finding->project_id) ? (int) $finding->project_id : null;
        }

        if ($model instanceof QaReleaseGateItem) {
            $gate = QaReleaseGate::query()->find($model->getAttribute('qa_release_gate_id'));

            return $gate && is_numeric($gate->project_id) ? (int) $gate->project_id : null;
        }

        return null;
    }

    private function currentRoute(): ?string
    {
        try {
            return request()?->fullUrl();
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array<string, mixed> */
    private function payload(Model $model): array
    {
        return collect($model->getAttributes())
            ->except(['password', 'remember_token'])
            ->map(function ($value) {
                if (is_scalar($value) || $value === null) {
                    return $value;
                }

                return '[complex]';
            })
            ->take(30)
            ->all();
    }
}
