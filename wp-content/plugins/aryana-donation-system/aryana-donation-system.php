<?php
/**
 * Plugin Name: Aryana Aid Donation System
 * Plugin URI: https://aryanaaid.org.uk
 * Description: Complete, high-performance donation system with dynamic multi-item basket, cascading dropdowns, project-specific donation cards, quick-action bars, Gift Aid, and secure checkout.
 * Version: 1.0.0
 * Author: Antigravity
 * Text Domain: aryana-donation-system
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ADS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ADS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ADS_VERSION', '3.0.0');

// Load Core Components
require_once ADS_PLUGIN_DIR . 'includes/projects-data.php';
require_once ADS_PLUGIN_DIR . 'includes/donations-db.php';
require_once ADS_PLUGIN_DIR . 'includes/campaigns.php';
require_once ADS_PLUGIN_DIR . 'includes/button-widgets.php';
require_once ADS_PLUGIN_DIR . 'includes/donations-admin.php';
require_once ADS_PLUGIN_DIR . 'includes/admin-settings.php';
require_once ADS_PLUGIN_DIR . 'includes/elementor-widgets.php';
require_once ADS_PLUGIN_DIR . 'includes/woocommerce-bridge.php';
require_once ADS_PLUGIN_DIR . 'includes/donation-drawer.php';
require_once ADS_PLUGIN_DIR . 'includes/hero-quick-bar.php';

// Auto-create donations table if not exists
add_action('plugins_loaded', function () {
    if (get_option('ads_db_version') !== '1.2.0') {
        ads_create_donations_table();
        update_option('ads_db_version', '1.2.0');
    }
});

// Security Hardening: Disable XML-RPC and hide WordPress version
add_filter('xmlrpc_enabled', '__return_false');
add_filter('xmlrpc_methods', '__return_empty_array');
add_filter('the_generator', '__return_empty_string');

// Enqueue Styles & Scripts (Frontend & Elementor Editor Preview)
function ads_enqueue_frontend_assets() {
    // Global Primary Font: Albert Sans & Outfit
    wp_enqueue_style(
        'ads-google-fonts',
        'https://fonts.googleapis.com/css2?family=Albert+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700&display=swap',
        [],
        null
    );

    // Modern Stylish Donation CSS
    wp_enqueue_style(
        'ads-modern-donation-css',
        ADS_PLUGIN_URL . 'assets/css/modern-donation.css',
        [],
        ADS_VERSION
    );

    // Project Cards, Campaigns and Buttons CSS
    wp_enqueue_style(
        'ads-donation-css',
        ADS_PLUGIN_URL . 'assets/css/donation.css',
        ['ads-modern-donation-css'],
        ADS_VERSION
    );

    // Gateways Dependencies
    wp_enqueue_script('ads-stripe-v3', 'https://js.stripe.com/v3/', [], null, true);

    // Custom Project Card & Quick Donate JS Engine
    wp_enqueue_script(
        'ads-donation-js',
        ADS_PLUGIN_URL . 'assets/js/donation.js',
        ['jquery'],
        ADS_VERSION,
        true
    );

    // Pass Config to JavaScript
    $matrix = ads_get_projects_matrix();
    $currency = get_option('ads_currency_symbol', '£');
    $currency_code = get_option('ads_currency_code', 'GBP');
    $admin_fee_pct = get_option('ads_admin_fee_percent', 1.10);
    $wc_pp = get_option('woocommerce_paypal_settings', []);
    $paypal_email = (!empty($wc_pp['email']) && is_email($wc_pp['email'])) ? $wc_pp['email'] : get_option('ads_paypal_email', 'paypal@aryanaaid.org.uk');
    $stripe_keys = function_exists('ads_get_wc_stripe_keys') ? ads_get_wc_stripe_keys() : ['pk' => ''];
    $stripe_pk = $stripe_keys['pk'];

    wp_localize_script('ads-donation-js', 'adsConfig', [
        'ajaxUrl'      => admin_url('admin-ajax.php'),
        'nonce'        => wp_create_nonce('ads_donation_nonce'),
        'matrix'       => $matrix,
        'currency'     => $currency,
        'currencyCode' => $currency_code,
        'adminFeePct'  => $admin_fee_pct,
        'checkoutUrl'  => home_url('/donate/'),
        'paypalEmail'  => $paypal_email,
        'stripePk'     => $stripe_pk,
    ]);
}
add_action('wp_enqueue_scripts', 'ads_enqueue_frontend_assets');
add_action('elementor/frontend/after_enqueue_styles', 'ads_enqueue_frontend_assets');
add_action('elementor/preview/enqueue_styles', 'ads_enqueue_frontend_assets');
add_action('elementor/preview/enqueue_scripts', 'ads_enqueue_frontend_assets');
add_action('elementor/editor/after_enqueue_styles', 'ads_enqueue_frontend_assets');
add_action('elementor/editor/after_enqueue_scripts', 'ads_enqueue_frontend_assets');

// AJAX Handler: Process & Record Complete Donation
add_action('wp_ajax_ads_process_donation', 'ads_ajax_process_donation');
add_action('wp_ajax_nopriv_ads_process_donation', 'ads_ajax_process_donation');

function ads_is_valid_luhn($number) {
    $clean = preg_replace('/\D/', '', $number);
    $len = strlen($clean);
    if ($len < 13 || $len > 19) {
        return false;
    }
    // Check all identical digits (e.g. 0000000000000000, 1111111111111111)
    if (preg_match('/^(\d)\1+$/', $clean)) {
        return false;
    }
    $sum = 0;
    $should_double = false;
    for ($i = $len - 1; $i >= 0; $i--) {
        $digit = intval($clean[$i]);
        if ($should_double) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        $sum += $digit;
        $should_double = !$should_double;
    }
    return ($sum % 10 === 0);
}

function ads_ajax_process_donation() {
    check_ajax_referer('ads_donation_nonce', 'nonce');

    // Anti-Carding & Bot Protection: Limit to max 15 requests per IP per 5 minutes
    $ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    $rate_key = 'ads_rate_' . md5($ip);
    $attempts = (int) get_transient($rate_key);
    if ($attempts >= 15) {
        wp_send_json_error(['message' => 'Security notice: Too many requests detected. Please wait 5 minutes before submitting again.']);
    }
    set_transient($rate_key, $attempts + 1, 300);

    $amount = floatval($_POST['amount'] ?? 0);
    if ($amount <= 0) {
        wp_send_json_error(['message' => 'Please select a valid donation amount.']);
    }

    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name  = sanitize_text_field($_POST['last_name'] ?? '');
    $email      = sanitize_email($_POST['email'] ?? '');

    if (empty($first_name) || empty($last_name) || empty($email)) {
        wp_send_json_error(['message' => 'Please provide your first name, last name, and a valid email address.']);
    }

    $stay_in_touch = [];
    if (!empty($_POST['stay_in_touch']) && is_array($_POST['stay_in_touch'])) {
        foreach ($_POST['stay_in_touch'] as $k => $v) {
            $stay_in_touch[sanitize_key($k)] = $v === 'yes' ? 'yes' : 'no';
        }
    }

    $campaign_id = !empty($_POST['campaign_id']) ? intval($_POST['campaign_id']) : null;
    $campaign_name = '';
    if ($campaign_id) {
        $cpost = get_post($campaign_id);
        if ($cpost) $campaign_name = $cpost->post_title;
    }

    $gateway = sanitize_key($_POST['gateway'] ?? 'card');
    $currency_code = sanitize_text_field($_POST['currency_code'] ?? 'GBP');

    $donation_data = [
        'donor_type'     => sanitize_key($_POST['donor_type'] ?? 'individual'),
        'org_name'       => sanitize_text_field($_POST['org_name'] ?? ''),
        'event_name'     => sanitize_text_field($_POST['event_name'] ?? ''),
        'title'          => sanitize_text_field($_POST['title'] ?? ''),
        'first_name'     => $first_name,
        'last_name'      => $last_name,
        'email'          => $email,
        'phone'          => sanitize_text_field($_POST['phone'] ?? ''),
        'address_1'      => sanitize_text_field($_POST['address1'] ?? ''),
        'address_2'      => sanitize_text_field($_POST['address2'] ?? ''),
        'city'           => sanitize_text_field($_POST['city'] ?? ''),
        'county'         => sanitize_text_field($_POST['county'] ?? ''),
        'postcode'       => sanitize_text_field($_POST['postcode'] ?? ''),
        'country'        => sanitize_text_field($_POST['country'] ?? 'United Kingdom'),
        'comments'       => sanitize_textarea_field($_POST['comments'] ?? ''),
        'gift_aid'       => (!empty($_POST['gift_aid']) && $_POST['gift_aid'] === 'yes') ? 1 : 0,
        'stay_in_touch'  => $stay_in_touch,
        'project'        => sanitize_text_field($_POST['project'] ?? 'General Donation'),
        'campaign_id'    => $campaign_id,
        'campaign_name'  => $campaign_name,
        'source_package' => sanitize_text_field($_POST['source_package'] ?? $_POST['package'] ?? ''),
        'frequency'      => sanitize_key($_POST['frequency'] ?? 'single'),
        'donation_type'  => sanitize_text_field($_POST['donation_type'] ?? 'Sadaqah'),
        'amount'         => $amount,
        'fee_amount'     => floatval($_POST['fee_amount'] ?? 0),
        'total_amount'   => floatval($_POST['total_amount'] ?? $amount),
        'currency'       => sanitize_text_field($_POST['currency'] ?? '£'),
        'gateway'        => $gateway,
        'txn_id'         => 'ARYANA_' . strtoupper(wp_generate_password(8, false)),
        'status'         => 'completed',
    ];

    // 1. Enforce WooCommerce Active Requirement
    if (!function_exists('ads_is_wc_active') || !ads_is_wc_active()) {
        wp_send_json_error(['message' => 'WooCommerce is currently deactivated. Donations require WooCommerce to be active. Please activate WooCommerce in WordPress Admin > Plugins.']);
    }

    $order = null;
    $wc_order_id = 0;

    // Real WooCommerce Stripe Card Processing
    if ($gateway === 'card') {
        if (!function_exists('ads_is_stripe_gateway_active') || !ads_is_stripe_gateway_active()) {
            wp_send_json_error(['message' => 'Credit/Debit Card payments are currently unavailable because the Stripe payment gateway plugin is deactivated or disabled in WooCommerce.']);
        }

        $payment_method_id = sanitize_text_field($_POST['payment_method_id'] ?? '');

        // If client is confirming an action (3D Secure)
        if (!empty($_POST['confirm_intent_id'])) {
            $intent_id = sanitize_text_field($_POST['confirm_intent_id']);
            $keys = function_exists('ads_get_wc_stripe_keys') ? ads_get_wc_stripe_keys() : [];
            $check_res = wp_remote_get('https://api.stripe.com/v1/payment_intents/' . $intent_id, [
                'headers' => ['Authorization' => 'Bearer ' . ($keys['sk'] ?? '')]
            ]);
            $intent = json_decode(wp_remote_retrieve_body($check_res), true);
            if (empty($intent['status']) || $intent['status'] !== 'succeeded') {
                wp_send_json_error(['message' => 'Card authorization failed or cancelled. Please try again.']);
            }
            $donation_data['txn_id'] = $intent['id'];
        } else {
            if (empty($payment_method_id) && !empty($_POST['card_number'])) {
                $raw_num = preg_replace('/\D/', '', sanitize_text_field($_POST['card_number']));
                $exp_m   = intval($_POST['card_exp_month'] ?? 0);
                $exp_y   = intval($_POST['card_exp_year'] ?? 0);
                if ($exp_y < 100) $exp_y += 2000;
                $cvc     = preg_replace('/\D/', '', sanitize_text_field($_POST['card_cvc'] ?? ''));

                // 1. Strict Luhn validation (reject fake card numbers)
                if (!ads_is_valid_luhn($raw_num)) {
                    wp_send_json_error(['message' => 'The card number provided is invalid. Please check your card number and try again.']);
                }

                // 2. Strict Expiration Month validation
                if ($exp_m < 1 || $exp_m > 12) {
                    wp_send_json_error(['message' => 'Invalid expiration month. Please enter a valid month (01 - 12).']);
                }

                // 3. Strict Expiration Year & Past date check
                $cur_year  = intval(date('Y'));
                $cur_month = intval(date('n'));
                if ($exp_y < $cur_year || ($exp_y === $cur_year && $exp_m < $cur_month)) {
                    wp_send_json_error(['message' => "Your card's expiration date is in the past. Please enter a valid future date (MM / YY)."]);
                }
                if ($exp_y > ($cur_year + 25)) {
                    wp_send_json_error(['message' => 'The expiration year is invalid. Please enter a valid expiration date.']);
                }

                // 4. Strict CVC validation
                if (strlen($cvc) < 3 || strlen($cvc) > 4) {
                    wp_send_json_error(['message' => 'Please enter a valid 3 or 4 digit CVC security code.']);
                }

                $keys = function_exists('ads_get_wc_stripe_keys') ? ads_get_wc_stripe_keys() : [];
                if (empty($keys['sk'])) {
                    wp_send_json_error(['message' => 'Stripe secret key is not configured in WooCommerce settings.']);
                }

                $is_test_mode = !empty($keys['mode']) && $keys['mode'] === 'test';
                $test_token_map = [
                    '4242424242424242' => 'tok_visa',
                    '4000000000000002' => 'tok_visa_chargeDeclined',
                    '5555555555554444' => 'tok_mastercard',
                    '378282246310005'  => 'tok_amex',
                    '6011111111111117' => 'tok_discover',
                    '30569309025904'   => 'tok_diners',
                    '3530111333300000' => 'tok_jcb',
                    '4000000000003023' => 'tok_threeDSecureRequired',
                ];

                if ($is_test_mode && isset($test_token_map[$raw_num])) {
                    $payment_method_id = $test_token_map[$raw_num];
                } else {
                    $pm_res = wp_remote_post('https://api.stripe.com/v1/payment_methods', [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $keys['sk'],
                            'Content-Type'  => 'application/x-www-form-urlencoded',
                        ],
                        'body' => [
                            'type' => 'card',
                            'card[number]'    => $raw_num,
                            'card[exp_month]' => $exp_m,
                            'card[exp_year]'  => $exp_y,
                            'card[cvc]'       => $cvc,
                        ],
                        'timeout' => 20,
                    ]);

                    if (is_wp_error($pm_res)) {
                        wp_send_json_error(['message' => 'Unable to connect to payment processor. Please check your network and try again.']);
                    }

                    $pm_body = json_decode(wp_remote_retrieve_body($pm_res), true);
                    if (!empty($pm_body['error'])) {
                        wp_send_json_error(['message' => $pm_body['error']['message'] ?? 'Invalid card details. Please check and try again.']);
                    }

                    $payment_method_id = $pm_body['id'] ?? '';
                }
            }

            if (empty($payment_method_id)) {
                wp_send_json_error(['message' => 'Please enter your card number, expiration date, and CVC code.']);
            }

            // Create initial WooCommerce order
            if (function_exists('ads_wc_create_background_order')) {
                $wc_order_id = ads_wc_create_background_order(0, $donation_data, $_POST['items'] ?? []);
                if ($wc_order_id) {
                    $order = wc_get_order($wc_order_id);
                }
            }

            if (!$order) {
                wp_send_json_error(['message' => 'Unable to create WooCommerce donation order. Please verify WooCommerce settings.']);
            }

            // Process real Stripe charge
            $stripe_result = ads_wc_process_stripe_payment($order, $payment_method_id, $donation_data['total_amount'], $currency_code, $donation_data);

            if (is_wp_error($stripe_result)) {
                if ($order) {
                    $order->update_status('failed', $stripe_result->get_error_message());
                }
                wp_send_json_error(['message' => $stripe_result->get_error_message()]);
            }

            // 3D Secure verification needed
            if (!empty($stripe_result['status']) && $stripe_result['status'] === 'requires_action') {
                wp_send_json_success([
                    'requires_action' => true,
                    'client_secret'   => $stripe_result['client_secret'],
                    'intent_id'       => $stripe_result['intent_id'],
                ]);
            }

            $donation_data['txn_id'] = $stripe_result['txn_id'];
        }
    } elseif ($gateway === 'paypal') {
        if (!function_exists('ads_is_paypal_gateway_active') || !ads_is_paypal_gateway_active()) {
            wp_send_json_error(['message' => 'PayPal payments are currently unavailable because the PayPal payment gateway plugin is deactivated or disabled in WooCommerce.']);
        }

        // Create pending WooCommerce order for PayPal
        if (function_exists('ads_wc_create_background_order')) {
            $donation_data['status'] = 'pending';
            $wc_order_id = ads_wc_create_background_order(0, $donation_data, $_POST['items'] ?? []);
            if ($wc_order_id) {
                $order = wc_get_order($wc_order_id);
                if ($order) {
                    $order->update_status('pending', 'Awaiting PayPal payment confirmation.');
                }
            }
        }
    }

    $donation_id = ads_insert_donation($donation_data);

    if (!$donation_id) {
        wp_send_json_error(['message' => 'Unable to save donation record. Please try again.']);
    }

    // Attach donation ID to WooCommerce order
    if ($order) {
        $order->update_meta_data('_ads_donation_id', $donation_id);
        $order->save();
    } elseif ($wc_order_id <= 0 && class_exists('WooCommerce') && function_exists('ads_wc_create_background_order')) {
        $wc_order_id = ads_wc_create_background_order($donation_id, $donation_data, $_POST['items'] ?? []);
    }

    wp_send_json_success([
        'donation_id' => $donation_id,
        'txn_id'      => $donation_data['txn_id'],
        'wc_order_id' => $wc_order_id,
        'donor_name'  => trim($first_name . ' ' . $last_name),
        'email'       => $email,
        'project'     => $donation_data['project'],
        'frequency'   => ucfirst($donation_data['frequency']),
        'gift_aid'    => $donation_data['gift_aid'] ? 'Yes (+25% UK Reclaim)' : 'No',
        'gateway'     => $donation_data['gateway'] === 'paypal' ? 'PayPal' : 'Credit / Debit Card (Stripe)',
        'total'       => number_format($donation_data['total_amount'], 2),
        'currency'    => $donation_data['currency'],
        'message'     => 'Thank you for your generous support! Your donation has been recorded successfully.',
    ]);
}



// ==========================================================================
// SHORTCODE 1: MASTER DONATION FORM ([aryana_donation_form])
// ==========================================================================
add_shortcode('aryana_donation_form', function ($atts) {
    ob_start();
    include ADS_PLUGIN_DIR . 'includes/modern-donation-template.php';
    return ob_get_clean();
});


// ==========================================================================
// SHORTCODE 2: QUICK DONATE HERO WIDGET ([quick_donate_bar], [ads_quick_donate])
// Two-Box layout: Left box (Projects + Single/Monthly) & Right box (Amount + Type + Donate)
// ==========================================================================
function ads_render_quick_donate_widget_html($atts = []) {
    wp_enqueue_style('ads-donation-css');
    wp_enqueue_script('ads-donation-js');

    ob_start();
    $matrix = ads_get_projects_matrix();
    $currency = get_option('ads_currency_symbol', '£');

    // Admin options with sensible defaults
    $saved_h1         = get_option('ads_qd_heading_1', 'Project & Frequency');
    $saved_h2         = get_option('ads_qd_heading_2', 'Amount & Donation Type');
    $saved_lbl_proj   = get_option('ads_qd_label_project', 'Select Project / Appeal');
    $saved_lbl_freq   = get_option('ads_qd_label_frequency', 'Giving Frequency');
    $saved_lbl_amount = get_option('ads_qd_label_amount', 'Amount');
    $saved_lbl_type   = get_option('ads_qd_label_type', 'Donation Type');
    $saved_btn_text   = get_option('ads_qd_btn_text', 'Donate Now');
    $saved_btn_bg     = get_option('ads_qd_btn_bg', '#038B5C');
    $saved_btn_color  = get_option('ads_qd_btn_color', '#ffffff');
    $saved_def_proj   = get_option('ads_qd_default_project', 'Water - Well');
    $saved_def_freq   = get_option('ads_qd_default_freq', 'single');
    $saved_show_freq  = get_option('ads_qd_show_freq', 'yes');
    $enabled_projs    = get_option('ads_qd_enabled_projects', []);

    // Filter projects if admin specified an enabled list
    $display_matrix = $matrix;
    if (!empty($enabled_projs) && is_array($enabled_projs)) {
        $filtered = [];
        foreach ($enabled_projs as $p_key) {
            if (isset($matrix[$p_key])) {
                $filtered[$p_key] = $matrix[$p_key];
            }
        }
        if (!empty($filtered)) {
            $display_matrix = $filtered;
        }
    }

    // Shortcode attribute overrides
    $atts = shortcode_atts([
        'heading_1'       => $saved_h1,
        'heading_2'       => $saved_h2,
        'label_project'   => $saved_lbl_proj,
        'label_frequency' => $saved_lbl_freq,
        'label_amount'    => $saved_lbl_amount,
        'label_type'      => $saved_lbl_type,
        'btn_text'        => $saved_btn_text,
        'btn_bg'          => $saved_btn_bg,
        'btn_color'       => $saved_btn_color,
        'project'         => $saved_def_proj,
        'frequency'       => $saved_def_freq,
        'show_frequency'  => $saved_show_freq,
    ], $atts, 'ads_quick_donate');

    $selected_proj = (!empty($atts['project']) && isset($display_matrix[$atts['project']]))
        ? $atts['project']
        : (array_keys($display_matrix)[0] ?? 'Water - Well');

    $first_data = $display_matrix[$selected_proj] ?? ['types' => ['Sadaqah', 'Zakat', 'Lillah'], 'fixed_single' => 1200, 'fixed_monthly' => 25];
    $init_freq = ($atts['frequency'] === 'monthly') ? 'monthly' : 'single';
    $init_amount = ($init_freq === 'monthly')
        ? (($first_data['fixed_monthly'] ?? 0) > 0 ? $first_data['fixed_monthly'] : 25)
        : (($first_data['fixed_single'] ?? 0) > 0 ? $first_data['fixed_single'] : 100);

    // Style & Appearance Customizer
    $card_bg      = get_option('ads_qd_card_bg', '#ffffff');
    $card_border  = get_option('ads_qd_card_border', '#e2e8f0');
    $card_radius  = get_option('ads_qd_card_radius', '14px');
    $card_shadow  = get_option('ads_qd_card_shadow', 'elevated');
    $max_width    = get_option('ads_qd_max_width', '100%');
    $accent_color = get_option('ads_qd_accent_color', '#038B5C');
    $title_color  = get_option('ads_qd_title_color', '#0f172a');
    $label_color  = get_option('ads_qd_label_color', '#64748b');
    $input_bg     = get_option('ads_qd_input_bg', '#ffffff');
    $input_border = get_option('ads_qd_input_border', '#cbd5e1');
    $input_radius = get_option('ads_qd_input_radius', '10px');
    $btn_radius   = get_option('ads_qd_btn_radius', '8px');
    $custom_css   = get_option('ads_qd_custom_css', '');

    // Frequency Tabs Style Options
    $freq_active_bg     = get_option('ads_qd_freq_active_bg', '#038B5C');
    $freq_active_color  = get_option('ads_qd_freq_active_color', '#ffffff');
    $freq_inactive_col  = get_option('ads_qd_freq_inactive_color', '#64748b');
    $freq_container_bg  = get_option('ads_qd_freq_container_bg', '#f1f5f9');

    $shadow_css = '0 10px 30px rgba(0, 0, 0, 0.08)';
    if ($card_shadow === 'subtle') {
        $shadow_css = '0 4px 14px rgba(0, 0, 0, 0.05)';
    } elseif ($card_shadow === 'flat') {
        $shadow_css = 'none';
    }

    $hex = ltrim($accent_color, '#');
    if (strlen($hex) === 3) {
        $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
        $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
        $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
    } elseif (strlen($hex) === 6) {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    } else {
        $r = 3; $g = 139; $b = 92;
    }
    $accent_glow = "rgba($r, $g, $b, 0.15)";
    ?>
    <style>
        .ads-quick-hero-wrapper {
            max-width: <?php echo esc_attr($max_width); ?>;
        }
        .ads-quick-hero-card {
            background-color: <?php echo esc_attr($card_bg); ?> !important;
            border-color: <?php echo esc_attr($card_border); ?> !important;
            border-radius: <?php echo esc_attr($card_radius); ?> !important;
            box-shadow: <?php echo esc_attr($shadow_css); ?> !important;
        }
        .ads-quick-step-num {
            background-color: <?php echo esc_attr($accent_color); ?> !important;
        }
        .ads-quick-col-title {
            color: <?php echo esc_attr($title_color); ?> !important;
        }
        .ads-quick-label {
            color: <?php echo esc_attr($label_color); ?> !important;
        }
        .ads-quick-hero-card .ads-quick-select,
        .ads-quick-hero-card .ads-quick-amount-box,
        .ads-quick-hero-card .ads-quick-amount-input {
            background-color: <?php echo esc_attr($input_bg); ?> !important;
            border-color: <?php echo esc_attr($input_border); ?> !important;
            border-radius: <?php echo esc_attr($input_radius); ?> !important;
        }
        .ads-quick-hero-card .ads-quick-select:focus,
        .ads-quick-hero-card .ads-quick-amount-box:focus-within,
        .ads-quick-hero-card .ads-quick-amount-input:focus {
            border-color: <?php echo esc_attr($accent_color); ?> !important;
            box-shadow: 0 0 0 3px <?php echo esc_attr($accent_glow); ?> !important;
        }
        .ads-quick-select-arrow svg {
            fill: <?php echo esc_attr($accent_color); ?> !important;
        }
        .ads-quick-freq-tabs {
            background-color: <?php echo esc_attr($freq_container_bg); ?> !important;
        }
        .ads-quick-hero-card .ads-quick-freq-btn {
            background: transparent !important;
            background-color: transparent !important;
            color: <?php echo esc_attr($freq_inactive_col); ?> !important;
            box-shadow: none !important;
        }
        .ads-quick-hero-card .ads-quick-freq-btn.active {
            background: <?php echo esc_attr($freq_active_bg); ?> !important;
            background-color: <?php echo esc_attr($freq_active_bg); ?> !important;
            color: <?php echo esc_attr($freq_active_color); ?> !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15) !important;
        }
        .ads-quick-submit-btn {
            border-radius: <?php echo esc_attr($btn_radius); ?> !important;
        }
        <?php if (!empty($custom_css)): ?>
        <?php echo wp_strip_all_tags($custom_css); ?>
        <?php endif; ?>
    </style>
    <div class="aryana-donation-wrapper ads-quick-hero-wrapper">
        <div class="ads-quick-hero-card">
            <!-- Box 1: Left / First Box -->
            <div class="ads-quick-col ads-quick-col-left">
                <div class="ads-quick-col-header">
                    <span class="ads-quick-step-num">1</span>
                    <span class="ads-quick-col-title"><?php echo esc_html($atts['heading_1']); ?></span>
                </div>

                <div class="ads-quick-field-group">
                    <label class="ads-quick-label"><?php echo esc_html($atts['label_project']); ?></label>
                    <div class="ads-quick-select-wrap">
                        <select class="ads-quick-select ads-quick-select-project">
                            <?php foreach ($display_matrix as $p_name => $p_data): ?>
                                <option value="<?php echo esc_attr($p_name); ?>" 
                                        data-single="<?php echo esc_attr($p_data['fixed_single'] ?? 0); ?>" 
                                        data-monthly="<?php echo esc_attr($p_data['fixed_monthly'] ?? 0); ?>"
                                        <?php selected($p_name, $selected_proj); ?>>
                                    <?php echo esc_html($p_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="ads-quick-select-arrow">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="#038B5C" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
                        </span>
                    </div>
                </div>

                <?php if ($atts['show_frequency'] !== 'no'): ?>
                <div class="ads-quick-field-group">
                    <label class="ads-quick-label"><?php echo esc_html($atts['label_frequency']); ?></label>
                    <div class="ads-quick-freq-tabs">
                        <button type="button" class="ads-quick-freq-btn <?php echo ($init_freq === 'single') ? 'active' : ''; ?>" data-freq="single">
                            <span>Single Payment</span>
                        </button>
                        <button type="button" class="ads-quick-freq-btn <?php echo ($init_freq === 'monthly') ? 'active' : ''; ?>" data-freq="monthly">
                            <span>Regular Payment</span>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Box 2: Right / Opposite Box -->
            <div class="ads-quick-col ads-quick-col-right">
                <div class="ads-quick-col-header">
                    <span class="ads-quick-step-num">2</span>
                    <span class="ads-quick-col-title"><?php echo esc_html($atts['heading_2']); ?></span>
                </div>

                <div class="ads-quick-fields-row">
                    <div class="ads-quick-field-group ads-quick-amount-col">
                        <label class="ads-quick-label"><?php echo esc_html($atts['label_amount']); ?> (<?php echo esc_html($currency); ?>)</label>
                        <div class="ads-quick-amount-box">
                            <span class="ads-quick-currency-symbol"><?php echo esc_html($currency); ?></span>
                            <input type="number" step="any" min="1" class="ads-quick-input ads-quick-amount-input" value="<?php echo esc_attr($init_amount); ?>">
                        </div>
                    </div>

                    <div class="ads-quick-field-group ads-quick-type-col">
                        <label class="ads-quick-label"><?php echo esc_html($atts['label_type']); ?></label>
                        <div class="ads-quick-select-wrap">
                            <select class="ads-quick-select ads-quick-select-type">
                                <?php foreach (($first_data['types'] ?? ['Sadaqah', 'Zakat', 'Lillah']) as $t): ?>
                                    <option value="<?php echo esc_attr($t); ?>"><?php echo esc_html($t); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="ads-quick-select-arrow">
                                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="#038B5C" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="ads-quick-action-wrap">
                    <button type="button" class="ads-quick-submit-btn" style="background: <?php echo esc_attr($atts['btn_bg']); ?>; color: <?php echo esc_attr($atts['btn_color']); ?>;">
                        <span><?php echo esc_html($atts['btn_text']); ?></span>
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('quick_donate_bar', 'ads_render_quick_donate_widget_html');
add_shortcode('ads_quick_donate', 'ads_render_quick_donate_widget_html');
add_shortcode('ads_quick_donate_bar', 'ads_render_quick_donate_widget_html');


// ==========================================================================
// SHORTCODE 3: PROJECT DONATION CARD ([project_donation_card])
// ==========================================================================
add_shortcode('project_donation_card', function ($atts) {
    $atts = shortcode_atts([
        'project' => 'Water - Well',
        'heading' => 'Give Water, Save Lives',
        'subtitle' => 'Access to clean water protects health, restores dignity, and saves lives.',
        'items_json' => '',
    ], $atts, 'project_donation_card');

    $project = sanitize_text_field($atts['project']);
    $heading = sanitize_text_field($atts['heading']);
    $subtitle = sanitize_text_field($atts['subtitle']);
    $currency = get_option('ads_currency_symbol', '£');

    // Default Items
    $items = [
        ['amount' => 600, 'label' => 'Water Pump', 'impact' => 'Could provide clean drinking water to a community in an emergency.'],
        ['amount' => 1200, 'label' => 'Water Well', 'impact' => 'Installs a deep solar water well for families in remote Afghan villages.'],
        ['amount' => 3600, 'label' => 'Community Tank', 'impact' => 'Builds a high-capacity solar water storage well for an entire village.'],
    ];

    if (!empty($atts['items_json'])) {
        $decoded = json_decode(htmlspecialchars_decode($atts['items_json']), true);
        if (is_array($decoded) && !empty($decoded)) {
            $items = $decoded;
        }
    }

    ob_start();
    ?>
    <div class="aryana-donation-wrapper">
        <div class="ads-project-card" data-project="<?php echo esc_attr($project); ?>">
            <h3 class="ads-card-title"><?php echo esc_html($heading); ?></h3>
            <div class="ads-card-subtitle"><?php echo esc_html($subtitle); ?></div>

            <!-- Single / Regular Toggle -->
            <div class="ads-card-freq-toggle">
                <button type="button" class="ads-card-freq-btn active" data-freq="single">Single Payment</button>
                <button type="button" class="ads-card-freq-btn" data-freq="monthly">Regular Payment</button>
            </div>

            <!-- Amount Pills Grid -->
            <div class="ads-card-pills-grid">
                <?php foreach ($items as $idx => $it): ?>
                    <div class="ads-card-pill <?php echo $idx === 0 ? 'active' : ''; ?>" 
                         data-amount="<?php echo esc_attr($it['amount']); ?>"
                         data-impact="<?php echo esc_attr($it['impact'] ?? ''); ?>">
                        <span class="ads-pill-val"><?php echo esc_html($currency . $it['amount']); ?></span>
                        <?php if (!empty($it['label'])): ?>
                            <span class="ads-pill-sub"><?php echo esc_html($it['label']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Impact Box -->
            <div class="ads-card-impact-box">
                <?php echo esc_html($items[0]['impact'] ?? 'Your donation provides life-saving relief.'); ?>
            </div>

            <!-- Other Amount Line -->
            <div class="ads-card-other-wrap">
                <span class="ads-card-other-label"><?php echo esc_html($currency); ?> Other</span>
                <input type="number" step="1" min="1" class="ads-card-other-input" placeholder="Custom amount">
            </div>

            <!-- Donation Type Dropdown -->
            <div class="ads-card-select-wrap">
                <select class="ads-card-select">
                    <option value="Sadaqah">Sadaqah</option>
                    <option value="Zakat">Zakat</option>
                    <option value="Lillah">Lillah</option>
                    <option value="General Donation">General Donation</option>
                </select>
            </div>

            <!-- Donate Button -->
            <div class="ads-card-btn-wrap">
                <button type="button" class="ads-card-donate-btn">
                    Donate 💧
                </button>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
});
