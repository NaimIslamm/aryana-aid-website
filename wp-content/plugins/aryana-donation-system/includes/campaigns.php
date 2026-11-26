<?php
/**
 * Campaigns Engine
 * Registers Custom Post Type 'ads_campaign', meta boxes, and shortcode renderer.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register 'ads_campaign' Custom Post Type
 */
add_action('init', function () {
    $labels = [
        'name'               => 'Campaigns',
        'singular_name'      => 'Campaign',
        'menu_name'          => 'Campaigns',
        'add_new'            => 'Add New Campaign',
        'add_new_item'       => 'Add New Campaign',
        'edit_item'          => 'Edit Campaign',
        'new_item'           => 'New Campaign',
        'view_item'          => 'View Campaign',
        'search_items'       => 'Search Campaigns',
        'not_found'          => 'No campaigns found',
        'not_found_in_trash' => 'No campaigns found in Trash',
    ];

    $args = [
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => 'aryana-donations',
        'query_var'          => true,
        'rewrite'            => ['slug' => 'campaign'],
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 2,
        'supports'           => ['title', 'editor', 'thumbnail', 'excerpt'],
    ];

    register_post_type('ads_campaign', $args);
});

/**
 * Register Meta Boxes for Campaign Settings
 */
add_action('add_meta_boxes', function () {
    add_meta_box(
        'ads_campaign_details',
        'Campaign Fundraising & Donation Box Settings',
        'ads_render_campaign_meta_box',
        'ads_campaign',
        'normal',
        'high'
    );
});

