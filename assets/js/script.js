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

    var timer = document.getElementById('gw-timer');

    if (timer) {
        var sisa = parseInt(timer.getAttribute('data-seconds'), 10) || 900;

        setInterval(function () {
            sisa = Math.max(0, sisa - 1);

            var menit = Math.floor(sisa / 60);
            var detik = sisa % 60;

            timer.textContent = String(menit).padStart(2, '0') + ':' + String(detik).padStart(2, '0');

            if (sisa === 0) {
                timer.textContent = 'Waktu habis';
            }
        }, 1000);
    }
})();
