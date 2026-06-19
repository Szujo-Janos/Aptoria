<div class="modal fade" id="calendarEventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('projects.calendar.store', $project) }}" id="calendarEventForm" data-aptoria-form-plugin data-aptoria-form-scope="calendar">
                @csrf
                <input type="hidden" name="_method" value="" disabled>
                <div class="modal-header"><h5 class="modal-title" id="calendarEventModalTitle"><i data-lucide="calendar-plus" class="me-2"></i>{{ __('messages.calendar.new') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button></div>
                <div class="modal-body">@include('calendar.partials.form', ['event' => null])</div>
                <div class="modal-footer aptoria-card-footer-subtle d-flex justify-content-between gap-2 flex-wrap">
                    <div class="d-flex gap-2">
                        <button id="calendarEventDeleteButton" type="submit" form="calendarEventDeleteForm" class="btn btn-danger d-none"><i data-lucide="trash-2" class="me-1"></i>{{ __('messages.common.delete') }}</button>
                        <button id="calendarEventCompleteButton" type="submit" form="calendarEventCompleteForm" class="btn btn-success d-none"><i data-lucide="check-circle" class="me-1"></i>{{ __('messages.calendar.mark_completed') }}</button>
                    </div>
                    <div class="d-flex gap-2"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button><button type="submit" class="btn btn-primary"><i data-lucide="save" class="me-1"></i>{{ __('messages.calendar.save') }}</button></div>
                </div>
            </form>
            <form id="calendarEventDeleteForm" method="POST" action="#" data-aptoria-confirm="delete" data-confirm-title="{{ __('messages.calendar.delete_title') }}" data-confirm-text="{{ __('messages.calendar.delete_text') }}" data-confirm-button="{{ __('messages.common.delete') }}">@csrf @method('DELETE')</form>
            <form id="calendarEventCompleteForm" method="POST" action="#">@csrf</form>
        </div>
    </div>
</div>

<div class="modal fade" id="calendarSystemLogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i data-lucide="lock" class="me-2"></i>{{ __('messages.calendar.system_log') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button></div>
            <div class="modal-body">
                <div class="d-flex align-items-start gap-3 mb-3"><span class="avatar avatar-md rounded text-bg-secondary"><span class="avatar-title"><i data-lucide="lock-keyhole"></i></span></span><div class="min-w-0"><h5 class="mb-1" data-system-log-title></h5><p class="text-muted mb-0" data-system-log-meta></p></div><span data-system-log-severity class="badge badge-soft-secondary ms-auto"></span></div>
                <div class="alert alert-light border"><i data-lucide="info" class="me-1"></i>{{ __('messages.calendar.system_locked') }}</div>
                <dl class="row mb-0"><dt class="col-sm-3">{{ __('messages.calendar.summary') }}</dt><dd class="col-sm-9" data-system-log-summary></dd><dt class="col-sm-3">{{ __('messages.calendar.user') }}</dt><dd class="col-sm-9" data-system-log-user></dd></dl>
            </div>
            <div class="modal-footer aptoria-card-footer-subtle"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.close') }}</button></div>
        </div>
    </div>
</div>

@foreach ($events as $event)
    <div class="modal fade" id="calendarPreviewModal{{ $event->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i data-lucide="eye" class="me-2"></i>{{ $event->title }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-6"><span class="text-muted small d-block">{{ __('messages.calendar.when') }}</span><strong>{{ $event->start_at?->format('Y-m-d H:i') ?? '—' }}</strong>@if($event->end_at)<span class="text-muted"> → {{ $event->end_at->format('Y-m-d H:i') }}</span>@endif</div><div class="col-md-6"><span class="text-muted small d-block">{{ __('messages.calendar.type') }}</span><span class="badge badge-soft-{{ $event->type_tone }}">{{ $event->type_label }}</span></div><div class="col-md-6"><span class="text-muted small d-block">{{ __('messages.common.status') }}</span><span class="badge badge-soft-{{ $event->status_tone }}">{{ $event->status_label }}</span></div><div class="col-md-6"><span class="text-muted small d-block">{{ __('messages.calendar.priority') }}</span><span class="badge badge-soft-{{ $event->priority_tone }}">{{ $event->priority_label }}</span></div><div class="col-12"><span class="text-muted small d-block">{{ __('messages.calendar.description') }}</span><p class="mb-0">{{ $event->description ?: __('messages.calendar.no_description') }}</p></div></div></div><div class="modal-footer aptoria-card-footer-subtle"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.close') }}</button></div></div></div>
    </div>
@endforeach
