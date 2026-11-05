<?php
defined('ABSPATH') || exit();

function sibel_seed(): void
{
    if (get_option('sibel_seed_version')) {
        return;
    }
    $products = [
        ['ktp', 'Комплектные трансформаторные подстанции (КТП)', 'КТП', '40e79.png'],
        ['shcho70', 'Панели распределительных щитов (ЩО70)', 'ЩО70', '53163.png'],
        ['shche', 'Щиты этажные (ЩЭ)', 'ЩЭ', 'd177c.png'],
        ['kso', 'Камеры сборные одностороннего обслуживания (КСО)', 'КСО', 'ca6ec.png'],
        ['vru', 'Вводно-распределительные устройства (ВРУ)', 'ВРУ', '82a8d.png'],
        ['shchr', 'Шкафы распределительные силовые (ШР11, ШРС)', 'ШР11, ШРС', '9a7bb.png'],
        ['pr11', 'Пункты распределительные силовые (ПР11)', 'ПР11', 'f6e9f.png'],
        ['ya5000', 'Ящики управления асинхронными двигателями (Я5000)', 'Я5000', 'f99cb.png'],
        ['lighting', 'Щитки осветительные, рубильники, ящики', 'Щитки', '75a84.png'],
        [
            'yakno',
            'Ячейки комплектных распределительных устройств (ЯКНО, ЯКУ, КРУН, КРУПЭ)',
            'ЯКНО',
            '8f74a.png',
        ],
    ];
    foreach ($products as $order => [$slug, $title, $short, $asset]) {
        $is_ktp = $slug === 'ktp';
        $id = wp_insert_post([
            'post_type' => 'sibel_product',
            'post_status' => 'publish',
            'post_name' => $slug,
            'post_title' => $title,
            'menu_order' => $order,
            'post_content' => $is_ktp
                ? 'Комплектные трансформаторные подстанции (КТП) предназначены для преобразования электрической энергии высокого напряжения в низкое с целью дальнейшего распределения среди потребителей. КТП могут быть выполнены в различных типах исполнения (тупиковое и проходное) и подходят для эксплуатации в различных отраслях, включая промышленность и энергообеспечение.'
                : '',
        ]);
        if (!$id || is_wp_error($id)) {
            continue;
        }
        update_post_meta($id, '_sibel_asset', $asset);
        update_post_meta($id, '_sibel_short', $short);
        if ($is_ktp) {
            update_post_meta(
                $id,
                '_sibel_features',
                "Напряжение: 6(10)/0,4 кВ\nМощность: 25–1000 кВА (киосковое исполнение), 25–250 кВА (мачтовое исполнение)\nЧастота: 50 Гц\nПреобразование напряжения с 6(10) кВ на 0,4 кВ для питания потребителей\nВозможность установки на фундамент или транспортные салазки",
            );
            update_post_meta(
                $id,
                '_sibel_note',
                'В некоторых случаях возможен выпуск КТП в нетиповом исполнении по индивидуальным техническим заданиям',
            );
            update_post_meta($id, '_sibel_gallery_assets', [
                '4d9c2.png',
                '1dcb5.png',
                '0d621.png',
                '981a5.png',
                '096c7.png',
                'cad45.png',
                'da5a3.png',
                '47daa.png',
            ]);
            update_post_meta(
                $id,
                '_sibel_gallery_labels',
                "2КТПН\nКТП\nКТП\nКТП\nКТП-400\nКТПМ\nКТПН\nКТПНУ-63",
            );
        }
    }
    $projects = [
        [
            'rosneft',
            'Производство и поставка КТП для объектов нефтегазовой отрасли',
            'ПАО «Роснефть»',
            '2022',
            '1d877.png',
            'Комплектная трансформаторная подстанция',
        ],
        [
            'tomskgazprom',
            'Объект электроснабжения для газовой отрасли',
            'ООО «Томскгазпром»',
            '2019',
            '01c0a.png',
            'Двухтрансформаторная КТП (2КПТН)',
        ],
        [
            'rzd',
            'Производство и поставка КТП для объектов транспортной инфраструктуры',
            'РЖД',
            '2020',
            '78f90.png',
            'Комплектная трансформаторная подстанция',
        ],
        [
            'suenko',
            'Производство и поставка КТП для объектов энергетической отрасли',
            'АО «СУЭНКО»',
            '2020',
            'f297b.png',
            'Комплектная трансформаторная подстанция',
        ],
    ];
    foreach ($projects as $order => [$slug, $title, $client, $year, $asset, $equipment]) {
        $id = wp_insert_post([
            'post_type' => 'sibel_project',
            'post_status' => 'publish',
            'post_name' => $slug,
            'post_title' => $title,
            'menu_order' => $order,
            'post_content' =>
                $slug === 'rosneft'
                    ? 'Подстанция напряжением 10 кВ и мощностью 1000 кВА предназначена для преобразования и распределения электрической энергии на объекте. В подстанции установлены автоматические выключатели и рубильники для отходящих линий 0,4 кВ. Проект был выполнен с учетом всех технических требований компании «Роснефть», обеспечивая стабильную работу на нефтегазовых объектах.'
                    : '',
        ]);
        if (!$id || is_wp_error($id)) {
            continue;
        }
        foreach (
            ['client' => $client, 'year' => $year, 'asset' => $asset, 'equipment' => $equipment]
            as $key => $value
        ) {
            update_post_meta($id, '_sibel_' . $key, $value);
        }
        if ($slug === 'rosneft') {
            update_post_meta($id, '_sibel_detail_asset', '3924f.png');
            update_post_meta($id, '_sibel_gallery_assets', ['3924f.png', 'eab20.png']);
        }
    }
    $certificates = [
        ['Сертификат промбезопасности', '29611.png'],
        ['Разрешение на применение ЯКНО, ЯКУ, КРУН, КРУПЭ', '553fc.png'],
        ['Свидетельство на товарный знак', '36bc5.png'],
        ['Сертификат КСО', 'ab02a.png'],
        ['Сертификат НКУ', '5ad52.png'],
        ['Сертификат КТП', 'c6391.png'],
        ['Сертификат ЯКНО', '8fcbc.png'],
        ['Сертификат Таврида', '4e1fb.png'],
    ];
    foreach ($certificates as $order => [$title, $asset]) {
        $id = wp_insert_post([
            'post_type' => 'sibel_certificate',
            'post_status' => 'publish',
            'post_title' => $title,
            'menu_order' => $order,
        ]);
        if ($id && !is_wp_error($id)) {
            update_post_meta($id, '_sibel_asset', $asset);
        }
    }
    foreach (
        [
            ['Роснефть', '70f22.png'],
            ['Газпром', 'ec492.png'],
            ['РЖД', '4adbe.svg'],
            ['Томскнефтехим', '1b6dc.png'],
            ['СУЭНКО', '1da5f.png'],
        ]
        as $order => [$title, $asset]
    ) {
        $id = wp_insert_post([
            'post_type' => 'sibel_partner',
            'post_status' => 'publish',
            'post_title' => $title,
            'menu_order' => $order,
        ]);
        if ($id && !is_wp_error($id)) {
            update_post_meta($id, '_sibel_asset', $asset);
        }
    }
    foreach (
        [
            ['products', 'Продукция'],
            ['projects', 'Реализованные проекты'],
            ['certificates', 'Сертификаты'],
            ['contacts', 'Контакты'],
            ['about', 'О компании'],
            ['privacy', 'Политика конфиденциальности'],
            ['terms', 'Пользовательское соглашение'],
        ]
        as [$slug, $title]
    ) {
        if (!get_page_by_path($slug)) {
            wp_insert_post([
                'post_type' => 'page',
                'post_status' => in_array($slug, ['privacy', 'terms'], true) ? 'draft' : 'publish',
                'post_name' => $slug,
                'post_title' => $title,
            ]);
        }
    }
    update_option('blogname', 'СИБЭЛ');
    update_option('blogdescription', 'Производственная электротехническая компания в Томске');
    update_option('sibel_settings', sibel_defaults());
    update_option('sibel_seed_version', '1.0.0');
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'sibel_seed');
add_action('admin_init', 'sibel_seed');
