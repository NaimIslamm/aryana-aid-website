<?php
/**
 * Plugin Name: Aryana Aid Theme Customizations & 1:1 Muslim Aid Layout Styles
 * Description: Injects Google Fonts (Playfair Display & Outfit) and custom CSS to match Muslim Aid reference design 1:1.
 * Version: 1.0.0
 * Author: Antigravity
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Performance & Speed Optimizations (LCP Preload, Google Fonts Preconnect, Script Deferral)
 */
add_action('wp_head', function () {
    // 1. Preconnect to Google Fonts CDN
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";

    // 2. High-priority LCP Preload for Hero Banner (serves WebP on modern browsers)
    if (is_front_page()) {
        $hero_webp = content_url('/uploads/2026/10/afghanistan-relief-hero.jpg.webp');
        echo '<link rel="preload" as="image" href="' . esc_url($hero_webp) . '" type="image/webp" fetchpriority="high">' . "\n";
    }
}, 1);

/**
 * Safely defer non-critical scripts to eliminate render-blocking JS without touching jQuery core
 */
add_filter('script_loader_tag', function ($tag, $handle) {
    if (is_admin()) {
        return $tag;
    }
    if (strpos($tag, 'defer') !== false || strpos($tag, 'async') !== false) {
        return $tag;
    }

    $defer_scripts = [
        'jquery-migrate',
        'v4-shims',
        'wp-whatsapp',
        'google-invisible-recaptcha'
    ];

    if (in_array($handle, $defer_scripts, true)) {
        return str_replace(' src=', ' defer src=', $tag);
    }

    return $tag;
}, 10, 2);

add_action('wp_enqueue_scripts', function () {
    // Enqueue Google Fonts: Playfair Display (for 1:1 Muslim Aid Hero typography) & Outfit
    wp_enqueue_style(
        'aryana-google-fonts',
        'https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,700;0,900;1,700;1,900&display=swap',
        [],
        null
    );
}, 20);

