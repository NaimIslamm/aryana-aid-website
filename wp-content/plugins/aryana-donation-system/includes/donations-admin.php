<?php
/**
 * Donations Admin Dashboard
 * Displays complete donor profiles, transaction history, filters, single view modal, and CSV export.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register submenu page
add_action('admin_menu', function () {
    add_submenu_page(
        'aryana-donations',
        'Donation Records',
        'All Donations',
        'manage_options',
        'aryana-donations-list',
        'ads_render_donations_admin_page'
    );
}, 20);

// Handle Delete Actions (Single & Bulk)
add_action('admin_init', function () {
    if (!isset($_GET['page']) || $_GET['page'] !== 'aryana-donations-list') {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }

    // 1. Single Delete
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['donation_id'])) {
        $id = intval($_GET['donation_id']);
        check_admin_referer('ads_delete_donation_' . $id);

        if (function_exists('ads_delete_donation')) {
            ads_delete_donation($id);
        }

        wp_safe_redirect(add_query_arg([
            'page'    => 'aryana-donations-list',
            'deleted' => $id,
        ], admin_url('admin.php')));
        exit;
    }

    // 2. Bulk Delete
    if (isset($_POST['ads_bulk_action']) && $_POST['ads_bulk_action'] === 'bulk_delete' && !empty($_POST['donation_ids'])) {
        check_admin_referer('ads_bulk_donations_nonce');

        $ids = array_map('intval', (array) $_POST['donation_ids']);
        if (function_exists('ads_bulk_delete_donations')) {
            $count = ads_bulk_delete_donations($ids);
            wp_safe_redirect(add_query_arg([
                'page'         => 'aryana-donations-list',
                'bulk_deleted' => $count,
            ], admin_url('admin.php')));
            exit;
        }
    }
});

// Handle CSV Export
add_action('admin_init', function () {
    if (!isset($_GET['page']) || $_GET['page'] !== 'aryana-donations-list' || empty($_GET['action']) || $_GET['action'] !== 'export_csv') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    check_admin_referer('ads_export_donations_nonce');

    $donations = ads_get_donations(['per_page' => -1]);
    $items = $donations['items'];

    $filename = 'aryana-donations-' . date('Y-m-d-His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Donation ID',
        'Date & Time',
        'Status',
        'Donor Type',
        'Organisation / Event',
        'Title',
        'First Name',
        'Last Name',
        'Email',
        'Phone',
        'Address 1',
        'Address 2',
        'City',
        'County',
        'Postcode',
        'Country',
        'Project',
        'Campaign Name',
        'Source Package / Button',
        'Frequency',
        'Donation Type',
        'Base Amount (£)',
        'Admin Fee (£)',
        'Total Amount (£)',
        'Gift Aid Eligible',
        'Gift Aid Reclaim (£)',
        'Stay In Touch Consents',
        'Gateway',
        'Transaction ID',
        'Comments / Notes'
    ]);

    foreach ($items as $d) {
        fputcsv($output, [
            $d['id'],
            $d['created_at'],
            ucfirst($d['status']),
            ucfirst($d['donor_type']),
            $d['org_name'] ?: $d['event_name'] ?: '-',
            $d['title'],
            $d['first_name'],
            $d['last_name'],
            $d['email'],
            $d['phone'],
            $d['address_1'],
            $d['address_2'],
            $d['city'],
            $d['county'],
            $d['postcode'],
            $d['country'],
            $d['project'],
            $d['campaign_name'] ?: '-',
            $d['source_package'] ?: 'Direct Form',
            ucfirst($d['frequency']),
            $d['donation_type'],
            number_format($d['amount'], 2, '.', ''),
            number_format($d['fee_amount'], 2, '.', ''),
            number_format($d['total_amount'], 2, '.', ''),
            $d['gift_aid'] ? 'Yes' : 'No',
            number_format($d['gift_aid_amount'], 2, '.', ''),
            $d['stay_in_touch'],
            strtoupper($d['gateway']),
            $d['txn_id'],
            $d['comments'],
        ]);
    }

    fclose($output);
    exit;
});

/**
 * Render Donations Admin Page
 */
