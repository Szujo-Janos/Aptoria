<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Model;

class AuditLogObserver
{
    public function created(Model $model): void
    {
        app(AuditLogService::class)->recordModel(AuditLog::ACTION_CREATED, $model);
    }

    public function updated(Model $model): void
    {
        app(AuditLogService::class)->recordModel(AuditLog::ACTION_UPDATED, $model);
    }

    public function deleted(Model $model): void
    {
        app(AuditLogService::class)->recordModel(AuditLog::ACTION_DELETED, $model);
    }
}
