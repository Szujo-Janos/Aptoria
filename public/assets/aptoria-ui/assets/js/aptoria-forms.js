(function () {
    'use strict';

    var config = window.AptoriaFormPlugin || {};
    var help = config.help || {};
    var placeholders = config.placeholders || {};
    var labels = config.labels || {};
    var icons = config.icons || {};

    var defaultIcons = {
        name: 'file-text',
        title: 'file-text',
        email: 'user',
        password: 'lock',
        password_confirmation: 'lock',
        current_password: 'shield-check',
        base_url: 'globe',
        url: 'globe',
        path: 'git-compare',
        method: 'send',
        status: 'badge-check',
        source: 'radar',
        severity: 'triangle-alert',
        priority: 'star',
        environment_type: 'server',
        default_environment_id: 'server',
        environment_id: 'server',
        default_auth_profile_id: 'key-round',
        auth_profile_id: 'key-round',
        type: 'table-2',
        token: 'key-round',
        username: 'user',
        header_name: 'file-text',
        header_value: 'key-round',
        expected_status: 'file-text',
        expected_content_type: 'file-text',
        risk_level: 'triangle-alert',
        locale: 'languages',
        timezone: 'clock',
        due_date: 'calendar-days',
        report_organization: 'folder',
        report_prepared_by: 'user',
        report_role_title: 'badge-check',
        report_confidentiality_label: 'shield-check',
        finding_id: 'bug',
        endpoint_id: 'git-compare',
        scan_result_id: 'radar',
        source_label: 'file-text',
        owner_name: 'user'
    };

    function normalizeName(raw) {
        return (raw || '').replace(/\[\]$/, '').replace(/\[[^\]]*\]/g, '');
    }

    function isVisibleField(field) {
        if (!field || field.disabled) { return false; }
        var type = (field.getAttribute('type') || '').toLowerCase();
        return ['hidden', 'submit', 'button', 'reset'].indexOf(type) === -1;
    }

    function fieldScope(form) {
        return form.getAttribute('data-aptoria-form-scope') || form.getAttribute('data-aptoria-form') || 'common';
    }

    function helpFor(scope, name) {
        return (help[scope] && help[scope][name]) || (help.common && help.common[name]) || null;
    }

    function placeholderFor(scope, name) {
        return (placeholders[scope] && placeholders[scope][name]) || (placeholders.common && placeholders.common[name]) || null;
    }

    function applyPlaceholder(field, text) {
        if (!text || !field || field.tagName === 'SELECT' || field.classList.contains('form-check-input')) { return; }
        var type = (field.getAttribute('type') || '').toLowerCase();
        if (['checkbox', 'radio', 'file'].indexOf(type) !== -1) { return; }
        if (!field.getAttribute('placeholder')) {
            field.setAttribute('placeholder', text);
        }
    }

    function iconFor(scope, name, field) {
        var type = (field.getAttribute('type') || '').toLowerCase();
        return (icons[scope] && icons[scope][name]) || icons[name] || defaultIcons[name] || (field.tagName === 'SELECT' ? 'table-2' : (type === 'email' ? 'user' : (type === 'url' ? 'globe' : (type === 'password' ? 'lock' : 'help-circle'))));
    }

    function hasNearbyText(field, className) {
        var wrapper = field.closest('.input-group') || field;
        var parent = wrapper.parentElement;
        if (!parent) { return false; }
        return Array.prototype.slice.call(parent.children).some(function (node) {
            return node !== wrapper && node.classList && node.classList.contains(className) && node.getAttribute('data-aptoria-generated') === '1';
        }) || Array.prototype.slice.call(parent.children).some(function (node) {
            return node !== wrapper && node.classList && node.classList.contains(className) && node.getAttribute('data-aptoria-generated') !== '1';
        });
    }

    function insertAfter(reference, node) {
        if (!reference || !reference.parentNode) { return; }
        reference.parentNode.insertBefore(node, reference.nextSibling);
    }

    function addHelpText(field, text) {
        if (!text || hasNearbyText(field, 'form-text')) { return; }
        var wrapper = field.closest('.input-group') || field;
        var div = document.createElement('div');
        div.className = 'form-text aptoria-form-help-text';
        div.setAttribute('data-aptoria-generated', '1');
        div.textContent = text;
        insertAfter(wrapper, div);
    }

    function addInvalidFeedback(field) {
        if (!field.hasAttribute('required') || hasNearbyText(field, 'invalid-feedback')) { return; }
        var wrapper = field.closest('.input-group') || field;
        var div = document.createElement('div');
        div.className = 'invalid-feedback';
        div.setAttribute('data-aptoria-generated', '1');
        div.textContent = labels.required || 'This field is required.';
        insertAfter(wrapper, div);
    }

    function addRequiredMarker(field) {
        if (!field.hasAttribute('required')) { return; }
        var id = field.getAttribute('id');
        var label = id ? document.querySelector('label[for="' + CSS.escape(id) + '"]') : null;
        if (!label) {
            var group = field.closest('.col-12, .col-md-2, .col-md-3, .col-md-4, .col-md-5, .col-md-6, .col-md-7, .col-md-8, .col-lg-4, .col-lg-8, .col-xl-6, .col-xl-8, .col-xl-12, div');
            label = group ? group.querySelector('.form-label') : null;
        }
        if (!label || label.querySelector('.aptoria-required-marker')) { return; }
        var marker = document.createElement('span');
        marker.className = 'text-danger ms-1 aptoria-required-marker';
        marker.textContent = '*';
        label.appendChild(marker);
    }

    function wrapInputGroup(field, scope) {
        if (!field || field.closest('.input-group') || field.closest('.form-check') || field.classList.contains('form-check-input')) { return; }
        var tag = field.tagName;
        var type = (field.getAttribute('type') || '').toLowerCase();
        if (tag === 'TEXTAREA' || type === 'file' || type === 'checkbox' || type === 'radio') { return; }
        if (!(field.classList.contains('form-control') || field.classList.contains('form-select'))) { return; }
        var name = normalizeName(field.getAttribute('name'));
        var group = document.createElement('div');
        group.className = 'input-group aptoria-input-group';
        var span = document.createElement('span');
        span.className = 'input-group-text';
        span.innerHTML = '<i data-lucide="' + iconFor(scope, name, field) + '"></i>';
        field.parentNode.insertBefore(group, field);
        group.appendChild(span);
        group.appendChild(field);
    }

    function enhanceSwitch(field, scope) {
        if (!field.classList.contains('form-check-input')) { return; }
        var name = normalizeName(field.getAttribute('name'));
        var text = helpFor(scope, name);
        if (!text) { return; }
        var holder = field.closest('.form-check, label, .list-group-item');
        if (!holder || holder.querySelector('.aptoria-form-help-text')) { return; }
        var small = document.createElement('small');
        small.className = 'form-text aptoria-form-help-text d-block mt-1';
        small.setAttribute('data-aptoria-generated', '1');
        small.textContent = text;
        holder.appendChild(small);
    }

    function enhancePasswordStrength(field, scope) {
        if (scope === 'login') { return; }
        if (!field || field.getAttribute('type') !== 'password' || field.getAttribute('name') !== 'password') { return; }
        if (field.closest('form') && field.closest('form').querySelector('[data-aptoria-password-strength]')) { return; }
        var wrapper = field.closest('.input-group') || field;
        var meter = document.createElement('div');
        meter.className = 'aptoria-password-strength mt-2';
        meter.setAttribute('data-aptoria-password-strength', '1');
        meter.innerHTML = '<div class="progress progress-sm"><div class="progress-bar" style="width: 0%"></div></div><small class="text-muted d-block mt-1">' + (labels.password_strength || 'Password strength') + '</small>';
        insertAfter(wrapper, meter);
        var bar = meter.querySelector('.progress-bar');
        var label = meter.querySelector('small');
        field.addEventListener('input', function () {
            var value = field.value || '';
            var score = 0;
            if (value.length >= 8) { score += 25; }
            if (/[A-Z]/.test(value)) { score += 25; }
            if (/[0-9]/.test(value)) { score += 25; }
            if (/[^A-Za-z0-9]/.test(value)) { score += 25; }
            bar.style.width = score + '%';
            bar.className = 'progress-bar ' + (score < 50 ? 'bg-danger' : (score < 75 ? 'bg-warning' : 'bg-success'));
            label.textContent = (labels.password_strength || 'Password strength') + ': ' + score + '%';
        });
    }

    function enhanceForm(form) {
        if (!form || form.getAttribute('data-aptoria-form-enhanced') === '1') { return; }
        var fields = Array.prototype.slice.call(form.querySelectorAll('input, select, textarea')).filter(isVisibleField);
        if (!fields.length) { return; }
        var scope = fieldScope(form);
        form.setAttribute('data-aptoria-form-enhanced', '1');
        form.classList.add('needs-validation', 'aptoria-enhanced-form');
        form.setAttribute('novalidate', 'novalidate');

        fields.forEach(function (field) {
            var name = normalizeName(field.getAttribute('name'));
            if (!name) { return; }
            enhanceSwitch(field, scope);
            addRequiredMarker(field);
            applyPlaceholder(field, placeholderFor(scope, name));
            wrapInputGroup(field, scope);
            addHelpText(field, helpFor(scope, name));
            addInvalidFeedback(field);
            enhancePasswordStrength(field, scope);
        });

        if (window.AptoriaIcons && typeof window.AptoriaIcons.refresh === 'function') {
            window.AptoriaIcons.refresh(form);
        }
    }

    function init(scope) {
        var root = scope || document;
        root.querySelectorAll('form[data-aptoria-form-scope], form[data-aptoria-form-plugin]').forEach(enhanceForm);
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !form.matches || !form.matches('form[data-aptoria-form-scope], form[data-aptoria-form-plugin]')) { return; }
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            if (event.stopImmediatePropagation) { event.stopImmediatePropagation(); }
            form.classList.add('was-validated');
            var firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) { firstInvalid.focus({ preventScroll: true }); firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
        }
    }, true);

    document.addEventListener('DOMContentLoaded', function () {
        init(document);
        document.addEventListener('shown.bs.modal', function (event) { init(event.target); });
    });

    window.AptoriaForms = { init: init, enhanceForm: enhanceForm };
})();
