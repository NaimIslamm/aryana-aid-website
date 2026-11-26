<?php
/**
 * Plugin Name: Aryana Project Gallery
 * Plugin URI: https://aryanaaid.org.uk/
 * Description: Interactive project gallery with customizable 4-column grid, animated hover states, and multi-image lightbox slider modal. Manage projects and all styles directly from the WordPress dashboard.
 * Version: 1.0.1
 * Author: Aryana Aid
 * Author URI: https://aryanaaid.org.uk/
 * Text Domain: aryana-project-gallery
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('APG_VERSION', '1.0.1');
define('APG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('APG_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load includes
require_once APG_PLUGIN_DIR . 'includes/admin.php';
require_once APG_PLUGIN_DIR . 'includes/frontend.php';
require_once APG_PLUGIN_DIR . 'includes/elementor-widget.php';

/**
 * Default settings and sample projects on plugin activation
 */
register_activation_hook(__FILE__, 'apg_activate_plugin');
function apg_activate_plugin() {
    // 1. Initialize Default Projects if none exist
    if (get_option('apg_projects') === false) {
        $sample_projects = [
            [
                'id' => 'proj_' . uniqid(),
                'title' => 'Water Project',
                'subtitle' => 'Clean & Safe Drinking Water',
                'featured_image' => 'https://www.aryanaaid.org.uk/wp-content/uploads/2020/04/water-project-270x245.jpeg',
                'gallery_images' => [
                    'https://www.aryanaaid.org.uk/wp-content/uploads/2020/04/water-project.jpeg',
                    'https://www.aryanaaid.org.uk/wp-content/uploads/2020/04/Water..jpg',
                    'https://www.aryanaaid.org.uk/wp-content/uploads/2020/04/water.jpeg',
                    'https://www.aryanaaid.org.uk/wp-content/uploads/2020/04/water-scaled.jpg',
                ],
                'order' => 1,
                'status' => 'active',
            ],
            [
                'id' => 'proj_' . uniqid(),
                'title' => 'Emergency Shelter',
                'subtitle' => 'Shelter Kits & Warmth',
                'featured_image' => 'https://www.aryanaaid.org.uk/wp-content/uploads/2020/04/Layer-748.png',
                'gallery_images' => [
                    'https://www.aryanaaid.org.uk/wp-content/uploads/2020/04/Layer-748.png',
                    'https://www.aryanaaid.org.uk/wp-content/uploads/2020/04/emergency-shelter-3.jpg',
                    'https://www.aryanaaid.org.uk/wp-content/uploads/2020/04/emergency-shelter-4.jpg',
                    'https://www.aryanaaid.org.uk/wp-content/uploads/2020/04/emergency-Relief-3.jpg',
                ],
                'order' => 2,
                'status' => 'active',
            ],
            [
                'id' => 'proj_' . uniqid(),
                'title' => 'Orphan Sponsorship',
                'subtitle' => 'Care, Education & Nutrition',
                'featured_image' => 'https://www.aryanaaid.org.uk/wp-content/uploads/2020/04/orphan.jpeg',
                'gallery_images' => [
                    'https://www.aryanaaid.org.uk/wp-content/uploads/2020/04/orphan.jpeg',
                    'https://www.aryanaaid.org.uk/wp-content/uploads/2020/04/sponsor-an-orphan-4.jpg',
                    'https://www.aryanaaid.org.uk/wp-content/uploads/2020/04/sponsor-an-orphan.jpg',
                ],
                'order' => 3,
                'status' => 'active',
            ],
            [
                'id' => 'proj_' . uniqid(),
                'title' => 'Food Packages',
                'subtitle' => 'Emergency Food & Rations',
                'featured_image' => 'https://www.aryanaaid.org.uk/wp-content/uploads/2020/04/food.jpeg',
                'gallery_images' => [
                    'https://www.aryanaaid.org.uk/wp-content/uploads/2020/04/food.jpeg',
                    'https://www.aryanaaid.org.uk/wp-content/uploads/2020/04/food-2.jpg',
                    'https://www.aryanaaid.org.uk/wp-content/uploads/2020/04/food-3.jpg',
                    'https://www.aryanaaid.org.uk/wp-content/uploads/2020/04/food-4.jpg',
                ],
                'order' => 4,
                'status' => 'active',
            ],
        ];
        update_option('apg_projects', $sample_projects);
    }

    // 2. Initialize Default Styles if none exist
    if (get_option('apg_settings') === false) {
        $default_settings = apg_get_default_settings();
        update_option('apg_settings', $default_settings);
    }
}

/**
 * Return default styling settings
 */
function apg_get_default_settings() {
    return [
        // Section Header
        'show_header' => 'yes',
        'section_title' => 'Our Work in Pictures',
        'title_color' => '#1a202c',
        'title_size' => '36',
        'title_weight' => '700',
        'section_subtitle' => 'A visual insight into where your money goes',
        'subtitle_color' => '#64748b',
        'subtitle_size' => '18',
        'header_align' => 'center',

        // Grid Layout
        'desktop_columns' => '4',
        'tablet_columns' => '2',
        'mobile_columns' => '1',
        'grid_gap' => '24',
        'section_bg' => '#f8fafc',
        'section_padding_top' => '40',
        'section_padding_bottom' => '40',

        // Featured Image Card Style
        'card_height' => '220',
        'card_radius' => '8',
        'normal_overlay_color' => 'rgba(67, 79, 97, 0.70)',
        'hover_overlay_color' => 'rgba(3, 139, 91, 0.75)',
        'enable_zoom' => 'yes',
        'show_hover_icon' => 'yes',
        'hover_icon_color' => '#ffffff',
        'hover_icon_border' => '#ffffff',

        // Card Title Style
        'card_title_size' => '15',
        'card_title_weight' => '600',
        'card_title_color' => '#ffffff',
        'card_title_transform' => 'uppercase',
        'card_title_letter_spacing' => '0.8',

        // Lightbox Slider Style
        'modal_bg' => 'rgba(10, 15, 29, 0.92)',
        'slider_max_width' => '960',
        'slider_max_height' => '620',
        'slider_fit' => 'contain', // contain or cover
        'arrow_color' => '#ffffff',
        'arrow_bg' => 'rgba(0, 0, 0, 0.45)',
        'arrow_hover_bg' => '#038b5c',
        'show_dots' => 'yes',
        'show_counter' => 'yes',
        'show_caption' => 'yes',
        'close_color' => '#ffffff',
        'animation' => 'slide', // slide or fade
    ];
}

/**
 * Enqueue Frontend Scripts & Styles
 */
add_action('wp_enqueue_scripts', 'apg_enqueue_frontend_assets');
function apg_enqueue_frontend_assets() {
    wp_register_style('apg-style', APG_PLUGIN_URL . 'assets/css/apg-style.css', [], APG_VERSION);
    wp_register_script('apg-gallery', APG_PLUGIN_URL . 'assets/js/apg-gallery.js', [], APG_VERSION, true);
}

