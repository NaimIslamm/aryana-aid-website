<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_menu_page(
        'Donation System Dashboard',
        'Donations',
        'manage_options',
        'aryana-donations',
        'ads_render_admin_settings_page',
        'dashicons-heart',
        25
    );

    // Explicit first submenu for the 6-tabs settings dashboard
    add_submenu_page(
        'aryana-donations',
        'Dashboard & Settings',
        'Dashboard & Settings',
        'manage_options',
        'aryana-donations',
        'ads_render_admin_settings_page',
        1
    );
}, 5);


function ads_render_admin_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $valid_tabs = ['projects', 'fees', 'donor_details', 'giftaid', 'stay_in_touch', 'gateways', 'other_ways', 'quick_donate', 'donation_drawer'];
    $active_tab = 'projects';
    if (!empty($_POST['active_tab']) && in_array(sanitize_key($_POST['active_tab']), $valid_tabs, true)) {
        $active_tab = sanitize_key($_POST['active_tab']);
    } elseif (!empty($_GET['tab']) && in_array(sanitize_key($_GET['tab']), $valid_tabs, true)) {
        $active_tab = sanitize_key($_GET['tab']);
    }

    $message = '';
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('ads_save_settings_nonce')) {

        // TAB 1: PROJECTS & MATRIX
        if ($active_tab === 'projects') {
            $matrix = ads_get_projects_matrix();
            $submitted = $_POST['projects'] ?? [];

            // Delete requested project
            if (!empty($_POST['delete_project'])) {
                $del_key = sanitize_text_field($_POST['delete_project']);
                if (isset($matrix[$del_key])) {
                    unset($matrix[$del_key]);
                    $message = "Project \"$del_key\" deleted successfully!";
                }
            } else {
                // Update existing
                $updated_matrix = [];
                foreach ($submitted as $orig_name => $row) {
                    $new_name = sanitize_text_field($row['name'] ?? $orig_name);
                    if (empty($new_name)) continue;

                    // Parse locations
                    $loc_str = sanitize_text_field($row['locations'] ?? '');
                    $locations = array_filter(array_map('trim', explode(',', $loc_str)));
                    if (empty($locations)) $locations = ['Afghanistan', 'Pakistan', 'Where Most Needed'];

                    // Parse types
                    $type_str = sanitize_text_field($row['types'] ?? '');
                    $types = array_filter(array_map('trim', explode(',', $type_str)));
                    if (empty($types)) $types = ['Sadaqah', 'Lillah', 'Zakat', 'Charitable Donation'];

                    $updated_matrix[$new_name] = [
                        'locations' => array_values($locations),
                        'types' => array_values($types),
                        'fixed_single' => floatval($row['fixed_single'] ?? 0),
                        'fixed_monthly' => floatval($row['fixed_monthly'] ?? 0),
                        'description' => sanitize_text_field($row['description'] ?? ''),
                    ];
                }

                // Add new project if submitted
                if (!empty($_POST['new_project_name'])) {
                    $new_name = sanitize_text_field($_POST['new_project_name']);
                    $new_loc = array_filter(array_map('trim', explode(',', sanitize_text_field($_POST['new_project_locations'] ?? ''))));
                    $new_types = array_filter(array_map('trim', explode(',', sanitize_text_field($_POST['new_project_types'] ?? ''))));
                    
                    if (empty($new_loc)) $new_loc = ['Afghanistan', 'Pakistan', 'Where Most Needed'];
                    if (empty($new_types)) $new_types = ['Sadaqah', 'Lillah', 'Zakat', 'Charitable Donation'];

                    $updated_matrix[$new_name] = [
                        'locations' => array_values($new_loc),
                        'types' => array_values($new_types),
                        'fixed_single' => floatval($_POST['new_project_single'] ?? 100),
                        'fixed_monthly' => floatval($_POST['new_project_monthly'] ?? 25),
                        'description' => sanitize_text_field($_POST['new_project_desc'] ?? ''),
                    ];
                    $message = "Added new project \"$new_name\"! ";

                    // Automatically add newly created project to Quick Donate Bar enabled list if configured
                    $qd_enabled = get_option('ads_qd_enabled_projects', []);
                    if (!empty($qd_enabled) && is_array($qd_enabled) && !in_array($new_name, $qd_enabled, true)) {
                        $qd_enabled[] = $new_name;
                        update_option('ads_qd_enabled_projects', $qd_enabled);
                    }
                }

                $matrix = $updated_matrix;
                update_option('ads_projects_matrix', $matrix);

                // Comments field setting
                update_option('ads_comments_enabled', isset($_POST['ads_comments_enabled']) ? 'yes' : 'no');
                update_option('ads_comments_placeholder', sanitize_text_field($_POST['ads_comments_placeholder'] ?? 'Note (optional)'));

                $message .= 'Projects and causes updated successfully!';
            }
        }

        // TAB 2: ADMIN FEE & GENERAL
        elseif ($active_tab === 'fees') {
            $curr_choice = sanitize_text_field($_POST['ads_currency_choice'] ?? '');
            if ($curr_choice && strpos($curr_choice, '|') !== false) {
                list($code, $sym) = explode('|', $curr_choice);
                update_option('ads_currency_code', sanitize_text_field($code));
                update_option('ads_currency_symbol', sanitize_text_field($sym));
            }
            update_option('ads_admin_fee_enabled', isset($_POST['ads_admin_fee_enabled']) ? 'yes' : 'no');
            update_option('ads_admin_fee_default_checked', (isset($_POST['ads_admin_fee_default_checked']) && $_POST['ads_admin_fee_default_checked'] === 'yes') ? 'yes' : 'no');
            update_option('ads_admin_fee_percent', floatval($_POST['ads_admin_fee_percent'] ?? 1.10));
            update_option('ads_admin_fee_label', sanitize_text_field($_POST['ads_admin_fee_label'] ?? 'Add {percent}% Admin Fee'));
            update_option('ads_admin_fee_desc', sanitize_textarea_field($_POST['ads_admin_fee_desc'] ?? ''));
            $message = 'Admin Fee & Currency settings saved successfully!';
        }

        // TAB 3: YOUR DETAILS & FIELDS
        elseif ($active_tab === 'donor_details') {
            // Donor Types (Individual, Event, Organisation)
            $types = ads_get_donor_types();
            $types['individual']['enabled'] = isset($_POST['donor_type_individual_enabled']) ? 'yes' : 'no';
            $types['individual']['label'] = sanitize_text_field($_POST['donor_type_individual_label'] ?? 'Individual');
            $types['event']['enabled'] = isset($_POST['donor_type_event_enabled']) ? 'yes' : 'no';
            $types['event']['label'] = sanitize_text_field($_POST['donor_type_event_label'] ?? 'Event');
            $types['organisation']['enabled'] = isset($_POST['donor_type_organisation_enabled']) ? 'yes' : 'no';
            $types['organisation']['label'] = sanitize_text_field($_POST['donor_type_organisation_label'] ?? 'Organisation');
            update_option('ads_donor_types', $types);

            // Delete requested field
            $fields = ads_get_donor_fields();
            if (!empty($_POST['delete_field'])) {
                $del_key = sanitize_key($_POST['delete_field']);
                if (isset($fields[$del_key])) {
                    unset($fields[$del_key]);
                    $message = "Field \"$del_key\" removed.";
                }
            } else {
                // Update existing fields
                $submitted_fields = $_POST['donor_fields'] ?? [];
                foreach ($submitted_fields as $f_key => $f_data) {
                    if (isset($fields[$f_key])) {
                        $fields[$f_key]['label'] = sanitize_text_field($f_data['label'] ?? '');
                        $fields[$f_key]['placeholder'] = sanitize_text_field($f_data['placeholder'] ?? '');
                        $fields[$f_key]['required'] = isset($f_data['required']) ? 'yes' : 'no';
                        $fields[$f_key]['enabled'] = isset($f_data['enabled']) ? 'yes' : 'no';
                        $fields[$f_key]['applies_to'] = sanitize_text_field($f_data['applies_to'] ?? 'all');
                    }
                }

                // Add new custom field if submitted
                if (!empty($_POST['new_field_label'])) {
                    $new_lbl = sanitize_text_field($_POST['new_field_label']);
                    $new_key = sanitize_key($_POST['new_field_key'] ?: $new_lbl);
                    $new_type = sanitize_text_field($_POST['new_field_type'] ?? 'text');
                    $new_req = isset($_POST['new_field_required']) ? 'yes' : 'no';
                    $new_applies = sanitize_text_field($_POST['new_field_applies'] ?? 'all');

                    $fields[$new_key] = [
                        'label' => $new_lbl,
                        'type' => $new_type,
                        'required' => $new_req,
                        'enabled' => 'yes',
                        'applies_to' => $new_applies,
                        'placeholder' => sanitize_text_field($_POST['new_field_placeholder'] ?? ''),
                    ];
                    $message = "Added field \"$new_lbl\"! ";
                }

                $message .= 'Donor details fields updated successfully!';
            }
            update_option('ads_donor_fields', $fields);
        }

        // TAB 4: GIFT AID SETTINGS
        elseif ($active_tab === 'giftaid') {
            $ga_settings = [
                'enabled' => isset($_POST['ads_giftaid_enabled']) ? 'yes' : 'no',
                'default_checked' => (isset($_POST['ads_giftaid_default_checked']) && $_POST['ads_giftaid_default_checked'] === 'yes') ? 'yes' : 'no',
                'title' => sanitize_text_field($_POST['ads_giftaid_title'] ?? ''),
                'badge' => sanitize_text_field($_POST['ads_giftaid_badge'] ?? ''),
                'rate' => floatval($_POST['ads_giftaid_rate'] ?? 25),
                'desc' => wp_kses_post($_POST['ads_giftaid_desc'] ?? ''),
                'declaration' => sanitize_textarea_field($_POST['ads_giftaid_declaration'] ?? ''),
            ];
            update_option('ads_giftaid_settings', $ga_settings);
            $message = 'Gift Aid options saved successfully!';
        }

        // TAB 5: STAY IN TOUCH SETTINGS
        elseif ($active_tab === 'stay_in_touch') {
            $sit_settings = ads_get_stay_in_touch_settings();
            $sit_settings['enabled'] = isset($_POST['ads_sit_enabled']) ? 'yes' : 'no';
            $sit_settings['title'] = sanitize_text_field($_POST['ads_sit_title'] ?? '');
            $sit_settings['desc'] = sanitize_textarea_field($_POST['ads_sit_desc'] ?? '');

            // Delete requested channel
            if (!empty($_POST['delete_channel'])) {
                $del_ch = sanitize_key($_POST['delete_channel']);
                if (isset($sit_settings['channels'][$del_ch])) {
                    unset($sit_settings['channels'][$del_ch]);
                    $message = "Channel \"$del_ch\" removed.";
                }
            } else {
                // Update channels
                $submitted_channels = $_POST['sit_channels'] ?? [];
                foreach ($submitted_channels as $ch_key => $ch_data) {
                    if (isset($sit_settings['channels'][$ch_key])) {
                        $sit_settings['channels'][$ch_key]['label'] = sanitize_text_field($ch_data['label'] ?? '');
                        $sit_settings['channels'][$ch_key]['enabled'] = isset($ch_data['enabled']) ? 'yes' : 'no';
                        $sit_settings['channels'][$ch_key]['default_checked'] = isset($ch_data['default_checked']) ? 'yes' : 'no';
                    }
                }

                // Add new channel
                if (!empty($_POST['new_channel_label'])) {
                    $new_lbl = sanitize_text_field($_POST['new_channel_label']);
                    $new_key = sanitize_key($_POST['new_channel_key'] ?: $new_lbl);
                    $sit_settings['channels'][$new_key] = [
                        'label' => $new_lbl,
                        'enabled' => 'yes',
                        'default_checked' => isset($_POST['new_channel_checked']) ? 'yes' : 'no',
                    ];
                    $message = "Added channel \"$new_lbl\"! ";
                }

                $message .= 'Stay in touch settings updated successfully!';
            }
            update_option('ads_stay_in_touch_settings', $sit_settings);
        }

        // TAB 6: PAYMENT GATEWAYS
        elseif ($active_tab === 'gateways') {
            update_option('ads_stripe_testmode', isset($_POST['ads_stripe_testmode']) ? 'yes' : 'no');
            update_option('ads_stripe_pk', sanitize_text_field($_POST['ads_stripe_pk'] ?? ''));
            update_option('ads_stripe_sk', sanitize_text_field($_POST['ads_stripe_sk'] ?? ''));
            update_option('ads_paypal_email', sanitize_email($_POST['ads_paypal_email'] ?? ''));
            $message = 'Payment gateway credentials saved!';
        }

        // TAB 7: OTHER WAYS TO SUPPORT (OFFLINE & BANK DETAILS)
        elseif ($active_tab === 'other_ways' || isset($_POST['ads_other_ways_save'])) {
            update_option('ads_other_ways_enabled', isset($_POST['ads_other_ways_enabled']) ? 'yes' : 'no');
            update_option('ads_other_ways_title', sanitize_text_field($_POST['ads_other_ways_title'] ?? 'Other Ways To Support'));
            update_option('ads_other_ways_subtitle', sanitize_text_field($_POST['ads_other_ways_subtitle'] ?? ''));

            // 1. UK Local Bank details
            update_option('ads_uk_bank_title', sanitize_text_field($_POST['ads_uk_bank_title'] ?? 'UK Local Bank Transfer'));
            update_option('ads_uk_bank_subtitle', sanitize_text_field($_POST['ads_uk_bank_subtitle'] ?? 'Within the UK • Zero Fees'));
            update_option('ads_uk_bank_instruction', sanitize_text_field($_POST['ads_uk_bank_instruction'] ?? 'For donors transferring from a UK bank account. Please use only the Sort Code and Account Number below:'));
            update_option('ads_bank_name', sanitize_text_field($_POST['ads_bank_name'] ?? 'Barclays Bank PLC'));
            update_option('ads_bank_account_name', sanitize_text_field($_POST['ads_bank_account_name'] ?? 'Aryana Aid'));
            update_option('ads_bank_sort_code', sanitize_text_field($_POST['ads_bank_sort_code'] ?? '20-29-77'));
            update_option('ads_bank_account_number', sanitize_text_field($_POST['ads_bank_account_number'] ?? '90526184'));

            // 2. International Bank details
            update_option('ads_intl_bank_title', sanitize_text_field($_POST['ads_intl_bank_title'] ?? 'International Bank Transfer'));
            update_option('ads_intl_bank_subtitle', sanitize_text_field($_POST['ads_intl_bank_subtitle'] ?? 'Outside the UK • Worldwide Wire'));
            update_option('ads_intl_bank_instruction', sanitize_text_field($_POST['ads_intl_bank_instruction'] ?? 'For international donors transferring from outside the UK. Please use the SWIFT/BIC code and IBAN below:'));
            update_option('ads_intl_bank_name', sanitize_text_field($_POST['ads_intl_bank_name'] ?? 'Barclays Bank PLC'));
            update_option('ads_intl_account_name', sanitize_text_field($_POST['ads_intl_account_name'] ?? 'Aryana Aid'));
            update_option('ads_bank_swift', sanitize_text_field($_POST['ads_bank_swift'] ?? 'BUKBGB22'));
            update_option('ads_bank_iban', sanitize_text_field($_POST['ads_bank_iban'] ?? 'GB85 BUKB 2029 7790 5261 84'));

            // 3. Post details
            update_option('ads_post_title', sanitize_text_field(wp_unslash($_POST['ads_post_title'] ?? 'Donate by Post')));
            update_option('ads_post_subtitle', sanitize_text_field(wp_unslash($_POST['ads_post_subtitle'] ?? 'Cheques & Postal Orders')));
            update_option('ads_post_instruction', sanitize_textarea_field(wp_unslash($_POST['ads_post_instruction'] ?? '')));
            update_option('ads_post_address', sanitize_textarea_field(wp_unslash($_POST['ads_post_address'] ?? '')));

            if (class_exists('LiteSpeed\Purge')) {
                \LiteSpeed\Purge::purge_all();
            }

            $message = 'Other Ways To Support details saved successfully!';
        }

        // TAB 8: QUICK DONATE HERO WIDGET
        elseif ($active_tab === 'quick_donate' || isset($_POST['ads_quick_donate_save'])) {
            update_option('ads_qd_heading_1', sanitize_text_field($_POST['ads_qd_heading_1'] ?? 'Project & Frequency'));
            update_option('ads_qd_heading_2', sanitize_text_field($_POST['ads_qd_heading_2'] ?? 'Amount & Donation Type'));
            update_option('ads_qd_label_project', sanitize_text_field($_POST['ads_qd_label_project'] ?? 'Select Project / Appeal'));
            update_option('ads_qd_label_frequency', sanitize_text_field($_POST['ads_qd_label_frequency'] ?? 'Giving Frequency'));
            update_option('ads_qd_label_amount', sanitize_text_field($_POST['ads_qd_label_amount'] ?? 'Amount'));
            update_option('ads_qd_label_type', sanitize_text_field($_POST['ads_qd_label_type'] ?? 'Donation Type'));
            update_option('ads_qd_btn_text', sanitize_text_field($_POST['ads_qd_btn_text'] ?? 'Donate Now'));
            // Clean & sanitize button colors (auto-prepend # if user omits it)
            $raw_bg = trim($_POST['ads_qd_btn_bg'] ?? '#038B5C');
            if ($raw_bg !== '' && $raw_bg[0] !== '#') {
                $raw_bg = '#' . $raw_bg;
            }
            $clean_bg = sanitize_hex_color($raw_bg) ?: '#038B5C';
            update_option('ads_qd_btn_bg', $clean_bg);

            $raw_color = trim($_POST['ads_qd_btn_color'] ?? '#ffffff');
            if ($raw_color !== '' && $raw_color[0] !== '#') {
                $raw_color = '#' . $raw_color;
            }
            $clean_color = sanitize_hex_color($raw_color) ?: '#ffffff';
            update_option('ads_qd_btn_color', $clean_color);
            update_option('ads_qd_default_project', sanitize_text_field($_POST['ads_qd_default_project'] ?? 'Water - Well'));
            update_option('ads_qd_default_freq', sanitize_key($_POST['ads_qd_default_freq'] ?? 'single'));
            update_option('ads_qd_show_freq', isset($_POST['ads_qd_show_freq']) ? 'yes' : 'no');

            $selected_projs = isset($_POST['ads_qd_enabled_projects']) && is_array($_POST['ads_qd_enabled_projects'])
                ? array_map('sanitize_text_field', $_POST['ads_qd_enabled_projects'])
                : [];
            update_option('ads_qd_enabled_projects', $selected_projs);

            // Style & Appearance Settings
            $card_bg = trim($_POST['ads_qd_card_bg'] ?? '#ffffff');
            if ($card_bg !== '' && $card_bg[0] !== '#') $card_bg = '#' . $card_bg;
            update_option('ads_qd_card_bg', sanitize_hex_color($card_bg) ?: '#ffffff');

            $card_border = trim($_POST['ads_qd_card_border'] ?? '#e2e8f0');
            if ($card_border !== '' && $card_border[0] !== '#') $card_border = '#' . $card_border;
            update_option('ads_qd_card_border', sanitize_hex_color($card_border) ?: '#e2e8f0');

            update_option('ads_qd_card_radius', sanitize_text_field($_POST['ads_qd_card_radius'] ?? '14px'));
            update_option('ads_qd_card_shadow', sanitize_key($_POST['ads_qd_card_shadow'] ?? 'elevated'));
            update_option('ads_qd_max_width', sanitize_text_field($_POST['ads_qd_max_width'] ?? '100%'));

            $accent = trim($_POST['ads_qd_accent_color'] ?? '#038B5C');
            if ($accent !== '' && $accent[0] !== '#') $accent = '#' . $accent;
            update_option('ads_qd_accent_color', sanitize_hex_color($accent) ?: '#038B5C');

            $title_col = trim($_POST['ads_qd_title_color'] ?? '#0f172a');
            if ($title_col !== '' && $title_col[0] !== '#') $title_col = '#' . $title_col;
            update_option('ads_qd_title_color', sanitize_hex_color($title_col) ?: '#0f172a');

            $label_col = trim($_POST['ads_qd_label_color'] ?? '#64748b');
            if ($label_col !== '' && $label_col[0] !== '#') $label_col = '#' . $label_col;
            update_option('ads_qd_label_color', sanitize_hex_color($label_col) ?: '#64748b');

            $input_bg = trim($_POST['ads_qd_input_bg'] ?? '#ffffff');
            if ($input_bg !== '' && $input_bg[0] !== '#') $input_bg = '#' . $input_bg;
            update_option('ads_qd_input_bg', sanitize_hex_color($input_bg) ?: '#ffffff');

            $input_border = trim($_POST['ads_qd_input_border'] ?? '#cbd5e1');
            if ($input_border !== '' && $input_border[0] !== '#') $input_border = '#' . $input_border;
            update_option('ads_qd_input_border', sanitize_hex_color($input_border) ?: '#cbd5e1');

            update_option('ads_qd_input_radius', sanitize_text_field($_POST['ads_qd_input_radius'] ?? '10px'));

            $input_col = trim($_POST['ads_qd_input_color'] ?? '#0f172a');
            if ($input_col !== '' && $input_col[0] !== '#') $input_col = '#' . $input_col;
            update_option('ads_qd_input_color', sanitize_hex_color($input_col) ?: '#0f172a');

            update_option('ads_qd_btn_radius', sanitize_text_field($_POST['ads_qd_btn_radius'] ?? '8px'));

            // Quick Donate Button Hover & Text Colors
            $btn_hover_bg = trim($_POST['ads_qd_btn_hover_bg'] ?? '#026E49');
            if ($btn_hover_bg !== '' && $btn_hover_bg[0] !== '#') $btn_hover_bg = '#' . $btn_hover_bg;
            update_option('ads_qd_btn_hover_bg', sanitize_hex_color($btn_hover_bg) ?: '#026E49');

            $btn_hover_col = trim($_POST['ads_qd_btn_hover_color'] ?? '#ffffff');
            if ($btn_hover_col !== '' && $btn_hover_col[0] !== '#') $btn_hover_col = '#' . $btn_hover_col;
            update_option('ads_qd_btn_hover_color', sanitize_hex_color($btn_hover_col) ?: '#ffffff');

            // Input Focus & Icon Colors
            $input_focus = trim($_POST['ads_qd_input_focus_border'] ?? '#038B5C');
            if ($input_focus !== '' && $input_focus[0] !== '#') $input_focus = '#' . $input_focus;
            update_option('ads_qd_input_focus_border', sanitize_hex_color($input_focus) ?: '#038B5C');

            $icon_col = trim($_POST['ads_qd_icon_color'] ?? '#038B5C');
            if ($icon_col !== '' && $icon_col[0] !== '#') $icon_col = '#' . $icon_col;
            update_option('ads_qd_icon_color', sanitize_hex_color($icon_col) ?: '#038B5C');

            // Bespoke Unique Dropdown Menu Styling Options
            $drop_bg = trim($_POST['ads_qd_dropdown_bg'] ?? '#ffffff');
            if ($drop_bg !== '' && $drop_bg[0] !== '#') $drop_bg = '#' . $drop_bg;
            update_option('ads_qd_dropdown_bg', sanitize_hex_color($drop_bg) ?: '#ffffff');

            $drop_border = trim($_POST['ads_qd_dropdown_border'] ?? '#e2e8f0');
            if ($drop_border !== '' && $drop_border[0] !== '#') $drop_border = '#' . $drop_border;
            update_option('ads_qd_dropdown_border', sanitize_hex_color($drop_border) ?: '#e2e8f0');

            update_option('ads_qd_dropdown_radius', sanitize_text_field($_POST['ads_qd_dropdown_radius'] ?? '12px'));

            $drop_text = trim($_POST['ads_qd_dropdown_text_color'] ?? '#0f172a');
            if ($drop_text !== '' && $drop_text[0] !== '#') $drop_text = '#' . $drop_text;
            update_option('ads_qd_dropdown_text_color', sanitize_hex_color($drop_text) ?: '#0f172a');

            $drop_hbg = trim($_POST['ads_qd_dropdown_hover_bg'] ?? '#f0fdf4');
            if ($drop_hbg !== '' && $drop_hbg[0] !== '#') $drop_hbg = '#' . $drop_hbg;
            update_option('ads_qd_dropdown_hover_bg', sanitize_hex_color($drop_hbg) ?: '#f0fdf4');

            $drop_htext = trim($_POST['ads_qd_dropdown_hover_text'] ?? '#038B5C');
            if ($drop_htext !== '' && $drop_htext[0] !== '#') $drop_htext = '#' . $drop_htext;
            update_option('ads_qd_dropdown_hover_text', sanitize_hex_color($drop_htext) ?: '#038B5C');

            $drop_badge_bg = trim($_POST['ads_qd_dropdown_badge_bg'] ?? '#e2e8f0');
            if ($drop_badge_bg !== '' && $drop_badge_bg[0] !== '#') $drop_badge_bg = '#' . $drop_badge_bg;
            update_option('ads_qd_dropdown_badge_bg', sanitize_hex_color($drop_badge_bg) ?: '#e2e8f0');

            $drop_badge_col = trim($_POST['ads_qd_dropdown_badge_color'] ?? '#334155');
            if ($drop_badge_col !== '' && $drop_badge_col[0] !== '#') $drop_badge_col = '#' . $drop_badge_col;
            update_option('ads_qd_dropdown_badge_color', sanitize_hex_color($drop_badge_col) ?: '#334155');

            $drop_sbg = trim($_POST['ads_qd_dropdown_search_bg'] ?? '#f8fafc');
            if ($drop_sbg !== '' && $drop_sbg[0] !== '#') $drop_sbg = '#' . $drop_sbg;
            update_option('ads_qd_dropdown_search_bg', sanitize_hex_color($drop_sbg) ?: '#f8fafc');

            // Auto-include newly added dashboard projects
            update_option('ads_qd_auto_include_new', isset($_POST['ads_qd_auto_include_new']) ? 'yes' : 'no');

            // Frequency Switcher Tabs Styling
            $freq_act_bg = trim($_POST['ads_qd_freq_active_bg'] ?? '#038B5C');
            if ($freq_act_bg !== '' && $freq_act_bg[0] !== '#') $freq_act_bg = '#' . $freq_act_bg;
            update_option('ads_qd_freq_active_bg', sanitize_hex_color($freq_act_bg) ?: '#038B5C');

            $freq_act_col = trim($_POST['ads_qd_freq_active_color'] ?? '#ffffff');
            if ($freq_act_col !== '' && $freq_act_col[0] !== '#') $freq_act_col = '#' . $freq_act_col;
            update_option('ads_qd_freq_active_color', sanitize_hex_color($freq_act_col) ?: '#ffffff');

            $freq_inact_col = trim($_POST['ads_qd_freq_inactive_color'] ?? '#64748b');
            if ($freq_inact_col !== '' && $freq_inact_col[0] !== '#') $freq_inact_col = '#' . $freq_inact_col;
            update_option('ads_qd_freq_inactive_color', sanitize_hex_color($freq_inact_col) ?: '#64748b');

            $freq_cont_bg = trim($_POST['ads_qd_freq_container_bg'] ?? '#f1f5f9');
            if ($freq_cont_bg !== '' && $freq_cont_bg[0] !== '#') $freq_cont_bg = '#' . $freq_cont_bg;
            update_option('ads_qd_freq_container_bg', sanitize_hex_color($freq_cont_bg) ?: '#f1f5f9');

            update_option('ads_qd_custom_css', wp_strip_all_tags($_POST['ads_qd_custom_css'] ?? ''));

            if (class_exists('LiteSpeed\Purge')) {
                \LiteSpeed\Purge::purge_all();
            }

            $message = 'Quick Donate Hero Widget settings saved successfully!';
        }

        // TAB 9: SLIDE-OUT DONATION DRAWER CART
        elseif ($active_tab === 'donation_drawer' || isset($_POST['ads_donation_drawer_save'])) {
            update_option('ads_drawer_enabled', isset($_POST['ads_drawer_enabled']) ? 'yes' : 'no');
            update_option('ads_drawer_title', sanitize_text_field($_POST['ads_drawer_title'] ?? 'Your donations'));
            update_option('ads_drawer_checkout_text', sanitize_text_field($_POST['ads_drawer_checkout_text'] ?? 'Proceed To Checkout'));
            update_option('ads_drawer_checkout_url', esc_url_raw($_POST['ads_drawer_checkout_url'] ?? home_url('/donate/')));
            update_option('ads_drawer_empty_text', sanitize_text_field($_POST['ads_drawer_empty_text'] ?? 'Your donation basket is currently empty.'));
            update_option('ads_drawer_continue_text', sanitize_text_field($_POST['ads_drawer_continue_text'] ?? 'Continue Browsing'));
            update_option('ads_drawer_totals_title', sanitize_text_field($_POST['ads_drawer_totals_title'] ?? 'Total Amount'));
            update_option('ads_drawer_single_payment_label', sanitize_text_field($_POST['ads_drawer_single_payment_label'] ?? 'Single Payment'));
            update_option('ads_drawer_monthly_payment_label', sanitize_text_field($_POST['ads_drawer_monthly_payment_label'] ?? 'Monthly Payment'));
            update_option('ads_drawer_total_due_label', sanitize_text_field($_POST['ads_drawer_total_due_label'] ?? 'Total due today'));
            update_option('ads_drawer_remove_text', sanitize_text_field($_POST['ads_drawer_remove_text'] ?? 'Remove'));
            
            // Screen Sizing & Position
            update_option('ads_drawer_desktop_width', sanitize_text_field($_POST['ads_drawer_desktop_width'] ?? '430px'));
            update_option('ads_drawer_tablet_width', sanitize_text_field($_POST['ads_drawer_tablet_width'] ?? '50vw'));
            update_option('ads_drawer_mobile_width', sanitize_text_field($_POST['ads_drawer_mobile_width'] ?? '70vw'));
            $panel_pos = in_array($_POST['ads_drawer_panel_position'] ?? 'right', ['right', 'left']) ? $_POST['ads_drawer_panel_position'] : 'right';
            update_option('ads_drawer_panel_position', $panel_pos);

            // Display Controls
            update_option('ads_drawer_show_qty', isset($_POST['ads_drawer_show_qty']) ? 'yes' : 'no');
            update_option('ads_drawer_show_remove', isset($_POST['ads_drawer_show_remove']) ? 'yes' : 'no');
            update_option('ads_drawer_show_thumbnails', isset($_POST['ads_drawer_show_thumbnails']) ? 'yes' : 'no');
            update_option('ads_drawer_show_floating_icon', isset($_POST['ads_drawer_show_floating_icon']) ? 'yes' : 'no');
            update_option('ads_drawer_close_on_backdrop', isset($_POST['ads_drawer_close_on_backdrop']) ? 'yes' : 'no');

            // Colors & Styling
            $clean_hex = function($val, $fallback) {
                $val = trim($val ?? '');
                if ($val !== '' && $val[0] !== '#') $val = '#' . $val;
                return sanitize_hex_color($val) ?: $fallback;
            };

            update_option('ads_drawer_bg_color', $clean_hex($_POST['ads_drawer_bg_color'] ?? '', '#FAF8F5'));
            update_option('ads_drawer_header_color', $clean_hex($_POST['ads_drawer_header_color'] ?? '', '#00473e'));
            update_option('ads_drawer_subtitle_color', $clean_hex($_POST['ads_drawer_subtitle_color'] ?? '', '#008254'));
            update_option('ads_drawer_item_title_color', $clean_hex($_POST['ads_drawer_item_title_color'] ?? '', '#00473e'));
            update_option('ads_drawer_item_price_color', $clean_hex($_POST['ads_drawer_item_price_color'] ?? '', '#00473e'));
            update_option('ads_drawer_btn_bg', $clean_hex($_POST['ads_drawer_btn_bg'] ?? '', '#007a50'));
            update_option('ads_drawer_btn_hover_bg', $clean_hex($_POST['ads_drawer_btn_hover_bg'] ?? '', '#006341'));
            update_option('ads_drawer_btn_color', $clean_hex($_POST['ads_drawer_btn_color'] ?? '', '#ffffff'));
            update_option('ads_drawer_btn_border_radius', sanitize_text_field($_POST['ads_drawer_btn_border_radius'] ?? '8px'));
            update_option('ads_drawer_stepper_color', $clean_hex($_POST['ads_drawer_stepper_color'] ?? '', '#00473e'));
            update_option('ads_drawer_stepper_bg', $clean_hex($_POST['ads_drawer_stepper_bg'] ?? '', '#ffffff'));
            update_option('ads_drawer_stepper_border_color', $clean_hex($_POST['ads_drawer_stepper_border_color'] ?? '', '#D5CEBF'));
            update_option('ads_drawer_remove_color', $clean_hex($_POST['ads_drawer_remove_color'] ?? '', '#9c7a56'));
            update_option('ads_drawer_launcher_bg', $clean_hex($_POST['ads_drawer_launcher_bg'] ?? '', '#007a50'));
            update_option('ads_drawer_launcher_color', $clean_hex($_POST['ads_drawer_launcher_color'] ?? '', '#ffffff'));
            update_option('ads_drawer_launcher_badge_bg', $clean_hex($_POST['ads_drawer_launcher_badge_bg'] ?? '', '#E11D48'));

            update_option('ads_drawer_default_image', esc_url_raw($_POST['ads_drawer_default_image'] ?? ''));
            update_option('ads_drawer_custom_css', wp_strip_all_tags($_POST['ads_drawer_custom_css'] ?? ''));

            if (class_exists('LiteSpeed\Purge')) {
                \LiteSpeed\Purge::purge_all();
            }

            $message = 'Slide-Out Drawer Cart settings saved successfully!';
        }
    }

    // Load fresh data
    $drawer_settings = function_exists('ads_get_drawer_settings') ? ads_get_drawer_settings() : [];
    $matrix = ads_get_projects_matrix();
    $gen = ads_get_general_settings();
    $donor_types = ads_get_donor_types();
    $donor_fields = ads_get_donor_fields();
    $giftaid = ads_get_giftaid_settings();
    $sit = ads_get_stay_in_touch_settings();
    $other_ways = function_exists('ads_get_other_ways_settings') ? ads_get_other_ways_settings() : [];
    $stripe_test = get_option('ads_stripe_testmode', 'yes');
    $stripe_pk = get_option('ads_stripe_pk', '');
    $stripe_sk = get_option('ads_stripe_sk', '');
    $paypal_email = get_option('ads_paypal_email', 'paypal@aryanaaid.org.uk');

    // Quick Donate Options
    $currency            = get_option('ads_currency_symbol', '£');
    $qd_heading_1        = get_option('ads_qd_heading_1', 'Project & Frequency');
    $qd_heading_2        = get_option('ads_qd_heading_2', 'Amount & Donation Type');
    $qd_label_project    = get_option('ads_qd_label_project', 'Select Project / Appeal');
    $qd_label_frequency  = get_option('ads_qd_label_frequency', 'Giving Frequency');
    $qd_label_amount     = get_option('ads_qd_label_amount', 'Amount');
    $qd_label_type       = get_option('ads_qd_label_type', 'Donation Type');
    $qd_btn_text         = get_option('ads_qd_btn_text', 'Donate Now');
    $qd_btn_bg           = get_option('ads_qd_btn_bg', '#038B5C');
    $qd_btn_color        = get_option('ads_qd_btn_color', '#ffffff');
    $qd_default_project  = get_option('ads_qd_default_project', 'Water - Well');
    $qd_default_freq     = get_option('ads_qd_default_freq', 'single');
    $qd_show_freq        = get_option('ads_qd_show_freq', 'yes');
    $qd_enabled_projects = get_option('ads_qd_enabled_projects', []);

    // Quick Donate Style Options
    $qd_card_bg      = get_option('ads_qd_card_bg', '#ffffff');
    $qd_card_border  = get_option('ads_qd_card_border', '#e2e8f0');
    $qd_card_radius  = get_option('ads_qd_card_radius', '14px');
    $qd_card_shadow  = get_option('ads_qd_card_shadow', 'elevated');
    $qd_max_width    = get_option('ads_qd_max_width', '100%');
    $qd_accent_color = get_option('ads_qd_accent_color', '#038B5C');
    $qd_title_color  = get_option('ads_qd_title_color', '#0f172a');
    $qd_label_color  = get_option('ads_qd_label_color', '#64748b');
    $qd_input_bg     = get_option('ads_qd_input_bg', '#ffffff');
    $qd_input_border = get_option('ads_qd_input_border', '#cbd5e1');
    $qd_input_radius = get_option('ads_qd_input_radius', '10px');
    $qd_input_color  = get_option('ads_qd_input_color', '#0f172a');
    $qd_btn_radius   = get_option('ads_qd_btn_radius', '8px');
    $qd_btn_hover_bg        = get_option('ads_qd_btn_hover_bg', '#026E49');
    $qd_btn_hover_color     = get_option('ads_qd_btn_hover_color', '#ffffff');
    $qd_input_focus_border  = get_option('ads_qd_input_focus_border', '#038B5C');
    $qd_icon_color          = get_option('ads_qd_icon_color', '#038B5C');
    $qd_dropdown_bg         = get_option('ads_qd_dropdown_bg', '#ffffff');
    $qd_dropdown_border     = get_option('ads_qd_dropdown_border', '#e2e8f0');
    $qd_dropdown_radius     = get_option('ads_qd_dropdown_radius', '12px');
    $qd_dropdown_text_color = get_option('ads_qd_dropdown_text_color', '#0f172a');
    $qd_dropdown_hover_bg   = get_option('ads_qd_dropdown_hover_bg', '#f0fdf4');
    $qd_dropdown_hover_text = get_option('ads_qd_dropdown_hover_text', '#038B5C');
    $qd_dropdown_badge_bg   = get_option('ads_qd_dropdown_badge_bg', '#e2e8f0');
    $qd_dropdown_badge_color= get_option('ads_qd_dropdown_badge_color', '#334155');
    $qd_dropdown_search_bg  = get_option('ads_qd_dropdown_search_bg', '#f8fafc');
    $qd_auto_include_new    = get_option('ads_qd_auto_include_new', 'yes');
    $qd_custom_css   = get_option('ads_qd_custom_css', '');

    // Frequency Switcher Tabs Style Options
    $qd_freq_active_bg      = get_option('ads_qd_freq_active_bg', '#038B5C');
    $qd_freq_active_color   = get_option('ads_qd_freq_active_color', '#ffffff');
    $qd_freq_inactive_color = get_option('ads_qd_freq_inactive_color', '#64748b');
    $qd_freq_container_bg   = get_option('ads_qd_freq_container_bg', '#f1f5f9');
    ?>

    <style>
    .aryana-admin-wrap {
        max-width: 100% !important;
        width: 100% !important;
        box-sizing: border-box;
        margin-right: 0 !important;
        padding-right: 25px !important;
    }
    .aryana-admin-wrap .nav-tab-wrapper {
        width: 100% !important;
        box-sizing: border-box;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        border-bottom: 2px solid #038B5C;
        margin-bottom: 25px;
        padding-bottom: 0;
    }
    .aryana-admin-wrap .nav-tab {
        font-weight: 600 !important;
        font-size: 14px !important;
        padding: 9px 18px !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
        border-radius: 6px 6px 0 0 !important;
        background: #e2e8f0;
        color: #475569;
        border: 1px solid #cbd5e1;
        border-bottom: none;
        text-decoration: none !important;
        margin: 0 !important;
    }
    .aryana-admin-wrap .nav-tab.nav-tab-active {
        background: #038B5C !important;
        color: #ffffff !important;
        border-color: #038B5C !important;
        box-shadow: 0 2px 4px rgba(3,139,92,0.2) !important;
    }
    .aryana-admin-wrap .nav-tab:not(.nav-tab-active):hover {
        background: #cbd5e1 !important;
        color: #0f172a !important;
    }
    .aryana-admin-wrap .card,
    .aryana-admin-wrap .aryana-card {
        max-width: 100% !important;
        width: 100% !important;
        box-sizing: border-box;
        background: #ffffff !important;
        border: 1px solid #ccd0d4 !important;
        box-shadow: 0 1px 1px rgba(0,0,0,0.04) !important;
        border-radius: 8px !important;
        padding: 24px !important;
        margin-top: 0 !important;
        margin-bottom: 25px !important;
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    .aryana-admin-wrap table.wp-list-table {
        table-layout: auto !important;
        width: 100% !important;
    }
    .aryana-admin-wrap table.wp-list-table th,
    .aryana-admin-wrap table.wp-list-table td {
        vertical-align: middle;
        padding: 10px 12px;
    }
    .aryana-admin-wrap table.wp-list-table input[type="text"],
    .aryana-admin-wrap table.wp-list-table input[type="number"],
    .aryana-admin-wrap table.wp-list-table select {
        width: 100%;
        box-sizing: border-box;
        font-size: 14px;
        padding: 6px 10px;
        height: 38px;
        border: 1px solid #8c8f94;
        border-radius: 4px;
    }
    .aryana-admin-wrap .form-table {
        width: 100% !important;
        max-width: 100% !important;
    }
    .aryana-admin-wrap .form-table th {
        width: 280px;
        font-weight: 600;
    }
    .aryana-admin-wrap .form-table input.large-text,
    .aryana-admin-wrap .form-table textarea.large-text {
        width: 100% !important;
        max-width: 900px;
    }
    .aryana-table-scroll {
        overflow-x: auto;
        width: 100%;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 20px;
    }
    .aryana-tab-panel {
        display: none;
    }
    .aryana-tab-panel.aryana-panel-active {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    </style>

    <div class="wrap aryana-admin-wrap">
        <!-- Top Section Switcher -->
        <div style="display: flex; gap: 8px; margin: 15px 0 20px 0;">
            <a href="<?php echo admin_url('admin.php?page=aryana-donations'); ?>" class="button button-primary" style="background:#038B5C; border-color:#026E49; font-weight:700;">⚙️ Dashboard & Settings (6 Tabs)</a>
            <a href="<?php echo admin_url('edit.php?post_type=ads_campaign'); ?>" class="button button-secondary" style="font-weight:600;">🎯 Campaigns</a>
            <a href="<?php echo admin_url('admin.php?page=aryana-donations-list'); ?>" class="button button-secondary" style="font-weight:600;">💳 All Donations & Donors</a>
        </div>

        <h1 style="display:flex; align-items:center; gap:10px; margin-bottom: 5px;">
            <span class="dashicons dashicons-heart" style="font-size:32px; color:#038B5C;"></span>
            Aryana Aid Donation System Dashboard
        </h1>
        <p style="color:#555; margin-bottom:20px;">Manage all projects, locations, donation types, dynamic admin fees, donor form fields, Gift Aid, and communication channels dynamically.</p>


        <?php if (!empty($message)): ?>
            <div class="notice notice-success is-dismissible"><p><strong><?php echo esc_html($message); ?></strong></p></div>
        <?php endif; ?>

        <!-- Nav Tabs -->
        <nav class="nav-tab-wrapper">
            <a href="<?php echo esc_url(admin_url('admin.php?page=aryana-donations&tab=projects')); ?>" data-tab="projects" class="nav-tab aryana-nav-tab <?php echo $active_tab === 'projects' ? 'nav-tab-active' : ''; ?>">Projects & Causes</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=aryana-donations&tab=fees')); ?>" data-tab="fees" class="nav-tab aryana-nav-tab <?php echo $active_tab === 'fees' ? 'nav-tab-active' : ''; ?>">Admin Fee & Currency</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=aryana-donations&tab=donor_details')); ?>" data-tab="donor_details" class="nav-tab aryana-nav-tab <?php echo $active_tab === 'donor_details' ? 'nav-tab-active' : ''; ?>">Your Details Form Fields</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=aryana-donations&tab=giftaid')); ?>" data-tab="giftaid" class="nav-tab aryana-nav-tab <?php echo $active_tab === 'giftaid' ? 'nav-tab-active' : ''; ?>">Gift Aid Options</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=aryana-donations&tab=stay_in_touch')); ?>" data-tab="stay_in_touch" class="nav-tab aryana-nav-tab <?php echo $active_tab === 'stay_in_touch' ? 'nav-tab-active' : ''; ?>">Stay In Touch Options</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=aryana-donations&tab=gateways')); ?>" data-tab="gateways" class="nav-tab aryana-nav-tab <?php echo $active_tab === 'gateways' ? 'nav-tab-active' : ''; ?>">Payment Gateways</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=aryana-donations&tab=other_ways')); ?>" data-tab="other_ways" class="nav-tab aryana-nav-tab <?php echo $active_tab === 'other_ways' ? 'nav-tab-active' : ''; ?>">Other Ways To Support</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=aryana-donations&tab=quick_donate')); ?>" data-tab="quick_donate" class="nav-tab aryana-nav-tab <?php echo $active_tab === 'quick_donate' ? 'nav-tab-active' : ''; ?>">Quick Donate Widget</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=aryana-donations&tab=donation_drawer')); ?>" data-tab="donation_drawer" class="nav-tab aryana-nav-tab <?php echo $active_tab === 'donation_drawer' ? 'nav-tab-active' : ''; ?>">🛒 Slide-Out Drawer Cart</a>
        </nav>

        <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=aryana-donations')); ?>" id="aryana-settings-form">
            <?php wp_nonce_field('ads_save_settings_nonce'); ?>
            <input type="hidden" name="active_tab" id="aryana_active_tab" value="<?php echo esc_attr($active_tab); ?>">

            <!-- ============================================================
                 TAB 1: PROJECTS & MATRIX
                 ============================================================ -->
            <div class="aryana-tab-panel <?php echo $active_tab === 'projects' ? 'aryana-panel-active' : ''; ?>" id="tab-panel-projects" data-tab="projects" style="<?php echo $active_tab === 'projects' ? 'display:block;' : 'display:none;'; ?>">
                <div class="card aryana-card">
                    <h2 style="margin-top:0;">Active Projects, Locations & Preset Amounts</h2>
                    <p style="color:#666;">Every single option here is dynamically populated on the front-end dropdowns and cascading logic. Separate multiple locations and types with commas.</p>

                    <div style="margin-bottom: 20px; background:#F8FAFC; padding:15px; border-radius:6px; border:1px solid #E2E8F0;">
                        <strong>Comments Field Setting:</strong>
                        <label style="margin-left:15px;">
                            <input type="checkbox" name="ads_comments_enabled" value="yes" <?php checked($gen['comments_enabled'], 'yes'); ?>>
                            Show Comments / Notes Field on Rows
                        </label>
                        <span style="margin-left:20px; color:#555;">Placeholder:</span>
                        <input type="text" name="ads_comments_placeholder" value="<?php echo esc_attr($gen['comments_placeholder']); ?>" style="width:200px; margin-left:6px; padding:4px 8px;">
                    </div>

                    <div class="aryana-table-scroll">
                        <table class="wp-list-table widefat striped" style="border-radius:6px; min-width: 950px;">
                            <thead>
                                <tr>
                                    <th style="width: 22%; min-width: 180px;">Project Name</th>
                                    <th style="width: 28%; min-width: 220px;">Locations (Comma-Separated)</th>
                                    <th style="width: 28%; min-width: 220px;">Donation Types (Comma-Separated)</th>
                                    <th style="width: 9%; min-width: 90px;">Single (£)</th>
                                    <th style="width: 9%; min-width: 90px;">Monthly (£)</th>
                                    <th style="width: 80px; text-align: center;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($matrix as $name => $p): ?>
                                    <tr>
                                        <td>
                                            <input type="text" name="projects[<?php echo esc_attr($name); ?>][name]" value="<?php echo esc_attr($name); ?>" style="font-weight:600;">
                                        </td>
                                        <td>
                                            <input type="text" name="projects[<?php echo esc_attr($name); ?>][locations]" value="<?php echo esc_attr(implode(', ', $p['locations'])); ?>">
                                        </td>
                                        <td>
                                            <input type="text" name="projects[<?php echo esc_attr($name); ?>][types]" value="<?php echo esc_attr(implode(', ', $p['types'])); ?>">
                                        </td>
                                        <td>
                                            <input type="number" step="any" min="0" name="projects[<?php echo esc_attr($name); ?>][fixed_single]" value="<?php echo esc_attr($p['fixed_single']); ?>">
                                        </td>
                                        <td>
                                            <input type="number" step="any" min="0" name="projects[<?php echo esc_attr($name); ?>][fixed_monthly]" value="<?php echo esc_attr($p['fixed_monthly']); ?>">
                                        </td>
                                        <td style="text-align:center;">
                                            <button type="submit" name="delete_project" value="<?php echo esc_attr($name); ?>" class="button button-link-delete" onclick="return confirm('Are you sure you want to delete this project?');">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <h3 style="margin-top:25px;">+ Add New Project / Cause</h3>
                    <div style="background:#F0FDF4; border:1px solid #86EFAC; padding:20px; border-radius:8px; display:grid; grid-template-columns: 2fr 2fr 2fr 1fr 1fr; gap:15px; align-items:end;">
                        <div>
                            <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Project Name</label>
                            <input type="text" name="new_project_name" placeholder="e.g. Winter Clothes Kit" style="width:100%; height:38px; border:1px solid #8c8f94; border-radius:4px; padding:6px 10px;">
                        </div>
                        <div>
                            <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Locations</label>
                            <input type="text" name="new_project_locations" placeholder="Afghanistan, Pakistan" style="width:100%; height:38px; border:1px solid #8c8f94; border-radius:4px; padding:6px 10px;">
                        </div>
                        <div>
                            <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Donation Types</label>
                            <input type="text" name="new_project_types" placeholder="Sadaqah, Zakat, Lillah" style="width:100%; height:38px; border:1px solid #8c8f94; border-radius:4px; padding:6px 10px;">
                        </div>
                        <div>
                            <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Single (£)</label>
                            <input type="number" step="any" name="new_project_single" placeholder="50" style="width:100%; height:38px; border:1px solid #8c8f94; border-radius:4px; padding:6px 10px;">
                        </div>
                        <div>
                            <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Monthly (£)</label>
                            <input type="number" step="any" name="new_project_monthly" placeholder="10" style="width:100%; height:38px; border:1px solid #8c8f94; border-radius:4px; padding:6px 10px;">
                        </div>
                    </div>

                    <p style="margin-top:20px;">
                        <button type="submit" class="button button-primary button-large" style="background:#038B5C; border-color:#026E49; font-weight:600; padding:4px 20px;">Save All Projects & Causes</button>
                    </p>
                </div>
            </div>

            <!-- ============================================================
                 TAB 2: ADMIN FEE & CURRENCY
                 ============================================================ -->
            <div class="aryana-tab-panel <?php echo $active_tab === 'fees' ? 'aryana-panel-active' : ''; ?>" id="tab-panel-fees" data-tab="fees" style="<?php echo $active_tab === 'fees' ? 'display:block;' : 'display:none;'; ?>">
                <div class="card aryana-card">
                    <h2 style="margin-top:0;">Admin Fee & Currency Settings</h2>
                    <p style="color:#666;">Set the optional admin processing fee percentage. When changed (e.g. to 1.5%), the front-end will dynamically update both the label and the live calculation!</p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable Admin Fee</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="ads_admin_fee_enabled" value="yes" <?php checked($gen['admin_fee_enabled'], 'yes'); ?>>
                                    Enable optional admin processing fee checkbox on donation form
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Admin Fee Default State</th>
                            <td>
                                <fieldset>
                                    <label style="margin-right:20px; font-weight:600; cursor:pointer;">
                                        <input type="radio" name="ads_admin_fee_default_checked" value="no" <?php checked(($gen['admin_fee_default_checked'] ?? 'no'), 'no'); ?>>
                                        Unchecked by default (Donor actively chooses to add fee)
                                    </label><br>
                                    <label style="margin-top:6px; display:inline-block; font-weight:600; cursor:pointer;">
                                        <input type="radio" name="ads_admin_fee_default_checked" value="yes" <?php checked(($gen['admin_fee_default_checked'] ?? 'no'), 'yes'); ?>>
                                        Checked by default (Pre-selected)
                                    </label>
                                    <p class="description">Choose whether the admin fee checkbox is pre-checked or unchecked when donors load the donation page.</p>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Admin Fee Percentage (%)</th>
                            <td>
                                <input type="number" step="0.01" min="0" max="100" name="ads_admin_fee_percent" value="<?php echo esc_attr($gen['admin_fee_percent']); ?>" style="width:120px; font-size:16px; font-weight:700;"> %
                                <p class="description">If you enter <code>1.5</code>, a £100 donation will calculate +£1.50 fee for a total of £101.50.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Admin Fee Label</th>
                            <td>
                                <input type="text" name="ads_admin_fee_label" value="<?php echo esc_attr($gen['admin_fee_label']); ?>" class="large-text">
                                <p class="description">Use <code>{percent}</code> as a placeholder. It will automatically be replaced with your percentage (e.g. "Add 1.5% Admin Fee").</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Admin Fee Description</th>
                            <td>
                                <textarea name="ads_admin_fee_desc" rows="3" class="large-text"><?php echo esc_textarea($gen['admin_fee_desc']); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Payment Currency</th>
                            <td>
                                <?php
                                $curr_code = $gen['currency_code'] ?? 'GBP';
                                ?>
                                <select name="ads_currency_choice" id="ads_currency_choice" class="regular-text" style="font-size:15px; font-weight:700; height:42px; min-width:320px; max-width:420px; padding:6px 14px; border-radius:6px; border:1px solid #8c8f94; background:#fff;">
                                    <option value="GBP|£" <?php selected($curr_code, 'GBP'); ?>>🇬🇧 British Pound (£ GBP)</option>
                                    <option value="USD|$" <?php selected($curr_code, 'USD'); ?>>🇺🇸 US Dollar ($ USD)</option>
                                    <option value="EUR|€" <?php selected($curr_code, 'EUR'); ?>>🇪🇺 Euro (€ EUR)</option>
                                    <option value="CAD|$" <?php selected($curr_code, 'CAD'); ?>>🇨🇦 Canadian Dollar ($ CAD)</option>
                                    <option value="AUD|$" <?php selected($curr_code, 'AUD'); ?>>🇦🇺 Australian Dollar ($ AUD)</option>
                                </select>
                                <p class="description" style="margin-top:8px;">Choose which currency donators can pay in (Pound £, Dollar $, or Euro €). Donators will automatically donate in the currency selected here.</p>
                            </td>
                        </tr>
                    </table>

                    <p style="margin-top:20px;">
                        <button type="submit" class="button button-primary button-large" style="background:#038B5C; border-color:#026E49; font-weight:600; padding:4px 20px;">Save Fee & Currency Settings</button>
                    </p>
                </div>
            </div>

            <!-- ============================================================
                 TAB 3: YOUR DETAILS & FIELDS
                 ============================================================ -->
            <div class="aryana-tab-panel <?php echo $active_tab === 'donor_details' ? 'aryana-panel-active' : ''; ?>" id="tab-panel-donor_details" data-tab="donor_details" style="<?php echo $active_tab === 'donor_details' ? 'display:block;' : 'display:none;'; ?>">
                <div class="card aryana-card">
                    <h2 style="margin-top:0;">Donor Details: Donor Types & Form Fields</h2>
                    <p style="color:#666;">Customize the donor types (Individual, Event, Organisation), toggle fields on/off, change labels, or add custom fields.</p>

                    <h3>1. Donor Types Enabled</h3>
                    <div style="display:flex; flex-wrap:wrap; gap:20px; background:#F8FAFC; padding:15px; border-radius:6px; border:1px solid #E2E8F0; margin-bottom:25px;">
                        <label>
                            <input type="checkbox" name="donor_type_individual_enabled" value="yes" <?php checked($donor_types['individual']['enabled'], 'yes'); ?>>
                            <strong>Individual</strong> Label:
                            <input type="text" name="donor_type_individual_label" value="<?php echo esc_attr($donor_types['individual']['label']); ?>" style="width:120px;">
                        </label>
                        <label>
                            <input type="checkbox" name="donor_type_event_enabled" value="yes" <?php checked($donor_types['event']['enabled'], 'yes'); ?>>
                            <strong>Event</strong> Label:
                            <input type="text" name="donor_type_event_label" value="<?php echo esc_attr($donor_types['event']['label']); ?>" style="width:120px;">
                        </label>
                        <label>
                            <input type="checkbox" name="donor_type_organisation_enabled" value="yes" <?php checked($donor_types['organisation']['enabled'], 'yes'); ?>>
                            <strong>Organisation</strong> Label:
                            <input type="text" name="donor_type_organisation_label" value="<?php echo esc_attr($donor_types['organisation']['label']); ?>" style="width:120px;">
                        </label>
                    </div>

                    <h3>2. Form Fields Manager</h3>
                    <div class="aryana-table-scroll">
                        <table class="wp-list-table widefat striped" style="min-width: 850px;">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">Field Key</th>
                                    <th style="width: 25%;">Field Label</th>
                                    <th style="width: 25%;">Placeholder</th>
                                    <th style="width: 14%;">Applies To</th>
                                    <th style="width: 7%; text-align:center;">Required?</th>
                                    <th style="width: 7%; text-align:center;">Enabled?</th>
                                    <th style="width: 7%; text-align:center;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($donor_fields as $key => $f): ?>
                                    <tr>
                                        <td><code><?php echo esc_html($key); ?></code></td>
                                        <td>
                                            <input type="text" name="donor_fields[<?php echo esc_attr($key); ?>][label]" value="<?php echo esc_attr($f['label']); ?>">
                                        </td>
                                        <td>
                                            <input type="text" name="donor_fields[<?php echo esc_attr($key); ?>][placeholder]" value="<?php echo esc_attr($f['placeholder'] ?? ''); ?>">
                                        </td>
                                        <td>
                                            <select name="donor_fields[<?php echo esc_attr($key); ?>][applies_to]">
                                                <option value="all" <?php selected($f['applies_to'], 'all'); ?>>All Types</option>
                                                <option value="individual" <?php selected($f['applies_to'], 'individual'); ?>>Individual Only</option>
                                                <option value="event" <?php selected($f['applies_to'], 'event'); ?>>Event Only</option>
                                                <option value="organisation" <?php selected($f['applies_to'], 'organisation'); ?>>Organisation Only</option>
                                            </select>
                                        </td>
                                        <td style="text-align:center;">
                                            <input type="checkbox" name="donor_fields[<?php echo esc_attr($key); ?>][required]" value="yes" <?php checked($f['required'], 'yes'); ?>>
                                        </td>
                                        <td style="text-align:center;">
                                            <input type="checkbox" name="donor_fields[<?php echo esc_attr($key); ?>][enabled]" value="yes" <?php checked($f['enabled'], 'yes'); ?>>
                                        </td>
                                        <td style="text-align:center;">
                                            <?php if (!in_array($key, ['first_name', 'last_name', 'email'])): ?>
                                                <button type="submit" name="delete_field" value="<?php echo esc_attr($key); ?>" class="button button-link-delete" onclick="return confirm('Remove this field?');">Remove</button>
                                            <?php else: ?>
                                                <small style="color:#999;">Core</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <h3 style="margin-top:25px;">+ Add Custom Extra Field</h3>
                    <div style="background:#F0FDF4; border:1px solid #86EFAC; padding:20px; border-radius:8px; display:grid; grid-template-columns: 2fr 2fr 1.5fr 1fr 1fr; gap:15px; align-items:end;">
                        <div>
                            <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Field Label</label>
                            <input type="text" name="new_field_label" placeholder="e.g. Preferred Contact Time" style="width:100%; height:38px; border:1px solid #8c8f94; border-radius:4px; padding:6px 10px;">
                        </div>
                        <div>
                            <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Placeholder</label>
                            <input type="text" name="new_field_placeholder" placeholder="e.g. Mornings / Evenings" style="width:100%; height:38px; border:1px solid #8c8f94; border-radius:4px; padding:6px 10px;">
                        </div>
                        <div>
                            <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Applies To</label>
                            <select name="new_field_applies" style="width:100%; height:38px; border:1px solid #8c8f94; border-radius:4px;">
                                <option value="all">All Types</option>
                                <option value="individual">Individual</option>
                                <option value="event">Event</option>
                                <option value="organisation">Organisation</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Field Type</label>
                            <select name="new_field_type" style="width:100%; height:38px; border:1px solid #8c8f94; border-radius:4px;">
                                <option value="text">Text</option>
                                <option value="tel">Phone</option>
                                <option value="email">Email</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:inline-block; font-size:12px; font-weight:600;">
                                <input type="checkbox" name="new_field_required" value="yes"> Required?
                            </label>
                        </div>
                    </div>

                    <p style="margin-top:20px;">
                        <button type="submit" class="button button-primary button-large" style="background:#038B5C; border-color:#026E49; font-weight:600; padding:4px 20px;">Save Donor Form Fields</button>
                    </p>
                </div>
            </div>

            <!-- ============================================================
                 TAB 4: GIFT AID SETTINGS
                 ============================================================ -->
            <div class="aryana-tab-panel <?php echo $active_tab === 'giftaid' ? 'aryana-panel-active' : ''; ?>" id="tab-panel-giftaid" data-tab="giftaid" style="<?php echo $active_tab === 'giftaid' ? 'display:block;' : 'display:none;'; ?>">
                <div class="card aryana-card">
                    <h2 style="margin-top:0;">Supersize Your Support With Gift Aid Options</h2>
                    <p style="color:#666;">Manage the Gift Aid card appearance, boost rate, explanation copy, and statutory declaration text.</p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable Gift Aid Card</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="ads_giftaid_enabled" value="yes" <?php checked($giftaid['enabled'], 'yes'); ?>>
                                    Show the Gift Aid section on the donation checkout form
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Default Checkbox State</th>
                            <td>
                                <fieldset>
                                    <label style="margin-right:20px; font-weight:600; cursor:pointer;">
                                        <input type="radio" name="ads_giftaid_default_checked" value="no" <?php checked(($giftaid['default_checked'] ?? 'no'), 'no'); ?>>
                                        Unchecked by default (Recommended for HMRC opt-in compliance)
                                    </label><br>
                                    <label style="margin-top:6px; display:inline-block; font-weight:600; cursor:pointer;">
                                        <input type="radio" name="ads_giftaid_default_checked" value="yes" <?php checked(($giftaid['default_checked'] ?? 'no'), 'yes'); ?>>
                                        Checked by default (Pre-selected)
                                    </label>
                                    <p class="description">Choose whether the Gift Aid declaration checkbox starts pre-checked or unchecked when a donor loads the checkout form.</p>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Section Heading</th>
                            <td>
                                <input type="text" name="ads_giftaid_title" value="<?php echo esc_attr($giftaid['title']); ?>" class="large-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Badge Text</th>
                            <td>
                                <input type="text" name="ads_giftaid_badge" value="<?php echo esc_attr($giftaid['badge']); ?>" style="width:200px;">
                                <p class="description">Displays in the green boost pill (e.g. <code>+25% Extra Free</code>).</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Boost Rate (%)</th>
                            <td>
                                <input type="number" step="0.01" min="0" max="100" name="ads_giftaid_rate" value="<?php echo esc_attr($giftaid['rate']); ?>" style="width:100px;"> %
                                <p class="description">HMRC standard is 25% (25p per £1 donated).</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Explanation Text</th>
                            <td>
                                <textarea name="ads_giftaid_desc" rows="4" class="large-text"><?php echo esc_textarea($giftaid['desc']); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Declaration Checkbox Statement</th>
                            <td>
                                <textarea name="ads_giftaid_declaration" rows="5" class="large-text"><?php echo esc_textarea($giftaid['declaration']); ?></textarea>
                                <p class="description">The statutory UK Gift Aid legal declaration that donors tick.</p>
                            </td>
                        </tr>
                    </table>

                    <p style="margin-top:20px;">
                        <button type="submit" class="button button-primary button-large" style="background:#038B5C; border-color:#026E49; font-weight:600; padding:4px 20px;">Save Gift Aid Settings</button>
                    </p>
                </div>
            </div>

            <!-- ============================================================
                 TAB 5: STAY IN TOUCH SETTINGS
                 ============================================================ -->
            <div class="aryana-tab-panel <?php echo $active_tab === 'stay_in_touch' ? 'aryana-panel-active' : ''; ?>" id="tab-panel-stay_in_touch" data-tab="stay_in_touch" style="<?php echo $active_tab === 'stay_in_touch' ? 'display:block;' : 'display:none;'; ?>">
                <div class="card aryana-card">
                    <h2 style="margin-top:0;">Stay In Touch (Communication Channels)</h2>
                    <p style="color:#666;">Enable or disable communication consent options, customize the labels, or add new channels (like WhatsApp, Newsletter, etc.).</p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable Section</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="ads_sit_enabled" value="yes" <?php checked($sit['enabled'], 'yes'); ?>>
                                    Show the Stay In Touch consent options on the donation form
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Section Title</th>
                            <td>
                                <input type="text" name="ads_sit_title" value="<?php echo esc_attr($sit['title']); ?>" class="large-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Description Text</th>
                            <td>
                                <textarea name="ads_sit_desc" rows="2" class="large-text"><?php echo esc_textarea($sit['desc']); ?></textarea>
                            </td>
                        </tr>
                    </table>

                    <h3>Communication Channels</h3>
                    <div class="aryana-table-scroll">
                        <table class="wp-list-table widefat striped" style="min-width: 700px; margin-bottom:20px;">
                            <thead>
                                <tr>
                                    <th style="width:20%;">Channel Key</th>
                                    <th style="width:40%;">Channel Label</th>
                                    <th style="width:15%; text-align:center;">Enabled?</th>
                                    <th style="width:15%; text-align:center;">Checked by Default?</th>
                                    <th style="width:10%; text-align:center;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sit['channels'] as $key => $ch): ?>
                                    <tr>
                                        <td><code><?php echo esc_html($key); ?></code></td>
                                        <td>
                                            <input type="text" name="sit_channels[<?php echo esc_attr($key); ?>][label]" value="<?php echo esc_attr($ch['label']); ?>">
                                        </td>
                                        <td style="text-align:center;">
                                            <input type="checkbox" name="sit_channels[<?php echo esc_attr($key); ?>][enabled]" value="yes" <?php checked($ch['enabled'] ?? 'yes', 'yes'); ?>>
                                        </td>
                                        <td style="text-align:center;">
                                            <input type="checkbox" name="sit_channels[<?php echo esc_attr($key); ?>][default_checked]" value="yes" <?php checked($ch['default_checked'] ?? 'no', 'yes'); ?>>
                                        </td>
                                        <td style="text-align:center;">
                                            <button type="submit" name="delete_channel" value="<?php echo esc_attr($key); ?>" class="button button-link-delete" onclick="return confirm('Remove channel?');">Remove</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <h3 style="margin-top:25px;">+ Add New Channel</h3>
                    <div style="background:#F0FDF4; border:1px solid #86EFAC; padding:20px; border-radius:8px; display:flex; gap:15px; align-items:end;">
                        <div style="flex:1;">
                            <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Channel Label</label>
                            <input type="text" name="new_channel_label" placeholder="e.g. WhatsApp Messages" style="width:100%; height:38px; border:1px solid #8c8f94; border-radius:4px; padding:6px 10px;">
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Channel Key (optional)</label>
                            <input type="text" name="new_channel_key" placeholder="e.g. whatsapp" style="width:100%; height:38px; border:1px solid #8c8f94; border-radius:4px; padding:6px 10px;">
                        </div>
                        <div>
                            <label style="display:inline-block; font-size:12px; font-weight:600;">
                                <input type="checkbox" name="new_channel_checked" value="yes"> Default Checked?
                            </label>
                        </div>
                    </div>

                    <p style="margin-top:20px;">
                        <button type="submit" class="button button-primary button-large" style="background:#038B5C; border-color:#026E49; font-weight:600; padding:4px 20px;">Save Stay In Touch Options</button>
                    </p>
                </div>
            </div>

            <!-- ============================================================
                 TAB 6: PAYMENT GATEWAYS
                 ============================================================ -->
            <div class="aryana-tab-panel <?php echo $active_tab === 'gateways' ? 'aryana-panel-active' : ''; ?>" id="tab-panel-gateways" data-tab="gateways" style="<?php echo $active_tab === 'gateways' ? 'display:block;' : 'display:none;'; ?>">
                <div class="card aryana-card">
                    <h2 style="margin-top:0;">Payment Gateways Configuration (WooCommerce Powered)</h2>
                    
                    <!-- WooCommerce Active Banner -->
                    <div style="background:#F0FDF4; border:1.5px solid #86EFAC; border-radius:10px; padding:20px 24px; margin-bottom:24px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
                            <div>
                                <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                                    <span style="display:inline-block; width:12px; height:12px; border-radius:50%; background:#10B981;"></span>
                                    <strong style="font-size:16px; color:#166534;">WooCommerce Payment Gateway System Active</strong>
                                </div>
                                <p style="margin:0; font-size:13.5px; color:#15803D; max-width:640px; line-height:1.5;">
                                    Donations are now seamlessly processed through WooCommerce! You can add, enable, and manage <strong>Stripe, PayPal, Apple Pay, Google Pay, Direct Debit</strong>, and more directly from your WooCommerce Payments dashboard.
                                </p>
                            </div>
                            <div>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=checkout')); ?>" class="button button-primary button-large" style="background:#038B5C; border-color:#026E49; font-weight:700; padding:6px 20px;">
                                    Manage WooCommerce Gateways &rarr;
                                </a>
                            </div>
                        </div>
                    </div>

                    <h3 style="font-size:16px; font-weight:700; color:#0F172A; margin-top:24px; margin-bottom:6px;">
                        Installed WooCommerce Payment Gateways
                    </h3>
                    <p style="color:#64748B; font-size:13.5px; margin-top:0; margin-bottom:18px;">
                        All payment processing, sandbox/live credentials, and gateway features are managed directly through WooCommerce:
                    </p>

                    <div style="background:#FFFFFF; border:1px solid #E2E8F0; border-radius:8px; overflow:hidden; margin-bottom:24px;">
                        <table class="widefat striped" style="border:none;">
                            <thead>
                                <tr style="background:#F8FAFC;">
                                    <th style="padding:12px 16px; font-weight:700; color:#334155;">Gateway</th>
                                    <th style="padding:12px 16px; font-weight:700; color:#334155;">Gateway ID</th>
                                    <th style="padding:12px 16px; font-weight:700; color:#334155;">Status</th>
                                    <th style="padding:12px 16px; font-weight:700; color:#334155; text-align:right;">Settings</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $display_gateways = [
                                    'stripe_cc'    => ['name' => 'Credit / Debit Card (Stripe)', 'icon' => '💳'],
                                    'ppcp-gateway' => ['name' => 'PayPal Payments', 'icon' => '🅿️'],
                                    'bacs'         => ['name' => 'Direct Bank Transfer (BACS)', 'icon' => '🏦'],
                                ];

                                $all_wc_gateways = class_exists('WC_Payment_Gateways') ? WC()->payment_gateways()->payment_gateways() : [];

                                foreach ($display_gateways as $gw_id => $gw_meta):
                                    $gw_obj = $all_wc_gateways[$gw_id] ?? null;
                                    $is_enabled = $gw_obj && $gw_obj->enabled === 'yes';
                                    $gw_title = $gw_obj ? $gw_obj->get_title() : $gw_meta['name'];
                                    $config_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=' . strtolower($gw_id));
                                ?>
                                <tr>
                                    <td style="padding:14px 16px; font-weight:600; color:#0F172A;">
                                        <span style="margin-right:8px;"><?php echo $gw_meta['icon']; ?></span>
                                        <?php echo esc_html($gw_title); ?>
                                    </td>
                                    <td style="padding:14px 16px;">
                                        <code style="background:#F1F5F9; padding:2px 6px; border-radius:4px; font-size:12px;"><?php echo esc_html($gw_id); ?></code>
                                    </td>
                                    <td style="padding:14px 16px;">
                                        <?php if ($is_enabled): ?>
                                            <span style="display:inline-flex; align-items:center; gap:5px; background:#DCFCE7; color:#166534; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:700;">
                                                <span style="width:7px; height:7px; border-radius:50%; background:#10B981;"></span> Enabled
                                            </span>
                                        <?php else: ?>
                                            <span style="display:inline-flex; align-items:center; gap:5px; background:#F1F5F9; color:#64748B; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600;">
                                                <span style="width:7px; height:7px; border-radius:50%; background:#94A3B8;"></span> Disabled
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:14px 16px; text-align:right;">
                                        <a href="<?php echo esc_url($config_url); ?>" class="button button-small" style="font-weight:600;">
                                            Configure in WooCommerce &rarr;
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Integration Overview Cards -->
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:16px; margin-top:20px;">
                        <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; padding:18px;">
                            <div style="font-size:20px; margin-bottom:8px;">⚡</div>
                            <strong style="color:#0F172A; font-size:14px; display:block; margin-bottom:6px;">Direct Payment UX</strong>
                            <p style="margin:0; font-size:12.5px; color:#64748B; line-height:1.5;">
                                Donors select their payment method (Bank Card or PayPal) right on the donation page without seeing duplicate WooCommerce checkout billing screens.
                            </p>
                        </div>
                        <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; padding:18px;">
                            <div style="font-size:20px; margin-bottom:8px;">📊</div>
                            <strong style="color:#0F172A; font-size:14px; display:block; margin-bottom:6px;">Unified Orders in WooCommerce</strong>
                            <p style="margin:0; font-size:12.5px; color:#64748B; line-height:1.5;">
                                Completed donations automatically sync to <strong>WooCommerce &rarr; Orders</strong> with customer details, Gift Aid status, and cause line items.
                            </p>
                        </div>
                        <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; padding:18px;">
                            <div style="font-size:20px; margin-bottom:8px;">🛡️</div>
                            <strong style="color:#0F172A; font-size:14px; display:block; margin-bottom:6px;">WooCommerce Gateways</strong>
                            <p style="margin:0; font-size:12.5px; color:#64748B; line-height:1.5;">
                                All API keys, webhooks, live/sandbox modes, and security settings are safely configured inside standard WooCommerce Payments.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================
                 TAB 7: OTHER WAYS TO SUPPORT (OFFLINE DONATIONS)
                 ============================================================ -->
            <div class="aryana-tab-panel <?php echo $active_tab === 'other_ways' ? 'aryana-panel-active' : ''; ?>" id="tab-panel-other_ways" data-tab="other_ways" style="<?php echo $active_tab === 'other_ways' ? 'display:block;' : 'display:none;'; ?>">
                <div class="card aryana-card">
                    <h2 style="margin-top:0;">Other Ways To Support (Offline & Bank Details)</h2>
                    <p style="color:#64748B; font-size:14px; margin-bottom:20px;">
                        Configure the bank transfer details, cheques/postal donations, and offline donation instructions displayed at the bottom of the donation form.
                    </p>

                    <!-- Section Level Visibility & Headings -->
                    <h3 style="font-size:16px; font-weight:700; color:#0F172A; border-bottom:1px solid #E2E8F0; padding-bottom:8px; margin-top:24px;">
                        1. Section Settings
                    </h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable Section</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="ads_other_ways_enabled" value="yes" <?php checked($other_ways['enabled'] ?? 'yes', 'yes'); ?>>
                                    Display "Other Ways To Support" block on donation form
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Section Heading</th>
                            <td>
                                <input type="text" name="ads_other_ways_title" value="<?php echo esc_attr($other_ways['section_title'] ?? 'Other Ways To Support'); ?>" class="large-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Section Subtitle</th>
                            <td>
                                <input type="text" name="ads_other_ways_subtitle" value="<?php echo esc_attr($other_ways['section_subtitle'] ?? 'Prefer donating offline or via direct bank transfer? You can use the details below.'); ?>" class="large-text">
                            </td>
                        </tr>
                    </table>

                    <!-- 1. UK Local Bank Transfer Details -->
                    <h3 style="font-size:16px; font-weight:700; color:#0F172A; border-bottom:1px solid #E2E8F0; padding-bottom:8px; margin-top:30px;">
                        2. UK Local Bank Transfer Details (Card 1)
                    </h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Card Title</th>
                            <td>
                                <input type="text" name="ads_uk_bank_title" value="<?php echo esc_attr($other_ways['uk_bank_title'] ?? 'UK Local Bank Transfer'); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Card Subtitle / Tag</th>
                            <td>
                                <input type="text" name="ads_uk_bank_subtitle" value="<?php echo esc_attr($other_ways['uk_bank_subtitle'] ?? 'Within the UK • Zero Fees'); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Instruction / Guidance Note</th>
                            <td>
                                <input type="text" name="ads_uk_bank_instruction" value="<?php echo esc_attr($other_ways['uk_bank_instruction'] ?? 'For donors transferring from a UK bank account. Please use only the Sort Code and Account Number below:'); ?>" class="large-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Bank Name</th>
                            <td>
                                <input type="text" name="ads_bank_name" value="<?php echo esc_attr($other_ways['bank_name'] ?? 'Barclays Bank PLC'); ?>" class="regular-text" style="font-weight:700;">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Account Name</th>
                            <td>
                                <input type="text" name="ads_bank_account_name" value="<?php echo esc_attr($other_ways['account_name'] ?? 'Aryana Aid'); ?>" class="regular-text" style="font-weight:700;">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Sort Code</th>
                            <td>
                                <input type="text" name="ads_bank_sort_code" value="<?php echo esc_attr($other_ways['sort_code'] ?? '20-29-77'); ?>" class="regular-text" style="font-weight:700;">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Account Number</th>
                            <td>
                                <input type="text" name="ads_bank_account_number" value="<?php echo esc_attr($other_ways['account_number'] ?? '90526184'); ?>" class="regular-text" style="font-weight:700;">
                            </td>
                        </tr>
                    </table>

                    <!-- 2. International Bank Transfer Details -->
                    <h3 style="font-size:16px; font-weight:700; color:#0F172A; border-bottom:1px solid #E2E8F0; padding-bottom:8px; margin-top:30px;">
                        3. International Bank Transfer Details (Card 2)
                    </h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Card Title</th>
                            <td>
                                <input type="text" name="ads_intl_bank_title" value="<?php echo esc_attr($other_ways['intl_bank_title'] ?? 'International Bank Transfer'); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Card Subtitle / Tag</th>
                            <td>
                                <input type="text" name="ads_intl_bank_subtitle" value="<?php echo esc_attr($other_ways['intl_bank_subtitle'] ?? 'Outside the UK • Worldwide Wire'); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Instruction / Guidance Note</th>
                            <td>
                                <input type="text" name="ads_intl_bank_instruction" value="<?php echo esc_attr($other_ways['intl_bank_instruction'] ?? 'For international donors transferring from outside the UK. Please use the SWIFT/BIC code and IBAN below:'); ?>" class="large-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Bank Name</th>
                            <td>
                                <input type="text" name="ads_intl_bank_name" value="<?php echo esc_attr($other_ways['intl_bank_name'] ?? ($other_ways['bank_name'] ?? 'Barclays Bank PLC')); ?>" class="regular-text" style="font-weight:700;">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Account Name</th>
                            <td>
                                <input type="text" name="ads_intl_account_name" value="<?php echo esc_attr($other_ways['intl_account_name'] ?? ($other_ways['account_name'] ?? 'Aryana Aid')); ?>" class="regular-text" style="font-weight:700;">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Swift / BIC Code</th>
                            <td>
                                <input type="text" name="ads_bank_swift" value="<?php echo esc_attr($other_ways['swift'] ?? 'BUKBGB22'); ?>" class="regular-text" style="font-weight:700;">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">IBAN</th>
                            <td>
                                <input type="text" name="ads_bank_iban" value="<?php echo esc_attr($other_ways['iban'] ?? 'GB85 BUKB 2029 7790 5261 84'); ?>" class="large-text" style="font-weight:700;">
                            </td>
                        </tr>
                    </table>

                    <!-- Donate by Post Details -->
                    <h3 style="font-size:16px; font-weight:700; color:#0F172A; border-bottom:1px solid #E2E8F0; padding-bottom:8px; margin-top:30px;">
                        4. Donate By Post Details (Card 3)
                    </h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Card Title</th>
                            <td>
                                <input type="text" name="ads_post_title" value="<?php echo esc_attr($other_ways['post_title'] ?? 'Donate by Post'); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Card Subtitle / Tag</th>
                            <td>
                                <input type="text" name="ads_post_subtitle" value="<?php echo esc_attr($other_ways['post_subtitle'] ?? 'Cheques & Postal Orders'); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Cheque Instructions</th>
                            <td>
                                <textarea name="ads_post_instruction" rows="2" class="large-text"><?php echo esc_textarea(stripslashes($other_ways['post_instruction'] ?? 'Please make cheques payable to "ARYANA AID" with your name, address, and intended project written on the reverse:')); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Office / Postal Address</th>
                            <td>
                                <textarea name="ads_post_address" rows="5" class="large-text" style="font-family:monospace; font-size:13px; font-weight:600; line-height:1.5;"><?php echo esc_textarea(stripslashes($other_ways['post_address'] ?? "Unit 1\n62 Alexandra Road\nEnfield, London, EN3 7ER\nUnited Kingdom")); ?></textarea>
                                <p class="description">Enter each address line on a new row. It will be displayed cleanly on the donation page.</p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit" style="margin-top:24px;">
                        <input type="hidden" name="ads_other_ways_save" value="1">
                        <button type="submit" class="button button-primary button-large" style="background:#038B5C; border-color:#026E49; font-weight:700; padding:4px 20px;">
                            Save Other Ways To Support Details
                        </button>
                    </p>
                </div>
            </div>

            <!-- ============================================================
                 TAB 8: QUICK DONATE HERO WIDGET
                 ============================================================ -->
            <div class="aryana-tab-panel <?php echo $active_tab === 'quick_donate' ? 'aryana-panel-active' : ''; ?>" id="tab-panel-quick_donate" data-tab="quick_donate" style="<?php echo $active_tab === 'quick_donate' ? 'display:block;' : 'display:none;'; ?>">
                <div class="card aryana-card">
                    <h2 style="margin-top:0;">Quick Donate Hero Widget Configuration</h2>
                    <p style="color:#666;">
                        This modern 2-box donation widget can be placed anywhere using the shortcode <code style="font-size:14px; color:#038B5C; font-weight:700;">[ads_quick_donate]</code> or <code style="font-size:14px; color:#038B5C; font-weight:700;">[quick_donate_bar]</code>. Configure labels, default values, projects, and styling below.
                    </p>

                    <div style="background:#f0fdf4; border:1px solid #bbf7d0; padding:12px 16px; border-radius:8px; margin-bottom:20px;">
                        <strong style="color:#166534;">Shortcodes for Pages & Elementor:</strong>
                        <ul style="margin:6px 0 0 18px; color:#15803d; list-style-type:disc;">
                            <li><code>[ads_quick_donate]</code> — Standard 2-box Quick Donate card (Hero / Banner).</li>
                            <li><code>[ads_quick_donate btn_text="Give Now" project="Water - Pump"]</code> — Override with custom parameters.</li>
                        </ul>
                    </div>

                    <h3 style="border-bottom:2px solid #E2E8F0; padding-bottom:8px; margin-top:25px; color:#0f172a;">
                        Box 1 Settings: Project & Frequency
                    </h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Box 1 Header</th>
                            <td>
                                <input type="text" name="ads_qd_heading_1" value="<?php echo esc_attr($qd_heading_1); ?>" class="regular-text">
                                <p class="description">Title displayed at the top of Step 1 (e.g. "Project & Frequency").</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Project Dropdown Label</th>
                            <td>
                                <input type="text" name="ads_qd_label_project" value="<?php echo esc_attr($qd_label_project); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Default Selected Project</th>
                            <td>
                                <select name="ads_qd_default_project" class="regular-text">
                                    <?php foreach ($matrix as $p_name => $p_data): ?>
                                        <option value="<?php echo esc_attr($p_name); ?>" <?php selected($qd_default_project, $p_name); ?>>
                                            <?php echo esc_html($p_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">Which project is pre-selected when visitors first view the widget.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Allowed Projects in Dropdown</th>
                            <td>
                                <div style="max-height:180px; overflow-y:auto; background:#f8fafc; border:1px solid #cbd5e1; border-radius:6px; padding:10px 14px;">
                                    <?php foreach ($matrix as $p_name => $p_data): ?>
                                        <label style="display:block; margin-bottom:6px; font-weight:600;">
                                            <input type="checkbox" name="ads_qd_enabled_projects[]" value="<?php echo esc_attr($p_name); ?>" 
                                                <?php checked(empty($qd_enabled_projects) || in_array($p_name, $qd_enabled_projects, true)); ?>>
                                            <?php echo esc_html($p_name); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <p class="description">Select which projects appear in the Quick Donate dropdown. If all are checked (or none unchecked), all projects will show.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Auto-Sync Dashboard Projects</th>
                            <td>
                                <label style="font-weight:700; color:#038B5C;">
                                    <input type="checkbox" name="ads_qd_auto_include_new" value="yes" <?php checked($qd_auto_include_new, 'yes'); ?>>
                                    Automatically include newly added projects from the dashboard
                                </label>
                                <p class="description">When checked, any project added in "Projects & Causes" or "Project Gallery" will automatically appear in the Quick Donate Bar dropdown on the website without needing to manually re-save this screen.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Show Frequency Switcher</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="ads_qd_show_freq" value="yes" <?php checked($qd_show_freq, 'yes'); ?>>
                                    Display "Single Payment / Regular Payment" switcher tabs
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Frequency Switcher Label</th>
                            <td>
                                <input type="text" name="ads_qd_label_frequency" value="<?php echo esc_attr($qd_label_frequency); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Default Giving Frequency</th>
                            <td>
                                <select name="ads_qd_default_freq" class="regular-text">
                                    <option value="single" <?php selected($qd_default_freq, 'single'); ?>>Single Payment</option>
                                    <option value="monthly" <?php selected($qd_default_freq, 'monthly'); ?>>Regular Payment (Monthly)</option>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <h3 style="border-bottom:2px solid #E2E8F0; padding-bottom:8px; margin-top:25px; color:#0f172a;">
                        Box 2 Settings: Amount, Donation Type & Action
                    </h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Box 2 Header</th>
                            <td>
                                <input type="text" name="ads_qd_heading_2" value="<?php echo esc_attr($qd_heading_2); ?>" class="regular-text">
                                <p class="description">Title displayed at the top of Step 2 (e.g. "Amount & Donation Type").</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Amount Field Label</th>
                            <td>
                                <input type="text" name="ads_qd_label_amount" value="<?php echo esc_attr($qd_label_amount); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Donation Type Field Label</th>
                            <td>
                                <input type="text" name="ads_qd_label_type" value="<?php echo esc_attr($qd_label_type); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Donate Button Text</th>
                            <td>
                                <input type="text" name="ads_qd_btn_text" value="<?php echo esc_attr($qd_btn_text); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Button Background Color</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="color" id="ads_qd_btn_bg_picker" value="<?php echo esc_attr(strtolower($qd_btn_bg)); ?>" style="height:38px; width:48px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_btn_bg').value = this.value.toUpperCase();">
                                    <input type="text" name="ads_qd_btn_bg" id="ads_qd_btn_bg" value="<?php echo esc_attr($qd_btn_bg); ?>" class="regular-text" style="width:110px; height:38px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_btn_bg_picker').value = v.toLowerCase(); }">
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_btn_bg').value='#038B5C'; document.getElementById('ads_qd_btn_bg_picker').value='#038b5c';" style="height:38px; line-height:36px; color:#038B5C; font-weight:700;">Set #038B5C (Emerald Green)</button>
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_btn_bg').value='#D8005A'; document.getElementById('ads_qd_btn_bg_picker').value='#d8005a';" style="height:38px; line-height:36px; color:#D8005A; font-weight:700;">Set #D8005A (Crimson)</button>
                                </div>
                                <p class="description">Select a color using the picker or type/paste any hex code. Click preset buttons for 1-click apply.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Button Hover Background Color</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="color" id="ads_qd_btn_hover_bg_picker" value="<?php echo esc_attr(strtolower($qd_btn_hover_bg)); ?>" style="height:38px; width:48px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_btn_hover_bg').value = this.value.toUpperCase();">
                                    <input type="text" name="ads_qd_btn_hover_bg" id="ads_qd_btn_hover_bg" value="<?php echo esc_attr($qd_btn_hover_bg); ?>" class="regular-text" style="width:110px; height:38px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_btn_hover_bg_picker').value = v.toLowerCase(); }">
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_btn_hover_bg').value='#026E49'; document.getElementById('ads_qd_btn_hover_bg_picker').value='#026e49';" style="height:38px; line-height:36px; color:#026E49; font-weight:700;">Set #026E49 (Dark Green)</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Button Text Color</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="color" id="ads_qd_btn_color_picker" value="<?php echo esc_attr(strtolower($qd_btn_color)); ?>" style="height:38px; width:48px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_btn_color').value = this.value.toUpperCase();">
                                    <input type="text" name="ads_qd_btn_color" id="ads_qd_btn_color" value="<?php echo esc_attr($qd_btn_color); ?>" class="regular-text" style="width:110px; height:38px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_btn_color_picker').value = v.toLowerCase(); }">
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_btn_color').value='#FFFFFF'; document.getElementById('ads_qd_btn_color_picker').value='#ffffff';" style="height:38px; line-height:36px; font-weight:700;">Set #FFFFFF (White)</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Button Hover Text Color</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="color" id="ads_qd_btn_hover_color_picker" value="<?php echo esc_attr(strtolower($qd_btn_hover_color)); ?>" style="height:38px; width:48px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_btn_hover_color').value = this.value.toUpperCase();">
                                    <input type="text" name="ads_qd_btn_hover_color" id="ads_qd_btn_hover_color" value="<?php echo esc_attr($qd_btn_hover_color); ?>" class="regular-text" style="width:110px; height:38px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_btn_hover_color_picker').value = v.toLowerCase(); }">
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_btn_hover_color').value='#FFFFFF'; document.getElementById('ads_qd_btn_hover_color_picker').value='#ffffff';" style="height:38px; line-height:36px; font-weight:700;">Set #FFFFFF (White)</button>
                                </div>
                            </td>
                        </tr>
                    </table>

                    <h3 style="border-bottom:2px solid #E2E8F0; padding-bottom:8px; margin-top:35px; color:#0f172a;">
                        🎨 Design &amp; Styling Controls (Appearance Customizer)
                    </h3>
                    <p style="color:#666; margin-bottom:15px;">
                        Customize every visual element of the Quick Donate card to match your brand style without editing any theme or plugin code.
                    </p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">Card Background Color</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="color" id="ads_qd_card_bg_picker" value="<?php echo esc_attr(strtolower($qd_card_bg)); ?>" style="height:38px; width:48px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_card_bg').value = this.value.toUpperCase();">
                                    <input type="text" name="ads_qd_card_bg" id="ads_qd_card_bg" value="<?php echo esc_attr($qd_card_bg); ?>" class="regular-text" style="width:110px; height:38px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_card_bg_picker').value = v.toLowerCase(); }">
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_card_bg').value='#FFFFFF'; document.getElementById('ads_qd_card_bg_picker').value='#ffffff';" style="height:38px; line-height:36px; font-weight:700;">#FFFFFF (White)</button>
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_card_bg').value='#F8FAFC'; document.getElementById('ads_qd_card_bg_picker').value='#f8fafc';" style="height:38px; line-height:36px;">#F8FAFC (Light Gray)</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Card Border Color</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="color" id="ads_qd_card_border_picker" value="<?php echo esc_attr(strtolower($qd_card_border)); ?>" style="height:38px; width:48px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_card_border').value = this.value.toUpperCase();">
                                    <input type="text" name="ads_qd_card_border" id="ads_qd_card_border" value="<?php echo esc_attr($qd_card_border); ?>" class="regular-text" style="width:110px; height:38px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_card_border_picker').value = v.toLowerCase(); }">
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_card_border').value='#E2E8F0'; document.getElementById('ads_qd_card_border_picker').value='#e2e8f0';" style="height:38px; line-height:36px;">#E2E8F0 (Default)</button>
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_card_border').value='#CBD5E1'; document.getElementById('ads_qd_card_border_picker').value='#cbd5e1';" style="height:38px; line-height:36px;">#CBD5E1 (Slate)</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Card Corner Radius</th>
                            <td>
                                <select name="ads_qd_card_radius" class="regular-text" style="height:38px;">
                                    <option value="6px" <?php selected($qd_card_radius, '6px'); ?>>6px (Slightly Rounded)</option>
                                    <option value="10px" <?php selected($qd_card_radius, '10px'); ?>>10px (Modern Medium)</option>
                                    <option value="14px" <?php selected($qd_card_radius, '14px'); ?>>14px (Default Smooth)</option>
                                    <option value="18px" <?php selected($qd_card_radius, '18px'); ?>>18px (Extra Rounded)</option>
                                    <option value="24px" <?php selected($qd_card_radius, '24px'); ?>>24px (Soft Pill Box)</option>
                                    <option value="0px" <?php selected($qd_card_radius, '0px'); ?>>0px (Sharp Corners)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Card Shadow Depth</th>
                            <td>
                                <select name="ads_qd_card_shadow" class="regular-text" style="height:38px;">
                                    <option value="elevated" <?php selected($qd_card_shadow, 'elevated'); ?>>Elevated 3D Shadow (Default)</option>
                                    <option value="subtle" <?php selected($qd_card_shadow, 'subtle'); ?>>Soft Subtle Shadow</option>
                                    <option value="flat" <?php selected($qd_card_shadow, 'flat'); ?>>Flat Border (No Shadow)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Card Max Width</th>
                            <td>
                                <input type="text" name="ads_qd_max_width" value="<?php echo esc_attr($qd_max_width); ?>" class="regular-text" style="height:38px;">
                                <p class="description">e.g. <code>100%</code>, <code>1140px</code>, <code>980px</code>, or <code>850px</code>.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Theme Accent Color</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="color" id="ads_qd_accent_color_picker" value="<?php echo esc_attr(strtolower($qd_accent_color)); ?>" style="height:38px; width:48px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_accent_color').value = this.value.toUpperCase();">
                                    <input type="text" name="ads_qd_accent_color" id="ads_qd_accent_color" value="<?php echo esc_attr($qd_accent_color); ?>" class="regular-text" style="width:110px; height:38px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_accent_color_picker').value = v.toLowerCase(); }">
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_accent_color').value='#038B5C'; document.getElementById('ads_qd_accent_color_picker').value='#038b5c';" style="height:38px; line-height:36px; color:#038B5C; font-weight:700;">#038B5C (Emerald)</button>
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_accent_color').value='#D8005A'; document.getElementById('ads_qd_accent_color_picker').value='#d8005a';" style="height:38px; line-height:36px; color:#D8005A; font-weight:700;">#D8005A (Crimson)</button>
                                </div>
                                <p class="description">Controls Step badge numbers (1 &amp; 2), dropdown chevron arrows, active tab highlight, and focus ring.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Step Titles Color</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="color" id="ads_qd_title_color_picker" value="<?php echo esc_attr(strtolower($qd_title_color)); ?>" style="height:38px; width:48px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_title_color').value = this.value.toUpperCase();">
                                    <input type="text" name="ads_qd_title_color" id="ads_qd_title_color" value="<?php echo esc_attr($qd_title_color); ?>" class="regular-text" style="width:110px; height:38px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_title_color_picker').value = v.toLowerCase(); }">
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_title_color').value='#0F172A'; document.getElementById('ads_qd_title_color_picker').value='#0f172a';" style="height:38px; line-height:36px; font-weight:700;">#0F172A (Deep Slate)</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Field Labels Color</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="color" id="ads_qd_label_color_picker" value="<?php echo esc_attr(strtolower($qd_label_color)); ?>" style="height:38px; width:48px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_label_color').value = this.value.toUpperCase();">
                                    <input type="text" name="ads_qd_label_color" id="ads_qd_label_color" value="<?php echo esc_attr($qd_label_color); ?>" class="regular-text" style="width:110px; height:38px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_label_color_picker').value = v.toLowerCase(); }">
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_label_color').value='#64748B'; document.getElementById('ads_qd_label_color_picker').value='#64748b';" style="height:38px; line-height:36px;">#64748B (Slate Muted)</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Input / Dropdown Background</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="color" id="ads_qd_input_bg_picker" value="<?php echo esc_attr(strtolower($qd_input_bg)); ?>" style="height:38px; width:48px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_input_bg').value = this.value.toUpperCase();">
                                    <input type="text" name="ads_qd_input_bg" id="ads_qd_input_bg" value="<?php echo esc_attr($qd_input_bg); ?>" class="regular-text" style="width:110px; height:38px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_input_bg_picker').value = v.toLowerCase(); }">
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_input_bg').value='#FFFFFF'; document.getElementById('ads_qd_input_bg_picker').value='#ffffff';" style="height:38px; line-height:36px; font-weight:700;">#FFFFFF (White)</button>
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_input_bg').value='#F8FAFC'; document.getElementById('ads_qd_input_bg_picker').value='#f8fafc';" style="height:38px; line-height:36px;">#F8FAFC (Soft Gray)</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Input / Dropdown Border</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="color" id="ads_qd_input_border_picker" value="<?php echo esc_attr(strtolower($qd_input_border)); ?>" style="height:38px; width:48px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_input_border').value = this.value.toUpperCase();">
                                    <input type="text" name="ads_qd_input_border" id="ads_qd_input_border" value="<?php echo esc_attr($qd_input_border); ?>" class="regular-text" style="width:110px; height:38px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_input_border_picker').value = v.toLowerCase(); }">
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_input_border').value='#CBD5E1'; document.getElementById('ads_qd_input_border_picker').value='#cbd5e1';" style="height:38px; line-height:36px;">#CBD5E1 (Default)</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Input Focus &amp; Hover Border</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="color" id="ads_qd_input_focus_border_picker" value="<?php echo esc_attr(strtolower($qd_input_focus_border)); ?>" style="height:38px; width:48px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_input_focus_border').value = this.value.toUpperCase();">
                                    <input type="text" name="ads_qd_input_focus_border" id="ads_qd_input_focus_border" value="<?php echo esc_attr($qd_input_focus_border); ?>" class="regular-text" style="width:110px; height:38px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_input_focus_border_picker').value = v.toLowerCase(); }">
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_input_focus_border').value='#038B5C'; document.getElementById('ads_qd_input_focus_border_picker').value='#038b5c';" style="height:38px; line-height:36px; color:#038B5C; font-weight:700;">#038B5C (Emerald)</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Icons &amp; Chevron Color</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="color" id="ads_qd_icon_color_picker" value="<?php echo esc_attr(strtolower($qd_icon_color)); ?>" style="height:38px; width:48px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_icon_color').value = this.value.toUpperCase();">
                                    <input type="text" name="ads_qd_icon_color" id="ads_qd_icon_color" value="<?php echo esc_attr($qd_icon_color); ?>" class="regular-text" style="width:110px; height:38px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_icon_color_picker').value = v.toLowerCase(); }">
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_icon_color').value='#038B5C'; document.getElementById('ads_qd_icon_color_picker').value='#038b5c';" style="height:38px; line-height:36px; color:#038B5C; font-weight:700;">#038B5C (Emerald)</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Input / Dropdown Corner Radius</th>
                            <td>
                                <select name="ads_qd_input_radius" class="regular-text" style="height:38px;">
                                    <option value="6px" <?php selected($qd_input_radius, '6px'); ?>>6px (Compact)</option>
                                    <option value="8px" <?php selected($qd_input_radius, '8px'); ?>>8px (Standard)</option>
                                    <option value="10px" <?php selected($qd_input_radius, '10px'); ?>>10px (Default Smooth)</option>
                                    <option value="12px" <?php selected($qd_input_radius, '12px'); ?>>12px (Rounded)</option>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <h3 style="border-bottom:2px solid #E2E8F0; padding-bottom:8px; margin-top:35px; color:#0f172a;">
                        ✨ Unique Bespoke Dropdown Menu Styling (Popup Appearance)
                    </h3>
                    <p style="color:#666; margin-bottom:15px;">
                        Customize every visual element of the bespoke popup dropdown menus (menu card, search bar, item hover states, and suggested amount badges).
                    </p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">Dropdown Menu Background</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="color" id="ads_qd_dropdown_bg_picker" value="<?php echo esc_attr(strtolower($qd_dropdown_bg)); ?>" style="height:38px; width:48px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_dropdown_bg').value = this.value.toUpperCase();">
                                    <input type="text" name="ads_qd_dropdown_bg" id="ads_qd_dropdown_bg" value="<?php echo esc_attr($qd_dropdown_bg); ?>" class="regular-text" style="width:110px; height:38px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_dropdown_bg_picker').value = v.toLowerCase(); }">
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_dropdown_bg').value='#FFFFFF'; document.getElementById('ads_qd_dropdown_bg_picker').value='#ffffff';" style="height:38px; line-height:36px; font-weight:700;">#FFFFFF (White)</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Dropdown Menu Border</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="color" id="ads_qd_dropdown_border_picker" value="<?php echo esc_attr(strtolower($qd_dropdown_border)); ?>" style="height:38px; width:48px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_dropdown_border').value = this.value.toUpperCase();">
                                    <input type="text" name="ads_qd_dropdown_border" id="ads_qd_dropdown_border" value="<?php echo esc_attr($qd_dropdown_border); ?>" class="regular-text" style="width:110px; height:38px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_dropdown_border_picker').value = v.toLowerCase(); }">
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_dropdown_border').value='#E2E8F0'; document.getElementById('ads_qd_dropdown_border_picker').value='#e2e8f0';" style="height:38px; line-height:36px;">#E2E8F0 (Default)</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Dropdown Menu Corner Radius</th>
                            <td>
                                <select name="ads_qd_dropdown_radius" class="regular-text" style="height:38px;">
                                    <option value="8px" <?php selected($qd_dropdown_radius, '8px'); ?>>8px (Subtle)</option>
                                    <option value="12px" <?php selected($qd_dropdown_radius, '12px'); ?>>12px (Default Smooth)</option>
                                    <option value="16px" <?php selected($qd_dropdown_radius, '16px'); ?>>16px (Modern Rounded)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Dropdown Item Text Color</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="color" id="ads_qd_dropdown_text_color_picker" value="<?php echo esc_attr(strtolower($qd_dropdown_text_color)); ?>" style="height:38px; width:48px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_dropdown_text_color').value = this.value.toUpperCase();">
                                    <input type="text" name="ads_qd_dropdown_text_color" id="ads_qd_dropdown_text_color" value="<?php echo esc_attr($qd_dropdown_text_color); ?>" class="regular-text" style="width:110px; height:38px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_dropdown_text_color_picker').value = v.toLowerCase(); }">
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_dropdown_text_color').value='#0F172A'; document.getElementById('ads_qd_dropdown_text_color_picker').value='#0f172a';" style="height:38px; line-height:36px; font-weight:700;">#0F172A (Deep Slate)</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Item Hover Background Color</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="color" id="ads_qd_dropdown_hover_bg_picker" value="<?php echo esc_attr(strtolower($qd_dropdown_hover_bg)); ?>" style="height:38px; width:48px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_dropdown_hover_bg').value = this.value.toUpperCase();">
                                    <input type="text" name="ads_qd_dropdown_hover_bg" id="ads_qd_dropdown_hover_bg" value="<?php echo esc_attr($qd_dropdown_hover_bg); ?>" class="regular-text" style="width:110px; height:38px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_dropdown_hover_bg_picker').value = v.toLowerCase(); }">
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_dropdown_hover_bg').value='#F0FDF4'; document.getElementById('ads_qd_dropdown_hover_bg_picker').value='#f0fdf4';" style="height:38px; line-height:36px; color:#038B5C; font-weight:700;">#F0FDF4 (Light Emerald)</button>
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_dropdown_hover_bg').value='#F8FAFC'; document.getElementById('ads_qd_dropdown_hover_bg_picker').value='#f8fafc';" style="height:38px; line-height:36px;">#F8FAFC (Soft Gray)</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Item Hover Text Color</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="color" id="ads_qd_dropdown_hover_text_picker" value="<?php echo esc_attr(strtolower($qd_dropdown_hover_text)); ?>" style="height:38px; width:48px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_dropdown_hover_text').value = this.value.toUpperCase();">
                                    <input type="text" name="ads_qd_dropdown_hover_text" id="ads_qd_dropdown_hover_text" value="<?php echo esc_attr($qd_dropdown_hover_text); ?>" class="regular-text" style="width:110px; height:38px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_dropdown_hover_text_picker').value = v.toLowerCase(); }">
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_dropdown_hover_text').value='#038B5C'; document.getElementById('ads_qd_dropdown_hover_text_picker').value='#038b5c';" style="height:38px; line-height:36px; color:#038B5C; font-weight:700;">#038B5C (Emerald)</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Suggested Amount Badge Background</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="color" id="ads_qd_dropdown_badge_bg_picker" value="<?php echo esc_attr(strtolower($qd_dropdown_badge_bg)); ?>" style="height:38px; width:48px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_dropdown_badge_bg').value = this.value.toUpperCase();">
                                    <input type="text" name="ads_qd_dropdown_badge_bg" id="ads_qd_dropdown_badge_bg" value="<?php echo esc_attr($qd_dropdown_badge_bg); ?>" class="regular-text" style="width:110px; height:38px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_dropdown_badge_bg_picker').value = v.toLowerCase(); }">
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_dropdown_badge_bg').value='#E2E8F0'; document.getElementById('ads_qd_dropdown_badge_bg_picker').value='#e2e8f0';" style="height:38px; line-height:36px;">#E2E8F0 (Default)</button>
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_dropdown_badge_bg').value='#DCFCE7'; document.getElementById('ads_qd_dropdown_badge_bg_picker').value='#dcfce7';" style="height:38px; line-height:36px; color:#166534; font-weight:700;">#DCFCE7 (Mint Green)</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Suggested Amount Badge Text Color</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="color" id="ads_qd_dropdown_badge_color_picker" value="<?php echo esc_attr(strtolower($qd_dropdown_badge_color)); ?>" style="height:38px; width:48px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_dropdown_badge_color').value = this.value.toUpperCase();">
                                    <input type="text" name="ads_qd_dropdown_badge_color" id="ads_qd_dropdown_badge_color" value="<?php echo esc_attr($qd_dropdown_badge_color); ?>" class="regular-text" style="width:110px; height:38px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_dropdown_badge_color_picker').value = v.toLowerCase(); }">
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_dropdown_badge_color').value='#334155'; document.getElementById('ads_qd_dropdown_badge_color_picker').value='#334155';" style="height:38px; line-height:36px;">#334155 (Slate Dark)</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Search Filter Box Background</th>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="color" id="ads_qd_dropdown_search_bg_picker" value="<?php echo esc_attr(strtolower($qd_dropdown_search_bg)); ?>" style="height:38px; width:48px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_dropdown_search_bg').value = this.value.toUpperCase();">
                                    <input type="text" name="ads_qd_dropdown_search_bg" id="ads_qd_dropdown_search_bg" value="<?php echo esc_attr($qd_dropdown_search_bg); ?>" class="regular-text" style="width:110px; height:38px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_dropdown_search_bg_picker').value = v.toLowerCase(); }">
                                    <button type="button" class="button" onclick="document.getElementById('ads_qd_dropdown_search_bg').value='#F8FAFC'; document.getElementById('ads_qd_dropdown_search_bg_picker').value='#f8fafc';" style="height:38px; line-height:36px;">#F8FAFC (Soft Light)</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Giving Frequency Tabs Styling</th>
                            <td>
                                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:15px; max-width:550px;">
                                    <div style="margin-bottom:12px;">
                                        <label style="display:block; font-weight:700; margin-bottom:4px; font-size:12px; text-transform:uppercase; color:#475569;">Active Tab Background</label>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <input type="color" id="ads_qd_freq_active_bg_picker" value="<?php echo esc_attr(strtolower($qd_freq_active_bg)); ?>" style="height:35px; width:45px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_freq_active_bg').value = this.value.toUpperCase();">
                                            <input type="text" name="ads_qd_freq_active_bg" id="ads_qd_freq_active_bg" value="<?php echo esc_attr($qd_freq_active_bg); ?>" class="regular-text" style="width:110px; height:35px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_freq_active_bg_picker').value = v.toLowerCase(); }">
                                            <button type="button" class="button button-small" onclick="document.getElementById('ads_qd_freq_active_bg').value='#038B5C'; document.getElementById('ads_qd_freq_active_bg_picker').value='#038b5c';" style="color:#038B5C; font-weight:700;">#038B5C (Emerald)</button>
                                            <button type="button" class="button button-small" onclick="document.getElementById('ads_qd_freq_active_bg').value='#D8005A'; document.getElementById('ads_qd_freq_active_bg_picker').value='#d8005a';" style="color:#D8005A; font-weight:700;">#D8005A (Crimson)</button>
                                        </div>
                                    </div>
                                    <div style="margin-bottom:12px;">
                                        <label style="display:block; font-weight:700; margin-bottom:4px; font-size:12px; text-transform:uppercase; color:#475569;">Active Tab Text Color</label>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <input type="color" id="ads_qd_freq_active_color_picker" value="<?php echo esc_attr(strtolower($qd_freq_active_color)); ?>" style="height:35px; width:45px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_freq_active_color').value = this.value.toUpperCase();">
                                            <input type="text" name="ads_qd_freq_active_color" id="ads_qd_freq_active_color" value="<?php echo esc_attr($qd_freq_active_color); ?>" class="regular-text" style="width:110px; height:35px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_freq_active_color_picker').value = v.toLowerCase(); }">
                                            <button type="button" class="button button-small" onclick="document.getElementById('ads_qd_freq_active_color').value='#FFFFFF'; document.getElementById('ads_qd_freq_active_color_picker').value='#ffffff';">#FFFFFF (White)</button>
                                        </div>
                                    </div>
                                    <div style="margin-bottom:12px;">
                                        <label style="display:block; font-weight:700; margin-bottom:4px; font-size:12px; text-transform:uppercase; color:#475569;">Inactive Tab Text Color (No Background)</label>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <input type="color" id="ads_qd_freq_inactive_color_picker" value="<?php echo esc_attr(strtolower($qd_freq_inactive_color)); ?>" style="height:35px; width:45px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_freq_inactive_color').value = this.value.toUpperCase();">
                                            <input type="text" name="ads_qd_freq_inactive_color" id="ads_qd_freq_inactive_color" value="<?php echo esc_attr($qd_freq_inactive_color); ?>" class="regular-text" style="width:110px; height:35px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_freq_inactive_color_picker').value = v.toLowerCase(); }">
                                            <button type="button" class="button button-small" onclick="document.getElementById('ads_qd_freq_inactive_color').value='#64748B'; document.getElementById('ads_qd_freq_inactive_color_picker').value='#64748b';">#64748B (Slate)</button>
                                        </div>
                                        <p class="description" style="margin-top:4px;">Inactive tabs have no background color (fully transparent) like the modern donation page tabs.</p>
                                    </div>
                                    <div>
                                        <label style="display:block; font-weight:700; margin-bottom:4px; font-size:12px; text-transform:uppercase; color:#475569;">Switcher Track / Container Background</label>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <input type="color" id="ads_qd_freq_container_bg_picker" value="<?php echo esc_attr(strtolower($qd_freq_container_bg)); ?>" style="height:35px; width:45px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" oninput="document.getElementById('ads_qd_freq_container_bg').value = this.value.toUpperCase();">
                                            <input type="text" name="ads_qd_freq_container_bg" id="ads_qd_freq_container_bg" value="<?php echo esc_attr($qd_freq_container_bg); ?>" class="regular-text" style="width:110px; height:35px; font-weight:700; text-transform:uppercase; text-align:center;" oninput="var v=this.value.trim(); if(/^#[0-9A-Fa-f]{6}$/.test(v)){ document.getElementById('ads_qd_freq_container_bg_picker').value = v.toLowerCase(); }">
                                            <button type="button" class="button button-small" onclick="document.getElementById('ads_qd_freq_container_bg').value='#F1F5F9'; document.getElementById('ads_qd_freq_container_bg_picker').value='#f1f5f9';">#F1F5F9 (Default)</button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Button Corner Radius</th>
                            <td>
                                <select name="ads_qd_btn_radius" class="regular-text" style="height:38px;">
                                    <option value="6px" <?php selected($qd_btn_radius, '6px'); ?>>6px (Standard)</option>
                                    <option value="8px" <?php selected($qd_btn_radius, '8px'); ?>>8px (Default Smooth)</option>
                                    <option value="10px" <?php selected($qd_btn_radius, '10px'); ?>>10px (Modern)</option>
                                    <option value="14px" <?php selected($qd_btn_radius, '14px'); ?>>14px (Curved)</option>
                                    <option value="30px" <?php selected($qd_btn_radius, '30px'); ?>>30px (Pill Button)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Custom CSS Rules (Overrides)</th>
                            <td>
                                <textarea name="ads_qd_custom_css" rows="5" class="large-text" style="font-family:Consolas, Monaco, monospace; font-size:13px; line-height:1.5;" placeholder=".ads-quick-hero-card { /* Custom styling */ }"><?php echo esc_textarea($qd_custom_css); ?></textarea>
                                <p class="description">Write custom CSS rules specifically for this widget. They will be loaded automatically on pages with the Quick Donate bar.</p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit" style="margin-top:24px;">
                        <input type="hidden" name="ads_quick_donate_save" value="1">
                        <button type="submit" name="active_tab" value="quick_donate" class="button button-primary button-large" style="background:#038B5C; border-color:#026E49; font-weight:700; padding:4px 20px;">
                            Save Quick Donate Settings
                        </button>
                    </p>
                </div>
            </div>

            <!-- ============================================================
                 TAB 9: SLIDE-OUT DONATION DRAWER (MINI-CART)
                 ============================================================ -->
            <div class="aryana-tab-panel <?php echo $active_tab === 'donation_drawer' ? 'aryana-panel-active' : ''; ?>" id="tab-panel-donation_drawer" data-tab="donation_drawer" style="<?php echo $active_tab === 'donation_drawer' ? 'display:block;' : 'display:none;'; ?>">
                <div class="card aryana-card" style="max-width:1000px; padding:25px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.06);">
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #E2E8F0; padding-bottom:15px; margin-bottom:20px;">
                        <div>
                            <h2 style="margin:0 0 5px 0; font-size:22px; color:#0F172A; display:flex; align-items:center; gap:8px;">
                                🛒 Slide-Out Donation Drawer Cart
                            </h2>
                            <p style="margin:0; color:#64748B; font-size:14px;">
                                Automatically slides out from the right when any donation button is clicked, showing live items, quantity controls ([-] 1 [+]), and instant checkout navigation.
                            </p>
                        </div>
                        <div>
                            <label style="display:flex; align-items:center; gap:8px; font-weight:700; font-size:15px; cursor:pointer; background:#F1F5F9; padding:8px 14px; border-radius:6px; border:1px solid #CBD5E1;">
                                <input type="checkbox" name="ads_drawer_enabled" value="yes" <?php checked($drawer_settings['enabled'] ?? 'yes', 'yes'); ?>>
                                Enable Slide-Out Drawer
                            </label>
                        </div>
                    </div>

                    <table class="form-table" role="presentation">
                        <!-- SECTION 1: RESPONSIVE SCREEN SIZING & POSITION -->
                        <tr>
                            <th colspan="2" style="padding-top:10px; padding-bottom:6px;">
                                <h3 style="margin:0; font-size:17px; color:#038B5C; border-bottom:2px solid #E2E8F0; padding-bottom:6px;">
                                    📐 1. Responsive Screen Sizing & Position
                                </h3>
                                <p style="margin:4px 0 0 0; color:#64748B; font-size:13px;">Control exactly how wide the drawer appears on desktop monitors, tablet screens, and mobile phones.</p>
                            </th>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_desktop_width">Desktop Drawer Width</label></th>
                            <td>
                                <input name="ads_drawer_desktop_width" type="text" id="ads_drawer_desktop_width" value="<?php echo esc_attr($drawer_settings['desktop_width'] ?? '430px'); ?>" class="regular-text" style="max-width:180px;">
                                <p class="description">Width on desktop screens (default: <code>430px</code>, or e.g. <code>400px</code>, <code>460px</code>, <code>28vw</code>).</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_tablet_width">Tablet Drawer Width</label></th>
                            <td>
                                <input name="ads_drawer_tablet_width" type="text" id="ads_drawer_tablet_width" value="<?php echo esc_attr($drawer_settings['tablet_width'] ?? '50vw'); ?>" class="regular-text" style="max-width:180px;">
                                <p class="description">Width on tablet screens &le; 768px (default: <code>50vw</code>, or e.g. <code>400px</code>, <code>60vw</code>).</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_mobile_width">Mobile Drawer Width</label></th>
                            <td>
                                <input name="ads_drawer_mobile_width" type="text" id="ads_drawer_mobile_width" value="<?php echo esc_attr($drawer_settings['mobile_width'] ?? '70vw'); ?>" class="regular-text" style="max-width:180px;">
                                <p class="description">Width on mobile screens &le; 480px (default: <code>70vw</code> &mdash; leaves 30% view of the underlying page visible, or customize e.g. <code>75vw</code>, <code>80vw</code>).</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_panel_position">Slide-In Position</label></th>
                            <td>
                                <select name="ads_drawer_panel_position" id="ads_drawer_panel_position" style="min-width:180px;">
                                    <option value="right" <?php selected($drawer_settings['panel_position'] ?? 'right', 'right'); ?>>Slide from Right (Standard)</option>
                                    <option value="left" <?php selected($drawer_settings['panel_position'] ?? 'right', 'left'); ?>>Slide from Left</option>
                                </select>
                                <p class="description">Which side of the screen the drawer slides out from.</p>
                            </td>
                        </tr>

                        <!-- SECTION 2: HEADER TITLES & BUTTON LABELS -->
                        <tr>
                            <th colspan="2" style="padding-top:20px; padding-bottom:6px;">
                                <h3 style="margin:0; font-size:17px; color:#038B5C; border-bottom:2px solid #E2E8F0; padding-bottom:6px;">
                                    ✍️ 2. Header Titles & Button Labels
                                </h3>
                                <p style="margin:4px 0 0 0; color:#64748B; font-size:13px;">Customize every piece of text shown on the slide-out cart.</p>
                            </th>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_title">Drawer Header Title</label></th>
                            <td>
                                <input name="ads_drawer_title" type="text" id="ads_drawer_title" value="<?php echo esc_attr($drawer_settings['title'] ?? 'Your donations'); ?>" class="regular-text" style="font-weight:600;">
                                <p class="description">Title displayed at the top of the side drawer (default: "Your donations").</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_remove_text">Remove Item Link Text</label></th>
                            <td>
                                <input name="ads_drawer_remove_text" type="text" id="ads_drawer_remove_text" value="<?php echo esc_attr($drawer_settings['remove_text'] ?? 'Remove'); ?>" class="regular-text" style="max-width:180px;">
                                <p class="description">Text for removing an item from the basket (default: "Remove").</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_totals_title">Totals Section Title</label></th>
                            <td>
                                <input name="ads_drawer_totals_title" type="text" id="ads_drawer_totals_title" value="<?php echo esc_attr($drawer_settings['totals_title'] ?? 'Total Amount'); ?>" class="regular-text" style="max-width:240px;">
                                <p class="description">Title above the payment breakdown in footer (default: "Total Amount").</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_single_payment_label">Single Payment Label</label></th>
                            <td>
                                <input name="ads_drawer_single_payment_label" type="text" id="ads_drawer_single_payment_label" value="<?php echo esc_attr($drawer_settings['single_payment_label'] ?? 'Single Payment'); ?>" class="regular-text" style="max-width:240px;">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_monthly_payment_label">Monthly Payment Label</label></th>
                            <td>
                                <input name="ads_drawer_monthly_payment_label" type="text" id="ads_drawer_monthly_payment_label" value="<?php echo esc_attr($drawer_settings['monthly_payment_label'] ?? 'Monthly Payment'); ?>" class="regular-text" style="max-width:240px;">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_total_due_label">Total Due Today Label</label></th>
                            <td>
                                <input name="ads_drawer_total_due_label" type="text" id="ads_drawer_total_due_label" value="<?php echo esc_attr($drawer_settings['total_due_label'] ?? 'Total due today'); ?>" class="regular-text" style="max-width:240px; font-weight:700;">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_checkout_text">Checkout Button Text</label></th>
                            <td>
                                <input name="ads_drawer_checkout_text" type="text" id="ads_drawer_checkout_text" value="<?php echo esc_attr($drawer_settings['checkout_text'] ?? 'Proceed To Checkout'); ?>" class="regular-text" style="font-weight:600;">
                                <p class="description">Call-to-action button text on the bottom of the drawer (default: "Proceed To Checkout").</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_checkout_url">Checkout Target Page URL</label></th>
                            <td>
                                <input name="ads_drawer_checkout_url" type="url" id="ads_drawer_checkout_url" value="<?php echo esc_attr($drawer_settings['checkout_url'] ?? home_url('/donate/')); ?>" class="large-text">
                                <p class="description">Destination page when donor clicks "Proceed To Checkout" (default: <?php echo esc_url(home_url('/donate/')); ?>).</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_empty_text">Empty Basket Message</label></th>
                            <td>
                                <input name="ads_drawer_empty_text" type="text" id="ads_drawer_empty_text" value="<?php echo esc_attr($drawer_settings['empty_text'] ?? 'Your donation basket is currently empty.'); ?>" class="large-text">
                                <p class="description">Shown inside the drawer when all donations are removed.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_continue_text">Continue Browsing Text</label></th>
                            <td>
                                <input name="ads_drawer_continue_text" type="text" id="ads_drawer_continue_text" value="<?php echo esc_attr($drawer_settings['continue_text'] ?? 'Continue Browsing'); ?>" class="regular-text">
                                <p class="description">Text for the button shown on the empty state.</p>
                            </td>
                        </tr>

                        <!-- SECTION 3: DISPLAY CONTROLS -->
                        <tr>
                            <th colspan="2" style="padding-top:20px; padding-bottom:6px;">
                                <h3 style="margin:0; font-size:17px; color:#038B5C; border-bottom:2px solid #E2E8F0; padding-bottom:6px;">
                                    ⚙️ 3. Interactive Elements & Controls
                                </h3>
                            </th>
                        </tr>
                        <tr>
                            <th scope="row">Quantity Steppers</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="ads_drawer_show_qty" id="ads_drawer_show_qty" value="yes" <?php checked($drawer_settings['show_qty'] ?? 'yes', 'yes'); ?>>
                                    Show Quantity Stepper <strong>[-] 1 [+]</strong> on each donation item
                                </label>
                                <p class="description">Allows donors to easily increase or decrease unit quantities.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Remove Link</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="ads_drawer_show_remove" id="ads_drawer_show_remove" value="yes" <?php checked($drawer_settings['show_remove'] ?? 'yes', 'yes'); ?>>
                                    Show <strong>"Remove"</strong> link on each donation item
                                </label>
                                <p class="description">Allows donors to delete accidental or unwanted items immediately.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Project Thumbnail Images</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="ads_drawer_show_thumbnails" id="ads_drawer_show_thumbnails" value="yes" <?php checked($drawer_settings['show_thumbnails'] ?? 'no', 'yes'); ?>>
                                    Show project thumbnail photo next to each item (disabled by default for clean presentation)
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Floating Cart Button</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="ads_drawer_show_floating_icon" id="ads_drawer_show_floating_icon" value="yes" <?php checked($drawer_settings['show_floating_icon'] ?? 'yes', 'yes'); ?>>
                                    Display floating cart icon with item count badge on bottom corner of screen
                                </label>
                                <p class="description">Provides a 1-click shortcut for donors to reopen their drawer basket at any time.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Backdrop Dismissal</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="ads_drawer_close_on_backdrop" value="yes" <?php checked($drawer_settings['close_on_backdrop'] ?? 'yes', 'yes'); ?>>
                                    Close drawer when clicking outside (on darkened backdrop)
                                </label>
                            </td>
                        </tr>

                        <!-- SECTION 4: STYLING & COLORS -->
                        <tr>
                            <th colspan="2" style="padding-top:20px; padding-bottom:6px;">
                                <h3 style="margin:0; font-size:17px; color:#038B5C; border-bottom:2px solid #E2E8F0; padding-bottom:6px;">
                                    🎨 4. Theme, Colors & Typography
                                </h3>
                                <p style="margin:4px 0 0 0; color:#64748B; font-size:13px;">Style every individual visual element of the drawer.</p>
                            </th>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_bg_color">Drawer Background Color</label></th>
                            <td>
                                <div style="display:inline-flex; align-items:center; gap:8px;">
                                    <input name="ads_drawer_bg_color" type="color" id="ads_drawer_bg_color" value="<?php echo esc_attr($drawer_settings['bg_color'] ?? '#FAF8F5'); ?>" class="ads-color-picker" style="height:36px; width:48px; padding:2px; vertical-align:middle; border-radius:4px; cursor:pointer;">
                                    <input type="text" id="ads_drawer_bg_color_hex" value="<?php echo esc_attr($drawer_settings['bg_color'] ?? '#FAF8F5'); ?>" class="ads-color-hex" style="width:90px; height:34px; font-family:monospace; font-size:13px; font-weight:600; padding:2px 8px; border-radius:4px; border:1px solid #CBD5E1;">
                                </div>
                                <p class="description">Warm background tone (default: #FAF8F5).</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_header_color">Header Title Color</label></th>
                            <td>
                                <div style="display:inline-flex; align-items:center; gap:8px;">
                                    <input name="ads_drawer_header_color" type="color" id="ads_drawer_header_color" value="<?php echo esc_attr($drawer_settings['header_color'] ?? '#00473e'); ?>" class="ads-color-picker" style="height:36px; width:48px; padding:2px; vertical-align:middle; border-radius:4px; cursor:pointer;">
                                    <input type="text" id="ads_drawer_header_color_hex" value="<?php echo esc_attr($drawer_settings['header_color'] ?? '#00473e'); ?>" class="ads-color-hex" style="width:90px; height:34px; font-family:monospace; font-size:13px; font-weight:600; padding:2px 8px; border-radius:4px; border:1px solid #CBD5E1;">
                                </div>
                                <p class="description">Deep rich dark green for top title and totals due.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_item_title_color">Item Name Color</label></th>
                            <td>
                                <div style="display:inline-flex; align-items:center; gap:8px;">
                                    <input name="ads_drawer_item_title_color" type="color" id="ads_drawer_item_title_color" value="<?php echo esc_attr($drawer_settings['item_title_color'] ?? '#00473e'); ?>" class="ads-color-picker" style="height:36px; width:48px; padding:2px; vertical-align:middle; border-radius:4px; cursor:pointer;">
                                    <input type="text" id="ads_drawer_item_title_color_hex" value="<?php echo esc_attr($drawer_settings['item_title_color'] ?? '#00473e'); ?>" class="ads-color-hex" style="width:90px; height:34px; font-family:monospace; font-size:13px; font-weight:600; padding:2px 8px; border-radius:4px; border:1px solid #CBD5E1;">
                                </div>
                                <p class="description">Item headline color (e.g. "Winter Pack", "Orphan Sponsorship").</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_subtitle_color">Subtitle / Package Color</label></th>
                            <td>
                                <div style="display:inline-flex; align-items:center; gap:8px;">
                                    <input name="ads_drawer_subtitle_color" type="color" id="ads_drawer_subtitle_color" value="<?php echo esc_attr($drawer_settings['subtitle_color'] ?? '#008254'); ?>" class="ads-color-picker" style="height:36px; width:48px; padding:2px; vertical-align:middle; border-radius:4px; cursor:pointer;">
                                    <input type="text" id="ads_drawer_subtitle_color_hex" value="<?php echo esc_attr($drawer_settings['subtitle_color'] ?? '#008254'); ?>" class="ads-color-hex" style="width:90px; height:34px; font-family:monospace; font-size:13px; font-weight:600; padding:2px 8px; border-radius:4px; border:1px solid #CBD5E1;">
                                </div>
                                <p class="description">Emerald accent color for package labels (e.g. "Winter Pack", "Sponsor 1 Orphan").</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_item_price_color">Item Price Color</label></th>
                            <td>
                                <div style="display:inline-flex; align-items:center; gap:8px;">
                                    <input name="ads_drawer_item_price_color" type="color" id="ads_drawer_item_price_color" value="<?php echo esc_attr($drawer_settings['item_price_color'] ?? '#00473e'); ?>" class="ads-color-picker" style="height:36px; width:48px; padding:2px; vertical-align:middle; border-radius:4px; cursor:pointer;">
                                    <input type="text" id="ads_drawer_item_price_color_hex" value="<?php echo esc_attr($drawer_settings['item_price_color'] ?? '#00473e'); ?>" class="ads-color-hex" style="width:90px; height:34px; font-family:monospace; font-size:13px; font-weight:600; padding:2px 8px; border-radius:4px; border:1px solid #CBD5E1;">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_stepper_color">Stepper Icon & Text Color</label></th>
                            <td>
                                <div style="display:inline-flex; align-items:center; gap:8px;">
                                    <input name="ads_drawer_stepper_color" type="color" id="ads_drawer_stepper_color" value="<?php echo esc_attr($drawer_settings['stepper_color'] ?? '#00473e'); ?>" class="ads-color-picker" style="height:36px; width:48px; padding:2px; vertical-align:middle; border-radius:4px; cursor:pointer;">
                                    <input type="text" id="ads_drawer_stepper_color_hex" value="<?php echo esc_attr($drawer_settings['stepper_color'] ?? '#00473e'); ?>" class="ads-color-hex" style="width:90px; height:34px; font-family:monospace; font-size:13px; font-weight:600; padding:2px 8px; border-radius:4px; border:1px solid #CBD5E1;">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_stepper_bg">Stepper Box Background</label></th>
                            <td>
                                <div style="display:inline-flex; align-items:center; gap:8px;">
                                    <input name="ads_drawer_stepper_bg" type="color" id="ads_drawer_stepper_bg" value="<?php echo esc_attr($drawer_settings['stepper_bg'] ?? '#ffffff'); ?>" class="ads-color-picker" style="height:36px; width:48px; padding:2px; vertical-align:middle; border-radius:4px; cursor:pointer;">
                                    <input type="text" id="ads_drawer_stepper_bg_hex" value="<?php echo esc_attr($drawer_settings['stepper_bg'] ?? '#ffffff'); ?>" class="ads-color-hex" style="width:90px; height:34px; font-family:monospace; font-size:13px; font-weight:600; padding:2px 8px; border-radius:4px; border:1px solid #CBD5E1;">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_stepper_border_color">Stepper Border Color</label></th>
                            <td>
                                <div style="display:inline-flex; align-items:center; gap:8px;">
                                    <input name="ads_drawer_stepper_border_color" type="color" id="ads_drawer_stepper_border_color" value="<?php echo esc_attr($drawer_settings['stepper_border_color'] ?? '#D5CEBF'); ?>" class="ads-color-picker" style="height:36px; width:48px; padding:2px; vertical-align:middle; border-radius:4px; cursor:pointer;">
                                    <input type="text" id="ads_drawer_stepper_border_color_hex" value="<?php echo esc_attr($drawer_settings['stepper_border_color'] ?? '#D5CEBF'); ?>" class="ads-color-hex" style="width:90px; height:34px; font-family:monospace; font-size:13px; font-weight:600; padding:2px 8px; border-radius:4px; border:1px solid #CBD5E1;">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_remove_color">Remove Link Color</label></th>
                            <td>
                                <div style="display:inline-flex; align-items:center; gap:8px;">
                                    <input name="ads_drawer_remove_color" type="color" id="ads_drawer_remove_color" value="<?php echo esc_attr($drawer_settings['remove_color'] ?? '#9c7a56'); ?>" class="ads-color-picker" style="height:36px; width:48px; padding:2px; vertical-align:middle; border-radius:4px; cursor:pointer;">
                                    <input type="text" id="ads_drawer_remove_color_hex" value="<?php echo esc_attr($drawer_settings['remove_color'] ?? '#9c7a56'); ?>" class="ads-color-hex" style="width:90px; height:34px; font-family:monospace; font-size:13px; font-weight:600; padding:2px 8px; border-radius:4px; border:1px solid #CBD5E1;">
                                </div>
                                <p class="description">Warm muted tan/brown for the Remove link (default: #9c7a56).</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_btn_bg">Checkout Button Background</label></th>
                            <td>
                                <div style="display:inline-flex; align-items:center; gap:8px;">
                                    <input name="ads_drawer_btn_bg" type="color" id="ads_drawer_btn_bg" value="<?php echo esc_attr($drawer_settings['btn_bg'] ?? '#007a50'); ?>" class="ads-color-picker" style="height:36px; width:48px; padding:2px; vertical-align:middle; border-radius:4px; cursor:pointer;">
                                    <input type="text" id="ads_drawer_btn_bg_hex" value="<?php echo esc_attr($drawer_settings['btn_bg'] ?? '#007a50'); ?>" class="ads-color-hex" style="width:90px; height:34px; font-family:monospace; font-size:13px; font-weight:600; padding:2px 8px; border-radius:4px; border:1px solid #CBD5E1;">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_btn_hover_bg">Checkout Button Hover Background</label></th>
                            <td>
                                <div style="display:inline-flex; align-items:center; gap:8px;">
                                    <input name="ads_drawer_btn_hover_bg" type="color" id="ads_drawer_btn_hover_bg" value="<?php echo esc_attr($drawer_settings['btn_hover_bg'] ?? '#006341'); ?>" class="ads-color-picker" style="height:36px; width:48px; padding:2px; vertical-align:middle; border-radius:4px; cursor:pointer;">
                                    <input type="text" id="ads_drawer_btn_hover_bg_hex" value="<?php echo esc_attr($drawer_settings['btn_hover_bg'] ?? '#006341'); ?>" class="ads-color-hex" style="width:90px; height:34px; font-family:monospace; font-size:13px; font-weight:600; padding:2px 8px; border-radius:4px; border:1px solid #CBD5E1;">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_btn_color">Checkout Button Text Color</label></th>
                            <td>
                                <div style="display:inline-flex; align-items:center; gap:8px;">
                                    <input name="ads_drawer_btn_color" type="color" id="ads_drawer_btn_color" value="<?php echo esc_attr($drawer_settings['btn_color'] ?? '#ffffff'); ?>" class="ads-color-picker" style="height:36px; width:48px; padding:2px; vertical-align:middle; border-radius:4px; cursor:pointer;">
                                    <input type="text" id="ads_drawer_btn_color_hex" value="<?php echo esc_attr($drawer_settings['btn_color'] ?? '#ffffff'); ?>" class="ads-color-hex" style="width:90px; height:34px; font-family:monospace; font-size:13px; font-weight:600; padding:2px 8px; border-radius:4px; border:1px solid #CBD5E1;">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_btn_border_radius">Checkout Button Border Radius</label></th>
                            <td>
                                <input name="ads_drawer_btn_border_radius" type="text" id="ads_drawer_btn_border_radius" value="<?php echo esc_attr($drawer_settings['btn_border_radius'] ?? '8px'); ?>" class="regular-text" style="max-width:140px;">
                                <p class="description">Corner roundness (default: <code>8px</code>, or e.g. <code>4px</code>, <code>24px</code> for pill).</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_launcher_bg">Floating Cart Icon Background</label></th>
                            <td>
                                <div style="display:inline-flex; align-items:center; gap:8px;">
                                    <input name="ads_drawer_launcher_bg" type="color" id="ads_drawer_launcher_bg" value="<?php echo esc_attr($drawer_settings['launcher_bg'] ?? '#007a50'); ?>" class="ads-color-picker" style="height:36px; width:48px; padding:2px; vertical-align:middle; border-radius:4px; cursor:pointer;">
                                    <input type="text" id="ads_drawer_launcher_bg_hex" value="<?php echo esc_attr($drawer_settings['launcher_bg'] ?? '#007a50'); ?>" class="ads-color-hex" style="width:90px; height:34px; font-family:monospace; font-size:13px; font-weight:600; padding:2px 8px; border-radius:4px; border:1px solid #CBD5E1;">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_launcher_badge_bg">Floating Cart Badge Color</label></th>
                            <td>
                                <div style="display:inline-flex; align-items:center; gap:8px;">
                                    <input name="ads_drawer_launcher_badge_bg" type="color" id="ads_drawer_launcher_badge_bg" value="<?php echo esc_attr($drawer_settings['launcher_badge_bg'] ?? '#E11D48'); ?>" class="ads-color-picker" style="height:36px; width:48px; padding:2px; vertical-align:middle; border-radius:4px; cursor:pointer;">
                                    <input type="text" id="ads_drawer_launcher_badge_bg_hex" value="<?php echo esc_attr($drawer_settings['launcher_badge_bg'] ?? '#E11D48'); ?>" class="ads-color-hex" style="width:90px; height:34px; font-family:monospace; font-size:13px; font-weight:600; padding:2px 8px; border-radius:4px; border:1px solid #CBD5E1;">
                                </div>
                            </td>
                        </tr>

                        <!-- SECTION 5: FALLBACK & CUSTOM CSS -->
                        <tr>
                            <th colspan="2" style="padding-top:20px; padding-bottom:6px;">
                                <h3 style="margin:0; font-size:17px; color:#038B5C; border-bottom:2px solid #E2E8F0; padding-bottom:6px;">
                                    💻 5. Fallback Thumbnail & Custom CSS
                                </h3>
                            </th>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_default_image">Default Project Image URL</label></th>
                            <td>
                                <input name="ads_drawer_default_image" type="url" id="ads_drawer_default_image" value="<?php echo esc_attr($drawer_settings['default_image'] ?? ''); ?>" class="large-text" placeholder="https://yoursite.com/wp-content/uploads/default-thumbnail.jpg">
                                <p class="description">Optional fallback thumbnail photo if thumbnail images are enabled.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ads_drawer_custom_css">Custom CSS Overrides</label></th>
                            <td>
                                <textarea name="ads_drawer_custom_css" id="ads_drawer_custom_css" rows="4" class="large-text" style="font-family:Consolas, Monaco, monospace; font-size:13px; line-height:1.5;" placeholder="#ads-donation-drawer-root .ads-drawer-panel { /* Custom CSS */ }"><?php echo esc_textarea($drawer_settings['custom_css'] ?? ''); ?></textarea>
                                <p class="description">Inject custom CSS directly for the slide-out drawer.</p>
                            </td>
                        </tr>
                    </table>

                    <!-- SECTION 6: LIVE INTERACTIVE SCREEN SIMULATOR -->
                    <div style="margin-top:35px; border-top:2px solid #E2E8F0; padding-top:25px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap; gap:10px;">
                            <div>
                                <h3 style="margin:0; font-size:18px; color:#0F172A; display:flex; align-items:center; gap:8px;">
                                    👁️ Live Screen Previewer (See Drawer on Every Screen)
                                </h3>
                                <p style="margin:3px 0 0 0; color:#64748B; font-size:13px;">
                                    Switch devices below to preview how your drawer appears on Desktop, Tablet, and Mobile in real-time as you tweak settings.
                                </p>
                            </div>
                            <div style="display:flex; gap:6px; background:#E2E8F0; padding:4px; border-radius:8px;" id="ads-preview-device-switcher">
                                <button type="button" class="button ads-preview-tab ads-preview-active" data-device="desktop" style="border-radius:6px; font-weight:700; background:#ffffff; color:#0F172A; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                                    🖥️ Desktop (1000px+)
                                </button>
                                <button type="button" class="button ads-preview-tab" data-device="tablet" style="border-radius:6px; font-weight:600; background:transparent; color:#475569; border:none;">
                                    💻 Tablet (768px)
                                </button>
                                <button type="button" class="button ads-preview-tab" data-device="mobile" style="border-radius:6px; font-weight:600; background:transparent; color:#475569; border:none;">
                                    📱 Mobile (375px - 70vw)
                                </button>
                            </div>
                        </div>

                        <!-- Device Screen Simulator Viewport -->
                        <div id="ads-preview-viewport-wrap" style="background:#0F172A; border-radius:12px; padding:20px; display:flex; justify-content:center; align-items:flex-start; min-height:550px; overflow-x:auto;">
                            <!-- Simulated Screen Frame -->
                            <div id="ads-preview-screen-frame" style="width:100%; max-width:850px; height:520px; background:#f8fafc; border-radius:8px; box-shadow:0 10px 30px rgba(0,0,0,0.4); position:relative; overflow:hidden; display:flex; transition:all 0.3s ease;">
                                
                                <!-- Underlying Website Backdrop (Simulated Background) -->
                                <div style="flex:1; padding:20px; opacity:0.35; filter:grayscale(30%); pointer-events:none; background:#ffffff;">
                                    <div style="height:20px; width:40%; background:#CBD5E1; border-radius:4px; margin-bottom:15px;"></div>
                                    <div style="height:120px; background:#E2E8F0; border-radius:8px; margin-bottom:15px;"></div>
                                    <div style="display:flex; gap:10px;">
                                        <div style="height:32px; width:90px; background:#008254; border-radius:4px;"></div>
                                        <div style="height:32px; width:90px; background:#008254; border-radius:4px;"></div>
                                    </div>
                                </div>

                                <!-- Dimmed Overlay -->
                                <div style="position:absolute; inset:0; background:rgba(15,23,42,0.45); pointer-events:none;"></div>

                                <!-- Simulated Drawer Panel -->
                                <div id="ads-mockup-panel" style="position:absolute; top:0; right:0; width:430px; height:100%; background:#FAF8F5; box-shadow:-10px 0 35px rgba(0,0,0,0.22); display:flex; flex-direction:column; z-index:10; font-family:'Albert Sans', sans-serif; transition:all 0.3s ease; box-sizing:border-box;">
                                    
                                    <!-- Header -->
                                    <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 20px; border-bottom:1px solid #EAE4D9;">
                                        <h3 id="ads-mockup-title" style="margin:0; font-size:20px; font-weight:800; color:#00473e;">Your donations</h3>
                                        <div style="width:28px; height:28px; border:1px solid #D5CEBF; border-radius:6px; background:#ffffff; display:flex; align-items:center; justify-content:center; color:#334155; font-size:13px; font-weight:700; cursor:pointer;">&times;</div>
                                    </div>

                                    <!-- Mockup Items Body -->
                                    <div style="flex:1; overflow-y:auto; padding:14px 20px;">
                                        <!-- Mockup Item 1 -->
                                        <div style="display:flex; justify-content:space-between; align-items:flex-start; padding:12px 0; border-bottom:1px solid #EAE4D9;">
                                            <div style="flex:1;">
                                                <h4 id="ads-mockup-item-title-1" style="margin:0 0 2px 0; font-size:15px; font-weight:800; color:#00473e;">Winter Pack</h4>
                                                <div id="ads-mockup-sub-1" style="font-size:13px; font-weight:700; color:#008254; margin-bottom:3px;">Winter Pack</div>
                                                <div style="font-size:11.5px; color:#64748b; margin-bottom:4px;">Single Payment, Sadaqah</div>
                                                <div id="ads-mockup-price-1" style="font-size:15px; font-weight:800; color:#00473e;">£120.00</div>
                                            </div>
                                            <div style="display:flex; flex-direction:column; align-items:flex-end;">
                                                <div id="ads-mockup-stepper" style="display:inline-flex; align-items:center; border:1px solid #D5CEBF; border-radius:4px; background:#ffffff; height:22px; padding:0 4px;">
                                                    <span style="font-size:13px; font-weight:700; color:#00473e; padding:0 4px; cursor:pointer;">&minus;</span>
                                                    <span style="font-size:12px; font-weight:700; color:#00473e; min-width:16px; text-align:center;">1</span>
                                                    <span style="font-size:13px; font-weight:700; color:#00473e; padding:0 4px; cursor:pointer;">&plus;</span>
                                                </div>
                                                <span id="ads-mockup-remove" style="font-size:11.5px; color:#9c7a56; text-decoration:underline; margin-top:5px; cursor:pointer;">Remove</span>
                                            </div>
                                        </div>

                                        <!-- Mockup Item 2 -->
                                        <div style="display:flex; justify-content:space-between; align-items:flex-start; padding:12px 0;">
                                            <div style="flex:1;">
                                                <h4 id="ads-mockup-item-title-2" style="margin:0 0 2px 0; font-size:15px; font-weight:800; color:#00473e;">Widow Family Sponsorship</h4>
                                                <div id="ads-mockup-sub-2" style="font-size:13px; font-weight:700; color:#008254; margin-bottom:3px;">Winter Zakat or Sadaqah</div>
                                                <div style="font-size:11.5px; color:#64748b; margin-bottom:4px;">Monthly Payment, Sadaqah</div>
                                                <div id="ads-mockup-price-2" style="font-size:15px; font-weight:800; color:#00473e;">£70.00</div>
                                            </div>
                                            <div style="display:flex; flex-direction:column; align-items:flex-end;">
                                                <div style="display:inline-flex; align-items:center; border:1px solid #D5CEBF; border-radius:4px; background:#ffffff; height:22px; padding:0 4px;">
                                                    <span style="font-size:13px; font-weight:700; color:#00473e; padding:0 4px;">&minus;</span>
                                                    <span style="font-size:12px; font-weight:700; color:#00473e; min-width:16px; text-align:center;">1</span>
                                                    <span style="font-size:13px; font-weight:700; color:#00473e; padding:0 4px;">&plus;</span>
                                                </div>
                                                <span style="font-size:11.5px; color:#9c7a56; text-decoration:underline; margin-top:5px;">Remove</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Mockup Totals & CTA Footer -->
                                    <div style="padding:16px 20px; border-top:1px solid #EAE4D9; background:#FAF8F5;" id="ads-mockup-footer">
                                        <div style="margin-bottom:12px;">
                                            <div id="ads-mockup-totals-title" style="font-size:15px; font-weight:800; color:#00473e; margin-bottom:6px;">Total Amount</div>
                                            <div style="display:flex; justify-content:space-between; font-size:13px; color:#475569; margin-bottom:4px;">
                                                <span id="ads-mockup-lbl-single">Single Payment</span>
                                                <span>£120.00</span>
                                            </div>
                                            <div style="display:flex; justify-content:space-between; font-size:13px; color:#475569; margin-bottom:6px;">
                                                <span id="ads-mockup-lbl-monthly">Monthly Payment</span>
                                                <span>£70.00 / month</span>
                                            </div>
                                            <div style="display:flex; justify-content:space-between; font-size:16px; font-weight:800; color:#00473e; padding-top:6px; border-top:1px dashed #D5CEBF;">
                                                <span id="ads-mockup-lbl-due">Total due today</span>
                                                <span id="ads-mockup-val-due">£190.00</span>
                                            </div>
                                        </div>
                                        <button type="button" id="ads-mockup-btn" style="width:100%; height:46px; border:none; border-radius:8px; background:#007a50; color:#ffffff; font-size:15.5px; font-weight:800; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;">
                                            <span id="ads-mockup-btn-text">Proceed To Checkout</span> &rarr;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="submit" style="margin-top:24px; border-top:1px solid #E2E8F0; padding-top:16px;">
                        <input type="hidden" name="ads_donation_drawer_save" value="1">
                        <button type="submit" name="active_tab" value="donation_drawer" class="button button-primary button-large" style="background:#038B5C; border-color:#026E49; font-weight:700; padding:4px 24px; font-size:15px;">
                            Save Slide-Out Drawer Settings
                        </button>
                    </p>
                </div>
            </div>
        </form>
    </div>

    <!-- Live Preview Script -->
    <script>
    (function($){
        $(document).ready(function(){
            // Live Preview Sync Function
            function updateDrawerPreview() {
                var bg = $('#ads_drawer_bg_color').val();
                var hdr = $('#ads_drawer_header_color').val();
                var sub = $('#ads_drawer_subtitle_color').val();
                var itemTitle = $('#ads_drawer_item_title_color').val();
                var itemPrice = $('#ads_drawer_item_price_color').val();
                var btnBg = $('#ads_drawer_btn_bg').val();
                var btnColor = $('#ads_drawer_btn_color').val();
                var btnRadius = $('#ads_drawer_btn_border_radius').val() || '8px';
                var stepperColor = $('#ads_drawer_stepper_color').val();
                var stepperBg = $('#ads_drawer_stepper_bg').val();
                var stepperBorder = $('#ads_drawer_stepper_border_color').val();
                var remColor = $('#ads_drawer_remove_color').val();
                
                var titleText = $('#ads_drawer_title').val() || 'Your donations';
                var btnText = $('#ads_drawer_checkout_text').val() || 'Proceed To Checkout';
                var remText = $('#ads_drawer_remove_text').val() || 'Remove';
                var totalsTitle = $('#ads_drawer_totals_title').val() || 'Total Amount';
                var singleLbl = $('#ads_drawer_single_payment_label').val() || 'Single Payment';
                var monthlyLbl = $('#ads_drawer_monthly_payment_label').val() || 'Monthly Payment';
                var dueLbl = $('#ads_drawer_total_due_label').val() || 'Total due today';

                // Sync Texts
                $('#ads-mockup-title').text(titleText).css('color', hdr);
                $('#ads-mockup-btn-text').text(btnText);
                $('#ads-mockup-remove').text(remText).css('color', remColor);
                $('#ads-mockup-totals-title').text(totalsTitle).css('color', hdr);
                $('#ads-mockup-lbl-single').text(singleLbl);
                $('#ads-mockup-lbl-monthly').text(monthlyLbl);
                $('#ads-mockup-lbl-due').text(dueLbl);
                $('#ads-mockup-lbl-due').parent().css('color', hdr);

                // Sync Colors & Box
                $('#ads-mockup-panel').css('background', bg);
                $('#ads-mockup-footer').css('background', bg);
                $('#ads-mockup-item-title-1, #ads-mockup-item-title-2').css('color', itemTitle);
                $('#ads-mockup-sub-1, #ads-mockup-sub-2').css('color', sub);
                $('#ads-mockup-price-1, #ads-mockup-price-2').css('color', itemPrice);
                $('#ads-mockup-btn').css({
                    'background': btnBg,
                    'color': btnColor,
                    'border-radius': btnRadius
                });

                // Stepper styling
                $('#ads-mockup-stepper').css({
                    'background': stepperBg,
                    'border-color': stepperBorder
                });
                $('#ads-mockup-stepper span').css('color', stepperColor);

                // Update text color code indicators
                $('#lbl_bg_color').text(bg);
                $('#lbl_header_color').text(hdr);
                $('#lbl_item_title_color').text(itemTitle);
                $('#lbl_subtitle_color').text(sub);
                $('#lbl_item_price_color').text(itemPrice);
                $('#lbl_stepper_color').text(stepperColor);
                $('#lbl_stepper_bg').text(stepperBg);
                $('#lbl_stepper_border_color').text(stepperBorder);
                $('#lbl_remove_color').text(remColor);
                $('#lbl_btn_bg').text(btnBg);
                $('#lbl_btn_hover_bg').text($('#ads_drawer_btn_hover_bg').val());
                $('#lbl_btn_color').text(btnColor);
                $('#lbl_launcher_bg').text($('#ads_drawer_launcher_bg').val());
                $('#lbl_launcher_badge_bg').text($('#ads_drawer_launcher_badge_bg').val());
            }

            // Sync Color Picker <-> Hex Text Input
            $('.ads-color-picker').on('input change', function() {
                var val = $(this).val();
                $(this).closest('td').find('.ads-color-hex').val(val);
                updateDrawerPreview();
            });

            $('.ads-color-hex').on('input change', function() {
                var val = $(this).val().trim();
                if (val && val[0] !== '#') val = '#' + val;
                if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                    $(this).closest('td').find('.ads-color-picker').val(val);
                    updateDrawerPreview();
                }
            });

            // Bind change and input events
            $('#tab-panel-donation_drawer input, #tab-panel-donation_drawer select').on('input change', updateDrawerPreview);
            updateDrawerPreview();

            // Device Switcher logic
            $('#ads-preview-device-switcher .ads-preview-tab').on('click', function(e){
                e.preventDefault();
                $('#ads-preview-device-switcher .ads-preview-tab').removeClass('ads-preview-active').css({
                    'background': 'transparent',
                    'color': '#475569',
                    'box-shadow': 'none'
                });
                $(this).addClass('ads-preview-active').css({
                    'background': '#ffffff',
                    'color': '#0F172A',
                    'box-shadow': '0 1px 3px rgba(0,0,0,0.1)'
                });

                var dev = $(this).data('device');
                var $frame = $('#ads-preview-screen-frame');
                var $panel = $('#ads-mockup-panel');
                var pos = $('#ads_drawer_panel_position').val() || 'right';

                if (pos === 'left') {
                    $panel.css({ 'left': '0', 'right': 'auto', 'box-shadow': '10px 0 35px rgba(0,0,0,0.22)' });
                } else {
                    $panel.css({ 'right': '0', 'left': 'auto', 'box-shadow': '-10px 0 35px rgba(0,0,0,0.22)' });
                }

                if (dev === 'desktop') {
                    $frame.css('max-width', '850px');
                    var deskW = $('#ads_drawer_desktop_width').val() || '430px';
                    $panel.css('width', deskW);
                } else if (dev === 'tablet') {
                    $frame.css('max-width', '620px');
                    var tabW = $('#ads_drawer_tablet_width').val() || '50vw';
                    $panel.css('width', '50%');
                } else if (dev === 'mobile') {
                    $frame.css('max-width', '375px');
                    // On mobile, user specified 70vw
                    var mobW = $('#ads_drawer_mobile_width').val() || '70vw';
                    $panel.css('width', '70%');
                }
            });
        });
    })(jQuery);
    </script>
    document.addEventListener('DOMContentLoaded', function () {
        var tabs = document.querySelectorAll('.aryana-nav-tab');
        var panels = document.querySelectorAll('.aryana-tab-panel');
        var hiddenActiveTab = document.getElementById('aryana_active_tab');

        function switchTab(targetTab) {
            var found = false;
            panels.forEach(function (p) {
                if (p.getAttribute('data-tab') === targetTab) {
                    p.style.display = 'block';
                    p.classList.add('aryana-panel-active');
                    found = true;
                } else {
                    p.style.display = 'none';
                    p.classList.remove('aryana-panel-active');
                }
            });

            if (!found && panels.length > 0) {
                panels[0].style.display = 'block';
                panels[0].classList.add('aryana-panel-active');
                targetTab = panels[0].getAttribute('data-tab');
            }

            tabs.forEach(function (t) {
                if (t.getAttribute('data-tab') === targetTab) {
                    t.classList.add('nav-tab-active');
                } else {
                    t.classList.remove('nav-tab-active');
                }
            });

            if (hiddenActiveTab) {
                hiddenActiveTab.value = targetTab;
            }

            if (window.history && window.history.replaceState) {
                var url = new URL(window.location.href);
                url.searchParams.set('tab', targetTab);
                window.history.replaceState(null, '', url.toString());
            }
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                var tabId = this.getAttribute('data-tab');
                switchTab(tabId);
            });
        });

        // Check URL search or hash
        var urlParams = new URLSearchParams(window.location.search);
        var urlTab = urlParams.get('tab');
        if (urlTab && document.getElementById('tab-panel-' + urlTab)) {
            switchTab(urlTab);
        } else if (window.location.hash && window.location.hash.startsWith('#tab-')) {
            var hashTab = window.location.hash.replace('#tab-', '');
            if (document.getElementById('tab-panel-' + hashTab)) {
                switchTab(hashTab);
            }
        }
    });
    </script>
    <?php
}
