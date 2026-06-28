<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class WorkspaceModeService
{
    public const LIVE = 'live';
    public const SANDBOX = 'sandbox';

    public static function normalize(?string $mode): string
    {
        return in_array($mode, [self::LIVE, self::SANDBOX], true) ? $mode : self::LIVE;
    }

    public function current(Request $request): string
    {
        if (! $request->hasSession()) {
            return self::LIVE;
        }

        return self::normalize($request->session()->get('workspace_mode', self::LIVE));
    }

    public function set(Request $request, string $mode): string
    {
        $mode = self::normalize($mode);

        if ($request->hasSession()) {
            $request->session()->put('workspace_mode', $mode);
        }

        return $mode;
    }

    public function projectType(?Project $project): string
    {
        return self::normalize($project?->workspace_type);
    }

    public function applyMode(Builder $query, string $mode): Builder
    {
        $mode = self::normalize($mode);

        if (! Schema::hasColumn('projects', 'workspace_type')) {
            return $mode === self::SANDBOX ? $query->whereRaw('1 = 0') : $query;
        }

        return $query->where('workspace_type', $mode);
    }

    public function matches(?Project $project, string $mode): bool
    {
        return $project instanceof Project && $this->projectType($project) === self::normalize($mode);
    }

    public function label(string $mode): string
    {
        return __('messages.workspace_mode.'.self::normalize($mode));
    }

    public function shortLabel(string $mode): string
    {
        return __('messages.workspace_mode.'.self::normalize($mode).'_short');
    }

    public function badgeClass(string $mode): string
    {
        return self::normalize($mode) === self::SANDBOX ? 'text-bg-warning' : 'text-bg-success';
    }

    public function softBadgeClass(string $mode): string
    {
        return self::normalize($mode) === self::SANDBOX ? 'badge-soft-warning' : 'badge-soft-success';
    }
}
