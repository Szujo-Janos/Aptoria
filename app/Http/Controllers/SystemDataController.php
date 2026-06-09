<?php

namespace App\Http\Controllers;

use App\Services\Database\DatabaseMaintenanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SystemDataController extends Controller
{
    public function export(DatabaseMaintenanceService $maintenance): StreamedResponse
    {
        $payload = $maintenance->exportPayload();
        $timestamp = now()->format('Ymd-His');
        $filename = 'aptoria-full-database-v'.config('aptoria.version').'-'.$timestamp.'.json';

        return response()->streamDownload(function () use ($payload): void {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }

    public function import(Request $request, DatabaseMaintenanceService $maintenance): RedirectResponse
    {
        $validated = $request->validate([
            'database_export' => ['required', 'file', 'max:51200'],
            'confirm_import' => ['required', 'string', 'in:IMPORT DATABASE'],
        ]);

        $uploaded = $request->file('database_export');
        $contents = $uploaded ? file_get_contents($uploaded->getRealPath()) : false;

        if ($contents === false || trim($contents) === '') {
            return back()->withErrors(['database_export' => __('messages.data_maintenance.import_file_empty')]);
        }

        try {
            $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($payload)) {
                throw new InvalidArgumentException('Invalid JSON payload.');
            }

            $summary = $maintenance->importPayload($payload);
        } catch (Throwable $exception) {
            return back()->withErrors(['database_export' => __('messages.data_maintenance.import_failed', ['message' => $exception->getMessage()])]);
        }

        return redirect()
            ->route('settings.index')
            ->with('success', __('messages.data_maintenance.import_done', $summary));
    }

    public function hardReset(Request $request, DatabaseMaintenanceService $maintenance): RedirectResponse
    {
        $request->validate([
            'confirm_hard_reset' => ['required', 'string', 'in:HARD RESET'],
        ]);

        try {
            $summary = $maintenance->hardReset();
        } catch (Throwable $exception) {
            return back()->withErrors(['hard_reset' => __('messages.data_maintenance.hard_reset_failed', ['message' => $exception->getMessage()])]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('setup.index')
            ->with('warning', __('messages.data_maintenance.hard_reset_done', $summary));
    }
}
