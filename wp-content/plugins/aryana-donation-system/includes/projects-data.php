<?php
if (!defined('ABSPATH')) {
    exit;
}

function ads_get_default_projects_matrix() {
    return [
        'Water - Well' => [
            'locations' => ['Afghanistan', 'Pakistan', 'Where Most Needed'],
            'types' => ['Sadaqah', 'Lillah', 'Charitable Donation'],
            'fixed_single' => 1200,
            'fixed_monthly' => 0,
            'description' => 'Installs a full deep water well providing reliable clean water.'
        ],
        'Water - Pump' => [
            'locations' => ['Afghanistan', 'Pakistan', 'Where Most Needed'],
            'types' => ['Sadaqah', 'Lillah', 'Charitable Donation'],
            'fixed_single' => 600,
            'fixed_monthly' => 0,
            'description' => 'Supplies a manual water hand-pump for community water access.'
        ],
        'Water Tank - Community Well' => [
            'locations' => ['Afghanistan', 'Pakistan', 'Where Most Needed'],
            'types' => ['Sadaqah', 'Lillah', 'Charitable Donation'],
            'fixed_single' => 3600,
            'fixed_monthly' => 0,
            'description' => 'Builds a high-capacity solar water storage tank for an entire community.'
        ],
        'Orphan Sponsorship Scheme' => [
            'locations' => ['Afghanistan', 'Pakistan', 'Where Most Needed'],
            'types' => ['Sadaqah', 'Lillah', 'Zakat', 'Charitable Donation'],
            'fixed_single' => 300,
            'fixed_monthly' => 25,
            'description' => 'Comprehensive orphan care: food, shelter, healthcare, and schooling.'
        ],
        'Food Pack' => [
            'locations' => ['Afghanistan', 'Pakistan', 'Where Most Needed'],
            'types' => ['Sadaqah', 'Lillah', 'Zakat', 'Kaffarah', 'Iftar Pack', 'Fidya', 'Charitable Donation'],
            'fixed_single' => 70,
            'fixed_monthly' => 0,
            'description' => 'Full monthly emergency food staples package for a family in need.'
        ],
        'General Poverty Fund' => [
            'locations' => ['Afghanistan', 'Pakistan', 'Where Most Needed'],
            'types' => ['Sadaqah', 'Lillah', 'Zakat', 'Riba - Interest', 'Charitable Donation'],
            'fixed_single' => 0, // Flexible
            'fixed_monthly' => 0,
            'description' => 'Emergency poverty alleviation deployed wherever the urgency is highest.'
        ],
        'Disabled Child Care - Sponsorship' => [
            'locations' => ['Afghanistan', 'Pakistan', 'Where Most Needed'],
            'types' => ['Sadaqah', 'Lillah', 'Zakat', 'Charitable Donation'],
            'fixed_single' => 360,
            'fixed_monthly' => 30,
            'description' => 'Specialized medical, mobility, and educational care for disabled children.'
        ],
        'Widow Family Sponsorship' => [
            'locations' => ['Afghanistan', 'Pakistan', 'Where Most Needed'],
            'types' => ['Sadaqah', 'Lillah', 'Zakat', 'Kaffarah', 'Iftar Pack', 'Fidya', 'Charitable Donation'],
            'fixed_single' => 840,
            'fixed_monthly' => 70,
            'description' => 'Year-long sustainable livelihood and dignity support for widows and children.'
        ],
        'Women Empowerment Scheme' => [
            'locations' => ['Afghanistan'],
            'types' => ['Sadaqah', 'Lillah', 'Zakat', 'Charitable Donation'],
            'fixed_single' => 420,
            'fixed_monthly' => 35,
            'description' => 'Vocational, tailoring, and micro-business skills training for vulnerable women.'
        ],
        'Disability Support Program - Wheelchair' => [
            'locations' => ['Afghanistan'],
            'types' => ['Sadaqah', 'Lillah', 'Zakat', 'Charitable Donation'],
            'fixed_single' => 120,
            'fixed_monthly' => 0,
            'description' => 'Donates a sturdy all-terrain wheelchair restoring mobility and independence.'
        ],
        'Emergency - Appeal' => [
            'locations' => ['Afghanistan', 'Pakistan', 'Where Most Needed'],
            'types' => ['Sadaqah', 'Lillah', 'Zakat', 'Charitable Donation'],
            'fixed_single' => 0,
            'fixed_monthly' => 0,
            'description' => 'Urgent disaster relief providing blankets, hot meals, and immediate shelter.'
        ],
        'Emergency Shelter - Family Sized Tent' => [
            'locations' => ['Afghanistan', 'Pakistan', 'Where Most Needed'],
            'types' => ['Sadaqah', 'Lillah', 'Zakat', 'Charitable Donation'],
            'fixed_single' => 130,
            'fixed_monthly' => 0,
            'description' => 'Durable all-weather disaster shelter tent for displaced families.'
        ],
        'Winter Pack' => [
            'locations' => ['Afghanistan', 'Pakistan', 'Where Most Needed'],
            'types' => ['Sadaqah', 'Lillah', 'Zakat', 'Charitable Donation'],
            'fixed_single' => 120,
            'fixed_monthly' => 0,
            'description' => 'Essential winter emergency pack with food supplies, blankets, and heating fuel.'
        ],
        'Winter Appeal' => [
            'locations' => ['Afghanistan', 'Pakistan', 'Where Most Needed'],
            'types' => ['Zakat', 'Sadaqah', 'Lillah', 'Charitable Donation'],
            'fixed_single' => 0,
            'fixed_monthly' => 0,
            'description' => 'Winter appeal emergency fund protecting displaced families from severe cold.'
        ],
        'Medical Health Camp Medicine Kits' => [
            'locations' => ['Afghanistan', 'Pakistan', 'Where Most Needed'],
            'types' => ['Sadaqah', 'Lillah', 'Zakat', 'Charitable Donation'],
            'fixed_single' => 500,
            'fixed_monthly' => 0,
            'description' => 'Complete mobile clinic kits supplying essential medication to remote villages.'
        ],
        'Purchase An Ambulance' => [
            'locations' => ['Afghanistan', 'Pakistan', 'Where Most Needed'],
            'types' => ['Sadaqah', 'Lillah', 'Zakat', 'Charitable Donation'],
            'fixed_single' => 7500,
            'fixed_monthly' => 0,
            'description' => 'Equips a life-saving emergency medical vehicle for rural transport.'
        ],
        'Qurbani Lamb' => [
            'locations' => ['Afghanistan', 'Pakistan'],
            'types' => ['Qurbani', 'Where Most Needed'],
            'fixed_single' => 120,
            'fixed_monthly' => 0,
            'description' => 'One full sheep/lamb sacrificed according to sunnah and distributed to poor families.'
        ],
        'Qurbani Goat' => [
            'locations' => ['Afghanistan', 'Pakistan'],
            'types' => ['Qurbani', 'Where Most Needed'],
            'fixed_single' => 90,
            'fixed_monthly' => 0,
            'description' => 'One full goat sacrificed and fresh meat distributed to impoverished families.'
        ],
        'Qurbani Cow Share' => [
            'locations' => ['Afghanistan', 'Pakistan'],
            'types' => ['Qurbani', 'Where Most Needed'],
            'fixed_single' => 65,
            'fixed_monthly' => 0,
            'description' => 'One seventh (1/7th) share in a large cow distributed to vulnerable families.'
        ],
        'Qurbani Full Cow' => [
            'locations' => ['Afghanistan', 'Pakistan'],
            'types' => ['Qurbani', 'Where Most Needed'],
            'fixed_single' => 455,
            'fixed_monthly' => 0,
            'description' => 'Full cow sacrifice feeding up to 30 impoverished families.'
        ],
        'Qurbani Camel Share' => [
            'locations' => ['Afghanistan', 'Pakistan'],
            'types' => ['Qurbani', 'Where Most Needed'],
            'fixed_single' => 60,
            'fixed_monthly' => 0,
            'description' => 'One seventh share in a camel distributed to vulnerable families.'
        ],
        'Qurbani Full Camel' => [
            'locations' => ['Afghanistan', 'Pakistan'],
            'types' => ['Qurbani', 'Where Most Needed'],
            'fixed_single' => 420,
            'fixed_monthly' => 0,
            'description' => 'Full camel sacrifice feeding impoverished families across remote villages.'
        ],
    ];
}

