<?php
/**
 * Admin Dashboard Management for Aryana Project Gallery
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Admin Menu
 */
add_action('admin_menu', 'apg_register_admin_menu');
function apg_register_admin_menu() {
    add_menu_page(
        __('Project Gallery', 'aryana-project-gallery'),
        __('Project Gallery', 'aryana-project-gallery'),
        'manage_options',
        'aryana-project-gallery',
        'apg_render_admin_page',
        'dashicons-images-alt2',
        26
    );
}

/**
 * Enqueue Admin Scripts & Styles
 */
add_action('admin_enqueue_scripts', 'apg_enqueue_admin_assets');
function apg_enqueue_admin_assets($hook) {
    if (strpos($hook, 'aryana-project-gallery') === false) {
        return;
    }

    // WP Media Uploader & Color Picker
    wp_enqueue_media();
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');

    // Plugin Admin Assets
    wp_enqueue_style('apg-admin-style', APG_PLUGIN_URL . 'assets/css/apg-admin.css', [], APG_VERSION);
    wp_enqueue_script('apg-admin-script', APG_PLUGIN_URL . 'assets/js/apg-admin.js', ['jquery', 'wp-color-picker'], APG_VERSION, true);

    wp_localize_script('apg-admin-script', 'apgAdmin', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('apg_admin_nonce'),
        'confirmDelete' => __('Are you sure you want to delete this project?', 'aryana-project-gallery'),
    ]);
}

/**
 * Handle Admin Settings and Project Saves
 */
