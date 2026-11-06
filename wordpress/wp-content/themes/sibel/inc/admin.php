<?php
defined('ABSPATH') || exit();

add_action('admin_init', function () {
    $admin = get_role('administrator');
    if ($admin && !$admin->has_cap('manage_sibel')) {
        $admin->add_cap('manage_sibel');
    }
    $manager = get_role('sibel_manager');
    if (!$manager) {
        add_role(
            'sibel_manager',
            'Менеджер сайта СИБЭЛ',
            array_fill_keys(
                [
                    'read',
                    'manage_sibel',
                    'upload_files',
                    'edit_posts',
                    'edit_others_posts',
                    'read_private_posts',
                    'edit_theme_options',
                    'edit_published_posts',
                    'publish_posts',
                    'delete_posts',
                    'delete_others_posts',
                    'delete_published_posts',
                    'edit_pages',
                    'edit_others_pages',
                    'edit_published_pages',
                    'publish_pages',
                    'delete_pages',
                    'delete_others_pages',
                    'delete_published_pages',
                ],
                true,
            ),
        );
    } elseif (!$manager->has_cap('edit_theme_options')) {
        $manager->add_cap('edit_theme_options');
    }
});

add_filter(
    'admin_post_thumbnail_html',
    function ($html, $post_id) {
        if (!has_post_thumbnail($post_id) && get_post_meta($post_id, '_sibel_asset', true)) {
            return '<div class="sibel-original-image"><img class="sibel-media-preview" src="' .
                esc_url(sibel_asset(get_post_meta($post_id, '_sibel_asset', true))) .
                '" alt=""><p>Текущее изображение. Чтобы заменить его, выберите новое в медиатеке.</p></div>' .
                $html;
        }
        return $html;
    },
    10,
    2,
);

add_action('admin_menu', function () {
    add_menu_page(
        'Управление сайтом СИБЭЛ',
        'СИБЭЛ · Сайт',
        'manage_sibel',
        'sibel',
        'sibel_admin_page',
        'dashicons-store',
        2,
    );
    add_submenu_page('sibel', 'Обзор сайта', 'Обзор сайта', 'manage_sibel', 'sibel', 'sibel_admin_page');
    add_submenu_page(
        'sibel',
        'Разделы каталога',
        'Разделы каталога',
        'manage_sibel',
        'edit-tags.php?taxonomy=sibel_category&post_type=sibel_product',
    );
    add_submenu_page(
        'sibel',
        'Главная и контакты',
        'Главная и контакты',
        'manage_sibel',
        'sibel-settings',
        'sibel_admin_settings',
    );
    add_submenu_page('sibel', 'Меню сайта', 'Меню сайта', 'edit_theme_options', 'nav-menus.php');
    add_submenu_page(
        'sibel',
        'Юридические документы',
        'Юридические документы',
        'manage_sibel',
        'sibel-legal',
        'sibel_admin_legal_page',
    );
    if (in_array('sibel_manager', wp_get_current_user()->roles, true)) {
        foreach (['index.php', 'edit.php', 'edit-comments.php', 'tools.php'] as $menu) {
            remove_menu_page($menu);
        }
    }
});

add_filter(
    'login_redirect',
    function ($redirect, $requested, $user) {
        return $user instanceof WP_User && user_can($user, 'manage_sibel') && !$requested
            ? admin_url('admin.php?page=sibel')
            : $redirect;
    },
    10,
    3,
);

add_action('admin_enqueue_scripts', function () {
    $screen = get_current_screen();
    if (
        !$screen ||
        (!str_contains($screen->id, 'sibel') && !str_starts_with($screen->post_type ?? '', 'sibel_'))
    ) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_style('sibel-admin', get_template_directory_uri() . '/assets/css/admin.css', [], '1.0.0');
    wp_enqueue_script(
        'sibel-admin',
        get_template_directory_uri() . '/assets/js/admin.js',
        ['jquery'],
        '1.0.0',
        true,
    );
});

