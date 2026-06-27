<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DeploymentReadinessService;
use App\Services\EnvironmentCheckService;
use App\Services\SetupAccessService;
use App\Services\SecurityHardeningService;
use App\Services\SetupAdminPolicyService;
use App\Services\SetupStateService;
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
        private readonly DeploymentReadinessService $deploymentReadiness,
        private readonly SetupStateService $setupState,
        private readonly SetupAdminPolicyService $setupAdminPolicy,
        private readonly SetupAccessService $setupAccess,
        private readonly SecurityHardeningService $securityHardening,
    ) {
    }

    public function index(Request $request): View
    {
        $access = $this->setupAccess->accessContext($request);

        if (! $access['has_valid_access']) {
            return view('setup.denied', [
                'access' => $access,
                'checks' => $this->environmentCheck->report()['checks'],
            ]);
        }

        $migrationsReady = $this->hasTable('projects');

        return view('setup.index', [
            'report' => $this->environmentCheck->report(),
            'setupState' => $this->setupState,
            'isInstalled' => $this->setupState->isInstalled(),
            'installationHint' => $this->setupState->installationHint(),
            'migrationsReady' => $migrationsReady,
            'demoImported' => false,
            'setupLockBlockers' => $this->localizedSetupLockBlockers(),
            'blockedAdminEmails' => $this->setupAdminPolicy->adminUsersWithUnsafeBlockedPasswords()->pluck('email')->all(),
            'activeSetupStep' => $this->setupStepFromRequest($request),
            'defaultAdminName' => (string) config('aptoria.default_admin.name', 'Aptoria Admin'),
            'defaultAdminEmail' => (string) config('aptoria.default_admin.email', 'admin@example.com'),
            'defaultAdminPassword' => (string) config('aptoria.default_admin.password', 'change-me-now'),
            'bootstrapAdminExists' => $this->setupAdminPolicy->hasAdminUser(),
            'automaticActionStatuses' => $this->automaticActionStatuses($migrationsReady),
            'access' => $access,
            'securityChecklist' => $this->securityHardening->checklist(),
            'deploymentPreflight' => $this->deploymentReadiness->run('installer'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->install($request);
    }

    public function createEnv(): RedirectResponse
    {
        $this->guardUnlockedSetup();

        $env = base_path('.env');
        $example = base_path('.env.example');

        if (is_file($env)) {
            return $this->toSetupStep('config')->with('success', __('messages.setup.env_already_exists'));
        }

        if (! is_file($example)) {
            return $this->toSetupStep('config')->withErrors(['setup' => __('messages.setup.env_example_missing')]);
        }

        File::copy($example, $env);

        return $this->toSetupStep('config')->with('success', __('messages.setup.env_created'));
    }

    public function createSqlite(): RedirectResponse
    {
        $this->guardUnlockedSetup();

        try {
            $this->ensureSqliteDatabaseFile();
        } catch (Throwable $exception) {
            return $this->toSetupStep('config')->withErrors(['setup' => $exception->getMessage()]);
        }

        return $this->toSetupStep('config')->with('success', __('messages.setup.sqlite_created'));
    }

    public function generateKey(): RedirectResponse
    {
        $this->guardUnlockedSetup();

        try {
            $this->ensureEnvFile();
            Artisan::call('key:generate', ['--force' => true]);
        } catch (Throwable $exception) {
            return $this->toSetupStep('config')->withErrors(['setup' => $exception->getMessage()]);
        }

        return $this->toSetupStep('config')->with('success', __('messages.setup.key_generated'));
    }

    public function migrate(): RedirectResponse
    {
        $this->guardUnlockedSetup();

        try {
            $this->prepareRuntimeFiles();
            Artisan::call('migrate', ['--force' => true]);
        } catch (Throwable $exception) {
            return $this->toSetupStep('config')->withErrors(['setup' => $exception->getMessage()]);
        }

        return $this->toSetupStep('config')->with('success', __('messages.setup.migration_done'));
    }

    public function createAdmin(Request $request): RedirectResponse
    {
        $this->guardUnlockedSetup();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'password' => $this->setupAdminPolicy->adminPasswordRules(),
        ]);

        if (! Schema::hasTable('users')) {
            return $this->toSetupStep('install')->withErrors(['setup' => __('messages.setup.users_table_missing')]);
        }

        $updates = [
            'name' => $data['name'],
            'password' => Hash::make($data['password']),
            'role' => 'admin',
        ];

        if (Schema::hasColumn('users', 'password_change_required')) {
            $updates['password_change_required'] = false;
        }
        if (Schema::hasColumn('users', 'locale')) {
            $updates['locale'] = app()->getLocale();
        }
        if (Schema::hasColumn('users', 'timezone')) {
            $updates['timezone'] = config('app.timezone', 'Europe/Budapest');
        }

        User::query()->updateOrCreate(['email' => $data['email']], $updates);

        return $this->toSetupStep('admin')->with('success', __('messages.setup.admin_created'));
    }

    public function finish(Request $request): RedirectResponse
    {
        return $this->install($request);
    }

    public function install(Request $request): RedirectResponse
    {
        $this->guardUnlockedSetup();

        if (! $request->boolean('confirm')) {
            return $this->toSetupStep('install')->withErrors(['setup' => __('messages.setup.install_confirm_required')]);
        }

        $preflight = $this->deploymentReadiness->run('installer');
        if ((bool) ($preflight['install_blocked'] ?? false)) {
            $firstBlocker = $preflight['blocking_checks'][0]['remediation'] ?? 'Fix installer preflight blockers before continuing.';
            return $this->toSetupStep('install')->withErrors(['setup' => 'Installer preflight blocked installation. '.$firstBlocker]);
        }

        try {
            $this->prepareRuntimeFiles();
            Artisan::call('migrate', ['--force' => true]);
            $this->bootstrapDefaultAdmin();
        } catch (Throwable $exception) {
            return $this->toSetupStep('install')->withErrors(['setup' => $exception->getMessage()]);
        }

        $setupLockBlockers = $this->localizedSetupLockBlockers();

        if ($setupLockBlockers !== []) {
            return $this->toSetupStep('install')->withErrors(['setup' => implode(' ', $setupLockBlockers)]);
        }

        $this->setupState->writeLock('guided-web-installer');

        return redirect()->route('login')
            ->with('status', __('messages.setup.finished'))
            ->with('warning', __('messages.setup.default_admin_after_install'));
    }

    /** @return array<int,string> */
    private function localizedSetupLockBlockers(): array
    {
        return collect($this->setupAdminPolicy->setupLockBlockerKeys())
            ->map(fn (string $key): string => __('messages.setup.'.$key))
            ->values()
            ->all();
    }

    /** @return array<string,bool> */
    private function automaticActionStatuses(bool $migrationsReady): array
    {
        return [
            'env' => is_file(base_path('.env')),
            'sqlite' => $this->sqliteDatabaseReady(),
            'key' => trim((string) config('app.key')) !== '' || $this->envHasApplicationKey(),
            'migrations' => $migrationsReady,
            'settings' => $migrationsReady,
            'admin' => $this->setupAdminPolicy->hasAdminUser(),
            'lock' => $this->setupState->isLocked(),
        ];
    }

    private function prepareRuntimeFiles(): void
    {
        $this->ensureEnvFile();
        $this->ensureSqliteDatabaseFile();
        $this->ensureApplicationKey();
    }

    private function ensureEnvFile(): void
    {
        $env = base_path('.env');

        if (is_file($env)) {
            return;
        }

        $example = base_path('.env.example');
        if (! is_file($example)) {
            throw new \RuntimeException(__('messages.setup.env_example_missing'));
        }

        File::copy($example, $env);
    }

    private function ensureSqliteDatabaseFile(): void
    {
        if ((string) config('database.default') !== 'sqlite' && ! str_contains($this->envDatabaseConnection(), 'sqlite')) {
            return;
        }

        $databasePath = $this->sqliteDatabasePath();
        File::ensureDirectoryExists(dirname($databasePath));

        if (! is_file($databasePath)) {
            File::put($databasePath, '');
        }
    }

    private function ensureApplicationKey(): void
    {
        if (trim((string) config('app.key')) !== '' && $this->envHasApplicationKey()) {
            return;
        }

        Artisan::call('key:generate', ['--force' => true]);
    }

    private function sqliteDatabaseReady(): bool
    {
        if ((string) config('database.default') !== 'sqlite' && ! str_contains($this->envDatabaseConnection(), 'sqlite')) {
            return true;
        }

        return is_file($this->sqliteDatabasePath());
    }

    private function sqliteDatabasePath(): string
    {
        $configured = (string) config('database.connections.sqlite.database', database_path('database.sqlite'));

        if ($configured === '' || $configured === ':memory:') {
            return database_path('database.sqlite');
        }

        $normalized = str_replace('\\', '/', $configured);
        if (! str_starts_with($normalized, '/') && ! preg_match('~^[A-Za-z]:[\\\\/]~', $configured)) {
            return base_path($configured);
        }

        return $configured;
    }

    private function envDatabaseConnection(): string
    {
        $env = base_path('.env');

        if (! is_file($env)) {
            return '';
        }

        $contents = (string) file_get_contents($env);

        if (preg_match('/^DB_CONNECTION\s*=\s*(.+)$/m', $contents, $matches) !== 1) {
            return '';
        }

        return strtolower(trim((string) $matches[1], " \t\n\r\0\x0B\"'"));
    }

    private function envHasApplicationKey(): bool
    {
        $env = base_path('.env');

        if (! is_file($env)) {
            return false;
        }

        $contents = (string) file_get_contents($env);

        if (preg_match('/^APP_KEY\s*=\s*(.*)$/m', $contents, $matches) !== 1) {
            return false;
        }

        return trim((string) $matches[1]) !== '';
    }

    private function bootstrapDefaultAdmin(): void
    {
        if (! Schema::hasTable('users')) {
            throw new \RuntimeException(__('messages.setup.users_table_missing'));
        }

        $defaultEmail = (string) config('aptoria.default_admin.email', 'admin@example.com');
        $defaultPassword = (string) config('aptoria.default_admin.password', 'change-me-now');
        $updates = [
            'name' => (string) config('aptoria.default_admin.name', 'Aptoria Admin'),
            'password' => Hash::make($defaultPassword),
            'role' => 'admin',
        ];

        if (Schema::hasColumn('users', 'password_change_required')) {
            $updates['password_change_required'] = true;
        }
        if (Schema::hasColumn('users', 'locale')) {
            $updates['locale'] = (string) config('aptoria.default_locale', 'en');
        }
        if (Schema::hasColumn('users', 'timezone')) {
            $updates['timezone'] = (string) config('app.timezone', 'Europe/Budapest');
        }

        User::query()->updateOrCreate(['email' => $defaultEmail], $updates);
    }

    private function guardUnlockedSetup(): void
    {
        abort_if($this->setupState->isLocked(), 403, __('messages.setup.locked'));
    }


    private function setupStepFromRequest(Request $request): string
    {
        $step = (string) $request->query('step', 'welcome');

        if ($step === 'quick') {
            return 'config';
        }

        return in_array($step, ['welcome', 'environment', 'config', 'admin', 'install'], true)
            ? $step
            : 'welcome';
    }

    private function toSetupStep(string $step): RedirectResponse
    {
        if ($step === 'quick') {
            $step = 'config';
        }

        return redirect()->route('setup.index', ['step' => $step]);
    }

    private function hasTable(string $table): bool
    {
        if (! $this->databasePrerequisitesReady()) {
            return false;
        }

        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    private function databasePrerequisitesReady(): bool
    {
        if (! is_file(base_path('.env'))) {
            return false;
        }

        $connection = (string) config('database.default');
        if ($connection === '') {
            return false;
        }

        if ($connection !== 'sqlite') {
            return true;
        }

        return $this->sqliteDatabaseReady();
    }
}
