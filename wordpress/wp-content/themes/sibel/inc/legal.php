<?php
defined('ABSPATH') || exit();

function sibel_legal_documents(): array
{
    return [
        'privacy' => 'Политика обработки персональных данных',
        'consent' => 'Согласие на обработку персональных данных',
    ];
}

function sibel_legal_template(string $slug): string
{
    if (!isset(sibel_legal_documents()[$slug])) {
        return '';
    }
    $source = file_get_contents(get_template_directory() . '/legal/' . $slug . '.html');
    return strtr($source, [
        '{{EMAIL}}' => esc_html(sibel_setting('email')),
        '{{MAILTO}}' => esc_url('mailto:' . sanitize_email(sibel_setting('email'))),
        '{{ADDRESS}}' => esc_html(preg_replace('/\s+/u', ' ', sibel_setting('address'))),
    ]);
}

function sibel_install_legal_documents(): void
{
    if (get_option('sibel_legal_documents_version') === '1') {
        return;
    }
    foreach (sibel_legal_documents() as $slug => $title) {
        $page = get_page_by_path($slug, OBJECT, 'page');
        if (!$page && $slug === 'consent') {
            $legacy = get_page_by_path('terms', OBJECT, 'page');
            if ($legacy && $legacy->post_status === 'draft' && !trim($legacy->post_content)) {
                $page = $legacy;
            }
        }
        if (!$page || ($page->post_status === 'draft' && !trim($page->post_content))) {
            $id = wp_insert_post(
                [
                    'ID' => $page ? $page->ID : 0,
                    'post_type' => 'page',
                    'post_name' => $slug,
                    'post_title' => $title,
                    'post_content' => wp_slash(sibel_legal_template($slug)),
                    // Local previews show both documents immediately; the owner completes production drafts.
                    'post_status' => wp_get_environment_type() === 'local' ? 'publish' : 'draft',
                    'page_template' => 'page-legal.php',
                ],
                true,
            );
            if (is_wp_error($id) || !$id) {
                return;
            }
        } elseif (in_array(get_post_meta($page->ID, '_wp_page_template', true), ['', 'default'], true)) {
            update_post_meta($page->ID, '_wp_page_template', 'page-legal.php');
        }
    }
    update_option('sibel_legal_documents_version', '1');
}
add_action('init', 'sibel_install_legal_documents', 30);

function sibel_admin_legal_page(): void
{
    if (!current_user_can('manage_sibel')) {
        return;
    } ?>
    <div class="wrap sibel-admin">
        <div class="sibel-admin-heading"><div><p class="sibel-eyebrow">СИБЭЛ · ДОКУМЕНТЫ</p><h1>Юридические документы</h1><p>Два готовых шаблона для сайта с формой заявки.</p></div></div>
        <section class="sibel-admin-panel"><h2>Что заполнить</h2><p>Замените поля <strong>[ЗАПОЛНИТЬ: …]</strong> на реквизиты компании, домен, сроки хранения и сведения о фактических сервисах. Контакты из настроек сайта уже подставлены в первоначальные тексты. После редактирования сохраните и опубликуйте страницы перед запуском сайта.</p><p>На локальной версии шаблоны опубликованы для просмотра. На новом рабочем сайте они создаются черновиками. Последующие обновления темы ваши тексты не перезаписывают.</p></section>
        <?php foreach (sibel_legal_documents() as $slug => $title):

            $page = get_page_by_path($slug, OBJECT, 'page');
            if (!$page) {
                continue;
            }
            $placeholders = str_contains($page->post_content, '[ЗАПОЛНИТЬ:');
            ?>
            <section class="sibel-admin-panel"><h2><?php echo esc_html($title); ?></h2><p><?php echo esc_html(
    $placeholders
        ? 'В тексте есть поля для заполнения.'
        : 'Поля шаблона заменены. Проверьте актуальность текста.',
); ?></p><p>Статус: <?php echo esc_html(
    $page->post_status === 'publish' ? 'Опубликован' : 'Черновик',
); ?></p><p><a class="button button-primary" href="<?php echo esc_url(
    get_edit_post_link($page->ID, 'raw'),
); ?>">Редактировать документ</a> <a class="button" href="<?php echo esc_url(
    $page->post_status === 'publish' ? get_permalink($page) : get_preview_post_link($page),
); ?>" target="_blank" rel="noopener">Открыть документ ↗</a></p></section>
        <?php
        endforeach; ?>
        <p>Для дополнительных документов в редакторе страницы доступен шаблон «Юридический документ».</p>
    </div>
    <?php
}
