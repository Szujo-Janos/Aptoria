<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\LicenseRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class LicenseIssuerController extends Controller
{
    private const DEFAULT_FEATURES = [
        'portable_usb',
        'evidence_repository',
        'import_adapter',
        'native_test_evidence',
        'release_gate',
        'client_portal',
    ];

    public function index(LicenseRequestService $requests): View
    {
        return view('license_issuer.index', [
            'toolPath' => $this->toolPath(),
            'keysPath' => $this->keysPath(),
            'outPath' => $this->outPath(),
            'privateKeyPath' => $this->defaultPrivateKeyPath(),
            'publicKeyPath' => $this->defaultPublicKeyPath(),
            'privateKeyExists' => File::exists($this->defaultPrivateKeyPath()),
            'publicKeyExists' => File::exists($this->defaultPublicKeyPath()),
            'licenseRequest' => $requests->build(request()->user()),
            'defaultFeatures' => self::DEFAULT_FEATURES,
        ]);
    }

    public function generateKeypair(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'key_name' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/'],
            'bits' => ['required', 'integer', 'min:2048', 'max:4096'],
            'force' => ['nullable', 'boolean'],
        ]);

        try {
            $result = $this->core()->generateKeypair(
                $this->keysPath(),
                (string) $data['key_name'],
                (int) $data['bits'],
                (bool) ($data['force'] ?? false),
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['key_name' => $exception->getMessage()])->withInput();
        }

        $auditLogger->record('generated', __('messages.audit_messages.license_issuer_keypair_generated'), null, [
            'subject_label' => 'License issuer keypair',
            'public_path' => $result['public_path'],
            'bits' => $result['bits'],
        ], 'license');

        return redirect()
            ->route('program-settings.license-issuer')
            ->with('issuer_result', [
                'type' => 'keypair',
                'tone' => 'success',
                'message' => __('messages.license_issuer.keypair_generated'),
                'private_path' => $result['private_path'],
                'public_path' => $result['public_path'],
                'public_key' => $result['public_key'],
            ]);
    }

    public function issue(Request $request, AuditLogger $auditLogger): Response|RedirectResponse
    {
        $data = $request->validate([
            'request_file' => ['nullable', 'file', 'max:512'],
            'request_json' => ['nullable', 'string', 'max:200000'],
            'private_key_file' => ['nullable', 'file', 'max:64'],
            'private_key_pem' => ['nullable', 'string', 'max:20000'],
            'private_key_path' => ['nullable', 'string', 'max:500'],
            'license_id' => ['nullable', 'string', 'max:80'],
            'subject' => ['required', 'string', 'max:160'],
            'issued_to' => ['nullable', 'string', 'max:160'],
            'edition' => ['required', 'string', 'in:portable,server,trial,internal'],
            'expires' => ['required', 'string', 'max:80'],
            'binding' => ['required', 'string', 'in:machine,usb,machine_or_usb,none'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:80'],
            'max_users' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'issuer' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $requestDocument = $this->jsonFromUploadOrTextarea($request, 'request_file', 'request_json', 'license request');
            $privateKey = $this->privateKeyFromRequest($request);
            $issued = $this->core()->issue($requestDocument, $privateKey, [
                'license_id' => $data['license_id'] ?? null,
                'subject' => $data['subject'],
                'issued_to' => $data['issued_to'] ?? null,
                'edition' => $data['edition'],
                'expires' => $data['expires'],
                'binding' => $data['binding'],
                'features' => $data['features'] ?? self::DEFAULT_FEATURES,
                'max_users' => $data['max_users'] ?? null,
                'issuer' => $data['issuer'] ?? 'Aptoria License Issuer',
                'notes' => $data['notes'] ?? null,
            ]);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['request_json' => $exception->getMessage()])->withInput();
        }

        $payload = $issued['payload'];
        $fileName = 'aptoria-license-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) ($payload['license_id'] ?? 'issued')).'.json';
        $outPath = $this->outPath().DIRECTORY_SEPARATOR.$fileName;
        File::ensureDirectoryExists($this->outPath());
        File::put($outPath, $issued['json']);

        $auditLogger->record('generated', __('messages.audit_messages.license_issuer_license_issued'), null, [
            'subject_label' => 'Issued license',
            'license_id' => $payload['license_id'] ?? null,
            'subject' => $payload['subject'] ?? null,
            'edition' => $payload['edition'] ?? null,
            'expires_at' => $payload['expires_at'] ?? null,
            'binding_mode' => $payload['fingerprint_binding']['mode'] ?? null,
        ], 'license');

        return response($issued['json'], 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="aptoria-license.json"',
            'X-Aptoria-Issued-License' => (string) ($payload['license_id'] ?? 'issued'),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'license_file' => ['nullable', 'file', 'max:512'],
            'license_json' => ['nullable', 'string', 'max:200000'],
            'public_key_file' => ['nullable', 'file', 'max:64'],
            'public_key_pem' => ['nullable', 'string', 'max:20000'],
            'public_key_path' => ['nullable', 'string', 'max:500'],
            'request_file' => ['nullable', 'file', 'max:512'],
            'request_json' => ['nullable', 'string', 'max:200000'],
        ]);

        try {
            $license = $this->jsonFromUploadOrTextarea($request, 'license_file', 'license_json', 'license');
            $publicKey = $this->publicKeyFromRequest($request);
            $licenseRequest = $this->optionalJsonFromUploadOrTextarea($request, 'request_file', 'request_json', 'license request');
            $result = $this->core()->verify($license, $publicKey, $licenseRequest);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['license_json' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('program-settings.license-issuer')
            ->with('issuer_result', [
                'type' => 'verify',
                'tone' => 'success',
                'message' => __('messages.license_issuer.verify_ok'),
                'license_id' => $result['license_id'],
                'subject' => $result['subject'],
                'edition' => $result['edition'],
                'expires_at' => $result['expires_at'],
                'binding_mode' => $result['binding_mode'],
                'binding_result' => $result['binding_result'],
            ]);
    }

    public function downloadPublicKey()
    {
        $path = $this->defaultPublicKeyPath();
        abort_unless(File::exists($path), 404);

        return response((string) File::get($path), 200, [
            'Content-Type' => 'application/x-pem-file; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="aptoria-license-public.pem"',
        ]);
    }

    private function jsonFromUploadOrTextarea(Request $request, string $fileField, string $textField, string $label): array
    {
        if ($request->hasFile($fileField)) {
            $raw = (string) file_get_contents($request->file($fileField)->getRealPath());
        } else {
            $raw = trim((string) $request->input($textField, ''));
        }

        if ($raw === '') {
            throw new RuntimeException('Provide a '.$label.' JSON file or paste JSON content.');
        }

        return $this->core()->decodeJson($raw, $label);
    }

    private function optionalJsonFromUploadOrTextarea(Request $request, string $fileField, string $textField, string $label): ?array
    {
        if (! $request->hasFile($fileField) && trim((string) $request->input($textField, '')) === '') {
            return null;
        }

        return $this->jsonFromUploadOrTextarea($request, $fileField, $textField, $label);
    }

    private function privateKeyFromRequest(Request $request): string
    {
        if ($request->hasFile('private_key_file')) {
            return (string) file_get_contents($request->file('private_key_file')->getRealPath());
        }

        $pasted = trim((string) $request->input('private_key_pem', ''));
        if ($pasted !== '') {
            return $pasted;
        }

        $path = trim((string) $request->input('private_key_path', $this->defaultPrivateKeyPath()));
        $path = $this->absoluteToolPath($path);
        if (! File::exists($path)) {
            throw new RuntimeException('Private key file not found: '.$path);
        }

        return (string) File::get($path);
    }

    private function publicKeyFromRequest(Request $request): string
    {
        if ($request->hasFile('public_key_file')) {
            return (string) file_get_contents($request->file('public_key_file')->getRealPath());
        }

        $pasted = trim((string) $request->input('public_key_pem', ''));
        if ($pasted !== '') {
            return $pasted;
        }

        $path = trim((string) $request->input('public_key_path', $this->defaultPublicKeyPath()));
        $path = $this->absoluteToolPath($path);
        if (! File::exists($path)) {
            throw new RuntimeException('Public key file not found: '.$path);
        }

        return (string) File::get($path);
    }

    private function core(): \AptoriaLicenseIssuerCore
    {
        require_once base_path('tools/license-issuer/src/LicenseIssuerCore.php');

        return new \AptoriaLicenseIssuerCore();
    }

    private function toolPath(): string
    {
        return base_path('tools/license-issuer');
    }

    private function keysPath(): string
    {
        return $this->toolPath().DIRECTORY_SEPARATOR.'keys';
    }

    private function outPath(): string
    {
        return $this->toolPath().DIRECTORY_SEPARATOR.'out';
    }

    private function defaultPrivateKeyPath(): string
    {
        return $this->keysPath().DIRECTORY_SEPARATOR.'aptoria-license-private.pem';
    }

    private function defaultPublicKeyPath(): string
    {
        return $this->keysPath().DIRECTORY_SEPARATOR.'aptoria-license-public.pem';
    }

    private function absoluteToolPath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (preg_match('/^[A-Z]:[\\\\\/]/i', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        return base_path($path);
    }
}
