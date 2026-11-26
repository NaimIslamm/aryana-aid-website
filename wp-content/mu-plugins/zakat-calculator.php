<?php
/**
 * Plugin Name: Aryana Aid - Zakat Calculator (Maintainable Fields + Submissions Dashboard)
 * Description: Client's 1:1 exact Zakat calculator with Dashboard field management and complete Submissions log with CSV export.
 * Version: 2.1.0
 * Author: Antigravity
 */

if (!defined('ABSPATH')) {
    exit;
}

// 1. DEFAULT SETTINGS HELPER
function aryana_zakat_get_default_settings() {
    return [
        'nisab' => '240.05',
        'nisab_text' => 'If your total assets minus your total liabilities is more than 87.48 grams of gold (equivalent to roughly £4759.79* this year) Zakat will be obligatory on you.',
        'donate_url' => '/donate/',
        'notify_admin' => 'yes',
        'assets' => [
            'Cash at home & bank accounts',
            'Value of gold you own',
            'Value of silver you own',
            'Value of investments & shares',
            'Money owed to you',
            'Value of goods in stock for sale',
            'Other assets',
        ],
        'liabilities' => [
            'Expenses (tax, rent, bills)',
            'Short term debts',
            'Other expenses',
        ],
        'appeals' => [
            [
                'amount' => '300',
                'label'  => 'Sponsor an Orphan',
                'url'    => '/donate/?field1=Orphan%20Sponsorship&field2=Where%20Most%20Needed&field3=Sadaqah&amount=300',
                'size'   => 'col-4',
            ],
            [
                'amount' => '120',
                'label'  => 'Provide a wheelchair',
                'url'    => '/donate/?field1=Wheelchair%20-%20New&field2=Where%20Most%20Needed&field3=Sadaqah&amount=120',
                'size'   => 'col-4',
            ],
            [
                'amount' => '75',
                'label'  => 'Food package',
                'url'    => '/donate?field1=Food%20Package&field2=Afghanistan&field3=Zakat&amount=75',
                'size'   => 'col-4',
            ],
            [
                'amount' => '840',
                'label'  => 'Widow Family Sponsorship',
                'url'    => '/donate/?field1=Widow%20Family%20Sponsorship&field2=Where%20Most%20Needed&field3=Sadaqah&amount=840',
                'size'   => 'col-6',
            ],
            [
                'amount' => '600',
                'label'  => 'Give a water pump',
                'url'    => '/donate/?field1=Water%20Sanitation%20-%20Water%20Pump&field2=Where%20Most%20Needed&field3=Sadaqah&amount=600',
                'size'   => 'col-6',
            ],
        ],
    ];
}

function aryana_zakat_get_settings() {
    $defaults = aryana_zakat_get_default_settings();
    $saved = get_option('aryana_zakat_calculator_options', []);
    if (!is_array($saved) || empty($saved)) {
        return $defaults;
    }
    return wp_parse_args($saved, $defaults);
}

// 2. SUBMISSIONS DATA HELPER
function aryana_zakat_get_submissions() {
    $subs = get_option('aryana_zakat_submissions', []);
    return is_array($subs) ? $subs : [];
}

// 3. ADMIN MENU WITH SUBMISSIONS & SETTINGS
add_action('admin_menu', function () {
    $subs = aryana_zakat_get_submissions();
    $count = count($subs);
    $badge = $count > 0 ? " <span class='update-plugins count-{$count}'><span class='plugin-count'>{$count}</span></span>" : '';

    add_menu_page(
        'Zakat Calculator',
        'Zakat Calculator' . $badge,
        'manage_options',
        'aryana-zakat-dashboard',
        'aryana_zakat_render_admin_dashboard',
        'dashicons-calculator',
        25
    );
});

// CSV Export Handler
add_action('admin_init', function() {
    if (isset($_GET['action']) && $_GET['action'] === 'export_zakat_csv' && current_user_can('manage_options')) {
        check_admin_referer('export_zakat_csv');
        $subs = aryana_zakat_get_submissions();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=zakat-submissions-' . date('Y-m-d') . '.csv');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Date', 'Name', 'Email', 'Phone', 'Total Assets', 'Total Liabilities', 'Net Wealth', 'Zakat Due', 'Action Taken', 'Breakdown']);

        foreach ($subs as $s) {
            $breakdown_str = '';
            if (!empty($s['breakdown']) && is_array($s['breakdown'])) {
                foreach ($s['breakdown'] as $k => $v) {
                    $breakdown_str .= "{$k}: £{$v} | ";
                }
            }
            fputcsv($out, [
                $s['id'] ?? '',
                $s['date'] ?? '',
                $s['name'] ?? 'Anonymous Visitor',
                $s['email'] ?? '',
                $s['phone'] ?? '',
                $s['assets'] ?? '£0.00',
                $s['liabilities'] ?? '£0.00',
                $s['net'] ?? '£0.00',
                $s['zakat_due'] ?? '£0.00',
                $s['action'] ?? '',
                $breakdown_str
            ]);
        }
        fclose($out);
        exit;
    }
});

