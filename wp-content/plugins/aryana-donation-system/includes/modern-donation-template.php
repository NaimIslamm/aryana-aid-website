<?php
if (!defined('ABSPATH')) {
    exit;
}

// Load dynamic configuration from database
$matrix = ads_get_projects_matrix();
$incoming_req_project = !empty($_GET['project']) ? sanitize_text_field(wp_unslash($_GET['project'])) : '';
if (!empty($incoming_req_project) && !isset($matrix[$incoming_req_project])) {
    $matrix[$incoming_req_project] = [
        'locations'     => ['Afghanistan', 'Pakistan', 'Where Most Needed'],
        'types'         => ['Sadaqah', 'Lillah', 'Zakat', 'Charitable Donation'],
        'fixed_single'  => 0,
        'fixed_monthly' => 0,
    ];
}
$gen = ads_get_general_settings();
$currency = $gen['currency'] ?? '£';
$currency_code = $gen['currency_code'] ?? 'GBP';

// Allow per-campaign currency override if arriving from a specific campaign box
if (!empty($_GET['currency'])) {
    $currency = sanitize_text_field($_GET['currency']);
}
if (!empty($_GET['currency_code'])) {
    $currency_code = strtoupper(sanitize_text_field($_GET['currency_code']));
}
$admin_fee_enabled = $gen['admin_fee_enabled'] === 'yes';
$admin_fee_fixed = 1.10;
$admin_fee_label = 'Please add an admin fee of ' . $currency . '1.10 to your donation.';
$admin_fee_desc = 'example: Donated ' . $currency . '300 plus ' . $currency . '1.10 = ' . $currency . '301.10';
$comments_enabled = $gen['comments_enabled'] === 'yes';
$comments_placeholder = $gen['comments_placeholder'];

$donor_types = ads_get_donor_types();
$donor_fields = ads_get_donor_fields();
$giftaid = ads_get_giftaid_settings();
$sit = ads_get_stay_in_touch_settings();
$other_ways = function_exists('ads_get_other_ways_settings') ? ads_get_other_ways_settings() : [];

$wc_pp = get_option('woocommerce_paypal_settings', []);
$paypal_email = (!empty($wc_pp['email']) && is_email($wc_pp['email'])) ? $wc_pp['email'] : get_option('ads_paypal_email', 'paypal@aryanaaid.org.uk');
$stripe_keys = function_exists('ads_get_wc_stripe_keys') ? ads_get_wc_stripe_keys() : ['pk' => ''];
$stripe_pk = $stripe_keys['pk'];
$giftaid_img = ADS_PLUGIN_URL . 'assets/images/giftaid.png?v=' . ADS_VERSION;
$payment_img = ADS_PLUGIN_URL . 'assets/images/payment-img.png';
$paypal_img = ADS_PLUGIN_URL . 'assets/images/paypal1.png';
$applepay_img = ADS_PLUGIN_URL . 'assets/images/applepay.svg';
$googlepay_img = ADS_PLUGIN_URL . 'assets/images/googlepay.svg';

$is_wc_active = function_exists('ads_is_wc_active') && ads_is_wc_active();
$is_stripe_active = function_exists('ads_is_stripe_gateway_active') && ads_is_stripe_gateway_active();
$is_paypal_active = function_exists('ads_is_paypal_gateway_active') && ads_is_paypal_gateway_active();
?>
<script src="https://js.stripe.com/v3/"></script>


<style>
/* 1. Completely hide the package attribution notice and express link checkout across all devices */
#ads-package-notice-container,
.ads-package-selected-notice,
.ads-admin-fee-desc,
#ads-payment-request-wrapper,
#ads-payment-request-button {
    display: none !important;
}

/* Gift Aid Header: Desktop vs Mobile */
.ads-giftaid-title-mobile {
    display: none !important;
}
.ads-giftaid-title-desktop {
    display: block !important;
}

@media (max-width: 768px) {
    .ads-giftaid-header {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        justify-content: flex-start !important;
        gap: 10px 14px !important;
    }
    .ads-giftaid-brand-box {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0 !important;
        margin: 0 !important;
    }
    .ads-giftaid-brand-box img {
        height: 36px !important;
        max-height: 38px !important;
        width: auto !important;
        vertical-align: middle !important;
        object-fit: contain !important;
    }
    .ads-giftaid-title-desktop {
        display: none !important;
    }
    .ads-giftaid-badge-25 {
        display: inline-flex !important;
        align-items: center !important;
        gap: 5px !important;
        font-size: 11.5px !important;
        font-weight: 700 !important;
        padding: 5px 10px !important;
        border-radius: 6px !important;
        margin: 0 !important;
        line-height: 1 !important;
    }
    .ads-giftaid-title-mobile {
        display: block !important;
        width: 100% !important;
        flex-basis: 100% !important;
        font-size: 15.5px !important;
        font-weight: 800 !important;
        color: var(--ads-dark, #0F172A) !important;
        margin: 4px 0 0 0 !important;
        line-height: 1.35 !important;
    }
}

/* Payment Method Pill Layout: 4 Columns (Card, Google Pay, Apple Pay, PayPal) */
.ads-payment-methods-grid {
    display: grid !important;
    grid-template-columns: repeat(4, 1fr) !important;
    gap: 8px !important;
    align-items: stretch !important;
    margin-bottom: 18px !important;
}
@media (max-width: 860px) {
    .ads-payment-methods-grid {
        grid-template-columns: 1fr 1fr !important;
        gap: 8px !important;
        margin-bottom: 14px !important;
    }
    .ads-pay-card-pill {
        border-radius: 8px !important;
        min-height: 48px !important;
        padding: 8px 10px !important;
    }
    /* 1. Debit or Credit Card (Top Left) */
    .ads-pay-pill-card {
        order: 1 !important;
        grid-column: span 1 !important;
        padding: 7px 9px !important;
        flex-direction: row !important;
        align-items: center !important;
        gap: 6px !important;
        min-height: 48px !important;
        border-radius: 8px !important;
    }
    .ads-pay-pill-card .ads-pay-pill-radio {
        width: 14px !important;
        height: 14px !important;
        min-width: 14px !important;
        min-height: 14px !important;
        margin-top: 0 !important;
    }
    .ads-pay-pill-card .ads-pay-pill-radio-dot {
        width: 6px !important;
        height: 6px !important;
    }
    .ads-pay-pill-card .ads-pay-pill-body {
        flex-direction: column !important;
        align-items: flex-start !important;
        justify-content: center !important;
        gap: 3px !important;
        min-width: 0 !important;
        width: 100% !important;
    }
    .ads-pay-pill-card .ads-pay-pill-title {
        font-size: 11.5px !important;
        white-space: nowrap !important;
        line-height: 1.1 !important;
    }
    .ads-pay-title-full {
        display: none !important;
    }
    .ads-pay-title-short {
        display: inline !important;
    }
    .ads-pay-pill-card .ads-pay-pill-icons {
        margin-top: 0 !important;
        gap: 2px !important;
        flex-wrap: nowrap !important;
    }
    .ads-pay-pill-card .ads-card-badge img.ads-card-badge-img,
    .ads-pay-pill-card .ads-card-badge svg {
        width: 22px !important;
        height: 14px !important;
        object-fit: contain !important;
        display: inline-block !important;
        vertical-align: middle !important;
        border-radius: 2px !important;
    }
    .ads-pay-pill-paypal .ads-wallet-btn-img,
    .ads-pay-pill-gpay .ads-wallet-btn-img,
    .ads-pay-pill-applepay .ads-wallet-btn-img {
        height: 36px !important;
        width: auto !important;
        max-width: 100% !important;
        object-fit: contain !important;
        display: block !important;
        margin: 0 auto !important;
        pointer-events: none !important;
    }

    /* 2. PayPal (Top Right) */
    .ads-pay-pill-paypal {
        order: 2 !important;
        grid-column: span 1 !important;
        min-height: 48px !important;
        padding: 7px 9px !important;
        border-radius: 8px !important;
        gap: 6px !important;
        justify-content: center !important;
        align-items: center !important;
        flex-direction: row !important;
    }

    /* 3. Google Pay (Bottom Left) */
    .ads-pay-pill-gpay {
        order: 3 !important;
        grid-column: span 1 !important;
        min-height: 48px !important;
        padding: 7px 9px !important;
        border-radius: 8px !important;
        gap: 6px !important;
        justify-content: center !important;
        align-items: center !important;
        flex-direction: row !important;
    }

    /* 4. Apple Pay (Bottom Right) */
    .ads-pay-pill-applepay {
        order: 4 !important;
        grid-column: span 1 !important;
        min-height: 48px !important;
        padding: 7px 9px !important;
        border-radius: 8px !important;
        gap: 6px !important;
        justify-content: center !important;
        align-items: center !important;
        flex-direction: row !important;
    }

    .ads-pay-pill-btn-label {
        display: flex !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        justify-content: center !important;
        line-height: 1.15 !important;
        text-align: left !important;
    }
    .ads-pay-sub-label {
        display: block !important;
        font-size: 8.5px !important;
        font-weight: 600 !important;
        color: #64748B !important;
        text-transform: uppercase !important;
        letter-spacing: 0.3px !important;
        line-height: 1 !important;
        white-space: nowrap !important;
    }
    .ads-pay-brand-label {
        font-size: 12.5px !important;
        font-weight: 700 !important;
        letter-spacing: -0.1px !important;
        line-height: 1.15 !important;
        white-space: nowrap !important;
    }
    .ads-pay-pill-gpay svg {
        width: 24px !important;
        height: 24px !important;
        min-width: 24px !important;
        min-height: 24px !important;
    }
    .ads-pay-pill-paypal svg {
        width: 24px !important;
        height: 24px !important;
        min-width: 24px !important;
        min-height: 24px !important;
    }
    .ads-pay-pill-applepay svg {
        width: 22px !important;
        height: 22px !important;
        min-width: 22px !important;
        min-height: 22px !important;
    }
}
.ads-pay-card-pill {
    position: relative !important;
    display: flex !important;
    border: 1.5px solid #CBD5E1 !important;
    border-radius: 10px !important;
    background: #FFFFFF !important;
    cursor: pointer !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
    box-sizing: border-box !important;
    user-select: none !important;
}
.ads-pay-card-pill:hover {
    border-color: #94A3B8 !important;
    background: #F8FAFC !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08) !important;
}
.ads-pay-card-pill.active {
    box-shadow: 0 3px 12px rgba(3, 139, 92, 0.15) !important;
}

/* 1. Card Option */
.ads-pay-pill-card {
    padding: 9px 12px !important;
    flex-direction: row !important;
    align-items: flex-start !important;
    gap: 9px !important;
    min-height: 56px !important;
}
.ads-pay-pill-card.active {
    border-color: #038B5C !important;
    background: #F0FDF4 !important;
}
.ads-pay-pill-card.active .ads-pay-pill-title {
    color: #065F46 !important;
}
.ads-pay-pill-radio {
    width: 16px !important;
    height: 16px !important;
    min-width: 16px !important;
    min-height: 16px !important;
    border-radius: 50% !important;
    border: 1.5px solid #CBD5E1 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    margin-top: 2px !important;
    flex-shrink: 0 !important;
    background: #FFFFFF !important;
    transition: all 0.2s ease !important;
}
.ads-pay-pill-card.active .ads-pay-pill-radio {
    border-color: #038B5C !important;
}
.ads-pay-pill-radio-dot {
    width: 8px !important;
    height: 8px !important;
    border-radius: 50% !important;
    background: #038B5C !important;
    display: none !important;
}
.ads-pay-pill-card.active .ads-pay-pill-radio-dot {
    display: block !important;
}
.ads-pay-pill-body {
    flex: 1 !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 5px !important;
    min-width: 0 !important;
}
.ads-pay-pill-title {
    font-size: 13px !important;
    font-weight: 700 !important;
    line-height: 1.2 !important;
    color: #1E293B !important;
    white-space: nowrap !important;
}
.ads-pay-title-full {
    display: inline !important;
}
.ads-pay-title-short {
    display: none !important;
}
.ads-pay-pill-icons {
    display: flex !important;
    align-items: center !important;
    gap: 4px !important;
    flex-wrap: nowrap !important;
    justify-content: flex-start !important;
}
.ads-card-badge {
    display: inline-flex !important;
    align-items: center !important;
    border-radius: 3px !important;
    line-height: 1 !important;
}
.ads-card-badge img.ads-card-badge-img {
    width: 26px !important;
    height: 16px !important;
    object-fit: contain !important;
    display: inline-block !important;
    vertical-align: middle !important;
    border-radius: 2px !important;
}
@media (max-width: 480px) {
    .ads-card-badge img.ads-card-badge-img {
        width: 20px !important;
        height: 13px !important;
    }
}

/* 2. Google Pay Pill */
.ads-pay-pill-gpay {
    border-radius: 10px !important;
    padding: 8px 10px !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    min-height: 56px !important;
}
.ads-pay-pill-gpay svg {
    width: 32px !important;
    height: 32px !important;
    min-width: 32px !important;
    min-height: 32px !important;
}
.ads-pay-pill-gpay.active {
    border-color: #4285F4 !important;
    background: #F8FAFF !important;
    box-shadow: 0 3px 12px rgba(66, 133, 244, 0.18) !important;
}

/* 3. Apple Pay Pill */
.ads-pay-pill-applepay {
    border-radius: 10px !important;
    padding: 8px 10px !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    min-height: 56px !important;
}
.ads-pay-pill-applepay svg {
    width: 28px !important;
    height: 28px !important;
    min-width: 28px !important;
    min-height: 28px !important;
}
.ads-pay-pill-applepay.active {
    border-color: #000000 !important;
    background: #FAFAFA !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.15) !important;
}

/* 4. PayPal Pill */
.ads-pay-pill-paypal {
    border-radius: 10px !important;
    padding: 8px 10px !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    min-height: 56px !important;
}
.ads-pay-pill-paypal svg {
    width: 30px !important;
    height: 30px !important;
    min-width: 30px !important;
    min-height: 30px !important;
}
.ads-pay-pill-paypal.active {
    border-color: #003087 !important;
    background: #F0F7FD !important;
    box-shadow: 0 3px 12px rgba(0, 112, 186, 0.22) !important;
}

/* Wallet Button Image (Google Pay, Apple Pay, PayPal) */
.ads-wallet-btn-img {
    height: 46px !important;
    width: auto !important;
    max-width: 100% !important;
    object-fit: contain !important;
    display: block !important;
    margin: 0 auto !important;
    pointer-events: none !important;
}

/* Common Pill Label Styles */
.ads-pay-pill-btn-label {
    display: flex !important;
    flex-direction: column !important;
    align-items: flex-start !important;
    justify-content: center !important;
    line-height: 1.15 !important;
    text-align: left !important;
}
.ads-pay-sub-label {
    font-size: 10px !important;
    font-weight: 600 !important;
    color: #64748B !important;
    text-transform: uppercase !important;
    letter-spacing: 0.4px !important;
    display: block !important;
    line-height: 1 !important;
}
.ads-pay-brand-label {
    font-size: 14.5px !important;
    font-weight: 700 !important;
    display: block !important;
    white-space: nowrap !important;
    line-height: 1.2 !important;
}
    white-space: nowrap !important;
}

/* Submit Button Variations */
.ads-checkout-submit-btn.ads-btn-paypal {
    background: #0070BA !important;
    border-color: #005EA6 !important;
    color: #FFFFFF !important;
}
.ads-checkout-submit-btn.ads-btn-paypal:hover {
    background: #005EA6 !important;
    box-shadow: 0 4px 16px rgba(0, 112, 186, 0.3) !important;
}
.ads-checkout-submit-btn.ads-btn-gpay {
    background: #202124 !important;
    border-color: #000000 !important;
    color: #FFFFFF !important;
}
.ads-checkout-submit-btn.ads-btn-gpay:hover {
    background: #000000 !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25) !important;
}
.ads-checkout-submit-btn.ads-btn-applepay {
    background: #000000 !important;
    border-color: #000000 !important;
    color: #FFFFFF !important;
}
.ads-checkout-submit-btn.ads-btn-applepay:hover {
    background: #1A1A1A !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3) !important;
}