function ads_render_donations_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $page = max(1, intval($_GET['paged'] ?? 1));
    $status_filter = sanitize_key($_GET['status_filter'] ?? '');
    $project_filter = sanitize_text_field($_GET['project_filter'] ?? '');
    $search = sanitize_text_field($_GET['s'] ?? '');

    $donations_data = ads_get_donations([
        'page'     => $page,
        'per_page' => 20,
        'status'   => $status_filter,
        'project'  => $project_filter,
        'search'   => $search,
    ]);

    $items = $donations_data['items'];
    $total_items = $donations_data['total'];
    $total_pages = $donations_data['pages'];
    $stats = ads_get_donation_stats();
    $matrix = ads_get_projects_matrix();
    ?>
    <style>
    .aryana-records-wrap {
        padding-right: 25px;
        box-sizing: border-box;
    }
    .aryana-stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 16px;
        margin: 20px 0 25px 0;
    }
    .aryana-stat-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 18px 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
        border-left: 4px solid #038B5C;
    }
    .aryana-stat-card.stat-crimson { border-left-color: #D8005A; }
    .aryana-stat-card.stat-blue { border-left-color: #0284c7; }
    .aryana-stat-card.stat-amber { border-left-color: #d97706; }
    .aryana-stat-label {
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .aryana-stat-value {
        font-size: 26px;
        font-weight: 800;
        color: #0f172a;
        margin-top: 6px;
    }
    .aryana-table-box {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .aryana-filter-bar {
        padding: 16px 20px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .aryana-filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }
    .aryana-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .badge-completed { background: #dcfce7; color: #15803d; }
    .badge-pending { background: #fef3c7; color: #b45309; }
    .badge-refunded { background: #fee2e2; color: #b91c1c; }
    .badge-package { background: #fce7f3; color: #be185d; border: 1px solid #fbcfe8; }
    .badge-giftaid { background: #ccfbf1; color: #0f766e; }

    /* Modal styling */
    .ads-modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.6);
        z-index: 999999;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(2px);
    }
    .ads-modal-overlay.active { display: flex; }
    .ads-modal-content {
        background: #ffffff;
        width: 100%;
        max-width: 780px;
        max-height: 90vh;
        overflow-y: auto;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);
        padding: 28px;
        position: relative;
    }
    .ads-modal-close {
        position: absolute;
        top: 20px; right: 20px;
        font-size: 24px;
        line-height: 1;
        cursor: pointer;
        border: none;
        background: transparent;
        color: #64748b;
    }
    .ads-modal-section {
        margin-bottom: 22px;
        padding-bottom: 18px;
        border-bottom: 1px solid #e2e8f0;
    }
    .ads-modal-section:last-child { border-bottom: none; }
    .ads-modal-section h4 {
        margin: 0 0 12px 0;
        font-size: 15px;
        color: #0f172a;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .ads-modal-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px 20px;
    }
    .ads-modal-grid.cols-3 {
        grid-template-columns: repeat(3, 1fr);
    }
    .ads-modal-item label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 2px;
    }
    .ads-modal-item span {
        font-size: 14px;
        color: #1e293b;
        font-weight: 500;
        word-break: break-word;
    }
    </style>

    <div class="wrap aryana-records-wrap">
        <!-- Top Section Switcher -->
        <div style="display: flex; gap: 8px; margin: 15px 0 20px 0;">
            <a href="<?php echo admin_url('admin.php?page=aryana-donations'); ?>" class="button button-secondary" style="font-weight:600;">⚙️ Dashboard & Settings (6 Tabs)</a>
            <a href="<?php echo admin_url('edit.php?post_type=ads_campaign'); ?>" class="button button-secondary" style="font-weight:600;">🎯 Campaigns</a>
            <a href="<?php echo admin_url('admin.php?page=aryana-donations-list'); ?>" class="button button-primary" style="background:#038B5C; border-color:#026E49; font-weight:700;">💳 All Donations & Donors</a>
        </div>

        <h1 style="display:flex; align-items:center; gap:10px; margin-bottom:5px;">
            <span class="dashicons dashicons-money-alt" style="font-size:32px; color:#038B5C;"></span>
            Donation Records & Donor Profiles
        </h1>
        <p style="color:#64748b; margin-bottom:20px;">Detailed transaction logs, donor contact info, Gift Aid claims, and campaign source button attribution.</p>

        <?php if (!empty($_GET['deleted'])): ?>
            <div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;">
                <p><strong>✅ Donation #<?php echo intval($_GET['deleted']); ?> was permanently deleted successfully.</strong></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($_GET['bulk_deleted'])): ?>
            <div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;">
                <p><strong>✅ <?php echo intval($_GET['bulk_deleted']); ?> donation records were permanently deleted successfully.</strong></p>
            </div>
        <?php endif; ?>


        <!-- Top Statistics Cards -->
        <div class="aryana-stat-grid">
            <div class="aryana-stat-card">
                <span class="aryana-stat-label">Total Revenue Raised</span>
                <span class="aryana-stat-value">£<?php echo number_format($stats['total_raised'], 2); ?></span>
            </div>
            <div class="aryana-stat-card stat-crimson">
                <span class="aryana-stat-label">Total Donors</span>
                <span class="aryana-stat-value"><?php echo number_format($stats['total_count']); ?></span>
            </div>
            <div class="aryana-stat-card stat-blue">
                <span class="aryana-stat-label">HMRC Gift Aid Reclaim</span>
                <span class="aryana-stat-value">£<?php echo number_format($stats['total_giftaid'], 2); ?></span>
            </div>
            <div class="aryana-stat-card stat-amber">
                <span class="aryana-stat-label">Average Donation</span>
                <span class="aryana-stat-value">£<?php echo number_format($stats['avg_amount'], 2); ?></span>
            </div>
        </div>

        <!-- Table Container -->
        <div class="aryana-table-box">
            <!-- Filter & Action Toolbar -->
            <div class="aryana-filter-bar">
                <form method="get" class="aryana-filter-form">
                    <input type="hidden" name="page" value="aryana-donations-list">
                    
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search name, email, package..." style="width:230px;">
                    
                    <select name="status_filter">
                        <option value="">All Statuses</option>
                        <option value="completed" <?php selected($status_filter, 'completed'); ?>>Completed</option>
                        <option value="pending" <?php selected($status_filter, 'pending'); ?>>Pending</option>
                        <option value="refunded" <?php selected($status_filter, 'refunded'); ?>>Refunded</option>
                    </select>

                    <select name="project_filter">
                        <option value="">All Projects / Causes</option>
                        <?php foreach (array_keys($matrix) as $p): ?>
                            <option value="<?php echo esc_attr($p); ?>" <?php selected($project_filter, $p); ?>><?php echo esc_html($p); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="button button-secondary">Filter</button>
                    <?php if ($search || $status_filter || $project_filter): ?>
                        <a href="<?php echo admin_url('admin.php?page=aryana-donations-list'); ?>" class="button button-link" style="color:#64748b;">Reset</a>
                    <?php endif; ?>
                </form>

                <div style="display:flex; gap:8px;">
                    <button type="submit" form="ads-donations-bulk-form" name="ads_bulk_action" value="bulk_delete" class="button button-secondary" style="color:#DC2626; border-color:#FCA5A5; font-weight:600;" onclick="return confirm('Are you sure you want to permanently delete all selected donations? This action cannot be undone.');">
                        <span class="dashicons dashicons-trash" style="vertical-align:middle; margin-top:-2px;"></span> Delete Selected
                    </button>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=aryana-donations-list&action=export_csv'), 'ads_export_donations_nonce'); ?>" class="button button-primary" style="background:#038B5C; border-color:#026E49; font-weight:600;">
                        <span class="dashicons dashicons-download" style="vertical-align:middle; margin-top:-2px;"></span> Export to CSV
                    </a>
                </div>
            </div>

            <!-- Donations List Form & Table -->
            <form method="post" id="ads-donations-bulk-form" action="<?php echo esc_url(admin_url('admin.php?page=aryana-donations-list')); ?>">
                <?php wp_nonce_field('ads_bulk_donations_nonce'); ?>
                <table class="wp-list-table widefat striped" style="border:none; margin:0;">
                    <thead>
                        <tr>
                            <th style="width: 36px; text-align: center;"><input type="checkbox" id="ads-select-all" title="Select all rows"></th>
                            <th style="width: 60px;">ID</th>
                            <th style="width: 130px;">Date</th>
                            <th>Donor Details</th>
                            <th>Project &amp; Cause</th>
                            <th>Source Package / Button</th>
                            <th style="text-align: right;">Amount</th>
                            <th style="text-align: center;">Gift Aid</th>
                            <th style="text-align: center;">Status</th>
                            <th style="width: 140px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="10" style="text-align:center; padding: 40px; color: #64748b;">
                                <span class="dashicons dashicons-info" style="font-size:32px; height:32px; width:32px; display:block; margin:0 auto 10px;"></span>
                                <strong>No donations found.</strong>
                                <p style="margin:5px 0 0;">Donations processed through your checkout form or campaign cards will appear here with full detailed dossiers.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $d): ?>
                            <?php 
                            $delete_url = wp_nonce_url(admin_url('admin.php?page=aryana-donations-list&action=delete&donation_id=' . $d['id']), 'ads_delete_donation_' . $d['id']);
                            $d_json = $d;
                            $d_json['delete_url'] = $delete_url;
                            ?>
                            <tr>
                                <td style="text-align: center;">
                                    <input type="checkbox" name="donation_ids[]" value="<?php echo esc_attr($d['id']); ?>" class="ads-donation-checkbox">
                                </td>
                                <td><strong>#<?php echo esc_html($d['id']); ?></strong></td>
                                <td>
                                    <?php echo esc_html(date('d M Y', strtotime($d['created_at']))); ?><br>
                                    <span style="font-size:11px; color:#64748b;"><?php echo esc_html(date('H:i', strtotime($d['created_at']))); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo esc_html(trim($d['title'] . ' ' . $d['first_name'] . ' ' . $d['last_name'])); ?></strong>
                                    <?php if ($d['donor_type'] !== 'individual'): ?>
                                        <span class="aryana-badge" style="background:#e0e7ff; color:#3730a3;"><?php echo esc_html(ucfirst($d['donor_type'])); ?></span>
                                    <?php endif; ?>
                                    <div style="font-size:12px; color:#475569;">
                                        <a href="mailto:<?php echo esc_attr($d['email']); ?>"><?php echo esc_html($d['email']); ?></a>
                                        <?php if (!empty($d['phone'])): ?>
                                            &bull; <?php echo esc_html($d['phone']); ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($d['project']); ?></strong>
                                    <div style="font-size:12px; color:#64748b;">
                                        <?php echo esc_html($d['donation_type']); ?> &bull; <?php echo esc_html(ucfirst($d['frequency'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($d['source_package'])): ?>
                                        <span class="aryana-badge badge-package">
                                            🎯 <?php echo esc_html($d['source_package']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#94a3b8; font-size:12px;">Direct Form</span>
                                    <?php endif; ?>
                                    <?php if (!empty($d['campaign_name'])): ?>
                                        <div style="font-size:11px; color:#64748b; margin-top:2px;">Campaign: <?php echo esc_html($d['campaign_name']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; font-weight: 700; font-size: 15px; color: #0f172a;">
                                    <?php echo esc_html($d['currency'] . number_format($d['total_amount'], 2)); ?>
                                    <?php if ($d['fee_amount'] > 0): ?>
                                        <div style="font-size:11px; color:#64748b; font-weight:400;">(inc. £<?php echo number_format($d['fee_amount'], 2); ?> fee)</div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($d['gift_aid']): ?>
                                        <span class="aryana-badge badge-giftaid" title="HMRC Claim: £<?php echo number_format($d['gift_aid_amount'], 2); ?>">
                                            +£<?php echo number_format($d['gift_aid_amount'], 2); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#94a3b8; font-size:12px;">No</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="aryana-badge badge-<?php echo esc_attr($d['status']); ?>">
                                        <?php echo esc_html(ucfirst($d['status'])); ?>
                                    </span>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <div style="display:inline-flex; align-items:center; gap:5px;">
                                        <button type="button" class="button button-small ads-view-details-btn" data-donation='<?php echo esc_attr(json_encode($d_json)); ?>' title="View Complete Details">
                                            View
                                        </button>
                                        <a href="<?php echo esc_url($delete_url); ?>" 
                                           class="button button-small" 
                                           style="color:#DC2626; border-color:#FCA5A5; background:#FEF2F2;" 
                                           onclick="return confirm('Are you sure you want to permanently delete Donation #<?php echo esc_js($d['id']); ?> (<?php echo esc_js($d['first_name'] . ' ' . $d['last_name'] . ' - ' . $d['currency'] . number_format($d['total_amount'], 2)); ?>)? This cannot be undone.');" 
                                           title="Delete this donation record">
                                            <span class="dashicons dashicons-trash" style="font-size:15px; width:15px; height:15px; vertical-align:middle; margin-top:-2px;"></span> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </form>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div style="padding:15px 20px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                    <span style="color:#64748b; font-size:13px;">Showing page <?php echo $page; ?> of <?php echo $total_pages; ?> (<?php echo $total_items; ?> total donations)</span>
                    <div style="display:flex; gap:6px;">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="<?php echo esc_url(add_query_arg('paged', $i)); ?>" class="button <?php echo $i === $page ? 'button-primary' : 'button-secondary'; ?>" style="<?php echo $i === $page ? 'background:#038B5C; border-color:#026E49;' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SINGLE DONATION DETAIL MODAL -->
    <div class="ads-modal-overlay" id="ads_donation_modal">
        <div class="ads-modal-content">
            <button type="button" class="ads-modal-close" id="ads_modal_close_btn">&times;</button>
            <h2 style="margin-top:0; display:flex; align-items:center; gap:10px; font-size:20px; border-bottom:1px solid #e2e8f0; padding-bottom:15px;">
                <span class="dashicons dashicons-id-alt" style="font-size:26px; color:#038B5C;"></span>
                Donation Details <span id="m_donation_id" style="color:#64748b; font-weight:400; font-size:16px;"></span>
            </h2>

            <!-- SECTION 1: DONOR PROFILE -->
            <div class="ads-modal-section">
                <h4>👤 Donor Profile & Identity</h4>
                <div class="ads-modal-grid cols-3">
                    <div class="ads-modal-item">
                        <label>Full Name</label>
                        <span id="m_full_name"></span>
                    </div>
                    <div class="ads-modal-item">
                        <label>Donor Type</label>
                        <span id="m_donor_type"></span>
                    </div>
                    <div class="ads-modal-item">
                        <label>Org / Event</label>
                        <span id="m_org_name"></span>
                    </div>
                    <div class="ads-modal-item">
                        <label>Email Address</label>
                        <span id="m_email"></span>
                    </div>
                    <div class="ads-modal-item">
                        <label>Phone Number</label>
                        <span id="m_phone"></span>
                    </div>
                    <div class="ads-modal-item">
                        <label>Status</label>
                        <span id="m_status"></span>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: BILLING ADDRESS -->
            <div class="ads-modal-section">
                <h4>🏡 Billing & Postal Address</h4>
                <div class="ads-modal-grid">
                    <div class="ads-modal-item">
                        <label>Address Lines</label>
                        <span id="m_address"></span>
                    </div>
                    <div class="ads-modal-item">
                        <label>City & County</label>
                        <span id="m_city_county"></span>
                    </div>
                    <div class="ads-modal-item">
                        <label>Postcode / Zip</label>
                        <span id="m_postcode"></span>
                    </div>
                    <div class="ads-modal-item">
                        <label>Country</label>
                        <span id="m_country"></span>
                    </div>
                </div>
                <div class="ads-modal-item" style="margin-top:12px;">
                    <label>Donor Comments / Dedication Note</label>
                    <span id="m_comments" style="font-style:italic; color:#475569;"></span>
                </div>
            </div>

            <!-- SECTION 3: TRANSACTION & CAUSE -->
            <div class="ads-modal-section">
                <h4>💳 Transaction & Cause Attribution</h4>
                <div class="ads-modal-grid cols-3">
                    <div class="ads-modal-item">
                        <label>Project / Cause</label>
                        <span id="m_project" style="font-weight:700;"></span>
                    </div>
                    <div class="ads-modal-item">
                        <label>Source Button / Package</label>
                        <span id="m_package" style="color:#D8005A; font-weight:700;"></span>
                    </div>
                    <div class="ads-modal-item">
                        <label>Campaign</label>
                        <span id="m_campaign"></span>
                    </div>
                    <div class="ads-modal-item">
                        <label>Base Donation Amount</label>
                        <span id="m_amount" style="font-weight:700;"></span>
                    </div>
                    <div class="ads-modal-item">
                        <label>Admin Processing Fee</label>
                        <span id="m_fee"></span>
                    </div>
                    <div class="ads-modal-item">
                        <label>Total Charged</label>
                        <span id="m_total" style="font-size:16px; font-weight:800; color:#038B5C;"></span>
                    </div>
                    <div class="ads-modal-item">
                        <label>Frequency</label>
                        <span id="m_frequency"></span>
                    </div>
                    <div class="ads-modal-item">
                        <label>Donation Type</label>
                        <span id="m_donation_type"></span>
                    </div>
                    <div class="ads-modal-item">
                        <label>Payment Gateway</label>
                        <span id="m_gateway"></span>
                    </div>
                    <div class="ads-modal-item">
                        <label>Transaction ID / Reference</label>
                        <span id="m_txn_id" style="font-family:monospace; font-size:12px;"></span>
                    </div>
                    <div class="ads-modal-item">
                        <label>Date & Time</label>
                        <span id="m_date"></span>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: GIFT AID & CONSENT -->
            <div class="ads-modal-section">
                <h4>📜 UK Gift Aid & Stay In Touch Consents</h4>
                <div class="ads-modal-grid">
                    <div class="ads-modal-item">
                        <label>HMRC Gift Aid Declaration</label>
                        <span id="m_giftaid"></span>
                    </div>
                    <div class="ads-modal-item">
                        <label>HMRC Claim Amount (+25%)</label>
                        <span id="m_giftaid_amount" style="font-weight:700; color:#038B5C;"></span>
                    </div>
                    <div class="ads-modal-item" style="grid-column: span 2;">
                        <label>Stay In Touch Communication Preferences</label>
                        <span id="m_stay_in_touch"></span>
                    </div>
                </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; padding-top:15px; border-top:1px solid #e2e8f0;">
                <a href="#" id="m_delete_link" class="button button-secondary" style="color:#DC2626; border-color:#FCA5A5; background:#FEF2F2;" onclick="return confirm('Are you sure you want to permanently delete this donation record? This action cannot be undone.');">
                    <span class="dashicons dashicons-trash" style="vertical-align:middle; margin-top:-2px;"></span> Delete This Donation
                </a>
                <button type="button" class="button button-secondary" id="ads_modal_close_btn_footer">Close</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('ads_donation_modal');
        var closeBtns = [document.getElementById('ads_modal_close_btn'), document.getElementById('ads_modal_close_btn_footer')];

        function closeModal() {
            modal.classList.remove('active');
        }

        closeBtns.forEach(function (b) {
            if (b) b.addEventListener('click', closeModal);
        });

        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) closeModal();
            });
        }

        document.querySelectorAll('.ads-view-details-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var d = JSON.parse(this.getAttribute('data-donation'));
                if (!d) return;

                document.getElementById('m_donation_id').textContent = '#' + d.id;
                document.getElementById('m_full_name').textContent = [d.title, d.first_name, d.last_name].filter(Boolean).join(' ') || 'N/A';
                document.getElementById('m_donor_type').textContent = (d.donor_type || 'individual').toUpperCase();
                document.getElementById('m_org_name').textContent = d.org_name || d.event_name || 'N/A';
                document.getElementById('m_email').textContent = d.email || 'N/A';
                document.getElementById('m_phone').textContent = d.phone || 'N/A';
                document.getElementById('m_status').textContent = (d.status || 'completed').toUpperCase();

                document.getElementById('m_address').textContent = [d.address_1, d.address_2].filter(Boolean).join(', ') || 'N/A';
                document.getElementById('m_city_county').textContent = [d.city, d.county].filter(Boolean).join(', ') || 'N/A';
                document.getElementById('m_postcode').textContent = d.postcode || 'N/A';
                document.getElementById('m_country').textContent = d.country || 'United Kingdom';
                document.getElementById('m_comments').textContent = d.comments ? '"' + d.comments + '"' : 'None provided';

                document.getElementById('m_project').textContent = d.project || 'General Donation';
                document.getElementById('m_package').textContent = d.source_package ? '🎯 ' + d.source_package : 'Direct Form Donation';
                document.getElementById('m_campaign').textContent = d.campaign_name || (d.campaign_id ? '#' + d.campaign_id : 'None');
                document.getElementById('m_amount').textContent = (d.currency || '£') + parseFloat(d.amount).toFixed(2);
                document.getElementById('m_fee').textContent = d.fee_amount > 0 ? (d.currency || '£') + parseFloat(d.fee_amount).toFixed(2) : 'None (£0.00)';
                document.getElementById('m_total').textContent = (d.currency || '£') + parseFloat(d.total_amount).toFixed(2);
                document.getElementById('m_frequency').textContent = (d.frequency || 'single').toUpperCase();
                document.getElementById('m_donation_type').textContent = d.donation_type || 'Sadaqah';
                document.getElementById('m_gateway').textContent = (d.gateway || 'card').toUpperCase();
                document.getElementById('m_txn_id').textContent = d.txn_id || 'N/A';
                document.getElementById('m_date').textContent = d.created_at || 'N/A';

                document.getElementById('m_giftaid').textContent = d.gift_aid == 1 ? '✅ YES - UK Taxpayer Confirmed' : '❌ NO';
                document.getElementById('m_giftaid_amount').textContent = d.gift_aid == 1 ? '+£' + parseFloat(d.gift_aid_amount).toFixed(2) : '£0.00';

                // Stay in touch parse
                var sitHtml = 'No consents recorded';
                try {
                    if (d.stay_in_touch) {
                        var sitObj = typeof d.stay_in_touch === 'string' ? JSON.parse(d.stay_in_touch) : d.stay_in_touch;
                        if (typeof sitObj === 'object') {
                            sitHtml = Object.keys(sitObj).map(function (k) {
                                var val = sitObj[k];
                                var icon = (val === 'yes' || val === true || val === 1) ? '✅' : '❌';
                                return icon + ' ' + k.toUpperCase();
                            }).join(' &nbsp;&bull;&nbsp; ');
                        }
                    }
                } catch(e) {
                    sitHtml = d.stay_in_touch;
                }
                document.getElementById('m_stay_in_touch').innerHTML = sitHtml;

                // Set delete link in modal
                var deleteLink = document.getElementById('m_delete_link');
                if (deleteLink && d.delete_url) {
                    deleteLink.href = d.delete_url;
                }

                modal.classList.add('active');
            });
        });

        // Select All Checkbox Handler
        var selectAll = document.getElementById('ads-select-all');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                var checked = this.checked;
                document.querySelectorAll('.ads-donation-checkbox').forEach(function (cb) {
                    cb.checked = checked;
                });
            });
        }
    });
    </script>
    <?php
}
