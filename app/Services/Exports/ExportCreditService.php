<?php

namespace App\Services\Exports;

use App\Models\Project;
use App\Models\User;
use App\Services\Settings\SettingService;

class ExportCreditService
{
    /** @return array<string, mixed> */
    public function metadata(?string $exportType = null, ?Project $project = null): array
    {
        $identity = $this->reportIdentity();

        $metadata = [
            'application' => (string) config('aptoria.product_name', 'Aptoria'),
            'version' => (string) config('aptoria.version'),
            'description' => (string) config('aptoria.positioning', 'Self-hosted API QA, security review and regression monitoring platform.'),
            'repository' => (string) config('aptoria.repository_url', 'https://github.com/Szujo-Janos/Aptoria'),
            'author' => (string) config('aptoria.author', 'János Szujó'),
            'license' => (string) config('aptoria.license_summary', 'Source-available. Free for local evaluation and portfolio/demo use. Commercial redistribution, hosted resale or derivative product use requires written permission.'),
            'generated_at' => now()->toIso8601String(),
            'prepared_by' => $identity['prepared_by'],
        ];

        foreach (['role_title', 'organization', 'github_profile', 'website'] as $key) {
            if ($identity[$key] !== '') {
                $metadata[$key] = $identity[$key];
            }
        }

        if ($exportType !== null && $exportType !== '') {
            $metadata['export_type'] = $exportType;
        }

        if ($project instanceof Project) {
            $metadata['project'] = [
                'id' => $project->id,
                'name' => $project->name,
                'base_url' => $project->display_base_url,
            ];
        }

        return $metadata;
    }

    /** @return array{prepared_by: string, role_title: string, organization: string, github_profile: string, website: string} */
    public function reportIdentity(): array
    {
        $user = auth()->user();

        if ($user instanceof User) {
            return [
                'prepared_by' => $this->clean((string) ($user->report_display_name ?: $user->name ?: config('aptoria.author', 'János Szujó'))),
                'role_title' => $this->clean((string) ($user->report_role_title ?: '')),
                'organization' => $this->clean((string) ($user->report_organization ?: '')),
                'github_profile' => $this->clean((string) ($user->report_github_url ?: '')),
                'website' => $this->clean((string) ($user->report_website_url ?: '')),
            ];
        }

        return [
            'prepared_by' => (string) config('aptoria.author', 'János Szujó'),
            'role_title' => '',
            'organization' => '',
            'github_profile' => '',
            'website' => '',
        ];
    }

    public function markdownFooter(?string $exportType = null, ?Project $project = null): string
    {
        if (! $this->includeCopyrightFooter()) {
            return '';
        }

        $meta = $this->metadata($exportType, $project);
        $lines = [
            '---',
            '',
            'Generated with **'.$meta['application'].' v'.$meta['version'].'**',
            $meta['description'],
            'Repository: `'.$meta['repository'].'`',
            'Prepared by: **'.$meta['prepared_by'].'**',
            'Application author: **'.$meta['author'].'**',
            'License: '.$meta['license'],
        ];

        if (isset($meta['organization'])) {
            array_splice($lines, 6, 0, ['Organization: '.$meta['organization']]);
        }

        return implode("\n", $lines);
    }

    /** @param array<int, string> $lines */
    public function appendMarkdownFooter(array &$lines, ?string $exportType = null, ?Project $project = null): void
    {
        $footer = $this->markdownFooter($exportType, $project);

        if ($footer === '') {
            return;
        }

        foreach (explode("\n", $footer) as $line) {
            $lines[] = $line;
        }
    }

    public function textFile(?string $exportType = null, ?Project $project = null): string
    {
        if (! $this->includeCopyrightFooter()) {
            return '';
        }

        $meta = $this->metadata($exportType, $project);

        $lines = [
            $meta['application'].' v'.$meta['version'],
            '',
            $meta['description'],
            '',
            'Repository: '.$meta['repository'],
            'Prepared by: '.$meta['prepared_by'],
            'Application author: '.$meta['author'],
            'License: '.$meta['license'],
            'Generated at: '.$meta['generated_at'],
        ];

        foreach (['organization' => 'Organization', 'role_title' => 'Role / title', 'github_profile' => 'GitHub profile', 'website' => 'Website'] as $key => $label) {
            if (isset($meta[$key])) {
                $lines[] = $label.': '.$meta[$key];
            }
        }

        if (isset($meta['export_type'])) {
            $lines[] = 'Export type: '.$meta['export_type'];
        }

        if (isset($meta['project']) && is_array($meta['project'])) {
            $lines[] = 'Project: '.$meta['project']['name'].' (#'.$meta['project']['id'].')';
            if (($meta['project']['base_url'] ?? '') !== '') {
                $lines[] = 'Base URL: '.$meta['project']['base_url'];
            }
        }

        return implode("\n", $lines)."\n";
    }

    /** @return array<int, string> */
    public function footerLines(): array
    {
        if (! $this->includeCopyrightFooter()) {
            return [];
        }

        return [
            sprintf('Generated with %s v%s', (string) config('aptoria.product_name', 'Aptoria'), (string) config('aptoria.version')),
            (string) config('aptoria.positioning', 'Self-hosted API QA, security review and regression monitoring platform.'),
            sprintf('GitHub: %s · Author: %s', (string) config('aptoria.repository_url', 'https://github.com/Szujo-Janos/Aptoria'), (string) config('aptoria.author', 'János Szujó')),
            (string) config('aptoria.license_summary', 'Source-available. Free for local evaluation and portfolio/demo use. Commercial redistribution, hosted resale or derivative product use requires written permission.'),
        ];
    }

    public function shortLine(): string
    {
        if (! $this->includeCopyrightFooter()) {
            return '';
        }

        return sprintf(
            'Generated with %s v%s · GitHub: %s · Author: %s',
            (string) config('aptoria.product_name', 'Aptoria'),
            (string) config('aptoria.version'),
            (string) config('aptoria.repository_url', 'https://github.com/Szujo-Janos/Aptoria'),
            (string) config('aptoria.author', 'János Szujó')
        );
    }

    private function includeCopyrightFooter(): bool
    {
        return app(SettingService::class)->boolean('report.include_copyright_footer', true);
    }

    private function clean(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}
