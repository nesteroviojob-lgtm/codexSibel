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
    return 'https://yandex.ru/maps/?text=' . rawurlencode(sibel_map_address());
}

function sibel_map_embed_url(): string
{
    return 'https://yandex.ru/map-widget/v1/?' .
        http_build_query(
            ['mode' => 'search', 'text' => sibel_map_address(), 'z' => '17', 'lang' => 'ru_RU'],
            '',
            '&',
            PHP_QUERY_RFC3986,
        );
}

function sibel_yandex_map(): void
{
    ?>
    <iframe class="yandex-map" src="<?php echo esc_url(sibel_map_embed_url()); ?>" title="<?php echo esc_attr(
    'Яндекс Карты: ' . sibel_map_address(),
); ?>" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
    <?php
}