function aryana_zakat_render_admin_dashboard() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'submissions';

    // Handle Clear Submissions
    if (isset($_POST['aryana_clear_submissions']) && check_admin_referer('aryana_clear_submissions_verify')) {
        update_option('aryana_zakat_submissions', []);
        echo '<div class="notice notice-success is-dismissible"><p><strong>All submissions cleared.</strong></p></div>';
    }

    // Handle Save Settings
    if (isset($_POST['aryana_save_zakat_settings']) && check_admin_referer('aryana_zakat_settings_verify')) {
        $assets = [];
        if (!empty($_POST['assets']) && is_array($_POST['assets'])) {
            foreach ($_POST['assets'] as $a) {
                $trimmed = trim(sanitize_text_field($a));
                if ($trimmed !== '') {
                    $assets[] = $trimmed;
                }
            }
        }

        $liabilities = [];
        if (!empty($_POST['liabilities']) && is_array($_POST['liabilities'])) {
            foreach ($_POST['liabilities'] as $l) {
                $trimmed = trim(sanitize_text_field($l));
                if ($trimmed !== '') {
                    $liabilities[] = $trimmed;
                }
            }
        }

        $appeals = [];
        if (!empty($_POST['appeal_amount']) && is_array($_POST['appeal_amount'])) {
            $count = count($_POST['appeal_amount']);
            for ($i = 0; $i < $count; $i++) {
                $amount = sanitize_text_field($_POST['appeal_amount'][$i] ?? '');
                $label  = sanitize_text_field($_POST['appeal_label'][$i] ?? '');
                $url    = sanitize_text_field($_POST['appeal_url'][$i] ?? '');
                $size   = sanitize_text_field($_POST['appeal_size'][$i] ?? 'col-4');

                if ($label !== '' || $amount !== '') {
                    $appeals[] = [
                        'amount' => $amount,
                        'label'  => $label,
                        'url'    => $url,
                        'size'   => in_array($size, ['col-4', 'col-6', 'col-12']) ? $size : 'col-4',
                    ];
                }
            }
        }

        $options = [
            'nisab'        => sanitize_text_field($_POST['nisab'] ?? '240.05'),
            'nisab_text'   => sanitize_textarea_field($_POST['nisab_text'] ?? ''),
            'donate_url'   => sanitize_text_field($_POST['donate_url'] ?? '/donate/'),
            'notify_admin' => isset($_POST['notify_admin']) ? 'yes' : 'no',
            'assets'       => $assets,
            'liabilities'  => $liabilities,
            'appeals'      => $appeals,
        ];

        update_option('aryana_zakat_calculator_options', $options);
        echo '<div class="notice notice-success is-dismissible"><p><strong>Zakat Calculator settings saved successfully!</strong></p></div>';
    }

    $settings = aryana_zakat_get_settings();
    $submissions = aryana_zakat_get_submissions();
    ?>
    <div class="wrap" style="max-width: 1100px;">
        <h1 style="display: flex; align-items: center; gap: 8px;">
            <span class="dashicons dashicons-calculator" style="font-size: 32px; width: 32px; height: 32px;"></span>
            Zakat Calculator Hub
        </h1>
        <p style="font-size: 14px; color: #64748b;">
            View all visitor calculations and manage the calculator fields shown on your website.
        </p>

        <!-- Navigation Tabs -->
        <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
            <a href="?page=aryana-zakat-dashboard&tab=submissions" class="nav-tab <?php echo $active_tab === 'submissions' ? 'nav-tab-active' : ''; ?>">
                📋 Submissions &amp; Inquiries (<?php echo count($submissions); ?>)
            </a>
            <a href="?page=aryana-zakat-dashboard&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                ⚙️ Calculator Field Manager
            </a>
        </h2>

        <?php if ($active_tab === 'submissions') : ?>
            <!-- TAB 1: SUBMISSIONS TABLE -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0;">Visitor Calculations Log</h3>
                <div style="display: flex; gap: 10px;">
                    <?php if (!empty($submissions)) : ?>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?action=export_zakat_csv'), 'export_zakat_csv'); ?>" class="button button-primary">
                            ⬇ Export to CSV (Excel)
                        </a>
                        <form method="post" action="" onsubmit="return confirm('Are you sure you want to delete all submission records?');" style="display: inline;">
                            <?php wp_nonce_field('aryana_clear_submissions_verify'); ?>
                            <input type="submit" name="aryana_clear_submissions" class="button" value="Clear All Records" style="color: #b32d2e;">
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($submissions)) : ?>
                <div class="notice notice-info" style="padding: 20px;">
                    <p style="font-size: 15px; margin: 0;">
                        <strong>No submissions recorded yet.</strong><br>
                        Every time a visitor uses the calculator on <code>/zakat/</code> and clicks "PAY YOUR ZAKAT NOW", submits their contact info, or selects an appeal, their full breakdown will appear here.
                    </p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped" style="box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <thead>
                        <tr>
                            <th style="width: 130px;">Date &amp; Time</th>
                            <th style="width: 170px;">Visitor / Contact</th>
                            <th>Total Assets</th>
                            <th>Liabilities</th>
                            <th>Net Wealth</th>
                            <th><strong>Zakat Due</strong></th>
                            <th style="width: 150px;">Action / Appeal</th>
                            <th style="width: 100px;">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $idx => $s) : ?>
                            <tr>
                                <td><?php echo esc_html($s['date'] ?? '—'); ?></td>
                                <td>
                                    <strong><?php echo esc_html($s['name'] ?? 'Online Visitor'); ?></strong>
                                    <?php if (!empty($s['email'])) : ?>
                                        <br><a href="mailto:<?php echo esc_attr($s['email']); ?>"><?php echo esc_html($s['email']); ?></a>
                                    <?php endif; ?>
                                    <?php if (!empty($s['phone'])) : ?>
                                        <br><span style="color: #64748b; font-size: 12px;"><?php echo esc_html($s['phone']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($s['assets'] ?? '£0.00'); ?></td>
                                <td><?php echo esc_html($s['liabilities'] ?? '£0.00'); ?></td>
                                <td><?php echo esc_html($s['net'] ?? '£0.00'); ?></td>
                                <td>
                                    <strong style="color: #038b5c; font-size: 15px;">
                                        <?php echo esc_html($s['zakat_due'] ?? '£0.00'); ?>
                                    </strong>
                                </td>
                                <td>
                                    <span class="badge" style="background: #e2e8f0; padding: 3px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 600;">
                                        <?php echo esc_html($s['action'] ?? 'Calculated'); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="button button-small aa-view-breakdown-btn" data-target="breakdown_<?php echo $idx; ?>">
                                        View
                                    </button>
                                </td>
                            </tr>
                            <tr id="breakdown_<?php echo $idx; ?>" style="display: none; background: #f8fafc;">
                                <td colspan="8" style="padding: 15px 20px;">
                                    <strong>Detailed Field Breakdown:</strong>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; margin-top: 10px;">
                                        <?php if (!empty($s['breakdown']) && is_array($s['breakdown'])) : ?>
                                            <?php foreach ($s['breakdown'] as $fName => $fVal) : ?>
                                                <div style="background: #fff; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 12px;">
                                                    <span style="color: #64748b; display: block;"><?php echo esc_html($fName); ?>:</span>
                                                    <strong style="color: #1e293b;">£<?php echo esc_html(number_format((float)$fVal, 2)); ?></strong>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <p style="margin: 0; color: #94a3b8;">No breakdown items recorded.</p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    document.querySelectorAll('.aa-view-breakdown-btn').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            const targetId = this.getAttribute('data-target');
                            const row = document.getElementById(targetId);
                            if (row) {
                                if (row.style.display === 'none') {
                                    row.style.display = 'table-row';
                                    this.textContent = 'Hide';
                                } else {
                                    row.style.display = 'none';
                                    this.textContent = 'View';
                                }
                            }
                        });
                    });
                });
                </script>
            <?php endif; ?>

        <?php else : ?>
            <!-- TAB 2: FIELD MANAGER & SETTINGS -->
            <form method="post" action="">
                <?php wp_nonce_field('aryana_zakat_settings_verify'); ?>

                <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee;">General Settings</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="nisab">Nisab Threshold (£)</label></th>
                            <td>
                                <input name="nisab" type="number" step="any" id="nisab" value="<?php echo esc_attr($settings['nisab']); ?>" class="regular-text" style="width: 200px;">
                                <p class="description">Default is <code>240.05</code> (matches original site). Change anytime to current gold or silver rate.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="donate_url">Default Donate URL</label></th>
                            <td>
                                <input name="donate_url" type="text" id="donate_url" value="<?php echo esc_attr($settings['donate_url']); ?>" class="regular-text">
                                <p class="description">Where "PAY YOUR ZAKAT NOW" sends the donor (e.g. <code>/donate/</code>).</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Admin Email Alerts</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="notify_admin" value="yes" <?php checked($settings['notify_admin'] ?? 'yes', 'yes'); ?>>
                                    Send instant email alert to site admin (<code><?php echo esc_html(get_option('admin_email')); ?></code>) when a visitor submits a calculation.
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="nisab_text">Nisab Information Text</label></th>
                            <td>
                                <textarea name="nisab_text" id="nisab_text" rows="3" class="large-text"><?php echo esc_textarea($settings['nisab_text']); ?></textarea>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- COLUMN 1: ASSETS -->
                <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                        <span>1. "What you own" Fields (Assets)</span>
                        <button type="button" class="button button-secondary" id="aaAddAssetBtn">+ Add Asset Field</button>
                    </h2>
                    <div id="aaAssetsContainer" style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($settings['assets'] as $idx => $asset) : ?>
                            <div class="aa-field-row" style="display: flex; gap: 10px; align-items: center;">
                                <span class="dashicons dashicons-menu" style="color: #94a3b8; cursor: grab;"></span>
                                <input type="text" name="assets[]" value="<?php echo esc_attr($asset); ?>" style="flex: 1;" placeholder="Field Label" required>
                                <button type="button" class="button aa-remove-field" style="color: #b32d2e; border-color: #b32d2e;">Delete</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- COLUMN 2: LIABILITIES -->
                <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                        <span>2. "What you owe" Fields (Liabilities)</span>
                        <button type="button" class="button button-secondary" id="aaAddLiabBtn">+ Add Liability Field</button>
                    </h2>
                    <div id="aaLiabContainer" style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($settings['liabilities'] as $idx => $liab) : ?>
                            <div class="aa-field-row" style="display: flex; gap: 10px; align-items: center;">
                                <span class="dashicons dashicons-menu" style="color: #94a3b8; cursor: grab;"></span>
                                <input type="text" name="liabilities[]" value="<?php echo esc_attr($liab); ?>" style="flex: 1;" placeholder="Field Label" required>
                                <button type="button" class="button aa-remove-field" style="color: #b32d2e; border-color: #b32d2e;">Delete</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- COLUMN 3: QUICK APPEAL BUTTONS -->
                <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                        <span>3. Quick Donation Cards (The 5 Green Blocks)</span>
                        <button type="button" class="button button-secondary" id="aaAddAppealBtn">+ Add Appeal Card</button>
                    </h2>
                    <div id="aaAppealsContainer" style="display: flex; flex-direction: column; gap: 14px;">
                        <?php foreach ($settings['appeals'] as $idx => $ap) : ?>
                            <div class="aa-appeal-row" style="display: grid; grid-template-columns: 90px 1.5fr 2fr 100px 70px; gap: 10px; align-items: center; background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
                                <div>
                                    <label style="font-size: 11px; display: block; font-weight: 600;">Amount (£)</label>
                                    <input type="text" name="appeal_amount[]" value="<?php echo esc_attr($ap['amount']); ?>" style="width: 100%; font-weight: 700;" placeholder="300">
                                </div>
                                <div>
                                    <label style="font-size: 11px; display: block; font-weight: 600;">Title / Cause</label>
                                    <input type="text" name="appeal_label[]" value="<?php echo esc_attr($ap['label']); ?>" style="width: 100%;" placeholder="Sponsor an Orphan">
                                </div>
                                <div>
                                    <label style="font-size: 11px; display: block; font-weight: 600;">Redirect URL</label>
                                    <input type="text" name="appeal_url[]" value="<?php echo esc_attr($ap['url']); ?>" style="width: 100%; font-size: 12px;">
                                </div>
                                <div>
                                    <label style="font-size: 11px; display: block; font-weight: 600;">Layout Width</label>
                                    <select name="appeal_size[]" style="width: 100%;">
                                        <option value="col-4" <?php selected($ap['size'] ?? '', 'col-4'); ?>>1/3 (Row 1)</option>
                                        <option value="col-6" <?php selected($ap['size'] ?? '', 'col-6'); ?>>1/2 (Row 2)</option>
                                        <option value="col-12" <?php selected($ap['size'] ?? '', 'col-12'); ?>>Full Width</option>
                                    </select>
                                </div>
                                <div style="text-align: right; padding-top: 16px;">
                                    <button type="button" class="button aa-remove-field" style="color: #b32d2e;">✕</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <p class="submit">
                    <input type="submit" name="aryana_save_zakat_settings" class="button button-primary button-hero" value="Save All Changes">
                </p>
            </form>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Add Asset Field
                document.getElementById('aaAddAssetBtn').addEventListener('click', function() {
                    const container = document.getElementById('aaAssetsContainer');
                    const row = document.createElement('div');
                    row.className = 'aa-field-row';
                    row.style.cssText = 'display: flex; gap: 10px; align-items: center;';
                    row.innerHTML = `
                        <span class="dashicons dashicons-menu" style="color: #94a3b8; cursor: grab;"></span>
                        <input type="text" name="assets[]" value="" style="flex: 1;" placeholder="New Asset Field Label" required>
                        <button type="button" class="button aa-remove-field" style="color: #b32d2e; border-color: #b32d2e;">Delete</button>
                    `;
                    container.appendChild(row);
                });

                // Add Liability Field
                document.getElementById('aaAddLiabBtn').addEventListener('click', function() {
                    const container = document.getElementById('aaLiabContainer');
                    const row = document.createElement('div');
                    row.className = 'aa-field-row';
                    row.style.cssText = 'display: flex; gap: 10px; align-items: center;';
                    row.innerHTML = `
                        <span class="dashicons dashicons-menu" style="color: #94a3b8; cursor: grab;"></span>
                        <input type="text" name="liabilities[]" value="" style="flex: 1;" placeholder="New Liability Field Label" required>
                        <button type="button" class="button aa-remove-field" style="color: #b32d2e; border-color: #b32d2e;">Delete</button>
                    `;
                    container.appendChild(row);
                });

                // Add Appeal Card
                document.getElementById('aaAddAppealBtn').addEventListener('click', function() {
                    const container = document.getElementById('aaAppealsContainer');
                    const row = document.createElement('div');
                    row.className = 'aa-appeal-row';
                    row.style.cssText = 'display: grid; grid-template-columns: 90px 1.5fr 2fr 100px 70px; gap: 10px; align-items: center; background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0;';
                    row.innerHTML = `
                        <div>
                            <label style="font-size: 11px; display: block; font-weight: 600;">Amount (£)</label>
                            <input type="text" name="appeal_amount[]" value="" style="width: 100%; font-weight: 700;" placeholder="100">
                        </div>
                        <div>
                            <label style="font-size: 11px; display: block; font-weight: 600;">Title / Cause</label>
                            <input type="text" name="appeal_label[]" value="" style="width: 100%;" placeholder="New Appeal Name">
                        </div>
                        <div>
                            <label style="font-size: 11px; display: block; font-weight: 600;">Redirect URL</label>
                            <input type="text" name="appeal_url[]" value="/donate/?amount=100" style="width: 100%; font-size: 12px;">
                        </div>
                        <div>
                            <label style="font-size: 11px; display: block; font-weight: 600;">Layout Width</label>
                            <select name="appeal_size[]" style="width: 100%;">
                                <option value="col-4">1/3 (Row 1)</option>
                                <option value="col-6">1/2 (Row 2)</option>
                                <option value="col-12">Full Width</option>
                            </select>
                        </div>
                        <div style="text-align: right; padding-top: 16px;">
                            <button type="button" class="button aa-remove-field" style="color: #b32d2e;">✕</button>
                        </div>
                    `;
                    container.appendChild(row);
                });

                // Delete Row
                document.addEventListener('click', function(e) {
                    if (e.target && e.target.classList.contains('aa-remove-field')) {
                        const row = e.target.closest('.aa-field-row') || e.target.closest('.aa-appeal-row');
                        if (row) {
                            row.remove();
                        }
                    }
                });
            });
            </script>
        <?php endif; ?>
    </div>
    <?php
}

