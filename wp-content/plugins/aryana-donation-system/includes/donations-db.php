<?php
/**
 * Donations Database Layer
 * Creates and manages the wp_aryana_donations table and helper query functions.
 */

if (!defined('ABSPATH')) {
    exit;
}

function ads_get_donations_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'aryana_donations';
}

/**
 * Create or update the donations table schema
 */
function ads_create_donations_table() {
    global $wpdb;
    $table_name = ads_get_donations_table_name();
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        donor_type varchar(50) NOT NULL DEFAULT 'individual',
        org_name varchar(255) DEFAULT '',
        event_name varchar(255) DEFAULT '',
        title varchar(50) DEFAULT '',
        first_name varchar(100) NOT NULL,
        last_name varchar(100) NOT NULL,
        email varchar(191) NOT NULL,
        phone varchar(100) DEFAULT '',
        address_1 varchar(255) DEFAULT '',
        address_2 varchar(255) DEFAULT '',
        city varchar(100) DEFAULT '',
        county varchar(100) DEFAULT '',
        postcode varchar(50) DEFAULT '',
        country varchar(100) DEFAULT 'United Kingdom',
        comments text,
        gift_aid tinyint(1) NOT NULL DEFAULT 0,
        gift_aid_amount decimal(10,2) NOT NULL DEFAULT 0.00,
        stay_in_touch text,
        project varchar(255) NOT NULL DEFAULT '',
        campaign_id bigint(20) unsigned DEFAULT NULL,
        campaign_name varchar(255) DEFAULT '',
        source_package varchar(255) DEFAULT '',
        frequency varchar(50) NOT NULL DEFAULT 'single',
        donation_type varchar(100) DEFAULT 'Sadaqah',
        amount decimal(10,2) NOT NULL DEFAULT 0.00,
        fee_amount decimal(10,2) NOT NULL DEFAULT 0.00,
        total_amount decimal(10,2) NOT NULL DEFAULT 0.00,
        currency varchar(10) NOT NULL DEFAULT '£',
        gateway varchar(50) NOT NULL DEFAULT 'card',
        txn_id varchar(191) DEFAULT '',
        status varchar(50) NOT NULL DEFAULT 'completed',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY email (email),
        KEY campaign_id (campaign_id),
        KEY status (status),
        KEY created_at (created_at)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

/**
 * Insert a donation into the database
 */
function ads_insert_donation($data) {
    global $wpdb;
    $table = ads_get_donations_table_name();

    $amount = floatval($data['amount'] ?? 0);
    $fee = floatval($data['fee_amount'] ?? 0);
    $total = floatval($data['total_amount'] ?? ($amount + $fee));
    $gift_aid = !empty($data['gift_aid']) ? 1 : 0;
    $gift_aid_amount = $gift_aid ? round($amount * 0.25, 2) : 0.00;

    $stay_in_touch = is_array($data['stay_in_touch'] ?? null)
        ? json_encode($data['stay_in_touch'])
        : (string)($data['stay_in_touch'] ?? '');

    $insert_data = [
        'donor_type'     => sanitize_key($data['donor_type'] ?? 'individual'),
        'org_name'       => sanitize_text_field($data['org_name'] ?? ''),
        'event_name'     => sanitize_text_field($data['event_name'] ?? ''),
        'title'          => sanitize_text_field($data['title'] ?? ''),
        'first_name'     => sanitize_text_field($data['first_name'] ?? ''),
        'last_name'      => sanitize_text_field($data['last_name'] ?? ''),
        'email'          => sanitize_email($data['email'] ?? ''),
        'phone'          => sanitize_text_field($data['phone'] ?? ''),
        'address_1'      => sanitize_text_field($data['address_1'] ?? $data['address1'] ?? ''),
        'address_2'      => sanitize_text_field($data['address_2'] ?? $data['address2'] ?? ''),
        'city'           => sanitize_text_field($data['city'] ?? ''),
        'county'         => sanitize_text_field($data['county'] ?? ''),
        'postcode'       => sanitize_text_field($data['postcode'] ?? ''),
        'country'        => sanitize_text_field($data['country'] ?? 'United Kingdom'),
        'comments'       => sanitize_textarea_field($data['comments'] ?? ''),
        'gift_aid'       => $gift_aid,
        'gift_aid_amount'=> $gift_aid_amount,
        'stay_in_touch'  => $stay_in_touch,
        'project'        => sanitize_text_field($data['project'] ?? 'General Donation'),
        'campaign_id'    => !empty($data['campaign_id']) ? intval($data['campaign_id']) : null,
        'campaign_name'  => sanitize_text_field($data['campaign_name'] ?? ''),
        'source_package' => sanitize_text_field($data['source_package'] ?? $data['package'] ?? ''),
        'frequency'      => sanitize_key($data['frequency'] ?? 'single'),
        'donation_type'  => sanitize_text_field($data['donation_type'] ?? 'Sadaqah'),
        'amount'         => $amount,
        'fee_amount'     => $fee,
        'total_amount'   => $total,
        'currency'       => sanitize_text_field($data['currency'] ?? '£'),
        'gateway'        => sanitize_key($data['gateway'] ?? 'card'),
        'txn_id'         => sanitize_text_field($data['txn_id'] ?? ('TXN_' . strtoupper(wp_generate_password(10, false)))),
        'status'         => sanitize_key($data['status'] ?? 'completed'),
        'created_at'     => current_time('mysql'),
    ];

    $res = $wpdb->insert($table, $insert_data);
    if ($res === false) {
        return false;
    }

    $donation_id = $wpdb->insert_id;

    // Attribute to Campaign if campaign_id is provided
    if (!empty($insert_data['campaign_id'])) {
        ads_increment_campaign_raised($insert_data['campaign_id'], $amount);
    }

    return $donation_id;
}

/**
 * Increment campaign raised total and donor count
 */
function ads_increment_campaign_raised($campaign_id, $amount) {
    $current_raised = (float) get_post_meta($campaign_id, '_raised_amount', true);
    $current_donors = (int) get_post_meta($campaign_id, '_donations_count', true);

    update_post_meta($campaign_id, '_raised_amount', $current_raised + $amount);
    update_post_meta($campaign_id, '_donations_count', $current_donors + 1);
}

/**
 * Fetch donations with filters and pagination
 */
function ads_get_donations($args = []) {
    global $wpdb;
    $table = ads_get_donations_table_name();

    $defaults = [
        'per_page' => 20,
        'page'     => 1,
        'orderby'  => 'id',
        'order'    => 'DESC',
        'status'   => '',
        'search'   => '',
        'project'  => '',
        'campaign' => '',
    ];

    $r = wp_parse_args($args, $defaults);
    $where = ['1=1'];
    $values = [];

    if (!empty($r['status'])) {
        $where[] = 'status = %s';
        $values[] = $r['status'];
    }

    if (!empty($r['project'])) {
        $where[] = 'project = %s';
        $values[] = $r['project'];
    }

    if (!empty($r['campaign'])) {
        $where[] = 'campaign_id = %d';
        $values[] = intval($r['campaign']);
    }

    if (!empty($r['search'])) {
        $like = '%' . $wpdb->esc_like($r['search']) . '%';
        $where[] = '(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR txn_id LIKE %s OR source_package LIKE %s)';
        $values[] = $like;
        $values[] = $like;
        $values[] = $like;
        $values[] = $like;
        $values[] = $like;
    }

    $where_sql = implode(' AND ', $where);
    $orderby = in_array(strtolower($r['orderby']), ['id', 'amount', 'created_at', 'last_name'], true) ? strtolower($r['orderby']) : 'id';
    $order = strtoupper($r['order']) === 'ASC' ? 'ASC' : 'DESC';

    $limit_sql = '';
    if ($r['per_page'] > 0) {
        $offset = ($r['page'] - 1) * $r['per_page'];
        $limit_sql = $wpdb->prepare('LIMIT %d, %d', $offset, $r['per_page']);
    }

    $sql = "SELECT * FROM $table WHERE $where_sql ORDER BY $orderby $order $limit_sql";
    if (!empty($values)) {
        $sql = $wpdb->prepare($sql, $values);
    }

    $items = $wpdb->get_results($sql, ARRAY_A);

    // Count query
    $count_sql = "SELECT COUNT(*) FROM $table WHERE $where_sql";
    if (!empty($values)) {
        $count_sql = $wpdb->prepare($count_sql, $values);
    }
    $total_items = (int) $wpdb->get_var($count_sql);

    return [
        'items' => $items,
        'total' => $total_items,
        'pages' => $r['per_page'] > 0 ? ceil($total_items / $r['per_page']) : 1,
    ];
}

/**
 * Fetch a single donation by ID
 */
function ads_get_donation($id) {
    global $wpdb;
    $table = ads_get_donations_table_name();
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);
}

