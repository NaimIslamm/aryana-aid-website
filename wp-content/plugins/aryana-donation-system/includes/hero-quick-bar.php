<?php
/**
 * Aryana Aid Donation System - Hero Carousel Quick Donate Bar
 *
 * Features:
 * - Bespoke, high-converting interactive dropdown menus matching Aryana Aid's layout & styling
 * - Opens UPWARDS above the bar to avoid carousel overflow clipping and ensure 100% visibility
 * - Dynamic inclusion of ALL active projects from the Dashboard:
 *   automatically syncs with ads_projects_matrix() and apg_projects (Project Gallery)
 * - Live instant search filter inside project dropdown with auto-focus and clear button
 * - Suggested donation amount badges (£100 / £25/mo) in site mint green badge styling
 * - Giving Frequency (Single / Regular) and Donation Type (Sadaqah / Zakat / Lillah) custom menus
 * - Full styling controls connected to Dashboard settings (wp-admin -> Donations -> Quick Donate Widget)
 * - Clean horizontal bar without top labels row or cart button
 * - Quick Donate CTA styled in site's primary brand green color (#038b5c)
 * - Swiper isolation (swiper-no-swiping & event stopping) to prevent carousel dragging on interaction
 * - Autoplay auto-pause when interacting or when any dropdown is open
 * - Slide-out donation drawer integration with window.adsDrawer.addItem()
 * - Strictly hidden on mobile (<= 767px), visible on Desktop & Tablet
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. Dynamically retrieve all active projects from WordPress Dashboard
 */
function ads_get_hero_quick_bar_all_projects() {
    // 1. Get master matrix projects (Donations -> Projects & Causes)
    $matrix = function_exists('ads_get_projects_matrix') ? ads_get_projects_matrix() : [];
    if (empty($matrix) && function_exists('ads_get_default_projects_matrix')) {
        $matrix = ads_get_default_projects_matrix();
    }

    // 2. Check Aryana Project Gallery (Project Gallery in admin dashboard)
    $apg_projects = get_option('apg_projects', []);
    if (!empty($apg_projects) && is_array($apg_projects)) {
        foreach ($apg_projects as $item) {
            $title = trim($item['title'] ?? '');
            $status = $item['status'] ?? 'active';
            if (empty($title) || $status === 'inactive') {
                continue;
            }

            // Check if already in matrix (case-insensitive)
            $found = false;
            foreach ($matrix as $k => $v) {
                if (strcasecmp($k, $title) === 0) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $matrix[$title] = [
                    'locations' => ['Afghanistan', 'Pakistan', 'Where Most Needed'],
                    'types' => ['Sadaqah', 'Zakat', 'Lillah', 'General'],
                    'fixed_single' => 50,
                    'fixed_monthly' => 20,
                    'description' => $item['subtitle'] ?? '',
                ];
            }
        }
    }

    // 3. Filter by enabled projects if configured, but respect auto-include for newly added projects
    $auto_include_new = get_option('ads_qd_auto_include_new', 'yes');
    $enabled = get_option('ads_qd_enabled_projects', []);

    if ($auto_include_new !== 'yes' && !empty($enabled) && is_array($enabled)) {
        $filtered = [];
        foreach ($matrix as $p_name => $p_data) {
            if (in_array($p_name, $enabled, true)) {
                $filtered[$p_name] = $p_data;
            }
        }
        if (!empty($filtered)) {
            return $filtered;
        }
    }

    return $matrix;
}

/**
 * 2. Get Dynamic Styles configured from WordPress Dashboard
 */