add_action('wp_head', function () {
?>
<style id="aryana-aid-hero-1to1-styles">
/* 1:1 Muslim Aid Design Reproduction Styles */
:root {
    --aa-magenta: #d81b60;
    --aa-magenta-dark: #c2185b;
    --aa-green: #2e7d32;
    --aa-dark-green: #1b5e20;
    --aa-emerald: #007a3d;
    --aa-slate: #0f172a;
    --aa-grey-border: #e2e8f0;
    --aa-grey-bg: #f8fafc;
}

/* Ensure base body and typography render smoothly without overriding Elementor Global Fonts */
body, input, select, textarea {
    font-family: 'Outfit', sans-serif;
    -webkit-font-smoothing: antialiased;
}

/* Hero Section Base */
.hero-1to1-section {
    position: relative !important;
    overflow: visible !important;
    background-image: url('<?php echo content_url('/uploads/2026/10/afghanistan-relief-hero.jpg'); ?>') !important;
    background-size: cover !important;
    background-position: center 38% !important;
    background-repeat: no-repeat !important;
}

/* Massive Display Serif Font for Hero Flanking */
.hero-massive-serif .elementor-heading-title {
    font-family: 'Playfair Display', Georgia, serif !important;
    font-weight: 900 !important;
    letter-spacing: 2px !important;
    text-transform: uppercase !important;
    line-height: 0.95 !important;
    text-shadow: 0 4px 25px rgba(0, 0, 0, 0.75), 0 2px 8px rgba(0, 0, 0, 0.9) !important;
    color: #ffffff !important;
}

/* Left Hero Donate Now Pill Button */
.hero-donate-pill .elementor-button {
    background-color: var(--aa-magenta) !important;
    color: #ffffff !important;
    border-radius: 40px !important;
    font-family: 'Outfit', sans-serif !important;
    font-weight: 800 !important;
    box-shadow: 0 8px 25px rgba(216, 27, 96, 0.6) !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    letter-spacing: 0.5px !important;
}
.hero-donate-pill .elementor-button:hover {
    background-color: var(--aa-magenta-dark) !important;
    transform: translateY(-2px) scale(1.03) !important;
    box-shadow: 0 12px 30px rgba(216, 27, 96, 0.75) !important;
}

/* Right Hero Relief Green Box Badge */
.hero-relief-badge .elementor-button {
    background-color: var(--aa-green) !important;
    color: #ffffff !important;
    border-radius: 4px !important;
    font-family: 'Playfair Display', Georgia, serif !important;
    font-weight: 900 !important;
    letter-spacing: 3px !important;
    text-transform: uppercase !important;
    box-shadow: 0 8px 25px rgba(46, 125, 50, 0.5) !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
}
.hero-relief-badge .elementor-button:hover {
    background-color: var(--aa-dark-green) !important;
    transform: translateY(-2px) scale(1.03) !important;
    box-shadow: 0 12px 30px rgba(46, 125, 50, 0.65) !important;
}

/* Left Carousel Arrow (<) */
.hero-carousel-left .elementor-button {
    background-color: rgba(0, 0, 0, 0.45) !important;
    color: #ffffff !important;
    border-radius: 0 4px 4px 0 !important;
    padding: 16px 12px !important;
    font-size: 20px !important;
    font-weight: 700 !important;
    border: none !important;
    cursor: pointer !important;
    transition: background-color 0.2s ease !important;
}
.hero-carousel-left .elementor-button:hover {
    background-color: rgba(0, 0, 0, 0.75) !important;
}

/* Right Carousel Arrow (>) */
.hero-carousel-right .elementor-button {
    background-color: #1b5e20 !important;
    color: #ffffff !important;
    border-radius: 4px 0 0 4px !important;
    padding: 16px 12px !important;
    font-size: 20px !important;
    font-weight: 700 !important;
    border: none !important;
    cursor: pointer !important;
    transition: background-color 0.2s ease !important;
}
.hero-carousel-right .elementor-button:hover {
    background-color: #0d3813 !important;
}

/* Floating Quick Donation Bar */
.floating-quick-bar {
    background: #ffffff !important;
    border-radius: 14px !important;
    box-shadow: 0 14px 45px rgba(0, 0, 0, 0.16), 0 4px 12px rgba(0, 0, 0, 0.08) !important;
    padding: 12px 18px !important;
    position: relative !important;
    z-index: 40 !important;
    border: 1px solid rgba(226, 232, 240, 0.8) !important;
}

/* Quick Donation Select Pills */
.quick-pill-select .elementor-button {
    background-color: #ffffff !important;
    color: #0f172a !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
    font-family: 'Outfit', sans-serif !important;
    font-weight: 600 !important;
    font-size: 14px !important;
    padding: 13px 18px !important;
    transition: all 0.2s ease !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
}
.quick-pill-select .elementor-button:hover {
    border-color: #038b5c !important;
    background-color: #f8fafc !important;
}

/* Quick Donate CTA (Magenta) */
.quick-pill-donate .elementor-button {
    background-color: var(--aa-magenta) !important;
    color: #ffffff !important;
    border-radius: 8px !important;
    font-family: 'Outfit', sans-serif !important;
    font-weight: 700 !important;
    font-size: 15px !important;
    padding: 13px 26px !important;
    box-shadow: 0 4px 15px rgba(216, 27, 96, 0.4) !important;
    transition: all 0.2s ease !important;
}
.quick-pill-donate .elementor-button:hover {
    background-color: var(--aa-magenta-dark) !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 6px 20px rgba(216, 27, 96, 0.55) !important;
}

/* Quick Cart Pill (Green with 2 lines) */
.quick-pill-cart .elementor-button {
    background-color: var(--aa-emerald) !important;
    color: #ffffff !important;
    border-radius: 8px !important;
    font-family: 'Outfit', sans-serif !important;
    font-weight: 700 !important;
    font-size: 13px !important;
    line-height: 1.25 !important;
    padding: 8px 20px !important;
    box-shadow: 0 4px 15px rgba(0, 122, 61, 0.35) !important;
    text-align: center !important;
    white-space: pre-line !important;
    transition: all 0.2s ease !important;
}
.quick-pill-cart .elementor-button .elementor-button-text {
    white-space: pre-line !important;
    display: inline-block !important;
}
.quick-pill-cart .elementor-button:hover {
    background-color: #005f30 !important;
    transform: translateY(-1px) !important;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .hero-massive-serif .elementor-heading-title {
        font-size: 80px !important;
    }
    .floating-quick-bar {
        flex-wrap: wrap !important;
        gap: 8px !important;
    }
}

@media (max-width: 767px) {
    .hero-massive-serif .elementor-heading-title {
        font-size: 52px !important;
    }
    .hero-donate-pill .elementor-button {
        font-size: 16px !important;
        padding: 10px 24px !important;
    }
    .hero-relief-badge .elementor-button {
        font-size: 18px !important;
        padding: 8px 20px !important;
    }
}

/* ==========================================================================
   MOBILE MENU & ACCORDION OVERLAP FIX (MOBILE ONLY <= 1024px)
   ========================================================================== */
@media (max-width: 1024px) {
    #mobile_menu .elementor-element-38aa213 {
        position: relative !important;
        z-index: 99999 !important;
    }
    #mobile_menu .elementor-nav-menu--dropdown,
    #mobile_menu .elementor-nav-menu__container.elementor-nav-menu--dropdown {
        position: absolute !important;
        z-index: 999999 !important;
    }
    #mobile_menu .elementor-element-42945d7 {
        position: relative !important;
        z-index: 90 !important;
    }

    /* Quick Donate Accordion - Left Drop Icon & Right Chevron Icon */
    .elementor-element-5882398 .e-n-accordion-item-title-header {
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
    }
    .elementor-element-5882398 .e-n-accordion-item-title-header::before {
        content: '' !important;
        display: inline-block !important;
        width: 14px !important;
        height: 14px !important;
        background-color: #a3e635 !important;
        -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z'/%3E%3C/svg%3E") no-repeat center / contain !important;
        mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z'/%3E%3C/svg%3E") no-repeat center / contain !important;
        flex-shrink: 0 !important;
    }
    .elementor-element-5882398 span.e-n-accordion-item-title-icon {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin-bottom: 2px !important;
    }
}
</style>
<?php
}, 100);

