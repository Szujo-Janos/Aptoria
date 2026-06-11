<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\BlindSpots\QaBlindSpotDetectorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QaBlindSpotController extends Controller
{
    public function __invoke(Request $request, Project $project, QaBlindSpotDetectorService $blindSpots): View
    {
        $data = $request->validate([
            'severity' => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
            'module' => ['nullable', 'string'],
            'blockers' => ['nullable', 'boolean'],
        ]);

        $summary = $blindSpots->summarize($project);
        $filters = [
            'severity' => (string) ($data['severity'] ?? ''),
            'category' => (string) ($data['category'] ?? ''),
            'module' => (string) ($data['module'] ?? ''),
            'blockers' => (bool) ($data['blockers'] ?? false),
        ];

        $items = $summary['items']
            ->filter(function (array $item) use ($filters): bool {
                if ($filters['severity'] !== '' && $item['severity'] !== $filters['severity']) {
                    return false;
                }
                if ($filters['category'] !== '' && $item['category'] !== $filters['category']) {
                    return false;
                }
                if ($filters['module'] !== '' && $item['module'] !== $filters['module']) {
                    return false;
                }
                if ($filters['blockers'] && ! (bool) $item['release_blocker']) {
                    return false;
                }

                return true;
            })
            ->values();

        $filterOptions = [
            'severities' => [
                QaBlindSpotDetectorService::SEVERITY_CRITICAL,
                QaBlindSpotDetectorService::SEVERITY_HIGH,
                QaBlindSpotDetectorService::SEVERITY_MEDIUM,
                QaBlindSpotDetectorService::SEVERITY_LOW,
            ],
            'categories' => array_keys($summary['by_category']),
            'modules' => array_keys($summary['by_module']),
        ];

        return view('blind_spots.index', compact('project', 'summary', 'items', 'filters', 'filterOptions'));
    }
}
