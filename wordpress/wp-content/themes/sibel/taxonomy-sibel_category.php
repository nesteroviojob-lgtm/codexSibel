<?php defined('ABSPATH') || exit();
get_header();
?>
<main id="main" class="container inner-main"><?php sibel_breadcrumbs([
    ['Продукция', sibel_page_url('products')],
    single_term_title('', false),
]); ?><h1><?php single_term_title(); ?></h1><?php if (
    term_description()
): ?><div class="page-intro"><?php echo wp_kses_post(
    term_description(),
); ?></div><?php endif; ?><div class="catalog-grid"><?php while (have_posts()):
    the_post(); ?><a class="catalog-card" href="<?php the_permalink(); ?>"><div class="catalog-card__image"><?php sibel_image(
    get_the_ID(),
); ?></div><h2><?php the_title(); ?></h2></a><?php
endwhile; ?></div><?php the_posts_pagination(); ?></main>
<?php get_footer(); ?>
