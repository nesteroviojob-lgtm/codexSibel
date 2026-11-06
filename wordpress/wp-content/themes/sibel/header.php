<?php defined('ABSPATH') || exit();
$is_home = is_front_page();
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#e65100">
    <meta name="description" content="<?php echo esc_attr(
        is_singular('sibel_product') || is_singular('sibel_project')
            ? wp_trim_words(wp_strip_all_tags(get_the_content()), 35)
            : get_bloginfo('description'),
    ); ?>">
    <?php wp_head(); ?>
</head>
<body <?php body_class($is_home ? 'home-page' : 'inner-page'); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main">Перейти к содержимому</a>
<header class="site-header<?php echo $is_home ? ' site-header--inverse' : ''; ?>">
    <div class="container site-header__inner">
        <a class="site-logo" href="<?php echo esc_url(
            home_url('/'),
        ); ?>" aria-label="СИБЭЛ — главная"><img src="<?php echo esc_url(
    sibel_asset($is_home ? '67b8e.svg' : '2e56a.svg'),
); ?>" width="178" height="40" alt="СИБЭЛ"></a>
        <nav class="main-nav" aria-label="Основное меню" id="main-nav">
            <?php if (has_nav_menu('sibel_primary')):
                wp_nav_menu([
                    'theme_location' => 'sibel_primary',
                    'container' => false,
                    'menu_class' => 'main-nav__list',
                    'depth' => 2,
                ]);
            else:
                 ?>
            <?php foreach (
                [
                    'products' => 'Продукция',
                    'about' => 'О компании',
                    'certificates' => 'Сертификаты',
                    'projects' => 'Проекты',
                    'contacts' => 'Контакты',
                ]
                as $slug => $label
            ): ?>
                <a href="<?php echo esc_url(
                    $slug === 'about' ? home_url('/#about') : sibel_page_url($slug),
                ); ?>"<?php if (is_page($slug)) {
    echo ' aria-current="page"';
} ?>><?php echo esc_html($label); ?></a>
            <?php endforeach; ?>
            <?php
            endif; ?>
        </nav>
        <div class="header-contacts"><a href="<?php echo esc_url(
            sibel_phone_href(sibel_setting('phone')),
        ); ?>"><?php echo esc_html(sibel_setting('phone')); ?></a><a href="mailto:<?php echo esc_attr(
    sibel_setting('email'),
); ?>"><?php echo esc_html(sibel_setting('email')); ?></a></div>
        <button class="icon-button search-toggle" type="button" aria-label="Поиск по сайту" data-open-search><?php sibel_icon(
            $is_home ? 'search-white' : 'search',
        ); ?></button>
        <button class="icon-button menu-toggle" type="button" aria-label="Открыть меню" aria-controls="main-nav" aria-expanded="false"><span></span><span></span><span></span></button>
    </div>
</header>
