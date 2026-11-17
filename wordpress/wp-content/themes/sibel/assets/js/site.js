(() => {
    'use strict';
    const home = document.querySelector('#main > .hero')?.parentElement;
    if (home && 'IntersectionObserver' in window) {
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        if (!reducedMotion.matches) {
            const targets = Array.from(
                home.querySelectorAll('.hero__copy, .hero__art, .home-sections > section'),
            );
            let stopped = false;
            const observer = new IntersectionObserver(
                (entries) => {
                    if (stopped) return;
                    for (const entry of entries) {
                        if (!entry.isIntersecting) continue;
                        observer.unobserve(entry.target);
                        if (!reducedMotion.matches && !entry.target.matches(':focus-within')) {
                            entry.target.classList.add('home-reveal');
                        }
                    }
                },
                { rootMargin: '0px 0px 32px 0px', threshold: 0 },
            );
            const stopReveals = () => {
                stopped = true;
                observer.disconnect();
                targets.forEach((target) => target.classList.remove('home-reveal'));
            };
            // Keep content visible until an animation actually starts; no hidden waiting state.
            targets.forEach((target) => {
                if (target.getBoundingClientRect().bottom > 0) observer.observe(target);
            });
            home.addEventListener('animationend', (event) => {
                if (event.animationName === 'home-block-reveal') {
                    event.target.classList.remove('home-reveal');
                }
            });
            home.addEventListener('focusin', (event) => {
                for (const target of targets) {
                    if (!target.contains(event.target)) continue;
                    observer.unobserve(target);
                    target.classList.remove('home-reveal');
                }
            });
            reducedMotion.addEventListener('change', () => {
                if (reducedMotion.matches) stopReveals();
            });
            window.addEventListener('pageshow', (event) => {
                if (event.persisted) stopReveals();
            });
        }
    }
    const header = document.querySelector('.site-header');
    if (header) {
        const adminBar = document.getElementById('wpadminbar');
        const updateHeader = () => {
            header.classList.toggle('is-scrolled', window.scrollY > 24);
            if (adminBar) header.style.top = `${Math.max(0, adminBar.getBoundingClientRect().bottom)}px`;
        };
        updateHeader();
        window.addEventListener('scroll', updateHeader, { passive: true });
        window.addEventListener('pageshow', updateHeader);
        if (adminBar) window.addEventListener('resize', updateHeader);
    }
    const menu = document.querySelector('#main-nav');
    const menuToggle = document.querySelector('.menu-toggle');
    const closeMenu = () => {
        menu?.classList.remove('is-open');
        menuToggle?.setAttribute('aria-expanded', 'false');
        menuToggle?.setAttribute('aria-label', 'Открыть меню');
    };
    menuToggle?.addEventListener('click', () => {
        const open = menuToggle.getAttribute('aria-expanded') !== 'true';
        menuToggle.setAttribute('aria-expanded', String(open));
        menuToggle.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
        menu?.classList.toggle('is-open', open);
    });
    menu?.querySelectorAll('a').forEach((a) => a.addEventListener('click', closeMenu));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && menuToggle?.getAttribute('aria-expanded') === 'true') {
            closeMenu();
            menuToggle.focus();
        }
    });
    document.addEventListener('click', (event) => {
        if (!header?.contains(event.target)) closeMenu();
    });
    window.matchMedia('(min-width: 1200px)').addEventListener('change', closeMenu);

    const openDialog = (id) => {
        const dialog = document.getElementById(id);
        if (!dialog || dialog.open) return;
        closeMenu();
        dialog.showModal();
    };
    document
        .querySelectorAll('[data-open-lead]')
        .forEach((button) => button.addEventListener('click', () => openDialog('lead-dialog')));
    document
        .querySelectorAll('[data-open-search]')
        .forEach((button) => button.addEventListener('click', () => openDialog('search-dialog')));
    document
        .querySelectorAll('[data-close-dialog]')
        .forEach((button) => button.addEventListener('click', () => button.closest('dialog').close()));
    document.querySelectorAll('dialog').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            if (event.target !== dialog) return;
            const rect = dialog.getBoundingClientRect();
            if (
                event.clientX < rect.left ||
                event.clientX > rect.right ||
                event.clientY < rect.top ||
                event.clientY > rect.bottom
            )
                dialog.close();
        });
    });
    document.querySelectorAll('[data-lightbox]').forEach((button) =>
        button.addEventListener('click', (event) => {
            event.preventDefault();
            const dialog = document.querySelector('#lightbox');
            const img = dialog.querySelector('img');
            img.src = button.dataset.lightbox;
            img.alt = button.dataset.caption || '';
            dialog.querySelector('p').textContent = button.dataset.caption || '';
            openDialog('lightbox');
        }),
    );
    document.querySelectorAll('[data-gallery-select]').forEach((button) =>
        button.addEventListener('click', () => {
            const main = document.querySelector('.project-detail__main-image');
            if (main) {
                main.querySelector('img').src = button.dataset.gallerySelect;
                main.dataset.lightbox = button.dataset.gallerySelect;
            }
        }),
    );
    document.querySelectorAll('[data-partner-marquee]').forEach((marquee) => {
        const track = marquee.querySelector('.partner-track');
        const group = track.querySelector('.partner-group');
        const originals = Array.from(group.children, (item) => item.cloneNode(true));
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        let previousWidth = -1;
        let buildGeneration = 0;
        const rebuild = (force = false) => {
            const width = document.documentElement.clientWidth;
            if (!force && width === previousWidth) return;
            previousWidth = width;
            const generation = ++buildGeneration;
            marquee.style.setProperty('--partner-viewport-width', `${width}px`);
            marquee.classList.remove('is-animated');
            track.querySelector('[data-marquee-copy-group]')?.remove();
            group.replaceChildren(...originals.map((item) => item.cloneNode(true)));
            group.querySelectorAll('img').forEach((img) => {
                img.loading = 'eager';
            });
            if (reducedMotion.matches || !originals.length) return;
            const originalWidth = group.getBoundingClientRect().width;
            if (!originalWidth) return;
            const repetitions = Math.max(1, Math.ceil(width / originalWidth));
            for (let index = 1; index < repetitions; index++) {
                for (const original of originals) {
                    const item = original.cloneNode(true);
                    item.setAttribute('aria-hidden', 'true');
                    group.append(item);
                }
            }
            const copy = group.cloneNode(true);
            copy.dataset.marqueeCopyGroup = '1';
            copy.setAttribute('aria-hidden', 'true');
            track.append(copy);
            track.querySelectorAll('img').forEach((img) => {
                img.loading = 'eager';
            });
            track.style.setProperty('--partner-duration', `${group.getBoundingClientRect().width / 40}s`);
            Promise.allSettled(Array.from(track.querySelectorAll('img'), (img) => img.decode())).then(() => {
                if (generation === buildGeneration && !reducedMotion.matches) {
                    marquee.classList.add('is-animated');
                }
            });
        };
        let pending;
        window.addEventListener('resize', () => {
            cancelAnimationFrame(pending);
            pending = requestAnimationFrame(() => rebuild());
        });
        reducedMotion.addEventListener('change', () => rebuild(true));
        rebuild();
    });

    document.querySelectorAll('[data-phone-input]').forEach((input) => {
        const synchronize = () => {
            const raw = input.value;
            const position = input.selectionStart ?? raw.length;
            const hasPrefix = raw.startsWith('+7');
            let digits = (hasPrefix ? raw.slice(2) : raw).replace(/[^0-9]/g, '');
            let caret = (hasPrefix ? raw.slice(2, Math.max(2, position)) : raw.slice(0, position)).replace(
                /[^0-9]/g,
                '',
            ).length;
            if (!hasPrefix && digits.length === 11 && /^[78]/.test(digits)) {
                digits = digits.slice(1);
                caret = Math.max(0, caret - 1);
            }
            input.value = '+7' + digits.slice(0, 10);
            input.setSelectionRange(
                Math.min(2 + caret, input.value.length),
                Math.min(2 + caret, input.value.length),
            );
        };
        input.addEventListener('beforeinput', (event) => {
            if (event.inputType === 'insertText' && event.data && /[^0-9]/.test(event.data)) {
                event.preventDefault();
                return;
            }
            const start = input.selectionStart ?? 2;
            const end = input.selectionEnd ?? start;
            if (event.inputType.startsWith('delete') && end <= 2) {
                event.preventDefault();
                input.setSelectionRange(2, 2);
            } else if (start < 2) {
                input.setSelectionRange(2, Math.max(2, end));
            }
        });
        input.addEventListener('input', synchronize);
        input.addEventListener('focus', () => {
            if (!input.value.startsWith('+7')) synchronize();
            if ((input.selectionStart ?? 0) < 2) input.setSelectionRange(2, 2);
        });
        input.addEventListener('paste', (event) => {
            event.preventDefault();
            const raw = event.clipboardData.getData('text').trim();
            let digits = raw.replace(/[^0-9]/g, '');
            if (
                (raw.startsWith('+7') && digits.startsWith('7')) ||
                (digits.length === 11 && /^[78]/.test(digits))
            )
                digits = digits.slice(1);
            const current = input.value.slice(2);
            const start = Math.max(0, (input.selectionStart ?? 2) - 2);
            const end = Math.max(0, (input.selectionEnd ?? 2) - 2);
            input.value = '+7' + (current.slice(0, start) + digits + current.slice(end)).slice(0, 10);
            const caret = Math.min(2 + start + digits.length, input.value.length);
            input.setSelectionRange(caret, caret);
        });
        if (!input.value) input.value = '+7';
        else synchronize();
    });
    document.querySelectorAll('[data-lead-form]').forEach((form) =>
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!form.reportValidity()) return;
            const button = form.querySelector('button[type="submit"]');
            const result = form.querySelector('.form-result');
            button.disabled = true;
            result.textContent = 'Отправляем заявку…';
            result.classList.remove('is-error');
            try {
                const data = new FormData(form);
                data.set('action', 'sibel_lead');
                data.set('nonce', window.sibelConfig?.nonce || '');
                const response = await fetch(window.sibelConfig?.ajaxUrl || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: data,
                    credentials: 'same-origin',
                });
                const payload = await response.json();
                if (!response.ok || !payload.success)
                    throw new Error(
                        payload.data?.message || 'Не удалось отправить заявку. Попробуйте ещё раз.',
                    );
                form.reset();
                result.textContent = payload.data.message;
            } catch (error) {
                result.classList.add('is-error');
                result.textContent =
                    error instanceof SyntaxError
                        ? 'Сервис временно недоступен. Позвоните нам или повторите отправку позже.'
                        : error.message;
            } finally {
                button.disabled = false;
            }
        }),
    );
})();
