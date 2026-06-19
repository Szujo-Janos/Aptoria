(function () {
    'use strict';

    var originalFetch = window.fetch;
    if (typeof originalFetch === 'function') {
        window.fetch = function (input, init) {
            var url = typeof input === 'string' ? input : (input && input.url ? input.url : '');
            if (url && (url.indexOf('assets/data/translations/') !== -1 || url.indexOf('assets/translations/') !== -1)) {
                return Promise.resolve(new Response('{}', {
                    status: 200,
                    headers: { 'Content-Type': 'application/json' }
                }));
            }
            return originalFetch.apply(this, arguments);
        };
    }

    function ensureSidenavChartPlaceholder() {
        if (document.getElementById('sidenavUserchart')) {
            return;
        }

        var holder = document.getElementById('aptoria-sidenav-chart-placeholder');
        if (!holder) {
            holder = document.createElement('div');
            holder.id = 'aptoria-sidenav-chart-placeholder';
            holder.className = 'd-none';
            holder.setAttribute('aria-hidden', 'true');
        }

        holder.innerHTML = '<canvas id="sidenavUserchart" width="1" height="1"></canvas>';

        if (document.body) {
            document.body.appendChild(holder);
        }
    }

    // app.js creates the HOMER sidebar chart immediately while the script is parsed,
    // not on DOMContentLoaded. Therefore the placeholder must exist before app.js loads.
    ensureSidenavChartPlaceholder();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureSidenavChartPlaceholder);
    }
})();
