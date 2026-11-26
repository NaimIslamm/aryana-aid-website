<?php
/**
 * Elementor Widget Registration for Aryana Project Gallery
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('elementor/widgets/register', 'apg_register_elementor_widget');
function apg_register_elementor_widget($widgets_manager) {
    // Only proceed if Elementor Widget_Base exists
    if (!class_exists('\Elementor\Widget_Base')) {
        return;
    }

    class Aryana_Project_Gallery_Elementor_Widget extends \Elementor\Widget_Base {

        public function get_name() {
            return 'aryana_project_gallery';
        }

        public function get_title() {
            return esc_html__('Aryana Project Gallery', 'aryana-project-gallery');
        }

        public function get_icon() {
            return 'eicon-gallery-grid';
        }

        public function get_categories() {
            return ['general', 'basic'];
        }

        public function get_keywords() {
            return ['project', 'gallery', 'slider', 'modal', 'pictures', 'aryana'];
        }

        protected function register_controls() {
            $this->start_controls_section(
                'content_section',
                [
                    'label' => esc_html__('Gallery Info', 'aryana-project-gallery'),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
            );

            $this->add_control(
                'info_notice',
                [
                    'type' => \Elementor\Controls_Manager::RAW_HTML,
                    'raw' => sprintf(
                        __('Projects, photos, and styling are managed in the <a href="%s" target="_blank">Project Gallery Dashboard</a>.', 'aryana-project-gallery'),
                        admin_url('admin.php?page=aryana-project-gallery')
                    ),
                    'content_classes' => 'elementor-descriptor',
                ]
            );

            $this->end_controls_section();
        }

        protected function render() {
            echo do_shortcode('[aryana_project_gallery]');
        }
    }

    $widgets_manager->register(new Aryana_Project_Gallery_Elementor_Widget());
}

