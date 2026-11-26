<?php
/**
 * Aryana Aid Donation System - Slide-Out Donation Drawer (Mini-Cart)
 *
 * Provides a slide-out drawer ("Your donations") when any donation button is clicked.
 * Includes live quantity adjustment ([-] 1 [+]), item removal, multi-item calculation,
 * full WooCommerce cart synchronization, and complete admin customizability.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. Retrieve Drawer Configuration Settings
 */
function ads_get_drawer_settings() {
    $defaults = [
        'enabled'              => 'yes',
        'title'                => 'Your donations',
        'checkout_text'        => 'Proceed To Checkout',
        'checkout_url'         => home_url('/donate/'),
        'empty_text'           => 'Your donation basket is currently empty.',
        'continue_text'        => 'Continue Browsing',
        'totals_title'         => 'Total Amount',
        'single_payment_label' => 'Single Payment',
        'monthly_payment_label'=> 'Monthly Payment',
        'total_due_label'      => 'Total due today',
        'remove_text'          => 'Remove',
        'panel_position'       => 'right',
        'desktop_width'        => '430px',
        'tablet_width'         => '50vw',
        'mobile_width'         => '70vw',
        'show_qty'             => 'yes',
        'show_remove'          => 'yes',
        'show_thumbnails'      => 'no',
        'show_floating_icon'   => 'yes',
        'close_on_backdrop'    => 'yes',
        'bg_color'             => '#FAF8F5',
        'header_color'         => '#00473e',
        'subtitle_color'       => '#008254',
        'item_title_color'     => '#00473e',
        'item_price_color'     => '#00473e',
        'btn_bg'               => '#007a50',
        'btn_hover_bg'         => '#006341',
        'btn_color'            => '#ffffff',
        'btn_border_radius'    => '8px',
        'stepper_color'        => '#00473e',
        'stepper_bg'           => '#ffffff',
        'stepper_border_color' => '#D5CEBF',
        'remove_color'         => '#9c7a56',
        'launcher_bg'          => '#007a50',
        'launcher_color'       => '#ffffff',
        'launcher_badge_bg'    => '#E11D48',
        'custom_css'           => '',
        'default_image'        => '',
    ];

    $settings = [];
    foreach ($defaults as $key => $default) {
        $settings[$key] = get_option('ads_drawer_' . $key, $default);
    }

    return $settings;
}

/**
 * 2. Enqueue Drawer CSS & JavaScript
 */
add_action('wp_enqueue_scripts', 'ads_enqueue_drawer_assets', 35);
add_action('elementor/frontend/after_enqueue_styles', 'ads_enqueue_drawer_assets', 35);

