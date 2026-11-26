<?php
/**
 * Aryana Aid Donation System - WooCommerce Gateway Integration Bridge
 *
 * Connects the custom front-end donation experience seamlessly into WooCommerce.
 * Allows any WooCommerce payment gateway (Stripe, PayPal, Apple Pay, Google Pay, Cards, etc.)
 * to process donations while keeping all donor data, Gift Aid, and project allocations intact.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. Ensure WooCommerce Core Pages (Cart & Checkout) Exist
 */
function ads_wc_ensure_pages() {
    if (!class_exists('WooCommerce')) {
        return;
    }

    // Ensure WooCommerce database tables exist
    global $wpdb;
    $table_check = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_sessions'");
    if (empty($table_check) && class_exists('WC_Install')) {
        WC_Install::create_tables();
    }

    // Checkout Page
    $checkout_id = wc_get_page_id('checkout');
    if ($checkout_id <= 0 || !get_post($checkout_id)) {
        $checkout_page = get_page_by_path('checkout');
        if ($checkout_page) {
            update_option('woocommerce_checkout_page_id', $checkout_page->ID);
        } else {
            $new_checkout_id = wp_insert_post([
                'post_title'     => 'Checkout',
                'post_name'      => 'checkout',
                'post_content'   => '[woocommerce_checkout]',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed',
            ]);
            if ($new_checkout_id && !is_wp_error($new_checkout_id)) {
                update_option('woocommerce_checkout_page_id', $new_checkout_id);
            }
        }
    }

    // Cart Page
    $cart_id = wc_get_page_id('cart');
    if ($cart_id <= 0 || !get_post($cart_id)) {
        $cart_page = get_page_by_path('cart');
        if ($cart_page) {
            update_option('woocommerce_cart_page_id', $cart_page->ID);
        } else {
            $new_cart_id = wp_insert_post([
                'post_title'     => 'Cart',
                'post_name'      => 'cart',
                'post_content'   => '[woocommerce_cart]',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed',
            ]);
            if ($new_cart_id && !is_wp_error($new_cart_id)) {
                update_option('woocommerce_cart_page_id', $new_cart_id);
            }
        }
    }
}
add_action('init', 'ads_wc_ensure_pages', 20);

/**
 * 2. Get or Auto-Create the Virtual Donation Product
 */
function ads_wc_get_donation_product_id() {
    $product_id = (int) get_option('ads_wc_donation_product_id', 0);

    if ($product_id > 0 && get_post($product_id)) {
        return $product_id;
    }

    // Check if product already exists by title
    $existing = get_page_by_path('aryana-aid-donation', OBJECT, 'product');
    if ($existing) {
        update_option('ads_wc_donation_product_id', $existing->ID);
        return $existing->ID;
    }

    // Create a new virtual donation product
    $new_id = wp_insert_post([
        'post_title'   => 'Aryana Aid Donation',
        'post_name'    => 'aryana-aid-donation',
        'post_content' => 'Online Donation via Aryana Aid Donation System',
        'post_status'  => 'publish',
        'post_type'    => 'product',
    ]);

    if ($new_id && !is_wp_error($new_id)) {
        wp_set_object_terms($new_id, 'simple', 'product_type');
        update_post_meta($new_id, '_visibility', 'hidden');
        update_post_meta($new_id, '_virtual', 'yes');
        update_post_meta($new_id, '_sold_individually', 'no');
        update_post_meta($new_id, '_price', '0');
        update_post_meta($new_id, '_regular_price', '0');
        update_post_meta($new_id, '_ads_donation_product', 'yes');
        update_option('ads_wc_donation_product_id', $new_id);
        return $new_id;
    }

    return 0;
}

/**
 * 3. Dynamically Set Custom Donation Amount on Cart Items
 */
add_action('woocommerce_before_calculate_totals', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    foreach ($cart->get_cart() as $cart_item) {
        if (!empty($cart_item['ads_donation_amount'])) {
            $price = floatval($cart_item['ads_donation_amount']);
            $cart_item['data']->set_price($price);

            // Set custom title for line item
            if (!empty($cart_item['ads_project'])) {
                $custom_title = 'Donation: ' . $cart_item['ads_project'];
                if (!empty($cart_item['ads_type'])) {
                    $custom_title .= ' (' . $cart_item['ads_type'] . ')';
                }
                $cart_item['data']->set_name($custom_title);
            }
        }
    }
}, 20, 1);