function ads_get_projects_matrix() {
    $custom = get_option('ads_projects_matrix', null);
    if (!empty($custom) && is_array($custom)) {
        return $custom;
    }
    return ads_get_default_projects_matrix();
}

function ads_get_default_donor_fields() {
    return [
        'title' => [
            'label' => 'Title',
            'type' => 'select',
            'required' => 'yes',
            'enabled' => 'yes',
            'applies_to' => 'all',
            'options' => ['Mr', 'Mrs', 'Miss', 'Ms', 'Dr', 'Prof', 'Sheikh', 'Other'],
        ],
        'first_name' => [
            'label' => 'First Name',
            'type' => 'text',
            'required' => 'yes',
            'enabled' => 'yes',
            'applies_to' => 'all',
            'placeholder' => 'Enter first name',
        ],
        'last_name' => [
            'label' => 'Last Name',
            'type' => 'text',
            'required' => 'yes',
            'enabled' => 'yes',
            'applies_to' => 'all',
            'placeholder' => 'Enter last name',
        ],
        'email' => [
            'label' => 'Email Address',
            'type' => 'email',
            'required' => 'yes',
            'enabled' => 'yes',
            'applies_to' => 'all',
            'placeholder' => 'your.email@example.com',
        ],
        'phone' => [
            'label' => 'Phone / Mobile',
            'type' => 'tel',
            'required' => 'no',
            'enabled' => 'yes',
            'applies_to' => 'all',
            'placeholder' => 'e.g. 07123 456789',
        ],
        'address1' => [
            'label' => 'Address Line 1',
            'type' => 'text',
            'required' => 'yes',
            'enabled' => 'yes',
            'applies_to' => 'all',
            'placeholder' => 'Street address or P.O. Box',
        ],
        'address2' => [
            'label' => 'Address Line 2',
            'type' => 'text',
            'required' => 'no',
            'enabled' => 'yes',
            'applies_to' => 'all',
            'placeholder' => 'Apartment, suite, unit, etc.',
        ],
        'city' => [
            'label' => 'Town / City',
            'type' => 'text',
            'required' => 'yes',
            'enabled' => 'yes',
            'applies_to' => 'all',
            'placeholder' => 'e.g. London',
        ],
        'postcode' => [
            'label' => 'Postcode',
            'type' => 'text',
            'required' => 'yes',
            'enabled' => 'yes',
            'applies_to' => 'all',
            'placeholder' => 'e.g. EN2 6LG',
        ],
        'country' => [
            'label' => 'Country',
            'type' => 'select',
            'required' => 'yes',
            'enabled' => 'yes',
            'applies_to' => 'all',
            'options' => ['United Kingdom', 'United States', 'Canada', 'Australia', 'Germany', 'France', 'Ireland', 'Afghanistan', 'Pakistan', 'Other'],
        ],
        'event_name' => [
            'label' => 'Event Name',
            'type' => 'text',
            'required' => 'yes',
            'enabled' => 'yes',
            'applies_to' => 'event',
            'placeholder' => 'e.g. London Marathon, Charity Dinner',
        ],
        'org_name' => [
            'label' => 'Organisation / Company Name',
            'type' => 'text',
            'required' => 'yes',
            'enabled' => 'yes',
            'applies_to' => 'organisation',
            'placeholder' => 'e.g. Aryana Community Centre',
        ],
    ];
}

