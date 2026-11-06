<?php defined('ABSPATH') || exit();
get_header();
$products = sibel_items('sibel_product');
?>
<main id="main">
    <section class="hero" aria-labelledby="hero-title" data-node-id="272:543">
        <div class="container hero__inner">
            <img class="hero__glow" src="<?php echo esc_url(
                sibel_asset('630d0.svg'),
            ); ?>" alt="" aria-hidden="true">
            <div class="hero__art"><img class="hero__bolt hero__bolt--top" src="<?php echo esc_url(
                sibel_asset('d22b2.svg'),
            ); ?>" alt=""><img class="hero__bolt hero__bolt--bottom" src="<?php echo esc_url(
    sibel_asset('80361.svg'),
); ?>" alt=""><img class="hero__equipment" src="<?php echo esc_url(
    sibel_media_url(sibel_setting('hero_image') ?: sibel_defaults()['hero_image']),
); ?>" alt="Электрощитовое оборудование СИБЭЛ" fetchpriority="high"><div class="hero__badge">Контроль качества<br>на каждом этапе<br>производства</div></div>
            <div class="hero__copy"><img class="hero__title-glow" src="<?php echo esc_url(
                sibel_asset('b81b6.svg'),
            ); ?>" alt=""><h1 id="hero-title"><?php echo esc_html(
    sibel_setting('hero_title'),
); ?></h1><p><?php echo nl2br(
    esc_html(sibel_setting('hero_description')),
); ?></p><button class="button" data-open-lead>Оставить заявку</button></div>
        </div>
    </section>
    <div class="container home-sections">
        <?php if (sibel_setting('show_directions')): ?>
        <section class="directions" aria-labelledby="directions-title"><div class="section-heading"><h2 id="directions-title"><?php echo esc_html(
            sibel_setting('directions_title'),
        ); ?></h2><a class="button button--soft" href="<?php echo esc_url(
    sibel_page_url('products'),
); ?>">Смотреть продукцию</a></div>
            <div class="directions-grid"><div class="directions-first"><p><?php echo esc_html(
                sibel_setting('directions_text'),
            ); ?></p>
                <?php if (isset($products[0])):
                    $p = $products[0]; ?><a href="<?php echo esc_url(
    get_permalink($p),
); ?>" class="direction-card direction-card--ktp"><img class="direction-decor direction-decor--ktp" src="<?php echo esc_url(
    sibel_asset('61781.svg'),
); ?>" alt=""><h3><?php echo esc_html(
    get_post_meta($p->ID, '_sibel_short', true),
); ?></h3><p><?php echo esc_html(
    preg_replace('/\s*\([^)]*\)$/u', '', $p->post_title),
); ?></p><?php sibel_image($p->ID, '', 'direction-product'); ?></a><?php
                endif; ?></div>
                <?php foreach (
                    array_slice($products, 1, 2)
                    as $i => $p
                ): ?><a class="direction-card <?php echo $i === 0
    ? 'direction-card--orange'
    : 'direction-card--floor'; ?>" href="<?php echo esc_url(get_permalink($p)); ?>"><?php if (
    $i === 1
): ?><img class="direction-decor direction-decor--floor" src="<?php echo esc_url(
    sibel_asset('a50ef.svg'),
); ?>" alt=""><?php endif; ?><h3><?php echo esc_html(
    get_post_meta($p->ID, '_sibel_short', true),
); ?></h3><p><?php echo esc_html(preg_replace('/\s*\([^)]*\)$/u', '', $p->post_title)); ?></p><?php if (
    $i === 0
): ?><span class="direction-arrow"><?php sibel_icon('up-right-white'); ?></span><?php if (
    $p->post_name === 'shcho70' &&
    !has_post_thumbnail($p->ID)
): ?><img class="direction-product" src="<?php echo esc_url(
    sibel_asset('7518b.png'),
); ?>" alt="Панели распределительных щитов"><?php else:sibel_image(
        $p->ID,
        '',
        'direction-product',
    );endif;else:sibel_image($p->ID, '', 'direction-product');endif; ?></a><?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        <?php if (sibel_setting('show_about')): ?>
        <section class="about" id="about" aria-labelledby="about-title"><h2 id="about-title"><?php echo esc_html(
            sibel_setting('about_title'),
        ); ?></h2><div class="about-grid"><div class="about-photo"><img src="<?php echo esc_url(
    sibel_media_url(sibel_setting('company_image') ?: sibel_defaults()['company_image']),
); ?>" alt="Производство электрощитового оборудования" loading="lazy"></div><div class="about-content"><div class="about-text"><h3><?php
$title = sibel_setting('company_title');
if (
    $title === sibel_defaults()['company_title']
): ?><span>Налаженное производство</span> и четкие сроки<?php else:echo esc_html($title);endif;
?></h3><p><?php echo esc_html(
    sibel_setting('company_text'),
); ?></p></div><div class="experience"><div class="experience__panel"><img src="<?php echo esc_url(
    sibel_asset('28419.svg'),
); ?>" alt=""><p><?php echo esc_html(sibel_setting('experience_caption')); ?></p><strong><?php echo esc_html(
    sibel_setting('experience'),
); ?></strong></div><img class="experience__equipment" src="<?php echo esc_url(
    sibel_asset('b4b38.png'),
); ?>" alt="Оборудование в разрезе" loading="lazy"></div></div></div></section>
        <?php endif; ?>
        <?php if (sibel_setting('show_partners')): ?>
        <section class="partners" aria-labelledby="partners-title"><h2 id="partners-title"><?php echo esc_html(
            sibel_setting('partners_title'),
        ); ?></h2><div class="partner-marquee" data-partner-marquee><div class="partner-track"><div class="partner-group"><?php foreach (
    sibel_items('sibel_partner')
    as $index => $p
): ?><div class="partner-logo" data-partner-index="<?php echo (int) $index; ?>"><?php sibel_image(
    $p->ID,
); ?></div><?php endforeach; ?></div></div></div></section>
        <?php endif; ?>
        <?php if (sibel_setting('show_certificates')): ?>
        <section class="certificate-feature"><div class="certificate-feature__copy"><div><h2><span>Сертифицированное</span> производство<br>и продукция</h2><p><?php echo esc_html(
            sibel_setting('certificates_text'),
        ); ?></p></div><a class="button button--outline" href="<?php echo esc_url(
    sibel_page_url('certificates'),
); ?>">Подробнее <?php sibel_icon('arrow'); ?></a></div><a class="certificate-art" href="<?php echo esc_url(
    sibel_page_url('certificates'),
); ?>" aria-label="Посмотреть сертификаты"><img class="certificate-art__glow" src="<?php echo esc_url(
    sibel_asset('cd902.svg'),
); ?>" alt=""><span class="certificate-art__back-left"><img src="<?php echo esc_url(
    sibel_asset('5ad52.png'),
); ?>" alt="" loading="lazy"></span><span class="certificate-art__back-right"><span><img src="<?php echo esc_url(
    sibel_asset('29611.png'),
); ?>" alt="" loading="lazy"></span></span><img class="certificate-art__front" src="<?php echo esc_url(
    sibel_asset('ab02a.png'),
); ?>" alt="Сертификат соответствия" loading="lazy"></a></section>
        <?php endif; ?>
        <?php if (sibel_setting('show_contact')): ?>
        <section class="contact-banner" id="contact"><div class="contact-banner__art"><img class="contact-art__device" src="<?php echo esc_url(
            sibel_asset('26061.png'),
        ); ?>" alt="" loading="lazy"><img class="contact-art__line contact-art__line--one" src="<?php echo esc_url(
    sibel_asset('d0533.svg'),
); ?>" alt=""><img class="contact-art__line contact-art__line--two" src="<?php echo esc_url(
    sibel_asset('b6f16.svg'),
); ?>" alt=""><img class="contact-art__line contact-art__line--three" src="<?php echo esc_url(
    sibel_asset('2016a.svg'),
); ?>" alt=""><img class="contact-art__ellipse" src="<?php echo esc_url(
    sibel_asset('3f743.svg'),
); ?>" alt=""></div><div class="contact-banner__content"><h2><?php echo esc_html(
    sibel_setting('contact_title'),
); ?></h2><p><?php echo esc_html(
    sibel_setting('contact_description'),
); ?></p><?php sibel_contact_form(); ?></div></section>
        <?php endif; ?>
        <?php if (sibel_setting('show_projects')): ?>
        <section class="home-projects"><div class="section-heading"><h2><?php echo esc_html(
            sibel_setting('projects_title'),
        ); ?></h2><a class="button button--outline" href="<?php echo esc_url(
    sibel_page_url('projects'),
); ?>">Смотреть еще <?php sibel_icon('arrow'); ?></a></div><div class="projects-grid"><?php foreach (
    sibel_items('sibel_project', 3)
    as $i => $p
) {
    sibel_project_card($p);
} ?></div></section>
        <?php endif; ?>
        <?php if (sibel_setting('show_map')): ?>
        <section class="home-map" aria-label="Адрес и контакты"><div class="home-map__image"><?php sibel_yandex_map(); ?></div><div class="map-card"><h2><?php echo esc_html(
    sibel_setting('map_title'),
); ?></h2><p><?php echo nl2br(
    esc_html(sibel_setting('address')),
); ?></p><p><strong>Телефон:</strong> <a href="<?php echo esc_url(
    sibel_phone_href(sibel_setting('phone')),
); ?>"><?php echo esc_html(sibel_setting('phone')); ?></a>; <a href="<?php echo esc_url(
    sibel_phone_href(sibel_setting('phone_second')),
); ?>"><?php echo esc_html(
    sibel_setting('phone_second'),
); ?></a></p><p><strong>E-mail:</strong> <a href="mailto:<?php echo esc_attr(
    sibel_setting('email'),
); ?>"><?php echo esc_html(
    sibel_setting('email'),
); ?></a></p><a class="map-open-link" href="<?php echo esc_url(
    sibel_map_url(),
); ?>" target="_blank" rel="noopener">Открыть в Яндекс Картах ↗</a></div></section>
        <?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>