function sibel_admin_page(): void
{
    if (!current_user_can('manage_sibel')) {
        return;
    }
    $new = new WP_Query([
        'post_type' => 'sibel_lead',
        'post_status' => 'private',
        'posts_per_page' => 5,
        'meta_query' => [['key' => '_sibel_status', 'value' => 'new']],
    ]);
    ?>
    <div class="wrap sibel-admin"><div class="sibel-admin-heading"><div><p class="sibel-eyebrow">СИБЭЛ · УПРАВЛЕНИЕ САЙТОМ</p><h1>Всё под рукой</h1><p>Обновляйте каталог, фотографии и тексты. Изменения появляются на сайте сразу после сохранения.</p></div><a class="sibel-admin-button secondary" href="<?php echo esc_url(
        home_url('/'),
    ); ?>" target="_blank" rel="noopener">Открыть сайт ↗</a></div>
        <div class="sibel-admin-cards"><?php foreach (
            [
                [
                    'sibel_product',
                    'Товары',
                    'Название, фото, характеристики и документы',
                    'dashicons-products',
                ],
                [
                    'sibel_project',
                    'Проекты',
                    'Заказчики, фотографии и выполненные работы',
                    'dashicons-portfolio',
                ],
                [
                    'sibel_certificate',
                    'Сертификаты',
                    'Документы и сертификаты соответствия',
                    'dashicons-awards',
                ],
                ['sibel_partner', 'Партнёры', 'Логотипы компаний на главной странице', 'dashicons-groups'],
            ]
            as [$type, $title, $description, $icon]
        ):
            $count = wp_count_posts($type); ?><a class="sibel-admin-card" href="<?php echo esc_url(
    admin_url('edit.php?post_type=' . $type),
); ?>"><span class="dashicons <?php echo esc_attr($icon); ?>"></span><strong><?php echo esc_html(
    $count->publish ?? 0,
); ?></strong><h2><?php echo esc_html($title); ?></h2><p><?php echo esc_html(
    $description,
); ?></p><span class="sibel-card-link">Управлять →</span></a><?php
        endforeach; ?></div>
        <div class="sibel-admin-columns"><section class="sibel-admin-panel"><div class="sibel-panel-heading"><h2>Новые заявки</h2><a href="<?php echo esc_url(
            admin_url('edit.php?post_type=sibel_lead'),
        ); ?>">Все заявки →</a></div><?php if ($new->have_posts()):
    foreach ($new->posts as $lead): ?><div class="sibel-lead-row"><div><a href="<?php echo esc_url(
    get_edit_post_link($lead->ID),
); ?>"><strong><?php echo esc_html(
    get_post_meta($lead->ID, '_sibel_name', true),
); ?></strong></a><span><?php echo sibel_contact_link(
    get_post_meta($lead->ID, '_sibel_phone', true),
); ?></span></div><span><?php echo esc_html(
    get_the_date('d.m.Y H:i', $lead),
); ?></span></div><?php endforeach;
else:
     ?><div class="sibel-empty"><span class="dashicons dashicons-email-alt"></span><p>Новых заявок пока нет</p><span>Сообщения из форм на сайте появятся здесь.</span></div><?php
endif; ?></section><section class="sibel-admin-panel"><h2>Быстрые действия</h2><div class="sibel-shortcuts"><a href="<?php echo esc_url(
    admin_url('post-new.php?post_type=sibel_product'),
); ?>">＋ Добавить товар</a><a href="<?php echo esc_url(
    admin_url('edit-tags.php?taxonomy=sibel_category&post_type=sibel_product'),
); ?>">Разделы каталога →</a><a href="<?php echo esc_url(
    admin_url('admin.php?page=sibel-settings'),
); ?>">Главная страница и контакты →</a><a href="<?php echo esc_url(
    admin_url('upload.php'),
); ?>">Изображения и файлы →</a><a href="<?php echo esc_url(
    admin_url('edit.php?post_type=page'),
); ?>">Тексты страниц →</a></div></section></div>
        <section class="sibel-admin-panel sibel-help"><h2>Как обновить сайт</h2><ol><li>Откройте нужный раздел и выберите запись или нажмите «Добавить».</li><li>Измените текст, выберите изображение из медиатеки и заполните характеристики.</li><li>Нажмите «Обновить» или «Опубликовать». Для скрытия записи выберите статус «Черновик».</li></ol><p>Поле «Порядок» определяет положение карточки: меньшие числа идут первыми. Черновики не видны посетителям.</p></section>
    </div>
    <?php
}