/**
 * Calculate dashboard totals
 */
function ads_get_donation_stats() {
    global $wpdb;
    $table = ads_get_donations_table_name();

    $stats = $wpdb->get_row("
        SELECT 
            COUNT(*) as total_count,
            COALESCE(SUM(amount), 0) as total_raised,
            COALESCE(SUM(fee_amount), 0) as total_fees,
            COALESCE(SUM(gift_aid_amount), 0) as total_giftaid,
            COALESCE(AVG(amount), 0) as avg_amount
        FROM $table 
        WHERE status = 'completed'
    ", ARRAY_A);

    return $stats ?: [
        'total_count' => 0,
        'total_raised' => 0,
        'total_fees' => 0,
        'total_giftaid' => 0,
        'avg_amount' => 0,
    ];
}

/**
 * Delete a single donation record by ID
 */
function ads_delete_donation($id) {
    global $wpdb;
    $id = intval($id);
    if ($id <= 0) {
        return false;
    }
    $table = ads_get_donations_table_name();
    return $wpdb->delete($table, ['id' => $id], ['%d']);
}

/**
 * Bulk delete donation records by array of IDs
 */
function ads_bulk_delete_donations($ids) {
    global $wpdb;
    if (empty($ids) || !is_array($ids)) {
        return 0;
    }
    $ids = array_map('intval', $ids);
    $ids = array_filter($ids, function ($v) { return $v > 0; });
    if (empty($ids)) {
        return 0;
    }
    $table = ads_get_donations_table_name();
    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    return (int) $wpdb->query($wpdb->prepare("DELETE FROM $table WHERE id IN ($placeholders)", $ids));
}
