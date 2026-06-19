(function () {
    'use strict';

    function qs(selector, root) { return (root || document).querySelector(selector); }
    function qsa(selector, root) { return Array.prototype.slice.call((root || document).querySelectorAll(selector)); }

    function toLocalInputValue(dateValue, allDay) {
        if (!dateValue) { return ''; }
        var date = new Date(dateValue);
        if (Number.isNaN(date.getTime())) { return ''; }
        var pad = function (num) { return String(num).padStart(2, '0'); };
        if (allDay) {
            return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()) + 'T00:00';
        }
        return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()) + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
    }

    function dateOnly(dateValue) {
        var date = dateValue instanceof Date ? dateValue : new Date(dateValue);
        if (Number.isNaN(date.getTime())) { return ''; }
        var pad = function (num) { return String(num).padStart(2, '0'); };
        return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
    }

    function icon(name) {
        return '<i data-lucide="' + name + '" class="me-1"></i>';
    }

    function refreshIcons() {
        if (window.AptoriaIcons && typeof window.AptoriaIcons.refresh === 'function') {
            window.AptoriaIcons.refresh();
        } else if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    }

    function initCalendar() {
        var el = qs('#aptoriaFullCalendar');
        if (!el || typeof FullCalendar === 'undefined') { return; }

        var modalEl = qs('#calendarEventModal');
        var systemModalEl = qs('#calendarSystemLogModal');
        var eventModal = modalEl && window.bootstrap ? new bootstrap.Modal(modalEl, { backdrop: 'static' }) : null;
        var systemModal = systemModalEl && window.bootstrap ? new bootstrap.Modal(systemModalEl) : null;
        var form = qs('#calendarEventForm');
        var methodInput = form ? qs('input[name="_method"]', form) : null;
        var deleteForm = qs('#calendarEventDeleteForm');
        var completeForm = qs('#calendarEventCompleteForm');
        var modalTitle = qs('#calendarEventModalTitle');
        var deleteButton = qs('#calendarEventDeleteButton');
        var completeButton = qs('#calendarEventCompleteButton');
        var timeline = qs('#calendar-day-timeline');
        var timelineTitle = qs('#calendar-day-title');

        var translations = window.AptoriaCalendar || {};

        function setFormAction(url, method) {
            if (!form) { return; }
            form.setAttribute('action', url);
            if (methodInput) {
                if (method === 'PUT') {
                    methodInput.value = 'PUT';
                    methodInput.disabled = false;
                } else {
                    methodInput.value = '';
                    methodInput.disabled = true;
                }
            }
        }

        function setFormValues(data) {
            if (!form) { return; }
            var fields = ['title', 'description', 'event_type', 'status', 'priority', 'location'];
            fields.forEach(function (field) {
                var input = qs('[name="' + field + '"]', form);
                if (input) { input.value = data[field] || ''; }
            });
            var start = qs('[name="start_at"]', form);
            var end = qs('[name="end_at"]', form);
            var allDay = qs('[name="is_all_day"]', form);
            if (start) { start.value = toLocalInputValue(data.start_at, data.is_all_day); }
            if (end) { end.value = toLocalInputValue(data.end_at, data.is_all_day); }
            if (allDay) { allDay.checked = !!data.is_all_day; }
        }

        function openCreateModal(selection) {
            if (!eventModal || !form) { return; }
            form.reset();
            form.classList.remove('was-validated');
            if (modalTitle) { modalTitle.innerHTML = icon('calendar-plus') + (translations.newTitle || 'New calendar event'); }
            setFormAction(el.dataset.storeUrl, 'POST');
            setFormValues({
                title: '',
                description: '',
                event_type: selection && selection.event_type ? selection.event_type : 'manual_qa_task',
                status: 'planned',
                priority: selection && selection.priority ? selection.priority : 'normal',
                start_at: selection ? (selection.start || selection.date) : new Date(),
                end_at: selection ? selection.end : null,
                is_all_day: selection ? !!selection.allDay : false,
                location: ''
            });
            if (deleteButton) { deleteButton.classList.add('d-none'); }
            if (completeButton) { completeButton.classList.add('d-none'); }
            eventModal.show();
            refreshIcons();
        }

        function openEditModal(event) {
            if (!eventModal || !form) { return; }
            var props = event.extendedProps || {};
            form.reset();
            form.classList.remove('was-validated');
            if (modalTitle) { modalTitle.innerHTML = icon('pencil') + (translations.editTitle || 'Edit calendar event'); }
            setFormAction((el.dataset.updateUrlTemplate || '').replace('__EVENT_ID__', props.numericId || event.id), 'PUT');
            setFormValues({
                title: event.title,
                description: props.description || '',
                event_type: props.event_type || 'manual_qa_task',
                status: props.status || 'planned',
                priority: props.priority || 'normal',
                start_at: event.start,
                end_at: event.end,
                is_all_day: event.allDay,
                location: props.location || ''
            });
            if (deleteForm) { deleteForm.setAttribute('action', (el.dataset.deleteUrlTemplate || '').replace('__EVENT_ID__', props.numericId || event.id)); }
            if (completeForm) { completeForm.setAttribute('action', (el.dataset.completeUrlTemplate || '').replace('__EVENT_ID__', props.numericId || event.id)); }
            if (deleteButton) { deleteButton.classList.remove('d-none'); }
            if (completeButton) {
                completeButton.classList.toggle('d-none', props.status === 'completed');
            }
            eventModal.show();
            refreshIcons();
        }

        function openSystemModal(event) {
            if (!systemModalEl || !systemModal) { return; }
            var props = event.extendedProps || {};
            var title = qs('[data-system-log-title]', systemModalEl);
            var meta = qs('[data-system-log-meta]', systemModalEl);
            var summary = qs('[data-system-log-summary]', systemModalEl);
            var severity = qs('[data-system-log-severity]', systemModalEl);
            var user = qs('[data-system-log-user]', systemModalEl);
            if (title) { title.textContent = event.title || ''; }
            if (meta) { meta.textContent = (props.action_label || props.action || '') + ' · ' + (props.created_at || ''); }
            if (summary) { summary.textContent = props.summary || ''; }
            if (severity) { severity.textContent = props.severity_label || props.severity || ''; severity.className = 'badge badge-soft-' + (props.tone || 'secondary'); }
            if (user) { user.textContent = props.user || '—'; }
            systemModal.show();
            refreshIcons();
        }

        function eventPayload(info) {
            var event = info.event;
            return {
                start_at: event.start ? event.start.toISOString() : null,
                end_at: event.end ? event.end.toISOString() : null,
                is_all_day: event.allDay ? 1 : 0
            };
        }

        function moveEvent(info) {
            var event = info.event;
            var props = event.extendedProps || {};
            if (props.source === 'system_log' || props.locked) {
                info.revert();
                return;
            }
            var url = (el.dataset.moveUrlTemplate || '').replace('__EVENT_ID__', props.numericId || event.id);
            fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': el.dataset.csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(eventPayload(info))
            }).then(function (response) {
                if (!response.ok) { throw new Error('Move failed'); }
                return response.json();
            }).then(function () {
                loadDayTimeline(dateOnly(event.start || new Date()));
            }).catch(function () {
                info.revert();
                if (window.Swal) {
                    Swal.fire(translations.moveFailedTitle || 'Could not move event', translations.moveFailedText || 'The calendar item was not updated.', 'error');
                }
            });
        }

        function loadDayTimeline(date) {
            if (!timeline || !el.dataset.dayUrl) { return; }
            if (timelineTitle) { timelineTitle.textContent = date; }
            timeline.innerHTML = '<div class="list-group-item text-muted">' + (translations.loading || 'Loading…') + '</div>';
            fetch(el.dataset.dayUrl + '?date=' + encodeURIComponent(date), { headers: { 'Accept': 'application/json' } })
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    if (timelineTitle && payload.label) { timelineTitle.textContent = payload.label; }
                    if (!payload.items || !payload.items.length) {
                        timeline.innerHTML = '<div class="list-group-item text-muted">' + (translations.noDayEvents || 'No events for this day.') + '</div>';
                        return;
                    }
                    timeline.innerHTML = payload.items.map(function (item) {
                        var locked = item.locked ? '<span class="badge badge-soft-secondary ms-1">' + (translations.locked || 'Locked') + '</span>' : '';
                        return '<div class="list-group-item d-flex gap-2 align-items-start">'
                            + '<span class="avatar avatar-sm rounded text-bg-' + (item.tone || 'secondary') + '"><span class="avatar-title"><i data-lucide="' + (item.icon || 'calendar-check') + '"></i></span></span>'
                            + '<div class="min-w-0 flex-grow-1"><div class="d-flex justify-content-between gap-2"><strong class="text-truncate">' + item.title + '</strong><small class="text-muted text-nowrap">' + (item.time || '') + '</small></div>'
                            + '<small class="text-muted d-block">' + (item.meta || '') + locked + '</small>'
                            + (item.summary ? '<p class="mb-0 small text-muted text-truncate">' + item.summary + '</p>' : '')
                            + '</div></div>';
                    }).join('');
                    refreshIcons();
                }).catch(function () {
                    timeline.innerHTML = '<div class="list-group-item text-danger">' + (translations.dayLoadFailed || 'Could not load day timeline.') + '</div>';
                });
        }

        qsa('.btn-new-event').forEach(function (button) {
            button.addEventListener('click', function () { openCreateModal({ date: new Date(), allDay: true }); });
        });

        var draggableRoot = qs('#external-events');
        if (draggableRoot && FullCalendar.Draggable) {
            new FullCalendar.Draggable(draggableRoot, {
                itemSelector: '.external-event',
                eventData: function (eventEl) {
                    return {
                        title: eventEl.getAttribute('data-title') || eventEl.innerText.trim(),
                        classNames: eventEl.getAttribute('data-class') || '',
                        extendedProps: {
                            event_type: eventEl.getAttribute('data-event-type') || 'manual_qa_task',
                            priority: eventEl.getAttribute('data-priority') || 'normal'
                        }
                    };
                }
            });
        }

        var calendar = new FullCalendar.Calendar(el, {
            themeSystem: 'bootstrap5',
            initialView: 'dayGridMonth',
            height: 'auto',
            contentHeight: 'auto',
            expandRows: false,
            handleWindowResize: true,
            eventSources: [{ url: el.dataset.eventsUrl, method: 'GET' }],
            editable: true,
            droppable: true,
            selectable: true,
            eventResizableFromStart: true,
            dayMaxEvents: 3,
            nowIndicator: true,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
            },
            buttonText: translations.buttonText || {},
            select: function (info) {
                openCreateModal({ start: info.start, end: info.end, allDay: info.allDay });
                calendar.unselect();
                loadDayTimeline(dateOnly(info.start));
            },
            dateClick: function (info) {
                loadDayTimeline(info.dateStr);
            },
            eventClick: function (info) {
                var props = info.event.extendedProps || {};
                if (props.source === 'system_log' || props.locked) {
                    openSystemModal(info.event);
                    return;
                }
                openEditModal(info.event);
            },
            eventDrop: moveEvent,
            eventResize: moveEvent,
            eventReceive: function (info) {
                var props = info.event.extendedProps || {};
                openCreateModal({
                    title: info.event.title,
                    start: info.event.start,
                    end: info.event.end,
                    allDay: info.event.allDay,
                    event_type: props.event_type || 'manual_qa_task',
                    priority: props.priority || 'normal'
                });
                var titleInput = form ? qs('[name="title"]', form) : null;
                if (titleInput) { titleInput.value = info.event.title; }
                var typeInput = form ? qs('[name="event_type"]', form) : null;
                if (typeInput) { typeInput.value = props.event_type || 'manual_qa_task'; }
                var priorityInput = form ? qs('[name="priority"]', form) : null;
                if (priorityInput) { priorityInput.value = props.priority || 'normal'; }
                info.event.remove();
            }
        });

        calendar.render();
        loadDayTimeline(dateOnly(new Date()));
        refreshIcons();
    }

    document.addEventListener('DOMContentLoaded', initCalendar);
})();