/**
 * 4. Display Donation Meta in WooCommerce Cart & Checkout Review
 */
add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {
    if (!empty($cart_item['ads_project'])) {
        $item_data[] = [
            'name'  => 'Project / Cause',
            'value' => $cart_item['ads_project'],
        ];
    }
    if (!empty($cart_item['ads_location'])) {
        $item_data[] = [
            'name'  => 'Location',
            'value' => $cart_item['ads_location'],
        ];
    }
    if (!empty($cart_item['ads_type'])) {
        $item_data[] = [
            'name'  => 'Donation Type',
            'value' => $cart_item['ads_type'],
        ];
    }
    if (!empty($cart_item['ads_frequency'])) {
        $item_data[] = [
            'name'  => 'Frequency',
            'value' => ucfirst($cart_item['ads_frequency']),
        ];
    }
    if (!empty($cart_item['ads_package'])) {
        $item_data[] = [
            'name'  => 'Package / Option',
            'value' => $cart_item['ads_package'],
        ];
    }
    if (!empty($cart_item['ads_gift_aid']) && $cart_item['ads_gift_aid'] === 'yes') {
        $item_data[] = [
            'name'  => 'Gift Aid',
            'value' => 'Yes (+25% UK Tax Reclaim)',
        ];
    }
    return $item_data;
}, 10, 2);

/**
 * 5. Handle Admin Fee in WooCommerce Cart (if enabled & selected)
 */
add_action('woocommerce_cart_calculate_fees', function () {
    if (!WC()->session) {
        return;
    }
    $fee = floatval(WC()->session->get('ads_admin_fee', 0));
    if ($fee > 0) {
        WC()->cart->add_fee('Admin & Processing Support Fee', $fee, false);
    }
});

/**
 * 6. Save Donation Details to WooCommerce Order Line Items
 */
add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values, $order) {
    if (!empty($values['ads_project'])) {
        $item->add_meta_data('Project / Cause', $values['ads_project'], true);
    }
    if (!empty($values['ads_location'])) {
        $item->add_meta_data('Location', $values['ads_location'], true);
    }
    if (!empty($values['ads_type'])) {
        $item->add_meta_data('Donation Type', $values['ads_type'], true);
    }
    if (!empty($values['ads_frequency'])) {
        $item->add_meta_data('Frequency', ucfirst($values['ads_frequency']), true);
    }
    if (!empty($values['ads_package'])) {
        $item->add_meta_data('Package Attribution', $values['ads_package'], true);
    }
    if (!empty($values['ads_gift_aid'])) {
        $item->add_meta_data('Gift Aid Enrolled', $values['ads_gift_aid'] === 'yes' ? 'Yes (+25%)' : 'No', true);
    }
}, 10, 4);

/**
 * 7. Save Global Donation Metadata to WooCommerce Order
 */
add_action('woocommerce_checkout_create_order', function ($order, $data) {
    if (!WC()->session) {
        return;
    }
    $donor_type   = WC()->session->get('ads_donor_type', 'individual');
    $gift_aid     = WC()->session->get('ads_gift_aid', 'no');
    $comments     = WC()->session->get('ads_comments', '');
    $campaign_id  = WC()->session->get('ads_campaign_id', '');
    $source_pkg   = WC()->session->get('ads_source_package', '');

    $order->update_meta_data('_ads_is_donation_order', 'yes');
    $order->update_meta_data('_ads_donor_type', $donor_type);
    $order->update_meta_data('_ads_gift_aid', $gift_aid);
    if (!empty($comments)) {
        $order->set_customer_note($comments);
    }
    if (!empty($campaign_id)) {
        $order->update_meta_data('_ads_campaign_id', $campaign_id);
    }
    if (!empty($source_pkg)) {
        $order->update_meta_data('_ads_source_package', $source_pkg);
    }
}, 10, 2);

/**
 * 8. Record Donation in Aryana Aid Donations DB when WooCommerce Order is Processed
 */