/* 2. Desktop Defaults */
.ads-mobile-remove-btn {
    display: none !important;
}
.ads-desktop-remove-wrap {
    display: flex !important;
}
.ads-field-label-row {
    display: block;
}

/* 3. Tablet & Mobile Responsive Architecture (<= 992px) */
@media (max-width: 992px) {
    /* Hide the desktop 6th-column remove cell completely */
    .ads-desktop-remove-wrap {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Cause Card Container */
    .ads-basket-item-row {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 12px 10px !important;
        padding: 14px 14px 16px !important;
        background: #FFFFFF !important;
        border: 1.5px solid #E2E8F0 !important;
        border-radius: 12px !important;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04) !important;
        position: relative !important;
        margin-bottom: 0 !important;
    }

    /* Header Row inside Project Wrap: Label on Left, Remove Button on Right */
    .ads-field-project-wrap {
        grid-column: span 2 !important;
        width: 100% !important;
    }

    .ads-field-label-row {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        width: 100% !important;
        margin-bottom: 6px !important;
    }

    .ads-field-label-row .ads-field-label-mobile {
        display: block !important;
        margin: 0 !important;
        font-size: 11.5px !important;
        font-weight: 700 !important;
        color: #64748B !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }

    /* Sleek Mobile Remove Icon Button in Card Header (Icon only, no text) */
    .ads-mobile-remove-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 26px !important;
        height: 26px !important;
        min-width: 26px !important;
        max-width: 26px !important;
        min-height: 26px !important;
        max-height: 26px !important;
        padding: 0 !important;
        margin: 0 !important;
        background: #FFF1F2 !important;
        border: 1px solid #FECDD3 !important;
        color: #E11D48 !important;
        border-radius: 6px !important;
        cursor: pointer !important;
        line-height: 1 !important;
        transition: all 0.2s ease !important;
        box-shadow: 0 1px 2px rgba(225, 29, 72, 0.08) !important;
    }

    .ads-mobile-remove-btn:hover,
    .ads-mobile-remove-btn:active {
        background: #E11D48 !important;
        color: #FFFFFF !important;
        border-color: #BE123C !important;
        box-shadow: 0 2px 6px rgba(225, 29, 72, 0.24) !important;
        transform: translateY(-1px);
    }

    .ads-mobile-remove-btn svg {
        width: 14px !important;
        height: 14px !important;
        fill: currentColor !important;
    }

    .ads-mobile-remove-btn .ads-remove-btn-text {
        display: none !important;
    }

    /* Grid layout for remaining fields */
    .ads-field-location-wrap {
        grid-column: span 1 !important;
    }

    .ads-field-type-wrap {
        grid-column: span 1 !important;
    }

    .ads-field-amount-wrap {
        grid-column: span 1 !important;
    }

    .ads-field-comments-wrap {
        grid-column: span 1 !important;
    }

    /* If no comments, let amount span full width */
    .ads-basket-item-row.ads-no-comments .ads-field-amount-wrap {
        grid-column: span 2 !important;
    }

    /* Mobile Direct Bank Transfer & Other Ways to Support: Sleek & Compact */
    .ads-other-ways-container {
        margin-top: 20px !important;
        padding-top: 16px !important;
    }
    .ads-other-ways-title {
        font-size: 16px !important;
        margin-bottom: 2px !important;
    }
    .ads-other-ways-subtitle {
        font-size: 11.5px !important;
        margin: 0 0 12px !important;
        line-height: 1.35 !important;
    }
    .ads-other-ways-grid {
        grid-template-columns: 1fr !important;
        gap: 10px !important;
    }
    .ads-bank-info-card {
        padding: 12px 12px !important;
        border-radius: 10px !important;
        border-width: 1px !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03) !important;
    }
    .ads-card-header-icon {
        gap: 8px !important;
        margin-bottom: 8px !important;
    }
    .ads-icon-circle {
        width: 28px !important;
        height: 28px !important;
        min-width: 28px !important;
    }
    .ads-icon-circle svg {
        width: 14px !important;
        height: 14px !important;
    }
    .ads-bank-card-title {
        font-size: 14.5px !important;
        font-weight: 700 !important;
        line-height: 1.2 !important;
    }
    .ads-bank-card-subtitle {
        font-size: 11px !important;
        margin-top: 1px !important;
    }
    .ads-bank-instruction {
        font-size: 11.5px !important;
        margin: 0 0 6px !important;
        line-height: 1.35 !important;
    }
    .ads-bank-details-box {
        padding: 8px 10px !important;
        border-radius: 6px !important;
    }
    .ads-bank-detail-row {
        padding: 4px 0 !important;
        gap: 8px !important;
    }
    .ads-bank-detail-label {
        font-size: 11px !important;
        font-weight: 600 !important;
    }
    .ads-bank-detail-value {
        font-size: 11.5px !important;
        font-weight: 700 !important;
    }
    .ads-iban-value {
        font-size: 10.5px !important;
        word-break: break-all !important;
    }
    .ads-post-address-box {
        padding: 8px 10px !important;
        font-size: 11.5px !important;
        line-height: 1.4 !important;
        border-radius: 6px !important;
    }
}

/* 4. Small Phones (<= 480px) */
@media (max-width: 480px) {
    .ads-basket-item-row {
        padding: 12px 10px 14px !important;
        gap: 10px 8px !important;
        border-radius: 10px !important;
    }

    .ads-mobile-remove-btn {
        padding: 3px 8px !important;
        font-size: 11px !important;
    }
}
</style>

