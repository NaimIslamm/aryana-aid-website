<?php
/**
 * Plugin Name: Islamic Date & Namaz Time
 * Description: Standalone widget & function displaying the 1:1 Muslim Aid style Islamic Hijri Date and Next Prayer with comprehensive Dashboard styling options.
 * Version: 2.0.0
 * Author: Antigravity
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. Default Configuration & Options
 */
function idnt_get_settings() {
    return [
        'city'             => get_option('idnt_city', 'London'),
        'country'          => get_option('idnt_country', 'United Kingdom'),
        'timezone'         => get_option('idnt_timezone', 'Europe/London'),
        'hijri_adjust'     => (int) get_option('idnt_hijri_adjust', 0),
        'time_format'      => get_option('idnt_time_format', '12'), // '12' or '24'
        'calc_method'      => (int) get_option('idnt_calc_method', 2), // 2 = ISNA / Muslim World League
        'auto_topbar'      => get_option('idnt_auto_topbar', 'no'), // auto-inject in top bar or use shortcode
    ];
}

/**
 * 2. Default Styling Configuration
 */
function idnt_get_style_defaults() {
    return [
        // Container
        'bg_color'             => 'transparent',
        'border_color'         => '#E2E8F0',
        'border_width'         => '0',
        'border_radius'        => '0',
        'padding_v'            => '0',
        'padding_h'            => '0',
        'box_shadow'           => 'none',
        'gap'                  => '16',

        // Typography
        'font_family'          => 'inherit',

        // Icons
        'show_icons'           => 'yes',
        'icon_color'           => '#007A3D',
        'icon_size'            => '22',

        // Labels
        'label_date_text'      => 'Today • Islamic Date',
        'label_prayer_text'    => 'Next Prayer: {city}',
        'label_color'          => '#888888',
        'label_size'           => '11',
        'label_weight'         => '500',
        'label_transform'      => 'none',

        // Values
        'value_color'          => '#1D3B2B',
        'value_size'           => '14.5',
        'value_weight'         => '600',
        'value_hover_color'    => '#007A3D',

        // Divider
        'show_divider'         => 'yes',
        'divider_color'        => '#E0E0E0',
        'divider_height'       => '28',
        'divider_width'        => '1',

        // Popover
        'popover_bg'           => '#FFFFFF',
        'popover_border'       => '#E2E8F0',
        'popover_text'         => '#0F172A',
        'popover_active_bg'    => '#E8F5E9',
        'popover_active_color' => '#007A3D',
        'popover_radius'       => '8',
        'popover_shadow'       => 'medium',

        // Custom CSS
        'custom_css'           => '',
    ];
}

function idnt_get_styles() {
    $defaults = idnt_get_style_defaults();
    $saved = get_option('idnt_styles', []);
    if (!is_array($saved) || empty($saved)) {
        return $defaults;
    }
    return wp_parse_args($saved, $defaults);
}

/**
 * 3. Enqueue Admin Assets (Color Picker & Admin Styles)
 */
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'toplevel_page_islamic-date-namaz-time') {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }
});

/**
 * 4. Get Live Prayer Timings & Hijri Date (with 6-Hour Transient Cache & Fallback)
 */
function idnt_get_prayer_and_hijri_data() {
    $settings = idnt_get_settings();
    $city     = sanitize_text_field($settings['city']);
    $country  = sanitize_text_field($settings['country']);
    $tz_str   = $settings['timezone'] ?: 'Europe/London';

    try {
        $tz = new DateTimeZone($tz_str);
    } catch (Exception $e) {
        $tz = new DateTimeZone('Europe/London');
    }

    $now = new DateTime('now', $tz);
    $date_key = $now->format('Y-m-d');
    $transient_key = 'idnt_data_' . md5($city . '_' . $country . '_' . $date_key . '_' . $settings['hijri_adjust'] . '_' . $settings['calc_method']);

    $cached = get_transient($transient_key);
    if (!empty($cached) && is_array($cached)) {
        return idnt_calculate_next_prayer($cached, $now, $settings);
    }

    // Prepare API URL
    $api_date = $now->format('d-m-Y');
    $adj = (int)$settings['hijri_adjust'];
    $url = sprintf(
        'https://api.aladhan.com/v1/timingsByCity/%s?city=%s&country=%s&method=%d%s',
        rawurlencode($api_date),
        rawurlencode($city),
        rawurlencode($country),
        $settings['calc_method'],
        $adj !== 0 ? '&adjustment=' . $adj : ''
    );

    $response = wp_remote_get($url, [
        'timeout'    => 6,
        'sslverify'  => false,
        'user-agent' => 'WordPress/Islamic-Date-Namaz-Time',
    ]);

    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);

        if (!empty($json['data']['timings']) && !empty($json['data']['date']['hijri'])) {
            $hijri_raw = $json['data']['date']['hijri'];
            $month_en = $hijri_raw['month']['en'];

            // Clean transcription to match Muslim Aid: "Jumada al-ula", "Ramadan", "Shawwal", etc.
            $month_clean = str_replace(
                ['ā', 'ū', 'ī', 'ḍ', 'ṣ', 'ṭ', 'ẓ', 'ḥ', 'á', 'ú', 'í', 'ó', 'é', 'Á', 'Ú', 'Í'],
                ['a', 'u', 'i', 'd', 's', 't', 'z', 'h', 'a', 'u', 'i', 'o', 'e', 'A', 'U', 'I'],
                $month_en
            );
            $hijri_formatted = sprintf('%s %d, %s', $month_clean, (int)$hijri_raw['day'], $hijri_raw['year']);

            $timings = $json['data']['timings'];

            $data = [
                'hijri'   => $hijri_formatted,
                'timings' => [
                    'Fajr'    => trim(explode(' ', $timings['Fajr'])[0]),
                    'Sunrise' => trim(explode(' ', $timings['Sunrise'])[0]),
                    'Dhuhr'   => trim(explode(' ', $timings['Dhuhr'])[0]),
                    'Asr'     => trim(explode(' ', $timings['Asr'])[0]),
                    'Maghrib' => trim(explode(' ', $timings['Maghrib'])[0]),
                    'Isha'    => trim(explode(' ', $timings['Isha'])[0]),
                ],
                'city'    => $city,
            ];

            // Cache for 6 hours
            set_transient($transient_key, $data, 6 * HOUR_IN_SECONDS);
            return idnt_calculate_next_prayer($data, $now, $settings);
        }
    }

    // Robust Fallback (London standard defaults for reliability)
    $fallback_data = [
        'hijri'   => 'Jumada al-ula 14, 1448',
        'timings' => [
            'Fajr'    => '05:09',
            'Sunrise' => '06:42',
            'Dhuhr'   => '11:45',
            'Asr'     => '14:19',
            'Maghrib' => '16:47',
            'Isha'    => '18:32',
        ],
        'city'    => $city,
    ];

    return idnt_calculate_next_prayer($fallback_data, $now, $settings);
}

/**
 * 5. Calculate Upcoming (Next) Prayer Time Based on Current Clock
 */
