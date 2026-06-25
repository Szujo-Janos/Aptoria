<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\LicenseGuardService;
use App\Services\LicenseRequestService;
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

    public function upload(Request $request, LicenseGuardService $licenses, AuditLogger $auditLogger): RedirectResponse
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

        $path = $licenses->licenseFilePath();
        File::ensureDirectoryExists(dirname($path));
        if (File::exists($path)) {
            File::copy($path, $path.'.backup-'.now()->format('YmdHis'));
        }

        File::put($path, json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $auditLogger->record('updated', __('messages.audit_messages.license_uploaded'), null, [
            'subject_label' => 'License',
            'license_id' => $preview['license_id'] ?? null,
            'edition' => $preview['edition'] ?? null,
            'expires_at' => $preview['expires_at'] ?? null,
            'binding_mode' => $preview['binding_mode'] ?? null,
        ], 'license');

        return redirect()->route('program-settings.license')->with('status', __('messages.license.uploaded'));
    }

    public function storePublicKey(Request $request, LicenseGuardService $licenses, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'public_key' => ['required', 'string', 'min:80', 'max:12000'],
        ]);

        $publicKey = trim((string) $data['public_key']);
        if (! str_contains($publicKey, 'BEGIN PUBLIC KEY') || ! str_contains($publicKey, 'END PUBLIC KEY')) {
            return back()->withErrors(['public_key' => __('messages.license.public_key_invalid')])->withInput();
        }

        $path = $licenses->publicKeyPath();
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $publicKey."\n");

        $auditLogger->record('updated', __('messages.audit_messages.license_public_key_updated'), null, [
            'subject_label' => 'License public key',
            'public_key_path' => $path,
        ], 'license');

        return redirect()->route('program-settings.license')->with('status', __('messages.license.public_key_saved'));
    }

    public function activateUpload(Request $request, LicenseGuardService $licenses, AuditLogger $auditLogger): RedirectResponse
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

        $path = $licenses->licenseFilePath();
        File::ensureDirectoryExists(dirname($path));
        if (File::exists($path)) {
            File::copy($path, $path.'.backup-'.now()->format('YmdHis'));
        }

        File::put($path, json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $auditLogger->record('updated', __('messages.audit_messages.license_uploaded'), null, [
            'subject_label' => 'License activation',
            'license_id' => $preview['license_id'] ?? null,
            'edition' => $preview['edition'] ?? null,
            'expires_at' => $preview['expires_at'] ?? null,
            'binding_mode' => $preview['binding_mode'] ?? null,
            'activation_recovery_flow' => true,
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
        if (! str_contains($publicKey, 'BEGIN PUBLIC KEY') || ! str_contains($publicKey, 'END PUBLIC KEY')) {
            return back()->withErrors(['public_key' => __('messages.license.public_key_invalid')])->withInput();
        }

        $path = $licenses->publicKeyPath();
        File::ensureDirectoryExists(dirname($path));
        if (File::exists($path)) {
            File::copy($path, $path.'.backup-'.now()->format('YmdHis'));
        }

        File::put($path, $publicKey."\n");

        $auditLogger->record('updated', __('messages.audit_messages.license_public_key_updated'), null, [
            'subject_label' => 'License activation public key',
            'public_key_path' => $path,
            'activation_recovery_flow' => true,
        ], 'license');

        return redirect()->route('license.activate')->with('status', __('messages.license.public_key_saved_activation'));
    }

    public function uploadPackage(Request $request, LicenseGuardService $licenses, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'activation_package' => ['required', 'file', 'max:2048'],
        ]);

        $file = $data['activation_package'];
        $package = $this->readActivationPackage(
            (string) $file->getRealPath(),
            (string) $file->getClientOriginalName()
        );

        if (! is_array($package['license'] ?? null)) {
            return back()->withErrors(['activation_package' => __('messages.license.simple_package_missing_license')]);
        }

        $publicKey = trim((string) ($package['public_key'] ?? ''));
        if ($publicKey !== '') {
            if (! $this->looksLikePublicKey($publicKey)) {
                return back()->withErrors(['activation_package' => __('messages.license.public_key_invalid')]);
            }

            $publicKeyPath = $licenses->publicKeyPath();
            File::ensureDirectoryExists(dirname($publicKeyPath));
            if (File::exists($publicKeyPath)) {
                File::copy($publicKeyPath, $publicKeyPath.'.backup-'.now()->format('YmdHis'));
            }
            File::put($publicKeyPath, $publicKey."\n");
        } elseif (! $licenses->publicKeyConfigured()) {
            return back()->withErrors(['activation_package' => __('messages.license.simple_package_missing_public_key')]);
        }

        $document = $package['license'];
        $preview = $licenses->evaluateDocument($document);
        if (! (bool) ($preview['valid'] ?? false)) {
            return back()->withErrors(['activation_package' => __('messages.license.upload_rejected', ['state' => $preview['label'] ?? 'invalid'])])->withInput();
        }

        $licensePath = $licenses->licenseFilePath();
        File::ensureDirectoryExists(dirname($licensePath));
        if (File::exists($licensePath)) {
            File::copy($licensePath, $licensePath.'.backup-'.now()->format('YmdHis'));
        }

        File::put($licensePath, json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $auditLogger->record('updated', __('messages.audit_messages.license_uploaded'), null, [
            'subject_label' => 'License management package',
            'license_id' => $preview['license_id'] ?? null,
            'edition' => $preview['edition'] ?? null,
            'expires_at' => $preview['expires_at'] ?? null,
            'binding_mode' => $preview['binding_mode'] ?? null,
            'one_step_management' => true,
            'public_key_in_package' => $publicKey !== '',
        ], 'license');

        return redirect()->route('program-settings.license')->with('status', __('messages.license.management_package_uploaded'));
    }

    public function activatePackage(Request $request, LicenseGuardService $licenses, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'activation_package' => ['required', 'file', 'max:2048'],
        ]);

        $file = $data['activation_package'];
        $package = $this->readActivationPackage(
            (string) $file->getRealPath(),
            (string) $file->getClientOriginalName()
        );

        if (! is_array($package['license'] ?? null)) {
            return back()->withErrors(['activation_package' => __('messages.license.simple_package_missing_license')]);
        }

        $publicKey = trim((string) ($package['public_key'] ?? ''));
        if ($publicKey !== '') {
            if (! $this->looksLikePublicKey($publicKey)) {
                return back()->withErrors(['activation_package' => __('messages.license.public_key_invalid')]);
            }

            $publicKeyPath = $licenses->publicKeyPath();
            File::ensureDirectoryExists(dirname($publicKeyPath));
            if (File::exists($publicKeyPath)) {
                File::copy($publicKeyPath, $publicKeyPath.'.backup-'.now()->format('YmdHis'));
            }
            File::put($publicKeyPath, $publicKey."\n");
        } elseif (! $licenses->publicKeyConfigured()) {
            return back()->withErrors(['activation_package' => __('messages.license.simple_package_missing_public_key')]);
        }

        $document = $package['license'];
        $preview = $licenses->evaluateDocument($document);
        if (! (bool) ($preview['valid'] ?? false)) {
            return back()->withErrors(['activation_package' => __('messages.license.upload_rejected', ['state' => $preview['label'] ?? 'invalid'])])->withInput();
        }

        $licensePath = $licenses->licenseFilePath();
        File::ensureDirectoryExists(dirname($licensePath));
        if (File::exists($licensePath)) {
            File::copy($licensePath, $licensePath.'.backup-'.now()->format('YmdHis'));
        }

        File::put($licensePath, json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $auditLogger->record('updated', __('messages.audit_messages.license_uploaded'), null, [
            'subject_label' => 'Simple license activation',
            'license_id' => $preview['license_id'] ?? null,
            'edition' => $preview['edition'] ?? null,
            'expires_at' => $preview['expires_at'] ?? null,
            'binding_mode' => $preview['binding_mode'] ?? null,
            'one_step_activation' => true,
            'public_key_in_package' => $publicKey !== '',
        ], 'license');

        $target = Auth::check() ? route('dashboard') : route('login');

        return redirect($target)->with('status', __('messages.license.activation_complete'));
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
            return ['license' => null, 'public_key' => null];
        }

        if (! is_array($json)) {
            return ['license' => null, 'public_key' => null];
        }

        return $this->normalizeJsonActivationPackage($json);
    }

    private function normalizeJsonActivationPackage(array $json): array
    {
        $publicKey = $json['public_key']
            ?? $json['public_key_pem']
            ?? $json['license_public_key']
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
        ];
    }

    private function readZipActivationPackage(string $path): array
    {
        if (! class_exists(\ZipArchive::class)) {
            return ['license' => null, 'public_key' => null];
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return ['license' => null, 'public_key' => null];
        }

        $licenseContent = null;
        $publicKeyContent = null;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = (string) $zip->getNameIndex($index);
            $base = strtolower(basename($name));

            if ($licenseContent === null && in_array($base, ['aptoria-license.json', 'license.json', 'signed-license.json'], true)) {
                $licenseContent = $zip->getFromIndex($index);
            }

            if ($publicKeyContent === null && in_array($base, ['license-public.pem', 'public-key.pem', 'aptoria-public-key.pem'], true)) {
                $publicKeyContent = $zip->getFromIndex($index);
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

        return [
            'license' => $license,
            'public_key' => is_string($publicKeyContent) ? $publicKeyContent : null,
        ];
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
