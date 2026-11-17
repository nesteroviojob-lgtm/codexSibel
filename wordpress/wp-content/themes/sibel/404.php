<?php defined('ABSPATH') || exit();
get_header();
?>
<main id="main" class="container inner-main empty-state"><p class="error-code">404</p><h1>Страница не найдена</h1><p>Возможно, адрес изменился. Найдите нужную продукцию в каталоге.</p><a class="button" href="<?php echo esc_url(
    sibel_page_url('products'),
); ?>">Перейти в каталог</a></main>
<?php get_footer(); ?>
