(function () {
    'use strict';

    // Theme (light / dark) -------------------------------------------------
    var root = document.documentElement;
    function applyTheme(theme) { root.setAttribute('data-bs-theme', theme); try { localStorage.setItem('theme', theme); } catch (e) {} }
    document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () { applyTheme(root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark'); });
    });

    // Sidebar toggle (mobile) ---------------------------------------------
    var sidebar = document.querySelector('.sidebar');
    document.querySelectorAll('[data-sidebar-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () { sidebar && sidebar.classList.toggle('open'); });
    });
    document.addEventListener('click', function (e) {
        if (sidebar && sidebar.classList.contains('open') && !sidebar.contains(e.target) && !e.target.closest('[data-sidebar-toggle]')) { sidebar.classList.remove('open'); }
    });

    // Sidebar groups: remember which ones the user opened or closed ----------
    document.querySelectorAll('.group-toggle').forEach(function (btn) {
        var id = btn.dataset.group, target = document.querySelector(btn.dataset.bsTarget), key = 'sb:' + id;
        var hasActive = target.querySelector('.nav-link.active');
        try {
            var saved = localStorage.getItem(key);
            if (!hasActive && saved === '1') { target.classList.add('show'); btn.classList.remove('collapsed'); btn.setAttribute('aria-expanded', 'true'); }
        } catch (e) {}
        target.addEventListener('shown.bs.collapse', function () { try { localStorage.setItem(key, '1'); } catch (e) {} });
        target.addEventListener('hidden.bs.collapse', function () { try { localStorage.setItem(key, '0'); } catch (e) {} });
    });

    // Confirm destructive actions -----------------------------------------
    document.addEventListener('submit', function (e) {
        var msg = e.target.getAttribute('data-confirm');
        if (msg && !window.confirm(msg)) { e.preventDefault(); }
    });

    // Prevent double submits ------------------------------------------------
    document.addEventListener('submit', function (e) {
        if (e.defaultPrevented) { return; }
        e.target.querySelectorAll('button[type=submit]:not([data-allow-multi])').forEach(function (b) {
            setTimeout(function () { b.disabled = true; }, 0);
        });
    });


    // Photos from phones are often several MB; shrink big images in the browser so uploads stay under the server limit.
    document.addEventListener('change', function (e) {
        var input = e.target;
        if (!input || input.type !== 'file' || !/image/.test(input.accept || '') || !window.DataTransfer || !input.files) { return; }
        var files = Array.prototype.slice.call(input.files);
        if (!files.some(function (f) { return f.size > 1200 * 1024 && /^image\/(jpeg|png|webp)$/.test(f.type); })) { return; }
        Promise.all(files.map(function (file) {
            if (file.size <= 1200 * 1024 || !/^image\/(jpeg|png|webp)$/.test(file.type)) { return file; }
            return new Promise(function (resolve) {
                var img = new Image(), url = URL.createObjectURL(file);
                img.onload = function () {
                    var max = 1800, k = Math.min(1, max / Math.max(img.width, img.height)), c = document.createElement('canvas');
                    c.width = Math.round(img.width * k); c.height = Math.round(img.height * k);
                    c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
                    c.toBlob(function (blob) {
                        URL.revokeObjectURL(url);
                        resolve(blob && blob.size < file.size ? new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }) : file);
                    }, 'image/jpeg', 0.85);
                };
                img.onerror = function () { URL.revokeObjectURL(url); resolve(file); };
                img.src = url;
            });
        })).then(function (out) {
            var dt = new DataTransfer(); out.forEach(function (f) { dt.items.add(f); }); input.files = dt.files;
        });
    });


    // Money in the hotel's currency (set by the layout): symbol, position and Indian-style grouping for Taka.
    window.fmtMoney = function (v) {
        var c = window.HOTEL_MONEY || {}, n = Number(v) || 0;
        var txt = Math.abs(n).toLocaleString(c.indian ? 'en-IN' : 'en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        txt = (n < 0 ? '-' : '') + txt;
        if (!c.symbol) { return txt; }
        return c.position === 2 ? txt + c.symbol : c.symbol + txt;
    };

    // Image viewer: any uploaded picture (thumbnail or link to an image) opens in a modal instead of a new tab.
    (function () {
        var modalEl = null, imgEl = null, capEl = null, modal = null;
        function build() {
            modalEl = document.createElement('div');
            modalEl.className = 'modal fade'; modalEl.tabIndex = -1;
            modalEl.innerHTML = '<div class="modal-dialog modal-dialog-centered modal-xl"><div class="modal-content bg-transparent border-0"><div class="modal-body p-0 text-center position-relative">' +
                '<button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2 bg-dark bg-opacity-50 p-2 rounded" data-bs-dismiss="modal" aria-label="Close"></button>' +
                '<img class="img-fluid rounded" style="max-height:85vh" alt=""><div class="text-white small mt-2"></div></div></div></div>';
            document.body.appendChild(modalEl);
            imgEl = modalEl.querySelector('img'); capEl = modalEl.querySelector('.text-white');
            modal = new bootstrap.Modal(modalEl);
        }
        function show(src, caption) {
            if (!window.bootstrap) { window.open(src, '_blank'); return; }
            if (!modalEl) { build(); }
            imgEl.src = src; capEl.textContent = caption || ''; modal.show();
        }
        document.addEventListener('click', function (e) {
            var a = e.target.closest('a[href]'), img = e.target.closest('img');
            var isImg = /\.(jpe?g|png|gif|webp|svg)(\?|#|$)/i;
            if (a && isImg.test(a.getAttribute('href') || '') && !a.hasAttribute('download')) {
                e.preventDefault(); show(a.href, (a.querySelector('img') && a.querySelector('img').alt) || a.textContent.trim()); return;
            }
            if (img && !a && img.closest('.page, .zoomable') && !img.classList.contains('no-zoom')) {
                e.preventDefault(); show(img.currentSrc || img.src, img.alt);
            }
        });
    })();

    // Enhanced widgets ------------------------------------------------------
    window.addEventListener('DOMContentLoaded', function () {
        if (window.flatpickr) {
            document.querySelectorAll('input[data-date]').forEach(function (el) {
                flatpickr(el, { dateFormat: 'Y-m-d', allowInput: true, minDate: el.dataset.min || null });
            });
            document.querySelectorAll('input[data-datetime]').forEach(function (el) {
                flatpickr(el, { dateFormat: 'Y-m-d H:i', enableTime: true, time_24hr: true, allowInput: true });
            });
            document.querySelectorAll('input[data-time]').forEach(function (el) {
                flatpickr(el, { dateFormat: 'H:i', enableTime: true, noCalendar: true, time_24hr: true });
            });
        }
        if (window.TomSelect) {
            document.querySelectorAll('select[data-search]').forEach(function (el) { new TomSelect(el, { allowEmptyOption: true, maxOptions: 500 }); });
        }
    });
})();
