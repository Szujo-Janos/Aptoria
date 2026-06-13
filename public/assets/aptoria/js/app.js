(function () {
    'use strict';

    function markUiReady() {
        if (!document.body) {
            return;
        }

        document.body.classList.remove('aptoria-js-loading');
        document.body.classList.add('aptoria-js-ready');
    }

    function scheduleUiReady() {
        markUiReady();
    }

    if (window.toastr) {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-center',
            newestOnTop: true,
            preventDuplicates: true,
            closeDuration: 200,
            showDuration: 250,
            hideDuration: 200,
            timeOut: 4200,
            extendedTimeOut: 1200
        };
    }

    var lastClickedSubmitButton = null;

    function closestScanForm(element) {
        while (element && element !== document) {
            if (element.matches && element.matches('form[data-aptoria-scan-form="true"]')) {
                return element;
            }
            element = element.parentNode;
        }

        return null;
    }

    function closestSuiteRunForm(element) {
        while (element && element !== document) {
            if (element.matches && element.matches('form[data-aptoria-suite-run-form="true"]')) {
                return element;
            }
            element = element.parentNode;
        }

        return null;
    }

    function getSubmitButton(form, event) {
        if (event && event.submitter) {
            return event.submitter;
        }

        if (lastClickedSubmitButton && (closestScanForm(lastClickedSubmitButton) === form || closestSuiteRunForm(lastClickedSubmitButton) === form)) {
            return lastClickedSubmitButton;
        }

        return form.querySelector('button[type="submit"], input[type="submit"]');
    }

    function disableSubmitButton(button) {
        if (!button) {
            return;
        }

        var loadingLabel = button.getAttribute('data-aptoria-submit-label') || button.textContent || 'Working...';
        button.disabled = true;
        button.classList.add('disabled');

        if (button.tagName.toLowerCase() === 'input') {
            button.value = loadingLabel;
        } else {
            button.innerHTML = loadingLabel;
        }
    }


    function getFormSubmitter(form, event) {
        if (event && event.submitter) {
            return event.submitter;
        }

        if (form.id) {
            return document.querySelector('[type="submit"][form="' + form.id + '"]');
        }

        return form.querySelector('button[type="submit"], input[type="submit"]');
    }

    function showConfirmDialog(options, onConfirm) {
        var title = options.title || 'Are you sure?';
        var text = options.text || '';
        var type = options.type || 'warning';
        var confirmButtonText = options.confirmButtonText || 'OK';
        var cancelButtonText = options.cancelButtonText || 'Cancel';

        var sweetAlertEnabled = !document.body || document.body.getAttribute('data-aptoria-sweetalert') !== 'disabled';

        if (sweetAlertEnabled && typeof window.swal === 'function') {
            window.swal({
                title: title,
                text: text,
                type: type,
                showCancelButton: true,
                confirmButtonText: confirmButtonText,
                cancelButtonText: cancelButtonText,
                closeOnConfirm: true,
                closeOnCancel: true
            }, function (isConfirm) {
                if (isConfirm) {
                    onConfirm();
                }
            });
            return;
        }

        var nativeConfirm = window['confirm'];
        if (text && typeof nativeConfirm === 'function' && !nativeConfirm(title + '\n\n' + text)) {
            return;
        }

        onConfirm();
    }

    function showFlashMessage() {
        var flash = document.getElementById('aptoria-flash-message');
        if (!flash) {
            return;
        }

        var title = flash.getAttribute('data-title') || 'Success';
        var message = flash.getAttribute('data-message') || '';
        var type = flash.getAttribute('data-type') || 'success';

        if (window.toastr && typeof window.toastr[type] === 'function') {
            window.toastr[type](message, title);
            return;
        }

        if ((!document.body || document.body.getAttribute('data-aptoria-sweetalert') !== 'disabled') && typeof window.swal === 'function') {
            window.swal({
                title: title,
                text: message,
                type: type,
                confirmButtonText: 'OK'
            });
        }
    }

    function handleConfirmedSubmit(event) {
        var form = event.target;
        if (!form || !form.matches || !form.matches('form[data-aptoria-confirm="true"]')) {
            return;
        }

        if (form.getAttribute('data-aptoria-confirmed') === 'true') {
            return;
        }

        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
        }

        var submitter = getFormSubmitter(form, event);
        var originalLabel = submitter ? (submitter.value || submitter.textContent || '') : '';

        showConfirmDialog({
            title: form.getAttribute('data-aptoria-confirm-title'),
            text: form.getAttribute('data-aptoria-confirm-text'),
            type: form.getAttribute('data-aptoria-confirm-type'),
            confirmButtonText: form.getAttribute('data-aptoria-confirm-button'),
            cancelButtonText: form.getAttribute('data-aptoria-cancel-button')
        }, function () {
            form.setAttribute('data-aptoria-confirmed', 'true');

            if (submitter) {
                var loadingLabel = submitter.getAttribute('data-aptoria-submit-label') || originalLabel;
                submitter.disabled = true;
                submitter.classList.add('disabled');

                if (submitter.tagName.toLowerCase() === 'input') {
                    submitter.value = loadingLabel;
                } else {
                    submitter.innerHTML = loadingLabel;
                }
            }

            if (form.matches && form.matches('form[data-aptoria-suite-run-form="true"]')) {
                submitWithSuiteRunAnimation(form, submitter);
                return;
            }

            HTMLFormElement.prototype.submit.call(form);
        });
    }

    function forceShowModal(modal) {
        modal.style.display = 'block';
        modal.removeAttribute('aria-hidden');
        modal.setAttribute('aria-modal', 'true');

        if (modal.className.indexOf('in') === -1) {
            modal.className += ' in';
        }
        if (modal.className.indexOf('aptoria-modal-force-open') === -1) {
            modal.className += ' aptoria-modal-force-open';
        }

        document.body.classList.add('modal-open');

        if (!document.querySelector('[data-aptoria-modal-backdrop="true"]')) {
            var backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade in aptoria-modal-fallback-backdrop';
            backdrop.setAttribute('data-aptoria-modal-backdrop', 'true');
            document.body.appendChild(backdrop);
        }
    }

    function showScanModal() {
        var modal = document.getElementById('aptoria-scan-modal');
        if (!modal) {
            return;
        }

        if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
            window.jQuery(modal).modal({
                backdrop: 'static',
                keyboard: false,
                show: true
            });

            // Bootstrap can fail silently if a vendor asset is stale/missing. Force visibility as a fallback.
            window.setTimeout(function () {
                if (window.getComputedStyle(modal).display === 'none') {
                    forceShowModal(modal);
                }
            }, 80);

            return;
        }

        forceShowModal(modal);
    }


    function showSuiteRunModal() {
        var modal = document.getElementById('aptoria-suite-run-modal');
        if (!modal) {
            return;
        }

        if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
            window.jQuery(modal).modal({
                backdrop: 'static',
                keyboard: false,
                show: true
            });

            window.setTimeout(function () {
                if (window.getComputedStyle(modal).display === 'none') {
                    forceShowModal(modal);
                }
            }, 80);

            return;
        }

        forceShowModal(modal);
    }

    function submitWithSuiteRunAnimation(form, submitter) {
        if (form.getAttribute('data-aptoria-suite-run-submitted') === 'true') {
            return;
        }

        form.setAttribute('data-aptoria-suite-run-submitted', 'true');
        disableSubmitButton(submitter || getFormSubmitter(form));
        showSuiteRunModal();

        window.setTimeout(function () {
            HTMLFormElement.prototype.submit.call(form);
        }, 1200);
    }

    function isSafeScanConfirmed(form) {
        if (!form.hasAttribute('data-aptoria-requires-confirm')) {
            return true;
        }

        var checkbox = form.querySelector('input[name="confirm_safe_scan"]');
        return !checkbox || checkbox.checked;
    }

    function handleScanSubmit(event) {
        var form = event.target;
        if (!form || !form.matches || !form.matches('form[data-aptoria-scan-form="true"]')) {
            return;
        }

        if (form.getAttribute('data-aptoria-submitted') === 'true') {
            return;
        }

        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            return;
        }

        if (!isSafeScanConfirmed(form)) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        form.setAttribute('data-aptoria-submitted', 'true');

        disableSubmitButton(getSubmitButton(form, event));
        showScanModal();

        window.setTimeout(function () {
            HTMLFormElement.prototype.submit.call(form);
        }, 1200);
    }


    function handleSuiteRunSubmit(event) {
        var form = event.target;
        if (!form || !form.matches || !form.matches('form[data-aptoria-suite-run-form="true"]')) {
            return;
        }

        if (form.getAttribute('data-aptoria-confirm') === 'true' && form.getAttribute('data-aptoria-confirmed') !== 'true') {
            return;
        }

        if (form.getAttribute('data-aptoria-suite-run-submitted') === 'true') {
            return;
        }

        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        submitWithSuiteRunAnimation(form, getSubmitButton(form, event));
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target) {
            return;
        }

        var button = target.closest ? target.closest('button[type="submit"], input[type="submit"]') : null;
        if (button && (closestScanForm(button) || closestSuiteRunForm(button))) {
            lastClickedSubmitButton = button;
        }
    }, true);





    function parseDashboardData(canvas) {
        try {
            return {
                labels: JSON.parse(canvas.getAttribute('data-labels') || '[]'),
                values: JSON.parse(canvas.getAttribute('data-values') || '[]')
            };
        } catch (error) {
            return { labels: [], values: [] };
        }
    }

    function showChartFallback(canvas, message) {
        var fallback = document.createElement('div');
        fallback.className = 'aptoria-chart-fallback text-center';
        fallback.innerHTML = '<div class="aptoria-empty-icon"><i class="fa fa-bar-chart"></i></div><p class="text-muted m-b-none">' + (message || 'No chart data available yet.') + '</p>';

        var wrapper = canvas.closest ? canvas.closest('.aptoria-chart-box') : null;
        if (wrapper) {
            wrapper.innerHTML = '';
            wrapper.appendChild(fallback);
            return;
        }

        if (canvas.parentNode) {
            canvas.parentNode.replaceChild(fallback, canvas);
        }
    }

    function prepareChartCanvas(canvas) {
        if (canvas.getAttribute('data-aptoria-chart-rendered') === 'true') {
            return false;
        }

        var wrapper = canvas.closest ? canvas.closest('.aptoria-chart-box') : null;
        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'aptoria-chart-box';
            canvas.parentNode.insertBefore(wrapper, canvas);
            wrapper.appendChild(canvas);
        }

        wrapper.style.height = '230px';
        wrapper.style.maxHeight = '230px';
        wrapper.style.minHeight = '230px';
        canvas.style.height = '230px';
        canvas.style.maxHeight = '230px';
        canvas.style.width = '100%';
        canvas.removeAttribute('height');
        canvas.setAttribute('data-aptoria-chart-rendered', 'true');

        return true;
    }

    function initDashboardCharts() {
        var canvases = Array.prototype.slice.call(document.querySelectorAll('.aptoria-chart'));
        if (!canvases.length) {
            return;
        }

        if (typeof Chart === 'undefined') {
            canvases.forEach(function (canvas) {
                showChartFallback(canvas, 'Chart library could not be loaded.');
            });
            return;
        }

        if (Chart.defaults && Chart.defaults.global) {
            Chart.defaults.global.defaultFontFamily = 'Open Sans, Helvetica Neue, Helvetica, Arial, sans-serif';
            Chart.defaults.global.defaultFontColor = '#6a6c6f';
            if (Chart.defaults.global.legend && Chart.defaults.global.legend.labels) {
                Chart.defaults.global.legend.labels.boxWidth = 10;
            }
        }

        canvases.forEach(function (canvas) {
            try {
                if (!prepareChartCanvas(canvas)) {
                    return;
                }

                var chartData = parseDashboardData(canvas);
                var type = canvas.getAttribute('data-chart-type') || 'bar';
                var values = chartData.values.map(function (value) { return Number(value) || 0; });
                var hasData = values.some(function (value) { return value > 0; });
                var labels = chartData.labels.length ? chartData.labels : ['No data'];

                var commonColors = [
                    'rgba(231, 76, 60, .82)',
                    'rgba(255, 182, 6, .82)',
                    'rgba(52, 73, 94, .75)',
                    'rgba(52, 152, 219, .82)',
                    'rgba(98, 203, 49, .82)',
                    'rgba(155, 89, 182, .82)',
                    'rgba(230, 126, 34, .82)'
                ];

                if (!hasData) {
                    labels = ['No data'];
                    values = type === 'doughnut' ? [1] : [0];
                    commonColors = ['rgba(220, 225, 232, .95)'];
                }

                var config;
                if (type === 'doughnut') {
                    config = {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: values,
                                backgroundColor: commonColors,
                                borderWidth: 0
                            }]
                        },
                        options: {
                            cutoutPercentage: 68,
                            responsive: true,
                            maintainAspectRatio: false,
                            legend: { position: 'bottom' },
                            tooltips: { enabled: hasData }
                        }
                    };
                } else if (type === 'line') {
                    config = {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Safe scans',
                                data: values,
                                backgroundColor: 'rgba(98, 203, 49, .12)',
                                borderColor: 'rgba(98, 203, 49, .95)',
                                pointBackgroundColor: '#62cb31',
                                pointBorderColor: '#ffffff',
                                pointRadius: 4,
                                borderWidth: 2,
                                lineTension: .35
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            legend: { display: false },
                            scales: {
                                yAxes: [{ ticks: { beginAtZero: true, precision: 0 }, gridLines: { color: '#eef0f3' } }],
                                xAxes: [{ gridLines: { display: false } }]
                            }
                        }
                    };
                } else {
                    config = {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Endpoints',
                                data: values,
                                backgroundColor: 'rgba(52, 152, 219, .82)',
                                borderColor: 'rgba(52, 152, 219, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            legend: { display: false },
                            scales: {
                                yAxes: [{ ticks: { beginAtZero: true, precision: 0 }, gridLines: { color: '#eef0f3' } }],
                                xAxes: [{ gridLines: { display: false } }]
                            }
                        }
                    };
                }

                new Chart(canvas.getContext('2d'), config);
            } catch (error) {
                showChartFallback(canvas, 'Chart could not be rendered.');
                if (window.console && window.console.warn) {
                    window.console.warn('Aptoria dashboard chart failed:', error);
                }
            }
        });
    }



    function initAptoriaProUi() {
        if (!document.body || document.body.getAttribute('data-aptoria-pro-ui-ready') === 'true') {
            return;
        }

        document.body.setAttribute('data-aptoria-pro-ui-ready', 'true');

        Array.prototype.slice.call(document.querySelectorAll('table.table')).forEach(function (table) {
            if (table.className.indexOf('table-hover') === -1) {
                table.className += ' table-hover';
            }
            if (table.className.indexOf('aptoria-pro-table') === -1) {
                table.className += ' aptoria-pro-table';
            }
        });

        Array.prototype.slice.call(document.querySelectorAll('.hpanel .panel-heading')).forEach(function (heading) {
            if (heading.querySelector('.panel-tools')) {
                heading.classList.add('aptoria-heading-with-tools');
            }
        });

        if (window.jQuery && typeof window.jQuery.fn.tooltip === 'function') {
            window.jQuery('[title]').tooltip({ container: 'body' });
        }
    }

    function initHelpSearch() {
        var input = document.querySelector('[data-aptoria-help-search="true"]');
        if (!input) {
            return;
        }

        var sections = Array.prototype.slice.call(document.querySelectorAll('[data-aptoria-help-section="true"]'));
        var noResults = document.getElementById('aptoria-help-no-results');

        function normalize(value) {
            return (value || '').toString().toLowerCase();
        }

        function filterSections() {
            var query = normalize(input.value).trim();
            var visible = 0;

            sections.forEach(function (section) {
                var haystack = normalize(section.getAttribute('data-search-text'));
                var match = query === '' || haystack.indexOf(query) !== -1;
                section.style.display = match ? '' : 'none';
                if (match) {
                    visible += 1;
                }
            });

            if (noResults) {
                noResults.classList.toggle('hidden', visible !== 0);
            }
        }

        input.addEventListener('input', filterSections);
        filterSections();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            showFlashMessage();
            initAptoriaProUi();
            initHelpSearch();
            initDashboardCharts();
            scheduleUiReady();
        });
    } else {
        showFlashMessage();
        initAptoriaProUi();
        initHelpSearch();
        initDashboardCharts();
        scheduleUiReady();
    }

    document.addEventListener('submit', handleConfirmedSubmit, true);
    document.addEventListener('submit', handleScanSubmit, true);
    document.addEventListener('submit', handleSuiteRunSubmit, true);
})();
