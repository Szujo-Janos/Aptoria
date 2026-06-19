<?php

namespace App\Services;

use App\Models\Finding;
use App\Models\FindingDuplicateCandidate;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FindingDeduplicationService
{
    public function scan(Project $project): array
    {
        if (! Schema::hasTable('finding_duplicate_candidates')) {
            return ['created' => 0, 'candidate_count' => 0];
        }

        $findings = $project->findings()
            ->whereNull('merged_into_finding_id')
            ->whereNotIn('status', ['verified'])
            ->with(['endpoint', 'evidence'])
            ->get();
        $created = 0;

        for ($i = 0; $i < $findings->count(); $i++) {
            for ($j = $i + 1; $j < $findings->count(); $j++) {
                $a = $findings[$i];
                $b = $findings[$j];
                $score = $this->score($a, $b);
                if ($score < 70) {
                    continue;
                }
                $primary = $this->canonical($a, $b);
                $duplicate = $primary->is($a) ? $b : $a;
                $signals = $this->signals($a, $b, $score);
                $candidate = FindingDuplicateCandidate::updateOrCreate([
                    'project_id' => $project->id,
                    'primary_finding_id' => $primary->id,
                    'duplicate_finding_id' => $duplicate->id,
                ], [
                    'score' => $score,
                    'status' => 'candidate',
                    'signals_json' => $signals,
                    'detected_at' => now(),
                ]);
                $created += $candidate->wasRecentlyCreated ? 1 : 0;
            }
        }

        return ['created' => $created, 'candidate_count' => $project->findingDuplicateCandidates()->where('status', 'candidate')->count()];
    }

    public function merge(Project $project, FindingDuplicateCandidate $candidate, ?User $user = null, ?string $note = null): Finding
    {
        abort_unless((int) $candidate->project_id === (int) $project->id, 404);
        abort_unless($candidate->status === 'candidate', 422);

        return DB::transaction(function () use ($candidate, $user, $note): Finding {
            $primary = $candidate->primaryFinding()->lockForUpdate()->firstOrFail();
            $duplicate = $candidate->duplicateFinding()->lockForUpdate()->firstOrFail();

            $duplicate->evidence()->update(['finding_id' => $primary->id]);
            $metadata = is_array($primary->metadata_json) ? $primary->metadata_json : [];
            $metadata['merged_sources'][] = [
                'finding_id' => $duplicate->id,
                'title' => $duplicate->title,
                'severity' => $duplicate->severity,
                'status' => $duplicate->status,
                'merged_at' => now()->toDateTimeString(),
                'note' => $note,
            ];
            $primary->update(['metadata_json' => $metadata]);

            $duplicate->update([
                'merged_into_finding_id' => $primary->id,
                'duplicate_group_key' => $this->groupKey($primary),
                'merged_at' => now(),
                'merged_by_user_id' => $user?->id,
                'status' => 'verified',
                'metadata_json' => array_merge(is_array($duplicate->metadata_json) ? $duplicate->metadata_json : [], [
                    'merged_into_finding_id' => $primary->id,
                    'merge_note' => $note,
                ]),
            ]);

            $candidate->update(['status' => 'merged', 'merged_at' => now()]);

            return $primary->refresh();
        });
    }

    public function dismiss(Project $project, FindingDuplicateCandidate $candidate): void
    {
        abort_unless((int) $candidate->project_id === (int) $project->id, 404);
        $candidate->update(['status' => 'dismissed']);
    }

    private function score(Finding $a, Finding $b): int
    {
        $score = 0;
        similar_text($this->normalize($a->title), $this->normalize($b->title), $titlePercent);
        $score += (int) round($titlePercent * 0.45);
        if ((int) $a->endpoint_id > 0 && (int) $a->endpoint_id === (int) $b->endpoint_id) { $score += 25; }
        if ($a->severity === $b->severity) { $score += 10; }
        if ($a->source === $b->source) { $score += 5; }
        if ($this->normalize((string) $a->summary) && $this->normalize((string) $a->summary) === $this->normalize((string) $b->summary)) { $score += 15; }
        return min(100, $score);
    }

    private function canonical(Finding $a, Finding $b): Finding
    {
        $rank = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
        if (($rank[$a->severity] ?? 0) !== ($rank[$b->severity] ?? 0)) {
            return ($rank[$a->severity] ?? 0) > ($rank[$b->severity] ?? 0) ? $a : $b;
        }
        return $a->created_at <= $b->created_at ? $a : $b;
    }

    private function signals(Finding $a, Finding $b, int $score): array
    {
        return [
            'score' => $score,
            'same_endpoint' => (int) $a->endpoint_id > 0 && (int) $a->endpoint_id === (int) $b->endpoint_id,
            'same_severity' => $a->severity === $b->severity,
            'same_source' => $a->source === $b->source,
            'title_a' => $a->title,
            'title_b' => $b->title,
        ];
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', Str::lower($value)) ?? '');
    }

    private function groupKey(Finding $finding): string
    {
        return sha1($finding->project_id.'|'.($finding->endpoint_id ?? 'none').'|'.$this->normalize($finding->title));
    }
}