function ads_get_donor_fields() {
    $custom = get_option('ads_donor_fields', null);
    if (!empty($custom) && is_array($custom)) {
        return $custom;
    }
    return ads_get_default_donor_fields();
}

function ads_get_donor_types() {
    return get_option('ads_donor_types', [
        'individual' => ['label' => 'Individual', 'enabled' => 'yes'],
        'event' => ['label' => 'Event', 'enabled' => 'yes'],
        'organisation' => ['label' => 'Organisation', 'enabled' => 'yes'],
    ]);
}

function ads_get_giftaid_settings() {
    $defaults = [
        'enabled' => 'yes',
        'default_checked' => 'no',
        'title' => 'Supersize Your Support With Gift Aid',
        'badge' => '+25% Extra Free',
        'rate' => 25,
        'desc' => 'If you are a UK taxpayer, making a personal donation allows us to claim an extra 25p for every £1 you give under the Gift Aid scheme at no extra cost to you. That turns a £100 donation into £125!',
        'declaration' => 'Yes, I want to Gift Aid this donation and any donations I make in the future or have made in the past 4 years to Aryana Aid. I am a UK taxpayer and understand that if I pay less Income Tax and/or Capital Gains Tax than the amount of Gift Aid claimed on all my donations in that tax year, it is my responsibility to pay any difference.',
    ];
    $custom = get_option('ads_giftaid_settings', []);
    return wp_parse_args($custom, $defaults);
}

