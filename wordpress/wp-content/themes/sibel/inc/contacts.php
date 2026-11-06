<?php
defined('ABSPATH') || exit();

function sibel_phone_href(string $phone): string
{
    $digits = preg_replace('/\D/', '', $phone);
    if (str_starts_with(trim($phone), '+')) {
        return 'tel:+' . $digits;
    }
    if (strlen($digits) === 11 && $digits[0] === '8') {
        $digits = '7' . substr($digits, 1);
    } elseif (strlen($digits) === 10) {
        $digits = '7' . $digits;
    } elseif (strlen($digits) === 6) {
        // The second Tomsk number is displayed locally; dial it with the main number's area code.
        $main = preg_replace('/\D/', '', sibel_setting('phone'));
        if (strlen($main) === 11 && in_array($main[0], ['7', '8'], true)) {
            $digits = '7' . substr($main, 1, 4) . $digits;
        }
    }
    return 'tel:' . (strlen($digits) === 11 && $digits[0] === '7' ? '+' : '') . $digits;
}

function sibel_map_address(): string
{
    return trim(preg_replace('/\s+/u', ' ', sibel_setting('address')));
}

function sibel_contact_link(string $value, string $type = 'phone'): string
{
    if (!trim($value)) {
        return '—';
    }
    $href = $type === 'email' ? 'mailto:' . sanitize_email($value) : sibel_phone_href($value);
    return '<a href="' . esc_url($href) . '">' . esc_html($value) . '</a>';
}

function sibel_map_url(): string
{
    $configured = sibel_setting('map_link');
    if ($configured && $configured !== sibel_defaults()['map_link']) {
        $parts = wp_parse_url($configured);
        if (
            ($parts['scheme'] ?? '') === 'https' &&
            in_array($parts['host'] ?? '', ['yandex.ru', 'www.yandex.ru'], true) &&
            str_starts_with($parts['path'] ?? '', '/maps')
        ) {
            return $configured;
        }
    }
    return sibel_defaults()['map_link'];
}

function sibel_upgrade_organization_map(): void
{
    if (get_option('sibel_organization_map_version') === '1') {
        return;
    }
    $settings = get_option('sibel_settings', null);
    if (is_array($settings)) {
        add_option(
            'sibel_map_before_organization_update',
            ['value' => $settings['map_link'] ?? null],
            '',
            false,
        );
        $settings['map_link'] = sibel_defaults()['map_link'];
        update_option('sibel_settings', $settings);
    }
    update_option('sibel_organization_map_version', '1');
}
add_action('init', 'sibel_upgrade_organization_map', 35);

function sibel_map_embed_url(): string
{
    $parts = wp_parse_url(sibel_map_url());
    if (preg_match('#^/maps/-/([a-zA-Z0-9_-]+)/?$#', $parts['path'] ?? '', $match)) {
        return 'https://yandex.ru/map-widget/v1/-/' . $match[1] . '?lang=ru_RU';
    }
    parse_str($parts['query'] ?? '', $query);
    $organization = null;
    if (preg_match('#/maps/org/(?:[^/]+/)?([0-9]+)(?:/|$)#', $parts['path'] ?? '', $match)) {
        $organization = $match[1];
    } elseif (isset($query['oid']) && is_string($query['oid']) && ctype_digit($query['oid'])) {
        $organization = $query['oid'];
    }
    $params = $organization
        ? ['mode' => 'search', 'ol' => 'biz', 'oid' => $organization, 'z' => '17', 'lang' => 'ru_RU']
        : [
            'mode' => 'search',
            'text' => is_string($query['text'] ?? null) ? $query['text'] : 'Сибел ' . sibel_map_address(),
            'z' => '17',
            'lang' => 'ru_RU',
        ];
    return 'https://yandex.ru/map-widget/v1/?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

function sibel_yandex_map(): void
{
    ?>
    <iframe class="yandex-map" src="<?php echo esc_url(sibel_map_embed_url()); ?>" title="<?php echo esc_attr(
    'Яндекс Карты: Сибел — ' . sibel_map_address(),
); ?>" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
    <?php
}
