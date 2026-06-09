<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Calendar\CalendarActivityLogger;
use App\Services\Setup\EnvironmentCheckService;
use App\Services\Setup\SetupStateService;
use Database\Seeders\DemoQaProjectSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class SetupController extends Controller
{
    public function __construct(
        private readonly EnvironmentCheckService $environmentCheck,
        private readonly SetupStateService $setupState,
    ) {
    }

    public function index(): View
    {
        $migrationsReady = Schema::hasTable('projects');

        return view('setup.index', [
            'report' => $this->environmentCheck->report(),
            'setupState' => $this->setupState,
            'isInstalled' => $this->setupState->isInstalled(),
            'installationHint' => $this->setupState->installationHint(),
            'migrationsReady' => $migrationsReady,
            'demoImported' => $migrationsReady
                && \App\Models\Project::query()->where('slug', DemoQaProjectSeeder::PROJECT_SLUG)->exists(),
        ]);
    }

    public function createEnv(): RedirectResponse
    {
        $this->guardUnlockedSetup();

        $env = base_path('.env');
        $example = base_path('.env.example');

        if (is_file($env)) {
            return back()->with('success', __('messages.setup.env_already_exists'));
        }

        if (! is_file($example)) {
            return back()->withErrors(['setup' => __('messages.setup.env_example_missing')]);
        }

        File::copy($example, $env);

        return back()->with('success', __('messages.setup.env_created'));
    }

    public function createSqlite(): RedirectResponse
    {
        $this->guardUnlockedSetup();

        $databasePath = database_path('database.sqlite');
        File::ensureDirectoryExists(dirname($databasePath));

        if (! is_file($databasePath)) {
            File::put($databasePath, '');
        }

        return back()->with('success', __('messages.setup.sqlite_created'));
    }

    public function generateKey(): RedirectResponse
    {
        $this->guardUnlockedSetup();

        try {
            Artisan::call('key:generate', ['--force' => true]);
        } catch (Throwable $exception) {
            return back()->withErrors(['setup' => $exception->getMessage()]);
        }

        return back()->with('success', __('messages.setup.key_generated'));
    }

    public function migrate(): RedirectResponse
    {
        $this->guardUnlockedSetup();

        try {
            CalendarActivityLogger::withoutRecording(fn () => Artisan::call('migrate', ['--seed' => true, '--force' => true]));
        } catch (Throwable $exception) {
            return back()->withErrors(['setup' => $exception->getMessage()]);
        }

        return back()->with('success', __('messages.setup.migration_done'));
    }

    public function createAdmin(Request $request): RedirectResponse
    {
        $this->guardUnlockedSetup();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Schema::hasTable('users')) {
            return back()->withErrors(['setup' => __('messages.setup.users_table_missing')]);
        }

        CalendarActivityLogger::withoutRecording(function () use ($data): void {
            User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                    'role' => 'admin',
                ]
            );
        });

        return back()->with('success', __('messages.setup.admin_created'));
    }

    public function importDemo(): RedirectResponse
    {
        $this->guardUnlockedSetup();

        if (! Schema::hasTable('projects')) {
            return back()->withErrors(['setup' => __('messages.setup.demo_requires_migrations')]);
        }

        try {
            CalendarActivityLogger::withoutRecording(fn () => Artisan::call('db:seed', [
                '--class' => DemoQaProjectSeeder::class,
                '--force' => true,
            ]));
        } catch (Throwable $exception) {
            return back()->withErrors(['setup' => $exception->getMessage()]);
        }

        return back()->with('success', __('messages.setup.demo_imported'));
    }

    public function finish(Request $request): RedirectResponse
    {
        $this->guardUnlockedSetup();

        if (! $request->boolean('confirm')) {
            return back()->withErrors(['setup' => __('messages.setup.finish_confirm_required')]);
        }

        if (! Schema::hasTable('users') || ! User::query()->exists()) {
            return back()->withErrors(['setup' => __('messages.setup.admin_required_before_finish')]);
        }

        $this->setupState->writeLock('web-setup');

        return redirect()->route('login')->with('success', __('messages.setup.finished'));
    }

    private function guardUnlockedSetup(): void
    {
        abort_if($this->setupState->isLocked(), 403, __('messages.setup.locked'));
    }
}
