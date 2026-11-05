(() => {
    'use strict';
    const menu = document.querySelector('#main-nav');
    const menuToggle = document.querySelector('.menu-toggle');
    const closeMenu = () => {
        menu?.classList.remove('is-open');
        menuToggle?.setAttribute('aria-expanded', 'false');
    };
    menuToggle?.addEventListener('click', () => {
        const open = menuToggle.getAttribute('aria-expanded') !== 'true';
        menuToggle.setAttribute('aria-expanded', String(open));
        menuToggle.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
        menu?.classList.toggle('is-open', open);
    });
    menu?.querySelectorAll('a').forEach((a) => a.addEventListener('click', closeMenu));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeMenu();
    });
    window.matchMedia('(min-width: 1024px)').addEventListener('change', closeMenu);

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
        button.addEventListener('click', () => {
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
    document.querySelector('.partner-next')?.addEventListener('click', () => {
        const track = document.querySelector('.partner-track');
        const end = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
        track.scrollTo({
            left: end ? 0 : track.scrollLeft + track.firstElementChild.getBoundingClientRect().width + 20,
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
        });
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