function ads_get_hero_quick_bar_styles() {
    $card_bg          = get_option('ads_qd_card_bg', '#ffffff');
    $card_border      = get_option('ads_qd_card_border', '#e2e8f0');
    $card_radius      = get_option('ads_qd_card_radius', '12px');
    $card_shadow_type = get_option('ads_qd_card_shadow', 'elevated');
    $max_width        = get_option('ads_qd_max_width', '1100px');

    if ($card_shadow_type === 'flat') {
        $card_shadow = 'none';
    } elseif ($card_shadow_type === 'subtle') {
        $card_shadow = '0 4px 16px rgba(15, 23, 42, 0.08)';
    } else {
        $card_shadow = '0 14px 45px rgba(0, 0, 0, 0.14), 0 4px 14px rgba(0, 0, 0, 0.05)';
    }

    $input_bg         = get_option('ads_qd_input_bg', '#ffffff');
    $input_border     = get_option('ads_qd_input_border', '#cbd5e1');
    $input_focus      = get_option('ads_qd_input_focus_border', '#038B5C');
    $input_radius     = get_option('ads_qd_input_radius', '8px');
    $input_color      = get_option('ads_qd_input_color', '#0f172a');
    $icon_color       = get_option('ads_qd_icon_color', '#038B5C');

    $dropdown_bg      = get_option('ads_qd_dropdown_bg', '#ffffff');
    $dropdown_border  = get_option('ads_qd_dropdown_border', '#e2e8f0');
    $dropdown_radius  = get_option('ads_qd_dropdown_radius', '12px');
    $dropdown_text    = get_option('ads_qd_dropdown_text_color', '#0f172a');
    $dropdown_hover_bg = get_option('ads_qd_dropdown_hover_bg', '#ebf8f2');
    $dropdown_hover_text = get_option('ads_qd_dropdown_hover_text', '#038B5C');
    $dropdown_badge_bg = get_option('ads_qd_dropdown_badge_bg', '#dcfce7');
    $dropdown_badge_color = get_option('ads_qd_dropdown_badge_color', '#166534');
    $dropdown_search_bg = get_option('ads_qd_dropdown_search_bg', '#f8fafc');

    $btn_bg           = get_option('ads_qd_btn_bg', '#038B5C');
    $btn_hover_bg     = get_option('ads_qd_btn_hover_bg', '#026E49');
    $btn_color        = get_option('ads_qd_btn_color', '#ffffff');
    $btn_hover_color  = get_option('ads_qd_btn_hover_color', '#ffffff');
    $btn_radius       = get_option('ads_qd_btn_radius', '8px');
    $btn_text         = get_option('ads_qd_btn_text', 'Quick Donate');

    $custom_css       = get_option('ads_qd_custom_css', '');

    return [
        'card_bg'             => $card_bg,
        'card_border'         => $card_border,
        'card_radius'         => $card_radius,
        'card_shadow'         => $card_shadow,
        'max_width'           => $max_width,
        'input_bg'            => $input_bg,
        'input_border'        => $input_border,
        'input_focus'         => $input_focus,
        'input_radius'        => $input_radius,
        'input_color'         => $input_color,
        'icon_color'          => $icon_color,
        'dropdown_bg'         => $dropdown_bg,
        'dropdown_border'     => $dropdown_border,
        'dropdown_radius'     => $dropdown_radius,
        'dropdown_text'       => $dropdown_text,
        'dropdown_hover_bg'   => $dropdown_hover_bg,
        'dropdown_hover_text' => $dropdown_hover_text,
        'dropdown_badge_bg'   => $dropdown_badge_bg,
        'dropdown_badge_color'=> $dropdown_badge_color,
        'dropdown_search_bg'  => $dropdown_search_bg,
        'btn_bg'              => $btn_bg,
        'btn_hover_bg'        => $btn_hover_bg,
        'btn_color'           => $btn_color,
        'btn_hover_color'     => $btn_hover_color,
        'btn_radius'          => $btn_radius,
        'btn_text'            => $btn_text,
        'custom_css'          => $custom_css,
    ];
}

/**
 * 3. Render Hero Quick Donate Bar Shortcode: [ads_hero_quick_bar]
 */
