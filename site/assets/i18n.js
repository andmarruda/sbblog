/*
 * English lives in the HTML. Each page defines window.SBBLOG_PT with the Portuguese text for
 * every [data-i18n] key. The first visit follows the browser language; a choice is remembered.
 */
(function () {
    var STORAGE_KEY = 'sbblog-site-lang';
    var english = {};

    function stored() {
        try {
            return localStorage.getItem(STORAGE_KEY);
        } catch (e) {
            return null;
        }
    }

    function remember(lang) {
        try {
            localStorage.setItem(STORAGE_KEY, lang);
        } catch (e) {
            /* private mode: the choice just isn't remembered */
        }
    }

    function nodes() {
        return document.querySelectorAll('[data-i18n]');
    }

    function apply(lang) {
        var pt = window.SBBLOG_PT || {};

        nodes().forEach(function (node) {
            var key = node.getAttribute('data-i18n');
            var text = lang === 'pt' && pt[key] !== undefined ? pt[key] : english[key];

            if (node.hasAttribute('data-i18n-attr')) {
                node.setAttribute(node.getAttribute('data-i18n-attr'), text);
            } else if (node.hasAttribute('data-i18n-html')) {
                node.innerHTML = text;
            } else {
                node.textContent = text;
            }
        });

        document.documentElement.lang = lang === 'pt' ? 'pt-BR' : 'en';

        document.querySelectorAll('[data-lang]').forEach(function (button) {
            button.setAttribute('aria-pressed', String(button.getAttribute('data-lang') === lang));
        });
    }

    function init() {
        nodes().forEach(function (node) {
            var key = node.getAttribute('data-i18n');

            if (node.hasAttribute('data-i18n-attr')) {
                english[key] = node.getAttribute(node.getAttribute('data-i18n-attr'));
            } else if (node.hasAttribute('data-i18n-html')) {
                english[key] = node.innerHTML;
            } else {
                english[key] = node.textContent;
            }
        });

        var lang = stored() || ((navigator.language || '').toLowerCase().indexOf('pt') === 0 ? 'pt' : 'en');

        document.querySelectorAll('[data-lang]').forEach(function (button) {
            button.addEventListener('click', function () {
                var next = button.getAttribute('data-lang');
                remember(next);
                apply(next);
            });
        });

        apply(lang);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