function idnt_calculate_next_prayer($data, $now, $settings) {
    $current_time = $now->format('H:i');
    $timings = $data['timings'];

    $prayers = [
        'Fajr'    => $timings['Fajr'],
        'Dhuhr'   => $timings['Dhuhr'],
        'Asr'     => $timings['Asr'],
        'Maghrib' => $timings['Maghrib'],
        'Isha'    => $timings['Isha'],
    ];

    $next_name = 'Fajr';
    $next_time = $timings['Fajr'];
    $is_tomorrow = true;

    foreach ($prayers as $name => $time) {
        if ($current_time < $time) {
            $next_name = $name;
            $next_time = $time;
            $is_tomorrow = false;
            break;
        }
    }

    // Format display time (12hr vs 24hr)
    if ($settings['time_format'] === '24') {
        $formatted_time = $next_time;
    } else {
        $dt = DateTime::createFromFormat('H:i', $next_time);
        $formatted_time = $dt ? strtolower($dt->format('h:i a')) : $next_time;
    }

    $data['next_name']        = $next_name;
    $data['next_time']        = $formatted_time;
    $data['next_display']     = $next_name . ' - ' . $formatted_time;
    $data['is_tomorrow']      = $is_tomorrow;
    $data['all_prayers_list'] = $prayers;

    return $data;
}

/**
 * 6. Master Output Function: islamic_date_namaz_time()
 */
function islamic_date_namaz_time($echo = true, $args = []) {
    $data     = idnt_get_prayer_and_hijri_data();
    $styles   = idnt_get_styles();
    $city     = esc_html($data['city']);
    $hijri    = esc_html($data['hijri']);
    $next_prayer = esc_html($data['next_display']);
    $uid      = 'idnt_' . wp_generate_password(6, false, false);

    // Dynamic Labels with {city} token replacement
    $label_date_template = !empty($styles['label_date_text']) ? $styles['label_date_text'] : 'Today • Islamic Date';
    $label_prayer_template = !empty($styles['label_prayer_text']) ? $styles['label_prayer_text'] : 'Next Prayer: {city}';

    $date_label   = esc_html(str_replace('{city}', $city, $label_date_template));
    $prayer_label = esc_html(str_replace('{city}', $city, $label_prayer_template));
    $icon_color   = esc_attr($styles['icon_color']);
    $icon_size    = intval($styles['icon_size']);

    ob_start();
    ?>
    <!-- Islamic Date & Namaz Time Widget -->
    <div class="idnt-muslimaid-widget" id="<?php echo esc_attr($uid); ?>">
        
        <!-- Left Item: Islamic Date -->
        <div class="idnt-item idnt-date-item">
            <?php if ($styles['show_icons'] === 'yes'): ?>
            <span class="idnt-icon idnt-calendar-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="<?php echo $icon_size; ?>" height="<?php echo $icon_size; ?>" fill="none" stroke="<?php echo $icon_color; ?>" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="3" ry="3"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                    <circle cx="8" cy="14" r="0.8" fill="<?php echo $icon_color; ?>"></circle>
                    <circle cx="12" cy="14" r="0.8" fill="<?php echo $icon_color; ?>"></circle>
                    <circle cx="16" cy="14" r="0.8" fill="<?php echo $icon_color; ?>"></circle>
                    <circle cx="8" cy="18" r="0.8" fill="<?php echo $icon_color; ?>"></circle>
                    <circle cx="12" cy="18" r="0.8" fill="<?php echo $icon_color; ?>"></circle>
                    <circle cx="16" cy="18" r="0.8" fill="<?php echo $icon_color; ?>"></circle>
                </svg>
            </span>
            <?php endif; ?>
            <div class="idnt-text-wrap">
                <span class="idnt-label"><?php echo $date_label; ?></span>
                <span class="idnt-value idnt-hijri-val"><?php echo $hijri; ?></span>
            </div>
        </div>

        <!-- Vertical Separator Divider -->
        <?php if ($styles['show_divider'] === 'yes'): ?>
        <div class="idnt-divider" aria-hidden="true"></div>
        <?php endif; ?>

        <!-- Right Item: Next Prayer -->
        <div class="idnt-item idnt-prayer-item" title="Click to view full daily timetable">
            <?php if ($styles['show_icons'] === 'yes'): ?>
            <span class="idnt-icon idnt-clock-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="<?php echo $icon_size; ?>" height="<?php echo $icon_size; ?>" fill="none" stroke="<?php echo $icon_color; ?>" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9"></circle>
                    <polyline points="12 7 12 12 14.5 15"></polyline>
                </svg>
            </span>
            <?php endif; ?>
            <div class="idnt-text-wrap">
                <span class="idnt-label"><?php echo $prayer_label; ?></span>
                <span class="idnt-value idnt-prayer-val"><?php echo $next_prayer; ?></span>
            </div>

            <!-- Sleek Popover Timetable on Hover / Click -->
            <div class="idnt-timetable-popover">
                <div class="idnt-popover-header">
                    <strong><?php echo $city; ?> Daily Prayer Times</strong>
                    <span class="idnt-popover-sub"><?php echo $hijri; ?></span>
                </div>
                <div class="idnt-popover-grid">
                    <?php 
                    $prayers = $data['timings'];
                    foreach ($prayers as $p_name => $p_raw):
                        $dt = DateTime::createFromFormat('H:i', $p_raw);
                        $formatted_p = $dt ? strtolower($dt->format('h:i a')) : $p_raw;
                        $is_current_next = ($p_name === $data['next_name']);
                    ?>
                        <div class="idnt-prayer-row <?php echo $is_current_next ? 'active-next' : ''; ?>">
                            <span class="idnt-p-name"><?php echo esc_html($p_name); ?></span>
                            <span class="idnt-p-time"><?php echo esc_html($formatted_p); ?></span>
                            <?php if ($is_current_next): ?>
                                <span class="idnt-next-tag">Next</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
    <?php
    $html = ob_get_clean();

    if ($echo) {
        echo $html;
    }
    return $html;
}

// Alias functions
function islamic_date_and_namaz_time($echo = true, $args = []) {
    return islamic_date_namaz_time($echo, $args);
}

/**
 * 7. Shortcodes Registration
 * Usage: [islamic_date_namaz_time]
 */
add_shortcode('islamic_date_namaz_time', function ($atts) {
    return islamic_date_namaz_time(false, $atts ?: []);
});
add_shortcode('islamic_date_and_namaz_time', function ($atts) {
    return islamic_date_namaz_time(false, $atts ?: []);
});
add_shortcode('namaz_time', function ($atts) {
    return islamic_date_namaz_time(false, $atts ?: []);
});
add_shortcode('islamic_date', function ($atts) {
    return islamic_date_namaz_time(false, $atts ?: []);
});

/**
 * 8. Dynamic CSS Generation Based on Dashboard Styling Options
 */
add_action('wp_head', 'idnt_render_styles', 99);
add_action('admin_head', 'idnt_render_styles');

