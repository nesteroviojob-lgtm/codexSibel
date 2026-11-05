<?php defined('ABSPATH') || exit();
get_header();
the_post();
$pid = get_the_ID();
$detail =
    get_the_post_thumbnail_url($pid, 'full') ?:
    sibel_asset(
        get_post_meta($pid, '_sibel_detail_asset', true) ?: get_post_meta($pid, '_sibel_asset', true),
    );
$gallery_ids = array_filter(array_map('absint', explode(',', get_post_meta($pid, '_sibel_gallery', true))));
$gallery_assets = get_post_meta($pid, '_sibel_gallery_assets', true) ?: [];
?>
<main id="main" class="container inner-main project-main"><?php sibel_breadcrumbs([
    ['Реализованные проекты', sibel_page_url('projects')],
    get_the_title(),
]); ?><h1><?php the_title(); ?></h1>
    <div class="project-detail"><div class="project-detail__gallery"><button class="project-detail__main-image" data-lightbox="<?php echo esc_url(
        $detail,
    ); ?>" data-caption="<?php the_title_attribute(); ?>"><img src="<?php echo esc_url(
    $detail,
); ?>" alt="<?php the_title_attribute(); ?>"></button><div class="project-thumbnails"><?php
$gallery = $gallery_ids
    ? array_map(function ($id) {
        return wp_get_attachment_image_url($id, 'full');
    }, $gallery_ids)
    : array_map('sibel_asset', $gallery_assets);
foreach ($gallery as $url):
    if (!$url) {
        continue;
    } ?><button<?php if (!$gallery_ids): ?> data-figma-asset="<?php echo esc_attr(
     basename($url),
 ); ?>"<?php endif; ?> data-gallery-select="<?php echo esc_url(
     $url,
 ); ?>" aria-label="Показать фотографию проекта"><img src="<?php echo esc_url(
    $url,
); ?>" alt="Фотография проекта" loading="lazy"></button><?php
endforeach;
?></div></div><div class="project-info"><div><h2>Заказчик</h2><p><?php echo esc_html(
    get_post_meta($pid, '_sibel_client', true),
); ?></p></div><div><h2>Оборудование</h2><p><?php echo esc_html(
    get_post_meta($pid, '_sibel_equipment', true),
); ?></p></div><div><h2>Год исполнения</h2><p><?php echo esc_html(
    get_post_meta($pid, '_sibel_year', true),
); ?> г.</p></div><?php if (
     trim(get_the_content())
 ): ?><div><h2>О проекте</h2><div class="prose"><?php the_content(); ?></div></div><?php endif; ?></div></div>
</main>
<?php get_footer(); ?>