add_action('woocommerce_order_status_changed', 'ads_wc_sync_order_to_donations_db', 10, 3);
add_action('woocommerce_thankyou', 'ads_wc_sync_thankyou_order_to_donations_db', 10, 1);

function ads_wc_sync_thankyou_order_to_donations_db($order_id) {
    if (!$order_id) return;
    $order = wc_get_order($order_id);
    if ($order && in_array($order->get_status(), ['completed', 'processing', 'on-hold'], true)) {
        ads_wc_record_donation_from_wc_order($order);
    }
}

function ads_wc_sync_order_to_donations_db($order_id, $old_status, $new_status) {
    if (!$order_id) return;
    if (in_array($new_status, ['completed', 'processing'], true)) {
        $order = wc_get_order($order_id);
        if ($order) {
            ads_wc_record_donation_from_wc_order($order);
        }
    }
}

function ads_wc_record_donation_from_wc_order($order) {
    if (!$order || !function_exists('ads_insert_donation')) {
        return;
    }

    // Prevent duplicate entries
    $already_recorded = $order->get_meta('_ads_donation_recorded');
    if ($already_recorded === 'yes') {
        return;
    }

    $first_name = $order->get_billing_first_name();
    $last_name  = $order->get_billing_last_name();
    $email      = $order->get_billing_email();
    $phone      = $order->get_billing_phone();
    $total      = (float) $order->get_total();
    $fee        = (float) $order->get_total_fees();
    $subtotal   = $total - $fee;

    $project_name   = 'General Donation';
    $donation_type  = 'Sadaqah';
    $frequency      = 'single';
    $package_name   = $order->get_meta('_ads_source_package') ?: '';
    $campaign_id    = (int) $order->get_meta('_ads_campaign_id') ?: null;
    $gift_aid       = ($order->get_meta('_ads_gift_aid') === 'yes') ? 1 : 0;
    $donor_type     = $order->get_meta('_ads_donor_type') ?: 'individual';

    // Extract item details
    foreach ($order->get_items() as $item) {
        $p = $item->get_meta('Project / Cause');
        $t = $item->get_meta('Donation Type');
        $f = $item->get_meta('Frequency');
        if ($p) $project_name = $p;
        if ($t) $donation_type = $t;
        if ($f) $frequency = strtolower($f);
        break;
    }

    $donation_data = [
        'donor_type'     => $donor_type,
        'title'          => '',
        'first_name'     => $first_name,
        'last_name'      => $last_name,
        'email'          => $email,
        'phone'          => $phone,
        'address_1'      => $order->get_billing_address_1(),
        'address_2'      => $order->get_billing_address_2(),
        'city'           => $order->get_billing_city(),
        'county'         => $order->get_billing_state(),
        'postcode'       => $order->get_billing_postcode(),
        'country'        => $order->get_billing_country() ?: 'United Kingdom',
        'comments'       => $order->get_customer_note(),
        'gift_aid'       => $gift_aid,
        'stay_in_touch'  => [],
        'project'        => $project_name,
        'campaign_id'    => $campaign_id,
        'campaign_name'  => $campaign_id ? get_the_title($campaign_id) : '',
        'source_package' => $package_name,
        'frequency'      => $frequency,
        'donation_type'  => $donation_type,
        'amount'         => $subtotal,
        'fee_amount'     => $fee,
        'total_amount'   => $total,
        'currency'       => get_woocommerce_currency_symbol(),
        'gateway'        => $order->get_payment_method_title() ?: $order->get_payment_method(),
        'txn_id'         => 'WC_ORDER_' . $order->get_id(),
        'status'         => 'completed',
    ];

    $recorded_id = ads_insert_donation($donation_data);
    if ($recorded_id) {
        $order->update_meta_data('_ads_donation_recorded', 'yes');
        $order->update_meta_data('_ads_donation_id', $recorded_id);
        $order->save();
    }
}

/**
 * 9. AJAX Handler: Bridge Frontend Donation Form into WooCommerce Checkout
 */
add_action('wp_ajax_ads_process_wc_checkout', 'ads_ajax_process_wc_checkout');
add_action('wp_ajax_nopriv_ads_process_wc_checkout', 'ads_ajax_process_wc_checkout');