/**
 * Mobile UX: Automatically close Quick Donate accordion when mobile menu opens
 */
add_action('wp_footer', function () {
?>
<script id="aryana-mobile-menu-accordion-sync">
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('click', function(e) {
            var toggle = e.target.closest('#mobile_menu .elementor-menu-toggle');
            if (toggle) {
                setTimeout(function() {
                    var isMenuOpen = toggle.classList.contains('elementor-active') || toggle.getAttribute('aria-expanded') === 'true';
                    if (isMenuOpen) {
                        var accordions = document.querySelectorAll('#mobile_menu .e-n-accordion-item[open]');
                        accordions.forEach(function(item) {
                            item.removeAttribute('open');
                        });
                        var activeAccordions = document.querySelectorAll('#mobile_menu .elementor-tab-title.elementor-active');
                        activeAccordions.forEach(function(item) {
                            item.click();
                        });
                    }
                }, 50);
            }

            var accTitle = e.target.closest('#mobile_menu .e-n-accordion-item-title, #mobile_menu .elementor-tab-title');
            if (accTitle) {
                var openMenuToggle = document.querySelector('#mobile_menu .elementor-menu-toggle.elementor-active');
                if (openMenuToggle) {
                    openMenuToggle.click();
                }
            }
        });
    });
})();
</script>
<?php
}, 999);