function sibel_admin_setting_fields(): array
{
    return [
        'Первый экран' => [
            'hero_title' => ['Заголовок', 'text'],
            'hero_description' => ['Описание', 'textarea'],
            'hero_image' => ['Изображение оборудования', 'image'],
        ],
        'О компании' => [
            'directions_title' => ['Заголовок направлений деятельности', 'text'],
            'directions_text' => ['Текст о направлениях деятельности', 'textarea'],
            'about_title' => ['Название раздела о компании', 'text'],
            'company_title' => ['Заголовок о производстве', 'text'],
            'company_text' => ['Описание компании', 'textarea'],
            'company_image' => ['Фотография производства', 'image'],
            'experience' => ['Количество лет опыта', 'text'],
            'experience_caption' => ['Подпись к опыту', 'text'],
        ],
        'Сертификаты и форма' => [
            'certificates_text' => ['Описание сертификатов на главной', 'textarea'],
            'certificates_intro' => ['Вступление на странице сертификатов', 'textarea'],
            'contact_title' => ['Заголовок формы', 'text'],
            'contact_description' => ['Описание формы', 'text'],
        ],
        'Контакты' => [
            'phone' => ['Основной телефон', 'text'],
            'phone_second' => ['Дополнительный телефон', 'text'],
            'email' => ['Электронная почта', 'email'],
            'address' => ['Адрес', 'textarea'],
            'map_link' => ['Ссылка на карточку в Яндекс Картах (необязательно)', 'url'],
            'notification_email' => ['Почта для уведомлений о заявках', 'email'],
        ],
        'Названия разделов' => [
            'partners_title' => ['Партнёры', 'text'],
            'projects_title' => ['Проекты', 'text'],
            'map_title' => ['Адрес и контакты', 'text'],
        ],
        'Разделы главной страницы' => [
            'show_directions' => ['Направления деятельности', 'checkbox'],
            'show_about' => ['О компании', 'checkbox'],
            'show_partners' => ['Нам доверяют', 'checkbox'],
            'show_certificates' => ['Сертифицированное производство', 'checkbox'],
            'show_contact' => ['Форма связи', 'checkbox'],
            'show_projects' => ['Реализованные проекты', 'checkbox'],
            'show_map' => ['Карта и контакты', 'checkbox'],
        ],
    ];
}

function sibel_admin_settings(): void
{
    if (!current_user_can('manage_sibel')) {
        return;
    } ?>
    <div class="wrap sibel-admin"><div class="sibel-admin-heading"><div><p class="sibel-eyebrow">СИБЭЛ · СОДЕРЖИМОЕ</p><h1>Главная страница и контакты</h1><p>Тексты, изображения и видимость разделов — в одном месте.</p></div><a class="sibel-admin-button secondary" href="<?php echo esc_url(
        home_url('/'),
    ); ?>" target="_blank" rel="noopener">Посмотреть сайт ↗</a></div>
    <?php if (
        isset($_GET['saved'])
    ): ?><div class="notice notice-success is-dismissible"><p>Изменения сохранены.</p></div><?php endif; ?>
    <form method="post" action="<?php echo esc_url(
        admin_url('admin-post.php'),
    ); ?>"><input type="hidden" name="action" value="sibel_save_settings"><?php wp_nonce_field(
    'sibel_save_settings',
); ?>
    <?php foreach (
        sibel_admin_setting_fields()
        as $group => $fields
    ): ?><section class="sibel-admin-panel"><h2><?php echo esc_html(
    $group,
); ?></h2><div class="sibel-settings-grid"><?php foreach ($fields as $key => [$label, $type]):
    $value = sibel_setting($key); ?><div class="sibel-field<?php echo $type === 'textarea'
    ? ' full'
    : ''; ?>"><label for="sibel-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label>
        <?php if ($type === 'textarea'): ?><textarea id="sibel-<?php echo esc_attr(
    $key,
); ?>" name="settings[<?php echo esc_attr($key); ?>]" rows="<?php echo $key === 'company_text'
    ? '7'
    : '3'; ?>"><?php echo esc_textarea($value); ?></textarea>
        <?php elseif (
            $type === 'checkbox'
        ): ?><label class="sibel-switch"><input type="checkbox" id="sibel-<?php echo esc_attr(
    $key,
); ?>" name="settings[<?php echo esc_attr($key); ?>]" value="1" <?php checked(
    $value,
    '1',
); ?>>Показывать на сайте</label>
        <?php elseif (
            $type === 'image'
        ): ?><div class="sibel-media-field"><input type="hidden" id="sibel-<?php echo esc_attr(
    $key,
); ?>" name="settings[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr(
    $value,
); ?>"><img class="sibel-media-preview" src="<?php echo esc_url(
    sibel_media_url($value),
); ?>" alt=""><button type="button" class="button sibel-select-media" data-target="sibel-<?php echo esc_attr(
    $key,
); ?>" data-type="image">Выбрать изображение</button></div>
        <?php else: ?><input type="<?php echo esc_attr($type); ?>" id="sibel-<?php echo esc_attr(
    $key,
); ?>" name="settings[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr(
    $value,
); ?>" <?php echo $key === 'email' ? 'required' : ''; ?>><?php endif; ?>
        <?php if (
            $key === 'notification_email'
        ): ?><p class="description">Заявки всегда сохраняются в панели. Для писем укажите адрес и настройте отправку почты на хостинге.</p><?php endif; ?>
        <?php if (
            $key === 'map_link'
        ): ?><p class="description">Обе карты показывают организацию по этой ссылке. Поддерживаются ссылки «Поделиться» из Яндекс Карт и полные ссылки на карточку компании.</p><?php endif; ?>
    </div><?php
endforeach; ?></div></section><?php endforeach; ?>
    <div class="sibel-save-bar"><button type="submit" class="sibel-admin-button">Сохранить изменения</button><span>Изменения будут видны всем посетителям сайта.</span></div></form></div>
    <?php
}