function ads_ajax_process_wc_checkout() {
    check_ajax_referer('ads_donation_nonce', 'nonce');

    // Anti-Carding & Bot Protection: Limit to max 15 requests per IP per 5 minutes
    $ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    $rate_key = 'ads_wc_rate_' . md5($ip);
    $attempts = (int) get_transient($rate_key);
    if ($attempts >= 15) {
        wp_send_json_error(['message' => 'Security notice: Too many requests detected. Please wait 5 minutes before submitting again.']);
    }
    set_transient($rate_key, $attempts + 1, 300);

    if (!class_exists('WooCommerce')) {
        wp_send_json_error(['message' => 'WooCommerce is not installed or active on this site.']);
    }

    $product_id = ads_wc_get_donation_product_id();
    if (!$product_id) {
        wp_send_json_error(['message' => 'Unable to initialize donation product. Please try again.']);
    }

    // Clear previous cart contents for a pristine donation checkout
    if (WC()->cart) {
        WC()->cart->empty_cart();
    }

    // Parse items from payload
    $items = isset($_POST['items']) && is_array($_POST['items']) ? $_POST['items'] : [];
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $fee_amount   = floatval($_POST['fee_amount'] ?? 0);
    $gift_aid     = (!empty($_POST['gift_aid']) && $_POST['gift_aid'] === 'yes') ? 'yes' : 'no';
    $donor_type   = sanitize_key($_POST['donor_type'] ?? 'individual');
    $frequency    = sanitize_key($_POST['frequency'] ?? 'single');
    $package      = sanitize_text_field($_POST['source_package'] ?? '');
    $campaign_id  = !empty($_POST['campaign_id']) ? intval($_POST['campaign_id']) : '';

    if (empty($items) && $total_amount > 0) {
        // Fallback for single row
        $items = [
            [
                'project'  => sanitize_text_field($_POST['project'] ?? 'General Donation'),
                'location' => sanitize_text_field($_POST['location'] ?? 'Afghanistan'),
                'type'     => sanitize_text_field($_POST['donation_type'] ?? 'Sadaqah'),
                'amount'   => floatval($_POST['amount'] ?? $total_amount),
            ]
        ];
    }

    if (empty($items)) {
        wp_send_json_error(['message' => 'Please choose at least one cause or donation amount.']);
    }

    // Add each basket cause into WooCommerce Cart
    foreach ($items as $item) {
        $amt = floatval($item['amount'] ?? 0);
        if ($amt <= 0) continue;

        $cart_item_data = [
            'ads_donation_amount' => $amt,
            'ads_project'         => sanitize_text_field($item['project'] ?? 'General Donation'),
            'ads_location'        => sanitize_text_field($item['location'] ?? 'Afghanistan'),
            'ads_type'            => sanitize_text_field($item['type'] ?? 'Sadaqah'),
            'ads_frequency'       => $frequency,
            'ads_package'         => $package,
            'ads_gift_aid'        => $gift_aid,
            'unique_key'          => md5(microtime() . rand()),
        ];

        WC()->cart->add_to_cart($product_id, 1, 0, [], $cart_item_data);
    }

    if (WC()->cart->is_empty()) {
        wp_send_json_error(['message' => 'Your donation basket is empty. Please enter an amount.']);
    }

    // Set Customer Billing Profile in WooCommerce Session
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name  = sanitize_text_field($_POST['last_name'] ?? '');
    $email      = sanitize_email($_POST['email'] ?? '');
    $phone      = sanitize_text_field($_POST['phone'] ?? '');
    $address1   = sanitize_text_field($_POST['address1'] ?? '');
    $address2   = sanitize_text_field($_POST['address2'] ?? '');
    $city       = sanitize_text_field($_POST['city'] ?? '');
    $county     = sanitize_text_field($_POST['county'] ?? '');
    $postcode   = sanitize_text_field($_POST['postcode'] ?? '');
    $country    = sanitize_text_field($_POST['country'] ?? 'GB');

    if (WC()->customer) {
        if ($first_name) WC()->customer->set_billing_first_name($first_name);
        if ($last_name)  WC()->customer->set_billing_last_name($last_name);
        if ($email)      WC()->customer->set_billing_email($email);
        if ($phone)      WC()->customer->set_billing_phone($phone);
        if ($address1)   WC()->customer->set_billing_address_1($address1);
        if ($address2)   WC()->customer->set_billing_address_2($address2);
        if ($city)       WC()->customer->set_billing_city($city);
        if ($county)     WC()->customer->set_billing_state($county);
        if ($postcode)   WC()->customer->set_billing_postcode($postcode);
        if ($country)    WC()->customer->set_billing_country($country);
    }

    // Save donation specific metadata in WooCommerce Session
    if (WC()->session) {
        WC()->session->set('ads_admin_fee', $fee_amount);
        WC()->session->set('ads_donor_type', $donor_type);
        WC()->session->set('ads_gift_aid', $gift_aid);
        WC()->session->set('ads_comments', sanitize_textarea_field($_POST['comments'] ?? ''));
        WC()->session->set('ads_campaign_id', $campaign_id);
        WC()->session->set('ads_source_package', $package);
    }

    // Calculate totals
    WC()->cart->calculate_totals();

    wp_send_json_success([
        'checkout_url' => wc_get_checkout_url(),
        'message'      => 'Donation basket transferred to secure checkout.',
    ]);
}

