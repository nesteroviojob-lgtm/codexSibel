<?php defined('ABSPATH') || exit(); ?>
<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-company"><a href="<?php echo esc_url(
            home_url('/'),
        ); ?>"><img src="<?php echo esc_url(
    sibel_asset('7845a.svg'),
); ?>" alt="СИБЭЛ" width="220" height="49"></a><p><?php echo nl2br(
    esc_html(sibel_setting('address')),
); ?></p></div>
        <div class="footer-contact"><div class="footer-phones"><?php sibel_icon(
            'phone',
        ); ?><span><a href="<?php echo esc_url(
    sibel_phone_href(sibel_setting('phone')),
); ?>"><?php echo esc_html(sibel_setting('phone')); ?></a><a href="<?php echo esc_url(
    sibel_phone_href(sibel_setting('phone_second')),
); ?>"><?php echo esc_html(
    sibel_setting('phone_second'),
); ?></a></span></div><a href="mailto:<?php echo esc_attr(sibel_setting('email')); ?>"><?php sibel_icon(
    'mail',
); ?><span><?php echo esc_html(
    sibel_setting('email'),
); ?></span></a><button class="button" data-open-lead>Оставить заявку</button></div>
        <div class="footer-column"><h2><a href="<?php echo esc_url(
            sibel_page_url('products'),
        ); ?>">Продукция</a></h2><?php foreach (
    sibel_items('sibel_product', 5)
    as $p
): ?><a href="<?php echo esc_url(get_permalink($p)); ?>"><?php echo esc_html(
    get_post_meta($p->ID, '_sibel_short', true) ?: $p->post_title,
); ?></a><?php endforeach; ?></div>
        <div class="footer-column"><h2><a href="<?php echo esc_url(
            sibel_page_url('certificates'),
        ); ?>">Сертификаты</a></h2><?php foreach (sibel_items('sibel_certificate', 6) as $i => $p):
    if ($i === 2) {
        continue;
    }
    $certificate_url = sibel_certificate_url($p);
    if ($certificate_url): ?><a href="<?php echo esc_url(
    $certificate_url,
); ?>" data-lightbox="<?php echo esc_url($certificate_url); ?>" data-caption="<?php echo esc_attr(
    $p->post_title,
); ?>"><?php echo esc_html(
    $i === 1 ? 'Разрешение на применение' : $p->post_title,
); ?></a><?php else: ?><span><?php echo esc_html($p->post_title); ?></span><?php endif;
endforeach; ?></div>
        <div class="footer-column"><h2><a href="<?php echo esc_url(
            sibel_page_url('projects'),
        ); ?>">Проекты</a></h2><?php foreach (
    sibel_items('sibel_project', 3)
    as $p
): ?><a href="<?php echo esc_url(get_permalink($p)); ?>"><?php echo esc_html(
    get_post_meta($p->ID, '_sibel_client', true),
); ?></a><?php endforeach; ?></div>
    </div>
    <div class="footer-bottom"><div class="container"><span>© <?php echo esc_html(
        wp_date('Y'),
    ); ?> Сибэл. Все права защищены.</span><div><a href="<?php echo esc_url(
     sibel_page_url('privacy'),
 ); ?>">Политика обработки персональных данных</a><a href="<?php echo esc_url(
    sibel_page_url('consent'),
); ?>">Согласие на обработку персональных данных</a></div></div></div>
</footer>
<dialog class="site-dialog" id="lead-dialog" aria-labelledby="lead-title"><button class="dialog-close" aria-label="Закрыть" data-close-dialog>×</button><h2 id="lead-title">Связаться по проекту</h2><p>Оставьте контакт — мы перезвоним и ответим на ваши вопросы</p><?php sibel_contact_form(
    'modal',
); ?></dialog>
<dialog class="site-dialog" id="search-dialog" aria-labelledby="search-title"><button class="dialog-close" aria-label="Закрыть" data-close-dialog>×</button><h2 id="search-title">Поиск по сайту</h2><form class="search-form" action="<?php echo esc_url(
    home_url('/'),
); ?>" method="get"><label for="site-search">Название товара или проекта</label><div><input id="site-search" type="search" name="s" placeholder="Например, трансформаторная подстанция" required><button class="button" type="submit">Найти</button></div></form></dialog>
<dialog class="lightbox" id="lightbox" aria-label="Просмотр изображения"><button class="dialog-close" aria-label="Закрыть" data-close-dialog>×</button><img src="" alt=""><p></p></dialog>
<?php wp_footer(); ?>
</body>
</html>
