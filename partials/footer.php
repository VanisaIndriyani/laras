    </div>
</div>

<!-- ============ BOOTSTRAP TOAST CONTAINER ============ -->
<div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer" style="z-index: 9999; margin-top: 72px;">
</div>

<!-- ============ BOOTSTRAP GLOBAL CONFIRM MODAL ============ -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <div id="confirmModalIcon" class="rounded-circle d-flex align-items-center justify-content-center text-danger"
                         style="width: 40px; height: 40px; background: rgba(239,68,68,0.12); font-size: 20px;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <h5 class="modal-title fw-bold text-dark mb-0" id="confirmModalLabel" style="font-size: 15px;">Konfirmasi</h5>
                </div>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2 pb-3">
                <p class="mb-0 text-muted" id="confirmModalText" style="font-size: 12.5px; line-height: 1.6;">Apakah Anda yakin ingin melanjutkan tindakan ini?</p>
            </div>
            <div class="modal-footer border-top-0 pt-0 gap-2">
                <button type="button" class="btn btn-light fw-semibold px-4 py-2 rounded-3" data-bs-dismiss="modal" style="font-size: 12px;">
                    <i class="bi bi-x-lg me-1"></i>Batal
                </button>
                <button type="button" class="btn btn-danger fw-semibold px-4 py-2 rounded-3 shadow-sm" id="confirmModalOk" style="font-size: 12px;">
                    <i class="bi bi-check-lg me-1"></i>Ya, Lanjutkan
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function() {
    'use strict';

    // ========== BASE URL HELPER (from PHP config) ==========
    var BASE_URL = '<?= BASE_URL ?>';
    function base_url(path) {
        path = path || '';
        if (/^https?:\/\//i.test(path)) return path;
        return BASE_URL + path.replace(/^\//, '');
    }
    window.base_url = base_url;

    // ========== TOGGLE FULLSCREEN ==========
    function toggleFullscreen() {
        var el = document.documentElement;
        try {
            if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
                if (el.requestFullscreen) el.requestFullscreen();
                else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
                else if (el.msRequestFullscreen) el.msRequestFullscreen();
            } else {
                if (document.exitFullscreen) document.exitFullscreen();
                else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
                else if (document.msExitFullscreen) document.msExitFullscreen();
            }
        } catch (e) {
            showToast('warning', 'Fullscreen', 'Browser Anda tidak mendukung fullscreen API.');
        }
    }
    window.toggleFullscreen = toggleFullscreen;

    // ========== GLOBAL SEARCH REDIRECT ==========
    function globalSearchDo(q) {
        q = (q || '').toString().toLowerCase().trim();
        if (!q) return;
        var map = [
            { keys: ['dashboard','home','beranda','halaman utama'], url: 'dashboard.php' },
            { keys: ['kendaraan','mobil','armada','unit'], url: 'kendaraan/index.php' },
            { keys: ['pinjam mobil','reservasi kendaraan','tambah pinjam mobil'], url: 'kendaraan/form.php' },
            { keys: ['ruangan','ruang','meeting','rapat','sarana'], url: 'ruangan/index.php' },
            { keys: ['pinjam ruang','reservasi ruangan','rapat','tambah ruang'], url: 'ruangan/form.php' },
            { keys: ['kalender','calendar','jadwal','peminjaman','agenda'], url: 'kalender.php' },
            { keys: ['laporan','rekapitulasi','rekap','export','csv','download'], url: 'laporan.php' },
            { keys: ['approval','setujui','acc','pending','pengajuan'], url: 'master/approvals.php' },
            { keys: ['user','pengguna','pegawai','data pengguna','akun'], url: 'master/users.php' },
            { keys: ['master kendaraan','data mobil','master mobil'], url: 'master/kendaraan.php' },
            { keys: ['master ruangan','data ruang','master ruang'], url: 'master/ruangan.php' }
        ];
        for (var i = 0; i < map.length; i++) {
            var keys = map[i].keys;
            for (var j = 0; j < keys.length; j++) {
                if (q.indexOf(keys[j]) !== -1) {
                    location.href = base_url(map[i].url);
                    return;
                }
            }
        }
        showToast('info', 'Pencarian', 'Tidak ditemukan hasil untuk pencarian "'+q+'".');
    }
    window.globalSearchDo = globalSearchDo;

    // ========== TOAST: showToast(type, title, message) ==========
    function showToast(type, title, message) {
        var icons = {
            success: '<i class="bi bi-check-circle-fill text-success"></i>',
            error:   '<i class="bi bi-x-circle-fill text-danger"></i>',
            warning: '<i class="bi bi-exclamation-triangle-fill text-warning"></i>',
            info:    '<i class="bi bi-info-circle-fill text-primary"></i>'
        };
        var colors = {
            success: 'border-success-subtle',
            error:   'border-danger-subtle',
            warning: 'border-warning-subtle',
            info:    'border-primary-subtle'
        };
        var container = document.getElementById('toastContainer');
        if (!container) return;

        var el = document.createElement('div');
        el.className = 'toast border-start-4 ' + colors[type] + ' shadow-lg rounded-3 mb-2';
        el.setAttribute('role', 'alert');
        el.setAttribute('aria-live', 'assertive');
        el.setAttribute('aria-atomic', 'true');
        el.style.minWidth = '290px';
        el.style.background = 'linear-gradient(135deg, #ffffff, #fcfcfd)';
        el.innerHTML =
            '<div class="toast-body py-3 px-3">' +
                '<div class="d-flex align-items-start gap-3">' +
                    '<div class="fs-5 pt-0.5">' + (icons[type] || icons.info) + '</div>' +
                    '<div class="flex-grow-1 min-w-0">' +
                        '<div class="fw-bold text-dark mb-0.5" style="font-size:13px;">' + (title || 'Notifikasi') + '</div>' +
                        '<div class="text-muted small" style="font-size:11.5px; line-height:1.5;">' + (message || '') + '</div>' +
                    '</div>' +
                    '<button type="button" class="btn-close btn-close-black p-0 ms-2 flex-shrink-0" data-bs-dismiss="toast" aria-label="Close" style="font-size:10px;"></button>' +
                '</div>' +
            '</div>';
        container.appendChild(el);
        var toast = new bootstrap.Toast(el, { delay: 4200, animation: true });
        toast.show();
        el.addEventListener('hidden.bs.toast', function () { el.remove(); });
    }
    window.showToast = showToast;

    // ========== AUTO-DISPLAY FLASH MESSAGES FROM PHP as TOASTS ==========
    (function autoShowFlashes() {
        var metaSuccess = document.querySelector('meta[name="flash-success"]');
        var metaError   = document.querySelector('meta[name="flash-error"]');
        var metaWarning = document.querySelector('meta[name="flash-warning"]');
        var metaInfo    = document.querySelector('meta[name="flash-info"]');
        if (metaSuccess && metaSuccess.content) showToast('success', 'Berhasil', metaSuccess.content);
        if (metaError   && metaError.content)   showToast('error',   'Gagal',   metaError.content);
        if (metaWarning && metaWarning.content) showToast('warning', 'Perhatian', metaWarning.content);
        if (metaInfo    && metaInfo.content)    showToast('info',    'Info',    metaInfo.content);
    })();

    // ========== GLOBAL CONFIRM MODAL ==========
    var confirmModalEl = document.getElementById('confirmModal');
    var confirmOkBtn = document.getElementById('confirmModalOk');
    var confirmText  = document.getElementById('confirmModalText');
    var confirmLabel = document.getElementById('confirmModalLabel');
    var confirmIcon  = document.getElementById('confirmModalIcon');
    var confirmInstance = bootstrap.Modal.getOrCreateInstance(confirmModalEl);
    var confirmCallback = null;

    window.bsConfirm = function(options) {
        options = options || {};
        confirmLabel.textContent = options.title || 'Konfirmasi';
        confirmText.textContent  = options.message || 'Apakah Anda yakin?';
        confirmCallback = options.onConfirm || null;
        // style icon
        var variant = options.variant || 'danger';
        var variants = {
            danger:  { cls: 'text-danger',  bg: 'rgba(239,68,68,0.12)', icon: 'bi-exclamation-triangle-fill' },
            warning: { cls: 'text-warning', bg: 'rgba(251,191,36,0.15)', icon: 'bi-exclamation-diamond-fill' },
            primary: { cls: 'text-primary', bg: 'rgba(59,130,246,0.14)', icon: 'bi-question-circle-fill' },
            success: { cls: 'text-success', bg: 'rgba(34,197,94,0.13)', icon: 'bi-check2-circle' }
        };
        var v = variants[variant] || variants.danger;
        confirmIcon.className = 'rounded-circle d-flex align-items-center justify-content-center ' + v.cls;
        confirmIcon.style.background = v.bg;
        confirmIcon.innerHTML = '<i class="bi ' + v.icon + '"></i>';
        confirmOkBtn.className = 'btn fw-semibold px-4 py-2 rounded-3 shadow-sm btn-' + variant;
        confirmInstance.show();
    };

    if (confirmOkBtn) {
        confirmOkBtn.addEventListener('click', function () {
            confirmInstance.hide();
            if (typeof confirmCallback === 'function') {
                setTimeout(confirmCallback, 120);
            }
        });
    }

    // ========== SIDEBAR TOGGLE (hybrid: desktop collapse + mobile offcanvas) ==========
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    var toggleBtn = document.getElementById('toggleSidebar');
    var mainContent = document.getElementById('mainContent');
    var IS_MOBILE = function () { return window.innerWidth < 992; };

    function openSidebarMobile() {
        if (!sidebar || !backdrop) return;
        sidebar.classList.add('show-mobile');
        backdrop.classList.add('show');
        try { document.body.style.overflow = 'hidden'; } catch(e){}
    }
    function closeSidebarMobile() {
        if (!sidebar || !backdrop) return;
        sidebar.classList.remove('show-mobile');
        backdrop.classList.remove('show');
        try { document.body.style.overflow = ''; } catch(e){}
    }
    function toggleSidebar() {
        if (IS_MOBILE()) {
            if (sidebar.classList.contains('show-mobile')) {
                closeSidebarMobile();
            } else {
                openSidebarMobile();
            }
        } else {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            try { localStorage.setItem('laras_sidebar_collapsed', sidebar.classList.contains('collapsed') ? '1' : '0'); } catch(e){}
        }
    }

    if (toggleBtn) toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        toggleSidebar();
    });
    if (backdrop) backdrop.addEventListener('click', closeSidebarMobile);

    // Escape key to close mobile sidebar
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && IS_MOBILE() && sidebar.classList.contains('show-mobile')) {
            closeSidebarMobile();
        }
    });

    // Restore desktop collapsed state on page load
    try {
        if (!IS_MOBILE() && localStorage.getItem('laras_sidebar_collapsed') === '1') {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
        }
    } catch(e) {}

    // Normalize sidebar state when window crosses breakpoint
    var resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            if (!IS_MOBILE()) {
                // Switching to desktop: close mobile variant, restore desktop state
                closeSidebarMobile();
                try {
                    if (localStorage.getItem('laras_sidebar_collapsed') === '1') {
                        sidebar.classList.add('collapsed');
                        mainContent.classList.add('expanded');
                    } else {
                        sidebar.classList.remove('collapsed');
                        mainContent.classList.remove('expanded');
                    }
                } catch(e){}
            } else {
                // Switching to mobile: remove collapsed, close if open
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
                closeSidebarMobile();
            }
        }, 120);
    });

    // Auto-close sidebar mobile when menu clicked
    document.querySelectorAll('.menu-link').forEach(function(link) {
        link.addEventListener('click', function () {
            if (IS_MOBILE()) closeSidebarMobile();
        });
    });
    // Also close when clicking logout (btn-logout)
    document.querySelectorAll('.btn-logout').forEach(function(btn) {
        btn.addEventListener('click', function () {
            if (IS_MOBILE()) closeSidebarMobile();
        });
    });

    // ========== GLOBAL SEARCH FILTER + DROPDOWN ==========
    (function initGlobalSearch() {
        var searchInput = document.getElementById('globalSearch');
        var searchResults = document.getElementById('searchResults');
        var searchBox = document.querySelector('.search-box');
        if (!searchInput || !searchResults) return;

        // Kumpulkan semua menu-link sebagai dataset
        var menuItems = [];
        document.querySelectorAll('.sidebar .menu-link').forEach(function (link) {
            var href = link.getAttribute('href') || '';
            if (!href || href === '#') return;
            var labelEl = link.querySelector('.menu-text') || link;
            var label = (labelEl.textContent || '').trim();
            if (!label) return;

            // Cari parent menu (Reservasi / Data Master / dst)
            var parentLabel = '';
            var parent = link.closest('.submenu-list');
            if (parent) {
                var parentTrigger = parent.parentElement.querySelector('.menu-parent');
                if (parentTrigger) {
                    var pt = parentTrigger.textContent || '';
                    parentLabel = pt.replace(/\s+/g, ' ').trim().split(' ')[0];
                }
            }

            // Cari icon class dari link tersebut
            var iconEl = link.querySelector('i.bi');
            var iconClass = iconEl ? iconEl.className : 'bi bi-arrow-right-short';

            menuItems.push({
                href: href,
                label: label,
                parentLabel: parentLabel || 'Menu Utama',
                iconClass: iconClass,
                linkEl: link
            });
        });

        function highlightText(text, keyword) {
            if (!keyword) return text;
            var kw = keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            var re = new RegExp('(' + kw + ')', 'gi');
            return text.replace(re, '<mark>$1</mark>');
        }

        function closeSearchDropdown() {
            searchResults.classList.remove('active');
        }

        searchInput.addEventListener('input', function () {
            var keyword = (searchInput.value || '').trim().toLowerCase();
            var resultsContainer = searchResults.querySelector('.search-results-list');
            if (!resultsContainer) {
                resultsContainer = document.createElement('div');
                resultsContainer.className = 'search-results-list';
                searchResults.appendChild(resultsContainer);
            }

            if (!keyword) {
                // Reset semua class sidebar
                document.querySelectorAll('.sidebar .menu-link').forEach(function (l) {
                    l.classList.remove('hidden-by-search', 'search-match');
                });
                document.querySelectorAll('.sidebar .menu-parent').forEach(function (p) {
                    p.classList.remove('parent-search-open', 'parent-hidden-by-search');
                    var collapseId = p.getAttribute('data-bs-target');
                    if (collapseId) {
                        var collapseEl = document.querySelector(collapseId);
                        if (collapseEl) {
                            try { bootstrap.Collapse.getInstance(collapseEl).hide(); } catch(e){}
                        }
                    }
                    p.setAttribute('aria-expanded', 'false');
                });
                closeSearchDropdown();
                return;
            }

            // Filter hasil
            var matched = menuItems.filter(function (item) {
                return item.label.toLowerCase().indexOf(keyword) !== -1
                    || item.parentLabel.toLowerCase().indexOf(keyword) !== -1;
            });

            // ─── 1) Update class di sidebar DOM ───
            var parentMatchedMap = {};
            document.querySelectorAll('.sidebar .menu-link').forEach(function (l) {
                l.classList.remove('hidden-by-search', 'search-match');
            });
            document.querySelectorAll('.sidebar .menu-parent').forEach(function (p) {
                p.classList.remove('parent-search-open', 'parent-hidden-by-search');
            });

            // Tandai parent jika ada child yang cocok
            matched.forEach(function (item) {
                var el = item.linkEl;
                if (el) {
                    el.classList.add('search-match');
                    var parentLi = el.closest('li.submenu');
                    if (parentLi) {
                        var parentBtn = parentLi.querySelector('.menu-parent');
                        if (parentBtn) {
                            parentBtn.classList.add('parent-search-open');
                            parentBtn.setAttribute('aria-expanded', 'true');
                            var collapseId = parentBtn.getAttribute('data-bs-target');
                            if (collapseId) {
                                var collapseEl = document.querySelector(collapseId);
                                if (collapseEl && !collapseEl.classList.contains('show')) {
                                    try { new bootstrap.Collapse(collapseEl, { show: true }); } catch(e){}
                                }
                            }
                        }
                    }
                }
            });

            // Sembunyikan menu yang TIDAK cocok (parent tanpa child cocok, link tanpa match)
            document.querySelectorAll('.sidebar .menu-link').forEach(function (l) {
                if (!l.classList.contains('search-match')) {
                    // Kalau parent link (menu-parent), baru sembunyikan jika TIDAK ADA child yang match
                    var isParent = l.classList.contains('menu-parent');
                    if (isParent) {
                        if (!l.classList.contains('parent-search-open')) {
                            l.classList.add('parent-hidden-by-search');
                        }
                    } else {
                        l.classList.add('hidden-by-search');
                    }
                }
            });

            // ─── 2) Render dropdown hasil ───
            resultsContainer.innerHTML = '';
            if (matched.length === 0) {
                resultsContainer.innerHTML =
                    '<div class="search-empty">' +
                    '<i class="bi bi-search"></i>' +
                    '<div class="se-title">Tidak ada menu yang cocok</div>' +
                    '<div class="se-sub">Coba kata kunci lain seperti "Kendaraan", "Ruangan", "Laporan"</div>' +
                    '</div>';
            } else {
                matched.slice(0, 8).forEach(function (item, idx) {
                    var a = document.createElement('a');
                    a.className = 'search-res-item';
                    a.setAttribute('href', item.href);
                    a.setAttribute('data-idx', idx);
                    a.innerHTML =
                        '<div class="sri-icon-box"><i class="' + item.iconClass + '"></i></div>' +
                        '<div class="sri-text">' +
                        '<div class="sri-title">' + highlightText(item.label, keyword) + '</div>' +
                        '<div class="sri-sub"><span class="sri-pill">' + item.parentLabel + '</span></div>' +
                        '</div>' +
                        '<i class="bi bi-chevron-right sri-arrow"></i>';
                    a.addEventListener('click', function () {
                        closeSearchDropdown();
                        searchInput.value = '';
                    });
                    resultsContainer.appendChild(a);
                });
            }

            searchResults.classList.add('active');
        });

        // Close search ketika klik di LUAR area .search-box
        document.addEventListener('click', function (e) {
            if (!searchBox) return;
            if (!e.target.closest('.search-box')) {
                closeSearchDropdown();
            }
        });

        // Escape key tutup dropdown
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeSearchDropdown();
                searchInput.blur();
            }
        });
    })();

    // Auto enable tooltips globally
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el, { boundary: document.body, placement: 'top' });
    });

    // Auto enable popovers globally
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
        new bootstrap.Popover(el);
    });

    // ========== HASH AUTO-SCROLL AFTER FULL LOAD (fix ketutup topbar) ==========
    function scrollToHashIfAny() {
        var hash = window.location.hash;
        if (!hash || hash.length < 2) return;
        var target = document.querySelector(hash);
        if (!target) return;
        setTimeout(function () {
            var topbarOffset = 100;
            var rect = target.getBoundingClientRect();
            var scrollPos = rect.top + window.pageYOffset - topbarOffset;
            window.scrollTo({ top: Math.max(0, scrollPos), behavior: 'smooth' });
            target.setAttribute('tabindex', '-1');
            try { target.focus({ preventScroll: true }); } catch(e){}
        }, 180);
    }
    if (document.readyState === 'complete') {
        scrollToHashIfAny();
    } else {
        window.addEventListener('load', scrollToHashIfAny);
    }

})();
</script>
</body>
</html>