add_action('admin_post_sibel_save_settings', function () {
    if (!current_user_can('manage_sibel')) {
        wp_die('Недостаточно прав.', 403);
    }
    check_admin_referer('sibel_save_settings');
    $input = isset($_POST['settings']) && is_array($_POST['settings']) ? wp_unslash($_POST['settings']) : [];
    $result = get_option('sibel_settings', []);
    foreach (sibel_admin_setting_fields() as $fields) {
        foreach ($fields as $key => [$label, $type]) {
            $value = $input[$key] ?? '';
            if (!is_scalar($value)) {
                continue;
            }
            $result[$key] = match ($type) {
                'checkbox' => $value === '1' ? '1' : '',
                'textarea' => sanitize_textarea_field($value),
                'url' => esc_url_raw($value, ['http', 'https']),
                'email' => sanitize_email($value),
                'image' => preg_match('/^[a-f0-9]+\.(png|svg)$/', $value)
                    ? $value
                    : esc_url_raw($value, ['http', 'https']),
                default => sanitize_text_field($value),
            };
        }
    }
    update_option('sibel_settings', $result);
    wp_safe_redirect(admin_url('admin.php?page=sibel-settings&saved=1'));
    exit();
});

function sibel_media_url(string $value): string
{
    return preg_match('#^https?://#i', $value) ? $value : sibel_asset($value);
}

function sibel_meta_fields(string $type): array
{
    return match ($type) {
        'sibel_product' => [
            'short' => ['Короткое название (например, КТП)', 'text'],
            'features' => ['Характеристики — по одной на строке', 'textarea'],
            'note' => ['Примечание под кнопкой', 'textarea'],
            'document' => ['Техническая документация (PDF)', 'file'],
            'gallery' => ['Фотогалерея', 'gallery'],
            'gallery_labels' => ['Подписи к фотографиям — по одной на строке', 'textarea'],
        ],
        'sibel_project' => [
            'client' => ['Заказчик', 'text'],
            'equipment' => ['Оборудование', 'text'],
            'year' => ['Год исполнения', 'number'],
            'gallery' => ['Фотогалерея', 'gallery'],
        ],
        default => [],
    };
}

add_action('add_meta_boxes', function () {
    foreach (['sibel_product', 'sibel_project'] as $type) {
        add_meta_box('sibel-details', 'Данные карточки', 'sibel_meta_box', $type, 'normal', 'high');
    }
    add_meta_box('sibel-lead', 'Контакты и статус', 'sibel_lead_meta_box', 'sibel_lead', 'normal', 'high');
});

