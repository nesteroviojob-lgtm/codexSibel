<?php defined('ABSPATH') || exit();
get_header();
the_post();
$pid = get_the_ID();
$gallery_ids = array_filter(array_map('absint', explode(',', get_post_meta($pid, '_sibel_gallery', true))));
$gallery_assets = get_post_meta($pid, '_sibel_gallery_assets', true) ?: [];
$gallery_labels = preg_split('/\R/u', get_post_meta($pid, '_sibel_gallery_labels', true));
$features = array_filter(preg_split('/\R/u', get_post_meta($pid, '_sibel_features', true)));
$doc = get_post_meta($pid, '_sibel_document', true);
?>
<main id="main" class="container inner-main product-main">
    <?php sibel_breadcrumbs([['Продукция', sibel_page_url('products')], get_the_title()]); ?>
    <div class="product-detail"><div class="product-detail__image"><?php sibel_image(
        $pid,
    ); ?></div><div class="product-detail__content"><div><h1><?php the_title(); ?></h1><div class="product-description"><?php the_content(); ?></div><?php if (
    $features
): ?><h2>Основные характеристики</h2><ul class="features-list"><?php foreach (
    $features
    as $feature
): ?><li><?php sibel_icon('flash'); ?><span><?php echo esc_html(
    $feature,
); ?></span></li><?php endforeach; ?></ul><?php endif; ?></div><div class="product-cta"><button class="button" data-open-lead>Рассчитать стоимость</button><?php if (
    get_post_meta($pid, '_sibel_note', true)
): ?><p><img class="product-note-line" src="<?php echo esc_url(
    sibel_asset('427fe.svg'),
); ?>" alt=""><?php echo esc_html(
    get_post_meta($pid, '_sibel_note', true),
); ?></p><?php endif; ?></div></div></div>
    <section class="document-banner"><img class="document-banner__decor" src="<?php echo esc_url(
        sibel_asset('76668.svg'),
    ); ?>" alt=""><img class="document-banner__art" src="<?php echo esc_url(
    sibel_asset('d4cac.png'),
); ?>" alt=""><div><h2>Типоисполнения электрощитового оборудования</h2><p>Скачайте файл, чтобы ознакомиться с параметрами модели</p><?php if (
    $doc
): ?><a class="button button--outline" href="<?php echo esc_url(
    $doc,
); ?>" target="_blank" rel="noopener" download>Скачать файл <?php sibel_icon(
    'download',
); ?></a><?php else: ?><button class="button button--outline" data-open-lead title="Запросить документацию у специалиста">Скачать файл <?php sibel_icon(
    'download',
); ?></button><?php endif; ?></div></section>
    <?php if ($gallery_ids || $gallery_assets): ?>
    <section class="product-gallery"><h2>Фото нашей продукции</h2><div class="gallery-grid"><?php
    $gallery = $gallery_ids
        ? array_map(function ($id) {
            return wp_get_attachment_image_url($id, 'full');
        }, $gallery_ids)
        : array_map('sibel_asset', $gallery_assets);
    foreach ($gallery as $i => $url):
        if (!$url) {
            continue;
        } ?><button class="gallery-tile" data-lightbox="<?php echo esc_url(
    $url,
); ?>" data-caption="<?php echo esc_attr(
    $gallery_labels[$i] ?? get_the_title(),
); ?>"><img src="<?php echo esc_url($url); ?>" alt="<?php echo esc_attr(
    $gallery_labels[$i] ?? get_the_title(),
); ?>" loading="lazy"><span><?php echo esc_html(
    $gallery_labels[$i] ?? get_the_title(),
); ?></span></button><?php
    endforeach;
    ?></div></section>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