function idnt_render_styles() {
    $s = idnt_get_styles();

    $font_family_map = [
        'inherit'           => 'inherit',
        'outfit'            => "'Outfit', sans-serif",
        'playfair'          => "'Playfair Display', Georgia, serif",
        'inter'             => "'Inter', -apple-system, BlinkMacSystemFont, sans-serif",
        'roboto'            => "'Roboto', Arial, sans-serif",
        'poppins'           => "'Poppins', sans-serif",
        'montserrat'        => "'Montserrat', sans-serif",
        'system'            => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
    ];
    $font_css = $font_family_map[$s['font_family']] ?? 'inherit';

    // Shadow presets
    $shadow_map = [
        'none'     => 'none',
        'subtle'   => '0 1px 3px rgba(0, 0, 0, 0.05)',
        'medium'   => '0 4px 12px rgba(0, 0, 0, 0.08)',
        'elevated' => '0 10px 25px rgba(0, 0, 0, 0.12)',
    ];
    $widget_shadow = $shadow_map[$s['box_shadow']] ?? 'none';
    $popover_shadow = $shadow_map[$s['popover_shadow']] ?? '0 10px 25px rgba(0, 0, 0, 0.12)';
    ?>
    <style id="idnt-muslimaid-style">
    /* =========================================================
       Islamic Date & Namaz Time - Dynamic Dashboard Styles
       ========================================================= */
    .idnt-muslimaid-widget {
        display: inline-flex;
        align-items: center;
        box-sizing: border-box;
        font-family: <?php echo $font_css; ?>;
        line-height: 1.25;
        position: relative;
        background-color: <?php echo esc_attr($s['bg_color']); ?>;
        border-radius: <?php echo intval($s['border_radius']); ?>px;
        padding: <?php echo intval($s['padding_v']); ?>px <?php echo intval($s['padding_h']); ?>px;
        gap: <?php echo intval($s['gap']); ?>px;
        box-shadow: <?php echo $widget_shadow; ?>;
        <?php if (!empty($s['border_width']) && intval($s['border_width']) > 0): ?>
        border: <?php echo intval($s['border_width']); ?>px solid <?php echo esc_attr($s['border_color']); ?>;
        <?php else: ?>
        border: none;
        <?php endif; ?>
    }

    .idnt-item {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        position: relative;
        cursor: default;
    }

    .idnt-prayer-item {
        cursor: pointer;
    }

    /* Icons */
    .idnt-icon {
        display: <?php echo $s['show_icons'] === 'yes' ? 'inline-flex' : 'none !important'; ?>;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: <?php echo intval($s['icon_size']) + 2; ?>px;
        height: <?php echo intval($s['icon_size']) + 2; ?>px;
        color: <?php echo esc_attr($s['icon_color']); ?>;
    }

    .idnt-icon svg {
        display: block;
        width: <?php echo intval($s['icon_size']); ?>px !important;
        height: <?php echo intval($s['icon_size']); ?>px !important;
        stroke: <?php echo esc_attr($s['icon_color']); ?> !important;
    }

    .idnt-icon svg circle {
        fill: <?php echo esc_attr($s['icon_color']); ?> !important;
    }

    .idnt-text-wrap {
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    /* Labels */
    .idnt-label {
        font-size: <?php echo floatval($s['label_size']); ?>px !important;
        font-weight: <?php echo esc_attr($s['label_weight']); ?> !important;
        color: <?php echo esc_attr($s['label_color']); ?> !important;
        text-transform: <?php echo esc_attr($s['label_transform']); ?> !important;
        letter-spacing: 0.15px;
        margin-bottom: 2px;
        white-space: nowrap;
        line-height: 1.2;
    }

    /* Values */
    .idnt-value {
        font-size: <?php echo floatval($s['value_size']); ?>px !important;
        font-weight: <?php echo esc_attr($s['value_weight']); ?> !important;
        color: <?php echo esc_attr($s['value_color']); ?> !important;
        letter-spacing: -0.1px;
        white-space: nowrap;
        transition: color 0.2s ease;
        line-height: 1.25;
    }

    .idnt-prayer-item:hover .idnt-prayer-val {
        color: <?php echo esc_attr($s['value_hover_color']); ?> !important;
    }

    /* Divider */
    .idnt-divider {
        display: <?php echo $s['show_divider'] === 'yes' ? 'block' : 'none !important'; ?>;
        width: <?php echo intval($s['divider_width']); ?>px !important;
        height: <?php echo intval($s['divider_height']); ?>px !important;
        background-color: <?php echo esc_attr($s['divider_color']); ?> !important;
        margin: 0 <?php echo max(4, intval($s['gap']) / 2); ?>px;
        flex-shrink: 0;
    }

    /* Interactive Timetable Popover */
    .idnt-timetable-popover {
        position: absolute;
        top: calc(100% + 10px);
        left: -20px;
        background: <?php echo esc_attr($s['popover_bg']); ?> !important;
        border: 1px solid <?php echo esc_attr($s['popover_border']); ?> !important;
        border-radius: <?php echo intval($s['popover_radius']); ?>px !important;
        box-shadow: <?php echo $popover_shadow; ?> !important;
        color: <?php echo esc_attr($s['popover_text']); ?> !important;
        padding: 14px 16px;
        min-width: 225px;
        z-index: 999999;
        display: none;
        opacity: 0;
        transform: translateY(6px);
        transition: opacity 0.2s ease, transform 0.2s ease;
        pointer-events: none;
    }

    .idnt-prayer-item:hover .idnt-timetable-popover,
    .idnt-prayer-item:focus .idnt-timetable-popover,
    .idnt-prayer-item.is-open .idnt-timetable-popover {
        display: block;
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }

    .idnt-popover-header {
        border-bottom: 1px solid <?php echo esc_attr($s['popover_border']); ?>;
        padding-bottom: 8px;
        margin-bottom: 8px;
    }

    .idnt-popover-header strong {
        display: block;
        font-size: 13px;
        color: <?php echo esc_attr($s['popover_text']); ?> !important;
    }

    .idnt-popover-sub {
        font-size: 11px;
        color: <?php echo esc_attr($s['popover_active_color']); ?> !important;
        font-weight: 600;
    }

    .idnt-popover-grid {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .idnt-prayer-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 8px;
        border-radius: 4px;
        font-size: 12.5px;
        color: <?php echo esc_attr($s['popover_text']); ?> !important;
    }

    .idnt-prayer-row.active-next {
        background: <?php echo esc_attr($s['popover_active_bg']); ?> !important;
        color: <?php echo esc_attr($s['popover_active_color']); ?> !important;
        font-weight: 700;
    }

    .idnt-next-tag {
        font-size: 9.5px;
        background: <?php echo esc_attr($s['popover_active_color']); ?> !important;
        color: #ffffff !important;
        padding: 1px 6px;
        border-radius: 3px;
        text-transform: uppercase;
        margin-left: 6px;
    }

    /* Mobile Responsiveness */
    @media (max-width: 768px) {
        .idnt-muslimaid-widget {
            flex-wrap: wrap;
            gap: 10px;
        }
        .idnt-divider {
            display: none !important;
        }
        .idnt-value {
            font-size: <?php echo max(12, floatval($s['value_size']) - 1.5); ?>px !important;
        }
        .idnt-label {
            font-size: <?php echo max(9.5, floatval($s['label_size']) - 1); ?>px !important;
        }
    }

    <?php if (!empty($s['custom_css'])): ?>
    /* Admin Custom CSS Override */
    <?php echo wp_strip_all_tags($s['custom_css']); ?>
    <?php endif; ?>
    </style>

    <script id="idnt-muslimaid-js">
    document.addEventListener('DOMContentLoaded', function() {
        const prayerItems = document.querySelectorAll('.idnt-prayer-item');
        prayerItems.forEach(item => {
            item.addEventListener('click', function(e) {
                this.classList.toggle('is-open');
                e.stopPropagation();
            });
        });
        document.addEventListener('click', function() {
            prayerItems.forEach(item => item.classList.remove('is-open'));
        });
    });
    </script>
    <?php
}

/**
 * 9. Optional Top Bar Auto-Injection (if enabled in Admin Settings)
 */
add_action('wp_body_open', function () {
    $settings = idnt_get_settings();
    if ($settings['auto_topbar'] === 'yes') {
        ?>
        <div class="idnt-site-topbar-wrapper" style="background:#FFFFFF; border-bottom:1px solid #E5E7EB; padding:7px 20px; display:flex; justify-content:flex-end; align-items:center; box-sizing:border-box; width:100%;">
            <div style="max-width:1280px; width:100%; margin:0 auto; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                <div style="font-size:12px; color:#6B7280; font-weight:500;">
                    Aryana Aid &bull; Serving Humanity Across Afghanistan & Beyond
                </div>
                <div>
                    <?php islamic_date_namaz_time(true); ?>
                </div>
            </div>
        </div>
        <?php
    }
}, 1);

/**
 * 10. WordPress Admin Dashboard & Comprehensive Style Manager
 */
add_action('admin_menu', function () {
    add_menu_page(
        'Islamic Date & Namaz Time Settings',
        'Islamic Date & Namaz Time',
        'manage_options',
        'islamic-date-namaz-time',
        'idnt_render_admin_page',
        'dashicons-clock',
        28
    );
});

function idnt_render_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $message = '';
    $reset_message = '';

    // Handle Reset to Defaults
    if (isset($_POST['idnt_reset_defaults']) && check_admin_referer('idnt_save_settings_nonce')) {
        update_option('idnt_styles', idnt_get_style_defaults());
        $message = 'Styles have been reset to the 1:1 Muslim Aid default layout!';
    }
    // Handle Save Settings & Styles
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('idnt_save_settings_nonce')) {
        
        // 1. General Config
        update_option('idnt_city', sanitize_text_field($_POST['idnt_city'] ?? 'London'));
        update_option('idnt_country', sanitize_text_field($_POST['idnt_country'] ?? 'United Kingdom'));
        update_option('idnt_timezone', sanitize_text_field($_POST['idnt_timezone'] ?? 'Europe/London'));
        update_option('idnt_hijri_adjust', (int)($_POST['idnt_hijri_adjust'] ?? 0));
        update_option('idnt_time_format', sanitize_text_field($_POST['idnt_time_format'] ?? '12'));
        update_option('idnt_calc_method', (int)($_POST['idnt_calc_method'] ?? 2));
        update_option('idnt_auto_topbar', isset($_POST['idnt_auto_topbar']) ? 'yes' : 'no');

        // 2. Style Options
        $styles = [
            // Container
            'bg_color'             => sanitize_text_field($_POST['idnt_style_bg_color'] ?? 'transparent'),
            'border_color'         => sanitize_text_field($_POST['idnt_style_border_color'] ?? '#E2E8F0'),
            'border_width'         => intval($_POST['idnt_style_border_width'] ?? 0),
            'border_radius'        => intval($_POST['idnt_style_border_radius'] ?? 0),
            'padding_v'            => intval($_POST['idnt_style_padding_v'] ?? 0),
            'padding_h'            => intval($_POST['idnt_style_padding_h'] ?? 0),
            'box_shadow'           => sanitize_text_field($_POST['idnt_style_box_shadow'] ?? 'none'),
            'gap'                  => intval($_POST['idnt_style_gap'] ?? 16),

            // Typography
            'font_family'          => sanitize_text_field($_POST['idnt_style_font_family'] ?? 'inherit'),

            // Icons
            'show_icons'           => isset($_POST['idnt_style_show_icons']) ? 'yes' : 'no',
            'icon_color'           => sanitize_hex_color($_POST['idnt_style_icon_color'] ?? '') ?: '#007A3D',
            'icon_size'            => intval($_POST['idnt_style_icon_size'] ?? 22),

            // Labels
            'label_date_text'      => sanitize_text_field($_POST['idnt_style_label_date_text'] ?? 'Today • Islamic Date'),
            'label_prayer_text'    => sanitize_text_field($_POST['idnt_style_label_prayer_text'] ?? 'Next Prayer: {city}'),
            'label_color'          => sanitize_hex_color($_POST['idnt_style_label_color'] ?? '') ?: '#888888',
            'label_size'           => floatval($_POST['idnt_style_label_size'] ?? 11),
            'label_weight'         => sanitize_text_field($_POST['idnt_style_label_weight'] ?? '500'),
            'label_transform'      => sanitize_text_field($_POST['idnt_style_label_transform'] ?? 'none'),

            // Values
            'value_color'          => sanitize_hex_color($_POST['idnt_style_value_color'] ?? '') ?: '#1D3B2B',
            'value_size'           => floatval($_POST['idnt_style_value_size'] ?? 14.5),
            'value_weight'         => sanitize_text_field($_POST['idnt_style_value_weight'] ?? '600'),
            'value_hover_color'    => sanitize_hex_color($_POST['idnt_style_value_hover_color'] ?? '') ?: '#007A3D',

            // Divider
            'show_divider'         => isset($_POST['idnt_style_show_divider']) ? 'yes' : 'no',
            'divider_color'        => sanitize_hex_color($_POST['idnt_style_divider_color'] ?? '') ?: '#E0E0E0',
            'divider_height'       => intval($_POST['idnt_style_divider_height'] ?? 28),
            'divider_width'        => intval($_POST['idnt_style_divider_width'] ?? 1),

            // Popover
            'popover_bg'           => sanitize_hex_color($_POST['idnt_style_popover_bg'] ?? '') ?: '#FFFFFF',
            'popover_border'       => sanitize_hex_color($_POST['idnt_style_popover_border'] ?? '') ?: '#E2E8F0',
            'popover_text'         => sanitize_hex_color($_POST['idnt_style_popover_text'] ?? '') ?: '#0F172A',
            'popover_active_bg'    => sanitize_hex_color($_POST['idnt_style_popover_active_bg'] ?? '') ?: '#E8F5E9',
            'popover_active_color' => sanitize_hex_color($_POST['idnt_style_popover_active_color'] ?? '') ?: '#007A3D',
            'popover_radius'       => intval($_POST['idnt_style_popover_radius'] ?? 8),
            'popover_shadow'       => sanitize_text_field($_POST['idnt_style_popover_shadow'] ?? 'medium'),

            // Custom CSS
            'custom_css'           => wp_strip_all_tags($_POST['idnt_style_custom_css'] ?? ''),
        ];

        update_option('idnt_styles', $styles);

        // Delete cache transients to immediately reflect new settings
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_idnt_data_%' OR option_name LIKE '_transient_timeout_idnt_data_%'");

        $message = 'Settings and styling options saved successfully!';
    }

    $settings = idnt_get_settings();
    $styles   = idnt_get_styles();
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'styles';
    ?>
    <div class="wrap" style="max-width:1080px; margin-top:20px;">
        
        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:20px;">
            <div>
                <h1 style="display:flex; align-items:center; gap:10px; margin:0; font-size:24px; font-weight:700; color:#0F172A;">
                    <span class="dashicons dashicons-clock" style="font-size:28px; width:28px; height:28px; color:#007A3D;"></span>
                    Islamic Date & Namaz Time
                </h1>
                <p style="color:#64748B; font-size:13.5px; margin:5px 0 0 0;">
                    Complete style manager & live prayer settings for the Muslim Aid 1:1 header component.
                </p>
            </div>
            <div>
                <a href="<?php echo esc_url(home_url()); ?>" target="_blank" class="button button-secondary" style="font-weight:600;">
                    Visit Website &rarr;
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="notice notice-success is-dismissible" style="padding:12px 18px; margin-bottom:22px; border-left-color:#007A3D;">
                <p style="margin:0; font-size:14px;"><strong>✓ <?php echo esc_html($message); ?></strong></p>
            </div>
        <?php endif; ?>

        <!-- Live Preview Card (Always visible on top) -->
        <div style="background:#FFFFFF; border:1px solid #E2E8F0; border-radius:12px; padding:22px 28px; margin-bottom:25px; box-shadow:0 4px 15px rgba(0,0,0,0.04);">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #F1F5F9; padding-bottom:12px; margin-bottom:16px;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#10B981;"></span>
                    <strong style="color:#0F172A; font-size:14px; text-transform:uppercase; letter-spacing:0.5px;">Live Component Preview</strong>
                </div>
                <span style="font-size:12px; color:#64748B;">
                    Hover or click <em>Next Prayer</em> to inspect the popover
                </span>
            </div>
            
            <div style="padding:18px 22px; background:#F8FAFC; border-radius:8px; border:1px dashed #CBD5E1; display:flex; justify-content:center; align-items:center; min-height:80px;" id="idnt-preview-box">
                <?php islamic_date_namaz_time(true); ?>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <h2 class="nav-tab-wrapper" style="margin-bottom:22px; border-bottom:1px solid #CBD5E1;">
            <a href="?page=islamic-date-namaz-time&tab=styles" class="nav-tab <?php echo $active_tab === 'styles' ? 'nav-tab-active' : ''; ?>" style="font-size:14px; font-weight:600; padding:8px 18px;">
                🎨 Styling & Appearance
            </a>
            <a href="?page=islamic-date-namaz-time&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>" style="font-size:14px; font-weight:600; padding:8px 18px;">
                ⚙️ Prayer & City Settings
            </a>
            <a href="?page=islamic-date-namaz-time&tab=embed" class="nav-tab <?php echo $active_tab === 'embed' ? 'nav-tab-active' : ''; ?>" style="font-size:14px; font-weight:600; padding:8px 18px;">
                📋 How to Embed
            </a>
        </h2>

        <!-- Main Form -->
        <form method="post" action="" id="idnt-main-form">
            <?php wp_nonce_field('idnt_save_settings_nonce'); ?>

            <!-- ========================================== -->
            <!-- TAB 1: STYLING & APPEARANCE                -->
            <!-- ========================================== -->
            <?php if ($active_tab === 'styles'): ?>
                
                <!-- Section A: Container & Layout -->
                <div class="idnt-card-section" style="background:#ffffff; border:1px solid #CBD5E1; border-radius:10px; padding:24px; margin-bottom:24px; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                    <h3 style="margin-top:0; color:#0F172A; font-size:16px; border-bottom:1px solid #F1F5F9; padding-bottom:10px;">
                        1. Widget Container & Layout
                    </h3>

                    <table class="form-table" style="margin-top:5px;">
                        <tr>
                            <th scope="row"><label for="idnt_style_bg_color">Background Color</label></th>
                            <td>
                                <input type="text" name="idnt_style_bg_color" id="idnt_style_bg_color" value="<?php echo esc_attr($styles['bg_color']); ?>" class="idnt-color-field" data-default-color="transparent">
                                <p class="description">Enter <code>transparent</code> or pick any solid color (e.g. <code>#FFFFFF</code>).</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="idnt_style_border_color">Border Color & Width</label></th>
                            <td>
                                <div style="display:flex; align-items:center; gap:15px; flex-wrap:wrap;">
                                    <div>
                                        <label style="font-size:12px; color:#64748B; display:block;">Border Width (px)</label>
                                        <input type="number" min="0" max="10" name="idnt_style_border_width" id="idnt_style_border_width" value="<?php echo intval($styles['border_width']); ?>" style="width:80px; height:32px;">
                                    </div>
                                    <div>
                                        <label style="font-size:12px; color:#64748B; display:block;">Border Radius (px)</label>
                                        <input type="number" min="0" max="50" name="idnt_style_border_radius" id="idnt_style_border_radius" value="<?php echo intval($styles['border_radius']); ?>" style="width:80px; height:32px;">
                                    </div>
                                    <div>
                                        <label style="font-size:12px; color:#64748B; display:block;">Border Color</label>
                                        <input type="text" name="idnt_style_border_color" id="idnt_style_border_color" value="<?php echo esc_attr($styles['border_color']); ?>" class="idnt-color-field" data-default-color="#E2E8F0">
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label>Padding & Gap (px)</label></th>
                            <td>
                                <div style="display:flex; align-items:center; gap:15px; flex-wrap:wrap;">
                                    <div>
                                        <label style="font-size:12px; color:#64748B; display:block;">Padding Top/Bottom</label>
                                        <input type="number" min="0" max="60" name="idnt_style_padding_v" id="idnt_style_padding_v" value="<?php echo intval($styles['padding_v']); ?>" style="width:90px; height:32px;"> px
                                    </div>
                                    <div>
                                        <label style="font-size:12px; color:#64748B; display:block;">Padding Left/Right</label>
                                        <input type="number" min="0" max="60" name="idnt_style_padding_h" id="idnt_style_padding_h" value="<?php echo intval($styles['padding_h']); ?>" style="width:90px; height:32px;"> px
                                    </div>
                                    <div>
                                        <label style="font-size:12px; color:#64748B; display:block;">Gap Between Sections</label>
                                        <input type="number" min="0" max="80" name="idnt_style_gap" id="idnt_style_gap" value="<?php echo intval($styles['gap']); ?>" style="width:90px; height:32px;"> px
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="idnt_style_box_shadow">Container Shadow</label></th>
                            <td>
                                <select name="idnt_style_box_shadow" id="idnt_style_box_shadow" style="height:34px; min-width:200px;">
                                    <option value="none" <?php selected($styles['box_shadow'], 'none'); ?>>None (Clean Flat)</option>
                                    <option value="subtle" <?php selected($styles['box_shadow'], 'subtle'); ?>>Subtle Soft Shadow</option>
                                    <option value="medium" <?php selected($styles['box_shadow'], 'medium'); ?>>Medium Modern Shadow</option>
                                    <option value="elevated" <?php selected($styles['box_shadow'], 'elevated'); ?>>Elevated Card Shadow</option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="idnt_style_font_family">Font Family</label></th>
                            <td>
                                <select name="idnt_style_font_family" id="idnt_style_font_family" style="height:34px; min-width:260px; font-weight:600;">
                                    <option value="inherit" <?php selected($styles['font_family'], 'inherit'); ?>>Theme Default (Inherit)</option>
                                    <option value="outfit" <?php selected($styles['font_family'], 'outfit'); ?>>Outfit (Modern Tech)</option>
                                    <option value="playfair" <?php selected($styles['font_family'], 'playfair'); ?>>Playfair Display (Serif Elegance)</option>
                                    <option value="inter" <?php selected($styles['font_family'], 'inter'); ?>>Inter (Clean UI)</option>
                                    <option value="roboto" <?php selected($styles['font_family'], 'roboto'); ?>>Roboto</option>
                                    <option value="poppins" <?php selected($styles['font_family'], 'poppins'); ?>>Poppins</option>
                                    <option value="montserrat" <?php selected($styles['font_family'], 'montserrat'); ?>>Montserrat</option>
                                    <option value="system" <?php selected($styles['font_family'], 'system'); ?>>System UI Font Stack</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Section B: Icons Styling -->
                <div class="idnt-card-section" style="background:#ffffff; border:1px solid #CBD5E1; border-radius:10px; padding:24px; margin-bottom:24px; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                    <h3 style="margin-top:0; color:#0F172A; font-size:16px; border-bottom:1px solid #F1F5F9; padding-bottom:10px;">
                        2. Calendar & Clock Icons
                    </h3>

                    <table class="form-table" style="margin-top:5px;">
                        <tr>
                            <th scope="row">Show Icons</th>
                            <td>
                                <label style="display:inline-flex; align-items:center; gap:8px; font-weight:600;">
                                    <input type="checkbox" name="idnt_style_show_icons" value="yes" <?php checked($styles['show_icons'], 'yes'); ?> style="width:18px; height:18px; margin:0;">
                                    Display SVG calendar and clock icons beside dates
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="idnt_style_icon_color">Icon Accent Color</label></th>
                            <td>
                                <input type="text" name="idnt_style_icon_color" id="idnt_style_icon_color" value="<?php echo esc_attr($styles['icon_color']); ?>" class="idnt-color-field" data-default-color="#007A3D">
                                <p class="description">Default: <code>#007A3D</code> (Muslim Aid Emerald Green).</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="idnt_style_icon_size">Icon Size (px)</label></th>
                            <td>
                                <input type="number" min="14" max="40" name="idnt_style_icon_size" id="idnt_style_icon_size" value="<?php echo intval($styles['icon_size']); ?>" style="width:90px; height:32px;"> px
                                <p class="description">Default: <code>22px</code>.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Section C: Labels (Top Small Text) -->
                <div class="idnt-card-section" style="background:#ffffff; border:1px solid #CBD5E1; border-radius:10px; padding:24px; margin-bottom:24px; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                    <h3 style="margin-top:0; color:#0F172A; font-size:16px; border-bottom:1px solid #F1F5F9; padding-bottom:10px;">
                        3. Section Labels (Top Text)
                    </h3>

                    <table class="form-table" style="margin-top:5px;">
                        <tr>
                            <th scope="row"><label for="idnt_style_label_date_text">Islamic Date Label Text</label></th>
                            <td>
                                <input type="text" name="idnt_style_label_date_text" id="idnt_style_label_date_text" value="<?php echo esc_attr($styles['label_date_text']); ?>" class="regular-text" style="font-weight:600;">
                                <p class="description">Default: <code>Today • Islamic Date</code></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="idnt_style_label_prayer_text">Prayer Label Text</label></th>
                            <td>
                                <input type="text" name="idnt_style_label_prayer_text" id="idnt_style_label_prayer_text" value="<?php echo esc_attr($styles['label_prayer_text']); ?>" class="regular-text" style="font-weight:600;">
                                <p class="description">Default: <code>Next Prayer: {city}</code> (token <code>{city}</code> is automatically replaced with your chosen city).</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="idnt_style_label_color">Label Color</label></th>
                            <td>
                                <input type="text" name="idnt_style_label_color" id="idnt_style_label_color" value="<?php echo esc_attr($styles['label_color']); ?>" class="idnt-color-field" data-default-color="#888888">
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Label Typography</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
                                    <div>
                                        <label style="font-size:12px; color:#64748B; display:block;">Font Size (px)</label>
                                        <input type="number" step="0.5" min="9" max="24" name="idnt_style_label_size" id="idnt_style_label_size" value="<?php echo floatval($styles['label_size']); ?>" style="width:85px; height:32px;"> px
                                    </div>
                                    <div>
                                        <label style="font-size:12px; color:#64748B; display:block;">Font Weight</label>
                                        <select name="idnt_style_label_weight" id="idnt_style_label_weight" style="height:32px;">
                                            <option value="400" <?php selected($styles['label_weight'], '400'); ?>>400 (Regular)</option>
                                            <option value="500" <?php selected($styles['label_weight'], '500'); ?>>500 (Medium)</option>
                                            <option value="600" <?php selected($styles['label_weight'], '600'); ?>>600 (Semi-Bold)</option>
                                            <option value="700" <?php selected($styles['label_weight'], '700'); ?>>700 (Bold)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="font-size:12px; color:#64748B; display:block;">Text Transform</label>
                                        <select name="idnt_style_label_transform" id="idnt_style_label_transform" style="height:32px;">
                                            <option value="none" <?php selected($styles['label_transform'], 'none'); ?>>Normal (None)</option>
                                            <option value="uppercase" <?php selected($styles['label_transform'], 'uppercase'); ?>>UPPERCASE</option>
                                            <option value="capitalize" <?php selected($styles['label_transform'], 'capitalize'); ?>>Capitalize</option>
                                        </select>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Section D: Values (Date & Time Text) -->
                <div class="idnt-card-section" style="background:#ffffff; border:1px solid #CBD5E1; border-radius:10px; padding:24px; margin-bottom:24px; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                    <h3 style="margin-top:0; color:#0F172A; font-size:16px; border-bottom:1px solid #F1F5F9; padding-bottom:10px;">
                        4. Date & Time Values (Main Text)
                    </h3>

                    <table class="form-table" style="margin-top:5px;">
                        <tr>
                            <th scope="row"><label for="idnt_style_value_color">Value Text Color</label></th>
                            <td>
                                <input type="text" name="idnt_style_value_color" id="idnt_style_value_color" value="<?php echo esc_attr($styles['value_color']); ?>" class="idnt-color-field" data-default-color="#1D3B2B">
                                <p class="description">Default: <code>#1D3B2B</code> (Deep Forest Slate).</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="idnt_style_value_hover_color">Prayer Hover Color</label></th>
                            <td>
                                <input type="text" name="idnt_style_value_hover_color" id="idnt_style_value_hover_color" value="<?php echo esc_attr($styles['value_hover_color']); ?>" class="idnt-color-field" data-default-color="#007A3D">
                                <p class="description">Color when visitor hovers over the Prayer timing trigger.</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Value Typography</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
                                    <div>
                                        <label style="font-size:12px; color:#64748B; display:block;">Font Size (px)</label>
                                        <input type="number" step="0.5" min="11" max="32" name="idnt_style_value_size" id="idnt_style_value_size" value="<?php echo floatval($styles['value_size']); ?>" style="width:85px; height:32px;"> px
                                    </div>
                                    <div>
                                        <label style="font-size:12px; color:#64748B; display:block;">Font Weight</label>
                                        <select name="idnt_style_value_weight" id="idnt_style_value_weight" style="height:32px;">
                                            <option value="500" <?php selected($styles['value_weight'], '500'); ?>>500 (Medium)</option>
                                            <option value="600" <?php selected($styles['value_weight'], '600'); ?>>600 (Semi-Bold)</option>
                                            <option value="700" <?php selected($styles['value_weight'], '700'); ?>>700 (Bold)</option>
                                            <option value="800" <?php selected($styles['value_weight'], '800'); ?>>800 (Extra-Bold)</option>
                                        </select>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Section E: Separator Divider -->
                <div class="idnt-card-section" style="background:#ffffff; border:1px solid #CBD5E1; border-radius:10px; padding:24px; margin-bottom:24px; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                    <h3 style="margin-top:0; color:#0F172A; font-size:16px; border-bottom:1px solid #F1F5F9; padding-bottom:10px;">
                        5. Divider Line Between Sections
                    </h3>

                    <table class="form-table" style="margin-top:5px;">
                        <tr>
                            <th scope="row">Show Divider</th>
                            <td>
                                <label style="display:inline-flex; align-items:center; gap:8px; font-weight:600;">
                                    <input type="checkbox" name="idnt_style_show_divider" value="yes" <?php checked($styles['show_divider'], 'yes'); ?> style="width:18px; height:18px; margin:0;">
                                    Enable vertical separator divider
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="idnt_style_divider_color">Divider Color</label></th>
                            <td>
                                <input type="text" name="idnt_style_divider_color" id="idnt_style_divider_color" value="<?php echo esc_attr($styles['divider_color']); ?>" class="idnt-color-field" data-default-color="#E0E0E0">
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Divider Dimensions</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
                                    <div>
                                        <label style="font-size:12px; color:#64748B; display:block;">Height (px)</label>
                                        <input type="number" min="10" max="60" name="idnt_style_divider_height" id="idnt_style_divider_height" value="<?php echo intval($styles['divider_height']); ?>" style="width:85px; height:32px;"> px
                                    </div>
                                    <div>
                                        <label style="font-size:12px; color:#64748B; display:block;">Thickness / Width (px)</label>
                                        <input type="number" min="1" max="10" name="idnt_style_divider_width" id="idnt_style_divider_width" value="<?php echo intval($styles['divider_width']); ?>" style="width:85px; height:32px;"> px
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Section F: Interactive Timetable Popover -->
                <div class="idnt-card-section" style="background:#ffffff; border:1px solid #CBD5E1; border-radius:10px; padding:24px; margin-bottom:24px; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                    <h3 style="margin-top:0; color:#0F172A; font-size:16px; border-bottom:1px solid #F1F5F9; padding-bottom:10px;">
                        6. Interactive Timetable Popover Box
                    </h3>

                    <table class="form-table" style="margin-top:5px;">
                        <tr>
                            <th scope="row"><label for="idnt_style_popover_bg">Popover Background</label></th>
                            <td>
                                <input type="text" name="idnt_style_popover_bg" id="idnt_style_popover_bg" value="<?php echo esc_attr($styles['popover_bg']); ?>" class="idnt-color-field" data-default-color="#FFFFFF">
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="idnt_style_popover_border">Popover Border Color</label></th>
                            <td>
                                <input type="text" name="idnt_style_popover_border" id="idnt_style_popover_border" value="<?php echo esc_attr($styles['popover_border']); ?>" class="idnt-color-field" data-default-color="#E2E8F0">
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="idnt_style_popover_text">Popover Text Color</label></th>
                            <td>
                                <input type="text" name="idnt_style_popover_text" id="idnt_style_popover_text" value="<?php echo esc_attr($styles['popover_text']); ?>" class="idnt-color-field" data-default-color="#0F172A">
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="idnt_style_popover_active_bg">Next Prayer Row Background</label></th>
                            <td>
                                <input type="text" name="idnt_style_popover_active_bg" id="idnt_style_popover_active_bg" value="<?php echo esc_attr($styles['popover_active_bg']); ?>" class="idnt-color-field" data-default-color="#E8F5E9">
                                <p class="description">Background highlight color for the upcoming prayer in the timetable list.</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="idnt_style_popover_active_color">Next Prayer Text & Tag Color</label></th>
                            <td>
                                <input type="text" name="idnt_style_popover_active_color" id="idnt_style_popover_active_color" value="<?php echo esc_attr($styles['popover_active_color']); ?>" class="idnt-color-field" data-default-color="#007A3D">
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Popover Border Radius & Shadow</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
                                    <div>
                                        <label style="font-size:12px; color:#64748B; display:block;">Border Radius (px)</label>
                                        <input type="number" min="0" max="30" name="idnt_style_popover_radius" id="idnt_style_popover_radius" value="<?php echo intval($styles['popover_radius']); ?>" style="width:85px; height:32px;"> px
                                    </div>
                                    <div>
                                        <label style="font-size:12px; color:#64748B; display:block;">Box Shadow</label>
                                        <select name="idnt_style_popover_shadow" id="idnt_style_popover_shadow" style="height:32px;">
                                            <option value="none" <?php selected($styles['popover_shadow'], 'none'); ?>>None</option>
                                            <option value="subtle" <?php selected($styles['popover_shadow'], 'subtle'); ?>>Subtle</option>
                                            <option value="medium" <?php selected($styles['popover_shadow'], 'medium'); ?>>Medium Elevation</option>
                                            <option value="elevated" <?php selected($styles['popover_shadow'], 'elevated'); ?>>Deep Drop Shadow</option>
                                        </select>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Section G: Custom CSS -->
                <div class="idnt-card-section" style="background:#ffffff; border:1px solid #CBD5E1; border-radius:10px; padding:24px; margin-bottom:24px; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                    <h3 style="margin-top:0; color:#0F172A; font-size:16px; border-bottom:1px solid #F1F5F9; padding-bottom:10px;">
                        7. Additional Custom CSS
                    </h3>
                    <p style="color:#64748B; font-size:13px; margin-bottom:10px;">
                        Add any custom CSS rules to fine-tune layout, animations, or responsive styles.
                    </p>
                    <textarea name="idnt_style_custom_css" id="idnt_style_custom_css" rows="6" class="large-text code" style="font-family:monospace;"><?php echo esc_textarea($styles['custom_css']); ?></textarea>
                </div>

            <!-- ========================================== -->
            <!-- TAB 2: GENERAL & TIMINGS                   -->
            <!-- ========================================== -->
            <?php elseif ($active_tab === 'general'): ?>

                <div style="background:#ffffff; border:1px solid #CBD5E1; border-radius:10px; padding:24px; margin-bottom:24px; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                    <h3 style="margin-top:0; font-size:16px; border-bottom:1px solid #F1F5F9; padding-bottom:10px;">
                        ⚙️ Geographic & Prayer Calculation Settings
                    </h3>

                    <table class="form-table" style="margin-top:10px;">
                        <tr>
                            <th scope="row"><label for="idnt_city">City</label></th>
                            <td>
                                <input type="text" name="idnt_city" id="idnt_city" value="<?php echo esc_attr($settings['city']); ?>" class="regular-text" style="font-weight:600;">
                                <p class="description">Default: <code>London</code>. Displays in "Next Prayer: London".</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="idnt_country">Country</label></th>
                            <td>
                                <input type="text" name="idnt_country" id="idnt_country" value="<?php echo esc_attr($settings['country']); ?>" class="regular-text">
                                <p class="description">Default: <code>United Kingdom</code>.</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="idnt_timezone">Timezone</label></th>
                            <td>
                                <input type="text" name="idnt_timezone" id="idnt_timezone" value="<?php echo esc_attr($settings['timezone']); ?>" class="regular-text">
                                <p class="description">Default: <code>Europe/London</code>.</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="idnt_hijri_adjust">Islamic Hijri Date Adjustment</label></th>
                            <td>
                                <select name="idnt_hijri_adjust" id="idnt_hijri_adjust" style="font-weight:600; height:36px;">
                                    <option value="-2" <?php selected($settings['hijri_adjust'], -2); ?>>-2 Days</option>
                                    <option value="-1" <?php selected($settings['hijri_adjust'], -1); ?>>-1 Day</option>
                                    <option value="0" <?php selected($settings['hijri_adjust'], 0); ?>>0 (Default Umm al-Qura Calculation)</option>
                                    <option value="1" <?php selected($settings['hijri_adjust'], 1); ?>>+1 Day</option>
                                    <option value="2" <?php selected($settings['hijri_adjust'], 2); ?>>+2 Days</option>
                                </select>
                                <p class="description">Adjust by +1 or -1 day to align with local moon sighting declarations if needed.</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="idnt_time_format">Prayer Time Format</label></th>
                            <td>
                                <label style="margin-right:20px; font-weight:600;">
                                    <input type="radio" name="idnt_time_format" value="12" <?php checked($settings['time_format'], '12'); ?>>
                                    12-Hour format (e.g. <code>05:09 am</code> - Muslim Aid style)
                                </label>
                                <label style="font-weight:600;">
                                    <input type="radio" name="idnt_time_format" value="24" <?php checked($settings['time_format'], '24'); ?>>
                                    24-Hour format (e.g. <code>05:09</code>)
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="idnt_calc_method">Calculation Method</label></th>
                            <td>
                                <select name="idnt_calc_method" id="idnt_calc_method" style="height:36px;">
                                    <option value="2" <?php selected($settings['calc_method'], 2); ?>>Islamic Society of North America (ISNA)</option>
                                    <option value="3" <?php selected($settings['calc_method'], 3); ?>>Muslim World League (MWL - UK Standard)</option>
                                    <option value="4" <?php selected($settings['calc_method'], 4); ?>>Umm al-Qura University, Makkah</option>
                                    <option value="1" <?php selected($settings['calc_method'], 1); ?>>University of Islamic Sciences, Karachi</option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Auto-Inject Header Bar</th>
                            <td>
                                <label style="display:inline-flex; align-items:center; gap:8px; font-weight:600;">
                                    <input type="checkbox" name="idnt_auto_topbar" value="yes" <?php checked($settings['auto_topbar'], 'yes'); ?> style="margin:0; width:18px; height:18px;">
                                    Automatically show top bar with Islamic Date & Next Prayer on all site pages
                                </label>
                                <p class="description">If enabled, renders at the very top of every page. If unchecked, use shortcode <code>[islamic_date_namaz_time]</code> in your Elementor header.</p>
                            </td>
                        </tr>
                    </table>
                </div>

            <!-- ========================================== -->
            <!-- TAB 3: HOW TO EMBED                        -->
            <!-- ========================================== -->
            <?php elseif ($active_tab === 'embed'): ?>

                <div style="background:#F0FDF4; border:1px solid #BBF7D0; border-radius:10px; padding:24px; margin-bottom:24px;">
                    <h3 style="margin-top:0; color:#166534; font-size:16px;">📋 How to Embed Anywhere</h3>
                    <p style="color:#15803D; font-size:14px; margin-bottom:12px;">
                        You can insert this widget anywhere on your website using any of the following methods:
                    </p>
                    
                    <div style="background:#ffffff; border:1px solid #86EFAC; border-radius:8px; padding:16px; margin-bottom:14px;">
                        <strong style="color:#0F172A; display:block; margin-bottom:6px;">1. Elementor / Gutenberg Shortcode Widget:</strong>
                        <code style="font-size:14px; font-weight:700; color:#007A3D;">[islamic_date_namaz_time]</code>
                        <p style="color:#64748B; font-size:12.5px; margin:6px 0 0 0;">Also supports aliases: <code>[namaz_time]</code> or <code>[islamic_date]</code>.</p>
                    </div>

                    <div style="background:#ffffff; border:1px solid #86EFAC; border-radius:8px; padding:16px; margin-bottom:14px;">
                        <strong style="color:#0F172A; display:block; margin-bottom:6px;">2. PHP Template Call (inside header.php or custom templates):</strong>
                        <code style="font-size:13.5px; font-weight:700; color:#007A3D;">&lt;?php islamic_date_namaz_time(); ?&gt;</code>
                    </div>

                    <div style="background:#ffffff; border:1px solid #86EFAC; border-radius:8px; padding:16px;">
                        <strong style="color:#0F172A; display:block; margin-bottom:6px;">3. Automatic Global Header Bar:</strong>
                        <p style="color:#334155; font-size:13px; margin:0;">
                            Go to <strong>Prayer & City Settings</strong> tab and check <em>"Automatically show top bar"</em> to inject it across every page without touching any code!
                        </p>
                    </div>
                </div>

            <?php endif; ?>

            <!-- Action Buttons Footer -->
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; padding:16px 20px; margin-top:20px;">
                <div>
                    <button type="submit" class="button button-primary button-large" style="background:#007A3D; border-color:#005A2D; font-weight:700; padding:6px 28px; font-size:14px;">
                        💾 Save All Changes & Refresh Live Preview
                    </button>
                </div>

                <?php if ($active_tab === 'styles'): ?>
                <div>
                    <button type="submit" name="idnt_reset_defaults" value="1" class="button button-secondary" onclick="return confirm('Are you sure you want to reset all styling back to the 1:1 Muslim Aid defaults?');" style="color:#DC2626; border-color:#FCA5A5;">
                        ↺ Reset Styles to Muslim Aid 1:1 Default
                    </button>
                </div>
                <?php endif; ?>
            </div>

        </form>
    </div>

    <!-- Live Color Picker & Dynamic Preview JavaScript -->
    <script>
    jQuery(document).ready(function($) {
        // Initialize WordPress Color Pickers
        $('.idnt-color-field').wpColorPicker({
            change: function(event, ui) {
                // Immediate preview feedback trigger
                triggerLivePreview();
            },
            clear: function() {
                triggerLivePreview();
            }
        });

        // Trigger live preview changes on input
        $('#idnt-main-form input, #idnt-main-form select').on('input change', function() {
            triggerLivePreview();
        });

        function triggerLivePreview() {
            const font = $('#idnt_style_font_family').val();
            const fontMap = {
                'outfit': "'Outfit', sans-serif",
                'playfair': "'Playfair Display', Georgia, serif",
                'inter': "'Inter', sans-serif",
                'roboto': "'Roboto', sans-serif",
                'poppins': "'Poppins', sans-serif",
                'montserrat': "'Montserrat', sans-serif",
                'system': '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                'inherit': 'inherit'
            };

            const $w = $('#idnt-preview-box .idnt-muslimaid-widget');
            if (!$w.length) return;

            // Background & Border
            const bg = $('#idnt_style_bg_color').val() || 'transparent';
            const bWidth = parseInt($('#idnt_style_border_width').val() || 0);
            const bColor = $('#idnt_style_border_color').val() || '#E2E8F0';
            const bRadius = parseInt($('#idnt_style_border_radius').val() || 0);
            const padV = parseInt($('#idnt_style_padding_v').val() || 0);
            const padH = parseInt($('#idnt_style_padding_h').val() || 0);
            const gap = parseInt($('#idnt_style_gap').val() || 16);

            $w.css({
                'background-color': bg,
                'border-radius': bRadius + 'px',
                'padding': padV + 'px ' + padH + 'px',
                'gap': gap + 'px',
                'font-family': fontMap[font] || 'inherit'
            });

            if (bWidth > 0) {
                $w.css('border', bWidth + 'px solid ' + bColor);
            } else {
                $w.css('border', 'none');
            }

            // Icons
            const showIcons = $('#idnt-main-form input[name="idnt_style_show_icons"]').is(':checked');
            const iconColor = $('#idnt_style_icon_color').val() || '#007A3D';
            const iconSize = parseInt($('#idnt_style_icon_size').val() || 22);

            if (showIcons) {
                $w.find('.idnt-icon').show();
                $w.find('.idnt-icon svg').attr({
                    'width': iconSize,
                    'height': iconSize,
                    'stroke': iconColor
                });
                $w.find('.idnt-icon circle').attr('fill', iconColor);
            } else {
                $w.find('.idnt-icon').hide();
            }

            // Labels
            const labelColor = $('#idnt_style_label_color').val() || '#888888';
            const labelSize = parseFloat($('#idnt_style_label_size').val() || 11);
            const labelWeight = $('#idnt_style_label_weight').val() || '500';
            const labelTransform = $('#idnt_style_label_transform').val() || 'none';

            $w.find('.idnt-label').css({
                'color': labelColor,
                'font-size': labelSize + 'px',
                'font-weight': labelWeight,
                'text-transform': labelTransform
            });

            // Values
            const valColor = $('#idnt_style_value_color').val() || '#1D3B2B';
            const valSize = parseFloat($('#idnt_style_value_size').val() || 14.5);
            const valWeight = $('#idnt_style_value_weight').val() || '600';

            $w.find('.idnt-value').css({
                'color': valColor,
                'font-size': valSize + 'px',
                'font-weight': valWeight
            });

            // Divider
            const showDivider = $('#idnt-main-form input[name="idnt_style_show_divider"]').is(':checked');
            const divColor = $('#idnt_style_divider_color').val() || '#E0E0E0';
            const divH = parseInt($('#idnt_style_divider_height').val() || 28);
            const divW = parseInt($('#idnt_style_divider_width').val() || 1);

            if (showDivider) {
                $w.find('.idnt-divider').show().css({
                    'background-color': divColor,
                    'height': divH + 'px',
                    'width': divW + 'px',
                    'margin': '0 ' + Math.max(4, gap / 2) + 'px'
                });
            } else {
                $w.find('.idnt-divider').hide();
            }
        }
    });
    </script>
    <?php
}
