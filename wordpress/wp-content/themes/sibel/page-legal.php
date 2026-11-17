<?php
/*
Template Name: Юридический документ
Template Post Type: page
*/
defined('ABSPATH') || exit();
get_header();
the_post();
?>
<main id="main" class="container inner-main legal-page">
    <?php sibel_breadcrumbs([get_the_title()]); ?>
    <h1><?php the_title(); ?></h1>
    <article class="prose legal-document"><?php the_content(); ?></article>
</main>
<?php get_footer(); ?>
