<div class="aptoria-cookie-consent" data-aptoria-cookie-consent hidden aria-live="polite">
    <div class="aptoria-cookie-consent__icon" aria-hidden="true">
        <i data-lucide="cookie"></i>
    </div>
    <div class="aptoria-cookie-consent__content">
        <strong>Cookie preferences</strong>
        <p>
            Aptoria uses essential cookies/storage for security, sessions and remembering your choice.
            Optional analytics or marketing cookies stay off unless you accept them.
        </p>
    </div>
    <div class="aptoria-cookie-consent__actions">
        <button type="button" class="btn btn-sm btn-primary" data-aptoria-cookie-accept-all>Accept all</button>
        <button type="button" class="btn btn-sm btn-light text-dark" data-aptoria-cookie-essential>Essential only</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-aptoria-cookie-manage>Settings</button>
    </div>
</div>

<div class="aptoria-cookie-modal" data-aptoria-cookie-modal hidden role="dialog" aria-modal="true" aria-labelledby="aptoria-cookie-modal-title">
    <div class="aptoria-cookie-modal__backdrop" data-aptoria-cookie-close></div>
    <div class="aptoria-cookie-modal__panel">
        <div class="aptoria-cookie-modal__head">
            <div>
                <small class="aptoria-landing-eyebrow">Privacy controls</small>
                <h2 id="aptoria-cookie-modal-title">Cookie settings</h2>
            </div>
            <button type="button" class="aptoria-cookie-modal__close" data-aptoria-cookie-close aria-label="Close cookie settings">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="aptoria-cookie-option is-required">
            <div>
                <strong>Essential cookies/storage</strong>
                <p>Required for security, session handling and remembering your cookie choice.</p>
            </div>
            <span>Always active</span>
        </div>

        <label class="aptoria-cookie-option">
            <div>
                <strong>Analytics</strong>
                <p>Optional measurement to understand website usage if analytics are enabled later.</p>
            </div>
            <input type="checkbox" data-aptoria-cookie-toggle="analytics">
        </label>

        <label class="aptoria-cookie-option">
            <div>
                <strong>Marketing</strong>
                <p>Optional campaign or retargeting cookies if marketing tools are enabled later.</p>
            </div>
            <input type="checkbox" data-aptoria-cookie-toggle="marketing">
        </label>

        <div class="aptoria-cookie-modal__actions">
            <button type="button" class="btn btn-primary" data-aptoria-cookie-save>Save choices</button>
            <button type="button" class="btn btn-light text-dark" data-aptoria-cookie-essential>Essential only</button>
            <button type="button" class="btn btn-outline-primary" data-aptoria-cookie-accept-all>Accept all</button>
        </div>
    </div>
</div>

<script>
(function () {
    const STORAGE_KEY = 'aptoria_cookie_preferences_v1';
    const DEFAULTS = { essential: true, analytics: false, marketing: false, savedAt: null, version: '2026-06-28' };
    const banner = document.querySelector('[data-aptoria-cookie-consent]');
    const modal = document.querySelector('[data-aptoria-cookie-modal]');

    function readPreferences() {
        try {
            const raw = window.localStorage.getItem(STORAGE_KEY);
            return raw ? Object.assign({}, DEFAULTS, JSON.parse(raw)) : null;
        } catch (error) {
            return null;
        }
    }

    function writePreferences(values) {
        const preferences = Object.assign({}, DEFAULTS, values, {
            essential: true,
            savedAt: new Date().toISOString()
        });

        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(preferences));
        } catch (error) {
            document.cookie = 'aptoria_cookie_preferences=essential; Max-Age=31536000; Path=/; SameSite=Lax';
        }

        window.AptoriaCookiePreferences = preferences;
        window.dispatchEvent(new CustomEvent('aptoria:cookie-preferences', { detail: preferences }));
        return preferences;
    }

    function setToggles(preferences) {
        document.querySelectorAll('[data-aptoria-cookie-toggle]').forEach((input) => {
            input.checked = Boolean(preferences[input.dataset.aptoriaCookieToggle]);
        });
    }

    function openModal(event) {
        if (event) {
            event.preventDefault();
        }

        const preferences = readPreferences() || DEFAULTS;
        setToggles(preferences);
        if (modal) {
            modal.hidden = false;
        }
    }

    function closeModal() {
        if (modal) {
            modal.hidden = true;
        }
    }

    function hideBanner() {
        if (banner) {
            banner.hidden = true;
        }
    }

    function save(values) {
        writePreferences(values);
        hideBanner();
        closeModal();
    }

    function selectedValues() {
        const values = { analytics: false, marketing: false };
        document.querySelectorAll('[data-aptoria-cookie-toggle]').forEach((input) => {
            values[input.dataset.aptoriaCookieToggle] = input.checked;
        });
        return values;
    }

    document.addEventListener('click', function (event) {
        const settingsLink = event.target.closest('.aptoria-cookie-settings-link, [data-aptoria-cookie-manage]');
        if (settingsLink) {
            openModal(event);
            return;
        }

        if (event.target.closest('[data-aptoria-cookie-accept-all]')) {
            event.preventDefault();
            save({ analytics: true, marketing: true });
            return;
        }

        if (event.target.closest('[data-aptoria-cookie-essential]')) {
            event.preventDefault();
            save({ analytics: false, marketing: false });
            return;
        }

        if (event.target.closest('[data-aptoria-cookie-save]')) {
            event.preventDefault();
            save(selectedValues());
            return;
        }

        if (event.target.closest('[data-aptoria-cookie-close]')) {
            event.preventDefault();
            closeModal();
        }
    });

    window.AptoriaCookieConsent = {
        open: openModal,
        close: closeModal,
        preferences: function () { return readPreferences() || DEFAULTS; },
        has: function (category) { return Boolean((readPreferences() || DEFAULTS)[category]); }
    };

    const preferences = readPreferences();
    window.AptoriaCookiePreferences = preferences || DEFAULTS;

    if (!preferences && banner) {
        banner.hidden = false;
    }
})();
</script>
