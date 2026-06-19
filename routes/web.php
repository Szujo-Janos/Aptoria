<?php

use App\Http\Controllers\AssertionRuleController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthProfileController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicClientPortalController;
use App\Http\Controllers\ClientPortalController;
use App\Http\Controllers\ContractValidationController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EndpointController;
use App\Http\Controllers\EndpointTestBatchController;
use App\Http\Controllers\EndpointTestRunController;
use App\Http\Controllers\EndpointSnapshotController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\EvidencePackController;
use App\Http\Controllers\FindingController;
use App\Http\Controllers\FindingMergeController;
use App\Http\Controllers\EnvironmentController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\ImportCenterController;
use App\Http\Controllers\NativeTestController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\PlaceholderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramSettingsController;
use App\Http\Controllers\ProjectContextController;
use App\Http\Controllers\ProjectMembershipController;
use App\Http\Controllers\QaCockpitController;
use App\Http\Controllers\ProjectSettingsController;
use App\Http\Controllers\ReleaseGateController;
use App\Http\Controllers\ReleaseReadinessController;
use App\Http\Controllers\ReleaseReadinessRuleController;
use App\Http\Controllers\ReleaseDecisionSnapshotController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\SafeScanController;
use App\Http\Middleware\EnsureSetupAccessIsAuthorized;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');
Route::get('/language/{locale}', LanguageController::class)->name('language.switch');

Route::get('/client-portal/{token}', [PublicClientPortalController::class, 'show'])->name('client-portal.show');
Route::post('/client-portal/{token}/acknowledge', [PublicClientPortalController::class, 'acknowledge'])->name('client-portal.acknowledge');
Route::get('/client-portal/{token}/reports/{reportVersion}/download/{format}', [PublicClientPortalController::class, 'download'])->name('client-portal.reports.download');

