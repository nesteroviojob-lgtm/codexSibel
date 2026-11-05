(() => {
    'use strict';
    document.addEventListener('click', (event) => {
        const clear = event.target.closest('.sibel-clear-gallery');
        if (clear) {
            const input = document.getElementById(clear.dataset.target);
            input.value = '';
            const wrapper = clear.closest('.sibel-media-field');
            wrapper.querySelector('.sibel-gallery-preview')?.replaceChildren();
            wrapper.querySelector('[name="sibel_clear_gallery"]').value = '1';
            return;
        }
        const button = event.target.closest('.sibel-select-media');
        if (!button || !window.wp?.media) return;
        event.preventDefault();
        const gallery = button.dataset.type === 'gallery';
        const frame = wp.media({
            title: gallery ? 'Выберите фотографии в нужном порядке' : 'Выберите файл',
            button: { text: 'Использовать' },
            multiple: gallery ? 'add' : false,
            library: { type: button.dataset.type === 'file' ? 'application/pdf' : 'image' },
        });
        frame.on('select', () => {
            const selection = frame.state().get('selection').toJSON();
            const input = document.getElementById(button.dataset.target);
            if (gallery) {
                input.value = selection.map((item) => item.id).join(',');
                const preview = button.closest('.sibel-media-field').querySelector('.sibel-gallery-preview');
                preview.replaceChildren(
                    ...selection.map((item) => {
                        const img = document.createElement('img');
                        img.src = item.sizes?.thumbnail?.url || item.url;
                        img.alt = item.alt || '';
                        return img;
                    }),
                );
                button.closest('.sibel-media-field').querySelector('[name="sibel_clear_gallery"]').value =
                    '0';
            } else {
                input.value = selection[0].url;
                const preview = button.closest('.sibel-media-field').querySelector('.sibel-media-preview');
                if (preview) preview.src = selection[0].url;
            }
        });
        frame.open();
    });
})();
