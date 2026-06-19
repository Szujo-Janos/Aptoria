<?php

namespace App\Http\Controllers;

use App\Models\EndpointTestBatch;
use App\Models\Project;
use Illuminate\View\View;

class EndpointTestBatchController extends Controller
{
    public function show(Project $project, EndpointTestBatch $endpointTestBatch): View
    {
        abort_unless((int) $endpointTestBatch->project_id === (int) $project->id, 404);

        $endpointTestBatch->load('project');
        $runs = $endpointTestBatch->testRuns()
            ->with(['endpoint', 'environment', 'authProfile'])
            ->latest('checked_at')
            ->latest()
            ->get();

        return view('endpoint_test_batches.show', [
            'project' => $project,
            'batch' => $endpointTestBatch,
            'runs' => $runs,
            'evidenceMarkdown' => $this->evidenceMarkdown($project, $endpointTestBatch, $runs),
        ]);
    }

    private function evidenceMarkdown(Project $project, EndpointTestBatch $batch, $runs): string
    {
        $lines = [
            '# '. __('messages.endpoints.batch_test_evidence'),
            '',
            '- '. __('messages.projects.title') .': '. $project->name,
            '- '. __('messages.endpoints.batch_test_total') .': '. $batch->total,
            '- '. __('messages.endpoints.batch_test_passed') .': '. $batch->passed,
            '- '. __('messages.endpoints.batch_test_warning') .': '. $batch->warning,
            '- '. __('messages.endpoints.batch_test_failed') .': '. $batch->failed,
            '- '. __('messages.endpoints.batch_test_skipped') .': '. $batch->skipped,
            '- '. __('messages.common.status') .': '. $batch->state_label,
            '- '. __('messages.endpoints.started_at') .': '. ($batch->started_at?->toDateTimeString() ?: __('messages.common.not_available')),
            '- '. __('messages.endpoints.completed_at') .': '. ($batch->completed_at?->toDateTimeString() ?: __('messages.common.not_available')),
            '- '. __('messages.endpoints.duration') .': '. $batch->duration_label,
            '',
            '## '. __('messages.endpoints.batch_test_runs_detail'),
            '',
        ];

        foreach ($runs as $run) {
            $endpointName = $run->endpoint?->name ?: __('messages.endpoints.unnamed');
            $endpointPath = $run->endpoint?->path ?: $run->url;
            $lines[] = '- ['.$run->state_label.'] '.$run->method.' '.$endpointName.' — '.($endpointPath ?: __('messages.common.not_available')).' — '.$run->status_summary;
        }

        return implode("\n", $lines);
    }
}