function sibel_meta_box(WP_Post $post): void
{
    wp_nonce_field('sibel_save_meta', 'sibel_meta_nonce');
    echo '<div class="sibel-meta-fields">';
    foreach (sibel_meta_fields($post->post_type) as $key => [$label, $type]) {
        $value = get_post_meta($post->ID, '_sibel_' . $key, true);
        $field_id = 'sibel-meta-' . $key;
        echo '<div class="sibel-field"><label for="' .
            esc_attr($field_id) .
            '">' .
            esc_html($label) .
            '</label>';
        if ($type === 'textarea') {
            echo '<textarea rows="4" id="' .
                esc_attr($field_id) .
                '" name="sibel_meta[' .
                esc_attr($key) .
                ']">' .
                esc_textarea($value) .
                '</textarea>';
        } elseif ($type === 'gallery') {
            echo '<div class="sibel-media-field"><input type="hidden" id="' .
                esc_attr($field_id) .
                '" name="sibel_meta[gallery]" value="' .
                esc_attr($value) .
                '"><div class="sibel-gallery-preview">';
            if ($value) {
                foreach (array_filter(array_map('absint', explode(',', $value))) as $id) {
                    echo wp_get_attachment_image($id, 'thumbnail');
                }
            } else {
                foreach (get_post_meta($post->ID, '_sibel_gallery_assets', true) ?: [] as $asset) {
                    echo '<img src="' . esc_url(sibel_asset($asset)) . '" alt="">';
                }
            }
            echo '</div><button type="button" class="button sibel-select-media" data-target="' .
                esc_attr($field_id) .
                '" data-type="gallery">Выбрать фотографии</button> <button type="button" class="button sibel-clear-gallery" data-target="' .
                esc_attr($field_id) .
                '">Убрать фотографии</button><input type="hidden" name="sibel_clear_gallery" value="0"></div><p class="description">Выберите фотографии в нужном порядке. Новая подборка заменит текущую.</p>';
        } elseif ($type === 'file') {
            echo '<div class="sibel-media-field"><input type="url" id="' .
                esc_attr($field_id) .
                '" name="sibel_meta[' .
                esc_attr($key) .
                ']" value="' .
                esc_attr($value) .
                '" placeholder="Выберите PDF из медиатеки"><button type="button" class="button sibel-select-media" data-target="' .
                esc_attr($field_id) .
                '" data-type="file">Выбрать файл</button></div>';
        } else {
            echo '<input type="' .
                esc_attr($type) .
                '" id="' .
                esc_attr($field_id) .
                '" name="sibel_meta[' .
                esc_attr($key) .
                ']" value="' .
                esc_attr($value) .
                '"' .
                ($type === 'number' ? ' min="1900" max="2100"' : '') .
                '>';
        }
        echo '</div>';
    }
    echo '</div>';
}

add_action('save_post', function ($post_id) {
    if (
        wp_is_post_revision($post_id) ||
        wp_is_post_autosave($post_id) ||
        !isset($_POST['sibel_meta_nonce']) ||
        !is_string($_POST['sibel_meta_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['sibel_meta_nonce'])), 'sibel_save_meta') ||
        !current_user_can('edit_post', $post_id)
    ) {
        return;
    }
    $input =
        isset($_POST['sibel_meta']) && is_array($_POST['sibel_meta']) ? wp_unslash($_POST['sibel_meta']) : [];
    foreach (sibel_meta_fields(get_post_type($post_id)) as $key => [$label, $type]) {
        if (!isset($input[$key]) || !is_scalar($input[$key])) {
            continue;
        }
        $value = match ($type) {
            'textarea' => sanitize_textarea_field($input[$key]),
            'file' => esc_url_raw($input[$key], ['http', 'https']),
            'gallery' => implode(
                ',',
                array_filter(
                    array_map('absint', explode(',', $input[$key])),
                    fn($id) => wp_attachment_is_image($id),
                ),
            ),
            'number' => (string) min(2100, max(1900, absint($input[$key]))),
            default => sanitize_text_field($input[$key]),
        };
        update_post_meta($post_id, '_sibel_' . $key, $value);
    }
    if (!empty($_POST['sibel_clear_gallery']) && $_POST['sibel_clear_gallery'] === '1') {
        delete_post_meta($post_id, '_sibel_gallery_assets');
    }
});

foreach (['sibel_product', 'sibel_project', 'sibel_certificate', 'sibel_partner'] as $sibel_type) {
    add_filter('manage_' . $sibel_type . '_posts_columns', function ($columns) {
        return array_slice($columns, 0, 1, true) + ['sibel_image' => 'Изображение'] +
            array_slice($columns, 1, null, true);
    });
    add_action(
        'manage_' . $sibel_type . '_posts_custom_column',
        function ($column, $post_id) {
            if ($column === 'sibel_image') {
                sibel_image($post_id, '', 'sibel-table-image');
            }
        },
        10,
        2,
    );
}
