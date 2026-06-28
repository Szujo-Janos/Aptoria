(function () {
    'use strict';

    function refreshIcons() {
        if (window.AptoriaIcons && typeof window.AptoriaIcons.refresh === 'function') {
            window.AptoriaIcons.refresh();
            return;
        }
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    }

    function hasEmptyState(table) {
        return !!table.querySelector('tbody td[colspan]');
    }

    function hasActionColumn(table) {
        if (table.getAttribute('data-aptoria-actions') === 'true') {
            return true;
        }

        var headerCells = table.querySelectorAll('thead th');
        if (!headerCells.length) {
            return false;
        }

        var lastHeader = headerCells[headerCells.length - 1];
        var label = (lastHeader.textContent || '').trim().toLowerCase();

        return lastHeader.classList.contains('aptoria-actions-cell')
            || lastHeader.classList.contains('no-sort')
            || label === 'actions'
            || label === 'műveletek'
            || label === 'muveletek';
    }

    function isInitialized(table) {
        if (!window.DataTable || typeof window.DataTable.isDataTable !== 'function') {
            return table.dataset.aptoriaTableReady === '1';
        }
        return window.DataTable.isDataTable(table);
    }

    function baseLanguage() {
        return {
            paginate: {
                first: '<i class="ti ti-chevrons-left"></i>',
                previous: '<i class="ti ti-chevron-left"></i>',
                next: '<i class="ti ti-chevron-right"></i>',
                last: '<i class="ti ti-chevrons-right"></i>'
            }
        };
    }



    function updateScrollShell(shell) {
        if (!shell) { return; }

        window.requestAnimationFrame(function () {
            try {
                var delta = shell.scrollWidth - shell.clientWidth;
                var active = delta > 8;

                shell.classList.toggle('aptoria-table-scroll-active', active);
                shell.classList.toggle('aptoria-table-scroll-idle', !active);

                if (!active && shell.scrollLeft) {
                    shell.scrollLeft = 0;
                }
            } catch (error) {
                // Scrollbar state must never break table rendering.
            }
        });
    }

    function updateTableScrollState(table) {
        if (!table) { return; }

        updateScrollShell(table.closest('.table-responsive'));

        var container = table.closest('.dt-container');
        if (container) {
            container.querySelectorAll('.dt-scroll-body').forEach(function (shell) {
                updateScrollShell(shell);
            });
        }
    }

    function refreshAllScrollStates(scope) {
        scope = scope || document;
        scope.querySelectorAll('.table-responsive, .dt-scroll-body').forEach(function (shell) {
            updateScrollShell(shell);
        });
    }

    function safeAdjust(api) {
        if (!api) { return; }

        function adjustNow() {
            try {
                api.columns.adjust();
                if (api.responsive && typeof api.responsive.recalc === 'function') {
                    api.responsive.recalc();
                }
                if (api.table && typeof api.table === 'function') {
                    updateTableScrollState(api.table().node());
                }
            } catch (error) {
                // Layout adjustment must never break the page.
            }
            refreshAllScrollStates(document);
            refreshIcons();
        }

        window.setTimeout(adjustNow, 0);
        window.setTimeout(adjustNow, 150);
    }

    function tableOptions(table) {
        var name = table.getAttribute('data-tables') || '';
        var paging = table.getAttribute('data-aptoria-paging') === 'true';
        var searching = table.getAttribute('data-aptoria-search') !== 'false';
        var orderColumn = parseInt(table.getAttribute('data-aptoria-order-column') || '0', 10);
        var orderDirection = table.getAttribute('data-aptoria-order-dir') || 'asc';

        if (name === 'dashboard-projects') {
            searching = false;
        }

        var columnDefs = [
            { targets: 'no-sort', orderable: false }
        ];

        if (hasActionColumn(table)) {
            columnDefs.push({ targets: -1, orderable: false, searchable: false, responsivePriority: 1, className: 'text-end aptoria-actions-cell' });
        }

        return {
            responsive: {
                details: {
                    type: 'inline',
                    target: 'tr'
                }
            },
            autoWidth: true,
            scrollX: false,
            paging: paging,
            info: paging,
            searching: searching,
            order: [[orderColumn, orderDirection]],
            language: baseLanguage(),
            columnDefs: columnDefs,
            drawCallback: function () {
                safeAdjust(this.api());
            },
            initComplete: function () {
                safeAdjust(this.api());
            }
        };
    }

    function normalizeActionColumn(table) {
        var actionColumn = hasActionColumn(table);

        if (actionColumn) {
            var rows = table.querySelectorAll('tr');
            rows.forEach(function (row) {
                var cells = row.children;
                if (!cells || !cells.length) { return; }
                var last = cells[cells.length - 1];
                last.classList.add('aptoria-actions-cell');
                last.classList.add('text-end');
                if (last.tagName && last.tagName.toLowerCase() === 'th') {
                    last.classList.add('no-sort');
                }
            });
        }

        table.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function (toggle) {
            toggle.setAttribute('data-bs-boundary', 'viewport');
            toggle.setAttribute('data-bs-display', 'dynamic');
        });
    }

    function normalizeTableShell(table) {
        table.classList.add('aptoria-resource-table', 'w-100');
        table.style.width = '100%';
        table.style.maxWidth = '100%';

        var responsive = table.closest('.table-responsive');
        if (!responsive) {
            var wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }

        var card = table.closest('.card');
        if (card) {
            card.classList.add('aptoria-table-card');
        }

        normalizeActionColumn(table);
        updateTableScrollState(table);
    }

    function initAptoriaTables(scope) {
        scope = scope || document;

        scope.querySelectorAll('table[data-tables]').forEach(function (table) {
            normalizeTableShell(table);

            if (!window.DataTable || hasEmptyState(table) || isInitialized(table)) {
                refreshIcons();
                return;
            }

            table.dataset.aptoriaTableReady = '1';
            var instance = new window.DataTable(table, tableOptions(table));
            safeAdjust(instance);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initAptoriaTables(document);
        refreshAllScrollStates(document);
    });

    window.addEventListener('resize', function () {
        refreshAllScrollStates(document);
        if (!window.DataTable) { return; }
        document.querySelectorAll('table[data-tables]').forEach(function (table) {
            updateTableScrollState(table);
            if (window.DataTable.isDataTable && window.DataTable.isDataTable(table)) {
                try { safeAdjust(new window.DataTable(table)); } catch (error) {}
            }
        });
    });

    document.addEventListener('shown.bs.modal', function (event) {
        initAptoriaTables(event.target);
        refreshAllScrollStates(event.target);
        refreshIcons();
    });

    document.addEventListener('shown.bs.dropdown', function () {
        refreshIcons();
    });

    window.AptoriaTables = {
        refresh: initAptoriaTables
    };
})();