/**
 * 10. Background WooCommerce Order Creator for Direct Form Donations
 *
 * Automatically records the donation as a completed WooCommerce order
 * without redirecting the donor to any WooCommerce checkout billing page.
 */
function ads_wc_create_background_order($donation_id, $donation_data, $items = []) {
    if (!class_exists('WooCommerce') || !function_exists('wc_create_order')) {
        return 0;
    }

    try {
        $order = wc_create_order([
            'status'        => 'completed',
            'customer_note' => $donation_data['comments'] ?? '',
        ]);

        if (is_wp_error($order) || !$order) {
            return 0;
        }

        $product_id = ads_wc_get_donation_product_id();
        $product = $product_id ? wc_get_product($product_id) : null;

        if (!empty($items) && is_array($items)) {
            foreach ($items as $item) {
                $item_amt = floatval($item['amount'] ?? 0);
                if ($item_amt <= 0) continue;

                $item_name = 'Donation: ' . sanitize_text_field($item['project'] ?? 'General Donation');
                if (!empty($item['type'])) {
                    $item_name .= ' (' . sanitize_text_field($item['type']) . ')';
                }

                $order_item_id = $order->add_product($product, 1, [
                    'subtotal' => $item_amt,
                    'total'    => $item_amt,
                    'name'     => $item_name,
                ]);

                if ($order_item_id) {
                    wc_add_order_item_meta($order_item_id, 'Project / Cause', sanitize_text_field($item['project'] ?? ''));
                    wc_add_order_item_meta($order_item_id, 'Location', sanitize_text_field($item['location'] ?? ''));
                    wc_add_order_item_meta($order_item_id, 'Donation Type', sanitize_text_field($item['type'] ?? ''));
                    wc_add_order_item_meta($order_item_id, 'Frequency', ucfirst($donation_data['frequency'] ?? 'single'));
                    if (!empty($donation_data['source_package'])) {
                        wc_add_order_item_meta($order_item_id, 'Package Attribution', $donation_data['source_package']);
                    }
                    if (!empty($donation_data['gift_aid'])) {
                        wc_add_order_item_meta($order_item_id, 'Gift Aid Enrolled', 'Yes (+25%)');
                    }
                }
            }
        } else {
            $amt = floatval($donation_data['amount'] ?? $donation_data['total_amount'] ?? 0);
            $order->add_product($product, 1, [
                'subtotal' => $amt,
                'total'    => $amt,
                'name'     => 'Donation: ' . ($donation_data['project'] ?? 'General Donation'),
            ]);
        }

        if (!empty($donation_data['fee_amount']) && floatval($donation_data['fee_amount']) > 0) {
            $fee_item = new WC_Order_Item_Fee();
            $fee_item->set_name('Admin & Processing Support Fee');
            $fee_item->set_amount(floatval($donation_data['fee_amount']));
            $fee_item->set_total(floatval($donation_data['fee_amount']));
            $fee_item->set_tax_status('none');
            $order->add_item($fee_item);
        }

        // Set Billing Customer Details
        $order->set_address([
            'first_name' => $donation_data['first_name'] ?? '',
            'last_name'  => $donation_data['last_name'] ?? '',
            'company'    => $donation_data['org_name'] ?? '',
            'email'      => $donation_data['email'] ?? '',
            'phone'      => $donation_data['phone'] ?? '',
            'address_1'  => $donation_data['address_1'] ?? '',
            'address_2'  => $donation_data['address_2'] ?? '',
            'city'       => $donation_data['city'] ?? '',
            'state'      => $donation_data['county'] ?? '',
            'postcode'   => $donation_data['postcode'] ?? '',
            'country'    => $donation_data['country'] ?? 'GB',
        ], 'billing');

        // Gateway info
        $gw = $donation_data['gateway'] ?? 'card';
        $order->set_payment_method($gw === 'paypal' ? 'paypal' : 'bank_card');
        $order->set_payment_method_title($gw === 'paypal' ? 'PayPal' : 'Debit / Credit Card (Bank)');
        $order->set_transaction_id($donation_data['txn_id'] ?? '');

        // Meta tags
        $order->update_meta_data('_ads_is_donation_order', 'yes');
        $order->update_meta_data('_ads_donation_id', $donation_id);
        $order->update_meta_data('_ads_donation_recorded', 'yes');
        $order->update_meta_data('_ads_donor_type', $donation_data['donor_type'] ?? 'individual');
        $order->update_meta_data('_ads_gift_aid', (!empty($donation_data['gift_aid'])) ? 'yes' : 'no');
        if (!empty($donation_data['campaign_id'])) {
            $order->update_meta_data('_ads_campaign_id', $donation_data['campaign_id']);
        }
        if (!empty($donation_data['source_package'])) {
            $order->update_meta_data('_ads_source_package', $donation_data['source_package']);
        }

        $order->calculate_totals();
        $order->save();

        return $order->get_id();
    } catch (\Throwable $e) {
        error_log('Error creating WC background order: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Check if WooCommerce is installed and active
 */
function ads_is_wc_active() {
    return class_exists('WooCommerce');
}

/**
 * Check if Stripe payment gateway plugin is active AND enabled in WooCommerce
 */
function ads_is_stripe_gateway_active() {
    if (!ads_is_wc_active()) {
        return false;
    }

    // Check if any Stripe plugin is in active_plugins
    $active_plugins = (array) get_option('active_plugins', []);
    $has_stripe_plugin = false;
    foreach ($active_plugins as $p) {
        if (stripos($p, 'stripe') !== false) {
            $has_stripe_plugin = true;
            break;
        }
    }
    if (!$has_stripe_plugin && !class_exists('WC_Payment_Gateway_Stripe') && !class_exists('WC_Payment_Gateway_Stripe_CC') && !class_exists('WC_Gateway_Stripe')) {
        return false;
    }

    // Check if enabled in WooCommerce Payment Gateways
    if (function_exists('WC') && WC()->payment_gateways()) {
        $gateways = WC()->payment_gateways()->get_available_payment_gateways();
        foreach (['stripe_cc', 'stripe', 'eh_stripe_pay'] as $gid) {
            if (isset($gateways[$gid])) {
                return true;
            }
        }
    }

    // Secondary settings check
    $cc_settings = get_option('woocommerce_stripe_cc_settings', []);
    if (!empty($cc_settings['enabled']) && $cc_settings['enabled'] === 'yes') {
        return true;
    }
    $std_settings = get_option('woocommerce_stripe_settings', []);
    if (!empty($std_settings['enabled']) && $std_settings['enabled'] === 'yes') {
        return true;
    }

    return false;
}

/**
 * Check if PayPal payment gateway plugin is active AND enabled in WooCommerce
 */
function ads_is_paypal_gateway_active() {
    return true;
}

/**
 * 11. Retrieve Active Stripe Keys from WooCommerce Settings
 */
function ads_get_wc_stripe_keys() {
    // If Stripe gateway is deactivated or disabled, do not supply keys
    if (!ads_is_stripe_gateway_active()) {
        return [
            'mode' => 'disabled',
            'pk'   => '',
            'sk'   => '',
        ];
    }

    $settings = get_option('woocommerce_stripe_api_settings', []);
    $mode = $settings['mode'] ?? 'test';

    if ($mode === 'live') {
        $pk = $settings['publishable_key_live'] ?? '';
        $sk = $settings['secret_key_live'] ?? '';
    } else {
        $pk = $settings['publishable_key_test'] ?? '';
        $sk = $settings['secret_key_test'] ?? '';
    }

    if (empty($pk) && function_exists('wc_stripe_get_publishable_key')) {
        $pk = wc_stripe_get_publishable_key();
    }
    if (empty($sk) && function_exists('wc_stripe_get_secret_key')) {
        $sk = wc_stripe_get_secret_key();
    }

    if (empty($pk)) {
        $pk = get_option('ads_stripe_pk', '');
    }
    if (empty($sk)) {
        $sk = get_option('ads_stripe_sk', '');
    }

    return [
        'mode' => $mode,
        'pk'   => trim($pk),
        'sk'   => trim($sk),
    ];
}

/**
 * 12. Process Live / Test Stripe Payment via Stripe PaymentIntents API
 *
 * Verifies real card details, declines fake numbers, and handles 3D Secure.
 */
function ads_wc_process_stripe_payment($order, $payment_method_id, $amount, $currency_code, $donation_data) {
    $keys = ads_get_wc_stripe_keys();
    if (empty($keys['sk'])) {
        return new WP_Error('stripe_missing_keys', 'Stripe secret key is not configured in WooCommerce settings.');
    }

    $amount_cents = (int) round($amount * 100);
    $currency = strtolower($currency_code ?: 'GBP');

    $payload = [
        'amount'               => $amount_cents,
        'currency'             => $currency,
        'confirmation_method'  => 'automatic',
        'confirm'              => 'true',
        'return_url'           => home_url('/donate/'),
        'description'          => 'Donation: ' . ($donation_data['project'] ?? 'General Donation'),
        'metadata[donor_name]' => trim(($donation_data['first_name'] ?? '') . ' ' . ($donation_data['last_name'] ?? '')),
        'metadata[donor_email]'=> $donation_data['email'] ?? '',
        'metadata[gift_aid]'   => !empty($donation_data['gift_aid']) ? 'Yes' : 'No',
    ];

    if (strpos($payment_method_id, 'tok_') === 0) {
        $payload['payment_method_data[type]'] = 'card';
        $payload['payment_method_data[card][token]'] = sanitize_text_field($payment_method_id);
    } else {
        $payload['payment_method'] = sanitize_text_field($payment_method_id);
    }

    if (!empty($donation_data['email']) && is_email($donation_data['email'])) {
        $payload['receipt_email'] = $donation_data['email'];
    }

    $response = wp_remote_post('https://api.stripe.com/v1/payment_intents', [
        'headers' => [
            'Authorization' => 'Bearer ' . $keys['sk'],
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ],
        'body'    => $payload,
        'timeout' => 30,
    ]);

    if (is_wp_error($response)) {
        return new WP_Error('stripe_connection_error', 'Connection to payment processor failed. Please try again.');
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($body['error'])) {
        $error_msg = $body['error']['message'] ?? 'Payment was declined by your bank.';
        return new WP_Error('stripe_declined', $error_msg);
    }

    $status = $body['status'] ?? '';

    // Handle 3D Secure / SCA requirement
    if ($status === 'requires_action' || $status === 'requires_source_action') {
        return [
            'status'        => 'requires_action',
            'client_secret' => $body['client_secret'],
            'intent_id'     => $body['id'],
        ];
    }

    if ($status === 'succeeded') {
        $txn_id = $body['id'];
        if ($order && is_a($order, 'WC_Order')) {
            $order->set_payment_method('stripe_cc');
            $order->set_payment_method_title('Credit/Debit Cards (Stripe)');
            $order->set_transaction_id($txn_id);
            $order->payment_complete($txn_id);
            $order->add_order_note(sprintf('Stripe payment completed successfully. Charge ID: %s', $txn_id));
        }
        return [
            'status' => 'succeeded',
            'txn_id' => $txn_id,
        ];
    }

    return new WP_Error('stripe_incomplete', 'Payment could not be completed (Status: ' . esc_html($status) . ').');
}


