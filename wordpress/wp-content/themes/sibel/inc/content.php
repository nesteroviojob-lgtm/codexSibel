<?php
defined('ABSPATH') || exit();

function sibel_defaults(): array
{
    return [
        'hero_title' => 'Производственная электротехническая компания',
        'hero_description' =>
            "Изготавливаем электрощиты по типовым\nи индивидуальным схемам заказчика,\nиспользуем комплектующие изделия лучших российских и зарубежных производителей",
        'hero_image' => 'd9626.png',
        'directions_title' => 'Основные направления деятельности',
        'about_title' => 'О компании',
        'partners_title' => 'Нам доверяют',
        'projects_title' => 'Реализованные проекты',
        'map_title' => 'Адрес и контакты',
        'company_title' => 'Налаженное производство и четкие сроки',
        'company_text' =>
            'Продукция «СИБЭЛ» применяется в системах электроснабжения промышленных предприятий, на объектах городского и жилищно-коммунального хозяйства, в строительстве и энергетике. Все выпускаемые изделия имеют необходимые сертификаты соответствия. Особое внимание мы уделяем качеству сборки. Каждое изделие проходит проверку и приёмочный контроль перед отгрузкой. Мы тщательно прорабатываем конструктивные и монтажные решения, чтобы оборудование было надёжным, безопасным и удобным в эксплуатации. Важной частью нашей работы является соблюдение согласованных сроков производства.',
        'company_image' => '5c50a.png',
        'experience' => '24',
        'experience_caption' => '24 года опыта',
        'directions_text' =>
            'Производство электрощитового оборудования и аппаратуры, низковольтных комплектных устройств (НКУ), устройств распределения электроэнергии.',
        'certificates_text' =>
            'Производство и электрощитовое оборудование СИБЭЛ имеют необходимые сертификаты и разрешительную документацию, подтверждающие соответствие требованиям безопасности, промышленным стандартам и техническим регламентам.',
        'certificates_intro' =>
            'Каждое оборудование, выпускаемое нашей компанией, сертифицировано в соответствии с российскими и международными стандартами безопасности и качества. Мы гарантируем, что все наши изделия соответствуют строгим требованиям безопасности и техническим регламентам.',
        'contact_title' => 'Связаться по проекту',
        'contact_description' => 'Оставьте контакт — мы перезвоним и ответим на ваши вопросы',
        'phone' => '+7(3822) 593-193',
        'phone_second' => '40-48-02',
        'email' => 'sibel.tomsk@mail.ru',
        'address' => "634009, Россия, г.Томск,\nул.Пролетарская, 62/2",
        'map_link' => 'https://yandex.ru/maps/?text=Томск%20Пролетарская%2062%2F2%20Сибэл',
        'notification_email' => '',
        'show_directions' => '1',
        'show_about' => '1',
        'show_partners' => '1',
        'show_certificates' => '1',
        'show_contact' => '1',
        'show_projects' => '1',
        'show_map' => '1',
    ];
}

add_action('init', function () {
    $types = [
        'sibel_product' => ['Товары', 'Товар', 'products', 'dashicons-products'],
        'sibel_project' => ['Проекты', 'Проект', 'projects', 'dashicons-portfolio'],
        'sibel_certificate' => ['Сертификаты', 'Сертификат', 'certificates', 'dashicons-awards'],
        'sibel_partner' => ['Партнёры', 'Партнёр', 'partners', 'dashicons-groups'],
    ];
    foreach ($types as $key => [$plural, $single, $slug, $icon]) {
        register_post_type($key, [
            'labels' => [
                'name' => $plural,
                'singular_name' => $single,
                'add_new' => 'Добавить',
                'add_new_item' => 'Добавить: ' . $single,
                'edit_item' => 'Редактировать: ' . $single,
                'all_items' => $plural,
                'not_found' => 'Записи не найдены',
                'featured_image' => 'Изображение',
                'set_featured_image' => 'Выбрать изображение',
            ],
            'public' => in_array($key, ['sibel_product', 'sibel_project'], true),
            'show_ui' => true,
            'show_in_menu' => 'sibel',
            'show_in_rest' => false,
            'has_archive' => false,
            'rewrite' => ['slug' => $slug, 'with_front' => false],
            'menu_icon' => $icon,
            'supports' => ['title', 'editor', 'thumbnail', 'page-attributes'],
        ]);
    }
    register_taxonomy('sibel_category', 'sibel_product', [
        'labels' => [
            'name' => 'Разделы каталога',
            'singular_name' => 'Раздел',
            'add_new_item' => 'Добавить раздел',
            'edit_item' => 'Редактировать раздел',
        ],
        'public' => true,
        'hierarchical' => true,
        'show_in_menu' => true,
        'show_admin_column' => true,
        'capabilities' => [
            'manage_terms' => 'manage_sibel',
            'edit_terms' => 'manage_sibel',
            'delete_terms' => 'manage_sibel',
            'assign_terms' => 'edit_posts',
        ],
        'rewrite' => ['slug' => 'category'],
    ]);
    register_post_type('sibel_lead', [
        'labels' => ['name' => 'Заявки', 'singular_name' => 'Заявка', 'edit_item' => 'Заявка'],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'sibel',
        'supports' => ['title'],
        'capabilities' => [
            'create_posts' => 'do_not_allow',
            'edit_post' => 'manage_sibel',
            'read_post' => 'manage_sibel',
            'delete_post' => 'manage_sibel',
            'edit_posts' => 'manage_sibel',
            'edit_others_posts' => 'manage_sibel',
            'publish_posts' => 'manage_sibel',
            'read_private_posts' => 'manage_sibel',
            'delete_posts' => 'manage_sibel',
            'delete_private_posts' => 'manage_sibel',
            'delete_published_posts' => 'manage_sibel',
            'delete_others_posts' => 'manage_sibel',
            'edit_private_posts' => 'manage_sibel',
            'edit_published_posts' => 'manage_sibel',
        ],
        'map_meta_cap' => false,
    ]);
});

add_filter(
    'use_block_editor_for_post_type',
    function ($use, $type) {
        return str_starts_with($type, 'sibel_') ? false : $use;
    },
    10,
    2,
);
