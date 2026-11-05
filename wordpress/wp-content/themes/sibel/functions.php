<?php
defined('ABSPATH') || exit();

require_once __DIR__ . '/inc/content.php';
require_once __DIR__ . '/inc/seed.php';
require_once __DIR__ . '/inc/admin.php';
require_once __DIR__ . '/inc/leads.php';

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('custom-logo');
    register_nav_menus(['sibel_primary' => 'Основное меню сайта']);
    remove_action('wp_head', 'wp_generator');
});

add_action('wp_enqueue_scripts', function () {
    $uri = get_template_directory_uri();
    wp_enqueue_style(
        'sibel',
        $uri . '/assets/css/site.css',
        [],
        (string) filemtime(__DIR__ . '/assets/css/site.css'),
    );
    wp_enqueue_script('sibel', $uri . '/assets/js/site.js', [], '1.0.0', [
        'strategy' => 'defer',
        'in_footer' => true,
    ]);
    wp_localize_script('sibel', 'sibelConfig', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('sibel_lead'),
    ]);
});

function sibel_asset(string $filename): string
{
    $url = get_template_directory_uri() . '/assets/images/' . $filename;
    $file = get_template_directory() . '/assets/images/' . $filename;
    return is_file($file) ? add_query_arg('ver', (string) filemtime($file), $url) : $url;
}

function sibel_setting(string $key, $fallback = null)
{
    $settings = get_option('sibel_settings', []);
    $defaults = sibel_defaults();
    return $settings[$key] ?? ($fallback ?? ($defaults[$key] ?? ''));
}

function sibel_image($post_id, string $fallback = '', string $class = '', string $alt = ''): void
{
    $src = get_the_post_thumbnail_url($post_id, 'full');
    if (!$src) {
        $asset = get_post_meta($post_id, '_sibel_asset', true) ?: $fallback;
        $src = $asset ? sibel_asset($asset) : '';
    }
    if ($src) {
        echo '<img src="' .
            esc_url($src) .
            '" alt="' .
            esc_attr($alt ?: get_the_title($post_id)) .
            '" class="' .
            esc_attr($class) .
            '" loading="lazy" decoding="async">';
    }
}

function sibel_icon(string $name, string $class = ''): void
{
    $icons = [
        'phone' => 'c55c0.svg',
        'contact-phone' => 'd913e.svg',
        'up-right-white' => '6c4b7.svg',
        'mail' => 'd0c87.svg',
        'search' => '5641e.svg',
        'search-white' => '9dd11.svg',
        'arrow' => '81865.svg',
        'next' => '98917.svg',
        'up-right' => '85891.svg',
        'flash' => '640dd.svg',
        'download' => '26aca.svg',
    ];
    if (isset($icons[$name])) {
        echo '<span class="icon icon--' .
            esc_attr($name) .
            ' ' .
            esc_attr($class) .
            '" aria-hidden="true"><img src="' .
            esc_url(sibel_asset($icons[$name])) .
            '" alt=""></span>';
    }
}

function sibel_items(string $type, int $limit = -1): array
{
    return get_posts([
        'post_type' => $type,
        'posts_per_page' => $limit,
        'post_status' => 'publish',
        'orderby' => ['menu_order' => 'ASC', 'ID' => 'ASC'],
        'suppress_filters' => false,
    ]);
}

function sibel_page_url(string $slug): string
{
    $page = get_page_by_path($slug);
    return $page ? get_permalink($page) : home_url('/' . $slug . '/');
}

function sibel_breadcrumbs(array $parts): void
{
    echo '<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="' .
        esc_url(home_url('/')) .
        '">Главная</a>';
    foreach ($parts as $part) {
        echo '<span aria-hidden="true">/</span>';
        if (is_array($part)) {
            echo '<a href="' . esc_url($part[1]) . '">' . esc_html($part[0]) . '</a>';
        } else {
            echo '<span aria-current="page">' . esc_html($part) . '</span>';
        }
    }
    echo '</nav>';
}

function sibel_project_card(WP_Post $project, bool $highlight = false): void
{
    ?>
    <a class="project-card<?php echo $highlight
        ? ' project-card--highlight'
        : ''; ?>" href="<?php echo esc_url(get_permalink($project)); ?>">
        <div class="project-card__image"><?php sibel_image($project->ID); ?></div>
        <div class="project-card__body">
            <div><h3><?php echo esc_html(
                $project->post_title,
            ); ?></h3><p><?php echo esc_html(get_post_meta($project->ID, '_sibel_equipment', true)); ?></p></div>
            <div class="project-card__meta"><span><?php echo esc_html(
                get_post_meta($project->ID, '_sibel_client', true),
            ); ?></span><span><?php echo esc_html(get_post_meta($project->ID, '_sibel_year', true)); ?></span></div>
        </div>
    </a>
    <?php
}

function sibel_contact_form(string $id = 'contact'): void
{
    ?>
    <form class="lead-form" data-lead-form>
        <div class="lead-form__fields">
            <label>Имя<input name="name" autocomplete="name" placeholder="Ваше имя" required maxlength="100"></label>
            <label>Название компании<input name="company" autocomplete="organization" placeholder="Ваша компания" maxlength="150"></label>
            <label>Номер телефона<input type="tel" name="phone" autocomplete="tel" placeholder="+7 (___) ___-__-__" required maxlength="30"></label>
            <button class="button" type="submit">Оставить запрос</button>
        </div>
        <label class="consent"><input type="checkbox" name="consent" required><span>Согласен на обработку персональных данных в соответствии с <a href="<?php echo esc_url(
            sibel_page_url('privacy'),
        ); ?>">Политикой конфиденциальности</a></span></label>
        <div class="form-honeypot" aria-hidden="true"><label>Сайт<input name="website" tabindex="-1" autocomplete="off"></label></div>
        <input type="hidden" name="product" value="<?php echo esc_attr(
            is_singular('sibel_product') ? get_the_title() : '',
        ); ?>">
        <p class="form-result" role="status" aria-live="polite"></p>
    </form>
    <?php
}