Route::middleware(EnsureSetupAccessIsAuthorized::class)->group(function (): void {
    Route::get('/setup', [SetupController::class, 'index'])->name('setup.index');
    Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');
    Route::post('/setup/create-env', [SetupController::class, 'createEnv'])->name('setup.create_env');
    Route::post('/setup/create-sqlite', [SetupController::class, 'createSqlite'])->name('setup.create_sqlite');
    Route::post('/setup/generate-key', [SetupController::class, 'generateKey'])->name('setup.generate_key');
    Route::post('/setup/migrate', [SetupController::class, 'migrate'])->name('setup.migrate');
    Route::post('/setup/create-admin', [SetupController::class, 'createAdmin'])->name('setup.create_admin');
    Route::post('/setup/install', [SetupController::class, 'install'])->name('setup.install');
    Route::post('/setup/finish', [SetupController::class, 'finish'])->name('setup.finish');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware(['auth', 'password.changed'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/projects/context/clear', [ProjectContextController::class, 'clear'])->name('projects.context.clear');
    Route::get('/projects/{project}/activate', [ProjectContextController::class, 'switch'])->name('projects.switch');

    Route::get('/projects/{project}/environments', [EnvironmentController::class, 'index'])->name('projects.environments.index');
    Route::post('/projects/{project}/environments', [EnvironmentController::class, 'store'])->name('projects.environments.store');
    Route::put('/projects/{project}/environments/{environment}', [EnvironmentController::class, 'update'])->name('projects.environments.update');
    Route::delete('/projects/{project}/environments/{environment}', [EnvironmentController::class, 'destroy'])->name('projects.environments.destroy');
    Route::post('/projects/{project}/environments/{environment}/default', [EnvironmentController::class, 'makeDefault'])->name('projects.environments.default');

    Route::get('/projects/{project}/auth-profiles', [AuthProfileController::class, 'index'])->name('projects.auth-profiles.index');
    Route::post('/projects/{project}/auth-profiles', [AuthProfileController::class, 'store'])->name('projects.auth-profiles.store');
    Route::post('/projects/{project}/auth-profiles/test', [AuthProfileController::class, 'test'])->name('projects.auth-profiles.test');
    Route::put('/projects/{project}/auth-profiles/{authProfile}', [AuthProfileController::class, 'update'])->name('projects.auth-profiles.update');
    Route::delete('/projects/{project}/auth-profiles/{authProfile}', [AuthProfileController::class, 'destroy'])->name('projects.auth-profiles.destroy');
    Route::post('/projects/{project}/auth-profiles/{authProfile}/default', [AuthProfileController::class, 'makeDefault'])->name('projects.auth-profiles.default');


    Route::get('/projects/{project}/qa-cockpit', [QaCockpitController::class, 'show'])->name('projects.qa-cockpit.show');

    Route::get('/projects/{project}/safe-scans', [SafeScanController::class, 'index'])->name('projects.safe-scans.index');
    Route::post('/projects/{project}/safe-scans', [SafeScanController::class, 'store'])->name('projects.safe-scans.store');
    Route::get('/projects/{project}/safe-scans/{scanRun}', [SafeScanController::class, 'show'])->name('projects.safe-scans.show');

    Route::get('/projects/{project}/contract-validation', [ContractValidationController::class, 'index'])->name('projects.contract-validation.index');
    Route::post('/projects/{project}/contract-validation', [ContractValidationController::class, 'store'])->name('projects.contract-validation.store');
    Route::get('/projects/{project}/contract-validation/{contractValidationRun}', [ContractValidationController::class, 'show'])->name('projects.contract-validation.show');

    Route::get('/projects/{project}/import-center', [ImportCenterController::class, 'index'])->name('projects.import-center.index');
    Route::get('/projects/{project}/import-center/create', [ImportCenterController::class, 'create'])->name('projects.import-center.create');
    Route::post('/projects/{project}/import-center', [ImportCenterController::class, 'store'])->name('projects.import-center.store');
    Route::get('/projects/{project}/import-center/{externalImportRun}', [ImportCenterController::class, 'show'])->name('projects.import-center.show');
    Route::post('/projects/{project}/import-center/{externalImportRun}/apply', [ImportCenterController::class, 'apply'])->name('projects.import-center.apply');
    Route::post('/projects/{project}/import-center/{externalImportRun}/undo', [ImportCenterController::class, 'undo'])->name('projects.import-center.undo');

    Route::get('/projects/{project}/tests', [NativeTestController::class, 'index'])->name('projects.tests.index');
    Route::get('/projects/{project}/tests/suites/create', [NativeTestController::class, 'createSuite'])->name('projects.tests.suites.create');
    Route::post('/projects/{project}/tests/suites', [NativeTestController::class, 'storeSuite'])->name('projects.tests.suites.store');
    Route::get('/projects/{project}/tests/suites/{testSuite}', [NativeTestController::class, 'showSuite'])->name('projects.tests.suites.show');
    Route::get('/projects/{project}/tests/suites/{testSuite}/cases/create', [NativeTestController::class, 'createCase'])->name('projects.tests.cases.create');
    Route::post('/projects/{project}/tests/suites/{testSuite}/cases', [NativeTestController::class, 'storeCase'])->name('projects.tests.cases.store');
    Route::get('/projects/{project}/tests/cases/{testCase}', [NativeTestController::class, 'showCase'])->name('projects.tests.cases.show');
    Route::get('/projects/{project}/tests/cases/{testCase}/runs/create', [NativeTestController::class, 'createRun'])->name('projects.tests.runs.create');
    Route::post('/projects/{project}/tests/cases/{testCase}/runs', [NativeTestController::class, 'storeRun'])->name('projects.tests.runs.store');

    Route::get('/projects/{project}/assertions', [AssertionRuleController::class, 'index'])->name('projects.assertions.index');
    Route::post('/projects/{project}/assertions', [AssertionRuleController::class, 'store'])->name('projects.assertions.store');
    Route::put('/projects/{project}/assertions/{assertion}', [AssertionRuleController::class, 'update'])->name('projects.assertions.update');
    Route::delete('/projects/{project}/assertions/{assertion}', [AssertionRuleController::class, 'destroy'])->name('projects.assertions.destroy');


    Route::get('/projects/{project}/snapshots', [EndpointSnapshotController::class, 'index'])->name('projects.snapshots.index');
    Route::post('/projects/{project}/snapshots', [EndpointSnapshotController::class, 'store'])->name('projects.snapshots.store');
    Route::post('/projects/{project}/snapshots/compare', [EndpointSnapshotController::class, 'compare'])->name('projects.snapshots.compare');
    Route::get('/projects/{project}/snapshots/{endpointSnapshot}', [EndpointSnapshotController::class, 'show'])->name('projects.snapshots.show');
    Route::get('/projects/{project}/snapshot-compares/{endpointSnapshotCompare}', [EndpointSnapshotController::class, 'compareShow'])->name('projects.snapshot-compares.show');
    Route::post('/projects/{project}/snapshot-compares/{endpointSnapshotCompare}/regression-findings', [EndpointSnapshotController::class, 'generateRegressionFindings'])->name('projects.snapshot-compares.regression-findings');

    Route::get('/projects/{project}/endpoints', [EndpointController::class, 'index'])->name('projects.endpoints.index');
    Route::post('/projects/{project}/endpoints', [EndpointController::class, 'store'])->name('projects.endpoints.store');
    Route::post('/projects/{project}/endpoints/quick-test-all', [EndpointController::class, 'testAll'])->name('projects.endpoints.test-all');
    Route::put('/projects/{project}/endpoints/{endpoint}', [EndpointController::class, 'update'])->name('projects.endpoints.update');
    Route::post('/projects/{project}/endpoints/{endpoint}/quick-test', [EndpointController::class, 'test'])->name('projects.endpoints.test');
    Route::get('/projects/{project}/endpoint-test-batches/{endpointTestBatch}', [EndpointTestBatchController::class, 'show'])->name('projects.endpoint-test-batches.show');
    Route::get('/projects/{project}/endpoint-test-runs/{endpointTestRun}', [EndpointTestRunController::class, 'show'])->name('projects.endpoint-test-runs.show');
    Route::delete('/projects/{project}/endpoints/{endpoint}', [EndpointController::class, 'destroy'])->name('projects.endpoints.destroy');


    Route::get('/projects/{project}/findings/deduplication', [FindingMergeController::class, 'index'])->name('projects.findings.dedup.index');
    Route::post('/projects/{project}/findings/deduplication/scan', [FindingMergeController::class, 'scan'])->name('projects.findings.dedup.scan');
    Route::post('/projects/{project}/findings/deduplication/{candidate}/merge', [FindingMergeController::class, 'merge'])->name('projects.findings.dedup.merge');
    Route::post('/projects/{project}/findings/deduplication/{candidate}/dismiss', [FindingMergeController::class, 'dismiss'])->name('projects.findings.dedup.dismiss');

    Route::get('/projects/{project}/findings', [FindingController::class, 'index'])->name('projects.findings.index');
    Route::post('/projects/{project}/findings', [FindingController::class, 'store'])->name('projects.findings.store');
    Route::get('/projects/{project}/findings/{finding}', [FindingController::class, 'show'])->name('projects.findings.show');
    Route::put('/projects/{project}/findings/{finding}', [FindingController::class, 'update'])->name('projects.findings.update');
    Route::post('/projects/{project}/findings/{finding}/request-retest', [FindingController::class, 'requestRetest'])->name('projects.findings.request-retest');
    Route::post('/projects/{project}/findings/{finding}/ready-for-retest', [FindingController::class, 'markReadyForRetest'])->name('projects.findings.ready-for-retest');
    Route::post('/projects/{project}/findings/{finding}/record-retest', [FindingController::class, 'recordRetest'])->name('projects.findings.record-retest');
    Route::post('/projects/{project}/findings/{finding}/risk-acceptance', [FindingController::class, 'acceptRisk'])->name('projects.findings.risk-acceptance.store');
    Route::post('/projects/{project}/findings/{finding}/risk-acceptance/renew', [FindingController::class, 'renewRisk'])->name('projects.findings.risk-acceptance.renew');
    Route::post('/projects/{project}/findings/{finding}/risk-acceptance/close-finding', [FindingController::class, 'closeRiskAcceptedFinding'])->name('projects.findings.risk-acceptance.close-finding');
    Route::post('/projects/{project}/findings/{finding}/risk-acceptance/revoke', [FindingController::class, 'revokeRisk'])->name('projects.findings.risk-acceptance.revoke');
    Route::delete('/projects/{project}/findings/{finding}', [FindingController::class, 'destroy'])->name('projects.findings.destroy');

    Route::get('/projects/{project}/evidence-packs', [EvidencePackController::class, 'index'])->name('projects.evidence-packs.index');
    Route::post('/projects/{project}/evidence-packs', [EvidencePackController::class, 'store'])->name('projects.evidence-packs.store');
    Route::get('/projects/{project}/evidence-packs/{evidencePack}', [EvidencePackController::class, 'show'])->name('projects.evidence-packs.show');
    Route::get('/projects/{project}/evidence-packs/{evidencePack}/download/{format}', [EvidencePackController::class, 'download'])->name('projects.evidence-packs.download');

    Route::get('/projects/{project}/evidence', [EvidenceController::class, 'index'])->name('projects.evidence.index');
    Route::get('/projects/{project}/evidence/create', [EvidenceController::class, 'create'])->name('projects.evidence.create');
    Route::post('/projects/{project}/evidence', [EvidenceController::class, 'store'])->name('projects.evidence.store');
    Route::get('/projects/{project}/evidence/{evidence}', [EvidenceController::class, 'show'])->name('projects.evidence.show');
    Route::post('/projects/{project}/evidence/{evidence}/verify', [EvidenceController::class, 'verify'])->name('projects.evidence.verify');
    Route::post('/projects/{project}/evidence/{evidence}/archive', [EvidenceController::class, 'archive'])->name('projects.evidence.archive');
    Route::post('/projects/{project}/evidence/{evidence}/restore', [EvidenceController::class, 'restore'])->name('projects.evidence.restore');
    Route::delete('/projects/{project}/evidence/{evidence}', [EvidenceController::class, 'destroy'])->name('projects.evidence.destroy');


    Route::get('/projects/{project}/release-gates', [ReleaseGateController::class, 'index'])->name('projects.release-gates.index');
    Route::post('/projects/{project}/release-gates', [ReleaseGateController::class, 'store'])->name('projects.release-gates.store');
    Route::get('/projects/{project}/release-gates/{releaseGate}', [ReleaseGateController::class, 'show'])->name('projects.release-gates.show');
    Route::post('/projects/{project}/release-gates/{releaseGate}/report-version', [ReleaseGateController::class, 'storeReportVersion'])->name('projects.release-gates.report-version.store');
    Route::get('/projects/{project}/release-gates/{releaseGate}/download/{format}', [ReleaseGateController::class, 'download'])->name('projects.release-gates.download');
    Route::put('/projects/{project}/release-gates/{releaseGate}/items/{item}', [ReleaseGateController::class, 'updateItem'])->name('projects.release-gates.items.update');
    Route::post('/projects/{project}/release-gates/{releaseGate}/finalize', [ReleaseGateController::class, 'finalize'])->name('projects.release-gates.finalize');

    Route::get('/projects/{project}/release-readiness', [ReleaseReadinessController::class, 'index'])->name('projects.release-readiness.index');
    Route::get('/projects/{project}/release-readiness/rules', [ReleaseReadinessRuleController::class, 'index'])->name('projects.release-readiness.rules.index');
    Route::match(['post', 'put'], '/projects/{project}/release-readiness/rules/simulate', [ReleaseReadinessRuleController::class, 'simulate'])->name('projects.release-readiness.rules.simulate');
    Route::post('/projects/{project}/release-readiness/rules/apply-profile', [ReleaseReadinessRuleController::class, 'applyProfile'])->name('projects.release-readiness.rules.apply-profile');
    Route::put('/projects/{project}/release-readiness/rules', [ReleaseReadinessRuleController::class, 'update'])->name('projects.release-readiness.rules.update');
    Route::post('/projects/{project}/release-readiness/rules/reset', [ReleaseReadinessRuleController::class, 'reset'])->name('projects.release-readiness.rules.reset');
    Route::post('/projects/{project}/release-readiness', [ReleaseReadinessController::class, 'store'])->name('projects.release-readiness.store');
    Route::get('/projects/{project}/release-readiness/{releaseReadinessRun}', [ReleaseReadinessController::class, 'show'])->name('projects.release-readiness.show');
    Route::post('/projects/{project}/release-decisions', [ReleaseDecisionSnapshotController::class, 'store'])->name('projects.release-decisions.store');
    Route::get('/projects/{project}/release-decisions/{releaseDecisionSnapshot}', [ReleaseDecisionSnapshotController::class, 'show'])->name('projects.release-decisions.show');
    Route::get('/projects/{project}/release-decisions/{releaseDecisionSnapshot}/report-preview', [ReleaseDecisionSnapshotController::class, 'reportPreview'])->name('projects.release-decisions.report-preview');
    Route::get('/projects/{project}/release-decisions/{releaseDecisionSnapshot}/download/{format}', [ReleaseDecisionSnapshotController::class, 'download'])->name('projects.release-decisions.download');
    Route::post('/projects/{project}/release-decisions/{releaseDecisionSnapshot}/report-version', [ReleaseDecisionSnapshotController::class, 'storeReportVersion'])->name('projects.release-decisions.report-version.store');

    Route::get('/projects/{project}/reports', [ReportController::class, 'index'])->name('projects.reports.index');
    Route::post('/projects/{project}/reports', [ReportController::class, 'store'])->name('projects.reports.store');
    Route::get('/projects/{project}/reports/{reportVersion}', [ReportController::class, 'show'])->name('projects.reports.show');
    Route::post('/projects/{project}/reports/{reportVersion}/status', [ReportController::class, 'status'])->name('projects.reports.status');
    Route::post('/projects/{project}/reports/{reportVersion}/delivery-link', [ReportController::class, 'deliveryLink'])->name('projects.reports.delivery-link');
    Route::get('/projects/{project}/reports/{reportVersion}/download/{format}', [ReportController::class, 'download'])->name('projects.reports.download');



    Route::get('/projects/{project}/calendar', [CalendarController::class, 'index'])->name('projects.calendar.index');
    Route::get('/projects/{project}/calendar/events', [CalendarController::class, 'events'])->name('projects.calendar.events');
    Route::get('/projects/{project}/calendar/day', [CalendarController::class, 'day'])->name('projects.calendar.day');
    Route::post('/projects/{project}/calendar', [CalendarController::class, 'store'])->name('projects.calendar.store');
    Route::put('/projects/{project}/calendar/{calendarEvent}', [CalendarController::class, 'update'])->name('projects.calendar.update');
    Route::patch('/projects/{project}/calendar/{calendarEvent}/move', [CalendarController::class, 'move'])->name('projects.calendar.move');
    Route::post('/projects/{project}/calendar/{calendarEvent}/complete', [CalendarController::class, 'complete'])->name('projects.calendar.complete');
    Route::delete('/projects/{project}/calendar/{calendarEvent}', [CalendarController::class, 'destroy'])->name('projects.calendar.destroy');

    Route::get('/projects/{project}/client-portal', [ClientPortalController::class, 'index'])->name('projects.client-portal.index');
    Route::post('/projects/{project}/client-portal', [ClientPortalController::class, 'store'])->name('projects.client-portal.store');
    Route::post('/projects/{project}/client-portal/{clientPortalAccess}/toggle', [ClientPortalController::class, 'toggle'])->name('projects.client-portal.toggle');
    Route::delete('/projects/{project}/client-portal/{clientPortalAccess}', [ClientPortalController::class, 'destroy'])->name('projects.client-portal.destroy');


    Route::get('/projects/{project}/members', [ProjectMembershipController::class, 'index'])->name('projects.members.index');
    Route::post('/projects/{project}/members', [ProjectMembershipController::class, 'store'])->name('projects.members.store');
    Route::post('/projects/{project}/members/create-user', [ProjectMembershipController::class, 'createUser'])->name('projects.members.create-user');
    Route::put('/projects/{project}/members/{membership}', [ProjectMembershipController::class, 'update'])->name('projects.members.update');
    Route::delete('/projects/{project}/members/{membership}', [ProjectMembershipController::class, 'destroy'])->name('projects.members.destroy');

    Route::get('/projects/{project}/settings', [ProjectSettingsController::class, 'edit'])->name('projects.settings.edit');
    Route::put('/projects/{project}/settings', [ProjectSettingsController::class, 'update'])->name('projects.settings.update');

    Route::get('/projects/{project}/modules/{module}', [PlaceholderController::class, 'project'])->name('projects.modules.show');
    Route::resource('projects', ProjectController::class);

    Route::get('/audit-log', AuditLogController::class)->name('audit.index');
    Route::middleware('admin')->group(function (): void {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/temporary-password', [UserManagementController::class, 'resetTemporaryPassword'])->name('users.temporary-password');
    });

    Route::get('/program-settings', [ProgramSettingsController::class, 'edit'])->name('program-settings.edit');
    Route::put('/program-settings', [ProgramSettingsController::class, 'update'])->name('program-settings.update');
    Route::post('/program-settings/demo-project', [ProgramSettingsController::class, 'buildDemoProject'])->name('program-settings.demo-project');
    Route::get('/help/how-it-works', [HelpController::class, 'howItWorks'])->name('help.how_it_works');
    Route::get('/help', [HelpController::class, 'index'])->name('help.index');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::get('/modules/{module}', [PlaceholderController::class, 'show'])->name('modules.show');
});
