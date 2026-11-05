<?php defined('ABSPATH') || exit();
get_header();
the_post();
$slug = get_post_field('post_name', get_the_ID());
?>
<main id="main" class="container inner-main <?php echo esc_attr('page-' . $slug); ?>">
    <?php sibel_breadcrumbs([get_the_title()]); ?>
    <h1><?php the_title(); ?></h1>
    <?php if ($slug === 'products'): ?>
        <?php
        $categories = get_terms(['taxonomy' => 'sibel_category', 'hide_empty' => true]);
        if (
            !is_wp_error($categories) &&
            $categories
        ): ?><nav class="category-filter" aria-label="Разделы каталога"><a class="active" href="<?php echo esc_url(
    sibel_page_url('products'),
); ?>">Все товары</a><?php foreach ($categories as $cat): ?><a href="<?php echo esc_url(
    get_term_link($cat),
); ?>"><?php echo esc_html($cat->name); ?></a><?php endforeach; ?></nav><?php endif;
        ?>
        <div class="catalog-grid"><?php foreach (
            sibel_items('sibel_product')
            as $i => $p
        ): ?><a data-product="<?php echo has_post_thumbnail($p->ID)
    ? 'custom'
    : esc_attr($p->post_name); ?>" class="catalog-card<?php echo $i === 1
    ? ' catalog-card--highlight'
    : ''; ?>" href="<?php echo esc_url(get_permalink($p)); ?>"><div class="catalog-card__image"><?php
sibel_image($p->ID);
if (
    $p->post_name === 'kso' &&
    !has_post_thumbnail($p->ID)
): ?><img class="catalog-shadow catalog-shadow--one" src="<?php echo esc_url(
    sibel_asset('b36df.svg'),
); ?>" alt=""><img class="catalog-shadow catalog-shadow--two" src="<?php echo esc_url(
    sibel_asset('0bc15.svg'),
); ?>" alt=""><?php endif;
?><span class="catalog-card__arrow"><?php sibel_icon('up-right'); ?></span></div><h2><?php echo esc_html(
    $p->post_title,
); ?></h2></a><?php endforeach; ?></div>
    <?php elseif ($slug === 'certificates'): ?>
        <p class="page-intro"><?php echo esc_html(sibel_setting('certificates_intro')); ?></p>
        <div class="certificates-grid"><?php foreach (sibel_items('sibel_certificate') as $p):
            $url =
                get_the_post_thumbnail_url($p, 'full') ?:
                sibel_asset(
                    get_post_meta($p->ID, '_sibel_asset', true),
                ); ?><button class="certificate-card<?php echo !has_post_thumbnail($p->ID) &&
get_post_meta($p->ID, '_sibel_asset', true) === '4e1fb.png'
    ? ' certificate-card--landscape'
    : ''; ?>" data-lightbox="<?php echo esc_url($url); ?>" data-caption="<?php echo esc_attr(
    $p->post_title,
); ?>"><span class="certificate-card__image"><?php sibel_image(
    $p->ID,
); ?></span><span class="certificate-card__title"><?php echo esc_html(
    $p->post_title,
); ?></span></button><?php
        endforeach; ?></div>
    <?php elseif ($slug === 'projects'): ?>
        <div class="projects-grid"><?php foreach (sibel_items('sibel_project') as $i => $p) {
            sibel_project_card($p, $i === 1);
        } ?></div>
    <?php elseif ($slug === 'contacts'): ?>
        <img class="contacts-glow" src="<?php echo esc_url(
            sibel_asset('c9f14.svg'),
        ); ?>" alt=""><div class="contacts-grid"><a class="contacts-map" href="<?php echo esc_url(
    sibel_setting('map_link'),
); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_url(
    sibel_asset('5e80c.png'),
); ?>" alt="СИБЭЛ на карте Томска, ул. Пролетарская, 62/2"></a><div class="contact-details"><div class="contact-detail"><span class="contact-detail__icon"><?php sibel_icon(
    'contact-phone',
); ?></span><div><span>Адрес</span><p><?php echo nl2br(
    esc_html(sibel_setting('address')),
); ?></p></div></div><div class="contact-detail"><span class="contact-detail__icon"><?php sibel_icon(
    'contact-phone',
); ?></span><div><span>Телефон</span><p><a href="tel:<?php echo esc_attr(
    preg_replace('/[^+0-9]/', '', sibel_setting('phone')),
); ?>"><?php echo esc_html(
    sibel_setting('phone') . '; ' . sibel_setting('phone_second'),
); ?></a></p></div></div><div class="contact-detail"><span class="contact-detail__icon"><?php sibel_icon(
    'mail',
); ?></span><div><span>Почта</span><p><a href="mailto:<?php echo esc_attr(
    sibel_setting('email'),
); ?>"><?php echo esc_html(sibel_setting('email')); ?></a></p></div></div></div></div>
    <?php elseif ($slug === 'about'): ?>
        <div class="about-grid"><div class="about-photo"><img src="<?php echo esc_url(
            sibel_media_url(sibel_setting('company_image') ?: sibel_defaults()['company_image']),
        ); ?>" alt="Производство СИБЭЛ"></div><div class="prose"><h2><?php echo esc_html(
    sibel_setting('company_title'),
); ?></h2><p><?php echo esc_html(
    sibel_setting('company_text'),
); ?></p><button class="button" data-open-lead>Связаться с нами</button></div></div>
    <?php else: ?>
        <article class="prose"><?php the_content(); ?></article>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
