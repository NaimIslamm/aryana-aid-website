<?php
/**
 * Frontend Rendering for Aryana Project Gallery
 */

if (!defined('ABSPATH')) {
    exit;
}

// Shortcode registrations
add_shortcode('aryana_project_gallery', 'apg_render_gallery_shortcode');
add_shortcode('project_gallery', 'apg_render_gallery_shortcode');

/**
 * Render Gallery Shortcode
 */
function apg_render_gallery_shortcode($atts = []) {
    // Enqueue registered frontend assets
    wp_enqueue_style('apg-style');
    wp_enqueue_script('apg-gallery');

    $all_projects = get_option('apg_projects', []);
    if (!is_array($all_projects)) {
        $all_projects = [];
    }
    $settings = wp_parse_args(get_option('apg_settings', []), apg_get_default_settings());

    // Filter only active projects
    $projects = array_values(array_filter($all_projects, function ($p) {
        return ($p['status'] ?? 'active') === 'active';
    }));

    if (empty($projects)) {
        return '';
    }

    // Sort projects by order
    usort($projects, function ($a, $b) {
        return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
    });

    // Build dynamic CSS styles based on dashboard settings
    $gallery_id = 'apg-gallery-' . uniqid();
    $custom_css = apg_generate_inline_css($gallery_id, $settings);

    ob_start();
    ?>
    <style id="<?php echo esc_attr($gallery_id); ?>-css">
        <?php echo $custom_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </style>

    <section id="<?php echo esc_attr($gallery_id); ?>" class="apg-gallery-section" aria-label="<?php echo esc_attr($settings['section_title']); ?>">
        <div class="apg-container">

            <?php if (($settings['show_header'] ?? 'yes') === 'yes') : ?>
                <div class="apg-header">
                    <?php if (!empty($settings['section_title'])) : ?>
                        <h2 class="apg-title"><?php echo esc_html($settings['section_title']); ?></h2>
                    <?php endif; ?>
                    <?php if (!empty($settings['section_subtitle'])) : ?>
                        <p class="apg-subtitle"><?php echo esc_html($settings['section_subtitle']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- 4-Column Responsive Grid -->
            <div class="apg-grid">
                <?php foreach ($projects as $index => $project) : 
                    $featured_img = !empty($project['featured_image']) ? $project['featured_image'] : '';
                    $slider_imgs = $project['gallery_images'] ?? [];
                    // If no slider images are specified, fallback to featured image
                    if (empty($slider_imgs) && !empty($featured_img)) {
                        $slider_imgs = [$featured_img];
                    }

                    // Prepare JSON data for the lightbox slider
                    $project_payload = htmlspecialchars(wp_json_encode([
                        'id' => $project['id'],
                        'title' => $project['title'],
                        'subtitle' => $project['subtitle'] ?? '',
                        'images' => $slider_imgs,
                    ]), ENT_QUOTES, 'UTF-8');
                ?>
                    <div class="apg-col">
                        <div class="apg-card-item <?php echo ($settings['enable_zoom'] ?? 'yes') === 'yes' ? 'apg-has-zoom' : ''; ?>"
                             tabindex="0"
                             role="button"
                             aria-label="<?php echo esc_attr(sprintf(__('View %s pictures gallery', 'aryana-project-gallery'), $project['title'])); ?>"
                             data-apg-project="<?php echo $project_payload; ?>">
                            
                            <!-- Background Image Container -->
                            <div class="apg-card-bg" style="background-image: url('<?php echo esc_url($featured_img); ?>');"></div>

                            <!-- Overlay with Hover Green Transition & Search Magnifier -->
                            <div class="apg-card-overlay">
                                <?php if (($settings['show_hover_icon'] ?? 'yes') === 'yes') : ?>
                                    <div class="apg-hover-icon-box" aria-hidden="true">
                                        <svg class="apg-svg-icon" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="11" cy="11" r="8"></circle>
                                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                        </svg>
                                    </div>
                                <?php endif; ?>

                                <div class="apg-card-info">
                                    <h3 class="apg-card-title"><?php echo esc_html($project['title']); ?></h3>
                                    <?php if (!empty($project['subtitle'])) : ?>
                                        <span class="apg-card-sub"><?php echo esc_html($project['subtitle']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </section>

    <!-- Global Lightbox Modal (Initialized once per page) -->
    <?php apg_render_lightbox_markup(); ?>

    <?php
    return ob_get_clean();
}

/**
 * Generate Scoped Dynamic CSS from Dashboard Settings
 */
function apg_generate_inline_css($gallery_id, $s) {
    $gid = '#' . $gallery_id;
    $d_cols = intval($s['desktop_columns'] ?? 4);
    $t_cols = intval($s['tablet_columns'] ?? 2);
    $m_cols = intval($s['mobile_columns'] ?? 1);

    return "
    $gid.apg-gallery-section {
        background-color: {$s['section_bg']};
        padding-top: {$s['section_padding_top']}px;
        padding-bottom: {$s['section_padding_bottom']}px;
    }
    $gid .apg-header {
        text-align: {$s['header_align']};
        margin-bottom: 30px;
    }
    $gid .apg-title {
        font-size: {$s['title_size']}px;
        font-weight: {$s['title_weight']};
        color: {$s['title_color']};
        margin: 0 0 8px 0;
        line-height: 1.25;
    }
    $gid .apg-subtitle {
        font-size: {$s['subtitle_size']}px;
        color: {$s['subtitle_color']};
        margin: 0;
        line-height: 1.5;
    }
    $gid .apg-grid {
        display: grid;
        grid-template-columns: repeat($d_cols, minmax(0, 1fr));
        gap: {$s['grid_gap']}px;
    }
    @media (max-width: 991px) {
        $gid .apg-grid {
            grid-template-columns: repeat($t_cols, minmax(0, 1fr));
        }
    }
    @media (max-width: 640px) {
        $gid .apg-grid {
            grid-template-columns: repeat($m_cols, minmax(0, 1fr));
        }
    }
    $gid .apg-card-item {
        height: {$s['card_height']}px;
        border-radius: {$s['card_radius']}px;
    }
    $gid .apg-card-overlay {
        background-color: {$s['normal_overlay_color']};
        border-radius: {$s['card_radius']}px;
    }
    $gid .apg-card-item:hover .apg-card-overlay,
    $gid .apg-card-item:focus .apg-card-overlay {
        background-color: {$s['hover_overlay_color']};
    }
    $gid .apg-hover-icon-box {
        border-color: {$s['hover_icon_border']};
        color: {$s['hover_icon_color']};
    }
    $gid .apg-card-title {
        font-size: {$s['card_title_size']}px;
        font-weight: {$s['card_title_weight']};
        color: {$s['card_title_color']};
        text-transform: {$s['card_title_transform']};
        letter-spacing: {$s['card_title_letter_spacing']}px;
    }
    @media (max-width: 767px) {
        $gid.apg-gallery-section {
            padding-top: 10px !important;
            padding-bottom: 15px !important;
        }
        $gid .apg-header {
            margin-bottom: 16px !important;
        }
        $gid .apg-title {
            font-size: 26px !important;
            margin: 0 0 6px 0 !important;
        }
        $gid .apg-subtitle {
            font-size: 14px !important;
        }
    }
    ";
}

/**
 * Render Lightbox Modal Container in DOM
 */
function apg_render_lightbox_markup() {
    static $rendered = false;
    if ($rendered) {
        return;
    }
    $rendered = true;

    $settings = wp_parse_args(get_option('apg_settings', []), apg_get_default_settings());
    ?>
    <div id="apg-modal" class="apg-modal" aria-hidden="true" role="dialog" aria-modal="true" style="--apg-modal-bg: <?php echo esc_attr($settings['modal_bg']); ?>; --apg-arrow-bg: <?php echo esc_attr($settings['arrow_bg']); ?>; --apg-arrow-hover-bg: <?php echo esc_attr($settings['arrow_hover_bg']); ?>; --apg-max-w: <?php echo esc_attr($settings['slider_max_width']); ?>px; --apg-max-h: <?php echo esc_attr($settings['slider_max_height']); ?>px; --apg-img-fit: <?php echo esc_attr($settings['slider_fit']); ?>;">
        <div class="apg-modal-backdrop" data-apg-close></div>
        
        <div class="apg-modal-container">
            <!-- Modal Header Bar -->
            <div class="apg-modal-topbar">
                <div class="apg-modal-title-wrap">
                    <?php if (($settings['show_caption'] ?? 'yes') === 'yes') : ?>
                        <h4 class="apg-modal-title" id="apg-modal-title">Project Name</h4>
                    <?php endif; ?>
                </div>

                <div class="apg-modal-topbar-right">
                    <?php if (($settings['show_counter'] ?? 'yes') === 'yes') : ?>
                        <span class="apg-modal-counter" id="apg-modal-counter">1 / 1</span>
                    <?php endif; ?>
                    <button type="button" class="apg-modal-close-btn" data-apg-close aria-label="<?php esc_attr_e('Close Gallery', 'aryana-project-gallery'); ?>">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            </div>

            <!-- Main Slider Stage -->
            <div class="apg-modal-stage">
                <!-- Prev Button -->
                <button type="button" class="apg-nav-btn apg-nav-prev" id="apg-prev-btn" aria-label="<?php esc_attr_e('Previous Image', 'aryana-project-gallery'); ?>">
                    <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>

                <!-- Current Slide Image Container -->
                <div class="apg-slide-viewer" id="apg-slide-viewer">
                    <div class="apg-slide-loader" id="apg-slide-loader"></div>
                    <img src="" alt="Project photo" id="apg-modal-img" class="apg-modal-image">
                </div>

                <!-- Next Button -->
                <button type="button" class="apg-nav-btn apg-nav-next" id="apg-next-btn" aria-label="<?php esc_attr_e('Next Image', 'aryana-project-gallery'); ?>">
                    <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>

            <!-- Bottom dots removed per user request -->
        </div>
    </div>
    <?php
}

