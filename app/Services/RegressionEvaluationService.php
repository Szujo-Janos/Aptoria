<?php

namespace App\Services;

use App\Models\CompareItem;
use App\Models\CompareRun;
use App\Models\Endpoint;
use Illuminate\Support\Collection;

class RegressionEvaluationService
{
    public const STATUS_NONE = 'none';
    public const STATUS_IMPROVED = 'improved';
    public const STATUS_RECOVERED = 'recovered';
    public const STATUS_WARNING = 'warning';
    public const STATUS_DETECTED = 'detected';

    /**
     * @return array{
     *     status: string,
     *     label: string,
     *     css: string,
     *     results: array<int, array<string, mixed>>,
     *     endpoints: array<string, array<string, mixed>>,
     *     detected_count: int,
     *     warning_count: int,
     *     recovered_count: int,
     *     improved_count: int
     * }
     */
    public function evaluateCompare(CompareRun $compareRun): array
    {
        $compareRun->loadMissing('items');
        $results = $compareRun->items
            ->map(fn (CompareItem $item): array => $this->evaluateItem($item))
            ->filter(fn (array $result): bool => $result['status'] !== self::STATUS_NONE)
            ->values()
            ->all();

        $endpoints = collect($results)
            ->groupBy('endpoint_key')
            ->map(fn (Collection $items): array => $this->endpointSummary($items))
            ->all();

        $status = $this->aggregateStatus(collect($endpoints)->pluck('status'));

        return [
            'status' => $status,
            'label' => $this->statusLabel($status),
            'css' => $this->statusCss($status),
            'results' => $results,
            'endpoints' => $endpoints,
            'detected_count' => collect($endpoints)->where('status', self::STATUS_DETECTED)->count(),
            'warning_count' => collect($endpoints)->where('status', self::STATUS_WARNING)->count(),
            'recovered_count' => collect($endpoints)->where('status', self::STATUS_RECOVERED)->count(),
            'improved_count' => collect($endpoints)->where('status', self::STATUS_IMPROVED)->count(),
        ];
    }

    /** @return array<string, mixed> */
    public function latestForEndpoint(Endpoint $endpoint): array
    {
        $compareRun = $endpoint->project?->compareRuns()->latest()->first();

        if (! $compareRun) {
            return $this->emptyEndpointSummary();
        }

        $key = $this->endpointKey($endpoint->method, $endpoint->path);

        return $this->evaluateCompare($compareRun)['endpoints'][$key] ?? $this->emptyEndpointSummary();
    }

    /** @return array<string, mixed> */
    private function evaluateItem(CompareItem $item): array
    {
        $status = self::STATUS_NONE;
        $reason = '';

        if ($item->change_type === CompareItem::TYPE_REMOVED) {
            $status = self::STATUS_DETECTED;
            $reason = __('messages.regressions.reasons.endpoint_removed');
        } elseif ($item->change_type === CompareItem::TYPE_NEW) {
            $status = self::STATUS_IMPROVED;
            $reason = __('messages.regressions.reasons.endpoint_added');
        } elseif ($item->field_changed === 'status_code') {
            $old = $this->numericValue($item->old_value);
            $new = $this->numericValue($item->new_value);

            if ($new === null && $old !== null) {
                $status = self::STATUS_DETECTED;
                $reason = __('messages.regressions.reasons.endpoint_unavailable');
            } elseif ($new !== null && $new >= 500 && ($old === null || $old < 500)) {
                $status = self::STATUS_DETECTED;
                $reason = __('messages.regressions.reasons.status_5xx', ['old' => $item->old_value, 'new' => $item->new_value]);
            } elseif ($new !== null && $new >= 400 && ($old === null || $old < 400)) {
                $status = self::STATUS_WARNING;
                $reason = __('messages.regressions.reasons.status_worse', ['old' => $item->old_value, 'new' => $item->new_value]);
            } elseif ($new !== null && $new < 400 && ($old === null || $old >= 400)) {
                $status = self::STATUS_RECOVERED;
                $reason = __('messages.regressions.reasons.status_recovered', ['old' => $item->old_value, 'new' => $item->new_value]);
            }
        } elseif ($item->field_changed === 'response_time_ms') {
            $old = $this->numericValue($item->old_value);
            $new = $this->numericValue($item->new_value);

            if ($old !== null && $new !== null && $new > $old) {
                $status = $item->severity === CompareItem::SEVERITY_HIGH ? self::STATUS_DETECTED : self::STATUS_WARNING;
                $reason = __('messages.regressions.reasons.response_time_increased', ['old' => $item->old_value, 'new' => $item->new_value]);
            } elseif ($old !== null && $new !== null && $new < $old) {
                // Faster response time is useful context, but it is not a regression signal.
                // Keep the endpoint status neutral so regression summaries focus on risk.
                $status = self::STATUS_NONE;
                $reason = '';
            }
        } elseif ($item->field_changed === 'risk_level') {
            $oldRank = $this->riskRank((string) $item->old_value);
            $newRank = $this->riskRank((string) $item->new_value);

            if ($newRank > $oldRank && in_array($item->severity, [CompareItem::SEVERITY_CRITICAL, CompareItem::SEVERITY_HIGH, CompareItem::SEVERITY_REVIEW], true)) {
                $status = in_array($item->severity, [CompareItem::SEVERITY_CRITICAL, CompareItem::SEVERITY_HIGH], true) ? self::STATUS_DETECTED : self::STATUS_WARNING;
                $reason = __('messages.regressions.reasons.risk_increased', ['old' => $item->old_value, 'new' => $item->new_value]);
            } elseif ($oldRank > $newRank) {
                $status = self::STATUS_IMPROVED;
                $reason = __('messages.regressions.reasons.risk_improved', ['old' => $item->old_value, 'new' => $item->new_value]);
            }
        } elseif ($item->field_changed === 'risk_score') {
            $old = $this->numericValue($item->old_value);
            $new = $this->numericValue($item->new_value);

            if ($old !== null && $new !== null && $new > $old) {
                $status = $item->severity === CompareItem::SEVERITY_HIGH ? self::STATUS_DETECTED : self::STATUS_WARNING;
                $reason = __('messages.regressions.reasons.risk_score_increased', ['old' => $item->old_value, 'new' => $item->new_value]);
            } elseif ($old !== null && $new !== null && $new < $old) {
                $status = self::STATUS_IMPROVED;
                $reason = __('messages.regressions.reasons.risk_score_improved', ['old' => $item->old_value, 'new' => $item->new_value]);
            }
        } elseif ($item->field_changed === 'scheme') {
            $oldScheme = strtolower((string) $item->old_value);
            $newScheme = strtolower((string) $item->new_value);

            if ($oldScheme === 'https' && $newScheme === 'http') {
                $status = self::STATUS_DETECTED;
                $reason = __('messages.regressions.reasons.https_downgrade');
            } elseif ($oldScheme === 'http' && $newScheme === 'https') {
                $status = self::STATUS_IMPROVED;
                $reason = __('messages.regressions.reasons.https_upgrade');
            }
        } elseif ($item->field_changed === 'security_header') {
            if (strtolower((string) $item->new_value) === strtolower(__('messages.snapshots.values.missing'))) {
                $status = self::STATUS_DETECTED;
                $reason = __('messages.regressions.reasons.security_header_missing', ['header' => $item->old_value]);
            } else {
                $status = self::STATUS_IMPROVED;
                $reason = __('messages.regressions.reasons.security_header_added', ['header' => $item->new_value]);
            }
        }

        return [
            'endpoint_key' => $this->endpointKey($item->method, $item->path),
            'method' => $item->method,
            'path' => $item->path,
            'status' => $status,
            'label' => $this->statusLabel($status),
            'css' => $this->statusCss($status),
            'reason' => $reason,
            'compare_item_id' => $item->id,
        ];
    }