<div class="ads-modern-scope">
    <div class="ads-modern-wrapper">
        <div class="ads-modern-card">

            <?php
            $raw_req_freq = strtolower(sanitize_key($_GET['freq'] ?? ($_GET['frequency'] ?? '')));
            $is_initial_monthly = in_array($raw_req_freq, ['monthly', 'regular', 'recurring', 'give_monthly', 'give-monthly']);
            ?>
            <!-- 2. Modern Frequency Toggle -->
            <div class="ads-freq-bar-wrapper">
                <div class="ads-freq-switcher" id="ads-freq-switcher">
                    <button type="button" class="ads-freq-tab <?php echo !$is_initial_monthly ? 'active' : ''; ?>" data-freq="single">
                        <svg viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
                        Give Once
                    </button>
                    <button type="button" class="ads-freq-tab <?php echo $is_initial_monthly ? 'active' : ''; ?>" data-freq="monthly">
                        <svg viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                        Give Monthly
                    </button>
                </div>
            </div>

            <!-- 3. Form Inner Body -->
            <form id="ads-modern-donation-form" class="ads-form-inner" onsubmit="return false;">

                <!-- Section: Projects Selection -->
                <div class="ads-section-header">
                    <h2 class="ads-section-title-modern">
                        <span class="ads-step-number">1</span> Select Your Causes & Amount
                    </h2>
                </div>

                <div class="ads-basket-container">
                    <div class="ads-basket-col-labels" style="<?php echo !$comments_enabled ? 'grid-template-columns: 2.2fr 1.6fr 1.6fr 1.4fr 44px;' : ''; ?>">
                        <div>Project / Appeal</div>
                        <div>Location</div>
                        <div>Donation Type</div>
                        <div>Amount (<span class="ads-col-curr-symbol"><?php echo esc_html($currency); ?></span>)</div>
                        <?php if ($comments_enabled): ?>
                            <div>Comments (Optional)</div>
                        <?php endif; ?>
                        <div></div>
                    </div>

                    <div id="ads-package-notice-container"></div>

                    <div class="ads-basket-rows-list" id="ads-basket-items">
                        <!-- Dynamic Basket Row Template -->
                    </div>

                    <div class="ads-basket-toolbar">
                        <button type="button" class="ads-add-project-btn" id="ads-btn-add-row">
                            <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                            Add Another Project
                        </button>
                    </div>
                </div>

                <!-- Admin Fee Checkbox (Dynamic) -->
                <?php if ($admin_fee_enabled): 
                    $admin_fee_default = ($gen['admin_fee_default_checked'] ?? 'no') === 'yes';
                ?>
                    <div class="ads-admin-fee-card <?php echo $admin_fee_default ? 'active' : ''; ?>" id="ads-admin-fee-card">
                        <input type="checkbox" id="ads-admin-fee-checkbox" class="ads-custom-checkbox" <?php checked($admin_fee_default, true); ?>>
                        <div class="ads-admin-fee-text">
                            <div class="ads-admin-fee-label"><?php echo esc_html($admin_fee_label); ?></div>
                        </div>
                        <div class="ads-admin-fee-value" id="ads-admin-fee-display">+<?php echo esc_html($currency); ?>1.10</div>
                    </div>
                <?php endif; ?>

                <!-- Total Summary Bar -->
                <div class="ads-total-summary-banner">
                    <div class="ads-total-summary-info">
                        <span class="ads-total-caption">Total Donation Amount</span>
                        <span class="ads-total-freq-indicator" id="ads-freq-indicator">One-off Donation</span>
                    </div>
                    <div class="ads-total-amount-display" id="ads-grand-total-display">
                        <?php echo esc_html($currency); ?>0.00
                    </div>
                </div>

                <!-- Section: Donor Details -->
                <div class="ads-section-header">
                    <h2 class="ads-section-title-modern">
                        <span class="ads-step-number">2</span> Your Details
                    </h2>
                </div>

                <!-- Donor Types (Configurable) -->
                <div class="ads-donor-type-pills">
                    <?php 
                    $first_type = true;
                    foreach ($donor_types as $t_key => $t_data): 
                        if (($t_data['enabled'] ?? 'yes') === 'yes'):
                    ?>
                        <label class="ads-donor-type-option <?php echo $first_type ? 'active' : ''; ?>">
                            <input type="radio" name="donor_type" value="<?php echo esc_attr($t_key); ?>" <?php checked($first_type, true); ?> style="display:none;">
                            <?php echo esc_html($t_data['label']); ?>
                        </label>
                    <?php 
                        $first_type = false;
                        endif; 
                    endforeach; 
                    ?>
                </div>

                <!-- Conditional Fields: Event Name -->
                <?php if (!empty($donor_fields['event_name']) && ($donor_fields['event_name']['enabled'] ?? 'yes') === 'yes'): ?>
                    <div id="ads-event-field-wrap" class="ads-floating-field" style="display:none; margin-bottom:16px;">
                        <label><?php echo esc_html($donor_fields['event_name']['label']); ?> <?php if (($donor_fields['event_name']['required'] ?? 'no') === 'yes') echo '<span class="required">*</span>'; ?></label>
                        <input type="text" name="event_name" id="ads-event-name" class="ads-modern-input" placeholder="<?php echo esc_attr($donor_fields['event_name']['placeholder'] ?? ''); ?>">
                    </div>
                <?php endif; ?>

                <!-- Conditional Fields: Organisation Name -->
                <?php if (!empty($donor_fields['org_name']) && ($donor_fields['org_name']['enabled'] ?? 'yes') === 'yes'): ?>
                    <div id="ads-org-field-wrap" class="ads-floating-field" style="display:none; margin-bottom:16px;">
                        <label><?php echo esc_html($donor_fields['org_name']['label']); ?> <?php if (($donor_fields['org_name']['required'] ?? 'no') === 'yes') echo '<span class="required">*</span>'; ?></label>
                        <input type="text" name="org_name" id="ads-org-name" class="ads-modern-input" placeholder="<?php echo esc_attr($donor_fields['org_name']['placeholder'] ?? ''); ?>">
                    </div>
                <?php endif; ?>

                <!-- Dynamic General Fields Grid -->
                <div class="ads-form-row-3col">
                    <?php if (!empty($donor_fields['title']) && ($donor_fields['title']['enabled'] ?? 'yes') === 'yes'): ?>
                        <div class="ads-floating-field">
                            <label><?php echo esc_html($donor_fields['title']['label']); ?> <?php if (($donor_fields['title']['required'] ?? 'no') === 'yes') echo '<span class="required">*</span>'; ?></label>
                            <select name="title" id="ads-donor-title" class="ads-modern-select">
                                <?php foreach (($donor_fields['title']['options'] ?? ['Mr','Mrs','Miss','Ms','Dr','Prof','Sheikh','Other']) as $opt): ?>
                                    <option value="<?php echo esc_attr($opt); ?>"><?php echo esc_html($opt); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($donor_fields['first_name']) && ($donor_fields['first_name']['enabled'] ?? 'yes') === 'yes'): ?>
                        <div class="ads-floating-field">
                            <label><?php echo esc_html($donor_fields['first_name']['label']); ?> <?php if (($donor_fields['first_name']['required'] ?? 'no') === 'yes') echo '<span class="required">*</span>'; ?></label>
                            <input type="text" name="first_name" id="ads-first-name" class="ads-modern-input" placeholder="<?php echo esc_attr($donor_fields['first_name']['placeholder'] ?? ''); ?>" <?php if (($donor_fields['first_name']['required'] ?? 'no') === 'yes') echo 'required'; ?>>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($donor_fields['last_name']) && ($donor_fields['last_name']['enabled'] ?? 'yes') === 'yes'): ?>
                        <div class="ads-floating-field">
                            <label><?php echo esc_html($donor_fields['last_name']['label']); ?> <?php if (($donor_fields['last_name']['required'] ?? 'no') === 'yes') echo '<span class="required">*</span>'; ?></label>
                            <input type="text" name="last_name" id="ads-last-name" class="ads-modern-input" placeholder="<?php echo esc_attr($donor_fields['last_name']['placeholder'] ?? ''); ?>" <?php if (($donor_fields['last_name']['required'] ?? 'no') === 'yes') echo 'required'; ?>>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="ads-form-row-2col">
                    <?php if (!empty($donor_fields['email']) && ($donor_fields['email']['enabled'] ?? 'yes') === 'yes'): ?>
                        <div class="ads-floating-field">
                            <label><?php echo esc_html($donor_fields['email']['label']); ?> <?php if (($donor_fields['email']['required'] ?? 'no') === 'yes') echo '<span class="required">*</span>'; ?></label>
                            <input type="email" name="email" id="ads-email" class="ads-modern-input" placeholder="<?php echo esc_attr($donor_fields['email']['placeholder'] ?? ''); ?>" <?php if (($donor_fields['email']['required'] ?? 'no') === 'yes') echo 'required'; ?>>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($donor_fields['phone']) && ($donor_fields['phone']['enabled'] ?? 'yes') === 'yes'): ?>
                        <div class="ads-floating-field">
                            <label><?php echo esc_html($donor_fields['phone']['label']); ?> <?php if (($donor_fields['phone']['required'] ?? 'no') === 'yes') echo '<span class="required">*</span>'; ?></label>
                            <input type="tel" name="phone" id="ads-phone" class="ads-modern-input" placeholder="<?php echo esc_attr($donor_fields['phone']['placeholder'] ?? ''); ?>" <?php if (($donor_fields['phone']['required'] ?? 'no') === 'yes') echo 'required'; ?>>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="ads-form-row-2col">
                    <?php if (!empty($donor_fields['address1']) && ($donor_fields['address1']['enabled'] ?? 'yes') === 'yes'): ?>
                        <div class="ads-floating-field">
                            <label><?php echo esc_html($donor_fields['address1']['label']); ?> <?php if (($donor_fields['address1']['required'] ?? 'no') === 'yes') echo '<span class="required">*</span>'; ?></label>
                            <input type="text" name="address1" id="ads-address1" class="ads-modern-input" placeholder="<?php echo esc_attr($donor_fields['address1']['placeholder'] ?? ''); ?>" <?php if (($donor_fields['address1']['required'] ?? 'no') === 'yes') echo 'required'; ?>>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($donor_fields['address2']) && ($donor_fields['address2']['enabled'] ?? 'yes') === 'yes'): ?>
                        <div class="ads-floating-field">
                            <label><?php echo esc_html($donor_fields['address2']['label']); ?> <?php if (($donor_fields['address2']['required'] ?? 'no') === 'yes') echo '<span class="required">*</span>'; ?></label>
                            <input type="text" name="address2" id="ads-address2" class="ads-modern-input" placeholder="<?php echo esc_attr($donor_fields['address2']['placeholder'] ?? ''); ?>" <?php if (($donor_fields['address2']['required'] ?? 'no') === 'yes') echo 'required'; ?>>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="ads-form-row-3col">
                    <?php if (!empty($donor_fields['city']) && ($donor_fields['city']['enabled'] ?? 'yes') === 'yes'): ?>
                        <div class="ads-floating-field">
                            <label><?php echo esc_html($donor_fields['city']['label']); ?> <?php if (($donor_fields['city']['required'] ?? 'no') === 'yes') echo '<span class="required">*</span>'; ?></label>
                            <input type="text" name="city" id="ads-city" class="ads-modern-input" placeholder="<?php echo esc_attr($donor_fields['city']['placeholder'] ?? ''); ?>" <?php if (($donor_fields['city']['required'] ?? 'no') === 'yes') echo 'required'; ?>>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($donor_fields['postcode']) && ($donor_fields['postcode']['enabled'] ?? 'yes') === 'yes'): ?>
                        <div class="ads-floating-field">
                            <label><?php echo esc_html($donor_fields['postcode']['label']); ?> <?php if (($donor_fields['postcode']['required'] ?? 'no') === 'yes') echo '<span class="required">*</span>'; ?></label>
                            <input type="text" name="postcode" id="ads-postcode" class="ads-modern-input" placeholder="<?php echo esc_attr($donor_fields['postcode']['placeholder'] ?? ''); ?>" <?php if (($donor_fields['postcode']['required'] ?? 'no') === 'yes') echo 'required'; ?>>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($donor_fields['country']) && ($donor_fields['country']['enabled'] ?? 'yes') === 'yes'): ?>
                        <div class="ads-floating-field">
                            <label><?php echo esc_html($donor_fields['country']['label']); ?> <?php if (($donor_fields['country']['required'] ?? 'no') === 'yes') echo '<span class="required">*</span>'; ?></label>
                            <select name="country" id="ads-country" class="ads-modern-select">
                                <?php foreach (($donor_fields['country']['options'] ?? ['United Kingdom','United States','Canada','Australia','Germany','France','Ireland','Afghanistan','Pakistan','Other']) as $opt): ?>
                                    <option value="<?php echo esc_attr($opt); ?>" <?php selected($opt, 'United Kingdom'); ?>><?php echo esc_html($opt); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Custom Added Fields -->
                <?php 
                $standard_keys = ['title','first_name','last_name','email','phone','address1','address2','city','postcode','country','event_name','org_name'];
                foreach ($donor_fields as $f_key => $f_val):
                    if (!in_array($f_key, $standard_keys) && ($f_val['enabled'] ?? 'yes') === 'yes'):
                ?>
                    <div class="ads-floating-field" style="margin-bottom:16px;">
                        <label><?php echo esc_html($f_val['label']); ?> <?php if (($f_val['required'] ?? 'no') === 'yes') echo '<span class="required">*</span>'; ?></label>
                        <input type="<?php echo esc_attr($f_val['type'] ?? 'text'); ?>" name="<?php echo esc_attr($f_key); ?>" class="ads-modern-input" placeholder="<?php echo esc_attr($f_val['placeholder'] ?? ''); ?>" <?php if (($f_val['required'] ?? 'no') === 'yes') echo 'required'; ?>>
                    </div>
                <?php 
                    endif;
                endforeach; 
                ?>

                <!-- Section: Gift Aid (Dynamic) -->
                <?php if (($giftaid['enabled'] ?? 'yes') === 'yes'): ?>
                    <div class="ads-giftaid-card" id="ads-giftaid-box">
                        <div class="ads-giftaid-header">
                            <div class="ads-giftaid-brand-box">
                                <img src="<?php echo esc_url($giftaid_img); ?>" alt="Gift Aid" style="height:36px; width:auto;">
                                <h3 class="ads-giftaid-title ads-giftaid-title-desktop"><?php echo esc_html($giftaid['title']); ?></h3>
                            </div>
                            <div class="ads-giftaid-badge-25">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                                <?php echo esc_html($giftaid['badge']); ?>
                            </div>
                            <h3 class="ads-giftaid-title ads-giftaid-title-mobile"><?php echo esc_html($giftaid['title']); ?></h3>
                        </div>
                        <div class="ads-giftaid-body">
                            <p><?php echo wp_kses_post($giftaid['desc']); ?></p>
                            <label class="ads-giftaid-checkbox-label">
                                <?php $giftaid_default = ($giftaid['default_checked'] ?? 'no') === 'yes'; ?>
                                <input type="checkbox" id="ads-giftaid-checkbox" class="ads-custom-checkbox" <?php checked($giftaid_default, true); ?>>
                                <span><?php echo esc_html($giftaid['declaration']); ?></span>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Section: Stay in Touch (Dynamic) -->
                <?php if (($sit['enabled'] ?? 'yes') === 'yes'): ?>
                    <div class="ads-consent-section">
                        <h3 class="ads-consent-title"><?php echo esc_html($sit['title']); ?></h3>
                        <p class="ads-consent-desc"><?php echo esc_html($sit['desc']); ?></p>
                        <div class="ads-consent-pills-grid">
                            <?php foreach ($sit['channels'] as $ch_key => $ch): ?>
                                <?php if (($ch['enabled'] ?? 'yes') === 'yes'): ?>
                                    <label class="ads-consent-checkbox-card <?php echo ($ch['default_checked'] ?? 'no') === 'yes' ? 'active' : ''; ?>">
                                        <input type="checkbox" name="consent_<?php echo esc_attr($ch_key); ?>" class="ads-custom-checkbox" <?php checked($ch['default_checked'] ?? 'no', 'yes'); ?>>
                                        <span><?php echo esc_html($ch['label']); ?></span>
                                    </label>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Section: Payment Method -->
                <div class="ads-section-header">
                    <h2 class="ads-section-title-modern">
                        <span class="ads-step-number">3</span> Payment Method
                    </h2>
                </div>

                <?php if (!$is_wc_active): ?>
                    <div class="ads-wc-disabled-alert" style="background:#FEF2F2; border:1px solid #FECACA; border-left:4px solid #EF4444; border-radius:8px; padding:14px 18px; margin-bottom:18px; color:#991B1B;">
                        <strong style="font-size:14.5px; display:block; margin-bottom:3px;">⚠️ Online Checkout is Temporarily Paused</strong>
                        <span style="font-size:13px; line-height:1.5;">WooCommerce is currently deactivated. Donations require WooCommerce and an active payment gateway (Stripe / PayPal) to be processed.</span>
                    </div>
                <?php endif; ?>

                <script>
                // Instant Global Form & Payment Handlers
                window.adsIsValidLuhn = function(val) {
                    var clean = (val || '').replace(/\D/g, '');
                    if (clean.length < 13 || clean.length > 19) return false;
                    // Reject repeated identical digits e.g. 0000... or 1111...
                    if (/^(\d)\1+$/.test(clean)) return false;
                    var sum = 0, shouldDouble = false;
                    for (var i = clean.length - 1; i >= 0; i--) {
                        var digit = parseInt(clean.charAt(i), 10);
                        if (shouldDouble) {
                            digit *= 2;
                            if (digit > 9) digit -= 9;
                        }
                        sum += digit;
                        shouldDouble = !shouldDouble;
                    }
                    return (sum % 10 === 0);
                };

                window.adsIsValidExpiry = function(val) {
                    if (!val) return { valid: false, message: 'Please enter an expiry date (MM / YY).' };
                    var clean = val.replace(/\s+/g, '');
                    var parts = clean.split('/');
                    if (parts.length < 2 || !parts[0] || !parts[1]) {
                        return { valid: false, message: 'Please enter a complete expiry date in MM / YY format.' };
                    }
                    var month = parseInt(parts[0], 10);
                    var yearStr = parts[1];
                    var year = parseInt(yearStr, 10);
                    if (isNaN(month) || month < 1 || month > 12) {
                        return { valid: false, message: 'Invalid expiration month. Please enter a month between 01 and 12.' };
                    }
                    if (isNaN(year) || yearStr.length < 2) {
                        return { valid: false, message: 'Please enter a 2-digit expiration year (YY).' };
                    }
                    if (year < 100) year += 2000;

                    var now = new Date();
                    var curYear = now.getFullYear();
                    var curMonth = now.getMonth() + 1;

                    if (year < curYear || (year === curYear && month < curMonth)) {
                        return { valid: false, message: "Your card's expiration date is in the past. Please enter a valid future date." };
                    }
                    if (year > curYear + 25) {
                        return { valid: false, message: 'Invalid expiration year. Please enter a valid expiration date.' };
                    }
                    return { valid: true };
                };

                window.adsFormatCardNumber = function(input) {
                    var val = input.value.replace(/\D/g, '').substring(0, 16);
                    var parts = val.match(/.{1,4}/g);
                    input.value = parts ? parts.join(' ') : val;

                    var badge = document.getElementById('ads-card-brand-badge');
                    if (badge) {
                        if (val.startsWith('4')) badge.innerText = 'Visa';
                        else if (val.startsWith('5')) badge.innerText = 'Mastercard';
                        else if (val.startsWith('3')) badge.innerText = 'Amex';
                        else if (val.startsWith('6')) badge.innerText = 'Discover';
                        else badge.innerText = '';
                    }

                    var errBox = document.getElementById('ads-card-errors');
                    if (val.length === 16) {
                        if (!window.adsIsValidLuhn(val)) {
                            input.style.borderColor = '#EF4444';
                            if (errBox) {
                                errBox.innerText = 'Please enter a valid card number.';
                                errBox.style.display = 'block';
                            }
                        } else {
                            input.style.borderColor = '#10B981';
                            if (errBox && errBox.innerText.indexOf('card number') !== -1) {
                                errBox.style.display = 'none';
                                errBox.innerText = '';
                            }
                            var exp = document.getElementById('ads-card-expiry');
                            if (exp) exp.focus();
                        }
                    } else {
                        input.style.borderColor = '#CBD5E1';
                        if (errBox && errBox.innerText.indexOf('card number') !== -1) {
                            errBox.style.display = 'none';
                            errBox.innerText = '';
                        }
                    }
                };

                window.adsValidateCardNumber = function(input) {
                    var val = input.value.replace(/\D/g, '');
                    var errBox = document.getElementById('ads-card-errors');
                    if (!val) {
                        input.style.borderColor = '#CBD5E1';
                        return;
                    }
                    if (!window.adsIsValidLuhn(val)) {
                        input.style.borderColor = '#EF4444';
                        if (errBox) {
                            errBox.innerText = 'Please enter a valid card number.';
                            errBox.style.display = 'block';
                        }
                    } else {
                        input.style.borderColor = '#10B981';
                        if (errBox && errBox.innerText.indexOf('card number') !== -1) {
                            errBox.style.display = 'none';
                            errBox.innerText = '';
                        }
                    }
                };

                window.adsFormatExpiry = function(input) {
                    var digits = input.value.replace(/\D/g, '');
                    if (!digits) {
                        input.value = '';
                        input.style.borderColor = '#CBD5E1';
                        return;
                    }
                    // Auto-pad single digit if donor types 2-9 for month
                    if (digits.length === 1 && parseInt(digits, 10) > 1) {
                        digits = '0' + digits;
                    }
                    if (digits.length > 4) {
                        digits = digits.substring(0, 4);
                    }
                    var errBox = document.getElementById('ads-card-errors');

                    if (digits.length >= 2) {
                        var month = digits.substring(0, 2);
                        var year = digits.substring(2);
                        var mNum = parseInt(month, 10);
                        if (mNum > 12) month = '12';
                        if (mNum === 0) month = '01';

                        if (year.length > 0) {
                            input.value = month + ' / ' + year;
                            if (year.length === 2) {
                                var chk = window.adsIsValidExpiry(input.value);
                                if (!chk.valid) {
                                    input.style.borderColor = '#EF4444';
                                    if (errBox) {
                                        errBox.innerText = chk.message;
                                        errBox.style.display = 'block';
                                    }
                                } else {
                                    input.style.borderColor = '#10B981';
                                    if (errBox && (errBox.innerText.indexOf('expiration') !== -1 || errBox.innerText.indexOf('expiry') !== -1)) {
                                        errBox.style.display = 'none';
                                        errBox.innerText = '';
                                    }
                                    var cvc = document.getElementById('ads-card-cvc');
                                    if (cvc) cvc.focus();
                                }
                            } else {
                                input.style.borderColor = '#CBD5E1';
                            }
                        } else {
                            input.value = month + ' / ';
                            input.style.borderColor = '#CBD5E1';
                        }
                    } else {
                        input.value = digits;
                        input.style.borderColor = '#CBD5E1';
                    }
                };

                window.adsValidateExpiry = function(input) {
                    var errBox = document.getElementById('ads-card-errors');
                    if (!input.value.trim()) {
                        input.style.borderColor = '#CBD5E1';
                        return;
                    }
                    var chk = window.adsIsValidExpiry(input.value);
                    if (!chk.valid) {
                        input.style.borderColor = '#EF4444';
                        if (errBox) {
                            errBox.innerText = chk.message;
                            errBox.style.display = 'block';
                        }
                    } else {
                        input.style.borderColor = '#10B981';
                        if (errBox && (errBox.innerText.indexOf('expiration') !== -1 || errBox.innerText.indexOf('expiry') !== -1)) {
                            errBox.style.display = 'none';
                            errBox.innerText = '';
                        }
                    }
                };

                window.adsHandleExpiryBackspace = function(e, input) {
                    if (e.key === 'Backspace') {
                        var val = input.value;
                        if (val.endsWith(' / ') || val.endsWith('/ ') || val.endsWith('/')) {
                            e.preventDefault();
                            var digits = val.replace(/\D/g, '');
                            input.value = digits.slice(0, -1);
                        }
                    }
                };

                window.adsFormatCvc = function(input) {
                    input.value = input.value.replace(/\D/g, '').substring(0, 4);
                    var errBox = document.getElementById('ads-card-errors');
                    if (input.value.length >= 3) {
                        input.style.borderColor = '#10B981';
                        if (errBox && errBox.innerText.indexOf('CVC') !== -1) {
                            errBox.style.display = 'none';
                            errBox.innerText = '';
                        }
                    } else {
                        input.style.borderColor = '#CBD5E1';
                    }
                };

                window.adsValidateCvc = function(input) {
                    var errBox = document.getElementById('ads-card-errors');
                    var val = input.value.replace(/\D/g, '');
                    if (!val) {
                        input.style.borderColor = '#CBD5E1';
                        return;
                    }
                    if (val.length < 3 || val.length > 4) {
                        input.style.borderColor = '#EF4444';
                        if (errBox) {
                            errBox.innerText = 'Please enter a valid 3 or 4 digit CVC security code.';
                            errBox.style.display = 'block';
                        }
                    } else {
                        input.style.borderColor = '#10B981';
                        if (errBox && errBox.innerText.indexOf('CVC') !== -1) {
                            errBox.style.display = 'none';
                            errBox.innerText = '';
                        }
                    }
                };

                window.adsSelectPaymentMethod = function(method, el) {
                    var pills = document.querySelectorAll('.ads-pay-card-pill');
                    pills.forEach(function(p) { p.classList.remove('active'); });
                    if (el) el.classList.add('active');

                    var cardSec = document.getElementById('ads-card-section');
                    var ppSec = document.getElementById('ads-paypal-section');
                    var $submitBtn = $('#ads-checkout-submit');
                    var currentTotal = $('#ads-grand-total-display').text() || '£0.00';

                    if (method === 'card') {
                        if (cardSec) $(cardSec).stop(true, true).slideDown(200);
                        if (ppSec) $(ppSec).stop(true, true).slideUp(200);
                        $submitBtn.removeClass('ads-btn-paypal ads-btn-gpay ads-btn-applepay')
                            .html(`<span>Complete Donation of <strong id="ads-btn-total-val">${currentTotal}</strong></span> <svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>`);
                    } else if (method === 'paypal') {
                        if (cardSec) $(cardSec).stop(true, true).slideUp(200);
                        if (ppSec) $(ppSec).stop(true, true).slideDown(200);
                        $submitBtn.removeClass('ads-btn-gpay ads-btn-applepay').addClass('ads-btn-paypal')
                            .html(`<span>Donate with <strong>PayPal</strong> (${currentTotal})</span> <svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>`);
                    } else if (method === 'gpay') {
                        if (cardSec) $(cardSec).stop(true, true).slideUp(200);
                        if (ppSec) $(ppSec).stop(true, true).slideUp(200);
                        $submitBtn.removeClass('ads-btn-paypal ads-btn-applepay').addClass('ads-btn-gpay')
                            .html(`<span>Donate with <strong>Google Pay</strong> (${currentTotal})</span> <svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>`);
                        if (typeof activePaymentRequest !== 'undefined' && activePaymentRequest) {
                            try { activePaymentRequest.show(); } catch (err) {}
                        }
                    } else if (method === 'applepay') {
                        if (cardSec) $(cardSec).stop(true, true).slideUp(200);
                        if (ppSec) $(ppSec).stop(true, true).slideUp(200);
                        $submitBtn.removeClass('ads-btn-paypal ads-btn-gpay').addClass('ads-btn-applepay')
                            .html(`<span>Donate with <strong>Apple Pay</strong> (${currentTotal})</span> <svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>`);
                        if (typeof activePaymentRequest !== 'undefined' && activePaymentRequest) {
                            try { activePaymentRequest.show(); } catch (err) {}
                        }
                    }

                    if (typeof window.adsRecalculateTotals === 'function') {
                        window.adsRecalculateTotals();
                    }
                };
                </script>

                <!-- Express 1-Click Checkout (Stripe Link) removed as requested -->

                <div class="ads-payment-methods-grid">
                    <?php 
                    $card_pill_active = true;
                    ?>
                    <!-- 1. Debit or Credit Card -->
                    <div class="ads-pay-card-pill ads-pay-pill-card <?php echo $card_pill_active ? 'active' : ''; ?>" 
                         data-method="card"
                         onclick="adsSelectPaymentMethod('card', this)">
                        <span class="ads-pay-pill-radio"><span class="ads-pay-pill-radio-dot"></span></span>
                        <div class="ads-pay-pill-body">
                            <span class="ads-pay-pill-title"><span class="ads-pay-title-full">Debit or Credit Card</span><span class="ads-pay-title-short">Debit / Credit</span></span>
                            <div class="ads-pay-pill-icons">
                                <!-- Debit Card -->
                                <span class="ads-card-badge" title="Debit Card">
                                    <img src="<?php echo esc_url(ADS_PLUGIN_URL . 'assets/images/badge-debit.png'); ?>" alt="Debit Card" class="ads-card-badge-img">
                                </span>
                                <!-- Visa -->
                                <span class="ads-card-badge" title="Visa">
                                    <img src="<?php echo esc_url(ADS_PLUGIN_URL . 'assets/images/badge-visa.png'); ?>" alt="Visa" class="ads-card-badge-img">
                                </span>
                                <!-- Mastercard -->
                                <span class="ads-card-badge" title="Mastercard">
                                    <img src="<?php echo esc_url(ADS_PLUGIN_URL . 'assets/images/badge-mastercard.png'); ?>" alt="Mastercard" class="ads-card-badge-img">
                                </span>
                                <!-- American Express -->
                                <span class="ads-card-badge" title="American Express">
                                    <img src="<?php echo esc_url(ADS_PLUGIN_URL . 'assets/images/badge-amex.png'); ?>" alt="American Express" class="ads-card-badge-img">
                                </span>
                                <!-- Credit Card -->
                                <span class="ads-card-badge" title="Credit Card">
                                    <img src="<?php echo esc_url(ADS_PLUGIN_URL . 'assets/images/badge-credit.png'); ?>" alt="Credit Card" class="ads-card-badge-img">
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Google Pay -->
                    <div class="ads-pay-card-pill ads-pay-pill-gpay" 
                         data-method="gpay"
                         onclick="adsSelectPaymentMethod('gpay', this)"
                         title="Donate with Google Pay">
                        <img src="<?php echo esc_url(ADS_PLUGIN_URL . 'assets/images/btn-gpay.png'); ?>" alt="Donate with Google Pay" class="ads-wallet-btn-img">
                    </div>

                    <!-- 3. Apple Pay -->
                    <div class="ads-pay-card-pill ads-pay-pill-applepay" 
                         data-method="applepay"
                         onclick="adsSelectPaymentMethod('applepay', this)"
                         title="Donate with Apple Pay">
                        <img src="<?php echo esc_url(ADS_PLUGIN_URL . 'assets/images/btn-applepay.png'); ?>" alt="Donate with Apple Pay" class="ads-wallet-btn-img">
                    </div>

                    <!-- 4. PayPal -->
                    <div class="ads-pay-card-pill ads-pay-pill-paypal" 
                         data-method="paypal"
                         onclick="adsSelectPaymentMethod('paypal', this)"
                         title="Donate with PayPal">
                        <img src="<?php echo esc_url(ADS_PLUGIN_URL . 'assets/images/btn-paypal.png?v=' . filemtime(ADS_PLUGIN_DIR . 'assets/images/btn-paypal.png')); ?>" alt="Donate with PayPal" class="ads-wallet-btn-img">
                    </div>
                </div>

                <!-- Credit / Debit Card Input Box -->
                <div class="ads-credit-card-container" id="ads-card-section">
                    <div class="ads-stripe-field-group">
                        <div class="ads-card-inputs-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 4px;">
                            <div class="ads-card-field-col" style="grid-column: span 2;">
                                <label for="ads-card-number" style="display:block; font-size:13px; font-weight:700; color:#1E293B; margin-bottom:6px;">
                                    Card Number <span class="required" style="color:#EF4444;">*</span>
                                </label>
                                <div style="position:relative; display:flex; align-items:center;">
                                    <input type="text" id="ads-card-number" class="ads-input" placeholder="4242 •••• •••• 4242" maxlength="19" autocomplete="cc-number" inputmode="numeric" oninput="adsFormatCardNumber(this)" onblur="adsValidateCardNumber(this)" style="width:100%; height:48px; padding-left:42px; padding-right:60px; font-size:15px; font-family:monospace, 'Albert Sans', sans-serif; letter-spacing:1px; border:1.5px solid #CBD5E1; border-radius:8px; background:#FFFFFF; color:#0F172A; box-sizing:border-box;">
                                    <span style="position:absolute; left:12px; pointer-events:none; display:flex; align-items:center;">
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="#64748B"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
                                    </span>
                                    <span id="ads-card-brand-badge" style="position:absolute; right:12px; font-size:11.5px; font-weight:800; color:#038B5C; text-transform:uppercase;"></span>
                                </div>
                            </div>
                            <div class="ads-card-field-col" style="grid-column: span 1;">
                                <label for="ads-card-expiry" style="display:block; font-size:13px; font-weight:700; color:#1E293B; margin-bottom:6px;">
                                    Expiry Date (MM/YY) <span class="required" style="color:#EF4444;">*</span>
                                </label>
                                <input type="text" id="ads-card-expiry" class="ads-input" placeholder="MM / YY" maxlength="7" autocomplete="cc-exp" inputmode="numeric" oninput="adsFormatExpiry(this)" onkeydown="adsHandleExpiryBackspace(event, this)" onblur="adsValidateExpiry(this)" style="width:100%; height:48px; text-align:center; font-size:15px; font-family:monospace, 'Albert Sans', sans-serif; letter-spacing:1px; border:1.5px solid #CBD5E1; border-radius:8px; background:#FFFFFF; color:#0F172A; box-sizing:border-box;">
                            </div>
                            <div class="ads-card-field-col" style="grid-column: span 1;">
                                <label for="ads-card-cvc" style="display:block; font-size:13px; font-weight:700; color:#1E293B; margin-bottom:6px;">
                                    CVC / CVV <span class="required" style="color:#EF4444;">*</span>
                                </label>
                                <input type="password" id="ads-card-cvc" class="ads-input" placeholder="CVC" maxlength="4" autocomplete="cc-csc" inputmode="numeric" oninput="adsFormatCvc(this)" onblur="adsValidateCvc(this)" style="width:100%; height:48px; text-align:center; font-size:15px; font-family:monospace, 'Albert Sans', sans-serif; letter-spacing:1px; border:1.5px solid #CBD5E1; border-radius:8px; background:#FFFFFF; color:#0F172A; box-sizing:border-box;">
                            </div>
                        </div>
                        <div id="ads-stripe-card-element" class="ads-stripe-element-container" style="display:none;"></div>
                        <div id="ads-card-errors" class="ads-stripe-error-message" role="alert" style="display:none; color:#DC2626; font-size:13px; font-weight:600; margin-top:10px;"></div>
                        <div class="ads-stripe-trust-footer" style="display:flex; align-items:center; gap:7px; margin-top:12px; font-size:12.5px; color:#475569; font-weight:500;">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="#038B5C" style="flex-shrink:0;"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                            <span>Guaranteed safe &amp; secure donation checkout. Protected by 256-bit SSL encryption.</span>
                        </div>
                    </div>
                </div>

                <!-- PayPal Box -->
                <div class="ads-paypal-container" id="ads-paypal-section" style="display:none;">
                    <div class="ads-paypal-info-card">
                        <div class="ads-paypal-badge-box">
                            <img src="<?php echo esc_url(ADS_PLUGIN_URL . 'assets/images/paypal_official.svg'); ?>" alt="PayPal" style="height:28px; width:auto; display:block;">
                        </div>
                        <div class="ads-paypal-text-box">
                            <div class="ads-paypal-title-row">Pay with PayPal or Any Debit / Credit Card</div>
                            <p class="ads-paypal-desc-text">You will be redirected securely to PayPal to confirm your donation. A PayPal account is not required — you can check out with your balance, bank, or card.</p>
                        </div>
                    </div>
                    <div class="ads-paypal-security-tags">
                        <span>🔒 Safe &amp; verified donation checkout via PayPal</span>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="ads-checkout-submit-btn" id="ads-checkout-submit">
                    <span>Complete Donation of <strong id="ads-btn-total-val"><?php echo esc_html($currency); ?>0.00</strong></span>
                    <svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
                </button>

                <div class="ads-security-footnote">
                    <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                    <span>100% secure donation checkout &bull; Your contribution is fully protected</span>
                </div>
            </form>

            <!-- Celebratory Success Popup Modal -->
            <div class="ads-success-modal-backdrop" id="ads-donation-success-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="ads-modal-title">
                <div class="ads-success-modal-dialog">
                    <!-- Close button -->
                    <button type="button" class="ads-modal-close-btn" id="ads-modal-close-btn" aria-label="Close dialog">
                        <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                    </button>

                    <!-- Animated Celebration Checkmark -->
                    <div class="ads-modal-icon-wrap">
                        <div class="ads-modal-pulse-ring"></div>
                        <svg class="ads-modal-check-svg" viewBox="0 0 52 52">
                            <circle class="ads-modal-check-circle" cx="26" cy="26" r="25" fill="none"/>
                            <path class="ads-modal-check-mark" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                        </svg>
                    </div>

                    <div class="ads-modal-header">
                        <span class="ads-modal-badge">Donation Successful</span>
                        <h3 class="ads-modal-title" id="ads-modal-title">Jazakallah Khair!</h3>
                        <p class="ads-modal-dua">May Allah accept your generosity, bless your wealth, and grant you and your family abundance in this world and the hereafter.</p>
                    </div>

                    <div class="ads-modal-summary-card">
                        <div class="ads-modal-row highlight">
                            <span class="ads-modal-row-label">Total Amount Paid</span>
                            <strong class="ads-modal-row-val highlight" id="ads-modal-conf-total">£0.00</strong>
                        </div>
                        <div class="ads-modal-row">
                            <span class="ads-modal-row-label">Transaction Reference</span>
                            <span class="ads-modal-row-val ref-badge">
                                <code id="ads-modal-conf-ref">pi_0000000000</code>
                                <button type="button" class="ads-modal-copy-btn" id="ads-btn-copy-ref" title="Copy Reference" aria-label="Copy transaction reference">
                                    <svg viewBox="0 0 24 24" width="14" height="14"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z" fill="currentColor"/></svg>
                                </button>
                                <span class="ads-copy-feedback" style="display:none;">Copied!</span>
                            </span>
                        </div>
                        <div class="ads-modal-row">
                            <span class="ads-modal-row-label">Donor Name</span>
                            <span class="ads-modal-row-val" id="ads-modal-conf-donor">-</span>
                        </div>
                        <div class="ads-modal-row">
                            <span class="ads-modal-row-label">Project / Cause</span>
                            <span class="ads-modal-row-val" id="ads-modal-conf-cause">-</span>
                        </div>
                        <div class="ads-modal-row">
                            <span class="ads-modal-row-label">Donation Frequency</span>
                            <span class="ads-modal-row-val" id="ads-modal-conf-freq">Single</span>
                        </div>
                        <div class="ads-modal-row">
                            <span class="ads-modal-row-label">Payment Method</span>
                            <span class="ads-modal-row-val" id="ads-modal-conf-gateway">Credit / Debit Card (Stripe)</span>
                        </div>
                        <div class="ads-modal-row">
                            <span class="ads-modal-row-label">Gift Aid (+25% UK Boost)</span>
                            <span class="ads-modal-row-val" id="ads-modal-conf-giftaid">No</span>
                        </div>
                    </div>

                    <div class="ads-modal-receipt-note">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="#038B5C"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        <span>A full receipt and confirmation email has been sent to <strong id="ads-modal-conf-email"></strong>.</span>
                    </div>

                    <div class="ads-modal-actions">
                        <button type="button" class="ads-modal-btn-primary" id="ads-modal-donate-again">
                            <svg viewBox="0 0 24 24"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/></svg>
                            <span>Make Another Donation</span>
                        </button>
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="ads-modal-btn-secondary">
                            <span>Back to Homepage</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Other Ways to Donate -->
        <?php if (!empty($other_ways['enabled']) && $other_ways['enabled'] === 'yes'): ?>
        <div class="ads-other-ways-container">
            <h2 class="ads-section-title-modern ads-other-ways-title">
                <?php echo esc_html($other_ways['section_title'] ?? 'Other Ways To Support'); ?>
            </h2>
            <?php if (!empty($other_ways['section_subtitle'])): ?>
                <p class="ads-other-ways-subtitle"><?php echo esc_html($other_ways['section_subtitle']); ?></p>
            <?php endif; ?>

            <div class="ads-other-ways-grid">
                <!-- 1. UK Local Bank Transfer -->
                <div class="ads-bank-info-card">
                    <div class="ads-card-header-icon">
                        <div class="ads-icon-circle">
                            <svg viewBox="0 0 24 24"><path d="M4 10v7h3v-7H4zm6 0v7h3v-7h-3zM2 22h19v-3H2v3zm14-12v7h3v-7h-3zm-4.5-9L2 8v1h19V8l-9.5-5z"/></svg>
                        </div>
                        <div>
                            <h3 class="ads-bank-card-title"><?php echo esc_html($other_ways['uk_bank_title'] ?? 'UK Local Bank Transfer'); ?></h3>
                            <span class="ads-bank-card-subtitle"><?php echo esc_html($other_ways['uk_bank_subtitle'] ?? 'Within the UK • Zero Fees'); ?></span>
                        </div>
                    </div>
                    <div class="ads-bank-card-body">
                        <p class="ads-bank-instruction"><?php echo esc_html($other_ways['uk_bank_instruction'] ?? 'For donors transferring from a UK bank account. Please use only the Sort Code and Account Number below:'); ?></p>
                        <div class="ads-bank-details-box">
                            <div class="ads-bank-detail-row">
                                <span class="ads-bank-detail-label">Bank Name</span>
                                <strong class="ads-bank-detail-value"><?php echo esc_html($other_ways['bank_name'] ?? 'Barclays Bank PLC'); ?></strong>
                            </div>
                            <div class="ads-bank-detail-row">
                                <span class="ads-bank-detail-label">Account Name</span>
                                <strong class="ads-bank-detail-value"><?php echo esc_html($other_ways['account_name'] ?? 'Aryana Aid'); ?></strong>
                            </div>
                            <div class="ads-bank-detail-row">
                                <span class="ads-bank-detail-label">Sort Code</span>
                                <strong class="ads-bank-detail-value"><?php echo esc_html($other_ways['sort_code'] ?? '20-29-77'); ?></strong>
                            </div>
                            <div class="ads-bank-detail-row ads-bank-detail-row-last">
                                <span class="ads-bank-detail-label">Account Number</span>
                                <strong class="ads-bank-detail-value"><?php echo esc_html($other_ways['account_number'] ?? '90526184'); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. International Bank Transfer -->
                <div class="ads-bank-info-card">
                    <div class="ads-card-header-icon">
                        <div class="ads-icon-circle">
                            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                        </div>
                        <div>
                            <h3 class="ads-bank-card-title"><?php echo esc_html($other_ways['intl_bank_title'] ?? 'International Bank Transfer'); ?></h3>
                            <span class="ads-bank-card-subtitle"><?php echo esc_html($other_ways['intl_bank_subtitle'] ?? 'Outside the UK • Worldwide Wire'); ?></span>
                        </div>
                    </div>
                    <div class="ads-bank-card-body">
                        <p class="ads-bank-instruction"><?php echo esc_html($other_ways['intl_bank_instruction'] ?? 'For international donors transferring from outside the UK. Please use the SWIFT/BIC code and IBAN below:'); ?></p>
                        <div class="ads-bank-details-box">
                            <div class="ads-bank-detail-row">
                                <span class="ads-bank-detail-label">Bank Name</span>
                                <strong class="ads-bank-detail-value"><?php echo esc_html($other_ways['intl_bank_name'] ?? ($other_ways['bank_name'] ?? 'Barclays Bank PLC')); ?></strong>
                            </div>
                            <div class="ads-bank-detail-row">
                                <span class="ads-bank-detail-label">Account Name</span>
                                <strong class="ads-bank-detail-value"><?php echo esc_html($other_ways['intl_account_name'] ?? ($other_ways['account_name'] ?? 'Aryana Aid')); ?></strong>
                            </div>
                            <div class="ads-bank-detail-row">
                                <span class="ads-bank-detail-label">SWIFT / BIC Code</span>
                                <strong class="ads-bank-detail-value"><?php echo esc_html($other_ways['swift'] ?? 'BUKBGB22'); ?></strong>
                            </div>
                            <div class="ads-bank-detail-row ads-bank-detail-row-last">
                                <span class="ads-bank-detail-label">IBAN</span>
                                <strong class="ads-bank-detail-value ads-iban-value"><?php echo esc_html($other_ways['iban'] ?? 'GB85 BUKB 2029 7790 5261 84'); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($other_ways['post_address'])): ?>
            <div class="ads-post-donate-container" style="margin-top: 16px;">
                <div class="ads-bank-info-card">
                    <div class="ads-card-header-icon">
                        <div class="ads-icon-circle">
                            <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        </div>
                        <div>
                            <h3 class="ads-bank-card-title"><?php echo esc_html($other_ways['post_title'] ?? 'Donate by Post'); ?></h3>
                            <?php if (!empty($other_ways['post_subtitle'])): ?>
                                <span class="ads-bank-card-subtitle"><?php echo esc_html($other_ways['post_subtitle']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="ads-bank-card-body">
                        <?php if (!empty($other_ways['post_instruction'])): ?>
                            <p class="ads-bank-instruction"><?php echo esc_html(stripslashes($other_ways['post_instruction'])); ?></p>
                        <?php endif; ?>
                        <div class="ads-post-address-box">
                            <?php echo nl2br(esc_html(stripslashes($other_ways['post_address'] ?? ''))); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- Dynamic Matrix Data & Engine -->
<script>
(function($) {
    'use strict';

    const matrix = <?php echo json_encode($matrix); ?>;
    let activeCurrency = <?php echo json_encode($currency); ?>;
    let currency = activeCurrency;
    let activeCurrencyCode = <?php echo json_encode($currency_code); ?>;
    const stripePk = <?php echo json_encode($stripe_pk); ?>;
    const isWcActive = <?php echo $is_wc_active ? 'true' : 'false'; ?>;
    const isStripeActive = <?php echo $is_stripe_active ? 'true' : 'false'; ?>;
    const isPaypalActive = <?php echo $is_paypal_active ? 'true' : 'false'; ?>;
    const adminFeeEnabled = <?php echo $admin_fee_enabled ? 'true' : 'false'; ?>;
    const adminFeeFixed = <?php echo $admin_fee_fixed; ?>;
    const commentsEnabled = <?php echo $comments_enabled ? 'true' : 'false'; ?>;
    const commentsPlaceholder = <?php echo json_encode($comments_placeholder); ?>;
    let mode = '<?php echo $is_initial_monthly ? "monthly" : "single"; ?>';
    let rowCount = 0;

    // Helper: Format Money
    function formatMoney(amount) {
        return activeCurrency + parseFloat(amount || 0).toLocaleString('en-GB', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Helper: Update Currency
    function updateCurrency(newCode, newSymbol) {
        activeCurrency = newSymbol;
        currency = newSymbol;
        activeCurrencyCode = newCode;

        // Update active pill button state
        $('.ads-curr-btn').removeClass('active');
        $(`.ads-curr-btn[data-code="${newCode}"]`).addClass('active');

        // Update table header symbol
        $('.ads-col-curr-symbol').text(newSymbol);

        // Update all existing row currency symbol prefixes
        $('.ads-currency-symbol').text(newSymbol);

        // Update admin fee labels with current currency
        $('.ads-admin-fee-label').text(`Please add an admin fee of ${newSymbol}1.10 to your donation.`);

        // Recalculate totals and button label
        recalculate();
    }

    // Helper: Project Name Resolver
    function resolveProjectName(input, amount, packageTag) {
        const amt = parseFloat(amount) || 0;
        const context = ((packageTag || '') + ' ' + (input || '')).toLowerCase();
        const isWaterRelated = context.includes('water') || context.includes('pump') || context.includes('well') || context.includes('tank');

        // If the project is already a known key in matrix:
        if (input && matrix[input]) {
            // Non-water projects (e.g. Orphan Sponsorship Scheme, Food Packs) must NEVER be overridden!
            if (!isWaterRelated && !input.toLowerCase().includes('water')) {
                return input;
            }
        }

        // Exact water cause resolution based on distinct package keywords or preset amounts ONLY if water-related or input unknown
        if (context.includes('tank') || (isWaterRelated && amt === 3600)) {
            return 'Water Tank - Community Well';
        }
        if (context.includes('pump') || (isWaterRelated && amt === 600)) {
            return 'Water - Pump';
        }
        if (context.includes('well') || (isWaterRelated && amt === 1200)) {
            return 'Water - Well';
        }

        if (input && matrix[input]) return input;

        // Try matching existing matrix projects
        if (input && input.trim() !== '') {
            const clean = (s) => (s || '').toLowerCase().replace(/[^a-z0-9]/g, '');
            const target = clean(input);
            for (const key of Object.keys(matrix)) {
                const kClean = clean(key);
                if (kClean === target || kClean.includes(target) || target.includes(kClean)) {
                    return key;
                }
            }
            // Custom project passed from external button or URL (e.g. "Winter Pack" or "£120 for a Winter Pack")
            const customTitle = input.trim();
            if (!matrix[customTitle]) {
                matrix[customTitle] = {
                    locations: ['Afghanistan', 'Pakistan', 'Where Most Needed'],
                    types: ['Sadaqah', 'Lillah', 'Zakat', 'Charitable Donation'],
                    fixed_single: 0,
                    fixed_monthly: 0
                };
            }
            return customTitle;
        }

        return '';
    }

    // Row HTML Generator
    function createRow(initialProject, initialLoc, initialType, initialAmt, initialComments, initialPackage) {
        rowCount++;
        const rowId = 'ads-row-' + rowCount;
        const hasProj = Boolean(initialProject && String(initialProject).trim() !== '');
        const selectedProj = hasProj ? resolveProjectName(initialProject, initialAmt, initialPackage) : '';
        if (selectedProj && !matrix[selectedProj]) {
            matrix[selectedProj] = {
                locations: ['Afghanistan', 'Pakistan', 'Where Most Needed'],
                types: ['Sadaqah', 'Lillah', 'Zakat', 'Charitable Donation'],
                fixed_single: 0,
                fixed_monthly: 0
            };
        }
        const projectNames = Object.keys(matrix);
        if (selectedProj && !projectNames.includes(selectedProj)) {
            projectNames.unshift(selectedProj);
        }
        const projData = selectedProj ? matrix[selectedProj] : null;

        let projOptions = `<option value="" ${!selectedProj ? 'selected' : ''}>Select Project / Appeal...</option>`;
        projectNames.forEach(p => {
            const isSel = (p === selectedProj) ? 'selected' : '';
            projOptions += `<option value="${p}" ${isSel}>${p}</option>`;
        });

        const availableLocs = (projData && projData.locations && projData.locations.length) ? projData.locations : ['Afghanistan', 'Pakistan', 'Where Most Needed'];
        let locOptions = '';
        availableLocs.forEach(loc => {
            const isSel = (loc === initialLoc || (!initialLoc && loc === 'Afghanistan')) ? 'selected' : '';
            locOptions += `<option value="${loc}" ${isSel}>${loc}</option>`;
        });

        const availableTypes = (projData && projData.types && projData.types.length) ? projData.types : ['Sadaqah', 'Lillah', 'Zakat', 'Charitable Donation'];
        let typeOptions = '';
        availableTypes.forEach(t => {
            const isSel = (t === initialType || (!initialType && t === 'Sadaqah')) ? 'selected' : '';
            typeOptions += `<option value="${t}" ${isSel}>${t}</option>`;
        });

        let amt = '';
        if (initialAmt !== undefined && initialAmt !== null && initialAmt !== '' && parseFloat(initialAmt) > 0) {
            amt = parseFloat(initialAmt);
        } else if (selectedProj && projData) {
            amt = (mode === 'monthly') ? (projData.fixed_monthly || 25) : (projData.fixed_single || 100);
        }
        if (mode === 'monthly' && projData && projData.fixed_monthly > 0 && Math.abs(amt - (projData.fixed_single || 0)) < 0.01) {
            amt = projData.fixed_monthly;
        }
        const comm = initialComments || '';

        const isWaterProj = (selectedProj || '').toLowerCase().includes('water') || (selectedProj || '').toLowerCase().includes('pump') || (selectedProj || '').toLowerCase().includes('well') || (selectedProj || '').toLowerCase().includes('tank');
        let resolvedPkg = initialPackage || '';
        if (!resolvedPkg && selectedProj && amt) {
            const numAmt = parseFloat(amt);
            if (isWaterProj) {
                if (numAmt === 600) resolvedPkg = '£600 Water Pump';
                else if (numAmt === 1200) resolvedPkg = '£1200 Water Well';
                else if (numAmt === 3600) resolvedPkg = '£3600 Community Water Tank';
                else resolvedPkg = `${activeCurrency}${numAmt}${mode === 'monthly' ? '/mo' : ''} ${selectedProj}`;
            } else {
                resolvedPkg = `${activeCurrency}${numAmt}${mode === 'monthly' ? '/mo' : ''} ${selectedProj}`;
            }
        }

        const commentsHtml = commentsEnabled ? `
            <div class="ads-field-group ads-field-comments-wrap">
                <span class="ads-field-label-mobile">Comments</span>
                <input type="text" class="ads-modern-input ads-item-comments" placeholder="${commentsPlaceholder}" value="${comm}">
            </div>` : '';

        const noCommentsClass = commentsEnabled ? '' : ' ads-no-comments';
        const packageAttr = resolvedPkg ? ` data-package="${String(resolvedPkg).replace(/"/g, '&quot;')}"` : '';
        return `
        <div class="ads-basket-item-row${noCommentsClass}" id="${rowId}"${packageAttr}>
            <div class="ads-field-group ads-field-project-wrap">
                <div class="ads-field-label-row">
                    <span class="ads-field-label-mobile">Project / Appeal</span>
                    <button type="button" class="ads-remove-row-btn ads-mobile-remove-btn" title="Remove this cause" aria-label="Remove this cause">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                    </button>
                </div>
                <select class="ads-modern-select ads-select-project">${projOptions}</select>
            </div>
            <div class="ads-field-group ads-field-location-wrap">
                <span class="ads-field-label-mobile">Location</span>
                <select class="ads-modern-select ads-select-location">${locOptions}</select>
            </div>
            <div class="ads-field-group ads-field-type-wrap">
                <span class="ads-field-label-mobile">Donation Type</span>
                <select class="ads-modern-select ads-select-type">${typeOptions}</select>
            </div>
            <div class="ads-field-group ads-field-amount-wrap">
                <span class="ads-field-label-mobile">Amount</span>
                <div class="ads-amount-input-box">
                    <span class="ads-currency-symbol">${activeCurrency}</span>
                    <input type="number" step="any" min="1" class="ads-modern-input ads-modern-amount-input ads-item-amount" value="${amt}" placeholder="0.00">
                </div>
            </div>
            ${commentsHtml}
            <div class="ads-field-remove-wrap ads-desktop-remove-wrap">
                <button type="button" class="ads-remove-row-btn ads-desktop-remove-btn" title="Remove this cause" aria-label="Remove this cause">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                    <span class="ads-remove-btn-text">Remove</span>
                </button>
            </div>
        </div>`;
    }

    // Update Totals
    function recalculate() {
        let subtotal = 0;
        $('#ads-basket-items .ads-basket-item-row').each(function() {
            const p = $(this).find('.ads-select-project').val() || '';
            const val = parseFloat($(this).find('.ads-item-amount').val()) || 0;
            if (p && val > 0) {
                subtotal += val;
            }
        });

        const hasAdminFee = adminFeeEnabled && $('#ads-admin-fee-checkbox').is(':checked');
        let adminFee = 0;
        if (hasAdminFee && subtotal > 0) {
            adminFee = adminFeeFixed;
        }

        const grandTotal = subtotal + adminFee;

        if (hasAdminFee) {
            $('#ads-admin-fee-display').text('+' + formatMoney(adminFeeFixed));
        } else {
            $('#ads-admin-fee-display').text('+' + formatMoney(0));
        }
        $('#ads-grand-total-display').text(formatMoney(grandTotal));
        $('#ads-freq-indicator').text(mode === 'monthly' ? 'Monthly Recurring' : 'One-off Donation');

        const activeMethod = $('.ads-pay-card-pill.active').data('method') || 'card';
        if (activeMethod === 'paypal') {
            $('#ads-checkout-submit').removeClass('ads-btn-gpay ads-btn-applepay').addClass('ads-btn-paypal')
                .html(`<span>Donate with <strong>PayPal</strong> (${formatMoney(grandTotal)})</span> <svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>`);
        } else if (activeMethod === 'gpay') {
            $('#ads-checkout-submit').removeClass('ads-btn-paypal ads-btn-applepay').addClass('ads-btn-gpay')
                .html(`<span>Donate with <strong>Google Pay</strong> (${formatMoney(grandTotal)})</span> <svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>`);
        } else if (activeMethod === 'applepay') {
            $('#ads-checkout-submit').removeClass('ads-btn-paypal ads-btn-gpay').addClass('ads-btn-applepay')
                .html(`<span>Donate with <strong>Apple Pay</strong> (${formatMoney(grandTotal)})</span> <svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>`);
        } else {
            $('#ads-checkout-submit').removeClass('ads-btn-paypal ads-btn-gpay ads-btn-applepay')
                .html(`<span>Complete Donation of <strong id="ads-btn-total-val">${formatMoney(grandTotal)}</strong></span> <svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>`);
        }

        if (typeof window.adsUpdatePaymentRequest === 'function') {
            window.adsUpdatePaymentRequest(grandTotal);
        }

        updatePackageNotice();
    }
    window.adsRecalculateTotals = recalculate;

    // Dynamic Multi-Package Attribution Notice Resolver
    function updatePackageNotice() {
        const packages = [];
        $('#ads-basket-items .ads-basket-item-row').each(function() {
            const $row = $(this);
            const proj = $row.find('.ads-select-project').val() || '';
            const amt = parseFloat($row.find('.ads-item-amount').val()) || 0;
            let pkg = $row.attr('data-package') || '';
            const isWater = proj.toLowerCase().includes('water') || proj.toLowerCase().includes('pump') || proj.toLowerCase().includes('well') || proj.toLowerCase().includes('tank');

            // If row has an existing package tag, keep it strictly in sync if user modifies the amount
            if (pkg && amt > 0) {
                const match = pkg.match(/^([^\d]*)(\d+(?:\.\d+)?)(.*)$/);
                if (match) {
                    const pkgAmt = parseFloat(match[2]);
                    if (Math.abs(pkgAmt - amt) > 0.001) {
                        // Amount was modified by donor
                        if (isWater && amt === 600) pkg = '£600 Water Pump';
                        else if (isWater && amt === 1200) pkg = '£1200 Water Well';
                        else if (isWater && amt === 3600) pkg = '£3600 Community Water Tank';
                        else pkg = `${activeCurrency}${amt}${mode === 'monthly' ? '/mo' : ''} ${proj}`;
                        $row.attr('data-package', pkg);
                    }
                }
            } else if (!pkg && amt > 0) {
                if (isWater) {
                    if (amt === 600) pkg = '£600 Water Pump';
                    else if (amt === 1200) pkg = '£1200 Water Well';
                    else if (amt === 3600) pkg = '£3600 Community Water Tank';
                    if (pkg) $row.attr('data-package', pkg);
                }
            }

            if (pkg && pkg.trim() && !packages.includes(pkg.trim())) {
                packages.push(pkg.trim());
            }
        });

        const $container = $('#ads-package-notice-container');
        $container.hide().empty();
        // Attribution banner disabled across all devices (Desktop, Tablet, Mobile) per client request
        return;
    }

    // Document Ready Logic
    $(function() {
        // Initialize Real Stripe Elements
        let stripe = null;
        let cardElement = null;

        // Interactive Card number formatting & live validation
        $('#ads-card-number').on('input', function() { window.adsFormatCardNumber(this); })
            .on('blur', function() { window.adsValidateCardNumber(this); });

        // Interactive Expiry Date with Instant Auto-Slash, live validation & Backspace Handling
        $('#ads-card-expiry').on('input', function() { window.adsFormatExpiry(this); })
            .on('blur', function() { window.adsValidateExpiry(this); })
            .on('keydown', function(e) { window.adsHandleExpiryBackspace(e, this); });

        // Interactive CVC formatting & validation
        $('#ads-card-cvc').on('input', function() { window.adsFormatCvc(this); })
            .on('blur', function() { window.adsValidateCvc(this); });

        if (typeof Stripe !== 'undefined' && stripePk) {
            try {
                stripe = Stripe(stripePk);
            } catch (stripeErr) {
                console.warn('Stripe init notice:', stripeErr);
            }
        }

        // ======================================================================
        // Apple Pay & Google Pay (Stripe Payment Request Button) Integration
        // ======================================================================
        let activePaymentRequest = null;
        let prButtonElement = null;

        window.adsUpdatePaymentRequest = function(total) {
            if (activePaymentRequest && total > 0) {
                try {
                    const pence = Math.round(parseFloat(total) * 100);
                    activePaymentRequest.update({
                        total: {
                            label: 'Aryana Aid Donation',
                            amount: pence,
                        },
                        currency: (activeCurrencyCode || 'GBP').toLowerCase(),
                    });
                } catch (e) {}
            }
        };

        function setupAppleGooglePay() {
            if (!stripe || typeof stripe.paymentRequest !== 'function') return;

            const initialPence = 1000;
            try {
                const pr = stripe.paymentRequest({
                    country: 'GB',
                    currency: (activeCurrencyCode || 'GBP').toLowerCase(),
                    total: {
                        label: 'Aryana Aid Donation',
                        amount: initialPence,
                    },
                    requestPayerName: true,
                    requestPayerEmail: true,
                    requestPayerPhone: false,
                });

                pr.canMakePayment().then(function(result) {
                    console.log('[Aryana Aid] Stripe canMakePayment() result:', result);
                    if (result) {
                        activePaymentRequest = pr;
                        // Standalone Stripe Link express button removed as requested
                        $('#ads-payment-request-wrapper').hide();

                        if (typeof recalculate === 'function') {
                            recalculate();
                        }
                    } else {
                        console.info('[Aryana Aid] Apple Pay / Google Pay button hidden: Device, browser, or HTTP environment does not have an active wallet configured.');
                        $('#ads-payment-request-wrapper').hide();
                    }
                }).catch(function(err) {
                    console.warn('[Aryana Aid] Stripe canMakePayment error:', err);
                    $('#ads-payment-request-wrapper').hide();
                });

                pr.on('paymentmethod', function(ev) {
                    let donorName = (ev.payerName || '').trim();
                    let nameParts = donorName.split(' ');
                    let firstName = nameParts[0] || 'Valued';
                    let lastName = nameParts.slice(1).join(' ') || 'Donor';
                    let donorEmail = ev.payerEmail || $('#ads-email').val() || 'donor@aryanaaid.org.uk';

                    if ($('#ads-first-name').val().trim()) firstName = $('#ads-first-name').val().trim();
                    if ($('#ads-last-name').val().trim()) lastName = $('#ads-last-name').val().trim();
                    if ($('#ads-email').val().trim()) donorEmail = $('#ads-email').val().trim();

                    const basketItems = [];
                    let subtotal = 0;
                    $('#ads-basket-items .ads-basket-item-row').each(function() {
                        const p = $(this).find('.ads-select-project').val() || 'General Donation';
                        const l = $(this).find('.ads-select-location').val() || 'Afghanistan';
                        const t = $(this).find('.ads-select-type').val() || 'Sadaqah';
                        const a = parseFloat($(this).find('.ads-item-amount').val()) || 0;
                        if (a > 0) {
                            subtotal += a;
                            basketItems.push({ project: p, location: l, type: t, amount: a });
                        }
                    });

                    if (subtotal <= 0 || basketItems.length === 0) {
                        ev.complete('fail');
                        alert('Please enter a valid donation amount.');
                        return;
                    }

                    const hasAdminFee = adminFeeEnabled && $('#ads-admin-fee-checkbox').is(':checked');
                    const adminFee = (hasAdminFee && subtotal > 0) ? adminFeeFixed : 0;
                    const totalAmount = subtotal + adminFee;

                    const allPackages = [];
                    $('#ads-basket-items .ads-basket-item-row').each(function() {
                        const pkg = $(this).attr('data-package');
                        if (pkg && !allPackages.includes(pkg)) allPackages.push(pkg);
                    });
                    const combinedPackage = allPackages.join(', ') || urlPackage || '';

                    const payload = {
                        action: 'ads_process_donation',
                        nonce: window.adsConfig ? window.adsConfig.nonce : '<?php echo wp_create_nonce("ads_donation_nonce"); ?>',
                        items: basketItems,
                        gateway: 'card',
                        payment_method_id: ev.paymentMethod.id,
                        donor_type: $('input[name="donor_type"]:checked').val() || 'individual',
                        title: $('#ads-title').val() || '',
                        first_name: firstName,
                        last_name: lastName,
                        email: donorEmail,
                        phone: $('#ads-phone').val() || '',
                        address1: $('#ads-address1').val() || '',
                        address2: $('#ads-address2').val() || '',
                        city: $('#ads-city').val() || '',
                        county: $('#ads-county').val() || '',
                        postcode: $('#ads-postcode').val() || '',
                        country: $('#ads-country').val() || 'United Kingdom',
                        comments: $('#ads-basket-items .ads-item-comments').first().val() || '',
                        gift_aid: ($('#ads-giftaid-checkbox').is(':checked') || $('#ads-giftaid-check').is(':checked')) ? 'yes' : 'no',
                        project: basketItems[0].project,
                        campaign_id: urlCampaignId || '',
                        source_package: combinedPackage,
                        frequency: mode,
                        donation_type: basketItems[0].type,
                        amount: subtotal,
                        fee_amount: adminFee,
                        total_amount: totalAmount,
                        currency: activeCurrency,
                        currency_code: activeCurrencyCode
                    };

                    const ajaxUrl = window.adsConfig ? window.adsConfig.ajaxUrl : '/wp-admin/admin-ajax.php';
                    $.post(ajaxUrl, payload, function(res) {
                        if (res && res.success && res.data) {
                            ev.complete('success');
                            renderSuccessConfirmation(res.data);
                        } else {
                            ev.complete('fail');
                            alert(res && res.data && res.data.message ? res.data.message : 'Payment authorization failed. Please try again.');
                        }
                    }).fail(function() {
                        ev.complete('fail');
                        alert('Payment processor connection failed. Please try again.');
                    });
                });
            } catch (err) {
                console.log('Setup Apple/Google Pay notice:', err);
                $('#ads-payment-request-wrapper').hide();
            }
        }

        setupAppleGooglePay();

        // Parse URL params
        const urlParams = new URLSearchParams(window.location.search);
        const urlProject = urlParams.get('project') || undefined;
        const urlAmount = urlParams.get('amount') || undefined;
        const urlFreq = urlParams.get('freq') || undefined;
        const urlType = urlParams.get('type') || undefined;
        const urlLoc = urlParams.get('location') || undefined;
        let urlPackage = urlParams.get('package') || undefined;
        const urlCampaignId = urlParams.get('campaign_id') || undefined;
        const urlFrom = urlParams.get('from') || undefined;

        const rawIncomingFreq = (urlParams.get('freq') || urlParams.get('frequency') || '').toLowerCase().trim();
        const isUrlMonthly = (rawIncomingFreq === 'monthly' || rawIncomingFreq === 'regular' || rawIncomingFreq.includes('month') || rawIncomingFreq.includes('regular') || rawIncomingFreq.includes('recur'));

        if (isUrlMonthly) {
            mode = 'monthly';
            $('.ads-freq-tab').removeClass('active');
            $('.ads-freq-tab[data-freq="monthly"]').addClass('active');
            $('#ads-freq-indicator').text('Monthly Recurring');
        } else if (rawIncomingFreq === 'single' || rawIncomingFreq === 'once' || rawIncomingFreq === 'one-off') {
            mode = 'single';
            $('.ads-freq-tab').removeClass('active');
            $('.ads-freq-tab[data-freq="single"]').addClass('active');
            $('#ads-freq-indicator').text('One-off Donation');
        } else if (mode === 'monthly') {
            $('.ads-freq-tab').removeClass('active');
            $('.ads-freq-tab[data-freq="monthly"]').addClass('active');
            $('#ads-freq-indicator').text('Monthly Recurring');
        }

        // Storage Keys for Separate Multi-Item Baskets (Give Once vs. Give Monthly)
        function getBasketStorageKey(targetMode) {
            const freq = (targetMode || mode || 'single') === 'monthly' ? 'monthly' : 'single';
            return 'ads_persistent_basket_' + freq;
        }

        // Clean up legacy unified basket so old mixed items do not persist
        try {
            localStorage.removeItem('ads_persistent_donation_basket');
        } catch (e) {}

        function loadBasketFromStorage(targetMode) {
            try {
                const key = getBasketStorageKey(targetMode || mode);
                const raw = localStorage.getItem(key);
                if (raw) {
                    const parsed = JSON.parse(raw);
                    if (Array.isArray(parsed) && parsed.length > 0) {
                        return parsed;
                    }
                }
            } catch (e) {
                console.warn('Could not read donation basket from localStorage:', e);
            }
            return [];
        }

        function saveBasketToStorage(targetMode) {
            try {
                const key = getBasketStorageKey(targetMode || mode);
                const items = [];
                $('#ads-basket-items .ads-basket-item-row').each(function() {
                    const $row = $(this);
                    const p = $row.find('.ads-select-project').val();
                    const l = $row.find('.ads-select-location').val();
                    const t = $row.find('.ads-select-type').val();
                    const a = parseFloat($row.find('.ads-item-amount').val()) || 0;
                    const c = $row.find('.ads-item-comments').val() || '';
                    const pkg = $row.attr('data-package') || '';
                    if (p && p.trim() !== '' && a > 0) {
                        items.push({
                            project: p,
                            location: l,
                            type: t,
                            amount: a,
                            comments: c,
                            package: pkg,
                            is_explicit: Boolean(pkg)
                        });
                    }
                });
                localStorage.setItem(key, JSON.stringify(items));
            } catch (e) {
                console.warn('Could not save donation basket to localStorage:', e);
            }
        }

        // Apply URL Currency parameters if passed
        const urlCurrCode = urlParams.get('currency_code');
        const urlCurrSym = urlParams.get('currency');
        if (urlCurrCode && (urlCurrCode === 'GBP' || urlCurrCode === 'USD' || urlCurrCode === 'EUR' || urlCurrCode === 'CAD' || urlCurrCode === 'AUD')) {
            const resolvedSym = urlCurrSym || (urlCurrCode === 'EUR' ? '€' : (urlCurrCode === 'GBP' ? '£' : '$'));
            updateCurrency(urlCurrCode, resolvedSym);
        }

        // Persistent Donation Basket Integration:
        // Load ONLY the basket for the active frequency mode (Give Once vs. Give Monthly)
        let savedBasket = loadBasketFromStorage(mode);

        // Self-healing normalization: correct any mismatched project names and infer packages for preset amounts
        savedBasket = (Array.isArray(savedBasket) ? savedBasket : []).filter(item => item && typeof item === 'object').map(function(item) {
            item.project = resolveProjectName(item.project, item.amount, item.package);
            const amt = parseFloat(item.amount) || 0;
            const isWater = (item.project || '').toLowerCase().includes('water') || (item.project || '').toLowerCase().includes('pump') || (item.project || '').toLowerCase().includes('well') || (item.project || '').toLowerCase().includes('tank');
            if (!item.package && item.is_explicit) {
                if (isWater) {
                    if (amt === 600) item.package = '£600 Water Pump';
                    else if (amt === 1200) item.package = '£1200 Water Well';
                    else if (amt === 3600) item.package = '£3600 Community Water Tank';
                    else item.package = `${activeCurrency}${amt}${mode === 'monthly' ? '/mo' : ''} ${item.project}`;
                } else {
                    item.package = `${activeCurrency}${amt}${mode === 'monthly' ? '/mo' : ''} ${item.project}`;
                }
            } else if (amt > 0 && item.package) {
                // Ensure package amount matches the item amount
                const match = item.package.match(/^([^\d]*)(\d+(?:\.\d+)?)(.*)$/);
                if (match) {
                    const pkgAmt = parseFloat(match[2]);
                    if (Math.abs(pkgAmt - amt) > 0.001) {
                        if (isWater && amt === 600) item.package = '£600 Water Pump';
                        else if (isWater && amt === 1200) item.package = '£1200 Water Well';
                        else if (isWater && amt === 3600) item.package = '£3600 Community Water Tank';
                        else item.package = `${activeCurrency}${amt}${mode === 'monthly' ? '/mo' : ''} ${item.project}`;
                    }
                }
            }
            return item;
        });

        // If user arrived by clicking any donation button (URL has project or amount):
        const hasIncomingClick = Boolean(urlProject || urlAmount);
        const isQuickDonate = (urlFrom === 'quick_donate');

        if (hasIncomingClick) {
            const incAmt = (urlAmount !== undefined && urlAmount !== null && urlAmount !== '') ?
                parseFloat(urlAmount) : 0;
            let incPkg = urlPackage || '';
            const incProj = resolveProjectName(urlProject, incAmt, incPkg);
            const pData = matrix[incProj] || { locations: ['Afghanistan', 'Pakistan', 'Where Most Needed'], types: ['Sadaqah', 'Lillah', 'Zakat', 'Charitable Donation'], fixed_single: 0, fixed_monthly: 0 };
            let finalAmt = incAmt;
            if (mode === 'monthly') {
                if (incAmt <= 0 || (pData.fixed_monthly > 0 && Math.abs(incAmt - (pData.fixed_single || 0)) < 0.01)) {
                    finalAmt = pData.fixed_monthly;
                }
            } else {
                if (incAmt <= 0) {
                    finalAmt = pData.fixed_single || 100;
                }
            }
            const incLoc = urlLoc || (pData.locations && pData.locations[0]) || 'Afghanistan';
            const incType = urlType || (pData.types && pData.types[0]) || 'Sadaqah';

            const isIncWater = (incProj || '').toLowerCase().includes('water') || (incProj || '').toLowerCase().includes('pump') || (incProj || '').toLowerCase().includes('well') || (incProj || '').toLowerCase().includes('tank');
            if (!incPkg || (mode === 'monthly' && incPkg.includes('600'))) {
                if (isIncWater) {
                    if (finalAmt === 60) incPkg = '£60/mo Water Pump';
                    else if (finalAmt === 600) incPkg = '£600 Water Pump';
                    else if (finalAmt === 1200) incPkg = '£1200 Water Well';
                    else if (finalAmt === 3600) incPkg = '£3600 Community Water Tank';
                    else incPkg = `${activeCurrency}${finalAmt}${mode === 'monthly' ? '/mo' : ''} ${incProj}`;
                } else {
                    incPkg = `${activeCurrency}${finalAmt}${mode === 'monthly' ? '/mo' : ''} ${incProj}`;
                }
            }

            // Multi-item accumulation (strictly within the active mode's dedicated basket)
            let existingIdx = savedBasket.findIndex(it => it.project === incProj);
            if (existingIdx !== -1) {
                // If project already in basket, update its amount, type & package to latest selection
                savedBasket[existingIdx].amount = finalAmt;
                savedBasket[existingIdx].package = incPkg;
                savedBasket[existingIdx].type = incType;
                if (urlLoc) savedBasket[existingIdx].location = incLoc;
                savedBasket[existingIdx].is_explicit = true;
            } else if (savedBasket.length === 0 || (savedBasket.length === 1 && (!savedBasket[0].project || !savedBasket[0].package || !savedBasket[0].is_explicit || parseFloat(savedBasket[0].amount) <= 0))) {
                // If only 1 generic unchosen placeholder row exists, replace it with the chosen donation
                savedBasket = [{
                    project: incProj,
                    location: incLoc,
                    type: incType,
                    amount: finalAmt,
                    comments: '',
                    package: incPkg,
                    is_explicit: true
                }];
            } else {
                // Add the new donation item to the accumulated basket
                savedBasket.push({
                    project: incProj,
                    location: incLoc,
                    type: incType,
                    amount: finalAmt,
                    comments: '',
                    package: incPkg,
                    is_explicit: true
                });
            }

            // Save immediately into localStorage for current mode
            try {
                localStorage.setItem(getBasketStorageKey(mode), JSON.stringify(savedBasket));
            } catch (e) {}

            // Clean query parameters from address bar to prevent duplicate additions on reload
            if (window.history && window.history.replaceState) {
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({}, document.title, cleanUrl);
            }
        } else {
            // Persist the self-healed basket if no new URL params were passed
            try {
                localStorage.setItem(getBasketStorageKey(mode), JSON.stringify(savedBasket));
            } catch (e) {}
        }

        // Render basket items for active mode
        $('#ads-basket-items').empty();
        const validSavedItems = (savedBasket || []).filter(item => item && item.project && item.project.trim() !== '' && parseFloat(item.amount) > 0);
        if (validSavedItems.length > 0) {
            validSavedItems.forEach(function(item) {
                $('#ads-basket-items').append(
                    createRow(item.project, item.location, item.type, item.amount, item.comments, item.package)
                );
            });
        } else {
            // Default 1 blank row with "Select Project / Appeal..." placeholder
            $('#ads-basket-items').append(createRow('', 'Afghanistan', 'Sadaqah', '', '', ''));
        }

        // Failsafe: Ensure basket never renders empty
        if ($('#ads-basket-items .ads-basket-item-row').length === 0) {
            $('#ads-basket-items').append(createRow('', 'Afghanistan', 'Sadaqah', '', '', ''));
        }

        recalculate();

        // Frequency Switch
        $('#ads-freq-switcher').on('click', '.ads-freq-tab', function() {
            const newMode = $(this).data('freq');
            if (newMode === mode) return;

            // Save the current rows to previous mode before switching
            saveBasketToStorage(mode);

            // Switch active tab UI
            $('.ads-freq-tab').removeClass('active');
            $(this).addClass('active');
            mode = newMode;
            $('#ads-freq-indicator').text(mode === 'monthly' ? 'Monthly Recurring' : 'One-off Donation');

            // Load and render the basket dedicated to this mode
            let targetBasket = loadBasketFromStorage(mode);
            $('#ads-basket-items').empty();

            if (targetBasket.length > 0) {
                targetBasket.forEach(function(item) {
                    $('#ads-basket-items').append(
                        createRow(item.project, item.location, item.type, item.amount, item.comments, item.package)
                    );
                });
            } else {
                // If this frequency basket is currently empty, create 1 clean row for this mode
                const defaultProject = Object.keys(matrix)[0] || 'Water - Well';
                const pData = matrix[defaultProject];
                const presetAmt = pData ? ((mode === 'monthly') ? (pData.fixed_monthly || 25) : (pData.fixed_single || 100)) : 0;
                $('#ads-basket-items').append(createRow(defaultProject, '', 'Sadaqah', presetAmt, '', ''));
                saveBasketToStorage(mode);
            }

            recalculate();
        });

        // Add Row Button
        $('#ads-btn-add-row').on('click', function() {
            $('#ads-basket-items').append(createRow('', 'Afghanistan', 'Sadaqah', '', '', ''));
            saveBasketToStorage();
            recalculate();
        });

        // Remove Row Button
        $('#ads-basket-items').on('click', '.ads-remove-row-btn', function() {
            const $row = $(this).closest('.ads-basket-item-row');
            if ($('#ads-basket-items .ads-basket-item-row').length > 1) {
                $row.fadeOut(200, function() {
                    $(this).remove();
                    saveBasketToStorage();
                    recalculate();
                });
            } else {
                // If only 1 row left, reset this row to blank placeholder instead of preventing removal
                $row.find('.ads-select-project').val('');
                $row.find('.ads-item-amount').val('');
                $row.find('.ads-item-comments').val('');
                $row.removeAttr('data-package');
                saveBasketToStorage();
                recalculate();
            }
        });

        // Remove Package Attribution Badge
        $(document).on('click', '.ads-remove-package-btn', function(e) {
            e.preventDefault();
            urlPackage = '';
            $('#ads-basket-items .ads-basket-item-row').removeAttr('data-package');
            saveBasketToStorage();
            $('#ads-package-notice-container').slideUp(200, function() {
                $(this).empty();
            });
            // Clean up the URL in the browser without reloading the page
            if (window.history && window.history.replaceState) {
                const cleanUrl = new URL(window.location.href);
                cleanUrl.searchParams.delete('package');
                window.history.replaceState({}, document.title, cleanUrl.toString());
            }
        });

        // Cascading Project Select
        $('#ads-basket-items').on('change', '.ads-select-project', function() {
            const $row = $(this).closest('.ads-basket-item-row');
            const proj = $(this).val();
            if (!proj) {
                // If the user selected the placeholder "Select Project / Appeal..."
                $row.removeAttr('data-package');
                $row.find('.ads-item-amount').val('');
                saveBasketToStorage();
                recalculate();
                return;
            }

            const pData = matrix[proj] || { locations: ['Afghanistan'], types: ['Sadaqah'], fixed_single: 0, fixed_monthly: 0 };

            // Update Locations
            let locHtml = '';
            (pData.locations || ['Afghanistan']).forEach(l => { locHtml += `<option value="${l}">${l}</option>`; });
            $row.find('.ads-select-location').html(locHtml);

            // Update Types
            let typeHtml = '';
            (pData.types || ['Sadaqah']).forEach(t => { typeHtml += `<option value="${t}">${t}</option>`; });
            $row.find('.ads-select-type').html(typeHtml);

            // Update Amount
            let currentAmt = parseFloat($row.find('.ads-item-amount').val()) || 0;
            const suggestedAmt = (mode === 'monthly') ? (pData.fixed_monthly || 25) : (pData.fixed_single || 100);
            if (currentAmt <= 0 && suggestedAmt > 0) {
                $row.find('.ads-item-amount').val(suggestedAmt);
                currentAmt = suggestedAmt;
            }

            // Sync package attribute if row is attributed to match newly selected project
            let pkg = $row.attr('data-package');
            if (pkg || currentAmt > 0) {
                const isWater = proj.toLowerCase().includes('water') || proj.toLowerCase().includes('pump') || proj.toLowerCase().includes('well') || proj.toLowerCase().includes('tank');
                if (isWater && currentAmt === 600) pkg = '£600 Water Pump';
                else if (isWater && currentAmt === 1200) pkg = '£1200 Water Well';
                else if (isWater && currentAmt === 3600) pkg = '£3600 Community Water Tank';
                else pkg = `${activeCurrency}${currentAmt}${mode === 'monthly' ? '/mo' : ''} ${proj}`;
                $row.attr('data-package', pkg);
            }

            saveBasketToStorage();
            recalculate();
        });

        // Recalculate and sync on amount change (takes user-modified amount immediately)
        $('#ads-basket-items').on('input change', '.ads-item-amount', function() {
            const $row = $(this).closest('.ads-basket-item-row');
            const amt = parseFloat($(this).val()) || 0;
            const proj = $row.find('.ads-select-project').val() || '';
            let pkg = $row.attr('data-package') || '';

            if (pkg && amt > 0) {
                const isWater = proj.toLowerCase().includes('water') || proj.toLowerCase().includes('pump') || proj.toLowerCase().includes('well') || proj.toLowerCase().includes('tank');
                if (isWater && amt === 600) pkg = '£600 Water Pump';
                else if (isWater && amt === 1200) pkg = '£1200 Water Well';
                else if (isWater && amt === 3600) pkg = '£3600 Community Water Tank';
                else pkg = `${activeCurrency}${amt}${mode === 'monthly' ? '/mo' : ''} ${proj}`;
                $row.attr('data-package', pkg);
            }

            saveBasketToStorage();
            recalculate();
        });

        // Comments change handler
        $('#ads-basket-items').on('input change', '.ads-item-comments', function() {
            saveBasketToStorage();
        });

        // Sync on location or type change
        $('#ads-basket-items').on('change', '.ads-select-location, .ads-select-type', function() {
            saveBasketToStorage();
        });

        // Admin fee toggle
        $('#ads-admin-fee-checkbox').on('change', function() {
            $('#ads-admin-fee-card').toggleClass('active', $(this).is(':checked'));
            recalculate();
        });

        // Donor Type Radio Pills
        $('.ads-donor-type-option').on('click', function() {
            $('.ads-donor-type-option').removeClass('active');
            $(this).addClass('active');
            $(this).find('input').prop('checked', true);

            const val = $(this).find('input').val();
            if (val === 'event') {
                $('#ads-event-field-wrap').slideDown(200);
                $('#ads-org-field-wrap').slideUp(200);
            } else if (val === 'organisation') {
                $('#ads-org-field-wrap').slideDown(200);
                $('#ads-event-field-wrap').slideUp(200);
            } else {
                $('#ads-event-field-wrap').slideUp(200);
                $('#ads-org-field-wrap').slideUp(200);
            }
        });

        // Consent Checkboxes styling
        $('.ads-consent-checkbox-card input').on('change', function() {
            $(this).closest('.ads-consent-checkbox-card').toggleClass('active', $(this).is(':checked'));
        }).trigger('change');

        // Payment Method Switcher
        $(document).on('click', '.ads-pay-card-pill', function(e) {
            e.preventDefault();
            $('.ads-pay-card-pill').removeClass('active');
            $(this).addClass('active');

            const m = $(this).attr('data-method') || $(this).data('method');
            if (m === 'paypal') {
                $('#ads-card-section').stop(true, true).slideUp(200);
                $('#ads-paypal-section').stop(true, true).slideDown(200);
            } else if (m === 'gpay' || m === 'applepay') {
                $('#ads-card-section').stop(true, true).slideUp(200);
                $('#ads-paypal-section').stop(true, true).slideUp(200);
                if (typeof activePaymentRequest !== 'undefined' && activePaymentRequest) {
                    try { activePaymentRequest.show(); } catch (err) {}
                }
            } else {
                $('#ads-paypal-section').stop(true, true).slideUp(200);
                $('#ads-card-section').stop(true, true).slideDown(200);
            }
            recalculate();
        });

        // Initialize default view based on gateway availability
        if (!isStripeActive && isPaypalActive) {
            $('#ads-card-section').hide();
            $('#ads-paypal-section').show();
            const currentTotal = $('#ads-grand-total-display').text() || '£0.00';
            $('#ads-checkout-submit').addClass('ads-btn-paypal')
                .html(`<span>Continue with <strong>PayPal</strong> (${currentTotal})</span> <svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>`);
        } else if (!isStripeActive && !isPaypalActive) {
            $('#ads-card-section').hide();
            $('#ads-paypal-section').hide();
            $('#ads-checkout-submit').prop('disabled', true).css({ opacity: '0.5', cursor: 'not-allowed' })
                .html('<span>Payment Gateways Deactivated in WooCommerce</span>');
        }

        // Copy Bank Details Button
        $('.ads-copy-btn').on('click', function(e) {
            e.preventDefault();
            const text = $(this).data('copy');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text);
                const $btn = $(this);
                $btn.css('color', '#038B5C');
                setTimeout(() => { $btn.css('color', ''); }, 1500);
            }
        });

        // Submit Button Action - AJAX Processing to Database
        $('#ads-modern-donation-form').on('submit', function(e) {
            e.preventDefault();

            if (!isWcActive) {
                alert('WooCommerce is currently deactivated. Donations require WooCommerce to be active. Please activate WooCommerce in WordPress Admin.');
                return;
            }

            const activeMethod = $('.ads-pay-card-pill.active').data('method') || 'card';

            if (activeMethod === 'card' && !isStripeActive) {
                alert('Credit/Debit Card payments are currently unavailable because the Stripe payment plugin is deactivated or disabled in WooCommerce.');
                return;
            }

            if (activeMethod === 'paypal' && !isPaypalActive) {
                alert('PayPal payments are currently unavailable because the PayPal payment plugin is deactivated or disabled in WooCommerce.');
                return;
            }

            if (activeMethod === 'gpay' || activeMethod === 'applepay') {
                if (typeof activePaymentRequest !== 'undefined' && activePaymentRequest) {
                    try {
                        activePaymentRequest.show();
                    } catch (err) {
                        console.log('Payment request show error:', err);
                    }
                    return;
                } else {
                    alert('Digital wallet checkout (' + (activeMethod === 'gpay' ? 'Google Pay' : 'Apple Pay') + ') is only supported on compatible devices with an active wallet card. Please select Debit or Credit Card below.');
                    adsSelectPaymentMethod('card', document.querySelector('.ads-pay-card-pill[data-method="card"]'));
                    return;
                }
            }

            // Form validation
            let valid = true;
            $('#ads-modern-donation-form input[required]:visible').each(function() {
                if (!$(this).val().trim()) {
                    $(this).css('border-color', '#EF4444');
                    valid = false;
                } else {
                    $(this).css('border-color', '');
                }
            });

            if (!valid) {
                const $firstErr = $('#ads-modern-donation-form input[required]:visible').filter(function() { return !$(this).val().trim(); }).first();
                if ($firstErr.length) {
                    $('html, body').animate({ scrollTop: $firstErr.offset().top - 120 }, 400);
                }
                alert('Please fill in all required contact and billing fields.');
                return;
            }

            // Collect Basket Items
            const basketItems = [];
            let subtotal = 0;
            $('#ads-basket-items .ads-basket-item-row').each(function() {
                const p = $(this).find('.ads-select-project').val() || '';
                const l = $(this).find('.ads-select-location').val() || 'Afghanistan';
                const t = $(this).find('.ads-select-type').val() || 'Sadaqah';
                const a = parseFloat($(this).find('.ads-item-amount').val()) || 0;
                if (p && p.trim() !== '' && a > 0) {
                    subtotal += a;
                    basketItems.push({ project: p, location: l, type: t, amount: a });
                }
            });

            if (subtotal <= 0 || basketItems.length === 0) {
                alert('Please select a project / appeal and enter a valid donation amount.');
                return;
            }

            const hasAdminFee = adminFeeEnabled && $('#ads-admin-fee-checkbox').is(':checked');
            const adminFee = (hasAdminFee && subtotal > 0) ? adminFeeFixed : 0;
            const totalAmount = subtotal + adminFee;

            const firstRow = basketItems[0] || {};
            const project = firstRow.project || 'General Donation';
            const donationType = firstRow.type || 'Sadaqah';
            const comments = $('#ads-basket-items .ads-item-comments').first().val() || '';

            // Stay in touch consents
            const stayInTouch = {};
            $('.ads-consent-checkbox-card input[type="checkbox"]').each(function() {
                const name = $(this).attr('name') || '';
                const key = name.replace('ads_sit_', '');
                if (key) {
                    stayInTouch[key] = $(this).is(':checked') ? 'yes' : 'no';
                }
            });

            const allPackages = [];
            $('#ads-basket-items .ads-basket-item-row').each(function() {
                const pkg = $(this).attr('data-package');
                if (pkg && !allPackages.includes(pkg)) allPackages.push(pkg);
            });
            const combinedPackage = allPackages.join(', ') || urlPackage || '';

            const payload = {
                action: 'ads_process_donation',
                nonce: window.adsConfig ? window.adsConfig.nonce : '<?php echo wp_create_nonce("ads_donation_nonce"); ?>',
                items: basketItems,
                gateway: activeMethod,
                donor_type: $('input[name="donor_type"]:checked').val() || 'individual',
                title: $('#ads-title').val() || '',
                first_name: $('#ads-first-name').val() || '',
                last_name: $('#ads-last-name').val() || '',
                email: $('#ads-email').val() || '',
                phone: $('#ads-phone').val() || '',
                org_name: $('input[name="org_name"]').val() || '',
                event_name: $('input[name="event_name"]').val() || '',
                address1: $('#ads-address1').val() || '',
                address2: $('#ads-address2').val() || '',
                city: $('#ads-city').val() || '',
                county: $('#ads-county').val() || '',
                postcode: $('#ads-postcode').val() || '',
                country: $('#ads-country').val() || 'United Kingdom',
                comments: comments,
                gift_aid: ($('#ads-giftaid-checkbox').is(':checked') || $('#ads-giftaid-check').is(':checked')) ? 'yes' : 'no',
                stay_in_touch: stayInTouch,
                project: project,
                campaign_id: urlCampaignId || '',
                source_package: combinedPackage,
                frequency: mode,
                donation_type: donationType,
                amount: subtotal,
                fee_amount: adminFee,
                total_amount: totalAmount,
                currency: activeCurrency,
                currency_code: activeCurrencyCode
            };

            const $btn = $('#ads-checkout-submit');
            const ajaxUrl = window.adsConfig ? window.adsConfig.ajaxUrl : '/wp-admin/admin-ajax.php';

            function renderSuccessConfirmation(data) {
                // Populate Modal details
                $('#ads-modal-conf-total').text(activeCurrency + parseFloat(totalAmount).toFixed(2));
                $('#ads-modal-conf-ref').text(data.txn_id || ('ARYANA_' + Math.random().toString(36).substring(2, 9).toUpperCase()));
                $('#ads-modal-conf-donor').text(data.donor_name || (payload.first_name + ' ' + payload.last_name));
                $('#ads-modal-conf-email').text(data.email || payload.email);
                $('#ads-modal-conf-cause').text(data.project || project);
                $('#ads-modal-conf-freq').text(data.frequency || (mode === 'monthly' ? 'Monthly Recurring' : 'Single'));
                $('#ads-modal-conf-gateway').text(data.gateway || (activeMethod === 'paypal' ? 'PayPal' : 'Credit / Debit Card (Stripe)'));
                $('#ads-modal-conf-giftaid').text(data.gift_aid || (payload.gift_aid === 'yes' ? 'Yes (+25% UK Reclaim)' : 'No'));

                // Restore submit button state
                $btn.prop('disabled', false).css('opacity', '1')
                    .html(`<span>Complete Donation of <strong id="ads-btn-total-val">${activeCurrency}${totalAmount.toFixed(2)}</strong></span> <svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>`);

                // Clear card element inputs safely
                if (cardElement && typeof cardElement.clear === 'function') {
                    cardElement.clear();
                }
                $('#ads-card-number, #ads-card-expiry, #ads-card-cvc').val('');
                $('#ads-card-brand-badge').text('');

                // Clear persistent basket storage upon successful donation
                try {
                    localStorage.removeItem(getBasketStorageKey('single'));
                    localStorage.removeItem(getBasketStorageKey('monthly'));
                    localStorage.removeItem('ads_persistent_donation_basket');
                } catch (e) {}

                // Show Celebratory Popup Modal
                $('#ads-donation-success-modal').fadeIn(250).css('display', 'flex');
                $('body').addClass('ads-modal-open');
            }

            function processDonationAjax(currentPayload) {
                $.post(ajaxUrl, currentPayload, function(res) {
                    if (res && res.success && res.data) {
                        const d = res.data;

                        // Handle 3D Secure / Strong Customer Authentication
                        if (d.requires_action && d.client_secret && stripe) {
                            $btn.html('<span class="ads-spinner"></span> Authenticating with your bank...');
                            stripe.handleCardAction(d.client_secret).then(function(actionResult) {
                                if (actionResult.error) {
                                    $('#ads-card-errors').text(actionResult.error.message).show();
                                    $('html, body').animate({ scrollTop: $('#ads-card-section').offset().top - 100 }, 300);
                                    $btn.prop('disabled', false).css('opacity', '1')
                                        .html(`<span>Complete Donation of <strong id="ads-btn-total-val">${activeCurrency}${totalAmount.toFixed(2)}</strong></span> <svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>`);
                                } else {
                                    // 3DS complete, finalize payment
                                    const finalizePayload = Object.assign({}, currentPayload, {
                                        confirm_intent_id: actionResult.paymentIntent.id
                                    });
                                    processDonationAjax(finalizePayload);
                                }
                            });
                            return;
                        }

                        renderSuccessConfirmation(d);
                    } else {
                        const err = (res && res.data && res.data.message) ? res.data.message : 'An error occurred while processing your donation. Please check your card details and try again.';
                        $('#ads-card-errors').text(err).show();
                        $('html, body').animate({ scrollTop: $('#ads-card-section').offset().top - 100 }, 300);
                        $btn.prop('disabled', false).css('opacity', '1')
                            .html(`<span>Complete Donation of <strong id="ads-btn-total-val">${activeCurrency}${totalAmount.toFixed(2)}</strong></span> <svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>`);
                    }
                }).fail(function() {
                    $('#ads-card-errors').text('Payment processor connection failed. Please try again.').show();
                    $('html, body').animate({ scrollTop: $('#ads-card-section').offset().top - 100 }, 300);
                    $btn.prop('disabled', false).css('opacity', '1')
                        .html(`<span>Complete Donation of <strong id="ads-btn-total-val">${activeCurrency}${totalAmount.toFixed(2)}</strong></span> <svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>`);
                });
            }

            if (activeMethod === 'paypal') {
                $btn.prop('disabled', true).css('opacity', '0.7');
                $btn.html('<span class="ads-spinner"></span> Connecting to PayPal...');
                const paypalEmail = <?php echo json_encode($paypal_email); ?> || 'paypal@aryanaaid.org.uk';
                const paypalUrl = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=' + encodeURIComponent(paypalEmail) + 
                    '&item_name=' + encodeURIComponent('Aryana Aid Donation - ' + project) + 
                    '&amount=' + totalAmount.toFixed(2) + 
                    '&currency_code=' + encodeURIComponent(activeCurrencyCode) + 
                    '&no_shipping=1';

                // Record donation in background (and WC order), then direct user to PayPal
                $.post(ajaxUrl, payload).always(function() {
                    window.location.href = paypalUrl;
                });
                return;
            }

            // Real Stripe Card Processing
            $('#ads-card-errors').text('').hide();

            const rawCardNum = ($('#ads-card-number').val() || '').replace(/\D/g, '');
            const rawExpiry = ($('#ads-card-expiry').val() || '').trim();
            const rawCvc = ($('#ads-card-cvc').val() || '').replace(/\D/g, '');

            // 1. Strict Luhn validation (rejects fake cards)
            if (!rawCardNum || !window.adsIsValidLuhn(rawCardNum)) {
                $('#ads-card-errors').text('Invalid card number. Please check your card number and try again.').show();
                $('#ads-card-number').css('border-color', '#EF4444').focus();
                $('html, body').animate({ scrollTop: $('#ads-card-section').offset().top - 100 }, 300);
                return;
            }

            // 2. Strict Expiration validation (rejects past or incomplete dates)
            const expCheck = window.adsIsValidExpiry(rawExpiry);
            if (!expCheck.valid) {
                $('#ads-card-errors').text(expCheck.message).show();
                $('#ads-card-expiry').css('border-color', '#EF4444').focus();
                $('html, body').animate({ scrollTop: $('#ads-card-section').offset().top - 100 }, 300);
                return;
            }

            // 3. Strict CVC validation
            if (rawCvc.length < 3 || rawCvc.length > 4) {
                $('#ads-card-errors').text('Please enter a valid 3 or 4 digit CVC security code.').show();
                $('#ads-card-cvc').css('border-color', '#EF4444').focus();
                $('html, body').animate({ scrollTop: $('#ads-card-section').offset().top - 100 }, 300);
                return;
            }

            const expParts = rawExpiry.replace(/\s+/g, '').split('/');
            const expMonth = parseInt(expParts[0], 10);
            let expYear = parseInt(expParts[1], 10);
            if (expYear < 100) expYear += 2000;

            $btn.prop('disabled', true).css('opacity', '0.7');
            $btn.html('<span class="ads-spinner"></span> Validating card details...');

            const donorFullName = ((($('#ads-title').val() || '') + ' ' + ($('#ads-first-name').val() || '') + ' ' + ($('#ads-last-name').val() || '')).trim()) || 'Valued Donor';
            const donorEmail = $('#ads-email').val() || '';
            const donorPhone = $('#ads-phone').val() || '';
            const donorAddress = {
                line1: $('#ads-address1').val() || undefined,
                line2: $('#ads-address2').val() || undefined,
                city: $('#ads-city').val() || undefined,
                state: $('#ads-county').val() || undefined,
                postal_code: $('#ads-postcode').val() || undefined,
                country: ($('#ads-country').val() === 'United Kingdom' ? 'GB' : ($('#ads-country').val() === 'United States' ? 'US' : undefined))
            };

            // If cardElement is active and mounted, tokenize via Stripe.js client
            if (stripe && cardElement) {
                stripe.createPaymentMethod({
                    type: 'card',
                    card: cardElement,
                    billing_details: {
                        name: donorFullName || undefined,
                        email: donorEmail || undefined,
                        phone: donorPhone || undefined,
                        address: donorAddress
                    }
                }).then(function(result) {
                    if (result.error) {
                        $('#ads-card-errors').text(result.error.message).show();
                        $('html, body').animate({ scrollTop: $('#ads-card-section').offset().top - 100 }, 300);
                        $btn.prop('disabled', false).css('opacity', '1')
                            .html(`<span>Complete Donation of <strong id="ads-btn-total-val">${activeCurrency}${totalAmount.toFixed(2)}</strong></span> <svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>`);
                        return;
                    }

                    payload.payment_method_id = result.paymentMethod.id;
                    $btn.html('<span class="ads-spinner"></span> Processing Secure Donation...');
                    processDonationAjax(payload);
                }).catch(function(err) {
                    $('#ads-card-errors').text(err.message || 'Payment method validation failed.').show();
                    $('html, body').animate({ scrollTop: $('#ads-card-section').offset().top - 100 }, 300);
                    $btn.prop('disabled', false).css('opacity', '1')
                        .html(`<span>Complete Donation of <strong id="ads-btn-total-val">${activeCurrency}${totalAmount.toFixed(2)}</strong></span> <svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>`);
                });
            } else {
                // Direct secure tokenization via backend Stripe API
                payload.card_number = rawCardNum;
                payload.card_exp_month = expMonth;
                payload.card_exp_year = expYear;
                payload.card_cvc = rawCvc;
                payload.card_holder = donorFullName;
                $btn.html('<span class="ads-spinner"></span> Processing Secure Donation...');
                processDonationAjax(payload);
            }
        });

        // Close Modal Helper
        function closeSuccessModal() {
            $('#ads-donation-success-modal').fadeOut(200);
            $('body').removeClass('ads-modal-open');
        }

        $(document).on('click', '#ads-modal-close-btn', function(e) {
            e.preventDefault();
            closeSuccessModal();
        });

        // Click outside dialog to dismiss
        $(document).on('click', '#ads-donation-success-modal', function(e) {
            if ($(e.target).closest('.ads-success-modal-dialog').length === 0) {
                closeSuccessModal();
            }
        });

        // Escape key to dismiss
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#ads-donation-success-modal').is(':visible')) {
                closeSuccessModal();
            }
        });

        // Copy Transaction Reference
        $(document).on('click', '#ads-btn-copy-ref', function(e) {
            e.preventDefault();
            const refText = $('#ads-modal-conf-ref').text().trim();
            if (navigator.clipboard) {
                navigator.clipboard.writeText(refText).then(function() {
                    const $fb = $('.ads-copy-feedback');
                    $fb.fadeIn(150);
                    setTimeout(function() { $fb.fadeOut(200); }, 2000);
                });
            }
        });

        // Make Another Donation button action inside modal
        $(document).on('click', '#ads-modal-donate-again', function(e) {
            e.preventDefault();
            closeSuccessModal();
            $('html, body').animate({ scrollTop: $('#ads-modern-donation-form').offset().top - 80 }, 400);
        });

    });
})(jQuery);
</script>

