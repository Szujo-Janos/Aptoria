(function () {
    'use strict';

    var aliases = {
        'bell-ring': 'bell',
        'circle-help': 'help-circle',
        'circle-question-mark': 'help-circle',
        'clipboard-check': 'check-circle',
        'clipboard-list': 'clipboard-list',
        'clipboard-plus': 'clipboard-plus',
        'skip-forward': 'skip-forward',
        'tags': 'tags',
        'file-clock': 'file-text',
        'file-code': 'file-text',
        'folder-kanban': 'folder',
        'folder-plus': 'folder',
        'info': 'help-circle',
        'list-tree': 'table-2',
        'file-database': 'database',
        'key': 'key-round',
        'lock-check': 'shield-check',
        'settings-cog': 'tool',
        'lock-keyhole': 'lock',
        'map': 'globe',
        'message-square-warning': 'triangle-alert',
        'network': 'globe',
        'panel-top-open': 'panel-top',
        'pause-circle': 'pause-circle',
        'plus-circle': 'plus-circle',
        'radar': 'crosshair',
        'server-cog': 'server',
        'shield-alert': 'triangle-alert',
        'sparkles': 'star',
        'ticket': 'file-text',
        'user-round-cog': 'user-cog',
        'workflow': 'activity',
        'box': 'layout-grid',
        'external-link': 'arrow-up-right',
        'link': 'globe',
        'more-horizontal': 'ellipsis',
        'power': 'circle',
        'share-2': 'send',
        'unlock-keyhole': 'lock',
    };



    Object.assign(aliases, {
        'activity-heartbeat': 'activity-heartbeat',
        'arrows-diff': 'arrows-diff',
        'braces': 'file-json',
        'arrows-diff': 'git-compare',
        'circle-alert': 'shield-alert',
        'layers-3': 'layers',
        'sliders-horizontal': 'sliders-horizontal',
        'book-open': 'book-open',
        'boxes': 'layout-grid',
        'code-2': 'file-code-2',
        'database-zap': 'database',
        'file-input': 'file-input',
        'file-search': 'file-search',
        'package-plus': 'file-input',
        'package-search': 'file-search',
        'rocket': 'play-circle',
        'sitemap': 'sitemap',
        'stethoscope': 'clipboard-search',
        'table': 'table',
        'table-2': 'table',
        'terminal': 'file-code-2',
        'wrench': 'tool',
        'brackets-contain': 'brackets-contain',
        'calendar-check': 'calendar-check',
        'calendar-clock': 'calendar-clock',
        'clock-alert': 'clock-alert',
        'calendar-plus': 'calendar-plus',
        'calendar-stats': 'calendar-stats',
        'camera': 'camera',
        'certificate': 'certificate',
        'checklist': 'checklist',
        'clipboard-check': 'clipboard-check',
        'clipboard-search': 'clipboard-search',
        'code': 'file-code-2',
        'code-xml': 'file-code-2',
        'database-cog': 'database-cog',
        'database-backup': 'database-backup',
        'door-open': 'door-open',
        'file-badge': 'file-type-pdf',
        'file-check-2': 'file-check',
        'file-clock': 'file-clock',
        'file-code': 'file-code-2',
        'file-code-2': 'file-code-2',
        'file-delta': 'file-delta',
        'file-json': 'file-json',
        'file-pen-line': 'markdown',
        'file-plus': 'file-plus',
        'file-search': 'file-search',
        'file-type-pdf': 'file-type-pdf',
        'files': 'report-analytics',
        'folder-cog': 'folder-settings',
        'folder-dot': 'folder-kanban',
        'folder-git-2': 'git-fork',
        'folder-kanban': 'folder-kanban',
        'folder-open': 'folder-open',
        'folder-plus': 'folder-plus',
        'folder-settings': 'folder-settings',
        'gauge': 'gauge',
        'git-fork': 'git-fork',
        'git-pull-request-closed': 'git-pull-request-closed',
        'hierarchy': 'hierarchy',
        'list-checks': 'checklist',
        'list-plus': 'list-plus',
        'markdown': 'markdown',
        'package-check': 'package-check',
        'plug-connected': 'plug-connected',
        'radio': 'test-tube',
        'report-analytics': 'report-analytics',
        'route': 'plug-connected',
        'search-check': 'clipboard-search',
        'settings-2': 'tool',
        'shield-alert': 'shield-alert',
        'shield-chevron': 'shield-chevron',
        'shield-x': 'shield-x',
        'sitemap': 'sitemap',
        'table-export': 'table-export',
        'test-tube': 'test-tube',
        'tool': 'tool',
        'trash': 'trash-2',
        'unlock-keyhole': 'unlock-keyhole',
        'variable': 'variable',
        'workflow': 'workflow'
    });

    var tablerToAptoria = {
        'ti-activity': 'activity',
        'ti-activity-heartbeat': 'activity',
        'ti-alert-triangle': 'triangle-alert',
        'ti-arrow-left': 'arrow-left',
        'ti-arrow-right': 'arrow-right',
        'ti-arrow-up-right': 'arrow-up-right',
        'ti-chevron-left': 'chevron-left',
        'ti-chevron-right': 'chevron-right',
        'ti-chevron-up': 'chevron-up',
        'ti-chevron-down': 'chevron-down',
        'ti-chevrons-left': 'chevrons-left',
        'ti-chevrons-right': 'chevrons-right',
        'ti-clock': 'clock',
        'ti-device-floppy': 'save',
        'ti-dots': 'ellipsis',
        'ti-file-analytics': 'file-text',
        'ti-file-certificate': 'file-check',
        'ti-fingerprint': 'fingerprint',
        'ti-folder-check': 'folder-check',
        'ti-key': 'key-round',
        'ti-language': 'languages',
        'ti-lock': 'lock',
        'ti-login': 'log-in',
        'ti-point-filled': 'circle',
        'ti-player-play': 'play',
        'ti-refresh': 'refresh-cw',
        'ti-send-2': 'send',
        'ti-shield-lock': 'shield-check',
        'ti-table': 'table-2',
        'ti-user-circle': 'circle-user-round',
        'ti-world': 'globe',
        'ti-moon': 'moon',
        'ti-sun': 'sun',
        'ti-check': 'check',
        'ti-circle-check': 'check-circle',
        'ti-help-circle': 'help-circle',
        'ti-info-circle': 'help-circle',
        'ti-circle-x': 'circle-x',
        'ti-server': 'server',
        'ti-server-cog': 'server',
        'ti-database': 'database',
        'ti-database-cog': 'database',
        'ti-file-database': 'database',
        'ti-file-text': 'file-text',
        'ti-brand-php': 'file-code-2',
        'ti-folder': 'folder',
        'ti-folder-cog': 'folder-settings',
        'ti-settings-cog': 'settings',
        'ti-user-cog': 'user-cog',
        'ti-rocket': 'play-circle',
        'ti-sparkles': 'star',
        'ti-shield-check': 'shield-check',
        'ti-shield-exclamation': 'shield-alert',
        'ti-key': 'key-round',
        'ti-lock-check': 'lock',
        'ti-route': 'plug-connected'
    };

    var paths = {
        'activity': '<path d="M22 12h-4l-3 8-6-16-3 8H2"/>',
        'archive': '<rect width="20" height="5" x="2" y="3" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/>',
        'archive-restore': '<rect width="20" height="5" x="2" y="3" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/><path d="m9 14 3-3 3 3"/><path d="M12 11v6"/>',
        'arrow-left': '<path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>',
        'arrow-right': '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'arrow-up-right': '<path d="M7 17 17 7"/><path d="M7 7h10v10"/>',
        'badge-check': '<path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.78 4.78 4 4 0 0 1-6.74 0 4 4 0 0 1-4.78-4.78 4 4 0 0 1 0-6.75Z"/><path d="m9 12 2 2 4-4"/>',
        'bell': '<path d="M10 21h4"/><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>',
        'bug': '<path d="m8 2 1.5 1.5"/><path d="M14.5 3.5 16 2"/><path d="M9 7h6"/><path d="M7 7a5 5 0 0 0 10 0"/><path d="M6 13H2"/><path d="M22 13h-4"/><path d="M6 17H3"/><path d="M21 17h-3"/><path d="M8 7v12a4 4 0 0 0 8 0V7"/>',
        'calendar-days': '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/>',
        'check': '<path d="M20 6 9 17l-5-5"/>',
        'check-circle': '<path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/>',
        'chevron-left': '<path d="m15 18-6-6 6-6"/>',
        'chevron-right': '<path d="m9 18 6-6-6-6"/>',
        'chevron-up': '<path d="m18 15-6-6-6 6"/>',
        'chevron-down': '<path d="m6 9 6 6 6-6"/>',
        'chevrons-left': '<path d="m11 17-5-5 5-5"/><path d="m18 17-5-5 5-5"/>',
        'chevrons-right': '<path d="m6 17 5-5-5-5"/><path d="m13 17 5-5-5-5"/>',
        'circle': '<circle cx="12" cy="12" r="4" fill="currentColor" stroke="none"/>',
        'circle-x': '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>',
        'circle-user-round': '<circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="12" r="10"/>',
        'clipboard-list': '<rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 12h6"/><path d="M9 16h6"/>',
        'clipboard-plus': '<rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11v6"/><path d="M9 14h6"/>',
        'clock': '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'clock-alert': '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l3 2"/><path d="M12 17h.01"/><path d="M12 8v4"/>',
        'crosshair': '<circle cx="12" cy="12" r="10"/><path d="M22 12h-4"/><path d="M6 12H2"/><path d="M12 6V2"/><path d="M12 22v-4"/>',
        'database': '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.7 4 3 9 3s9-1.3 9-3V5"/><path d="M3 12c0 1.7 4 3 9 3s9-1.3 9-3"/>',
        'ellipsis': '<circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/>',
        'eye': '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'file-check': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="m9 15 2 2 4-4"/>',
        'file-text': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h8"/><path d="M8 9h1"/>',
        'folder': '<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7l-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/>',
        'folder-check': '<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7l-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/><path d="m9 14 2 2 4-4"/>',
        'git-compare': '<circle cx="18" cy="18" r="3"/><circle cx="6" cy="6" r="3"/><path d="M13 6h3a2 2 0 0 1 2 2v7"/><path d="M6 9v12"/>',
        'globe': '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15 15 0 0 1 0 20"/><path d="M12 2a15 15 0 0 0 0 20"/>',
        'help-circle': '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 1 1 5.8 1c-.8 1.2-2.9 1.6-2.9 3"/><path d="M12 17h.01"/>',
        'key-round': '<path d="M2 18a6 6 0 1 0 10.9-3.4L22 5.5V2h-3.5l-9.1 9.1A6 6 0 0 0 2 18Z"/><circle cx="8" cy="18" r="1"/>',
        'languages': '<path d="m5 8 6 6"/><path d="m4 14 6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/><path d="m22 22-5-10-5 10"/><path d="M14 18h6"/>',
        'layout-dashboard': '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
        'layout-grid': '<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>',
        'lock': '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'log-in': '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/>',
        'log-out': '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'menu': '<path d="M4 12h16"/><path d="M4 6h16"/><path d="M4 18h16"/>',
        'moon': '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
        'panel-top': '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/>',
        'pause-circle': '<circle cx="12" cy="12" r="10"/><path d="M10 15V9"/><path d="M14 15V9"/>',
        'pencil': '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'plus': '<path d="M5 12h14"/><path d="M12 5v14"/>',
        'plus-circle': '<circle cx="12" cy="12" r="10"/><path d="M8 12h8"/><path d="M12 8v8"/>',
        'play': '<polygon points="6 3 20 12 6 21 6 3"/>',
        'refresh-cw': '<path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/>',
        'save': '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/>',
        'scroll-text': '<path d="M8 21h8"/><path d="M12 21V3"/><path d="M5 3h14"/><path d="M7 7h10"/><path d="M7 11h10"/><path d="M7 15h10"/>',
        'send': '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
        'server': '<rect width="20" height="8" x="2" y="2" rx="2"/><rect width="20" height="8" x="2" y="14" rx="2"/><path d="M6 6h.01"/><path d="M6 18h.01"/>',
        'settings': '<path d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5Z"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.6-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.6V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V9c.3.6.9 1 1.6 1h.1a2 2 0 1 1 0 4H21a1.7 1.7 0 0 0-1.6 1Z"/>',
        'shield-check': '<path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3Z"/><path d="m9 12 2 2 4-4"/>',
        'star': '<path d="M11.5 2.8 8.4 9.1l-6.9 1 5 4.9-1.2 6.9 6.2-3.3 6.2 3.3-1.2-6.9 5-4.9-6.9-1Z"/>',
        'sun': '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="M4.93 4.93l1.41 1.41"/><path d="M17.66 17.66l1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="M6.34 17.66l-1.41 1.41"/><path d="M19.07 4.93l-1.41 1.41"/>',
        'table-2': '<path d="M9 3v18"/><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M3 15h18"/>',
        'trash-2': '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/>',
        'triangle-alert': '<path d="m21.7 18-8-14a2 2 0 0 0-3.4 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'user': '<path d="M19 21a7 7 0 0 0-14 0"/><circle cx="12" cy="7" r="4"/>',
        'user-cog': '<circle cx="10" cy="8" r="4"/><path d="M2 21a8 8 0 0 1 12.5-6.6"/><circle cx="19" cy="19" r="2"/><path d="M19 15v2"/><path d="M19 21v1"/><path d="M16.5 16.5l1.4 1.4"/><path d="M20.1 20.1l1.4 1.4"/>',
        'users-round': '<path d="M17 21a5 5 0 0 0-10 0"/><circle cx="12" cy="8" r="4"/><path d="M22 21a4.5 4.5 0 0 0-5-4"/><path d="M2 21a4.5 4.5 0 0 1 5-4"/><circle cx="18" cy="9" r="3"/><circle cx="6" cy="9" r="3"/>',
        'user-plus': '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/>',
        'user-check': '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m16 11 2 2 4-4"/>',
        'user-x': '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m17 8 5 5"/><path d="m22 8-5 5"/>',
        'mail': '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-10 6L2 7"/>',
        'id-card': '<rect width="20" height="14" x="2" y="5" rx="2"/><circle cx="8" cy="12" r="2"/><path d="M12 10h6"/><path d="M12 14h4"/><path d="M5 16a3 3 0 0 1 6 0"/>',
        'copy-check': '<rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/><path d="m11 15 2 2 4-4"/>',
        'history': '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 3v6h6"/><path d="M12 7v5l3 2"/>',
        'hierarchy': '<path d="M12 5v6"/><path d="M6 13v3"/><path d="M18 13v3"/><path d="M6 13h12"/><rect width="8" height="4" x="8" y="1" rx="1"/><rect width="8" height="4" x="2" y="16" rx="1"/><rect width="8" height="4" x="14" y="16" rx="1"/>',
        'monitor-check': '<rect width="18" height="12" x="3" y="4" rx="2"/><path d="M8 20h8"/><path d="M12 16v4"/><path d="m9 10 2 2 4-4"/>',
        'scan-eye': '<path d="M7 3H5a2 2 0 0 0-2 2v2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M2 12s3.5-5 10-5 10 5 10 5-3.5 5-10 5-10-5-10-5Z"/><circle cx="12" cy="12" r="2"/>',
        'scan-search': '<path d="M7 3H5a2 2 0 0 0-2 2v2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><circle cx="11" cy="11" r="4"/><path d="m14 14 4 4"/>',
        'undo-2': '<path d="M9 14 4 9l5-5"/><path d="M4 9h11a4 4 0 0 1 0 8H7"/>',
        'combine': '<path d="M7 2h10a2 2 0 0 1 2 2v10"/><path d="M5 10H4a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1"/><path d="M9 15h6V9"/>',
        'x-circle': '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>',
        'gauge': '<path d="M4 19a8 8 0 1 1 16 0"/><path d="M12 14l4-4"/><path d="M8 19h8"/>',
        'fingerprint': '<path d="M2 12C2 6.5 6.5 2 12 2s10 4.5 10 10"/><path d="M6 12a6 6 0 0 1 12 0"/><path d="M10 20c.5-1.6.8-3.2.8-4.8 0-1.1.9-2 2-2s2 .9 2 2c0 2-.3 4-.9 5.8"/><path d="M14.9 9.6A4 4 0 0 0 8 12.3"/><path d="M4 16c.5-1.5.7-3 .7-4.5A7.3 7.3 0 0 1 12 4.2"/><path d="M18.4 17c.3-1.3.5-2.7.5-4"/>',
        'activity-heartbeat': '<path d="M22 12h-4l-3 8-6-16-3 8H2"/><path d="M4 19h16"/>',
        'bar-chart-3': '<path d="M3 3v18h18"/><path d="M8 17V9"/><path d="M13 17V5"/><path d="M18 17v-4"/>',
        'calendar-clock': '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><circle cx="12" cy="16" r="3"/><path d="M12 14.5V16l1 1"/>',
        'calendar-plus': '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M12 14v5"/><path d="M9.5 16.5h5"/>',
        'calendar-stats': '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 18v-3"/><path d="M12 18v-6"/><path d="M16 18v-4"/>',
        'camera': '<path d="M14.5 4 16 7h3a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h3l1.5-3Z"/><circle cx="12" cy="13" r="3"/>',
        'certificate': '<circle cx="12" cy="8" r="5"/><path d="M8.5 12.5 7 22l5-3 5 3-1.5-9.5"/><path d="m10 8 1.5 1.5L14.5 6"/>',
        'checklist': '<path d="m3 6 1.5 1.5L8 4"/><path d="M10 6h11"/><path d="m3 12 1.5 1.5L8 10"/><path d="M10 12h11"/><path d="m3 18 1.5 1.5L8 16"/><path d="M10 18h11"/>',
        'clipboard-check': '<rect width="16" height="18" x="4" y="4" rx="2"/><path d="M9 2h6v4H9z"/><path d="m8.5 14 2 2 5-5"/>',
        'clipboard-search': '<rect width="16" height="18" x="4" y="4" rx="2"/><path d="M9 2h6v4H9z"/><circle cx="11" cy="14" r="3"/><path d="m13.5 16.5 2 2"/>',
        'door-open': '<path d="M13 4h5a2 2 0 0 1 2 2v14"/><path d="M13 20V4L4 6v14Z"/><path d="M4 20h18"/><path d="M10 12h.01"/>',
        'file-code-2': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="m10 13-2 2 2 2"/><path d="m14 13 2 2-2 2"/>',
        'file-delta': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M12 12v6"/><path d="m9 15 3-3 3 3"/>',
        'file-warning': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M12 12v4"/><path d="M12 19h.01"/>',
        'flask-conical': '<path d="M10 2v7.5L4.5 20a2 2 0 0 0 1.7 3h11.6a2 2 0 0 0 1.7-3L14 9.5V2"/><path d="M8 2h8"/><path d="M7 16h10"/>',
        'octagon-alert': '<path d="M7.9 2h8.2L22 7.9v8.2L16.1 22H7.9L2 16.1V7.9Z"/><path d="M12 7v6"/><path d="M12 17h.01"/>',
        'toggle-right': '<rect width="20" height="12" x="2" y="6" rx="6"/><circle cx="16" cy="12" r="3"/>',
        'file-json': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M9 13c-1 0-1 1-1 2s0 2-1 2"/><path d="M15 13c1 0 1 1 1 2s0 2 1 2"/>',
        'file-clock': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><circle cx="12" cy="15" r="3"/><path d="M12 13.5V15l1 1"/>',
        'file-plus': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M12 12v6"/><path d="M9 15h6"/>',
        'file-type-pdf': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M7 16h1.5a1.5 1.5 0 0 0 0-3H7v5"/><path d="M11 13v5h1.5a2.5 2.5 0 0 0 0-5Z"/><path d="M16 18v-5h3"/><path d="M16 15h2"/>',
        'folder-kanban': '<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7l-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/><path d="M8 10v6"/><path d="M12 10v6"/><path d="M16 10v6"/>',
        'folder-open': '<path d="M6 14h14l-2 6H4l2-6Z"/><path d="M4 14V6a2 2 0 0 1 2-2h5l2 2h5a2 2 0 0 1 2 2v6"/>',
        'folder-plus': '<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7l-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/><path d="M12 10v6"/><path d="M9 13h6"/>',
        'folder-settings': '<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7l-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/><circle cx="16" cy="14" r="2"/><path d="M16 10v1"/><path d="M16 17v1"/>',
        'git-fork': '<circle cx="6" cy="6" r="3"/><circle cx="18" cy="6" r="3"/><circle cx="12" cy="18" r="3"/><path d="M6 9v1a4 4 0 0 0 4 4h2"/><path d="M18 9v1a4 4 0 0 1-4 4h-2"/><path d="M12 14v1"/>',
        'git-pull-request-closed': '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M6 9v6"/><path d="M18 6l-6 6"/><path d="m12 6 6 6"/>',
        'list-plus': '<path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h7"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/><path d="M19 17v4"/><path d="M17 19h4"/>',
        'layers': '<path d="m12 2 10 5-10 5L2 7l10-5Z"/><path d="m2 12 10 5 10-5"/><path d="m2 17 10 5 10-5"/>',
        'markdown': '<rect width="20" height="14" x="2" y="5" rx="2"/><path d="M6 15V9l3 3 3-3v6"/><path d="M17 9v6"/><path d="m15 13 2 2 2-2"/>',
        'plug-connected': '<path d="M7 7v4"/><path d="M17 7v4"/><path d="M9 7h6"/><path d="M9 11h6"/><path d="M12 11v4"/><path d="M8 21h8"/><path d="M12 15v6"/><path d="M12 3v4"/>',
        'report-analytics': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 17v-3"/><path d="M12 17v-6"/><path d="M16 17v-4"/>',
        'search': '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'shield-alert': '<path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3Z"/><path d="M12 8v5"/><path d="M12 17h.01"/>',
        'shield-chevron': '<path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3Z"/><path d="m8 12 4-4 4 4"/><path d="m8 16 4-4 4 4"/>',
        'test-tube': '<path d="M10 2v7.3a6 6 0 1 0 4 0V2"/><path d="M8 2h8"/><path d="M8.5 14h7"/>',
        'tool': '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-3 3-3-3 3-3Z"/>',
        'unlock-keyhole': '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 9.5-2"/>',
        'user-circle': '<circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="12" r="10"/>',
        'workflow': '<path d="M5 6h4"/><path d="M15 18h4"/><path d="M9 6a6 6 0 0 1 6 6v6"/><path d="M15 12h4"/><circle cx="5" cy="6" r="2"/><circle cx="19" cy="12" r="2"/><circle cx="19" cy="18" r="2"/>',
        'copy': '<rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
        'download': '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/>',
        'filter': '<path d="M22 3H2l8 9.5V20l4 2v-9.5Z"/>',
        'flame': '<path d="M8.5 14.5A2.5 2.5 0 0 0 11 17c0-2 1-3 2-4 1-1 2-2.5 2-5 2 1.5 4 4 4 7a7 7 0 1 1-14 0c0-2 1-4 3-6 0 2 0 3.5.5 5.5Z"/>',
        'play-circle': '<circle cx="12" cy="12" r="10"/><path d="m10 8 6 4-6 4Z"/>',
        'rotate-ccw': '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 3v6h6"/>',
        'alert-triangle': '<path d="m21.7 18-8-14a2 2 0 0 0-3.4 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'brackets-contain': '<path d="M8 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3"/><path d="M16 3h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3"/><path d="M9 8h6"/><path d="M9 12h6"/><path d="M9 16h6"/>',
        'package-check': '<path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/><path d="m9 17 2 2 4-4"/>',
        'book-open': '<path d="M12 7v14"/><path d="M3 18a2 2 0 0 1 2-2h7V5H5a2 2 0 0 0-2 2Z"/><path d="M21 18a2 2 0 0 0-2-2h-7V5h7a2 2 0 0 1 2 2Z"/>',
        'file-input': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="m9 15 3 3 3-3"/>',
        'file-search': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><circle cx="11" cy="15" r="3"/><path d="m13.5 17.5 2 2"/>',
        'sitemap': '<path d="M12 3v4"/><path d="M6 11h12"/><path d="M6 11v4"/><path d="M18 11v4"/><rect width="6" height="4" x="9" y="3" rx="1"/><rect width="6" height="4" x="3" y="17" rx="1"/><rect width="6" height="4" x="15" y="17" rx="1"/>',
        'shield-x': '<path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3Z"/><path d="m9 9 6 6"/><path d="m15 9-6 6"/>',
        'sliders-horizontal': '<path d="M21 4h-7"/><path d="M10 4H3"/><circle cx="12" cy="4" r="2"/><path d="M21 12h-9"/><path d="M8 12H3"/><circle cx="10" cy="12" r="2"/><path d="M21 20h-5"/><path d="M12 20H3"/><circle cx="14" cy="20" r="2"/>',
        'table': '<rect width="18" height="14" x="3" y="5" rx="2"/><path d="M3 10h18"/><path d="M9 5v14"/><path d="M15 5v14"/>',
        'x': '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>'
    };

    function clean(name) {
        if (!name || name.indexOf('{{') !== -1) { return 'activity'; }
        return aliases[name] || name;
    }

    function buildSvg(name) {
        var normalized = clean(name);
        var path = paths[normalized] || paths[aliases[normalized]] || paths.activity;
        return '<svg class="aptoria-icon-svg" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' + path + '</svg>';
    }

    function renderLucide(scope) {
        (scope || document).querySelectorAll('[data-lucide]').forEach(function (node) {
            var iconName = node.getAttribute('data-lucide');
            node.innerHTML = buildSvg(iconName);
            node.removeAttribute('data-lucide');
            node.setAttribute('data-aptoria-icon', iconName || 'activity');
            node.classList.add('aptoria-icon-ready');
        });
    }

    function tablerFontEnabled() {
        return document.documentElement.classList.contains('aptoria-tabler-fonts-enabled') ||
            document.body.classList.contains('aptoria-tabler-fonts-enabled');
    }

    function renderTabler(scope) {
        if (tablerFontEnabled()) {
            (scope || document).querySelectorAll('i.ti').forEach(function (node) {
                node.classList.add('aptoria-icon-ready');
                node.setAttribute('aria-hidden', 'true');
            });
            return;
        }
        (scope || document).querySelectorAll('i.ti').forEach(function (node) {
            var iconClass = Array.prototype.slice.call(node.classList).find(function (className) {
                return className.indexOf('ti-') === 0 && className !== 'ti';
            });
            if (!iconClass) { return; }
            var mapped = tablerToAptoria[iconClass] || iconClass.replace(/^ti-/, '');
            node.innerHTML = buildSvg(mapped);
            Array.prototype.slice.call(node.classList).forEach(function (className) {
                if (className === 'ti' || className.indexOf('ti-') === 0) {
                    node.classList.remove(className);
                }
            });
            node.classList.add('aptoria-icon-ready');
            node.setAttribute('data-aptoria-icon', mapped);
            node.setAttribute('aria-hidden', 'true');
        });
    }

    function refresh(scope) {
        renderLucide(scope || document);
        renderTabler(scope || document);
    }

    window.AptoriaIcons = { refresh: refresh, renderTabler: renderTabler, renderRemainingLucide: renderLucide };

    document.addEventListener('DOMContentLoaded', function () {
        refresh(document);
        document.addEventListener('shown.bs.modal', function (event) { refresh(event.target); });
        document.addEventListener('shown.bs.dropdown', function (event) { refresh(event.target); });
        document.addEventListener('draw.dt', function () { refresh(document); });
        if (window.MutationObserver) {
            var timer;
            var observer = new MutationObserver(function (mutations) {
                var needsRefresh = mutations.some(function (mutation) {
                    return Array.prototype.slice.call(mutation.addedNodes || []).some(function (node) {
                        return node.nodeType === 1 && ((node.matches && (node.matches('[data-lucide], i.ti') || node.querySelector('[data-lucide], i.ti'))));
                    });
                });
                if (needsRefresh) {
                    window.clearTimeout(timer);
                    timer = window.setTimeout(function () { refresh(document); }, 50);
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
        window.setTimeout(function () { refresh(document); }, 300);
        window.setTimeout(function () { refresh(document); }, 1200);
    });
})();
