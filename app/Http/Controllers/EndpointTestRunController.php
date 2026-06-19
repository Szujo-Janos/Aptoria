<?php

namespace App\Http\Controllers;

use App\Models\EndpointTestRun;
use App\Models\Project;
use Illuminate\View\View;

class EndpointTestRunController extends Controller
{
    public function show(Project $project, EndpointTestRun $endpointTestRun): View
    {
        abort_unless($endpointTestRun->project_id === $project->id, 404);

        $endpointTestRun->load(['endpoint', 'environment', 'authProfile', 'project', 'batch']);

        return view('endpoint_test_runs.show', [
            'project' => $project,
            'run' => $endpointTestRun,
            'evidenceMarkdown' => $this->evidenceMarkdown($project, $endpointTestRun),
        ]);
    }

    private function evidenceMarkdown(Project $project, EndpointTestRun $run): string
    {
        $endpointName = $run->endpoint?->name ?: __('messages.endpoints.unnamed');
        $endpointPath = $run->endpoint?->path ?: $run->url;
        $checkedAt = $run->checked_at?->toDateTimeString() ?: __('messages.common.not_available');

        $lines = [
            '# '. __('messages.endpoints.quick_test_evidence'),
            '',
            '- '. __('messages.projects.title') .': '. $project->name,
            '- '. __('messages.endpoints.endpoint') .': '. $endpointName,
            '- '. __('messages.endpoints.path') .': '. ($endpointPath ?: __('messages.common.not_available')),
            '- '. __('messages.endpoints.method') .': '. ($run->method ?: __('messages.common.not_available')),
            '- '. __('messages.common.url') .': '. ($run->url ?: __('messages.common.not_available')),
            '- '. __('messages.common.status') .': '. $run->state_label,
            '- '. __('messages.endpoints.quick_test_http_result') .': '. $run->status_summary,
            '- '. __('messages.endpoints.expected_status') .': '. ($run->expected_status ?: __('messages.common.not_available')),
            '- '. __('messages.endpoints.content_type') .': '. ($run->expected_content_type ?: __('messages.common.not_available')),
            '- '. __('messages.endpoints.actual_content_type') .': '. ($run->content_type ?: __('messages.common.not_available')),
            '- '. __('messages.endpoints.response_time') .': '. ($run->response_time_ms !== null ? $run->response_time_ms.' '.__('messages.common.milliseconds') : __('messages.common.not_available')),
            '- '. __('messages.endpoints.response_size') .': '. ($run->response_size !== null ? $run->response_size.' bytes' : __('messages.common.not_available')),
            '- '. __('messages.nav.environments') .': '. ($run->environment?->name ?: __('messages.auth_profiles.project_base_url')),
            '- '. __('messages.nav.auth_profiles') .': '. ($run->authProfile?->name ?: __('messages.auth_profiles.no_auth_preview')),
            '- '. __('messages.endpoints.checked_at') .': '. $checkedAt,
        ];

        if ($run->message) {
            $lines[] = '- '. __('messages.endpoints.result_message') .': '. $run->message;
        }

        if (($run->assertion_total ?? 0) > 0) {
            $lines[] = '';
            $lines[] = '## '. __('messages.assertions.title');
            $lines[] = '';
            $lines[] = '- '. __('messages.assertions.metrics.total') .': '. $run->assertion_total;
            $lines[] = '- '. __('messages.assertions.metrics.enabled') .': '. $run->assertion_passed;
            $lines[] = '- '. __('messages.release_decisions.failed_short') .': '. $run->assertion_failed;

            foreach (($run->assertion_summary_json['items'] ?? []) as $item) {
                $lines[] = '- '. (($item['passed'] ?? false) ? 'PASS' : 'FAIL') .' · '. ($item['name'] ?? __('messages.assertions.rule')) .' · '. ($item['actual'] ?? __('messages.common.not_available')) .' '. ($item['operator'] ?? '') .' '. ($item['expected'] ?? '');
            }
        }

        if ($run->body_preview) {
            $lines[] = '';
            $lines[] = '## '. __('messages.auth_profiles.body_preview');
            $lines[] = '';
            $lines[] = '```text';
            $lines[] = $run->body_preview;
            $lines[] = '```';
        }

        return implode("\n", $lines);
    }
}