    /** @return array<string, mixed> */
    private function endpointSummary(Collection $items): array
    {
        $status = $this->aggregateStatus($items->pluck('status'));

        return [
            'status' => $status,
            'label' => $this->statusLabel($status),
            'css' => $this->statusCss($status),
            'reasons' => $items->pluck('reason')->filter()->unique()->values()->all(),
        ];
    }

    private function aggregateStatus(Collection $statuses): string
    {
        if ($statuses->contains(self::STATUS_DETECTED)) {
            return self::STATUS_DETECTED;
        }

        if ($statuses->contains(self::STATUS_WARNING)) {
            return self::STATUS_WARNING;
        }

        if ($statuses->contains(self::STATUS_RECOVERED)) {
            return self::STATUS_RECOVERED;
        }

        if ($statuses->contains(self::STATUS_IMPROVED)) {
            return self::STATUS_IMPROVED;
        }

        return self::STATUS_NONE;
    }

    /** @return array<string, mixed> */
    private function emptyEndpointSummary(): array
    {
        return [
            'status' => self::STATUS_NONE,
            'label' => $this->statusLabel(self::STATUS_NONE),
            'css' => $this->statusCss(self::STATUS_NONE),
            'reasons' => [],
        ];
    }

    private function endpointKey(string $method, string $path): string
    {
        return strtoupper($method).' '.strtolower($path);
    }

    private function numericValue(?string $value): ?int
    {
        if ($value === null || ! preg_match('/\d+/', $value, $matches)) {
            return null;
        }

        return (int) $matches[0];
    }

    private function riskRank(string $riskLabelOrLevel): int
    {
        $value = strtolower($riskLabelOrLevel);
        $labels = [
            strtolower(__('messages.endpoints.risks.low')) => 1,
            strtolower(__('messages.endpoints.risks.public')) => 2,
            strtolower(__('messages.endpoints.risks.review')) => 3,
            strtolower(__('messages.endpoints.risks.high')) => 4,
            strtolower(__('messages.endpoints.risks.critical')) => 5,
        ];

        $levels = [
            Endpoint::RISK_LOW => 1,
            Endpoint::RISK_PUBLIC => 2,
            Endpoint::RISK_REVIEW => 3,
            Endpoint::RISK_HIGH => 4,
            Endpoint::RISK_CRITICAL => 5,
        ];

        return $levels[$value] ?? $labels[$value] ?? 0;
    }

    private function statusLabel(string $status): string
    {
        return __('messages.regressions.statuses.'.$status);
    }

    private function statusCss(string $status): string
    {
        return match ($status) {
            self::STATUS_DETECTED => 'danger',
            self::STATUS_WARNING => 'warning',
            self::STATUS_RECOVERED => 'success',
            self::STATUS_IMPROVED => 'info',
            default => 'success',
        };
    }
}