function ads_enqueue_drawer_assets() {
    $settings = ads_get_drawer_settings();
    if ($settings['enabled'] !== 'yes') {
        return;
    }

    // Drawer Stylesheet
    wp_enqueue_style(
        'ads-donation-drawer-css',
        ADS_PLUGIN_URL . 'assets/css/donation-drawer.css',
        ['ads-modern-donation-css'],
        ADS_VERSION
    );

    // Dynamic Color & Dimension Customizations Injection
    $custom_styles = "
    :root {
        --ads-drawer-width-desktop: {$settings['desktop_width']};
        --ads-drawer-width-tablet: {$settings['tablet_width']};
        --ads-drawer-width-mobile: {$settings['mobile_width']};
        --ads-drawer-bg: {$settings['bg_color']};
        --ads-drawer-header-color: {$settings['header_color']};
        --ads-drawer-subtitle-color: {$settings['subtitle_color']};
        --ads-drawer-item-title-color: {$settings['item_title_color']};
        --ads-drawer-item-price-color: {$settings['item_price_color']};
        --ads-drawer-btn-bg: {$settings['btn_bg']};
        --ads-drawer-btn-hover-bg: {$settings['btn_hover_bg']};
        --ads-drawer-btn-color: {$settings['btn_color']};
        --ads-drawer-btn-radius: {$settings['btn_border_radius']};
        --ads-drawer-stepper-color: {$settings['stepper_color']};
        --ads-drawer-stepper-bg: {$settings['stepper_bg']};
        --ads-drawer-stepper-border: {$settings['stepper_border_color']};
        --ads-drawer-remove-color: {$settings['remove_color']};
        --ads-drawer-launcher-bg: {$settings['launcher_bg']};
        --ads-drawer-launcher-color: {$settings['launcher_color']};
        --ads-drawer-launcher-badge-bg: {$settings['launcher_badge_bg']};
    }

    /* Direct Theme & Color Enforcement */
    #ads-donation-drawer-root .ads-drawer-panel {
        background-color: {$settings['bg_color']} !important;
    }
    #ads-donation-drawer-root .ads-drawer-header {
        background-color: {$settings['bg_color']} !important;
    }
    #ads-donation-drawer-root .ads-drawer-title {
        color: {$settings['header_color']} !important;
    }
    #ads-donation-drawer-root .ads-drawer-item-title {
        color: {$settings['item_title_color']} !important;
    }
    #ads-donation-drawer-root .ads-drawer-item-subtitle {
        color: {$settings['subtitle_color']} !important;
    }
    #ads-donation-drawer-root .ads-drawer-item-price {
        color: {$settings['item_price_color']} !important;
    }
    #ads-donation-drawer-root .ads-drawer-stepper {
        background-color: {$settings['stepper_bg']} !important;
        border-color: {$settings['stepper_border_color']} !important;
    }
    #ads-donation-drawer-root .ads-drawer-step-btn,
    #ads-donation-drawer-root span.ads-drawer-step-btn {
        color: {$settings['stepper_color']} !important;
    }
    #ads-donation-drawer-root .ads-drawer-step-val,
    #ads-donation-drawer-root span.ads-drawer-step-val {
        color: {$settings['stepper_color']} !important;
    }
    #ads-donation-drawer-root .ads-drawer-remove-link,
    #ads-donation-drawer-root a.ads-drawer-remove-link {
        color: {$settings['remove_color']} !important;
    }
    #ads-donation-drawer-root .ads-drawer-footer {
        background-color: {$settings['bg_color']} !important;
    }
    #ads-donation-drawer-root .ads-drawer-totals-title {
        color: {$settings['header_color']} !important;
    }
    #ads-donation-drawer-root .ads-drawer-due-label,
    #ads-donation-drawer-root .ads-drawer-due-val {
        color: {$settings['header_color']} !important;
    }
    #ads-donation-drawer-root .ads-drawer-checkout-btn,
    #ads-donation-drawer-root button#ads-drawer-checkout-btn {
        background-color: {$settings['btn_bg']} !important;
        color: {$settings['btn_color']} !important;
        border-radius: {$settings['btn_border_radius']} !important;
    }
    #ads-donation-drawer-root .ads-drawer-checkout-btn:hover {
        background-color: {$settings['btn_hover_bg']} !important;
    }
    #ads-donation-drawer-root .ads-drawer-launcher {
        background-color: {$settings['launcher_bg']} !important;
        color: {$settings['launcher_color']} !important;
    }
    #ads-donation-drawer-root .ads-drawer-launcher-badge {
        background-color: {$settings['launcher_badge_bg']} !important;
    }
    #ads-donation-drawer-root .ads-drawer-continue-btn {
        background-color: {$settings['btn_bg']} !important;
        color: {$settings['btn_color']} !important;
    }
    #ads-donation-drawer-root .ads-drawer-continue-btn:hover {
        background-color: {$settings['btn_hover_bg']} !important;
    }
    ";
    if (($settings['panel_position'] ?? 'right') === 'left') {
        $custom_styles .= "
        #ads-donation-drawer-root .ads-drawer-panel {
            right: auto !important;
            left: 0 !important;
            transform: translateX(-100%) !important;
            box-shadow: 10px 0 35px rgba(0, 0, 0, 0.18) !important;
        }
        #ads-donation-drawer-root.ads-drawer-open .ads-drawer-panel {
            transform: translateX(0) !important;
        }
        ";
    }
    if (!empty($settings['custom_css'])) {
        $custom_styles .= "\n" . $settings['custom_css'];
    }
    wp_add_inline_style('ads-donation-drawer-css', $custom_styles);

    // Drawer Script
    wp_enqueue_script(
        'ads-donation-drawer-js',
        ADS_PLUGIN_URL . 'assets/js/donation-drawer.js',
        ['jquery'],
        ADS_VERSION,
        true
    );

    // Pass configuration to frontend JS
    $currency = get_option('ads_currency_symbol', '£');
    $currency_code = get_option('ads_currency_code', 'GBP');

    // Default project images mapping
    $upload_dir = wp_upload_dir();
    $base_upload_url = $upload_dir['baseurl'];
    $default_images = [
        'water'    => $base_upload_url . '/2024/09/Community-Water-Tank-150x150.png',
        'tank'     => $base_upload_url . '/2024/09/Community-Water-Tank-150x150.png',
        'pump'     => $base_upload_url . '/2024/09/Community-Water-Tank-150x150.png',
        'well'     => $base_upload_url . '/2024/09/Community-Water-Tank-150x150.png',
        'orphan'   => $base_upload_url . '/2026/10/Orphan-150x150.jpg',
        'widow'    => $base_upload_url . '/2026/10/Aryana-Our_Projects-Widow-150x150.jpg',
        'winter'   => $base_upload_url . '/2026/10/Emergency-Shelter-Image-for-Home-Page-1024x576-1-150x150.jpg',
        'food'     => $base_upload_url . '/2026/10/Aryana-Header-Food_Parcels-363x243-150x150.jpg',
        'general'  => $base_upload_url . '/2026/10/Aryana-Header-Food_Parcels-363x243-150x150.jpg',
        'fallback' => !empty($settings['default_image']) ? $settings['default_image'] : $base_upload_url . '/2026/10/Orphan-150x150.jpg',
    ];

    wp_localize_script('ads-donation-drawer-js', 'adsDrawerConfig', [
        'enabled'           => $settings['enabled'] === 'yes',
        'title'             => $settings['title'],
        'checkoutText'      => $settings['checkout_text'],
        'checkoutUrl'       => $settings['checkout_url'],
        'emptyText'         => $settings['empty_text'],
        'continueText'      => $settings['continue_text'],
        'removeText'        => $settings['remove_text'] ?? 'Remove',
        'showQty'           => $settings['show_qty'] === 'yes',
        'showRemove'        => $settings['show_remove'] === 'yes',
        'showThumbnails'    => $settings['show_thumbnails'] === 'yes',
        'showFloatingIcon'  => $settings['show_floating_icon'] === 'yes',
        'closeOnBackdrop'   => $settings['close_on_backdrop'] === 'yes',
        'currency'          => $currency,
        'currencyCode'      => $currency_code,
        'defaultImages'     => $default_images,
        'ajaxUrl'           => admin_url('admin-ajax.php'),
        'nonce'             => wp_create_nonce('ads_donation_nonce'),
    ]);
}

