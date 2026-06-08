<?php

namespace App\Observers;

use App\Services\Calendar\CalendarActivityLogger;
use Illuminate\Database\Eloquent\Model;

class CalendarActivityObserver
{
    public function created(Model $model): void
    {
        app(CalendarActivityLogger::class)->record(CalendarActivityLogger::ACTION_CREATED, $model);
    }

    public function updated(Model $model): void
    {
        app(CalendarActivityLogger::class)->record(CalendarActivityLogger::ACTION_UPDATED, $model);
    }

    public function deleted(Model $model): void
    {
        app(CalendarActivityLogger::class)->record(CalendarActivityLogger::ACTION_DELETED, $model);
    }
}