function ads_render_hero_quick_bar($atts = []) {
    $matrix = ads_get_hero_quick_bar_all_projects();
    $currency = get_option('ads_currency_symbol', '£');
    $styles = ads_get_hero_quick_bar_styles();

    $atts = shortcode_atts([
        'project'   => 'Winter Appeal',
        'frequency' => 'single',
        'amount'    => '',
        'type'      => 'Sadaqah',
        'cta_text'  => $styles['btn_text'],
    ], $atts, 'ads_hero_quick_bar');

    // Find best matching project from all available projects
    $selected_project = $atts['project'];
    if (!isset($matrix[$selected_project])) {
        foreach ($matrix as $p_name => $p_data) {
            if (stripos($p_name, $selected_project) !== false || stripos($selected_project, $p_name) !== false) {
                $selected_project = $p_name;
                break;
            }
        }
    }
    if (!isset($matrix[$selected_project])) {
        $selected_project = array_keys($matrix)[0] ?? 'Water - Well';
    }

    $curr_data = $matrix[$selected_project] ?? [];
    $init_freq = strtolower($atts['frequency']) === 'monthly' ? 'monthly' : 'single';
    $init_single = floatval($curr_data['fixed_single'] ?? 100);
    $init_monthly = floatval($curr_data['fixed_monthly'] ?? 25);
    
    // Determine suggested amount
    if (!empty($atts['amount'])) {
        $init_amount = floatval($atts['amount']);
    } else {
        if ($init_freq === 'monthly') {
            $init_amount = $init_monthly > 0 ? $init_monthly : 25;
        } else {
            $init_amount = $init_single > 0 ? $init_single : 100;
        }
    }

    // Supported donation types for currently selected project
    $supported_types = !empty($curr_data['types']) ? $curr_data['types'] : ['Sadaqah', 'Zakat', 'Lillah', 'General'];
    $init_type = in_array($atts['type'], $supported_types) ? $atts['type'] : ($supported_types[0] ?? 'Sadaqah');
    $init_types_csv = implode(',', $supported_types);

    ob_start();
    ?>
    <div class="aa-hero-quick-bar-wrapper swiper-no-swiping" data-hero-bar="1">
        <div class="aa-hero-quick-bar floating-quick-bar swiper-no-swiping">
            
            <!-- 1. SELECT PROJECT / APPEAL (Bespoke Interactive Dropdown) -->
            <div class="aa-bar-col aa-bar-col-project aa-unique-dropdown-wrap" data-dropdown="project">
                <input type="hidden" class="aa-quick-select-project" value="<?php echo esc_attr($selected_project); ?>" 
                       data-single="<?php echo esc_attr($init_single); ?>" 
                       data-monthly="<?php echo esc_attr($init_monthly); ?>" 
                       data-types="<?php echo esc_attr($init_types_csv); ?>">
                
                <button type="button" class="aa-bar-trigger-btn aa-dropdown-trigger" aria-haspopup="listbox" aria-expanded="false">
                    <span class="aa-bar-icon-left">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                            <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
                        </svg>
                    </span>
                    <span class="aa-bar-label-text aa-current-project-title"><?php echo esc_html($selected_project); ?></span>
                    <span class="aa-bar-icon-chevron">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
                    </span>
                </button>

                <!-- BESPOKE FLOATING MENU CARD (Opens UPWARDS into hero image) -->
                <div class="aa-unique-dropdown-menu aa-dropdown-menu-project aa-drop-up" role="listbox">
                    <div class="aa-dropdown-search-wrap">
                        <span class="aa-search-icon">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </span>
                        <input type="text" class="aa-dropdown-search-input" placeholder="Search appeal / project..." autocomplete="off">
                        <button type="button" class="aa-search-clear" style="display:none;" title="Clear search">&times;</button>
                    </div>
                    <div class="aa-dropdown-list-wrap">
                        <div class="aa-dropdown-list">
                            <?php foreach ($matrix as $p_name => $p_data): 
                                $s_amt = $p_data['fixed_single'] ?? 0;
                                $m_amt = $p_data['fixed_monthly'] ?? 0;
                                $types_csv = !empty($p_data['types']) ? implode(',', $p_data['types']) : 'Sadaqah,Zakat,Lillah';
                                $is_sel = ($p_name === $selected_project);
                            ?>
                                <div class="aa-dropdown-item <?php echo $is_sel ? 'aa-selected' : ''; ?>" 
                                     data-value="<?php echo esc_attr($p_name); ?>"
                                     data-single="<?php echo esc_attr($s_amt); ?>"
                                     data-monthly="<?php echo esc_attr($m_amt); ?>"
                                     data-types="<?php echo esc_attr($types_csv); ?>">
                                    <span class="aa-item-bullet"></span>
                                    <span class="aa-item-title"><?php echo esc_html($p_name); ?></span>
                                    <span class="aa-item-check">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="aa-dropdown-empty" style="display:none;">No matching appeals found</div>
                    </div>
                </div>
            </div>

            <!-- 2. GIVING FREQUENCY (Bespoke Interactive Dropdown) -->
            <div class="aa-bar-col aa-bar-col-freq aa-unique-dropdown-wrap" data-dropdown="freq">
                <input type="hidden" class="aa-quick-select-freq" value="<?php echo esc_attr($init_freq); ?>">
                
                <button type="button" class="aa-bar-trigger-btn aa-dropdown-trigger" aria-haspopup="listbox" aria-expanded="false">
                    <span class="aa-bar-icon-left">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19a2 2 0 0 0 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
                    </span>
                    <span class="aa-bar-label-text aa-current-freq-title"><?php echo $init_freq === 'monthly' ? 'Regular Payment' : 'Single Payment'; ?></span>
                    <span class="aa-bar-icon-chevron">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
                    </span>
                </button>

                <div class="aa-unique-dropdown-menu aa-dropdown-menu-freq aa-drop-up" role="listbox">
                    <div class="aa-dropdown-list">
                        <div class="aa-dropdown-item <?php echo $init_freq === 'single' ? 'aa-selected' : ''; ?>" data-value="single">
                            <span class="aa-item-freq-icon">⚡</span>
                            <div class="aa-item-info">
                                <span class="aa-item-title">Single Payment</span>
                                <span class="aa-item-sub">One-off donation</span>
                            </div>
                            <span class="aa-item-check"><svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></span>
                        </div>
                        <div class="aa-dropdown-item <?php echo $init_freq === 'monthly' ? 'aa-selected' : ''; ?>" data-value="monthly">
                            <span class="aa-item-freq-icon">🔄</span>
                            <div class="aa-item-info">
                                <span class="aa-item-title">Regular Payment</span>
                                <span class="aa-item-sub">Monthly ongoing gift</span>
                            </div>
                            <span class="aa-item-check"><svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. AMOUNT (£) INPUT -->
            <div class="aa-bar-col aa-bar-col-amount aa-bar-amount-box">
                <span class="aa-bar-currency-prefix"><?php echo esc_html($currency); ?></span>
                <input type="number" step="any" min="1" class="aa-bar-input aa-quick-input-amount" 
                       value="<?php echo esc_attr($init_amount); ?>" placeholder="Amount" aria-label="Donation Amount">
            </div>

            <!-- 4. DONATION TYPE (Bespoke Interactive Dropdown) -->
            <div class="aa-bar-col aa-bar-col-type aa-unique-dropdown-wrap" data-dropdown="type">
                <input type="hidden" class="aa-quick-select-type" value="<?php echo esc_attr($init_type); ?>">
                
                <button type="button" class="aa-bar-trigger-btn aa-dropdown-trigger" aria-haspopup="listbox" aria-expanded="false">
                    <span class="aa-bar-icon-left">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                    </span>
                    <span class="aa-bar-label-text aa-current-type-title"><?php echo esc_html($init_type); ?></span>
                    <span class="aa-bar-icon-chevron">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
                    </span>
                </button>

                <div class="aa-unique-dropdown-menu aa-dropdown-menu-type aa-drop-up" role="listbox">
                    <div class="aa-dropdown-list">
                        <?php foreach ($supported_types as $t): 
                            $is_t_sel = ($t === $init_type);
                        ?>
                            <div class="aa-dropdown-item <?php echo $is_t_sel ? 'aa-selected' : ''; ?>" data-value="<?php echo esc_attr($t); ?>">
                                <span class="aa-item-bullet"></span>
                                <span class="aa-item-title"><?php echo esc_html($t); ?></span>
                                <span class="aa-item-check"><svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 5. Quick Donate Button (Brand Green & Customizable from Dashboard) -->
            <button type="button" class="aa-bar-cta-donate quick-pill-donate">
                <span><?php echo esc_html($atts['cta_text']); ?></span>
                <span class="aa-bar-btn-drop">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                    </svg>
                </span>
            </button>

        </div>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('ads_hero_quick_bar', 'ads_render_hero_quick_bar');
add_shortcode('hero_quick_bar', 'ads_render_hero_quick_bar');

/**
 * 4. Enqueue Dynamic CSS for Quick Donate Bar & Unique Bespoke Dropdowns
 */
add_action('wp_enqueue_scripts', function () {
    $s = ads_get_hero_quick_bar_styles();

    $css = "
    /* ==========================================================================
       HERO CAROUSEL QUICK DONATE BAR - DYNAMIC STYLING & BESPOKE DROPDOWNS
       Typography: Albert Sans (Headings/Buttons/Badges) & Outfit (Body/Inputs)
       ========================================================================== */
    .aa-hero-quick-widget,
    .elementor-widget-shortcode:has(.aa-hero-quick-bar-wrapper) {
        overflow: visible !important;
        z-index: 60 !important;
    }

    .aa-hero-quick-bar-wrapper {
        position: relative;
        width: 100%;
        max-width: {$s['max_width']};
        margin: 0 auto;
        padding: 0 16px;
        box-sizing: border-box;
        z-index: 100;
        pointer-events: auto;
        user-select: auto !important;
    }

    .aa-hero-quick-bar.floating-quick-bar {
        background: {$s['card_bg']} !important;
        border-radius: {$s['card_radius']} !important;
        box-shadow: {$s['card_shadow']} !important;
        padding: 10px 14px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 10px !important;
        border: 1px solid {$s['card_border']} !important;
        box-sizing: border-box !important;
        width: 100% !important;
        position: relative;
    }

    /* Column Sizing */
    .aa-bar-col-project {
        flex: 1.8 1 210px;
    }

    .aa-bar-col-freq {
        flex: 1.2 1 140px;
    }

    .aa-bar-col-amount {
        flex: 1 1 115px;
    }

    .aa-bar-col-type {
        flex: 1.1 1 125px;
    }

    /* Input Pill Trigger & Amount Box */
    .aa-unique-dropdown-wrap {
        position: relative;
    }

    /* Trigger Buttons (Project, Frequency, Donation Type) */
    .aa-bar-trigger-btn {
        position: relative !important;
        display: flex !important;
        align-items: center !important;
        justify-content: flex-start !important;
        gap: 10px !important;
        background-color: #038b5c !important;
        color: #ffffff !important;
        border: 1.5px solid #026e49 !important;
        border-radius: {$s['input_radius']} !important;
        height: 48px !important;
        padding: 0 34px 0 14px !important;
        box-sizing: border-box !important;
        transition: all 0.2s ease !important;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08) !important;
        min-width: 0 !important;
        width: 100% !important;
        text-align: left !important;
        cursor: pointer !important;
        font-family: 'Outfit', sans-serif !important;
    }

    .aa-bar-trigger-btn:hover {
        background-color: #026e49 !important;
        border-color: #02573a !important;
    }

    .aa-unique-dropdown-wrap.aa-open .aa-bar-trigger-btn {
        background-color: #026e49 !important;
        border-color: #02573a !important;
        box-shadow: 0 0 0 3px rgba(3, 139, 92, 0.28) !important;
    }

    .aa-bar-icon-left {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 18px !important;
        height: 18px !important;
        min-width: 18px !important;
        margin: 0 !important;
        padding: 0 !important;
        color: #ffffff !important;
        flex-shrink: 0 !important;
        line-height: 1 !important;
    }

    .aa-bar-icon-left svg {
        display: block !important;
        width: 16px !important;
        height: 16px !important;
        margin: 0 !important;
        padding: 0 !important;
        fill: currentColor !important;
        vertical-align: middle !important;
    }

    .aa-bar-trigger-btn .aa-bar-label-text {
        color: #ffffff !important;
        font-weight: 700 !important;
        flex: 1 1 auto !important;
        display: inline-flex !important;
        align-items: center !important;
        font-family: 'Outfit', sans-serif !important;
        font-size: 14px !important;
        line-height: 1 !important;
        height: 100% !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .aa-bar-icon-chevron {
        position: absolute !important;
        right: 12px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 16px !important;
        height: 16px !important;
        margin: 0 !important;
        padding: 0 !important;
        color: #ffffff !important;
        pointer-events: none !important;
        transition: transform 0.25s ease !important;
    }

    .aa-unique-dropdown-wrap.aa-open .aa-bar-icon-chevron {
        transform: translateY(-50%) rotate(180deg) !important;
    }

    /* Amount Box */
    .aa-bar-amount-box {
        position: relative;
        display: flex;
        align-items: center;
        background-color: #ffffff !important;
        color: #0f172a !important;
        border: 1.5px solid #cbd5e1 !important;
        border-radius: {$s['input_radius']};
        height: 48px;
        padding: 0 14px;
        box-sizing: border-box;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        min-width: 0;
        width: 100%;
        text-align: left;
        font-family: 'Outfit', sans-serif;
    }

    .aa-bar-amount-box:hover {
        border-color: #038b5c !important;
        background-color: #f8fafc !important;
    }

    .aa-bar-amount-box:focus-within {
        border-color: #038b5c !important;
        box-shadow: 0 0 0 3px rgba(3, 139, 92, 0.18) !important;
        background-color: #ffffff !important;
    }

    /* Currency prefix & amount input */
    .aa-bar-currency-prefix {
        font-family: 'Albert Sans', sans-serif;
        font-size: 16px;
        font-weight: 800;
        color: {$s['icon_color']};
        margin-right: 4px;
        flex-shrink: 0;
    }

    .aa-bar-input {
        width: 100%;
        height: 100%;
        background: transparent !important;
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        font-family: 'Outfit', sans-serif !important;
        font-size: 15px !important;
        font-weight: 700 !important;
        color: {$s['input_color']} !important;
        padding: 0 !important;
        margin: 0 !important;
        cursor: text;
    }

    .aa-bar-input::-webkit-outer-spin-button,
    .aa-bar-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .aa-bar-input[type=number] {
        -moz-appearance: textfield;
    }

    /* ==========================================================================
       UNIQUE BESPOKE FLOATING MENU CARD (Opens UPWARDS by default into Hero)
       ========================================================================== */
    .aa-unique-dropdown-menu {
        position: absolute;
        bottom: calc(100% + 8px);
        top: auto;
        left: 0;
        min-width: 100%;
        background: {$s['dropdown_bg']};
        border: 1.5px solid {$s['dropdown_border']};
        border-radius: {$s['dropdown_radius']};
        box-shadow: 0 -14px 40px rgba(15, 23, 42, 0.18), 0 -2px 10px rgba(15, 23, 42, 0.06);
        z-index: 999999;
        box-sizing: border-box;
        overflow: hidden;
        opacity: 0;
        visibility: hidden;
        transform: translateY(8px) scale(0.98);
        pointer-events: none;
        transition: opacity 0.2s cubic-bezier(0.16, 1, 0.3, 1), transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.2s;
    }

    .aa-unique-dropdown-wrap.aa-open .aa-unique-dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
        pointer-events: auto;
    }

    /* Fallback if drop-down is needed */
    .aa-unique-dropdown-menu.aa-drop-down {
        bottom: auto !important;
        top: calc(100% + 8px) !important;
        box-shadow: 0 14px 40px rgba(15, 23, 42, 0.18), 0 2px 10px rgba(15, 23, 42, 0.06) !important;
        transform: translateY(-8px) scale(0.98) !important;
    }
    .aa-unique-dropdown-wrap.aa-open .aa-unique-dropdown-menu.aa-drop-down {
        transform: translateY(0) scale(1) !important;
    }

    /* Menu Width Sizing */
    .aa-dropdown-menu-project {
        min-width: 320px;
        max-width: 420px;
    }
    .aa-dropdown-menu-freq {
        min-width: 210px;
    }
    .aa-dropdown-menu-type {
        min-width: 180px;
    }

    /* Smart Search Filter Bar */
    .aa-dropdown-search-wrap {
        position: relative;
        padding: 10px 14px;
        background: {$s['dropdown_search_bg']};
        border-bottom: 1px solid {$s['dropdown_border']};
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .aa-search-icon {
        color: #038b5c;
        display: inline-flex;
        align-items: center;
        flex-shrink: 0;
    }

    .aa-dropdown-search-input {
        width: 100%;
        background: #ffffff !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px !important;
        outline: none !important;
        font-family: 'Outfit', sans-serif !important;
        font-size: 13.5px !important;
        color: #0f172a !important;
        padding: 6px 10px !important;
        box-sizing: border-box !important;
        transition: border-color 0.2s ease !important;
    }
    .aa-dropdown-search-input:focus {
        border-color: #038b5c !important;
        box-shadow: 0 0 0 2px rgba(3, 139, 92, 0.15) !important;
    }
    .aa-dropdown-search-input::placeholder {
        color: #94a3b8;
    }

    .aa-search-clear {
        background: transparent;
        border: none;
        color: #94a3b8;
        font-size: 18px;
        line-height: 1;
        cursor: pointer;
        padding: 0 4px;
        transition: color 0.15s ease;
    }
    .aa-search-clear:hover {
        color: #0f172a;
    }

    /* Scrollable List Container */
    .aa-dropdown-list-wrap {
        position: relative;
    }

    .aa-dropdown-list {
        max-height: 290px;
        overflow-y: auto;
        padding: 6px;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }

    .aa-dropdown-list::-webkit-scrollbar {
        width: 5px;
    }
    .aa-dropdown-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    /* Item Rows (Site Layout) */
    .aa-dropdown-item {
        display: flex;
        align-items: center;
        padding: 10px 14px;
        margin: 2px 4px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.15s ease;
        gap: 10px;
        user-select: none;
    }

    .aa-dropdown-item:hover {
        background-color: {$s['dropdown_hover_bg']};
        color: {$s['dropdown_hover_text']};
        transform: translateX(3px);
    }

    .aa-dropdown-item.aa-selected {
        background-color: {$s['dropdown_hover_bg']};
        color: {$s['dropdown_hover_text']};
        font-weight: 700;
    }

    .aa-item-bullet {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background-color: #cbd5e1;
        flex-shrink: 0;
        transition: background-color 0.15s ease;
    }

    .aa-dropdown-item:hover .aa-item-bullet,
    .aa-dropdown-item.aa-selected .aa-item-bullet {
        background-color: {$s['dropdown_hover_text']};
    }

    .aa-item-title {
        flex: 1;
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        font-weight: 600;
        color: {$s['dropdown_text']};
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .aa-dropdown-item:hover .aa-item-title,
    .aa-dropdown-item.aa-selected .aa-item-title {
        color: {$s['dropdown_hover_text']};
    }

    .aa-item-badge {
        display: none !important;
    }

    .aa-item-check {
        color: {$s['dropdown_hover_text']};
        display: inline-flex;
        align-items: center;
        opacity: 0;
        transform: scale(0.6);
        transition: all 0.15s ease;
        flex-shrink: 0;
    }

    .aa-dropdown-item.aa-selected .aa-item-check {
        opacity: 1;
        transform: scale(1);
    }

    /* Frequency Item Details */
    .aa-item-freq-icon {
        font-size: 16px;
        flex-shrink: 0;
    }
    .aa-item-info {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .aa-item-sub {
        font-family: 'Outfit', sans-serif;
        font-size: 11.5px;
        color: #64748b;
        font-weight: 400;
        margin-top: 1px;
    }

    .aa-dropdown-empty {
        padding: 22px 14px;
        text-align: center;
        font-family: 'Outfit', sans-serif;
        font-size: 13.5px;
        color: #64748b;
    }

    /* ==========================================================================
       QUICK DONATE BUTTON (Site Primary Brand Green #038B5C)
       ========================================================================== */
    .aa-bar-cta-donate {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 8px !important;
        background-color: {$s['btn_bg']} !important;
        color: {$s['btn_color']} !important;
        border: none !important;
        border-radius: {$s['btn_radius']} !important;
        height: 48px !important;
        padding: 0 26px !important;
        font-family: 'Albert Sans', sans-serif !important;
        font-size: 15px !important;
        font-weight: 700 !important;
        letter-spacing: 0.3px !important;
        cursor: pointer !important;
        white-space: nowrap !important;
        flex-shrink: 0 !important;
        box-shadow: 0 4px 15px rgba(3, 139, 92, 0.32) !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .aa-bar-cta-donate:hover {
        background-color: {$s['btn_hover_bg']} !important;
        color: {$s['btn_hover_color']} !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 6px 20px rgba(3, 139, 92, 0.45) !important;
    }

    .aa-bar-btn-drop {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: inherit;
    }

    /* Custom CSS Overrides from Dashboard */
    {$s['custom_css']}

    /* ==========================================================================
       RESPONSIVE RULES:
       Desktop & Tablet ONLY (> 767px)
       Strictly HIDDEN on Mobile (<= 767px)
       ========================================================================== */
    @media (max-width: 767px) {
        .aa-hero-quick-bar-wrapper {
            display: none !important;
        }
    }

    /* Tablet Optimizations (768px - 1024px) */
    @media (min-width: 768px) and (max-width: 1024px) {
        .aa-hero-quick-bar.floating-quick-bar {
            padding: 8px 10px !important;
            gap: 6px !important;
        }
        .aa-bar-trigger-btn {
            height: 44px !important;
            padding: 0 28px 0 10px !important;
            gap: 8px !important;
        }
        .aa-bar-amount-box {
            height: 44px !important;
            padding: 0 10px !important;
        }
        .aa-bar-label-text, .aa-bar-input {
            font-size: 13px !important;
        }
        .aa-bar-cta-donate {
            height: 44px !important;
            padding: 0 18px !important;
            font-size: 14px !important;
        }
    }
    ";

    wp_add_inline_style('ads-modern-donation-css', $css);
}, 40);

/**
 * 5. JavaScript Interaction Engine for Bespoke Dropdowns & Swiper Protection
 */
add_action('wp_footer', function () {
    ?>
    <script id="aa-hero-quick-bar-script">
    (function($) {
        'use strict';

        $(document).ready(function() {
            // Helper: Pause Swiper autoplay while user is interacting
            function pauseSwiper($bar) {
                var swiperInstance = $bar.closest('.swiper')[0]?.swiper;
                if (swiperInstance && swiperInstance.autoplay) {
                    swiperInstance.autoplay.stop();
                }
            }

            // Helper: Resume Swiper autoplay when all menus close
            function resumeSwiper($bar) {
                if ($('.aa-unique-dropdown-wrap.aa-open').length > 0) return;
                var swiperInstance = $bar.closest('.swiper')[0]?.swiper;
                if (swiperInstance && swiperInstance.autoplay) {
                    swiperInstance.autoplay.start();
                }
            }

            // Helper: Close all open dropdowns
            function closeAllDropdowns() {
                var $openMenus = $('.aa-unique-dropdown-wrap.aa-open');
                if ($openMenus.length) {
                    var $bar = $openMenus.first().closest('.aa-hero-quick-bar');
                    $openMenus.removeClass('aa-open');
                    $('.aa-bar-trigger-btn').attr('aria-expanded', 'false');
                    resumeSwiper($bar);
                }
            }

            // 1. Isolate bar & dropdowns from Swiper touch/drag events
            $(document).on('mousedown touchstart pointerdown wheel scroll', '.aa-hero-quick-bar-wrapper, .aa-unique-dropdown-menu', function(e) {
                e.stopPropagation();
            });

            // 2. Toggle Dropdown on Trigger Button Click
            $(document).on('click', '.aa-dropdown-trigger', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var $btn = $(this);
                var $wrap = $btn.closest('.aa-unique-dropdown-wrap');
                var $bar = $wrap.closest('.aa-hero-quick-bar');
                var wasOpen = $wrap.hasClass('aa-open');

                closeAllDropdowns();

                if (!wasOpen) {
                    $wrap.addClass('aa-open');
                    $btn.attr('aria-expanded', 'true');
                    pauseSwiper($bar);

                    // Smart Dropdown Direction: Check vertical space
                    var $menu = $wrap.find('.aa-unique-dropdown-menu');
                    if ($menu.length) {
                        var wrapOffset = $wrap.offset();
                        var scrollTop = $(window).scrollTop();
                        var spaceAbove = wrapOffset.top - scrollTop;
                        var spaceBelow = $(window).height() - (wrapOffset.top - scrollTop + $wrap.outerHeight());

                        // If not enough space above (< 300px) and plenty below, drop down
                        if (spaceAbove < 300 && spaceBelow >= 300) {
                            $menu.removeClass('aa-drop-up').addClass('aa-drop-down');
                        } else {
                            $menu.removeClass('aa-drop-down').addClass('aa-drop-up');
                        }
                    }

                    // Auto-focus search input if present
                    var $searchInput = $wrap.find('.aa-dropdown-search-input');
                    if ($searchInput.length) {
                        setTimeout(function() {
                            $searchInput.focus();
                        }, 60);
                    }
                }
            });

            // 3. Prevent clicks inside dropdown menu from closing it
            $(document).on('click', '.aa-unique-dropdown-menu', function(e) {
                e.stopPropagation();
            });

            // 4. Close when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.aa-unique-dropdown-wrap').length) {
                    closeAllDropdowns();
                }
            });

            // 5. Close on Escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' || e.keyCode === 27) {
                    closeAllDropdowns();
                }
            });

            // 6. Instant Live Search Filter in Project Dropdown
            $(document).on('input', '.aa-dropdown-search-input', function() {
                var query = $(this).val().toLowerCase().trim();
                var $menu = $(this).closest('.aa-unique-dropdown-menu');
                var $items = $menu.find('.aa-dropdown-item');
                var $clearBtn = $menu.find('.aa-search-clear');
                var visibleCount = 0;

                if (query.length > 0) {
                    $clearBtn.show();
                } else {
                    $clearBtn.hide();
                }

                $items.each(function() {
                    var text = $(this).find('.aa-item-title').text().toLowerCase();
                    if (!query || text.indexOf(query) !== -1) {
                        $(this).show();
                        visibleCount++;
                    } else {
                        $(this).hide();
                    }
                });

                var $empty = $menu.find('.aa-dropdown-empty');
                if (visibleCount === 0) {
                    $empty.show();
                } else {
                    $empty.hide();
                }
            });

            // Clear search filter
            $(document).on('click', '.aa-search-clear', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $menu = $(this).closest('.aa-unique-dropdown-menu');
                var $input = $menu.find('.aa-dropdown-search-input');
                $input.val('').trigger('input').focus();
            });

            // 7. Handle Item Selection in Project Dropdown
            $(document).on('click', '.aa-dropdown-menu-project .aa-dropdown-item', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var $item = $(this);
                var $wrap = $item.closest('.aa-unique-dropdown-wrap');
                var $bar = $wrap.closest('.aa-hero-quick-bar');
                var val = $item.data('value');
                var singleAmt = parseFloat($item.data('single')) || 0;
                var monthlyAmt = parseFloat($item.data('monthly')) || 0;
                var rawTypes = $item.data('types') || 'Sadaqah,Zakat,Lillah';

                // Update UI selection
                $wrap.find('.aa-dropdown-item').removeClass('aa-selected');
                $item.addClass('aa-selected');
                $wrap.find('.aa-current-project-title').text(val);
                $wrap.find('.aa-quick-select-project')
                    .val(val)
                    .data('single', singleAmt)
                    .data('monthly', monthlyAmt)
                    .data('types', rawTypes);

                // Update Suggested Amount based on current frequency
                var freq = $bar.find('.aa-quick-select-freq').val() || 'single';
                var newAmt = (freq === 'monthly' && monthlyAmt > 0) ? monthlyAmt : singleAmt;
                if (newAmt > 0) {
                    $bar.find('.aa-quick-input-amount').val(newAmt);
                }

                // Update Types Dropdown for this project
                var $typeWrap = $bar.find('.aa-bar-col-type');
                var $typeList = $typeWrap.find('.aa-dropdown-list');
                var currentType = $typeWrap.find('.aa-quick-select-type').val();
                $typeList.empty();

                var typesList = String(rawTypes).split(',');
                var firstType = '';
                var typeMatched = false;

                typesList.forEach(function(t) {
                    t = t.trim();
                    if (!t) return;
                    if (!firstType) firstType = t;
                    var isSel = (t === currentType);
                    if (isSel) typeMatched = true;

                    var $row = $('<div class="aa-dropdown-item' + (isSel ? ' aa-selected' : '') + '" data-value="' + t + '"></div>')
                        .append('<span class="aa-item-bullet"></span>')
                        .append('<span class="aa-item-title">' + t + '</span>')
                        .append('<span class="aa-item-check"><svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></span>');

                    $typeList.append($row);
                });

                if (!typeMatched && firstType) {
                    $typeWrap.find('.aa-quick-select-type').val(firstType);
                    $typeWrap.find('.aa-current-type-title').text(firstType);
                    $typeList.find('.aa-dropdown-item[data-value="' + firstType + '"]').addClass('aa-selected');
                }

                closeAllDropdowns();
            });

            // 8. Handle Item Selection in Frequency Dropdown
            $(document).on('click', '.aa-dropdown-menu-freq .aa-dropdown-item', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var $item = $(this);
                var $wrap = $item.closest('.aa-unique-dropdown-wrap');
                var $bar = $wrap.closest('.aa-hero-quick-bar');
                var val = $item.data('value'); // 'single' or 'monthly'
                var title = val === 'monthly' ? 'Regular Payment' : 'Single Payment';

                $wrap.find('.aa-dropdown-item').removeClass('aa-selected');
                $item.addClass('aa-selected');
                $wrap.find('.aa-current-freq-title').text(title);
                $wrap.find('.aa-quick-select-freq').val(val);

                // Update amount based on project preset
                var $projInput = $bar.find('.aa-quick-select-project');
                var singleAmt = parseFloat($projInput.data('single')) || 0;
                var monthlyAmt = parseFloat($projInput.data('monthly')) || 0;

                if (val === 'monthly' && monthlyAmt > 0) {
                    $bar.find('.aa-quick-input-amount').val(monthlyAmt);
                } else if (val === 'single' && singleAmt > 0) {
                    $bar.find('.aa-quick-input-amount').val(singleAmt);
                }

                closeAllDropdowns();
            });

            // 9. Handle Item Selection in Donation Type Dropdown
            $(document).on('click', '.aa-dropdown-menu-type .aa-dropdown-item', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var $item = $(this);
                var $wrap = $item.closest('.aa-unique-dropdown-wrap');
                var val = $item.data('value');

                $wrap.find('.aa-dropdown-item').removeClass('aa-selected');
                $item.addClass('aa-selected');
                $wrap.find('.aa-current-type-title').text(val);
                $wrap.find('.aa-quick-select-type').val(val);

                closeAllDropdowns();
            });

            // 10. Handle Quick Donate Button Click
            $(document).on('click', '.aa-bar-cta-donate', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var $bar = $(this).closest('.aa-hero-quick-bar');
                var project = $bar.find('.aa-quick-select-project').val() || 'Winter Appeal';
                var freq = $bar.find('.aa-quick-select-freq').val() || 'single';
                var rawAmt = $bar.find('.aa-quick-input-amount').val();
                var amount = parseFloat(rawAmt) || 0;
                var type = $bar.find('.aa-quick-select-type').val() || 'Sadaqah';
                var currency = (window.adsDrawerConfig && window.adsDrawerConfig.currency) ? window.adsDrawerConfig.currency : '£';

                if (amount <= 0) {
                    $bar.find('.aa-quick-input-amount').focus();
                    alert('Please enter a valid donation amount.');
                    return;
                }

                var packageTitle = currency + amount + ' ' + project;

                // Add to Aryana Donation Drawer Basket
                if (window.adsDrawer && typeof window.adsDrawer.addItem === 'function') {
                    window.adsDrawer.addItem({
                        project: project,
                        package: packageTitle,
                        amount: amount,
                        unit_amount: amount,
                        quantity: 1,
                        type: type,
                        frequency: freq,
                        location: 'Afghanistan'
                    });
                } else {
                    window.location.href = '/donate/?project=' + encodeURIComponent(project) + '&amount=' + amount + '&freq=' + freq + '&type=' + encodeURIComponent(type);
                }
            });

            // 11. Autoplay pause on hover / focus
            $(document).on('mouseenter focusin', '.aa-hero-quick-bar', function() {
                pauseSwiper($(this));
            }).on('mouseleave focusout', '.aa-hero-quick-bar', function() {
                resumeSwiper($(this));
            });
        });
    })(jQuery);
    </script>
    <?php
}, 100);