// 4. AJAX HANDLER: RECORD SUBMISSIONS (From Frontend clicks or lead form)
add_action('wp_ajax_aa_record_zakat_submission', 'aryana_ajax_record_zakat_submission');
add_action('wp_ajax_nopriv_aa_record_zakat_submission', 'aryana_ajax_record_zakat_submission');

function aryana_ajax_record_zakat_submission() {
    $name = isset($_POST['name']) && !empty($_POST['name']) ? sanitize_text_field($_POST['name']) : 'Online Visitor';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $assets = isset($_POST['total_assets']) ? sanitize_text_field($_POST['total_assets']) : '£0.00';
    $liabilities = isset($_POST['total_liabilities']) ? sanitize_text_field($_POST['total_liabilities']) : '£0.00';
    $net = isset($_POST['net_wealth']) ? sanitize_text_field($_POST['net_wealth']) : '£0.00';
    $zakat_due = isset($_POST['zakat_due']) ? sanitize_text_field($_POST['zakat_due']) : '£0.00';
    $action_taken = isset($_POST['action_taken']) ? sanitize_text_field($_POST['action_taken']) : 'Calculation Done';

    $breakdown_raw = isset($_POST['breakdown']) ? sanitize_text_field($_POST['breakdown']) : '{}';
    $breakdown = json_decode(stripslashes($breakdown_raw), true);

    $entry = [
        'id'          => uniqid('sub_'),
        'date'        => current_time('Y-m-d H:i:s'),
        'name'        => $name,
        'email'       => $email,
        'phone'       => $phone,
        'assets'      => $assets,
        'liabilities' => $liabilities,
        'net'         => $net,
        'zakat_due'   => $zakat_due,
        'action'      => $action_taken,
        'breakdown'   => is_array($breakdown) ? $breakdown : [],
    ];

    $submissions = aryana_zakat_get_submissions();
    array_unshift($submissions, $entry);

    // Keep up to 1000 submissions
    if (count($submissions) > 1000) {
        $submissions = array_slice($submissions, 0, 1000);
    }
    update_option('aryana_zakat_submissions', $submissions);

    // Check if email alert enabled
    $settings = aryana_zakat_get_settings();
    if (($settings['notify_admin'] ?? 'yes') === 'yes' && ($net !== '£0.00' || !empty($email))) {
        $admin_email = get_option('admin_email');
        $subject = "New Zakat Inquiry: {$name} - Zakat Due: {$zakat_due}";
        $body = "A new Zakat calculation was recorded on your website:\n\n";
        $body .= "Date: " . current_time('r') . "\n";
        $body .= "Visitor: {$name}\n";
        if (!empty($email)) $body .= "Email: {$email}\n";
        if (!empty($phone)) $body .= "Phone: {$phone}\n";
        $body .= "Total Assets: {$assets}\n";
        $body .= "Total Liabilities: {$liabilities}\n";
        $body .= "Net Wealth: {$net}\n";
        $body .= "Zakat Due (2.5%): {$zakat_due}\n";
        $body .= "Action: {$action_taken}\n\n";
        $body .= "View all submissions in your WordPress Dashboard under 'Zakat Calculator'.\n";
        @wp_mail($admin_email, $subject, $body);
    }

    wp_send_json_success(['status' => 'logged', 'id' => $entry['id']]);
}