/**
 * 3. Render Drawer HTML in Site Footer
 */
add_action('wp_footer', 'ads_render_donation_drawer', 99);

function ads_render_donation_drawer() {
    $settings = ads_get_drawer_settings();
    if ($settings['enabled'] !== 'yes') {
        return;
    }
    ?>
    <!-- Aryana Aid Slide-Out Donation Drawer -->
    <div id="ads-donation-drawer-root" class="ads-drawer-root" aria-hidden="true">
        <div class="ads-drawer-backdrop" id="ads-drawer-backdrop"></div>
        <aside class="ads-drawer-panel" id="ads-drawer-panel" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr($settings['title']); ?>">
            
            <!-- Drawer Header -->
            <div class="ads-drawer-header">
                <h2 class="ads-drawer-title"><?php echo esc_html($settings['title']); ?></h2>
                <div class="ads-drawer-close-btn" id="ads-drawer-close" role="button" tabindex="0" aria-label="Close donations drawer" title="Close">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#1e293b" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </div>
            </div>

            <!-- Drawer Body -->
            <div class="ads-drawer-body" id="ads-drawer-body">
                <!-- Item List Container -->
                <div class="ads-drawer-items-list" id="ads-drawer-items-list">
                    <!-- Dynamic Items Injected Here via JavaScript -->
                </div>

                <!-- Empty State -->
                <div class="ads-drawer-empty-state" id="ads-drawer-empty" style="display:none;">
                    <div class="ads-drawer-empty-icon">
                        <svg viewBox="0 0 24 24" width="54" height="54" fill="none" stroke="#9ca3af" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                    </div>
                    <p class="ads-drawer-empty-msg"><?php echo esc_html($settings['empty_text']); ?></p>
                    <button type="button" class="ads-drawer-continue-btn" id="ads-drawer-empty-continue">
                        <?php echo esc_html($settings['continue_text']); ?>
                    </button>
                </div>
            </div>

            <!-- Drawer Footer (Sticky at bottom) -->
            <div class="ads-drawer-footer" id="ads-drawer-footer">
                <div class="ads-drawer-totals-box">
                    <div class="ads-drawer-totals-title"><?php echo esc_html($settings['totals_title'] ?? 'Total Amount'); ?></div>
                    <div class="ads-drawer-totals-row ads-drawer-single-row">
                        <span class="ads-drawer-totals-label"><?php echo esc_html($settings['single_payment_label'] ?? 'Single Payment'); ?></span>
                        <span class="ads-drawer-totals-val" id="ads-drawer-single-total">£0.00</span>
                    </div>
                    <div class="ads-drawer-totals-row ads-drawer-monthly-row" style="display:none;">
                        <span class="ads-drawer-totals-label"><?php echo esc_html($settings['monthly_payment_label'] ?? 'Monthly Payment'); ?></span>
                        <span class="ads-drawer-totals-val" id="ads-drawer-monthly-total">£0.00</span>
                    </div>
                    <div class="ads-drawer-totals-row ads-drawer-due-row">
                        <span class="ads-drawer-due-label"><?php echo esc_html($settings['total_due_label'] ?? 'Total due today'); ?></span>
                        <span class="ads-drawer-due-val" id="ads-drawer-due-total">£0.00</span>
                    </div>
                </div>

                <button type="button" class="ads-drawer-checkout-btn" id="ads-drawer-checkout-btn">
                    <span><?php echo esc_html($settings['checkout_text']); ?></span>
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
        </aside>

        <!-- Floating Basket Trigger Button -->
        <?php if ($settings['show_floating_icon'] === 'yes'): ?>
        <button type="button" class="ads-drawer-launcher" id="ads-drawer-launcher" title="View your donations" aria-label="View your donations" style="display:none;">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
            <span class="ads-drawer-launcher-badge" id="ads-drawer-launcher-badge">0</span>
        </button>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * 4. AJAX Endpoint to Sync Drawer Cart into WooCommerce Session (Cart Synchronizer)
 */
add_action('wp_ajax_ads_sync_drawer_cart', 'ads_ajax_sync_drawer_cart');
add_action('wp_ajax_nopriv_ads_sync_drawer_cart', 'ads_ajax_sync_drawer_cart');

function ads_ajax_sync_drawer_cart() {
    check_ajax_referer('ads_donation_nonce', 'nonce');

    if (!class_exists('WooCommerce') || !WC()->cart) {
        wp_send_json_success(['synced' => false]);
    }

    $product_id = function_exists('ads_wc_get_donation_product_id') ? ads_wc_get_donation_product_id() : 0;
    if (!$product_id) {
        wp_send_json_success(['synced' => false]);
    }

    $items = isset($_POST['items']) && is_array($_POST['items']) ? $_POST['items'] : [];

    WC()->cart->empty_cart();

    foreach ($items as $item) {
        $amt = floatval($item['amount'] ?? 0);
        $qty = intval($item['quantity'] ?? 1);
        if ($amt <= 0 || $qty <= 0) continue;

        $cart_item_data = [
            'ads_donation_amount' => $amt,
            'ads_project'         => sanitize_text_field($item['project'] ?? 'General Donation'),
            'ads_location'        => sanitize_text_field($item['location'] ?? 'Afghanistan'),
            'ads_type'            => sanitize_text_field($item['type'] ?? 'Sadaqah'),
            'ads_frequency'       => sanitize_key($item['frequency'] ?? 'single'),
            'ads_package'         => sanitize_text_field($item['package'] ?? ''),
            'unique_key'          => md5(microtime() . rand()),
        ];

        WC()->cart->add_to_cart($product_id, $qty, 0, [], $cart_item_data);
    }

    wp_send_json_success([
        'synced'     => true,
        'cart_count' => WC()->cart->get_cart_contents_count(),
        'cart_total' => WC()->cart->get_cart_total(),
    ]);
}