add_action('admin_init', 'apg_handle_form_submissions');
function apg_handle_form_submissions() {
    if (!isset($_POST['apg_action']) || !current_user_can('manage_options')) {
        return;
    }

    // 1. Save Project (Add or Edit)
    if ($_POST['apg_action'] === 'save_project') {
        check_admin_referer('apg_save_project_action', 'apg_nonce');

        $projects = get_option('apg_projects', []);
        $project_id = !empty($_POST['project_id']) ? sanitize_text_field($_POST['project_id']) : 'proj_' . uniqid();
        $title = sanitize_text_field($_POST['project_title'] ?? '');
        $subtitle = sanitize_text_field($_POST['project_subtitle'] ?? '');
        $featured_image = esc_url_raw($_POST['featured_image'] ?? '');
        $status = sanitize_text_field($_POST['project_status'] ?? 'active');
        $order = intval($_POST['project_order'] ?? 0);

        // Process gallery slider images array
        $gallery_images = [];
        if (!empty($_POST['gallery_images']) && is_array($_POST['gallery_images'])) {
            foreach ($_POST['gallery_images'] as $img_url) {
                $clean_url = esc_url_raw($img_url);
                if (!empty($clean_url)) {
                    $gallery_images[] = $clean_url;
                }
            }
        }

        $project_data = [
            'id' => $project_id,
            'title' => $title,
            'subtitle' => $subtitle,
            'featured_image' => $featured_image,
            'gallery_images' => $gallery_images,
            'order' => $order,
            'status' => $status,
        ];

        // Update existing or add new
        $found = false;
        foreach ($projects as $idx => $p) {
            if ($p['id'] === $project_id) {
                $projects[$idx] = $project_data;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $projects[] = $project_data;
        }

        // Sort by order
        usort($projects, function ($a, $b) {
            return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
        });

        update_option('apg_projects', $projects);
        wp_safe_redirect(add_query_arg(['page' => 'aryana-project-gallery', 'tab' => 'projects', 'saved' => '1'], admin_url('admin.php')));
        exit;
    }

    // 2. Delete Project
    if ($_POST['apg_action'] === 'delete_project') {
        check_admin_referer('apg_delete_project_action', 'apg_nonce');

        $delete_id = sanitize_text_field($_POST['delete_id'] ?? '');
        $projects = get_option('apg_projects', []);
        $projects = array_values(array_filter($projects, function ($p) use ($delete_id) {
            return $p['id'] !== $delete_id;
        }));

        update_option('apg_projects', $projects);
        wp_safe_redirect(add_query_arg(['page' => 'aryana-project-gallery', 'tab' => 'projects', 'deleted' => '1'], admin_url('admin.php')));
        exit;
    }

    // 3. Save Style & Layout Settings
    if ($_POST['apg_action'] === 'save_settings') {
        check_admin_referer('apg_save_settings_action', 'apg_nonce');

        $defaults = apg_get_default_settings();
        $saved_settings = [];

        foreach ($defaults as $key => $default_val) {
            if (isset($_POST[$key])) {
                $saved_settings[$key] = sanitize_text_field($_POST[$key]);
            } else {
                // Checkboxes that are unchecked
                if (in_array($key, ['show_header', 'enable_zoom', 'show_hover_icon', 'show_dots', 'show_counter', 'show_caption'])) {
                    $saved_settings[$key] = 'no';
                } else {
                    $saved_settings[$key] = $default_val;
                }
            }
        }

        update_option('apg_settings', $saved_settings);
        wp_safe_redirect(add_query_arg(['page' => 'aryana-project-gallery', 'tab' => 'styles', 'saved' => '1'], admin_url('admin.php')));
        exit;
    }
}

/**
 * Render Admin Page
 */
function apg_render_admin_page() {
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'projects';
    $projects = get_option('apg_projects', []);
    $settings = wp_parse_args(get_option('apg_settings', []), apg_get_default_settings());

    // Check if editing a project
    $editing_project = null;
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && !empty($_GET['id'])) {
        $edit_id = sanitize_text_field($_GET['id']);
        foreach ($projects as $p) {
            if ($p['id'] === $edit_id) {
                $editing_project = $p;
                break;
            }
        }
    }
    ?>
    <div class="wrap apg-admin-wrap">
        <div class="apg-header-bar">
            <div class="apg-brand">
                <span class="dashicons dashicons-images-alt2 apg-icon-lg"></span>
                <div>
                    <h1><?php esc_html_e('Aryana Project Gallery', 'aryana-project-gallery'); ?> <span class="apg-badge">v<?php echo esc_html(APG_VERSION); ?></span></h1>
                    <p class="apg-subtitle"><?php esc_html_e('Manage projects, photos, hover effects, and slider lightbox popup.', 'aryana-project-gallery'); ?></p>
                </div>
            </div>
            <div class="apg-shortcode-pill">
                <span class="apg-sc-label"><?php esc_html_e('Shortcode:', 'aryana-project-gallery'); ?></span>
                <code>[aryana_project_gallery]</code>
                <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('[aryana_project_gallery]'); alert('Shortcode copied to clipboard!');">
                    <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e('Copy', 'aryana-project-gallery'); ?>
                </button>
            </div>
        </div>

        <?php if (isset($_GET['saved'])) : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Settings updated successfully!', 'aryana-project-gallery'); ?></p></div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])) : ?>
            <div class="notice notice-info is-dismissible"><p><?php esc_html_e('Project removed successfully!', 'aryana-project-gallery'); ?></p></div>
        <?php endif; ?>

        <!-- Nav Tabs -->
        <h2 class="nav-tab-wrapper apg-nav-tabs">
            <a href="<?php echo esc_url(add_query_arg(['page' => 'aryana-project-gallery', 'tab' => 'projects'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $active_tab === 'projects' ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-format-gallery"></span> <?php esc_html_e('Projects & Galleries', 'aryana-project-gallery'); ?> (<?php echo count($projects); ?>)
            </a>
            <a href="<?php echo esc_url(add_query_arg(['page' => 'aryana-project-gallery', 'tab' => 'styles'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $active_tab === 'styles' ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-admin-appearance"></span> <?php esc_html_e('Style & Design Customizer', 'aryana-project-gallery'); ?>
            </a>
        </h2>

        <div class="apg-tab-content">
            <?php if ($active_tab === 'projects') : ?>
                <?php apg_render_projects_tab($projects, $editing_project); ?>
            <?php else : ?>
                <?php apg_render_styles_tab($settings); ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Render Projects Management Tab
 */
function apg_render_projects_tab($projects, $editing_project) {
    ?>
    <div class="apg-grid-container">
        <!-- Left: Project Form (Add / Edit) -->
        <div class="apg-card apg-form-card">
            <h2 class="apg-card-title">
                <?php echo $editing_project ? esc_html__('Edit Project', 'aryana-project-gallery') : esc_html__('Add New Project', 'aryana-project-gallery'); ?>
            </h2>
            <form method="post" action="" id="apg-project-form">
                <?php wp_nonce_field('apg_save_project_action', 'apg_nonce'); ?>
                <input type="hidden" name="apg_action" value="save_project">
                <input type="hidden" name="project_id" value="<?php echo esc_attr($editing_project['id'] ?? ''); ?>">

                <!-- Title -->
                <div class="apg-field-group">
                    <label for="project_title"><?php esc_html_e('Project Name / Title', 'aryana-project-gallery'); ?> <span class="required">*</span></label>
                    <input type="text" name="project_title" id="project_title" class="regular-text widefat" required value="<?php echo esc_attr($editing_project['title'] ?? ''); ?>" placeholder="e.g. Water Project">
                    <p class="description"><?php esc_html_e('Displayed on the featured image card on the main page.', 'aryana-project-gallery'); ?></p>
                </div>

                <!-- Subtitle / Tag -->
                <div class="apg-field-group">
                    <label for="project_subtitle"><?php esc_html_e('Subtitle / Tagline (Optional)', 'aryana-project-gallery'); ?></label>
                    <input type="text" name="project_subtitle" id="project_subtitle" class="regular-text widefat" value="<?php echo esc_attr($editing_project['subtitle'] ?? ''); ?>" placeholder="e.g. Clean & Safe Drinking Water">
                </div>

                <!-- Featured Image -->
                <div class="apg-field-group">
                    <label><?php esc_html_e('Featured Image (Front Card Cover)', 'aryana-project-gallery'); ?> <span class="required">*</span></label>
                    <div class="apg-media-picker-single">
                        <div class="apg-img-preview" id="apg-featured-preview">
                            <?php if (!empty($editing_project['featured_image'])) : ?>
                                <img src="<?php echo esc_url($editing_project['featured_image']); ?>" alt="Featured preview">
                            <?php else : ?>
                                <span class="apg-no-image"><span class="dashicons dashicons-format-image"></span><br><?php esc_html_e('No image selected', 'aryana-project-gallery'); ?></span>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="featured_image" id="apg_featured_image" value="<?php echo esc_url($editing_project['featured_image'] ?? ''); ?>" required>
                        <div class="apg-media-actions">
                            <button type="button" class="button button-secondary" id="apg-select-featured-btn">
                                <span class="dashicons dashicons-upload"></span> <?php esc_html_e('Select / Upload Image', 'aryana-project-gallery'); ?>
                            </button>
                            <button type="button" class="button button-link-delete" id="apg-remove-featured-btn" style="<?php echo empty($editing_project['featured_image']) ? 'display:none;' : ''; ?>">
                                <?php esc_html_e('Remove', 'aryana-project-gallery'); ?>
                            </button>
                        </div>
                    </div>
                    <p class="description"><?php esc_html_e('The main picture displayed in the 4-column grid.', 'aryana-project-gallery'); ?></p>
                </div>

                <!-- Gallery Slider Images -->
                <div class="apg-field-group">
                    <label><?php esc_html_e('Popup Slider Images (Bunch of Images)', 'aryana-project-gallery'); ?> <span class="required">*</span></label>
                    <p class="description"><?php esc_html_e('Select multiple images that donors will slide left and right after clicking this project.', 'aryana-project-gallery'); ?></p>
                    
                    <button type="button" class="button button-primary" id="apg-add-gallery-btn">
                        <span class="dashicons dashicons-images-alt"></span> <?php esc_html_e('Add Images to Slider (Multi-select)', 'aryana-project-gallery'); ?>
                    </button>

                    <div class="apg-gallery-preview-grid" id="apg-gallery-preview-grid">
                        <?php
                        $gallery_imgs = $editing_project['gallery_images'] ?? [];
                        if (!empty($gallery_imgs)) :
                            foreach ($gallery_imgs as $g_url) :
                        ?>
                            <div class="apg-gallery-thumb-item">
                                <img src="<?php echo esc_url($g_url); ?>" alt="Gallery item">
                                <input type="hidden" name="gallery_images[]" value="<?php echo esc_url($g_url); ?>">
                                <button type="button" class="apg-remove-thumb" title="<?php esc_attr_e('Remove', 'aryana-project-gallery'); ?>">&times;</button>
                            </div>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </div>
                </div>

                <div class="apg-field-row">
                    <!-- Order -->
                    <div class="apg-field-group apg-field-half">
                        <label for="project_order"><?php esc_html_e('Display Order', 'aryana-project-gallery'); ?></label>
                        <input type="number" name="project_order" id="project_order" value="<?php echo esc_attr($editing_project['order'] ?? (count($projects) + 1)); ?>" class="small-text" min="1">
                    </div>

                    <!-- Status -->
                    <div class="apg-field-group apg-field-half">
                        <label for="project_status"><?php esc_html_e('Status', 'aryana-project-gallery'); ?></label>
                        <select name="project_status" id="project_status">
                            <option value="active" <?php selected($editing_project['status'] ?? 'active', 'active'); ?>><?php esc_html_e('Active (Visible)', 'aryana-project-gallery'); ?></option>
                            <option value="draft" <?php selected($editing_project['status'] ?? '', 'draft'); ?>><?php esc_html_e('Draft (Hidden)', 'aryana-project-gallery'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="apg-form-submit">
                    <button type="submit" class="button button-primary button-large">
                        <span class="dashicons dashicons-yes"></span> <?php echo $editing_project ? esc_html__('Update Project', 'aryana-project-gallery') : esc_html__('Save & Add Project', 'aryana-project-gallery'); ?>
                    </button>
                    <?php if ($editing_project) : ?>
                        <a href="<?php echo esc_url(add_query_arg(['page' => 'aryana-project-gallery', 'tab' => 'projects'], admin_url('admin.php'))); ?>" class="button button-secondary">
                            <?php esc_html_e('Cancel', 'aryana-project-gallery'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Right: Current Projects List -->
        <div class="apg-card apg-list-card">
            <h2 class="apg-card-title">
                <?php esc_html_e('Current Projects', 'aryana-project-gallery'); ?> (<?php echo count($projects); ?>)
            </h2>
            <p class="description"><?php esc_html_e('All projects listed below will automatically appear on the frontend grid according to their order.', 'aryana-project-gallery'); ?></p>

            <?php if (empty($projects)) : ?>
                <div class="apg-empty-state">
                    <span class="dashicons dashicons-format-gallery"></span>
                    <p><?php esc_html_e('No projects added yet. Use the form on the left to create your first project!', 'aryana-project-gallery'); ?></p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped apg-table">
                    <thead>
                        <tr>
                            <th width="45"><?php esc_html_e('#', 'aryana-project-gallery'); ?></th>
                            <th width="80"><?php esc_html_e('Cover', 'aryana-project-gallery'); ?></th>
                            <th><?php esc_html_e('Project Name', 'aryana-project-gallery'); ?></th>
                            <th width="90"><?php esc_html_e('Photos', 'aryana-project-gallery'); ?></th>
                            <th width="75"><?php esc_html_e('Status', 'aryana-project-gallery'); ?></th>
                            <th width="120"><?php esc_html_e('Actions', 'aryana-project-gallery'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $p) : 
                            $img_count = count($p['gallery_images'] ?? []);
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($p['order'] ?? 1); ?></strong></td>
                                <td>
                                    <?php if (!empty($p['featured_image'])) : ?>
                                        <img src="<?php echo esc_url($p['featured_image']); ?>" class="apg-list-thumb" alt="Thumb">
                                    <?php else : ?>
                                        <div class="apg-no-thumb"><span class="dashicons dashicons-format-image"></span></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($p['title']); ?></strong>
                                    <?php if (!empty($p['subtitle'])) : ?>
                                        <div class="apg-item-sub"><?php echo esc_html($p['subtitle']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="apg-badge apg-badge-count"><?php echo intval($img_count); ?> <?php esc_html_e('images', 'aryana-project-gallery'); ?></span>
                                </td>
                                <td>
                                    <?php if (($p['status'] ?? 'active') === 'active') : ?>
                                        <span class="apg-badge apg-badge-active"><?php esc_html_e('Active', 'aryana-project-gallery'); ?></span>
                                    <?php else : ?>
                                        <span class="apg-badge apg-badge-draft"><?php esc_html_e('Draft', 'aryana-project-gallery'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(add_query_arg(['page' => 'aryana-project-gallery', 'tab' => 'projects', 'action' => 'edit', 'id' => $p['id']], admin_url('admin.php'))); ?>" class="button button-small">
                                        <span class="dashicons dashicons-edit"></span> <?php esc_html_e('Edit', 'aryana-project-gallery'); ?>
                                    </a>
                                    <form method="post" action="" style="display:inline;" onsubmit="return confirm('<?php echo esc_js(__('Are you sure you want to delete this project?', 'aryana-project-gallery')); ?>');">
                                        <?php wp_nonce_field('apg_delete_project_action', 'apg_nonce'); ?>
                                        <input type="hidden" name="apg_action" value="delete_project">
                                        <input type="hidden" name="delete_id" value="<?php echo esc_attr($p['id']); ?>">
                                        <button type="submit" class="button button-small button-link-delete" title="<?php esc_attr_e('Delete', 'aryana-project-gallery'); ?>">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Render Style & Design Customizer Tab
 */
function apg_render_styles_tab($settings) {
    ?>
    <form method="post" action="" class="apg-styles-form">
        <?php wp_nonce_field('apg_save_settings_action', 'apg_nonce'); ?>
        <input type="hidden" name="apg_action" value="save_settings">

        <div class="apg-settings-grid">
            <!-- 1. Section Header Styles -->
            <div class="apg-card">
                <h3 class="apg-card-title"><span class="dashicons dashicons-heading"></span> <?php esc_html_e('1. Section Header Styles', 'aryana-project-gallery'); ?></h3>
                
                <div class="apg-field-group">
                    <label>
                        <input type="checkbox" name="show_header" value="yes" <?php checked($settings['show_header'], 'yes'); ?>>
                        <strong><?php esc_html_e('Display Section Header (Title & Subtitle)', 'aryana-project-gallery'); ?></strong>
                    </label>
                </div>

                <div class="apg-field-group">
                    <label for="section_title"><?php esc_html_e('Section Title', 'aryana-project-gallery'); ?></label>
                    <input type="text" name="section_title" id="section_title" class="widefat" value="<?php echo esc_attr($settings['section_title']); ?>">
                </div>

                <div class="apg-field-row">
                    <div class="apg-field-group apg-field-third">
                        <label for="title_size"><?php esc_html_e('Title Font Size (px)', 'aryana-project-gallery'); ?></label>
                        <input type="number" name="title_size" id="title_size" value="<?php echo esc_attr($settings['title_size']); ?>" min="16" max="72">
                    </div>
                    <div class="apg-field-group apg-field-third">
                        <label for="title_weight"><?php esc_html_e('Title Font Weight', 'aryana-project-gallery'); ?></label>
                        <select name="title_weight" id="title_weight">
                            <option value="400" <?php selected($settings['title_weight'], '400'); ?>>400 (Normal)</option>
                            <option value="500" <?php selected($settings['title_weight'], '500'); ?>>500 (Medium)</option>
                            <option value="600" <?php selected($settings['title_weight'], '600'); ?>>600 (Semi-Bold)</option>
                            <option value="700" <?php selected($settings['title_weight'], '700'); ?>>700 (Bold)</option>
                            <option value="800" <?php selected($settings['title_weight'], '800'); ?>>800 (Extra-Bold)</option>
                        </select>
                    </div>
                    <div class="apg-field-group apg-field-third">
                        <label for="title_color"><?php esc_html_e('Title Color', 'aryana-project-gallery'); ?></label>
                        <input type="text" name="title_color" id="title_color" class="apg-color-picker" value="<?php echo esc_attr($settings['title_color']); ?>">
                    </div>
                </div>

                <div class="apg-field-group">
                    <label for="section_subtitle"><?php esc_html_e('Section Subtitle', 'aryana-project-gallery'); ?></label>
                    <input type="text" name="section_subtitle" id="section_subtitle" class="widefat" value="<?php echo esc_attr($settings['section_subtitle']); ?>">
                </div>

                <div class="apg-field-row">
                    <div class="apg-field-group apg-field-half">
                        <label for="subtitle_size"><?php esc_html_e('Subtitle Size (px)', 'aryana-project-gallery'); ?></label>
                        <input type="number" name="subtitle_size" id="subtitle_size" value="<?php echo esc_attr($settings['subtitle_size']); ?>" min="12" max="36">
                    </div>
                    <div class="apg-field-group apg-field-half">
                        <label for="subtitle_color"><?php esc_html_e('Subtitle Color', 'aryana-project-gallery'); ?></label>
                        <input type="text" name="subtitle_color" id="subtitle_color" class="apg-color-picker" value="<?php echo esc_attr($settings['subtitle_color']); ?>">
                    </div>
                </div>

                <div class="apg-field-group">
                    <label for="header_align"><?php esc_html_e('Header Alignment', 'aryana-project-gallery'); ?></label>
                    <select name="header_align" id="header_align">
                        <option value="center" <?php selected($settings['header_align'], 'center'); ?>><?php esc_html_e('Center', 'aryana-project-gallery'); ?></option>
                        <option value="left" <?php selected($settings['header_align'], 'left'); ?>><?php esc_html_e('Left', 'aryana-project-gallery'); ?></option>
                        <option value="right" <?php selected($settings['header_align'], 'right'); ?>><?php esc_html_e('Right', 'aryana-project-gallery'); ?></option>
                    </select>
                </div>
            </div>

            <!-- 2. Grid & Columns Layout -->
            <div class="apg-card">
                <h3 class="apg-card-title"><span class="dashicons dashicons-grid-view"></span> <?php esc_html_e('2. Grid Layout & Columns', 'aryana-project-gallery'); ?></h3>

                <div class="apg-field-row">
                    <div class="apg-field-group apg-field-third">
                        <label for="desktop_columns"><?php esc_html_e('Desktop Columns', 'aryana-project-gallery'); ?></label>
                        <select name="desktop_columns" id="desktop_columns">
                            <option value="2" <?php selected($settings['desktop_columns'], '2'); ?>>2 Columns</option>
                            <option value="3" <?php selected($settings['desktop_columns'], '3'); ?>>3 Columns</option>
                            <option value="4" <?php selected($settings['desktop_columns'], '4'); ?>>4 Columns (Default)</option>
                            <option value="5" <?php selected($settings['desktop_columns'], '5'); ?>>5 Columns</option>
                            <option value="6" <?php selected($settings['desktop_columns'], '6'); ?>>6 Columns</option>
                        </select>
                    </div>
                    <div class="apg-field-group apg-field-third">
                        <label for="tablet_columns"><?php esc_html_e('Tablet Columns', 'aryana-project-gallery'); ?></label>
                        <select name="tablet_columns" id="tablet_columns">
                            <option value="1" <?php selected($settings['tablet_columns'], '1'); ?>>1 Column</option>
                            <option value="2" <?php selected($settings['tablet_columns'], '2'); ?>>2 Columns (Default)</option>
                            <option value="3" <?php selected($settings['tablet_columns'], '3'); ?>>3 Columns</option>
                        </select>
                    </div>
                    <div class="apg-field-group apg-field-third">
                        <label for="mobile_columns"><?php esc_html_e('Mobile Columns', 'aryana-project-gallery'); ?></label>
                        <select name="mobile_columns" id="mobile_columns">
                            <option value="1" <?php selected($settings['mobile_columns'], '1'); ?>>1 Column (Default)</option>
                            <option value="2" <?php selected($settings['mobile_columns'], '2'); ?>>2 Columns</option>
                        </select>
                    </div>
                </div>

                <div class="apg-field-row">
                    <div class="apg-field-group apg-field-half">
                        <label for="grid_gap"><?php esc_html_e('Grid Gap (px)', 'aryana-project-gallery'); ?></label>
                        <input type="number" name="grid_gap" id="grid_gap" value="<?php echo esc_attr($settings['grid_gap']); ?>" min="0" max="60">
                    </div>
                    <div class="apg-field-group apg-field-half">
                        <label for="section_bg"><?php esc_html_e('Section Background Color', 'aryana-project-gallery'); ?></label>
                        <input type="text" name="section_bg" id="section_bg" class="apg-color-picker" value="<?php echo esc_attr($settings['section_bg']); ?>">
                    </div>
                </div>

                <div class="apg-field-row">
                    <div class="apg-field-group apg-field-half">
                        <label for="section_padding_top"><?php esc_html_e('Padding Top (px)', 'aryana-project-gallery'); ?></label>
                        <input type="number" name="section_padding_top" id="section_padding_top" value="<?php echo esc_attr($settings['section_padding_top']); ?>" min="0" max="150">
                    </div>
                    <div class="apg-field-group apg-field-half">
                        <label for="section_padding_bottom"><?php esc_html_e('Padding Bottom (px)', 'aryana-project-gallery'); ?></label>
                        <input type="number" name="section_padding_bottom" id="section_padding_bottom" value="<?php echo esc_attr($settings['section_padding_bottom']); ?>" min="0" max="150">
                    </div>
                </div>
            </div>

            <!-- 3. Featured Image Card Style -->
            <div class="apg-card">
                <h3 class="apg-card-title"><span class="dashicons dashicons-format-image"></span> <?php esc_html_e('3. Featured Image & Card Styles', 'aryana-project-gallery'); ?></h3>

                <div class="apg-field-row">
                    <div class="apg-field-group apg-field-half">
                        <label for="card_height"><?php esc_html_e('Card Height (px)', 'aryana-project-gallery'); ?></label>
                        <input type="number" name="card_height" id="card_height" value="<?php echo esc_attr($settings['card_height']); ?>" min="120" max="450">
                    </div>
                    <div class="apg-field-group apg-field-half">
                        <label for="card_radius"><?php esc_html_e('Card Border Radius (px)', 'aryana-project-gallery'); ?></label>
                        <input type="number" name="card_radius" id="card_radius" value="<?php echo esc_attr($settings['card_radius']); ?>" min="0" max="40">
                    </div>
                </div>

                <div class="apg-field-row">
                    <div class="apg-field-group apg-field-half">
                        <label for="normal_overlay_color"><?php esc_html_e('Normal Overlay Color (RGBA / Hex)', 'aryana-project-gallery'); ?></label>
                        <input type="text" name="normal_overlay_color" id="normal_overlay_color" value="<?php echo esc_attr($settings['normal_overlay_color']); ?>" class="widefat" placeholder="rgba(67, 79, 97, 0.70)">
                        <p class="description"><?php esc_html_e('Semi-transparent overlay over the featured image.', 'aryana-project-gallery'); ?></p>
                    </div>
                    <div class="apg-field-group apg-field-half">
                        <label for="hover_overlay_color"><?php esc_html_e('Hover Overlay Color (RGBA / Hex)', 'aryana-project-gallery'); ?></label>
                        <input type="text" name="hover_overlay_color" id="hover_overlay_color" value="<?php echo esc_attr($settings['hover_overlay_color']); ?>" class="widefat" placeholder="rgba(3, 139, 91, 0.75)">
                        <p class="description"><?php esc_html_e('Active green overlay color when hovered.', 'aryana-project-gallery'); ?></p>
                    </div>
                </div>

                <div class="apg-field-group">
                    <label>
                        <input type="checkbox" name="enable_zoom" value="yes" <?php checked($settings['enable_zoom'], 'yes'); ?>>
                        <strong><?php esc_html_e('Enable Subtle Image Zoom on Hover (Scale)', 'aryana-project-gallery'); ?></strong>
                    </label>
                </div>

                <div class="apg-field-group">
                    <label>
                        <input type="checkbox" name="show_hover_icon" value="yes" <?php checked($settings['show_hover_icon'], 'yes'); ?>>
                        <strong><?php esc_html_e('Show Magnifying Search Icon on Hover', 'aryana-project-gallery'); ?></strong>
                    </label>
                </div>

                <div class="apg-field-row">
                    <div class="apg-field-group apg-field-half">
                        <label for="hover_icon_color"><?php esc_html_e('Hover Icon Color', 'aryana-project-gallery'); ?></label>
                        <input type="text" name="hover_icon_color" id="hover_icon_color" class="apg-color-picker" value="<?php echo esc_attr($settings['hover_icon_color']); ?>">
                    </div>
                    <div class="apg-field-group apg-field-half">
                        <label for="hover_icon_border"><?php esc_html_e('Hover Icon Border Box Color', 'aryana-project-gallery'); ?></label>
                        <input type="text" name="hover_icon_border" id="hover_icon_border" class="apg-color-picker" value="<?php echo esc_attr($settings['hover_icon_border']); ?>">
                    </div>
                </div>
            </div>

            <!-- 4. Card Title Styles -->
            <div class="apg-card">
                <h3 class="apg-card-title"><span class="dashicons dashicons-editor-textcolor"></span> <?php esc_html_e('4. Card Title Styles', 'aryana-project-gallery'); ?></h3>

                <div class="apg-field-row">
                    <div class="apg-field-group apg-field-third">
                        <label for="card_title_size"><?php esc_html_e('Font Size (px)', 'aryana-project-gallery'); ?></label>
                        <input type="number" name="card_title_size" id="card_title_size" value="<?php echo esc_attr($settings['card_title_size']); ?>" min="12" max="32">
                    </div>
                    <div class="apg-field-group apg-field-third">
                        <label for="card_title_weight"><?php esc_html_e('Font Weight', 'aryana-project-gallery'); ?></label>
                        <select name="card_title_weight" id="card_title_weight">
                            <option value="500" <?php selected($settings['card_title_weight'], '500'); ?>>500 (Medium)</option>
                            <option value="600" <?php selected($settings['card_title_weight'], '600'); ?>>600 (Semi-Bold)</option>
                            <option value="700" <?php selected($settings['card_title_weight'], '700'); ?>>700 (Bold)</option>
                        </select>
                    </div>
                    <div class="apg-field-group apg-field-third">
                        <label for="card_title_color"><?php esc_html_e('Title Color', 'aryana-project-gallery'); ?></label>
                        <input type="text" name="card_title_color" id="card_title_color" class="apg-color-picker" value="<?php echo esc_attr($settings['card_title_color']); ?>">
                    </div>
                </div>

                <div class="apg-field-row">
                    <div class="apg-field-group apg-field-half">
                        <label for="card_title_transform"><?php esc_html_e('Text Transform', 'aryana-project-gallery'); ?></label>
                        <select name="card_title_transform" id="card_title_transform">
                            <option value="uppercase" <?php selected($settings['card_title_transform'], 'uppercase'); ?>>UPPERCASE</option>
                            <option value="capitalize" <?php selected($settings['card_title_transform'], 'capitalize'); ?>>Capitalize Each Word</option>
                            <option value="none" <?php selected($settings['card_title_transform'], 'none'); ?>>Normal (None)</option>
                        </select>
                    </div>
                    <div class="apg-field-group apg-field-half">
                        <label for="card_title_letter_spacing"><?php esc_html_e('Letter Spacing (px)', 'aryana-project-gallery'); ?></label>
                        <input type="number" name="card_title_letter_spacing" id="card_title_letter_spacing" step="0.1" value="<?php echo esc_attr($settings['card_title_letter_spacing']); ?>" min="0" max="5">
                    </div>
                </div>
            </div>

            <!-- 5. Lightbox Slider Styles -->
            <div class="apg-card apg-card-full">
                <h3 class="apg-card-title"><span class="dashicons dashicons-slides"></span> <?php esc_html_e('5. Popup Slider / Lightbox Modal Styles', 'aryana-project-gallery'); ?></h3>

                <div class="apg-field-row">
                    <div class="apg-field-group apg-field-third">
                        <label for="modal_bg"><?php esc_html_e('Modal Backdrop Color (RGBA / Hex)', 'aryana-project-gallery'); ?></label>
                        <input type="text" name="modal_bg" id="modal_bg" value="<?php echo esc_attr($settings['modal_bg']); ?>" class="widefat" placeholder="rgba(10, 15, 29, 0.92)">
                    </div>
                    <div class="apg-field-group apg-field-third">
                        <label for="slider_max_width"><?php esc_html_e('Slider Max Width (px)', 'aryana-project-gallery'); ?></label>
                        <input type="number" name="slider_max_width" id="slider_max_width" value="<?php echo esc_attr($settings['slider_max_width']); ?>" min="500" max="1600">
                    </div>
                    <div class="apg-field-group apg-field-third">
                        <label for="slider_max_height"><?php esc_html_e('Slider Max Height (px)', 'aryana-project-gallery'); ?></label>
                        <input type="number" name="slider_max_height" id="slider_max_height" value="<?php echo esc_attr($settings['slider_max_height']); ?>" min="350" max="950">
                    </div>
                </div>

                <div class="apg-field-row">
                    <div class="apg-field-group apg-field-third">
                        <label for="slider_fit"><?php esc_html_e('Slider Image Fit', 'aryana-project-gallery'); ?></label>
                        <select name="slider_fit" id="slider_fit">
                            <option value="contain" <?php selected($settings['slider_fit'], 'contain'); ?>><?php esc_html_e('Contain (Fit Entire Image Without Crop)', 'aryana-project-gallery'); ?></option>
                            <option value="cover" <?php selected($settings['slider_fit'], 'cover'); ?>><?php esc_html_e('Cover (Fill Entire Slide)', 'aryana-project-gallery'); ?></option>
                        </select>
                    </div>
                    <div class="apg-field-group apg-field-third">
                        <label for="arrow_bg"><?php esc_html_e('Arrow Background', 'aryana-project-gallery'); ?></label>
                        <input type="text" name="arrow_bg" id="arrow_bg" value="<?php echo esc_attr($settings['arrow_bg']); ?>" class="widefat" placeholder="rgba(0, 0, 0, 0.45)">
                    </div>
                    <div class="apg-field-group apg-field-third">
                        <label for="arrow_hover_bg"><?php esc_html_e('Arrow Hover Background', 'aryana-project-gallery'); ?></label>
                        <input type="text" name="arrow_hover_bg" id="arrow_hover_bg" class="apg-color-picker" value="<?php echo esc_attr($settings['arrow_hover_bg']); ?>">
                    </div>
                </div>

                <div class="apg-field-row">
                    <div class="apg-field-group apg-field-third">
                        <label>
                            <input type="checkbox" name="show_dots" value="yes" <?php checked($settings['show_dots'], 'yes'); ?>>
                            <?php esc_html_e('Show Indicator Dots at Bottom', 'aryana-project-gallery'); ?>
                        </label>
                    </div>
                    <div class="apg-field-group apg-field-third">
                        <label>
                            <input type="checkbox" name="show_counter" value="yes" <?php checked($settings['show_counter'], 'yes'); ?>>
                            <?php esc_html_e('Show Slide Counter (e.g. 1 / 4)', 'aryana-project-gallery'); ?>
                        </label>
                    </div>
                    <div class="apg-field-group apg-field-third">
                        <label>
                            <input type="checkbox" name="show_caption" value="yes" <?php checked($settings['show_caption'], 'yes'); ?>>
                            <?php esc_html_e('Show Project Name in Modal Header', 'aryana-project-gallery'); ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="apg-save-bar">
            <button type="submit" class="button button-primary button-hero">
                <span class="dashicons dashicons-saved"></span> <?php esc_html_e('Save All Style Changes', 'aryana-project-gallery'); ?>
            </button>
        </div>
    </form>
    <?php
}