function ads_render_campaign_meta_box($post) {
    wp_nonce_field('ads_save_campaign_meta', 'ads_campaign_nonce');

    $goal_enabled = get_post_meta($post->ID, '_goal_enabled', true) === 'yes';
    $goal_amount  = get_post_meta($post->ID, '_goal_amount', true) ?: '';
    $raised_amount= get_post_meta($post->ID, '_raised_amount', true) ?: 0;
    $donors_count = get_post_meta($post->ID, '_donations_count', true) ?: 0;
    $linked_project = get_post_meta($post->ID, '_linked_project', true) ?: '';
    $accent_color = get_post_meta($post->ID, '_accent_color', true) ?: '#D8005A';
    $button_text  = get_post_meta($post->ID, '_button_text', true) ?: 'Donate 💧';
    
    // Default preset pills
    $default_single_pills = [
        ['amount' => 75, 'label' => '', 'impact' => 'Provide a sewing machine to empower a widow with sustainable income'],
        ['amount' => 100, 'label' => '', 'impact' => 'Provides essential emergency food and medical assistance for a family'],
        ['amount' => 290, 'label' => '', 'impact' => 'Equips a vocational training workshop for vulnerable women'],
        ['amount' => 1500, 'label' => '', 'impact' => 'Sponsors a community micro-business hub transforming an entire village'],
    ];

    $default_monthly_pills = [
        ['amount' => 10, 'label' => '', 'impact' => 'Provides regular clean water and hygiene kits to a family every month'],
        ['amount' => 20, 'label' => '', 'impact' => 'Provides monthly food rations and essential nutrition for a child'],
        ['amount' => 30, 'label' => '', 'impact' => 'Supports ongoing education and school supplies for orphaned children'],
        ['amount' => 50, 'label' => '', 'impact' => 'Provides comprehensive monthly medical care and food security for a family'],
    ];

    $saved_single = get_post_meta($post->ID, '_single_pills_data', true);
    if (empty($saved_single) || !is_array($saved_single)) {
        $legacy = get_post_meta($post->ID, '_pills_data', true);
        $single_pills = (!empty($legacy) && is_array($legacy)) ? $legacy : $default_single_pills;
    } else {
        $single_pills = $saved_single;
    }

    $saved_monthly = get_post_meta($post->ID, '_monthly_pills_data', true);
    $monthly_pills = (!empty($saved_monthly) && is_array($saved_monthly)) ? $saved_monthly : $default_monthly_pills;

    $enable_single = get_post_meta($post->ID, '_enable_single', true);
    if ($enable_single === '') {
        $enable_single = 'yes';
    }
    $enable_monthly = get_post_meta($post->ID, '_enable_monthly', true);
    if ($enable_monthly === '') {
        $enable_monthly = 'yes';
    }

    $matrix = ads_get_projects_matrix();
    ?>
    <style>
        .ads-meta-section { margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #e2e8f0; }
        .ads-meta-section h3 { margin-top: 0; color: #1e293b; font-size: 16px; display: flex; align-items: center; gap: 8px; }
        .ads-meta-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 15px; }
        .ads-meta-field { flex: 1; min-width: 220px; }
        .ads-meta-field label { display: block; font-weight: 600; margin-bottom: 5px; color: #334155; }
        .ads-pill-table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; }
        .ads-pill-table th, .ads-pill-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; text-align: left; }
        .ads-pill-table th { background: #f8fafc; font-weight: 600; font-size: 13px; color: #475569; }
        .ads-stat-badge { background: #f1f5f9; border-radius: 6px; padding: 12px 16px; display: inline-flex; flex-direction: column; min-width: 130px; border: 1px solid #e2e8f0; }
        .ads-stat-badge span:first-child { font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; }
        .ads-stat-badge span:last-child { font-size: 20px; color: #0f172a; font-weight: 700; margin-top: 2px; }
        .ads-slot-header { display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px 8px 0 0; padding: 14px 18px; margin-top: 15px; border-bottom: none; }
        .ads-slot-box { border: 1px solid #e2e8f0; border-radius: 0 0 8px 8px; padding: 16px; background: #ffffff; margin-bottom: 25px; }
    </style>

    <!-- SECTION 1: EMBED SHORTCODE -->
    <div class="ads-meta-section" style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 15px; border-radius: 6px;">
        <h3 style="color: #166534; margin: 0 0 6px 0;">📋 Embed Shortcode</h3>
        <p style="margin: 0; color: #15803d; font-size: 14px;">
            Copy and paste this shortcode into any Project Page, post, or Elementor widget:
            <code style="background: #fff; padding: 4px 10px; border-radius: 4px; font-weight: 700; border: 1px solid #86efac; margin-left: 8px;">[campaign_donation_box id="<?php echo esc_attr($post->ID); ?>"]</code>
        </p>
    </div>

    <!-- SECTION 2: FUNDRAISING GOAL & TRACKING -->
    <div class="ads-meta-section">
        <h3>🎯 Fundraising Goal & Progress Bar (Optional)</h3>
        <p style="color: #64748b; margin-top: 0;">If enabled, a visual progress bar and target will show on the donation box. If disabled, it acts as a clean ongoing donation box without a goal.</p>
        
        <div style="margin-bottom: 15px;">
            <label style="font-weight: 600; font-size: 14px; cursor: pointer;">
                <input type="checkbox" name="ads_goal_enabled" value="yes" <?php checked($goal_enabled, true); ?> id="ads_toggle_goal">
                Enable Fundraising Goal & Live Progress Bar
            </label>
        </div>

        <div id="ads_goal_options_box" style="<?php echo $goal_enabled ? '' : 'display:none;'; ?>">
            <div class="ads-meta-row">
                <div class="ads-meta-field">
                    <label for="ads_goal_amount">Target Goal Amount (£)</label>
                    <input type="number" step="any" min="1" name="ads_goal_amount" id="ads_goal_amount" value="<?php echo esc_attr($goal_amount); ?>" placeholder="e.g. 5000" style="width: 100%; max-width: 250px;">
                </div>
                <div class="ads-meta-field">
                    <label for="ads_raised_amount">Total Raised So Far (£)</label>
                    <input type="number" step="any" min="0" name="ads_raised_amount" id="ads_raised_amount" value="<?php echo esc_attr($raised_amount); ?>" style="width: 100%; max-width: 250px;">
                    <p class="description">Automatically updates when donations are received. You can also seed/adjust manually.</p>
                </div>
                <div class="ads-meta-field">
                    <label for="ads_donations_count">Total Donors Count</label>
                    <input type="number" step="1" min="0" name="ads_donations_count" id="ads_donations_count" value="<?php echo esc_attr($donors_count); ?>" style="width: 100%; max-width: 180px;">
                </div>
            </div>

            <?php if (!empty($goal_amount) && (float)$goal_amount > 0): 
                $pct = min(100, round(((float)$raised_amount / (float)$goal_amount) * 100));
            ?>
                <div style="margin-top: 15px; background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; font-weight: 600; margin-bottom: 6px;">
                        <span>Progress: £<?php echo number_format((float)$raised_amount, 2); ?> raised of £<?php echo number_format((float)$goal_amount, 2); ?></span>
                        <span style="color: #038B5C;"><?php echo $pct; ?>% Funded</span>
                    </div>
                    <div style="width: 100%; height: 12px; background: #e2e8f0; border-radius: 6px; overflow: hidden;">
                        <div style="width: <?php echo $pct; ?>%; height: 100%; background: #038B5C; border-radius: 6px;"></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECTION 3: PROJECT LINKING & STYLING -->
    <div class="ads-meta-section">
        <h3>🔗 Project Association & Appearance</h3>
        <div class="ads-meta-row">
            <div class="ads-meta-field">
                <label for="ads_linked_project">Linked Master Project / Cause</label>
                <select name="ads_linked_project" id="ads_linked_project" style="width: 100%; max-width: 320px;">
                    <option value="">-- Use Campaign Title as Project Name --</option>
                    <?php foreach (array_keys($matrix) as $pname): ?>
                        <option value="<?php echo esc_attr($pname); ?>" <?php selected($linked_project, $pname); ?>><?php echo esc_html($pname); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description">Select which cause this maps to in your master checkout matrix.</p>
            </div>

            <div class="ads-meta-field">
                <label for="ads_accent_color">Accent Color</label>
                <input type="color" name="ads_accent_color" id="ads_accent_color" value="<?php echo esc_attr($accent_color); ?>" style="height: 38px; width: 60px; padding: 2px; cursor: pointer; vertical-align: middle;">
                <span style="font-family: monospace; margin-left: 8px;"><?php echo esc_html($accent_color); ?></span>
                <p class="description">Used for active amount pills and the Donate button (Default: <code>#D8005A</code> crimson).</p>
            </div>

            <div class="ads-meta-field">
                <label for="ads_button_text">Donate Button Label</label>
                <input type="text" name="ads_button_text" id="ads_button_text" value="<?php echo esc_attr($button_text); ?>" style="width: 100%; max-width: 250px;">
                <p class="description">e.g. <code>Donate 💧</code> or <code>Donate Now</code>.</p>
            </div>

            <div class="ads-meta-field">
                <?php
                $campaign_currency = get_post_meta($post->ID, '_campaign_currency', true);
                $global_currency = get_option('ads_currency_symbol', '£');
                $global_currency_code = get_option('ads_currency_code', 'GBP');
                ?>
                <label for="ads_campaign_currency">Campaign Currency</label>
                <select name="ads_campaign_currency" id="ads_campaign_currency" style="width: 100%; max-width: 280px; font-weight:600;">
                    <option value="" <?php selected($campaign_currency, ''); ?>>🌐 Use Global Default (<?php echo esc_html($global_currency . ' ' . $global_currency_code); ?>)</option>
                    <option value="GBP|£" <?php selected($campaign_currency, 'GBP|£'); ?>>🇬🇧 British Pound (£ GBP)</option>
                    <option value="USD|$" <?php selected($campaign_currency, 'USD|$'); ?>>🇺🇸 US Dollar ($ USD)</option>
                    <option value="EUR|€" <?php selected($campaign_currency, 'EUR|€'); ?>>🇪🇺 Euro (€ EUR)</option>
                    <option value="CAD|$" <?php selected($campaign_currency, 'CAD|$'); ?>>🇨🇦 Canadian Dollar ($ CAD)</option>
                    <option value="AUD|$" <?php selected($campaign_currency, 'AUD|$'); ?>>🇦🇺 Australian Dollar ($ AUD)</option>
                </select>
                <p class="description">Select the currency for this campaign box (Pound £, Dollar $, or Euro €).</p>
            </div>
        </div>
    </div>

    <!-- SECTION 4: PRESET AMOUNT PILLS & DYNAMIC IMPACT (TWO SEPARATE SLOTS) -->
    <div class="ads-meta-section" style="border-bottom: none; margin-bottom: 0;">
        <h3>💎 Preset Amount Pills & Dynamic Impact Statements</h3>
        <p style="color: #64748b; margin-top: 0;">Configure preset buttons and impact statements for <strong>Give Once (Single)</strong> and <strong>Regular Payment (Monthly)</strong>. You can choose to enable both or show only one payment frequency on this campaign.</p>

        <!-- Allowed Frequency Modes -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px; margin: 15px 0 20px 0;">
            <div style="font-weight: 700; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                ⚙️ Payment Frequencies Enabled on this Campaign:
            </div>
            <div style="display: flex; gap: 24px; flex-wrap: wrap; align-items: center;">
                <label style="display: inline-flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer; color: #0f172a; font-size: 13px;">
                    <input type="checkbox" name="ads_enable_single" id="ads_enable_single" value="yes" <?php checked($enable_single, 'yes'); ?> style="margin: 0; width: 17px; height: 17px;">
                    🌟 Enable Give Once Payment
                </label>
                <label style="display: inline-flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer; color: #0f172a; font-size: 13px;">
                    <input type="checkbox" name="ads_enable_monthly" id="ads_enable_monthly" value="yes" <?php checked($enable_monthly, 'yes'); ?> style="margin: 0; width: 17px; height: 17px;">
                    🔄 Enable Regular Payment (Monthly)
                </label>
            </div>
            <p style="margin: 8px 0 0 0; color: #64748b; font-size: 12px; line-height: 1.4;">
                Tip: Uncheck either option if this campaign should ONLY accept one-off gifts or ONLY accept recurring monthly donations. When only one frequency is enabled, the toggle switcher on the frontend donation box is hidden automatically.
            </p>
        </div>

        <!-- Slot Switcher Buttons -->
        <div style="display: flex; gap: 10px; margin: 15px 0 5px 0;">
            <button type="button" class="button <?php echo $enable_single === 'yes' ? 'button-primary' : 'button-secondary'; ?> ads-slot-tab-btn" data-target="slot-single" style="font-weight: 700; padding: 6px 18px; border-radius: 6px;" id="ads_tab_btn_single">
                🌟 Give Once Payment <span id="ads_status_single" style="font-size: 11px; opacity: 0.8; margin-left: 6px;"><?php echo $enable_single === 'yes' ? '' : '(Disabled)'; ?></span>
            </button>
            <button type="button" class="button <?php echo ($enable_single !== 'yes' && $enable_monthly === 'yes') ? 'button-primary' : 'button-secondary'; ?> ads-slot-tab-btn" data-target="slot-monthly" style="font-weight: 700; padding: 6px 18px; border-radius: 6px;" id="ads_tab_btn_monthly">
                🔄 Regular Payment <span id="ads_status_monthly" style="font-size: 11px; opacity: 0.8; margin-left: 6px;"><?php echo $enable_monthly === 'yes' ? '' : '(Disabled)'; ?></span>
            </button>
        </div>

        <!-- SLOT 1: GIVE ONCE PAYMENT -->
        <div id="ads_slot_single_wrap" class="ads-slot-panel" style="<?php echo ($enable_single === 'yes') ? '' : 'display: none;'; ?>">
            <div class="ads-slot-header" style="background: #fdf2f8; border-color: #fbcfe8;">
                <div>
                    <strong style="color: #9d174d; font-size: 15px;">🌟 Give Once Payment Preset Amounts</strong>
                    <span style="color: #be185d; font-size: 13px; margin-left: 10px;">(Default: £75, £100, £290, £1500)</span>
                </div>
            </div>
            <div class="ads-slot-box" style="border-color: #fbcfe8;">
                <table class="ads-pill-table" id="ads_single_pills_table">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Amount (£)</th>
                            <th style="width: 160px;">Button Subtitle (optional)</th>
                            <th>Dynamic Impact Message (shows in grey impact box)</th>
                            <th style="width: 60px; text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($single_pills as $idx => $pill): ?>
                            <tr class="ads-pill-row">
                                <td>
                                    <input type="number" step="any" min="1" name="ads_single_pills[<?php echo $idx; ?>][amount]" value="<?php echo esc_attr($pill['amount'] ?? 50); ?>" style="width: 100%; font-weight: 700;" required>
                                </td>
                                <td>
                                    <input type="text" name="ads_single_pills[<?php echo $idx; ?>][label]" value="<?php echo esc_attr($pill['label'] ?? ''); ?>" placeholder="e.g. Water Pump" style="width: 100%;">
                                </td>
                                <td>
                                    <input type="text" name="ads_single_pills[<?php echo $idx; ?>][impact]" value="<?php echo esc_attr($pill['impact'] ?? ''); ?>" placeholder="What this one-off donation achieves..." style="width: 100%;">
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" class="button button-link-delete ads-remove-single-pill-btn" style="color: #ef4444;">&times;</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="margin-top: 12px; margin-bottom: 0;">
                    <button type="button" class="button button-secondary" id="ads_add_single_pill_btn">+ Add Give Once Preset Pill</button>
                </p>
            </div>
        </div>

        <!-- SLOT 2: REGULAR PAYMENT -->
        <div id="ads_slot_monthly_wrap" class="ads-slot-panel" style="<?php echo ($enable_single !== 'yes' && $enable_monthly === 'yes') ? '' : 'display: none;'; ?>">
            <div class="ads-slot-header" style="background: #f0fdf4; border-color: #bbf7d0;">
                <div>
                    <strong style="color: #166534; font-size: 15px;">🔄 Regular Payment Preset Amounts</strong>
                    <span style="color: #15803d; font-size: 13px; margin-left: 10px;">(Default: £10, £20, £30, £50)</span>
                </div>
            </div>
            <div class="ads-slot-box" style="border-color: #bbf7d0;">
                <table class="ads-pill-table" id="ads_monthly_pills_table">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Monthly Amount (£)</th>
                            <th style="width: 160px;">Button Subtitle (optional)</th>
                            <th>Dynamic Impact Message (shows in grey impact box)</th>
                            <th style="width: 60px; text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthly_pills as $idx => $pill): ?>
                            <tr class="ads-pill-row">
                                <td>
                                    <input type="number" step="any" min="1" name="ads_monthly_pills[<?php echo $idx; ?>][amount]" value="<?php echo esc_attr($pill['amount'] ?? 10); ?>" style="width: 100%; font-weight: 700;" required>
                                </td>
                                <td>
                                    <input type="text" name="ads_monthly_pills[<?php echo $idx; ?>][label]" value="<?php echo esc_attr($pill['label'] ?? ''); ?>" placeholder="e.g. Monthly Water" style="width: 100%;">
                                </td>
                                <td>
                                    <input type="text" name="ads_monthly_pills[<?php echo $idx; ?>][impact]" value="<?php echo esc_attr($pill['impact'] ?? ''); ?>" placeholder="What this monthly regular donation achieves..." style="width: 100%;">
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" class="button button-link-delete ads-remove-monthly-pill-btn" style="color: #ef4444;">&times;</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="margin-top: 12px; margin-bottom: 0;">
                    <button type="button" class="button button-secondary" id="ads_add_monthly_pill_btn">+ Add Regular Monthly Preset Pill</button>
                </p>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Toggle goal options box
        var goalCheckbox = document.getElementById('ads_toggle_goal');
        var goalBox = document.getElementById('ads_goal_options_box');
        if (goalCheckbox && goalBox) {
            goalCheckbox.addEventListener('change', function () {
                goalBox.style.display = this.checked ? 'block' : 'none';
            });
        }

        // Slot Tabs Switcher
        var tabButtons = document.querySelectorAll('.ads-slot-tab-btn');
        var slotSingle = document.getElementById('ads_slot_single_wrap');
        var slotMonthly = document.getElementById('ads_slot_monthly_wrap');
        tabButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                tabButtons.forEach(function (b) {
                    b.classList.remove('button-primary');
                    b.classList.add('button-secondary');
                });
                this.classList.remove('button-secondary');
                this.classList.add('button-primary');

                var target = this.getAttribute('data-target');
                if (target === 'slot-single') {
                    if (slotSingle) slotSingle.style.display = 'block';
                    if (slotMonthly) slotMonthly.style.display = 'none';
                } else {
                    if (slotSingle) slotSingle.style.display = 'none';
                    if (slotMonthly) slotMonthly.style.display = 'block';
                }
            });
        });

        // Enable / Disable Frequency Checkboxes
        var chkSingle = document.getElementById('ads_enable_single');
        var chkMonthly = document.getElementById('ads_enable_monthly');
        var statusSingle = document.getElementById('ads_status_single');
        var statusMonthly = document.getElementById('ads_status_monthly');
        var tabBtnSingle = document.getElementById('ads_tab_btn_single');
        var tabBtnMonthly = document.getElementById('ads_tab_btn_monthly');

        function updateFreqUI() {
            if (!chkSingle.checked && !chkMonthly.checked) {
                alert('At least one payment frequency (Give Once or Regular Payment) must be enabled.');
                chkSingle.checked = true;
            }
            if (statusSingle) statusSingle.textContent = chkSingle.checked ? '' : '(Disabled)';
            if (statusMonthly) statusMonthly.textContent = chkMonthly.checked ? '' : '(Disabled)';

            if (!chkSingle.checked && tabBtnSingle && tabBtnSingle.classList.contains('button-primary')) {
                if (tabBtnMonthly) tabBtnMonthly.click();
            } else if (!chkMonthly.checked && tabBtnMonthly && tabBtnMonthly.classList.contains('button-primary')) {
                if (tabBtnSingle) tabBtnSingle.click();
            }
        }

        if (chkSingle && chkMonthly) {
            chkSingle.addEventListener('change', updateFreqUI);
            chkMonthly.addEventListener('change', updateFreqUI);
        }

        // 1. Add Single Pill Row
        var addSingleBtn = document.getElementById('ads_add_single_pill_btn');
        var singleTableBody = document.querySelector('#ads_single_pills_table tbody');
        if (addSingleBtn && singleTableBody) {
            addSingleBtn.addEventListener('click', function () {
                var newIdx = singleTableBody.querySelectorAll('tr').length;
                var tr = document.createElement('tr');
                tr.className = 'ads-pill-row';
                tr.innerHTML = '<td><input type="number" step="any" min="1" name="ads_single_pills[' + newIdx + '][amount]" value="50" style="width: 100%; font-weight: 700;" required></td>' +
                               '<td><input type="text" name="ads_single_pills[' + newIdx + '][label]" value="" placeholder="e.g. Hand Pump" style="width: 100%;"></td>' +
                               '<td><input type="text" name="ads_single_pills[' + newIdx + '][impact]" value="" placeholder="What this one-off donation achieves..." style="width: 100%;"></td>' +
                               '<td style="text-align: center;"><button type="button" class="button button-link-delete ads-remove-single-pill-btn" style="color: #ef4444;">&times;</button></td>';
                singleTableBody.appendChild(tr);
            });
        }

        // Remove Single Pill Row
        if (singleTableBody) {
            singleTableBody.addEventListener('click', function (e) {
                if (e.target && e.target.classList.contains('ads-remove-single-pill-btn')) {
                    var row = e.target.closest('tr');
                    if (singleTableBody.querySelectorAll('tr').length > 1) {
                        row.remove();
                    } else {
                        alert('At least one preset amount is required.');
                    }
                }
            });
        }

        // 2. Add Monthly Pill Row
        var addMonthlyBtn = document.getElementById('ads_add_monthly_pill_btn');
        var monthlyTableBody = document.querySelector('#ads_monthly_pills_table tbody');
        if (addMonthlyBtn && monthlyTableBody) {
            addMonthlyBtn.addEventListener('click', function () {
                var newIdx = monthlyTableBody.querySelectorAll('tr').length;
                var tr = document.createElement('tr');
                tr.className = 'ads-pill-row';
                tr.innerHTML = '<td><input type="number" step="any" min="1" name="ads_monthly_pills[' + newIdx + '][amount]" value="10" style="width: 100%; font-weight: 700;" required></td>' +
                               '<td><input type="text" name="ads_monthly_pills[' + newIdx + '][label]" value="" placeholder="e.g. Monthly Water" style="width: 100%;"></td>' +
                               '<td><input type="text" name="ads_monthly_pills[' + newIdx + '][impact]" value="" placeholder="What this monthly regular donation achieves..." style="width: 100%;"></td>' +
                               '<td style="text-align: center;"><button type="button" class="button button-link-delete ads-remove-monthly-pill-btn" style="color: #ef4444;">&times;</button></td>';
                monthlyTableBody.appendChild(tr);
            });
        }

        // Remove Monthly Pill Row
        if (monthlyTableBody) {
            monthlyTableBody.addEventListener('click', function (e) {
                if (e.target && e.target.classList.contains('ads-remove-monthly-pill-btn')) {
                    var row = e.target.closest('tr');
                    if (monthlyTableBody.querySelectorAll('tr').length > 1) {
                        row.remove();
                    } else {
                        alert('At least one monthly preset amount is required.');
                    }
                }
            });
        }
    });
    </script>
    <?php
}

