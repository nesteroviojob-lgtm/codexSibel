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
        ); ?>" aria-label="СИБЭЛ — главная">
            <img class="site-logo__dark" src="<?php echo esc_url(
                sibel_asset('2e56a.svg'),
            ); ?>" width="178" height="40" alt="СИБЭЛ">
            <img class="site-logo__light" src="<?php echo esc_url(
                sibel_asset('67b8e.svg'),
            ); ?>" width="178" height="40" alt="" aria-hidden="true">
        </a>
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
        ); ?>"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 3H4a1 1 0 0 0-1 1c0 9.4 7.6 17 17 17a1 1 0 0 0 1-1v-3l-5-2-2 2a14 14 0 0 1-7-7l2-2-2-5Z"/><path d="M14 3a7 7 0 0 1 7 7M14 7a3 3 0 0 1 3 3"/></svg><span><?php echo esc_html(sibel_setting('phone')); ?></span></a></div>
        <button class="header-cta" type="button" data-open-lead aria-label="Оставить заявку"><span class="header-cta__full">Оставить заявку</span><span class="header-cta__short" aria-hidden="true">Заявка</span></button>
        <button class="icon-button search-toggle" type="button" aria-label="Поиск по сайту" data-open-search><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/></svg></button>
        <button class="icon-button menu-toggle" type="button" aria-label="Открыть меню" aria-controls="main-nav" aria-expanded="false"><span></span><span></span><span></span></button>
    </div>
</header>
