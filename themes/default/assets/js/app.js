// Varsayılan tema betikleri
document.addEventListener('DOMContentLoaded', () => {
    // Mobil menü
    const toggle = document.getElementById('navToggle');
    const menu = document.getElementById('navMenu');
    if (toggle && menu) {
        toggle.addEventListener('click', () => {
            const open = menu.classList.toggle('open');
            toggle.classList.toggle('open', open);
            toggle.setAttribute('aria-expanded', String(open));
        });
    }

    // Kaydırınca navbar arka planı
    const navbar = document.getElementById('navbar');
    const onScroll = () => navbar && navbar.classList.toggle('scrolled', window.scrollY > 10);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    // Badge harf dalgası (bakım sayfasındaki animasyon)
    document.querySelectorAll('.js-wave').forEach((badge) => {
        const text = badge.textContent.trim();
        badge.textContent = '';
        [...text].forEach((char, i) => {
            const span = document.createElement('span');
            span.className = 'letter';
            span.innerHTML = char === ' ' ? '&nbsp;' : char;
            span.style.animationDelay = `${i * 0.06}s`;
            badge.appendChild(span);
        });
    });

    // E-posta kopyalama + toast
    const toast = document.getElementById('toast');
    let toastTimer;
    document.querySelectorAll('.js-copy-email').forEach((btn) => {
        btn.addEventListener('click', () => {
            const email = btn.dataset.email;
            const done = () => {
                if (!toast) return;
                toast.classList.add('show');
                clearTimeout(toastTimer);
                toastTimer = setTimeout(() => toast.classList.remove('show'), 3000);
            };
            if (navigator.clipboard) {
                navigator.clipboard.writeText(email).then(done).catch(() => { window.location.href = 'mailto:' + email; });
            } else {
                window.location.href = 'mailto:' + email;
            }
        });
    });

    // Sayaç animasyonu: kilometre sayacı gibi kayan rakamlar.
    // Sayı artarken yeni rakamlar alttan, azalırken üstten gelir.
    // Ekrana girince 0'dan hedefe çıkar, ekrandan çıkınca 0'a iner (tekrar görünce yeniden sayar).
    const counters = document.querySelectorAll('[data-count]');
    const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const buildOdometer = (el) => {
        const target = Math.max(0, parseInt(el.dataset.count, 10) || 0);
        const width = String(target).length;
        el.setAttribute('aria-label', target + (el.dataset.suffix || ''));
        el.textContent = '';
        const odo = document.createElement('span');
        odo.className = 'odo';
        odo.setAttribute('aria-hidden', 'true');
        for (let i = 0; i < width; i++) {
            const col = document.createElement('span');
            col.className = 'odo-col';
            const strip = document.createElement('span');
            strip.className = 'odo-strip';
            for (let d = 0; d <= 9; d++) {
                const digit = document.createElement('span');
                digit.textContent = d;
                strip.appendChild(digit);
            }
            col.appendChild(strip);
            odo.appendChild(col);
        }
        el.appendChild(odo);
        if (el.dataset.suffix) {
            const suffix = document.createElement('span');
            suffix.className = 'odo-suffix';
            suffix.setAttribute('aria-hidden', 'true');
            suffix.textContent = el.dataset.suffix;
            el.appendChild(suffix);
        }
        return { el, target, cols: [...odo.children] };
    };
    const setOdometer = (o, value) => {
        const digits = String(value).padStart(o.cols.length, '0');
        const firstSignificant = digits.search(/[1-9]/);
        o.cols.forEach((col, i) => {
            // Baştaki sıfırlar gizlenir (ör. 12 -> 0 olurken "00" değil "0" görünür)
            col.classList.toggle('is-off', i < o.cols.length - 1 && (firstSignificant === -1 || i < firstSignificant));
            // Sağdaki basamaklar biraz daha uzun döner: daha doğal bir sayaç hissi
            col.firstChild.style.transitionDuration = (1 + i * 0.25) + 's';
            col.firstChild.style.transform = 'translateY(' + (-Number(digits[i]) * 10) + '%)';
        });
    };
    if (counters.length && !reduceMotion && 'IntersectionObserver' in window) {
        const odometers = new Map();
        counters.forEach((el) => {
            const o = buildOdometer(el);
            setOdometer(o, 0);
            odometers.set(el, o);
        });
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                const o = odometers.get(entry.target);
                setOdometer(o, entry.isIntersecting ? o.target : 0);
            });
        }, { threshold: 0.4 });
        counters.forEach((c) => io.observe(c));
        // Yazdır / PDF: ekranda görünmeyen sayılar 0'da kalmasın
        window.addEventListener('beforeprint', () => odometers.forEach((o) => setOdometer(o, o.target)));
    }

    // Kartlar ve yetenek çubukları görünür olunca yumuşak giriş
    const io2 = 'IntersectionObserver' in window ? new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('in');
                io2.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 }) : null;
    const animateIn = (root) => {
        root.querySelectorAll('.project-card:not(.in), .skill-bar:not(.in)').forEach((c) => (io2 ? io2.observe(c) : c.classList.add('in')));
    };
    animateIn(document);

    // ------------------------------------------------------------ Kartlarda 3D eğim (bakım sayfasındaki efekt)
    // Fare kartın üzerinde gezdikçe kart imleci takip ederek eğilir. Olay delegasyonu sayesinde
    // AJAX ile sonradan gelen kartlar da (ör. proje listesi) kendiliğinden çalışır.
    // Dokunmatik cihazlarda ve "hareketi azalt" ayarında kapalıdır; formlar bilerek dahil edilmedi.
    const TILT = '.project-card, .service-card, .package, .feature, .process-step, .stats > div, .cv-stats > div, .about-card, .contact-info, .cta-box';
    const canTilt = window.matchMedia('(hover: hover) and (pointer: fine)').matches
        && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (canTilt) {
        let active = null, frame = 0, lastEvent = null;
        const lift = (card) => (card.matches(':hover') && card.classList.contains('project-card') ? ' translateY(-4px)' : '');
        const apply = () => {
            frame = 0;
            if (!active || !lastEvent) return;
            const r = active.getBoundingClientRect();
            const x = lastEvent.clientX - r.left - r.width / 2;
            const y = lastEvent.clientY - r.top - r.height / 2;
            // Bakım sayfasıyla aynı oran (/35); çok büyük kartlarda en fazla 6 derece
            const rx = Math.max(-6, Math.min(6, -y / 35));
            const ry = Math.max(-6, Math.min(6, x / 35));
            active.style.transform = `perspective(1000px) rotateX(${rx}deg) rotateY(${ry}deg)${lift(active)}`;
        };
        const reset = (card) => {
            card.style.transition = 'transform .45s ease';
            card.style.transform = '';
            setTimeout(() => { if (card !== active) card.style.transition = ''; }, 460);
        };
        document.addEventListener('pointermove', (e) => {
            const card = e.target.closest(TILT);
            if (card !== active) {
                if (active) reset(active);
                active = card;
                if (active) active.style.transition = 'transform .1s ease-out';
            }
            if (!active) return;
            lastEvent = e;
            if (!frame) frame = requestAnimationFrame(apply);
        }, { passive: true });
        // Fare pencereden çıkınca kart düz konumuna dönsün
        document.documentElement.addEventListener('mouseleave', () => { if (active) { reset(active); active = null; } });
    }

    // ------------------------------------------------------------ Hizmetler açılır menüsü
    document.querySelectorAll('.nav-dropdown').forEach((dd) => {
        const btn = dd.querySelector('.nav-dropdown-toggle');
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const open = dd.classList.toggle('open');
            btn.setAttribute('aria-expanded', String(open));
        });
    });
    document.addEventListener('click', (e) => {
        document.querySelectorAll('.nav-dropdown.open').forEach((dd) => {
            if (!dd.contains(e.target)) dd.classList.remove('open');
        });
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') document.querySelectorAll('.nav-dropdown.open').forEach((dd) => dd.classList.remove('open'));
    });

    // Özgeçmişi yazdır / PDF
    document.querySelectorAll('.js-print').forEach((b) => b.addEventListener('click', () => {
        document.querySelectorAll('.skill-bar').forEach((s) => s.classList.add('in'));
        window.print();
    }));

    // ------------------------------------------------------------ Projeler: AJAX arama / filtre / sayfalama
    const results = document.getElementById('projectResults');
    const searchForm = document.getElementById('projectSearch');
    if (results && searchForm && window.Ajax) {
        const baseUrl = results.dataset.url;
        const params = new URLSearchParams(window.location.search);
        const state = { q: params.get('q') || '', dil: params.get('dil') || '', tur: params.get('tur') || '', sayfa: parseInt(params.get('sayfa') || '1', 10) };
        let timer;

        // Filtreleri adrese çevirir (tür, dil, arama, sayfa); "over" ile bazıları değiştirilebilir
        const query = (over = {}) => {
            const s = { ...state, ...over };
            const p = new URLSearchParams();
            if (s.tur) p.set('tur', s.tur);
            if (s.dil) p.set('dil', s.dil);
            if (s.q) p.set('q', s.q);
            if (s.sayfa > 1) p.set('sayfa', s.sayfa);
            const qs = p.toString();
            return baseUrl + (qs ? '?' + qs : '');
        };
        const buildUrl = () => query();

        // Etkin sekme / çip ve bağlantıları güncel filtrelere göre tazele
        const syncChips = () => {
            document.querySelectorAll('#languageChips .chip').forEach((chip) => {
                chip.classList.toggle('active', (chip.dataset.lang || '') === state.dil);
                chip.href = query({ dil: chip.dataset.lang || '', sayfa: 1 });
            });
            document.querySelectorAll('#kindTabs .kind-tab').forEach((tab) => {
                tab.classList.toggle('active', (tab.dataset.kind || '') === state.tur);
                tab.href = query({ tur: tab.dataset.kind || '', sayfa: 1 });
            });
            searchForm.elements.dil.value = state.dil;
            if (searchForm.elements.tur) searchForm.elements.tur.value = state.tur;
        };

        const load = (push = true, scroll = false) => {
            const url = buildUrl();
            results.classList.add('ajax-busy');
            Ajax.get(url).then((res) => {
                results.classList.remove('ajax-busy');
                if (!res.ok) { Ajax.showMessages(res); return; }
                results.innerHTML = res.html;
                const total = document.getElementById('projectTotal');
                if (total) total.textContent = res.total;
                animateIn(results);
                syncChips();
                if (push) history.pushState({ ...state }, '', url);
                if (scroll) results.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        };

        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            clearTimeout(timer);
            state.q = searchForm.elements.q.value.trim();
            state.sayfa = 1;
            load();
        });
        searchForm.elements.q.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                state.q = searchForm.elements.q.value.trim();
                state.sayfa = 1;
                load();
            }, 350);
        });
        document.querySelectorAll('#languageChips .chip').forEach((chip) => {
            chip.addEventListener('click', (e) => {
                e.preventDefault();
                state.dil = chip.dataset.lang || '';
                state.sayfa = 1;
                load();
            });
        });
        // Tür: Tümü / Web / Uygulama
        document.querySelectorAll('#kindTabs .kind-tab').forEach((tab) => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                state.tur = tab.dataset.kind || '';
                state.sayfa = 1;
                load();
            });
        });
        results.addEventListener('click', (e) => {
            const a = e.target.closest('.pagination a, [data-ajax-link]');
            if (!a) return;
            e.preventDefault();
            const p = new URL(a.href).searchParams;
            if (a.hasAttribute('data-ajax-link')) {
                state.q = ''; state.dil = ''; state.tur = ''; searchForm.elements.q.value = '';
            }
            state.sayfa = parseInt(p.get('sayfa') || '1', 10);
            load(true, true);
        });
        window.addEventListener('popstate', () => {
            const p = new URLSearchParams(window.location.search);
            state.q = p.get('q') || '';
            state.dil = p.get('dil') || '';
            state.tur = p.get('tur') || '';
            state.sayfa = parseInt(p.get('sayfa') || '1', 10);
            searchForm.elements.q.value = state.q;
            load(false);
        });
    }

    // ------------------------------------------------------------ Teklif formu
    const quoteForm = document.getElementById('quoteForm');
    if (quoteForm) {
        const pkg = quoteForm.elements.package;
        const pkgField = document.getElementById('packageField');
        const filterPackages = () => {
            const service = (quoteForm.querySelector('input[name="service"]:checked') || {}).value;
            let hasAny = false;
            pkg.querySelectorAll('optgroup').forEach((g) => {
                const match = g.dataset.service === service;
                g.hidden = !match;
                g.disabled = !match;
                if (match) hasAny = true;
            });
            if (pkg.selectedOptions[0] && pkg.selectedOptions[0].parentElement.disabled) pkg.value = '';
            pkgField.style.visibility = hasAny ? '' : 'hidden';
        };
        quoteForm.querySelectorAll('input[name="service"]').forEach((r) => r.addEventListener('change', filterPackages));
        filterPackages();

        const msg = quoteForm.elements.message;
        const count = document.getElementById('msgCount');
        const updateCount = () => { count.textContent = msg.value.length; };
        msg.addEventListener('input', updateCount);
        updateCount();

        quoteForm.addEventListener('ajax:success', (e) => {
            e.preventDefault();
            document.getElementById('quoteSuccessText').textContent = e.detail.message;
            quoteForm.hidden = true;
            const box = document.getElementById('quoteSuccess');
            box.hidden = false;
            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    }

    // ------------------------------------------------------------ İletişim formu
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        const box = document.getElementById('contactSuccess');
        contactForm.addEventListener('ajax:success', (e) => {
            e.preventDefault();
            document.getElementById('contactSuccessText').textContent = e.detail.message;
            contactForm.hidden = true;
            box.hidden = false;
            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
        document.getElementById('contactAgain').addEventListener('click', () => {
            contactForm.elements.subject.value = '';
            contactForm.elements.message.value = '';
            contactForm.elements.consent.checked = false;
            box.hidden = true;
            contactForm.hidden = false;
        });
    }
});

// Tam ekran galeri (proje tanıtımı): tıkla, oklar / klavye ile gez, Esc ile kapat
(function () {
    var box = document.getElementById('lightbox');
    var items = Array.prototype.slice.call(document.querySelectorAll('[data-gallery-item]'));
    if (!box || !items.length) return;
    var img = document.getElementById('lightboxImage');
    var index = 0, lastFocus = null;
    function show(i) {
        index = (i + items.length) % items.length;
        var it = items[index];
        img.src = it.dataset.src;
        img.alt = it.dataset.title || '';
        document.getElementById('lightboxTitle').textContent = it.dataset.title || '';
        document.getElementById('lightboxCaption').textContent = it.dataset.caption || '';
        document.getElementById('lightboxCount').textContent = (index + 1) + ' / ' + items.length;
    }
    function open(i) { lastFocus = document.activeElement; show(i); box.hidden = false; document.body.style.overflow = 'hidden'; box.querySelector('[data-lb="close"]').focus(); }
    function close() { box.hidden = true; document.body.style.overflow = ''; if (lastFocus) lastFocus.focus(); }
    items.forEach(function (it, i) { it.addEventListener('click', function () { open(i); }); });
    box.addEventListener('click', function (e) {
        var a = e.target.closest('[data-lb]');
        if (a) { a.dataset.lb === 'close' ? close() : show(index + (a.dataset.lb === 'next' ? 1 : -1)); return; }
        if (e.target === box) close();
    });
    document.addEventListener('keydown', function (e) {
        if (box.hidden) return;
        if (e.key === 'Escape') close();
        else if (e.key === 'ArrowRight') show(index + 1);
        else if (e.key === 'ArrowLeft') show(index - 1);
    });
})();

// ------------------------------------------------------------ Çerez onayı (alttan kayarak açılır)
(function () {
    'use strict';
    const box = document.getElementById('cookieConsent');
    if (!box) return;
    const settings = document.getElementById('cookieSettings');
    const btn = (name) => box.querySelector('[data-cookie="' + name + '"]');
    const secure = location.protocol === 'https:' ? '; Secure' : '';

    const open = (withSettings) => {
        box.hidden = false;
        if (withSettings) toggleSettings(true);
        document.body.classList.add('cookie-open');
        void box.offsetWidth; // başlangıç konumu çizilsin, sonra alttan kayarak gelsin
        setTimeout(() => box.classList.add('is-open'), 20);
    };
    const close = () => {
        box.classList.remove('is-open');
        document.body.classList.remove('cookie-open');
        setTimeout(() => { box.hidden = true; }, 600);
    };
    const toggleSettings = (show) => {
        settings.hidden = !show;
        btn('settings').hidden = show;
        btn('necessary').hidden = show;
        btn('save').hidden = !show;
        btn('settings').setAttribute('aria-expanded', show ? 'true' : 'false');
    };

    // Seçim: s = istatistik, a = reklam (sunucu App\Support\Consent ile okur)
    const save = (stats, ads) => {
        const before = document.cookie.match(/(?:^|;\s*)cerez_tercihi=([^;]*)/);
        const value = 's=' + (stats ? 1 : 0) + '&a=' + (ads ? 1 : 0);
        document.cookie = 'cerez_tercihi=' + encodeURIComponent(value) + '; Max-Age=' + (180 * 86400) + '; Path=/; SameSite=Lax' + secure;
        if (!stats) document.cookie = 'ziyaretci=; Max-Age=0; Path=/; SameSite=Lax' + secure; // anonim sayaç çerezi silinir
        close();
        // Reklam tercihi sayfa yüklenirken uygulanır: değiştiyse sayfa yenilenir
        const oldAds = before ? /a=1/.test(decodeURIComponent(before[1])) : false;
        if (oldAds !== ads) setTimeout(() => location.reload(), 650);
    };

    btn('all').addEventListener('click', () => save(true, true));
    btn('necessary').addEventListener('click', () => save(false, false));
    btn('settings').addEventListener('click', () => toggleSettings(true));
    btn('save').addEventListener('click', () => save(settings.querySelector('[name="s"]').checked, settings.querySelector('[name="a"]').checked));

    // Altbilgideki / politika sayfasındaki "Çerez Ayarları"
    document.querySelectorAll('[data-cookie-open]').forEach((el) => el.addEventListener('click', () => open(true)));

    // Henüz seçim yapılmadıysa sayfa açıldıktan kısa süre sonra alttan gelir
    if (box.dataset.decided !== '1') setTimeout(() => open(false), 900);
})();