// 5. FRONTEND SHORTCODE [zakat_calculator]
add_shortcode('zakat_calculator', 'aryana_render_client_zakat_calculator');

function aryana_render_client_zakat_calculator($atts) {
    $settings = aryana_zakat_get_settings();

    $a = shortcode_atts([
        'nisab'      => $settings['nisab'],
        'donate_url' => $settings['donate_url'],
    ], $atts);

    $nisab = floatval($a['nisab']);
    $donate_url = esc_url($a['donate_url']);

    ob_start();
    ?>
    <div class="aryana-zakat-container" id="ZK1">
        <!-- Exact Client CSS (1:1 styling matching Aryana Aid live site) -->
        <style>
            .aryana-zakat-container {
                font-family: var(--e-global-typography-primary-font-family, 'Outfit'), sans-serif !important;
                color: #212529;
                margin: 25px 0;
                width: 100%;
                box-sizing: border-box;
                -webkit-font-smoothing: antialiased;
            }
            .aryana-zakat-container *,
            .aryana-zakat-container *::before,
            .aryana-zakat-container *::after {
                box-sizing: border-box;
                font-family: inherit;
            }
            .aryana-zakat-row {
                display: flex;
                flex-wrap: wrap;
                margin-right: -15px;
                margin-left: -15px;
            }
            .aryana-col-4 {
                flex: 0 0 33.333333%;
                max-width: 33.333333%;
                padding-right: 15px;
                padding-left: 15px;
            }
            @media (max-width: 991px) {
                .aryana-col-4 {
                    flex: 0 0 100%;
                    max-width: 100%;
                    margin-bottom: 30px;
                }
            }
            /* Clean Decent Heading Typography (Outfit) */
            .aryana-heading {
                text-align: center;
                font-family: 'Outfit', var(--e-global-typography-primary-font-family, sans-serif) !important;
                font-size: 28px;
                color: #1e293b;
                font-weight: 700;
                margin-bottom: 20px;
                line-height: 1.3;
                letter-spacing: -0.02em;
            }
            .aryana-form-group {
                margin-bottom: 1rem;
            }
            /* Primary Body & Label Font (Outfit) */
            .aryana-form-group label {
                display: inline-block;
                margin-bottom: 0.5rem;
                font-family: 'Outfit', var(--e-global-typography-primary-font-family, sans-serif) !important;
                font-weight: 500;
                font-size: 15px;
                color: #212529;
            }
            /* Exact Solid Green Input Boxes from Client site */
            .form-control.zin,
            .form-control.zex {
                display: block;
                width: 100%;
                height: calc(1.5em + 0.75rem + 2px);
                padding: 0.375rem 0.75rem;
                font-family: 'Outfit', var(--e-global-typography-primary-font-family, sans-serif) !important;
                font-size: 1.05rem;
                font-weight: 500;
                line-height: 1.5;
                background-color: #038b5c !important;
                color: #ffffff !important;
                border: 0px !important;
                border-radius: 0.25rem !important;
                transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            }
            .form-control.zin:focus,
            .form-control.zex:focus {
                outline: 0;
                box-shadow: 0 0 0 0.2rem rgba(3, 139, 92, 0.35);
            }
            /* Column 3 Totals & Summary */
            .ztotal {
                display: flex;
                justify-content: space-between;
                font-family: 'Outfit', var(--e-global-typography-primary-font-family, sans-serif) !important;
                font-size: 16px;
                padding: 5px 0;
                color: #212529;
            }
            .ztotal span.ZTA,
            .ztotal span.ZTE {
                font-family: 'Outfit', var(--e-global-typography-primary-font-family, sans-serif) !important;
                font-weight: 700;
                font-size: 17px;
            }
            /* Zakat Due Result using Clean Decent Outfit font */
            #zakatResultWrap {
                display: none;
                margin: 12px 0 14px 0;
                padding: 12px 14px;
                background: #f0fdf4;
                border: 1.5px solid #86efac;
                border-radius: 6px;
                text-align: center;
                box-sizing: border-box;
            }
            h2.ZVN {
                font-family: 'Outfit', var(--e-global-typography-primary-font-family, sans-serif) !important;
                font-size: 22px;
                font-weight: 700;
                color: #038b5c;
                margin: 0 0 10px 0;
                letter-spacing: -0.01em;
                text-align: center;
            }
            /* Primary "Pay Your Zakat Now" button */
            #addtocart {
                margin: 0;
            }
            #addtocart a.btn-primary {
                display: block;
                background-color: #038b5c;
                border: 1px solid #038b5c;
                color: #ffffff !important;
                font-family: var(--e-global-typography-primary-font-family, 'Outfit'), sans-serif !important;
                font-weight: 700;
                padding: 12px 20px;
                font-size: 15px;
                letter-spacing: 0.5px;
                border-radius: 4px;
                text-decoration: none !important;
                text-align: center;
                width: 100%;
                transition: background-color 0.15s ease;
                cursor: pointer;
                box-sizing: border-box;
            }
            #addtocart a.btn-primary:hover {
                background-color: #026b46;
                border-color: #026b46;
            }
            /* Optional Contact details box (Directly visible, matching client instruction) */
            .aa-save-box {
                display: block;
                background: #ffffff;
                padding: 12px 14px;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                margin: 14px 0 14px 0;
                box-sizing: border-box;
            }
            .aa-save-title {
                font-family: 'Outfit', var(--e-global-typography-primary-font-family, sans-serif) !important;
                font-size: 13.5px;
                font-weight: 700;
                color: #007a3d;
                margin-bottom: 10px;
                display: block;
                text-decoration: underline;
                line-height: 1.3;
            }
            .aa-save-input {
                width: 100%;
                height: 38px;
                padding: 6px 10px;
                margin-bottom: 8px;
                border: 1px solid #cbd5e1;
                border-radius: 4px;
                font-size: 13.5px;
                font-family: inherit;
                box-sizing: border-box;
                outline: none;
                transition: border-color 0.15s ease;
            }
            .aa-save-input:focus {
                border-color: #007a3d;
                box-shadow: 0 0 0 2px rgba(0, 122, 61, 0.15);
            }
            .aa-save-btn {
                width: 100%;
                background: #007a3d;
                color: #ffffff;
                border: none;
                padding: 10px 14px;
                border-radius: 4px;
                font-size: 14px;
                font-weight: 700;
                cursor: pointer;
                font-family: inherit;
                transition: background-color 0.15s ease;
            }
            .aa-save-btn:hover {
                background: #006331;
            }
            /* Exact 5 Green Appeal Cards (zbwh / zbwh1) from Screenshot */
            .zbwh-grid-row-1 {
                display: flex;
                gap: 8px;
                margin-bottom: 8px;
            }
            .zbwh-grid-row-2 {
                display: flex;
                gap: 8px;
                margin-bottom: 8px;
            }
            .zbwh-col-4 {
                flex: 1 1 33.33%;
            }
            .zbwh-col-6 {
                flex: 1 1 50%;
            }
            .btn.btn-primary.zbwh,
            .btn.btn-primary.zbwh1 {
                display: flex;
                flex-direction: column;
                justify-content: flex-start;
                background-color: #007a3d !important;
                border: 1px solid #007a3d !important;
                color: #ffffff !important;
                font-family: var(--e-global-typography-primary-font-family, 'Outfit'), sans-serif !important;
                border-radius: 4px;
                padding: 12px 10px;
                text-align: left;
                text-decoration: none !important;
                font-weight: 500;
                font-size: 13px;
                line-height: 1.3;
                transition: all 0.2s ease;
                box-sizing: border-box;
                cursor: pointer;
            }
            .btn.btn-primary.zbwh {
                width: 100%;
                min-height: 90px;
            }
            .btn.btn-primary.zbwh1 {
                width: 100%;
                min-height: 75px;
            }
            .btn.btn-primary.zbwh strong,
            .btn.btn-primary.zbwh1 strong {
                font-family: var(--e-global-typography-primary-font-family, 'Outfit'), sans-serif !important;
                font-size: 16px;
                font-weight: 700;
                margin-bottom: 4px;
                display: block;
                color: #ffffff;
            }
            .btn.btn-primary.zbwh:hover,
            .btn.btn-primary.zbwh1:hover,
            .btn.btn-primary.zbwh.active,
            .btn.btn-primary.zbwh1.active {
                background-color: #7cbfa3 !important;
                border-color: #7cbfa3 !important;
                color: #1e3a2f !important;
            }
            .btn.btn-primary.zbwh:hover strong,
            .btn.btn-primary.zbwh1:hover strong,
            .btn.btn-primary.zbwh.active strong,
            .btn.btn-primary.zbwh1.active strong {
                color: #1e3a2f !important;
            }
            @media (max-width: 768px) {
                .zbwh-grid-row-1,
                .zbwh-grid-row-2 {
                    display: flex;
                    gap: 8px;
                    margin-bottom: 8px;
                    width: 100%;
                }
                .zbwh-col-4 {
                    flex: 0 0 calc((100% - 16px) / 3) !important;
                    max-width: calc((100% - 16px) / 3) !important;
                    width: calc((100% - 16px) / 3) !important;
                    box-sizing: border-box !important;
                }
                .zbwh-col-6 {
                    flex: 0 0 calc((100% - 8px) / 2) !important;
                    max-width: calc((100% - 8px) / 2) !important;
                    width: calc((100% - 8px) / 2) !important;
                    box-sizing: border-box !important;
                }
                .btn.btn-primary.zbwh,
                .btn.btn-primary.zbwh1 {
                    min-height: 95px !important;
                    height: 100% !important;
                    padding: 10px 8px !important;
                    font-size: 13px !important;
                    line-height: 1.25 !important;
                    box-sizing: border-box !important;
                    word-break: break-word;
                }
                .btn.btn-primary.zbwh strong,
                .btn.btn-primary.zbwh1 strong {
                    font-size: 15px !important;
                    margin-bottom: 4px !important;
                }
            }
        </style>

        <?php
        $all_assets = $settings['assets'] ?? [];
        $assets_col1 = array_slice($all_assets, 0, 5);
        $assets_col2 = array_slice($all_assets, 5);
        $liabilities = $settings['liabilities'] ?? [];
        ?>
        <div class="aryana-zakat-row">
            <!-- COLUMN 1: WHAT YOU OWN (5 Fields) -->
            <div class="aryana-col-4">
                <p class="aryana-heading">What you own</p>
                <?php foreach ($assets_col1 as $idx => $label) : ?>
                    <div class="aryana-form-group">
                        <label for="in_<?php echo $idx; ?>"><?php echo esc_html($label); ?></label>
                        <input name="in_<?php echo $idx; ?>" id="in_<?php echo $idx; ?>" type="text" class="form-control zin" data-label="<?php echo esc_attr($label); ?>" value="">
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- COLUMN 2: WHAT YOU OWE (3 Liabilities + 2 Moved Assets = 5 Fields, Eliminating Empty Space) -->
            <div class="aryana-col-4">
                <p class="aryana-heading">What you owe</p>
                <?php foreach ($liabilities as $idx => $label) : ?>
                    <div class="aryana-form-group">
                        <label for="o_<?php echo $idx; ?>"><?php echo esc_html($label); ?></label>
                        <input name="o_<?php echo $idx; ?>" id="o_<?php echo $idx; ?>" type="text" class="form-control zex" data-label="<?php echo esc_attr($label); ?>" value="">
                    </div>
                <?php endforeach; ?>

                <?php foreach ($assets_col2 as $sub_idx => $label) : 
                    $actual_idx = 5 + $sub_idx;
                ?>
                    <div class="aryana-form-group">
                        <label for="in_<?php echo $actual_idx; ?>"><?php echo esc_html($label); ?></label>
                        <input name="in_<?php echo $actual_idx; ?>" id="in_<?php echo $actual_idx; ?>" type="text" class="form-control zin" data-label="<?php echo esc_attr($label); ?>" value="">
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- COLUMN 3: IS ZAKAT DUE? (Clean Compact Layout without Empty Gap) -->
            <div class="aryana-col-4">
                <p class="aryana-heading">Is Zakat Due?</p>
                <p style="font-size: 14px; line-height: 1.5; color: #495057; margin-bottom: 16px;">
                    <?php echo esc_html($settings['nisab_text']); ?>
                </p>

                <div id="totalIn" class="ztotal">
                    <span>Total Assets</span>
                    <span class="ZTA">£0.00</span>
                </div>
                <div id="totalOut" class="ztotal">
                    <span>Total Expenses</span>
                    <span class="ZTE">£0.00</span>
                </div>

                <!-- Dynamic Calculation Banner (Appears smoothly when calculated) -->
                <div id="zakatResultWrap">
                    <h2 class="ZVN"></h2>
                    <div id="addtocart"></div>
                </div>

                <!-- Optional Visitor Contact Capture (Visible directly as in client design) -->
                <div class="aa-save-box" id="aaSaveBox">
                    <span class="aa-save-title">✉ Optional: Save / Email My Calculation Summary</span>
                    <input type="text" id="aa_vis_name" class="aa-save-input" placeholder="Your Name">
                    <input type="email" id="aa_vis_email" class="aa-save-input" placeholder="Your Email Address">
                    <input type="text" id="aa_vis_phone" class="aa-save-input" placeholder="Phone (Optional)">
                    <button type="button" id="aaSubmitLeadBtn" class="aa-save-btn">Submit Calculation To Aryana Aid</button>
                    <div id="aaSaveNotice" style="font-size: 12px; margin-top: 6px; display: none;"></div>
                </div>

                <!-- 5 Green Quick Donation Appeal Cards -->
                <div class="zbwh-wrapper">
                    <?php
                    $row1 = [];
                    $row2 = [];
                    foreach ($settings['appeals'] as $ap) {
                        if (($ap['size'] ?? 'col-4') === 'col-6') {
                            $row2[] = $ap;
                        } else {
                            $row1[] = $ap;
                        }
                    }
                    ?>

                    <?php if (!empty($row1)) : ?>
                        <div class="zbwh-grid-row-1">
                            <?php foreach ($row1 as $ap) : ?>
                                <div class="zbwh-col-4">
                                    <a class="btn btn-primary text-left zbwh aa-appeal-click" data-appeal="<?php echo esc_attr($ap['label']); ?>" title="DONATE NOW" href="<?php echo esc_url($ap['url']); ?>">
                                        <strong>£<?php echo esc_html($ap['amount']); ?></strong>
                                        <span><?php echo esc_html($ap['label']); ?></span>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($row2)) : ?>
                        <div class="zbwh-grid-row-2">
                            <?php foreach ($row2 as $ap) : ?>
                                <div class="zbwh-col-6">
                                    <a class="btn btn-primary text-left zbwh1 aa-appeal-click" data-appeal="<?php echo esc_attr($ap['label']); ?>" title="DONATE NOW" href="<?php echo esc_url($ap['url']); ?>">
                                        <strong>£<?php echo esc_html($ap['amount']); ?></strong>
                                        <span><?php echo esc_html($ap['label']); ?></span>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Client's Calculation & Instant Logging Script -->
    <script>
    (function() {
        function initZakatCalculator() {
            if (typeof jQuery === 'undefined') {
                setTimeout(initZakatCalculator, 50);
                return;
            }

            var $ = jQuery;
            var nisab = <?php echo floatval($nisab); ?>;
            var donateBase = <?php echo json_encode($donate_url); ?>;
            var ajaxUrl = <?php echo json_encode(admin_url('admin-ajax.php')); ?>;

            var currentAssets = 0.0;
            var currentExpenses = 0.0;
            var currentZakat = 0.0;

            function getFieldBreakdown() {
                var breakdown = {};
                $(".zin, .zex").each(function() {
                    var label = $(this).attr("data-label") || $(this).prev("label").text();
                    var val = parseFloat($(this).val()) || 0;
                    if (val > 0) {
                        breakdown[label] = val;
                    }
                });
                return breakdown;
            }

            // Function to log calculation to WordPress Dashboard
            function logSubmission(actionName, nameVal, emailVal, phoneVal, callback) {
                var payload = {
                    action: 'aa_record_zakat_submission',
                    name: nameVal || $("#aa_vis_name").val() || '',
                    email: emailVal || $("#aa_vis_email").val() || '',
                    phone: phoneVal || $("#aa_vis_phone").val() || '',
                    total_assets: '£' + currentAssets.toFixed(2),
                    total_liabilities: '£' + currentExpenses.toFixed(2),
                    net_wealth: '£' + Math.max(0, currentAssets - currentExpenses).toFixed(2),
                    zakat_due: currentZakat > 0 ? '£' + currentZakat.toFixed(2) : '£0.00',
                    action_taken: actionName || 'Calculated',
                    breakdown: JSON.stringify(getFieldBreakdown())
                };

                $.post(ajaxUrl, payload, function(res) {
                    if (callback) callback(res);
                }).fail(function() {
                    if (callback) callback();
                });
            }


            // Handle Lead Save Button
            $("#aaSubmitLeadBtn").on("click", function() {
                var $btn = $(this);
                $btn.prop("disabled", true).text("Submitting...");
                logSubmission("Submitted via Form", $("#aa_vis_name").val(), $("#aa_vis_email").val(), $("#aa_vis_phone").val(), function() {
                    $btn.prop("disabled", false).text("Submitted Successfully!");
                    $("#aaSaveNotice").css("color", "#038b5c").text("✓ Your calculation has been recorded!").show();
                });
            });

            // Decimal restriction
            $(document).on("focusout keypress", ".zin, .zex", function(event) {
                var $this = $(this);
                if ((event.which != 46 || $this.val().indexOf('.') != -1) &&
                   ((event.which < 48 || event.which > 57) &&
                   (event.which != 0 && event.which != 8))) {
                       event.preventDefault();
                }

                var text = $(this).val();
                if ((event.which == 46) && (text.indexOf('.') == -1)) {
                    setTimeout(function() {
                        if ($this.val().substring($this.val().indexOf('.')).length > 3) {
                            $this.val($this.val().substring(0, $this.val().indexOf('.') + 3));
                        }
                    }, 1);
                }

                if ((text.indexOf('.') != -1) &&
                    (text.substring(text.indexOf('.')).length > 2) &&
                    (event.which != 0 && event.which != 8) &&
                    ($(this)[0].selectionStart >= text.length - 2)) {
                        event.preventDefault();
                }

                if (event.type == "focusout" || event.keyCode == "13") {
                    calculateZakat();
                }
            });

            $(document).on("input", ".zin, .zex", function() {
                calculateZakat();
            });

            function calculateZakat() {
                $("#addtocart").empty();
                var tAssets = 0.0;
                var tExpense = 0.0;

                $(".zin").each(function() {
                    var val = parseFloat($(this).val());
                    if (!isNaN(val) && val > 0) {
                        tAssets += val;
                    }
                });

                $(".zex").each(function() {
                    var val = parseFloat($(this).val());
                    if (!isNaN(val) && val > 0) {
                        tExpense += val;
                    }
                });

                currentAssets = tAssets;
                currentExpenses = tExpense;

                $(".ZTA").text("£" + tAssets.toFixed(2));
                $(".ZTE").text("£" + tExpense.toFixed(2));

                var net = tAssets - tExpense;

                if (net > nisab) {
                    var zakatDue = ((net) / 40);
                    currentZakat = zakatDue;
                    $("#zakatResultWrap").slideDown(150);
                    $(".ZVN").text("£" + zakatDue.toFixed(2) + " Zakat is now due");
                    
                    var payUrl = donateBase + (donateBase.indexOf('?') !== -1 ? '&' : '?') + 
                        "field1=General%20Poverty%20Fund&field2=Where%20Most%20Needed&field3=Zakat&amount=" + zakatDue.toFixed(2) + 
                        "&give-amount=" + zakatDue.toFixed(2);
                    
                    $("#addtocart").html('<a class="btn btn-primary aa-pay-btn" href="' + payUrl + '" title="DONATE NOW">PAY YOUR ZAKAT NOW</a>');
                } else {
                    currentZakat = 0.0;
                    if (tAssets > 0 || tExpense > 0) {
                        $("#zakatResultWrap").slideDown(150);
                        $(".ZVN").text("Zakat is not due");
                        $("#addtocart").empty();
                    } else {
                        $("#zakatResultWrap").hide();
                        $(".ZVN").text("");
                        $("#addtocart").empty();
                    }
                }
            }

            // When donor clicks "PAY YOUR ZAKAT NOW", log to dashboard immediately
            $(document).on("click", ".aa-pay-btn", function(e) {
                var targetHref = $(this).attr("href");
                e.preventDefault();
                logSubmission("Clicked: PAY YOUR ZAKAT NOW (£" + currentZakat.toFixed(2) + ")", '', '', '', function() {
                    window.location.href = targetHref;
                });
            });

            // When donor clicks any of the 5 quick appeal cards, log to dashboard
            $(document).on("click", ".aa-appeal-click", function(e) {
                var appealName = $(this).attr("data-appeal") || "Appeal Card";
                var targetHref = $(this).attr("href");
                if (currentAssets > 0 || currentExpenses > 0) {
                    e.preventDefault();
                    logSubmission("Clicked: " + appealName, '', '', '', function() {
                        window.location.href = targetHref;
                    });
                }
            });
        }

        initZakatCalculator();
    })();
    </script>
    <?php
    return ob_get_clean();
}
