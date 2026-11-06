<?php
defined('ABSPATH') || exit();

function sibel_submit_lead(): void
{
    if (!check_ajax_referer('sibel_lead', 'nonce', false)) {
        wp_send_json_error(['message' => 'Обновите страницу и попробуйте ещё раз.'], 403);
    }
    $success = ['message' => 'Спасибо! Заявка принята. Наш специалист свяжется с вами.'];
    $fields = ['name', 'company', 'phone', 'email', 'website', 'product', 'consent'];
    foreach ($fields as $field) {
        if (isset($_POST[$field]) && !is_string($_POST[$field])) {
            wp_send_json_error(['message' => 'Проверьте заполненные поля.'], 400);
        }
    }
    if (!empty($_POST['website'])) {
        wp_send_json_success($success);
    }
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $company = sanitize_text_field(wp_unslash($_POST['company'] ?? ''));
    $phone_raw = trim(wp_unslash($_POST['phone'] ?? ''));
    $phone_valid = preg_match('/^(?:\+7|7|8)[0-9 ()-]+$/D', $phone_raw);
    $phone_digits = preg_replace('/\D/', '', $phone_raw);
    $phone = '+7' . substr($phone_digits, 1);
    $email_raw = trim(wp_unslash($_POST['email'] ?? ''));
    $product = sanitize_text_field(wp_unslash($_POST['product'] ?? ''));
    if (
        !$name ||
        mb_strlen($name) > 100 ||
        mb_strlen($company) > 150 ||
        !$phone_valid ||
        strlen($phone_digits) !== 11 ||
        mb_strlen($product) > 300 ||
        ($email_raw && !is_email($email_raw)) ||
        !in_array($_POST['consent'] ?? '', ['1', 'on'], true)
    ) {
        wp_send_json_error(
            ['message' => 'Укажите имя, корректный телефон и согласие на обработку данных.'],
            422,
        );
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'sibel_lead_' . substr(wp_hash($ip), 0, 32);
    $rate = (int) get_transient($key);
    if ($rate >= 5) {
        wp_send_json_error(
            [
                'message' =>
                    'Вы уже отправили несколько заявок. Пожалуйста, попробуйте позже или позвоните нам.',
            ],
            429,
        );
    }
    $id = wp_insert_post(
        ['post_type' => 'sibel_lead', 'post_status' => 'private', 'post_title' => $name . ' · ' . $phone],
        true,
    );
    if (is_wp_error($id) || !$id) {
        wp_send_json_error(
            ['message' => 'Не удалось сохранить заявку. Попробуйте позже или позвоните нам.'],
            500,
        );
    }
    foreach (
        [
            'name' => $name,
            'company' => $company,
            'phone' => $phone,
            'email' => sanitize_email($email_raw),
            'product' => $product,
            'status' => 'new',
            'consent' => wp_date('c'),
        ]
        as $key_name => $value
    ) {
        update_post_meta($id, '_sibel_' . $key_name, $value);
    }
    $consent_page = get_page_by_path('consent', OBJECT, 'page');
    if ($consent_page) {
        $consent_text = wp_strip_all_tags($consent_page->post_content);
        update_post_meta($id, '_sibel_consent_url', get_permalink($consent_page));
        update_post_meta($id, '_sibel_consent_text', $consent_text);
        update_post_meta($id, '_sibel_consent_version', hash('sha256', $consent_text));
    }
    set_transient($key, $rate + 1, HOUR_IN_SECONDS);
    $recipient = sibel_setting('notification_email');
    if ($recipient && is_email($recipient)) {
        $sent = wp_mail(
            $recipient,
            'Новая заявка с сайта СИБЭЛ',
            "Имя: $name\nКомпания: $company\nТелефон: $phone\nE-mail: $email_raw\nТовар: $product\n\n" .
                admin_url('post.php?post=' . $id . '&action=edit'),
        );
        update_post_meta($id, '_sibel_email_sent', $sent ? '1' : '0');
    }
    wp_send_json_success($success);
}
add_action('wp_ajax_nopriv_sibel_lead', 'sibel_submit_lead');
add_action('wp_ajax_sibel_lead', 'sibel_submit_lead');

function sibel_lead_meta_box(WP_Post $post): void
{
    if (!current_user_can('manage_sibel')) {
        return;
    }
    wp_nonce_field('sibel_lead_status', 'sibel_lead_nonce');
    echo '<div class="sibel-meta-fields">';
    foreach (
        [
            'name' => 'Имя',
            'company' => 'Компания',
            'phone' => 'Телефон',
            'email' => 'Почта',
            'product' => 'Товар',
            'consent' => 'Согласие получено',
        ]
        as $key => $label
    ) {
        $value = get_post_meta($post->ID, '_sibel_' . $key, true);
        $display = in_array($key, ['phone', 'email'], true)
            ? sibel_contact_link($value, $key)
            : esc_html($value ?: '—');
        echo '<div class="sibel-field"><strong>' .
            esc_html($label) .
            '</strong><p>' .
            $display .
            '</p></div>';
    }
    $status = get_post_meta($post->ID, '_sibel_status', true);
    echo '<div class="sibel-field"><label for="sibel-lead-status">Статус обработки</label><select id="sibel-lead-status" name="sibel_lead_status">';
    foreach (['new' => 'Новая', 'processing' => 'В работе', 'done' => 'Обработана'] as $value => $label) {
        echo '<option value="' .
            esc_attr($value) .
            '" ' .
            selected($status, $value, false) .
            '>' .
            esc_html($label) .
            '</option>';
    }
    echo '</select></div></div>';
}

add_action('save_post_sibel_lead', function ($post_id) {
    if (
        wp_is_post_revision($post_id) ||
        wp_is_post_autosave($post_id) ||
        !current_user_can('manage_sibel') ||
        !isset($_POST['sibel_lead_nonce']) ||
        !is_string($_POST['sibel_lead_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['sibel_lead_nonce'])), 'sibel_lead_status')
    ) {
        return;
    }
    $status =
        isset($_POST['sibel_lead_status']) && is_string($_POST['sibel_lead_status'])
            ? sanitize_key($_POST['sibel_lead_status'])
            : '';
    if (in_array($status, ['new', 'processing', 'done'], true)) {
        update_post_meta($post_id, '_sibel_status', $status);
    }
});

add_filter(
    'manage_sibel_lead_posts_columns',
    fn($columns) => [
        'cb' => $columns['cb'],
        'title' => 'Заявка',
        'sibel_phone' => 'Телефон',
        'sibel_company' => 'Компания',
        'sibel_email' => 'Почта',
        'sibel_status' => 'Статус',
        'date' => 'Дата',
    ],
);
add_action(
    'manage_sibel_lead_posts_custom_column',
    function ($column, $id) {
        if (!current_user_can('manage_sibel')) {
            return;
        }
        if ($column === 'sibel_status') {
            echo esc_html(
                ['new' => 'Новая', 'processing' => 'В работе', 'done' => 'Обработана'][
                    get_post_meta($id, '_sibel_status', true)
                ] ?? 'Новая',
            );
        } elseif (in_array($column, ['sibel_phone', 'sibel_company', 'sibel_email'], true)) {
            $value = get_post_meta($id, '_' . $column, true);
            echo $column === 'sibel_company'
                ? esc_html($value)
                : sibel_contact_link($value, $column === 'sibel_email' ? 'email' : 'phone');
        }
    },
    10,
    2,
);

add_filter(
    'post_row_actions',
    function ($actions, $post) {
        if ($post->post_type === 'sibel_lead') {
            unset($actions['inline hide-if-no-js']);
        }
        return $actions;
    },
    10,
    2,
);