function ads_get_stay_in_touch_settings() {
    $defaults = [
        'enabled' => 'yes',
        'title' => 'Stay In Touch (Optional)',
        'desc' => 'We would love to share updates on how your donations are saving lives. Please select your preferred channels:',
        'channels' => [
            'email' => ['label' => 'Email Updates', 'enabled' => 'yes', 'default_checked' => 'yes'],
            'sms' => ['label' => 'SMS Messages', 'enabled' => 'yes', 'default_checked' => 'no'],
            'post' => ['label' => 'Post / Letters', 'enabled' => 'yes', 'default_checked' => 'no'],
            'phone' => ['label' => 'Phone Calls', 'enabled' => 'yes', 'default_checked' => 'no'],
        ],
    ];
    $custom = get_option('ads_stay_in_touch_settings', []);
    return wp_parse_args($custom, $defaults);
}

function ads_get_general_settings() {
    return [
        'currency' => get_option('ads_currency_symbol', '£'),
        'currency_code' => get_option('ads_currency_code', 'GBP'),
        'frontend_currency_switcher' => get_option('ads_frontend_currency_switcher', 'yes'),
        'admin_fee_enabled' => get_option('ads_admin_fee_enabled', 'yes'),
        'admin_fee_default_checked' => get_option('ads_admin_fee_default_checked', 'no'),
        'admin_fee_percent' => floatval(get_option('ads_admin_fee_percent', 1.10)),
        'admin_fee_label' => get_option('ads_admin_fee_label', 'Please add an admin fee of £1.10 to your donation.'),
        'admin_fee_desc' => get_option('ads_admin_fee_desc', ''),
        'comments_enabled' => get_option('ads_comments_enabled', 'yes'),
        'comments_placeholder' => get_option('ads_comments_placeholder', 'Note (optional)'),
    ];
}

function ads_get_other_ways_settings() {
    return [
        'enabled'          => get_option('ads_other_ways_enabled', 'yes'),
        'section_title'    => get_option('ads_other_ways_title', 'Other Ways To Support'),
        'section_subtitle' => get_option('ads_other_ways_subtitle', 'Prefer donating offline or via direct bank transfer? You can use the details below.'),
        
        // 1. UK Local Bank Transfer
        'uk_bank_title'       => get_option('ads_uk_bank_title', 'UK Local Bank Transfer'),
        'uk_bank_subtitle'    => get_option('ads_uk_bank_subtitle', 'Within the UK • Zero Fees'),
        'uk_bank_instruction' => get_option('ads_uk_bank_instruction', 'For donors transferring from a UK bank account. Please use only the Sort Code and Account Number below:'),
        'bank_name'           => get_option('ads_bank_name', 'Barclays Bank PLC'),
        'account_name'        => get_option('ads_bank_account_name', 'Aryana Aid'),
        'sort_code'           => get_option('ads_bank_sort_code', '20-29-77'),
        'account_number'      => get_option('ads_bank_account_number', '90526184'),
        
        // 2. International Bank Transfer
        'intl_bank_title'       => get_option('ads_intl_bank_title', 'International Bank Transfer'),
        'intl_bank_subtitle'    => get_option('ads_intl_bank_subtitle', 'Outside the UK • Worldwide Wire'),
        'intl_bank_instruction' => get_option('ads_intl_bank_instruction', 'For international donors transferring from outside the UK. Please use the SWIFT/BIC code and IBAN below:'),
        'intl_bank_name'        => get_option('ads_intl_bank_name', get_option('ads_bank_name', 'Barclays Bank PLC')),
        'intl_account_name'     => get_option('ads_intl_account_name', get_option('ads_bank_account_name', 'Aryana Aid')),
        'swift'                 => get_option('ads_bank_swift', 'BUKBGB22'),
        'iban'                  => get_option('ads_bank_iban', 'GB85 BUKB 2029 7790 5261 84'),
        
        // 3. Donate by Post
        'post_title'       => get_option('ads_post_title', 'Donate by Post'),
        'post_subtitle'    => get_option('ads_post_subtitle', 'Cheques & Postal Orders'),
        'post_instruction' => stripslashes(get_option('ads_post_instruction', 'Please make cheques payable to "ARYANA AID" with your name, address, and intended project written on the reverse:')),
        'post_address'     => stripslashes(get_option('ads_post_address', "Unit 1\n62 Alexandra Road\nEnfield, London, EN3 7ER\nUnited Kingdom")),
    ];
}