/**
 * Save Campaign Meta Box Data
 */
add_action('save_post_ads_campaign', function ($post_id) {
    if (!isset($_POST['ads_campaign_nonce']) || !wp_verify_nonce($_POST['ads_campaign_nonce'], 'ads_save_campaign_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Goal settings
    $goal_enabled = isset($_POST['ads_goal_enabled']) ? 'yes' : 'no';
    update_post_meta($post_id, '_goal_enabled', $goal_enabled);
    update_post_meta($post_id, '_goal_amount', floatval($_POST['ads_goal_amount'] ?? 0));

    if (isset($_POST['ads_raised_amount'])) {
        update_post_meta($post_id, '_raised_amount', floatval($_POST['ads_raised_amount']));
    }
    if (isset($_POST['ads_donations_count'])) {
        update_post_meta($post_id, '_donations_count', intval($_POST['ads_donations_count']));
    }

    // Association and styling
    update_post_meta($post_id, '_linked_project', sanitize_text_field($_POST['ads_linked_project'] ?? ''));
    update_post_meta($post_id, '_accent_color', sanitize_hex_color($_POST['ads_accent_color'] ?? '#D8005A'));
    update_post_meta($post_id, '_button_text', sanitize_text_field($_POST['ads_button_text'] ?? 'Donate 💧'));
    update_post_meta($post_id, '_campaign_currency', sanitize_text_field($_POST['ads_campaign_currency'] ?? ''));

    // Payment frequencies enabled
    $enable_single = isset($_POST['ads_enable_single']) ? 'yes' : 'no';
    $enable_monthly = isset($_POST['ads_enable_monthly']) ? 'yes' : 'no';
    if ($enable_single === 'no' && $enable_monthly === 'no') {
        $enable_single = 'yes';
    }
    update_post_meta($post_id, '_enable_single', $enable_single);
    update_post_meta($post_id, '_enable_monthly', $enable_monthly);

    // 1. Give Once (Single Payment) pills data
    $clean_single = [];
    $raw_single = !empty($_POST['ads_single_pills']) ? $_POST['ads_single_pills'] : (!empty($_POST['ads_pills']) ? $_POST['ads_pills'] : []);
    if (!empty($raw_single) && is_array($raw_single)) {
        foreach ($raw_single as $p) {
            $amt = floatval($p['amount'] ?? 0);
            if ($amt > 0) {
                $clean_single[] = [
                    'amount' => $amt,
                    'label'  => sanitize_text_field($p['label'] ?? ''),
                    'impact' => sanitize_text_field($p['impact'] ?? ''),
                ];
            }
        }
    }

    if (empty($clean_single)) {
        $clean_single = [
            ['amount' => 75, 'label' => '', 'impact' => 'Provide a sewing machine to empower a widow with sustainable income'],
            ['amount' => 100, 'label' => '', 'impact' => 'Provides essential emergency food and medical assistance for a family'],
            ['amount' => 290, 'label' => '', 'impact' => 'Equips a vocational training workshop for vulnerable women'],
            ['amount' => 1500, 'label' => '', 'impact' => 'Sponsors a community micro-business hub transforming an entire village'],
        ];
    }

    update_post_meta($post_id, '_single_pills_data', $clean_single);
    update_post_meta($post_id, '_pills_data', $clean_single); // Legacy alias

    // 2. Regular Payment (Give Monthly) pills data
    $clean_monthly = [];
    if (!empty($_POST['ads_monthly_pills']) && is_array($_POST['ads_monthly_pills'])) {
        foreach ($monthly_pills_in = $_POST['ads_monthly_pills'] as $p) {
            $amt = floatval($p['amount'] ?? 0);
            if ($amt > 0) {
                $clean_monthly[] = [
                    'amount' => $amt,
                    'label'  => sanitize_text_field($p['label'] ?? ''),
                    'impact' => sanitize_text_field($p['impact'] ?? ''),
                ];
            }
        }
    }

    if (empty($clean_monthly)) {
        $clean_monthly = [
            ['amount' => 10, 'label' => '', 'impact' => 'Provides regular clean water and hygiene kits to a family every month'],
            ['amount' => 20, 'label' => '', 'impact' => 'Provides monthly food rations and essential nutrition for a child'],
            ['amount' => 30, 'label' => '', 'impact' => 'Supports ongoing education and school supplies for orphaned children'],
            ['amount' => 50, 'label' => '', 'impact' => 'Provides comprehensive monthly medical care and food security for a family'],
        ];
    }

    update_post_meta($post_id, '_monthly_pills_data', $clean_monthly);
});

/**
 * Custom Columns in Campaigns Admin List
 */
add_filter('manage_ads_campaign_posts_columns', function ($columns) {
    $new_cols = [];
    $new_cols['cb'] = $columns['cb'];
    $new_cols['title'] = 'Campaign Title';
    $new_cols['linked_project'] = 'Linked Project';
    $new_cols['goal_progress'] = 'Goal & Progress';
    $new_cols['shortcode'] = 'Shortcode';
    $new_cols['donors'] = 'Donors';
    $new_cols['date'] = $columns['date'];
    return $new_cols;
});

add_action('manage_ads_campaign_posts_custom_column', function ($column, $post_id) {
    if ($column === 'linked_project') {
        $proj = get_post_meta($post_id, '_linked_project', true);
        echo esc_html($proj ?: get_the_title($post_id));
    } elseif ($column === 'goal_progress') {
        $goal_enabled = get_post_meta($post_id, '_goal_enabled', true) === 'yes';
        $goal = (float) get_post_meta($post_id, '_goal_amount', true);
        $raised = (float) get_post_meta($post_id, '_raised_amount', true);

        if ($goal_enabled && $goal > 0) {
            $pct = min(100, round(($raised / $goal) * 100));
            echo '<strong>£' . number_format($raised, 2) . '</strong> of £' . number_format($goal, 2) . '<br>';
            echo '<div style="background:#e2e8f0; border-radius:4px; height:8px; width:120px; overflow:hidden; margin-top:4px;">';
            echo '<div style="background:#038B5C; height:100%; width:' . $pct . '%;"></div></div>';
            echo '<span style="font-size:11px; color:#64748b;">' . $pct . '% Funded</span>';
        } else {
            echo '<span style="color:#038B5C; font-weight:600;">£' . number_format($raised, 2) . '</span><br>';
            echo '<span style="color:#94a3b8; font-size:12px;">Ongoing (No Goal)</span>';
        }
    } elseif ($column === 'shortcode') {
        echo '<code style="background:#f1f5f9; padding:3px 6px; border-radius:4px; font-size:12px; user-select:all;">[campaign_donation_box id="' . $post_id . '"]</code>';
    } elseif ($column === 'donors') {
        $count = (int) get_post_meta($post_id, '_donations_count', true);
        echo '<strong>' . number_format($count) . '</strong>';
    }
}, 10, 2);

/**
 * SHORTCODES: [campaign_donation_box], [donation_box], [campaign_donation_card], [campaign_box], [donation_card]
 * Renders the dedicated project donation card matching the user's screenshot.
 */
add_shortcode('campaign_donation_box', 'ads_render_campaign_donation_box_shortcode');
add_shortcode('donation_box', 'ads_render_campaign_donation_box_shortcode');
add_shortcode('campaign_donation_card', 'ads_render_campaign_donation_box_shortcode');
add_shortcode('campaign_box', 'ads_render_campaign_donation_box_shortcode');
add_shortcode('donation_card', 'ads_render_campaign_donation_box_shortcode');
add_shortcode('campaign', 'ads_render_campaign_donation_box_shortcode');
add_shortcode('ads_campaign_box', 'ads_render_campaign_donation_box_shortcode');
add_shortcode('ads_campaign_donation_box', 'ads_render_campaign_donation_box_shortcode');

function ads_render_campaign_donation_box_shortcode($atts) {
    // Ensure styles and scripts are always loaded
    wp_enqueue_style('ads-donation-css');
    wp_enqueue_script('ads-donation-js');

    $atts = shortcode_atts([
        'id'        => 0,
        'slug'      => '',
        'name'      => '',
        'campaign'  => '',
        'project'   => '',
        'accent'    => '',
    ], $atts, 'campaign_donation_box');

    // Clean up id if string or slug passed as id
    $raw_id = trim((string)($atts['id'] ?? ''));
    $post_id = 0;
    if (is_numeric($raw_id)) {
        $post_id = intval($raw_id);
    } elseif (!empty($raw_id)) {
        $atts['slug'] = $raw_id;
    }

    $target_slug = !empty($atts['slug']) ? $atts['slug'] : (!empty($atts['name']) ? $atts['name'] : $atts['campaign']);

    if (!$post_id && !empty($target_slug)) {
        $found = get_page_by_path(sanitize_title($target_slug), OBJECT, 'ads_campaign');
        if ($found) {
            $post_id = $found->ID;
        } else {
            // Also try search by post_title or post_name directly
            global $wpdb;
            $found_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'ads_campaign' AND (post_name = %s OR post_title = %s) AND post_status = 'publish' LIMIT 1",
                sanitize_title($target_slug),
                $target_slug
            ));
            if ($found_id) $post_id = intval($found_id);
        }
    }

    // Auto-detect campaign from current page context if still 0
    if (!$post_id) {
        $curr_page_id = get_the_ID();
        if (!$curr_page_id) {
            if (!empty($_GET['elementor-preview'])) {
                $curr_page_id = intval($_GET['elementor-preview']);
            } elseif (!empty($_GET['post'])) {
                $curr_page_id = intval($_GET['post']);
            } elseif (!empty($_POST['editor_post_id'])) {
                $curr_page_id = intval($_POST['editor_post_id']);
            }
        }
        if ($curr_page_id) {
            $curr_slug = get_post_field('post_name', $curr_page_id);
            $curr_title = get_the_title($curr_page_id);
            global $wpdb;
            $auto_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'ads_campaign' AND (post_name = %s OR post_title = %s) AND post_status = 'publish' LIMIT 1",
                $curr_slug,
                $curr_title
            ));
            if ($auto_id) {
                $post_id = intval($auto_id);
            }
        }
    }

    // Fallback: if still 0 and there are published campaigns, load the most recent published campaign
    if (!$post_id) {
        global $wpdb;
        $first_camp = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'ads_campaign' AND post_status = 'publish' ORDER BY ID DESC LIMIT 1");
        if ($first_camp) {
            $post_id = intval($first_camp);
        }
    }

    // Fallback defaults if no campaign found
    $title = 'Support Our Cause';
    $goal_enabled = false;
    $goal_amount = 0;
    $raised_amount = 0;
    $linked_project = !empty($atts['project']) ? sanitize_text_field($atts['project']) : 'General Donation';
    $accent_color = !empty($atts['accent']) ? sanitize_hex_color($atts['accent']) : '#D8005A';
    $button_text = 'Donate 💧';

    $default_single_pills = [
        ['amount' => 75, 'label' => '', 'impact' => 'Provide a sewing machine to empower a widow with sustainable income'],
        ['amount' => 100, 'label' => '', 'impact' => 'Provides essential emergency food and medical assistance for a family'],
        ['amount' => 290, 'label' => '', 'impact' => 'Equips a vocational training workshop for vulnerable women'],
        ['amount' => 1500, 'label' => '', 'impact' => 'Sponsors a community micro-business hub transforming an entire village'],
    ];

    $default_monthly_pills = [
        ['amount' => 10, 'label' => '', 'impact' => 'Provides regular clean water and hygiene kits to a family every month'],
        ['amount' => 20, 'label' => '', 'impact' => 'Provides monthly food rations and essential nutrition for a child'],
        ['amount' => 30, 'label' => '', 'impact' => 'Supports ongoing education and school supplies for orphaned children'],
        ['amount' => 50, 'label' => '', 'impact' => 'Provides comprehensive monthly medical care and food security for a family'],
    ];

    $single_pills = $default_single_pills;
    $monthly_pills = $default_monthly_pills;

    if ($post_id > 0) {
        $campaign_post = get_post($post_id);
        if ($campaign_post) {
            $title = $campaign_post->post_title;
            $goal_enabled = get_post_meta($post_id, '_goal_enabled', true) === 'yes';
            $goal_amount = (float) get_post_meta($post_id, '_goal_amount', true);
            $raised_amount = (float) get_post_meta($post_id, '_raised_amount', true);
            $meta_project = get_post_meta($post_id, '_linked_project', true);
            if (!empty($meta_project)) $linked_project = $meta_project;
            $meta_accent = get_post_meta($post_id, '_accent_color', true);
            if (!empty($meta_accent)) $accent_color = $meta_accent;
            $meta_btn = get_post_meta($post_id, '_button_text', true);
            if (!empty($meta_btn)) $button_text = $meta_btn;

            $saved_single = get_post_meta($post_id, '_single_pills_data', true);
            if (empty($saved_single) || !is_array($saved_single)) {
                $legacy = get_post_meta($post_id, '_pills_data', true);
                if (!empty($legacy) && is_array($legacy)) $saved_single = $legacy;
            }
            if (!empty($saved_single) && is_array($saved_single)) {
                $single_pills = $saved_single;
            }

            $saved_monthly = get_post_meta($post_id, '_monthly_pills_data', true);
            if (!empty($saved_monthly) && is_array($saved_monthly)) {
                $monthly_pills = $saved_monthly;
            }

            $enable_single = get_post_meta($post_id, '_enable_single', true);
            if ($enable_single === '') $enable_single = 'yes';
            $enable_monthly = get_post_meta($post_id, '_enable_monthly', true);
            if ($enable_monthly === '') $enable_monthly = 'yes';
        }
    }

    if (empty($enable_single)) $enable_single = 'yes';
    if (empty($enable_monthly)) $enable_monthly = 'yes';
    if ($enable_single !== 'yes' && $enable_monthly !== 'yes') {
        $enable_single = 'yes';
    }

    $show_toggle = ($enable_single === 'yes' && $enable_monthly === 'yes');
    $default_mode = ($enable_single === 'yes') ? 'single' : 'monthly';

    // If project is still General Donation and current page is Water Appeal, link to Water - Well
    if ($linked_project === 'General Donation') {
        $curr_title = get_the_title();
        if (stripos($curr_title, 'water') !== false) {
            $linked_project = 'Water - Well';
        }
    }

    $currency = get_option('ads_currency_symbol', '£');
    $currency_code = get_option('ads_currency_code', 'GBP');
    $camp_curr = get_post_meta($post_id, '_campaign_currency', true);
    if (!empty($camp_curr) && strpos($camp_curr, '|') !== false) {
        list($c_code, $c_sym) = explode('|', $camp_curr);
        $currency = $c_sym;
        $currency_code = $c_code;
    }
    $uid = 'camp_box_' . ($post_id ?: wp_generate_password(6, false, false));

    ob_start();
    ?>
    <div class="aryana-donation-wrapper" style="width: 100%; box-sizing: border-box;">
        <div class="ads-campaign-card" id="<?php echo esc_attr($uid); ?>" data-campaign-id="<?php echo esc_attr($post_id); ?>" data-project="<?php echo esc_attr($linked_project); ?>" data-accent="<?php echo esc_attr($accent_color); ?>" data-enable-single="<?php echo esc_attr($enable_single); ?>" data-enable-monthly="<?php echo esc_attr($enable_monthly); ?>" data-default-mode="<?php echo esc_attr($default_mode); ?>" data-currency="<?php echo esc_attr($currency); ?>" data-currency-code="<?php echo esc_attr($currency_code); ?>" style="--campaign-accent: <?php echo esc_attr($accent_color); ?>; width: 100%; margin: 0 auto; box-sizing: border-box;">
            
            <?php if ($goal_enabled && $goal_amount > 0): 
                $pct = min(100, round(($raised_amount / $goal_amount) * 100));
            ?>
                <!-- Optional Live Fundraising Goal Bar -->
                <div class="ads-campaign-goal-bar">
                    <div class="ads-campaign-goal-info">
                        <span class="ads-goal-raised"><strong><?php echo esc_html($currency . number_format($raised_amount, 2)); ?></strong> raised</span>
                        <span class="ads-goal-target">Target: <?php echo esc_html($currency . number_format($goal_amount, 2)); ?> (<?php echo $pct; ?>%)</span>
                    </div>
                    <div class="ads-goal-progress-track">
                        <div class="ads-goal-progress-fill" style="width: <?php echo $pct; ?>%;"></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Campaign Header (Donate Now Script + Campaign Title) -->
            <div class="ads-campaign-header-block">
                <span class="ads-campaign-script-title">Donate Now</span>
                <h3 class="ads-campaign-heading"><?php echo esc_html($title); ?></h3>
            </div>

            <!-- 1. Single Payment / Monthly Payment Toggle -->
            <?php if ($show_toggle): ?>
                <div class="ads-campaign-freq-toggle">
                    <button type="button" class="ads-campaign-freq-btn active" data-freq="single">Single Payment</button>
                    <button type="button" class="ads-campaign-freq-btn" data-freq="monthly">Monthly Payment</button>
                </div>
            <?php endif; ?>

            <!-- 2. Single Payment (Give Once) Amount Pills Grid -->
            <div class="ads-campaign-pills-grid ads-pills-grid-single ads-pills-count-<?php echo count($single_pills); ?>" style="<?php echo ($default_mode === 'single' && $enable_single === 'yes') ? 'display: grid;' : 'display: none;'; ?>">
                <?php foreach ($single_pills as $idx => $p): 
                    $amt = $p['amount'];
                    $sub = !empty($p['label']) ? $p['label'] : '';
                    $impact = !empty($p['impact']) ? $p['impact'] : 'Your generous donation delivers immediate life-saving aid.';
                    $package_name = !empty($sub) ? ($currency . $amt . ' ' . $sub) : ($currency . $amt);
                ?>
                    <button type="button" 
                            class="ads-campaign-pill <?php echo $idx === 0 ? 'active' : ''; ?>" 
                            data-amount="<?php echo esc_attr($amt); ?>" 
                            data-impact="<?php echo esc_attr($impact); ?>"
                            data-package="<?php echo esc_attr($package_name); ?>"
                            data-freq="single">
                        <span class="ads-pill-amount"><?php echo esc_html($currency . $amt); ?></span>
                        <?php if (!empty($sub)): ?>
                            <span class="ads-pill-sub"><?php echo esc_html($sub); ?></span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- 3. Monthly Payment Amount Pills Grid -->
            <div class="ads-campaign-pills-grid ads-pills-grid-monthly ads-pills-count-<?php echo count($monthly_pills); ?>" style="<?php echo ($default_mode === 'monthly' && $enable_monthly === 'yes') ? 'display: grid;' : 'display: none;'; ?>">
                <?php foreach ($monthly_pills as $idx => $p): 
                    $amt = $p['amount'];
                    $sub = !empty($p['label']) ? $p['label'] : '';
                    $impact = !empty($p['impact']) ? $p['impact'] : 'Your regular donation delivers ongoing, sustainable support.';
                    $package_name = !empty($sub) ? ($currency . $amt . '/mo ' . $sub) : ($currency . $amt . '/mo Regular Donation');
                ?>
                    <button type="button" 
                            class="ads-campaign-pill <?php echo $idx === 0 ? 'active' : ''; ?>" 
                            data-amount="<?php echo esc_attr($amt); ?>" 
                            data-impact="<?php echo esc_attr($impact); ?>"
                            data-package="<?php echo esc_attr($package_name); ?>"
                            data-freq="monthly">
                        <span class="ads-pill-amount"><?php echo esc_html($currency . $amt); ?></span>
                        <?php if (!empty($sub)): ?>
                            <span class="ads-pill-sub"><?php echo esc_html($sub); ?></span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- 4. Other Custom Amount Input -->
            <div class="ads-campaign-other-row">
                <span class="ads-other-label"><?php echo esc_html($currency); ?></span>
                <input type="number" step="any" min="1" class="ads-campaign-other-input" placeholder="Other amount">
            </div>

            <!-- 5. White Impact Statement / Package Box -->
            <div class="ads-campaign-impact-box">
                <?php 
                $initial_impact = ($default_mode === 'monthly') ? ($monthly_pills[0]['impact'] ?? '') : ($single_pills[0]['impact'] ?? '');
                echo esc_html($initial_impact ?: 'Your donation provides sustainable empowerment and life-saving relief.'); 
                ?>
            </div>

            <!-- 6. Donation Type Selector -->
            <div class="ads-campaign-type-row">
                <select class="ads-campaign-type-select">
                    <option value="General Donation" selected>General Donation</option>
                    <option value="Sadaqah">Sadaqah</option>
                    <option value="Zakat">Zakat</option>
                    <option value="Lillah">Lillah</option>
                    <option value="Charitable Donation">Charitable Donation</option>
                </select>
                <span class="ads-campaign-select-arrow">
                    <svg viewBox="0 0 24 24" width="18" height="18"><path fill="#FFFFFF" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
                </span>
            </div>

            <!-- 7. Appeal Location Selector (Where most needed) -->
            <div class="ads-campaign-location-row">
                <select class="ads-campaign-location-select">
                    <option value="Where most needed" selected>Where most needed</option>
                    <option value="Afghanistan">Afghanistan</option>
                    <option value="Gaza">Gaza</option>
                    <option value="Yemen">Yemen</option>
                    <option value="Pakistan">Pakistan</option>
                    <option value="Bangladesh">Bangladesh</option>
                </select>
                <span class="ads-campaign-select-arrow">
                    <svg viewBox="0 0 24 24" width="18" height="18"><path fill="#FFFFFF" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
                </span>
            </div>

            <!-- 8. Donate Button Wrapper -->
            <div class="ads-campaign-btn-wrap">
                <button type="button" class="ads-campaign-donate-btn">
                    <span>Donate Now</span>
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; margin-left:4px;">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>

        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Top Navigation Switcher on Campaigns Admin screens
 */
add_action('all_admin_notices', function () {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'ads_campaign') {
        ?>
        <div style="display: flex; gap: 8px; margin: 20px 0 15px 0;">
            <a href="<?php echo admin_url('admin.php?page=aryana-donations'); ?>" class="button button-secondary" style="font-weight:600;">⚙️ Dashboard & Settings (6 Tabs)</a>
            <a href="<?php echo admin_url('edit.php?post_type=ads_campaign'); ?>" class="button button-primary" style="background:#038B5C; border-color:#026E49; font-weight:700;">🎯 Campaigns</a>
            <a href="<?php echo admin_url('admin.php?page=aryana-donations-list'); ?>" class="button button-secondary" style="font-weight:600;">💳 All Donations & Donors</a>
        </div>
        <?php
    }
});

