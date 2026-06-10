<?php

namespace App\Services\Exports;

use App\Models\Project;
use App\Models\User;
use App\Services\Settings\SettingService;
use Illuminate\Support\Facades\Storage;

class ExportCreditService
{
    /** @return array<string, mixed> */
    public function metadata(?string $exportType = null, ?Project $project = null): array
    {
        $identity = $this->reportIdentity($project);

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

        foreach (['role_title', 'organization', 'client_name', 'confidentiality_label', 'disclaimer', 'github_profile', 'website'] as $key) {
            if (($identity[$key] ?? '') !== '') {
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
                'branding_override' => $project->hasProjectReportBranding(),
                'logo_configured' => $this->logoDataUri($project) !== '',
            ];
        }

        return $metadata;
    }

    /** @return array{prepared_by: string, role_title: string, organization: string, client_name: string, confidentiality_label: string, disclaimer: string, github_profile: string, website: string, logo_data_uri: string, logo_original_name: string} */
    public function reportIdentity(?Project $project = null): array
    {
        $user = auth()->user();
        $base = $this->userReportIdentity($user instanceof User ? $user : null);

        if ($project instanceof Project) {
            $client = $this->clean((string) ($project->report_client_name ?: ''));
            $organization = $this->clean((string) ($project->report_organization ?: ''));
            $preparedBy = $this->clean((string) ($project->report_prepared_by ?: ''));
            $roleTitle = $this->clean((string) ($project->report_role_title ?: ''));
            $confidentiality = $this->clean((string) ($project->report_confidentiality_label ?: ''));
            $disclaimer = trim((string) ($project->report_disclaimer ?: ''));

            return [
                'prepared_by' => $preparedBy !== '' ? $preparedBy : $base['prepared_by'],
                'role_title' => $roleTitle !== '' ? $roleTitle : $base['role_title'],
                'organization' => $organization !== '' ? $organization : ($client !== '' ? $client : $base['organization']),
                'client_name' => $client,
                'confidentiality_label' => $confidentiality,
                'disclaimer' => $disclaimer,
                'github_profile' => $base['github_profile'],
                'website' => $base['website'],
                'logo_data_uri' => $this->logoDataUri($project),
                'logo_original_name' => $this->clean((string) ($project->report_logo_original_name ?: '')),
            ];
        }

        return $base + [
            'client_name' => '',
            'confidentiality_label' => '',
            'disclaimer' => '',
            'logo_data_uri' => '',
            'logo_original_name' => '',
        ];
    }

    /** @return array{prepared_by: string, role_title: string, organization: string, github_profile: string, website: string} */
    private function userReportIdentity(?User $user): array
    {
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

    /** @return array<int, string> */
    public function projectBrandingMarkdownLines(Project $project): array
    {
        $identity = $this->reportIdentity($project);
        $lines = [];

        if ($identity['client_name'] !== '') {
            $lines[] = '**Client:** '.$identity['client_name'];
        }
        if ($identity['organization'] !== '') {
            $lines[] = '**Organization / client:** '.$identity['organization'];
        }
        if ($identity['prepared_by'] !== '') {
            $lines[] = '**Prepared by:** '.$identity['prepared_by'];
        }
        if ($identity['role_title'] !== '') {
            $lines[] = '**Role / title:** '.$identity['role_title'];
        }
        if ($identity['confidentiality_label'] !== '') {
            $lines[] = '**Confidentiality:** '.$identity['confidentiality_label'];
        }
        if ($identity['logo_original_name'] !== '') {
            $lines[] = '**Report logo:** '.$identity['logo_original_name'];
        }

        return $lines;
    }

    public function projectDisclaimerMarkdown(Project $project): string
    {
        return trim((string) ($this->reportIdentity($project)['disclaimer'] ?? ''));
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

        if (isset($meta['client_name'])) {
            array_splice($lines, 6, 0, ['Client: '.$meta['client_name']]);
        }
        if (isset($meta['organization'])) {
            array_splice($lines, 6, 0, ['Organization: '.$meta['organization']]);
        }
        if (isset($meta['confidentiality_label'])) {
            array_splice($lines, 6, 0, ['Confidentiality: '.$meta['confidentiality_label']]);
        }
        if (isset($meta['disclaimer'])) {
            $lines[] = 'Disclaimer: '.$meta['disclaimer'];
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

        foreach (['client_name' => 'Client', 'organization' => 'Organization', 'confidentiality_label' => 'Confidentiality', 'role_title' => 'Role / title', 'github_profile' => 'GitHub profile', 'website' => 'Website', 'disclaimer' => 'Disclaimer'] as $key => $label) {
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
            $lines[] = 'Project branding override: '.(($meta['project']['branding_override'] ?? false) ? 'yes' : 'no');
            $lines[] = 'Project logo configured: '.(($meta['project']['logo_configured'] ?? false) ? 'yes' : 'no');
        }

        return implode("\n", $lines)."\n";
    }

    /** @return array<int, string> */
    public function footerLines(?Project $project = null): array
    {
        if (! $this->includeCopyrightFooter()) {
            return [];
        }

        $identity = $this->reportIdentity($project);
        $owner = $identity['organization'] !== '' ? $identity['organization'] : (string) config('aptoria.author', 'János Szujó');

        return [
            sprintf('Generated with %s v%s', (string) config('aptoria.product_name', 'Aptoria'), (string) config('aptoria.version')),
            (string) config('aptoria.positioning', 'Self-hosted API QA, security review and regression monitoring platform.'),
            sprintf('GitHub: %s · Prepared by: %s · Owner/client: %s', (string) config('aptoria.repository_url', 'https://github.com/Szujo-Janos/Aptoria'), $identity['prepared_by'], $owner),
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

    public function logoDataUri(Project $project): string
    {
        $path = (string) ($project->report_logo_path ?: '');
        if ($path === '' || ! Storage::exists($path)) {
            return '';
        }

        $mime = Storage::mimeType($path) ?: $this->mimeFromPath($path);
        $content = Storage::get($path);

        return 'data:'.$mime.';base64,'.base64_encode($content);
    }

    private function mimeFromPath(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'image/png',
        };
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
