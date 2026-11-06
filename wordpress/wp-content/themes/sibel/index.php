<?php defined('ABSPATH') || exit();
get_header();
?>
<main id="main" class="container inner-main"><h1><?php echo is_search()
    ? 'Результаты поиска'
    : 'Новости'; ?></h1><?php
if (is_search()): ?><p class="page-intro">По запросу «<?php echo esc_html(
    get_search_query(),
); ?>»</p><?php endif;
if (have_posts()): ?><div class="search-results"><?php while (have_posts()):
    the_post(); ?><article><a href="<?php the_permalink(); ?>"><h2><?php the_title(); ?></h2></a><p><?php echo esc_html(
    wp_trim_words(wp_strip_all_tags(get_the_content()), 35),
); ?></p></article><?php
endwhile; ?></div><?php the_posts_pagination([
    'prev_text' => 'Назад',
    'next_text' => 'Далее',
]);else: ?><div class="empty-state"><p>Ничего не найдено. Попробуйте другое название или свяжитесь с нами.</p><button class="button" data-open-lead>Связаться с нами</button></div><?php endif;
?></main>
<?php get_footer(); ?>
