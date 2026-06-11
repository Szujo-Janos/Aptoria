<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class HelpController extends Controller
{
    public function howItWorks(): View
    {
        return view('help.how-it-works', [
            'steps' => __('messages.how_it_works.steps'),
            'workflow' => __('messages.how_it_works.workflow'),
            'safetyRules' => __('messages.how_it_works.safety_rules'),
        ]);
    }

    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $sections = $this->sections();

        if ($query !== '') {
            $sections = array_values(array_filter($sections, function (array $section) use ($query): bool {
                return str_contains($this->searchHaystack($section), $this->lower($query));
            }));
        }

        return view('help.index', [
            'query' => $query,
            'sections' => $sections,
            'allSections' => $this->sections(),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function sections(): array
    {
        $sections = __('messages.help.sections');

        return is_array($sections) ? array_values($sections) : [];
    }

    private function searchHaystack(array $section): string
    {
        $parts = [
            $section['title'] ?? '',
            $section['summary'] ?? '',
            $section['keywords'] ?? '',
        ];

        foreach (($section['items'] ?? []) as $item) {
            $parts[] = $item['title'] ?? '';
            $parts[] = $item['body'] ?? '';

            foreach (($item['bullets'] ?? []) as $bullet) {
                $parts[] = $bullet;
            }
        }

        return $this->lower(implode(' ', array_filter($parts)));
    }

    private function lower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
    }
}

