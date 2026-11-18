(function () {
    'use strict';

    document.querySelectorAll('[data-amount]').forEach(function (chip) {
        chip.addEventListener('click', function () {
            var input = document.getElementById('nominal');
            if (!input) return;

            input.value = chip.getAttribute('data-amount');
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.focus();
        });
    });

    document.querySelectorAll('[data-copy]').forEach(function (button) {
        button.addEventListener('click', function () {
            var text = button.getAttribute('data-copy');
            var label = button.textContent;

            var done = function () {
                button.textContent = 'Tersalin';
                setTimeout(function () { button.textContent = label; }, 1600);
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done, done);
            } else {
                var area = document.createElement('textarea');
                area.value = text;
                document.body.appendChild(area);
                area.select();
                try { document.execCommand('copy'); } catch (e) {}
                document.body.removeChild(area);
                done();
            }
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('.site-nav a').forEach(function (link) {
        link.addEventListener('click', function () {
            var toggle = document.getElementById('nav-toggle');
            if (toggle) toggle.checked = false;
        });
    });
})();
