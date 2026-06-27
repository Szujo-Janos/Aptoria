<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\LicenseGuardService;
use App\Services\LicenseRequestService;
use App\Services\OnlineLicenseAuthorityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use JsonException;

class LicenseController extends Controller
{
    public function invalid(LicenseGuardService $licenses, LicenseRequestService $requests): View
    {
        return $this->activationView($licenses, $requests);
    }

    public function activate(LicenseGuardService $licenses, LicenseRequestService $requests): View
    {
        return $this->activationView($licenses, $requests);
    }

    private function activationView(LicenseGuardService $licenses, LicenseRequestService $requests): View
    {
        return view('license.activate', [
            'licenseStatus' => $licenses->status(),
            'licenseRequest' => $requests->build(request()->user()),
        ]);
    }

    public function manage(LicenseGuardService $licenses, LicenseRequestService $requests): View
    {
        return view('license.manage', [
            'licenseStatus' => $licenses->status(),
            'licenseRequest' => $requests->build(request()->user()),
        ]);
    }

    public function downloadRequest(Request $request, LicenseRequestService $requests)
    {
        $json = $requests->toJson($request->user());

        return response($json, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$requests->filename().'"',
        ]);
    }

    public function upload(Request $request, LicenseGuardService $licenses, OnlineLicenseAuthorityService $onlineAuthority, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'license_file' => ['required', 'file', 'max:128'],
        ]);

        $file = $data['license_file'];
        $content = (string) file_get_contents($file->getRealPath());

        try {
            $document = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return back()->withErrors(['license_file' => __('messages.license.upload_invalid_json')]);
        }

        if (! is_array($document)) {
            return back()->withErrors(['license_file' => __('messages.license.upload_invalid_json')]);
        }

        $preview = $licenses->evaluateDocument($document);
        if (! (bool) ($preview['valid'] ?? false)) {
            return back()->withErrors(['license_file' => __('messages.license.upload_rejected', ['state' => $preview['label'] ?? 'invalid'])])->withInput();
        }

        $this->writeJsonWithBackup($licenses->licenseFilePath(), $document);
        $onlineAuthority->clearCachedLease();

        $auditLogger->record('updated', __('messages.audit_messages.license_uploaded'), null, [
            'subject_label' => 'License',
            'license_id' => $preview['license_id'] ?? null,
            'edition' => $preview['edition'] ?? null,
            'expires_at' => $preview['expires_at'] ?? null,
            'binding_mode' => $preview['binding_mode'] ?? null,
            'lease_cache_cleared' => true,
        ], 'license');

        return redirect()->route('program-settings.license')->with('status', __('messages.license.uploaded'));
    }

    public function storePublicKey(Request $request, LicenseGuardService $licenses, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'public_key' => ['required', 'string', 'min:80', 'max:12000'],
        ]);

        $publicKey = trim((string) $data['public_key']);
        if (! $this->looksLikePublicKey($publicKey)) {
            return back()->withErrors(['public_key' => __('messages.license.public_key_invalid')])->withInput();
        }

        $this->writeTextWithBackup($licenses->publicKeyPath(), $publicKey."\n");

        $auditLogger->record('updated', __('messages.audit_messages.license_public_key_updated'), null, [
            'subject_label' => 'License public key',
            'public_key_path' => $licenses->publicKeyPath(),
        ], 'license');

        return redirect()->route('program-settings.license')->with('status', __('messages.license.public_key_saved'));
    }

    public function activateUpload(Request $request, LicenseGuardService $licenses, OnlineLicenseAuthorityService $onlineAuthority, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'license_file' => ['required', 'file', 'max:128'],
        ]);

        $file = $data['license_file'];
        $content = (string) file_get_contents($file->getRealPath());

        try {
            $document = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return back()->withErrors(['license_file' => __('messages.license.upload_invalid_json')]);
        }

        if (! is_array($document)) {
            return back()->withErrors(['license_file' => __('messages.license.upload_invalid_json')]);
        }

        $preview = $licenses->evaluateDocument($document);
        if (! (bool) ($preview['valid'] ?? false)) {
            return back()->withErrors(['license_file' => __('messages.license.upload_rejected', ['state' => $preview['label'] ?? 'invalid'])])->withInput();
        }

        $this->writeJsonWithBackup($licenses->licenseFilePath(), $document);
        $onlineAuthority->clearCachedLease();

        $auditLogger->record('updated', __('messages.audit_messages.license_uploaded'), null, [
            'subject_label' => 'License activation',
            'license_id' => $preview['license_id'] ?? null,
            'edition' => $preview['edition'] ?? null,
            'expires_at' => $preview['expires_at'] ?? null,
            'binding_mode' => $preview['binding_mode'] ?? null,
            'activation_recovery_flow' => true,
            'lease_cache_cleared' => true,
        ], 'license');

        $target = Auth::check() ? route('dashboard') : route('login');

        return redirect($target)->with('status', __('messages.license.activation_complete'));
    }

    public function activatePublicKey(Request $request, LicenseGuardService $licenses, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'public_key' => ['required', 'string', 'min:80', 'max:12000'],
        ]);

        $publicKey = trim((string) $data['public_key']);
        if (! $this->looksLikePublicKey($publicKey)) {
            return back()->withErrors(['public_key' => __('messages.license.public_key_invalid')])->withInput();
        }

        $this->writeTextWithBackup($licenses->publicKeyPath(), $publicKey."\n");

        $auditLogger->record('updated', __('messages.audit_messages.license_public_key_updated'), null, [
            'subject_label' => 'License activation public key',
            'public_key_path' => $licenses->publicKeyPath(),
            'activation_recovery_flow' => true,
        ], 'license');

        return redirect()->route('license.activate')->with('status', __('messages.license.public_key_saved_activation'));
    }

    public function uploadPackage(Request $request, LicenseGuardService $licenses, OnlineLicenseAuthorityService $onlineAuthority, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'activation_package' => ['required', 'file', 'max:2048'],
        ]);

        $package = $this->readActivationPackage(
            (string) $data['activation_package']->getRealPath(),
            (string) $data['activation_package']->getClientOriginalName()
        );

        $result = $this->installActivationPackage($package, $licenses, $onlineAuthority, $auditLogger, 'License management package');
        if ($result instanceof RedirectResponse) {
            return $result;
        }

        return redirect()->route('program-settings.license')
            ->with('status', __('messages.license.management_package_uploaded'))
            ->with('license_online_status', $result['online_message'] ?? null)
            ->with('license_online_tone', $result['online_tone'] ?? null);
    }

    public function activatePackage(Request $request, LicenseGuardService $licenses, OnlineLicenseAuthorityService $onlineAuthority, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'activation_package' => ['required', 'file', 'max:2048'],
        ]);

        $package = $this->readActivationPackage(
            (string) $data['activation_package']->getRealPath(),
            (string) $data['activation_package']->getClientOriginalName()
        );

        $result = $this->installActivationPackage($package, $licenses, $onlineAuthority, $auditLogger, 'Simple license activation');
        if ($result instanceof RedirectResponse) {
            return $result;
        }

        $target = Auth::check() ? route('dashboard') : route('login');

        return redirect($target)
            ->with('status', __('messages.license.activation_complete'))
            ->with('license_online_status', $result['online_message'] ?? null)
            ->with('license_online_tone', $result['online_tone'] ?? null);
    }

    public function refreshOnlineLease(LicenseGuardService $licenses, OnlineLicenseAuthorityService $onlineAuthority, AuditLogger $auditLogger): RedirectResponse
    {
        $localStatus = $licenses->status();
        $onlineStatus = $onlineAuthority->refreshLease($localStatus);

        $auditLogger->record('updated', __('messages.audit_messages.license_online_lease_refreshed'), null, [
            'subject_label' => 'License online lease',
            'license_id' => $localStatus['license_id'] ?? null,
            'state' => $onlineStatus['state'] ?? null,
            'valid' => (bool) ($onlineStatus['valid'] ?? false),
        ], 'license');

        return redirect()->route('program-settings.license')
            ->with((bool) ($onlineStatus['valid'] ?? false) ? 'status' : 'warning', __('messages.license.online_refresh_finished', [
                'state' => $onlineStatus['label'] ?? $onlineStatus['state'] ?? 'unknown',
            ]));
    }

    private function installActivationPackage(array $package, LicenseGuardService $licenses, OnlineLicenseAuthorityService $onlineAuthority, AuditLogger $auditLogger, string $subjectLabel): array|RedirectResponse
    {
        if (($package['manifest_errors'] ?? []) !== []) {
            return back()->withErrors(['activation_package' => implode(' ', $package['manifest_errors'])]);
        }

        if (! is_array($package['license'] ?? null)) {
            return back()->withErrors(['activation_package' => __('messages.license.simple_package_missing_license')]);
        }

        $publicKey = trim((string) ($package['public_key'] ?? ''));
        if ($publicKey !== '') {
            if (! $this->looksLikePublicKey($publicKey)) {
                return back()->withErrors(['activation_package' => __('messages.license.public_key_invalid')]);
            }

            $this->writeTextWithBackup($licenses->publicKeyPath(), $publicKey."\n");
        } elseif (! $licenses->publicKeyConfigured()) {
            return back()->withErrors(['activation_package' => __('messages.license.simple_package_missing_public_key')]);
        }

        $authorityPublicKey = trim((string) ($package['authority_public_key'] ?? ''));
        if ($authorityPublicKey !== '') {
            if (! $this->looksLikePublicKey($authorityPublicKey)) {
                return back()->withErrors(['activation_package' => __('messages.license.authority_public_key_invalid')]);
            }

            $this->writeTextWithBackup($this->authorityPublicKeyPath(), $authorityPublicKey."\n");
        }

        $document = $package['license'];
        $preview = $licenses->evaluateDocument($document);
        if (! (bool) ($preview['valid'] ?? false)) {
            return back()->withErrors(['activation_package' => __('messages.license.upload_rejected', ['state' => $preview['label'] ?? 'invalid'])])->withInput();
        }

        $this->writeJsonWithBackup($licenses->licenseFilePath(), $document);
        $onlineAuthority->clearCachedLease();

        $onlineStatus = null;
        $postInstallStatus = $licenses->status();
        if ($onlineAuthority->enabled()) {
            $onlineStatus = $onlineAuthority->refreshLease($postInstallStatus);
        }

        $auditLogger->record('updated', __('messages.audit_messages.license_uploaded'), null, [
            'subject_label' => $subjectLabel,
            'license_id' => $preview['license_id'] ?? null,
            'edition' => $preview['edition'] ?? null,
            'expires_at' => $preview['expires_at'] ?? null,
            'binding_mode' => $preview['binding_mode'] ?? null,
            'one_step_activation' => true,
            'public_key_in_package' => $publicKey !== '',
            'authority_public_key_in_package' => $authorityPublicKey !== '',
            'manifest_checked' => ($package['manifest'] ?? null) !== null,
            'online_state_after_install' => $onlineStatus['state'] ?? null,
            'lease_cache_cleared' => true,
        ], 'license');

        return [
            'preview' => $preview,
            'online_status' => $onlineStatus,
            'online_message' => $onlineStatus ? __('messages.license.online_refresh_finished', [
                'state' => $onlineStatus['label'] ?? $onlineStatus['state'] ?? 'unknown',
            ]) : null,
            'online_tone' => $onlineStatus['tone'] ?? null,
        ];
    }

    private function readActivationPackage(string $path, string $originalName): array
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($extension === 'zip') {
            return $this->readZipActivationPackage($path);
        }

        $content = (string) file_get_contents($path);

        try {
            $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return ['license' => null, 'public_key' => null, 'authority_public_key' => null, 'manifest' => null, 'manifest_errors' => []];
        }

        if (! is_array($json)) {
            return ['license' => null, 'public_key' => null, 'authority_public_key' => null, 'manifest' => null, 'manifest_errors' => []];
        }

        return $this->normalizeJsonActivationPackage($json);
    }

    private function normalizeJsonActivationPackage(array $json): array
    {
        $publicKey = $json['public_key']
            ?? $json['public_key_pem']
            ?? $json['license_public_key']
            ?? null;

        $authorityPublicKey = $json['authority_public_key']
            ?? $json['authority_public_key_pem']
            ?? $json['license_authority_public_key']
            ?? null;

        $license = $json['license']
            ?? $json['aptoria_license']
            ?? $json['signed_license']
            ?? null;

        if (! is_array($license) && isset($json['payload'], $json['signature'])) {
            $license = $json;
        }

        return [
            'license' => is_array($license) ? $license : null,
            'public_key' => is_string($publicKey) ? $publicKey : null,
            'authority_public_key' => is_string($authorityPublicKey) ? $authorityPublicKey : null,
            'manifest' => is_array($json['activation_manifest'] ?? null) ? $json['activation_manifest'] : null,
            'manifest_errors' => [],
        ];
    }

    private function readZipActivationPackage(string $path): array
    {
        if (! class_exists(\ZipArchive::class)) {
            return ['license' => null, 'public_key' => null, 'authority_public_key' => null, 'manifest' => null, 'manifest_errors' => [__('messages.license.zip_extension_missing')]];
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return ['license' => null, 'public_key' => null, 'authority_public_key' => null, 'manifest' => null, 'manifest_errors' => [__('messages.license.zip_open_failed')]];
        }

        $files = [];
        $licenseContent = null;
        $publicKeyContent = null;
        $authorityPublicKeyContent = null;
        $manifestContent = null;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = (string) $zip->getNameIndex($index);
            $base = strtolower(basename($name));
            $content = $zip->getFromIndex($index);
            if (! is_string($content)) {
                continue;
            }

            $files[$base] = $content;

            if ($licenseContent === null && in_array($base, ['aptoria-license.json', 'license.json', 'signed-license.json'], true)) {
                $licenseContent = $content;
            }

            if ($publicKeyContent === null && in_array($base, ['license-public.pem', 'public-key.pem', 'aptoria-public-key.pem'], true)) {
                $publicKeyContent = $content;
            }

            if ($authorityPublicKeyContent === null && in_array($base, ['license-authority-public.pem', 'authority-public-key.pem', 'aptoria-authority-public-key.pem'], true)) {
                $authorityPublicKeyContent = $content;
            }

            if ($manifestContent === null && in_array($base, ['activation-manifest.json', 'manifest.json'], true)) {
                $manifestContent = $content;
            }
        }

        $zip->close();

        $license = null;
        if (is_string($licenseContent) && trim($licenseContent) !== '') {
            try {
                $decoded = json_decode($licenseContent, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $license = $decoded;
                }
            } catch (JsonException) {
                $license = null;
            }
        }

        $manifest = null;
        $manifestErrors = [];
        if (is_string($manifestContent) && trim($manifestContent) !== '') {
            try {
                $decodedManifest = json_decode($manifestContent, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decodedManifest)) {
                    $manifest = $decodedManifest;
                    $manifestErrors = $this->verifyActivationManifest($manifest, $files);
                }
            } catch (JsonException) {
                $manifestErrors[] = __('messages.license.manifest_invalid');
            }
        }

        return [
            'license' => $license,
            'public_key' => is_string($publicKeyContent) ? $publicKeyContent : null,
            'authority_public_key' => is_string($authorityPublicKeyContent) ? $authorityPublicKeyContent : null,
            'manifest' => $manifest,
            'manifest_errors' => $manifestErrors,
        ];
    }

    private function verifyActivationManifest(array $manifest, array $files): array
    {
        $errors = [];
        if (($manifest['package_format'] ?? null) !== 'aptoria-activation-package-v1') {
            $errors[] = __('messages.license.manifest_format_invalid');
        }

        $manifestFiles = $manifest['files'] ?? [];
        if (! is_array($manifestFiles)) {
            return array_merge($errors, [__('messages.license.manifest_files_invalid')]);
        }

        foreach ($manifestFiles as $filename => $expectedHash) {
            $base = strtolower(basename((string) $filename));
            $expectedHash = (string) $expectedHash;
            if (! isset($files[$base])) {
                $errors[] = __('messages.license.manifest_file_missing', ['file' => $base]);
                continue;
            }

            $actualHash = 'sha256:'.hash('sha256', $files[$base]);
            if (! hash_equals($expectedHash, $actualHash)) {
                $errors[] = __('messages.license.manifest_hash_mismatch', ['file' => $base]);
            }
        }

        return $errors;
    }

    private function writeJsonWithBackup(string $path, array $document): void
    {
        $this->writeTextWithBackup($path, json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
    }

    private function writeTextWithBackup(string $path, string $content): void
    {
        File::ensureDirectoryExists(dirname($path));
        if (File::exists($path)) {
            File::copy($path, $path.'.backup-'.now()->format('YmdHis'));
        }

        File::put($path, $content);
    }

    private function authorityPublicKeyPath(): string
    {
        $path = trim((string) config('aptoria.license.authority.public_key_path', storage_path('app/license-authority-public.pem')));
        if ($path === '') {
            return storage_path('app/license-authority-public.pem');
        }

        if (preg_match('/^[A-Z]:[\\\/]/i', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        return base_path($path);
    }

    private function looksLikePublicKey(string $publicKey): bool
    {
        return str_contains($publicKey, 'BEGIN PUBLIC KEY') && str_contains($publicKey, 'END PUBLIC KEY');
    }

    public function status(LicenseGuardService $licenses): JsonResponse
    {
        return response()->json($licenses->status());
    }
}
