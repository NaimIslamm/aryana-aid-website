<?php
/**
 * Customizable Donation Button Widgets & Shortcodes
 * Provides standalone buttons and button groups with package tracking.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * SHORTCODE: [donate_button]
 * Standalone customizable donation button.
 * Example: [donate_button amount="600" label="£600 Water Pump" project="Water Appeal" package="£600 Water Pump"]
 */
add_shortcode('donate_button', function ($atts) {
    wp_enqueue_style('ads-donation-css');
    wp_enqueue_script('ads-donation-js');
    $currency = get_option('ads_currency_symbol', '£');
    $atts = shortcode_atts([
        'amount'      => '50',
        'label'       => '',
        'project'     => 'General Donation',
        'package'     => '',
        'campaign_id' => '',
        'type'        => 'Sadaqah',
        'freq'        => 'single',
        'bg_color'    => '#D8005A',
        'text_color'  => '#ffffff',
        'size'        => 'medium', // small, medium, large
        'icon'        => '💧',
        'class'       => '',
    ], $atts, 'donate_button');

    $amount = floatval($atts['amount']);
    $label = !empty($atts['label']) ? $atts['label'] : ($currency . $amount . ' ' . $atts['package']);
    if (empty($label)) {
        $label = 'Donate ' . $currency . $amount;
    }
    $package = !empty($atts['package']) ? $atts['package'] : $label;
    $project = sanitize_text_field($atts['project']);

    // Auto-resolve project if default was left
    if ($project === 'Water - Well' || $project === 'General Donation' || empty($project)) {
        if ($amount == 3600 || stripos($label . ' ' . $package, 'tank') !== false) {
            $project = 'Water Tank - Community Well';
        } elseif ($amount == 600 || stripos($label . ' ' . $package, 'pump') !== false) {
            $project = 'Water - Pump';
        } elseif ($amount == 1200) {
            $project = 'Water - Well';
        }
    }
    $type = sanitize_text_field($atts['type']);
    $freq = sanitize_key($atts['freq'] ?: 'single');
    $campaign_id = intval($atts['campaign_id']);

    $checkout_url = home_url('/donate/');
    $query_args = [
        'project' => $project,
        'amount'  => $amount,
        'package' => $package,
        'freq'    => $freq,
        'type'    => $type,
    ];
    if ($campaign_id > 0) {
        $query_args['campaign_id'] = $campaign_id;
    }

    $final_url = add_query_arg(array_map('urlencode', $query_args), $checkout_url);

    // Padding based on size
    $padding = '12px 24px';
    $font_size = '16px';
    if ($atts['size'] === 'large') {
        $padding = '16px 36px';
        $font_size = '18px';
    } elseif ($atts['size'] === 'small') {
        $padding = '8px 16px';
        $font_size = '14px';
    }

    ob_start();
    ?>
    <a href="<?php echo esc_url($final_url); ?>" 
       class="ads-custom-donate-btn <?php echo esc_attr($atts['class']); ?>"
       data-project="<?php echo esc_attr($project); ?>"
       data-amount="<?php echo esc_attr($amount); ?>"
       data-package="<?php echo esc_attr($package); ?>"
       data-freq="<?php echo esc_attr($freq); ?>"
       style="
           display: inline-flex;
           align-items: center;
           justify-content: center;
           gap: 8px;
           background-color: <?php echo esc_attr($atts['bg_color']); ?>;
           color: <?php echo esc_attr($atts['text_color']); ?>;
           padding: <?php echo esc_attr($padding); ?>;
           font-size: <?php echo esc_attr($font_size); ?>;
           font-weight: 700;
           border-radius: 9999px;
           text-decoration: none;
           box-shadow: 0 4px 12px rgba(216, 0, 90, 0.25);
           transition: all 0.2s ease;
           cursor: pointer;
           border: none;
           text-align: center;
       ">
        <?php if (!empty($atts['icon'])): ?>
            <span class="ads-btn-icon"><?php echo esc_html($atts['icon']); ?></span>
        <?php endif; ?>
        <span><?php echo esc_html($label); ?></span>
    </a>
    <?php
    return ob_get_clean();
});

/**
 * SHORTCODE: [donate_button_group]
 * Preset button trio or grid for a specific project.
 * Example: [donate_button_group project="Water Appeal" buttons="600:Water Pump,1200:Water Well,3600:Community Tank"]
 */
add_shortcode('donate_button_group', function ($atts) {
    wp_enqueue_style('ads-donation-css');
    wp_enqueue_script('ads-donation-js');
    $atts = shortcode_atts([
        'project'   => 'Water - Well',
        'buttons'   => '600:Water Pump,1200:Water Well,3600:Community Tank',
        'bg_color'  => '#D8005A',
        'alignment' => 'center', // left, center, right
        'gap'       => '12px',
        'freq'      => 'single',
    ], $atts, 'donate_button_group');

    $project = sanitize_text_field($atts['project']);
    $buttons_raw = explode(',', $atts['buttons']);
    $currency = get_option('ads_currency_symbol', '£');

    $justify = 'center';
    if ($atts['alignment'] === 'left') $justify = 'flex-start';
    if ($atts['alignment'] === 'right') $justify = 'flex-end';
    if ($atts['alignment'] === 'justify') $justify = 'stretch';

    ob_start();
    ?>
    <div class="ads-btn-group-wrap" style="display:flex; flex-wrap:wrap; gap:<?php echo esc_attr($atts['gap']); ?>; justify-content:<?php echo esc_attr($justify); ?>; margin: 15px 0;">
        <?php 
        foreach ($buttons_raw as $b) {
            $parts = explode(':', trim($b));
            $amt = floatval($parts[0] ?? 0);
            $sub = trim($parts[1] ?? '');
            if ($amt <= 0) continue;

            $label = $currency . $amt . ($sub ? ' ' . $sub : '');
            $pkg = $currency . $amt . ($sub ? ' ' . $sub : '');

            echo do_shortcode(sprintf(
                '[donate_button amount="%s" label="%s" project="%s" package="%s" bg_color="%s" freq="%s"]',
                esc_attr($amt),
                esc_attr($label),
                esc_attr($project),
                esc_attr($pkg),
                esc_attr($atts['bg_color']),
                esc_attr($atts['freq'] ?? 'single')
            ));
        }
        ?>
    </div>
    <?php
    return ob_get_clean();
});
