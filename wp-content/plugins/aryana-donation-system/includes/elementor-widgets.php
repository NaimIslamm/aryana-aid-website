<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('elementor/widgets/register', function ($widgets_manager) {
    if (!class_exists('\Elementor\Widget_Base')) {
        return;
    }

    // 1. Quick Donate Bar Widget
    class ADS_Quick_Donate_Widget extends \Elementor\Widget_Base {
        public function get_name() { return 'ads_quick_donate_bar'; }
        public function get_title() { return 'Quick Donate Bar'; }
        public function get_icon() { return 'eicon-form-horizontal'; }
        public function get_categories() { return ['general', 'basic']; }

        protected function register_controls() {
            $this->start_controls_section('content_section', [
                'label' => 'Settings',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]);
            $this->add_control('bar_bg', [
                'label' => 'Bar Background Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#007a3d',
            ]);
            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            echo do_shortcode('[quick_donate_bar]');
        }
    }

    // 2. Project Donation Card Widget
    class ADS_Project_Donation_Card_Widget extends \Elementor\Widget_Base {
        public function get_name() { return 'ads_project_donation_card'; }
        public function get_title() { return 'Project Donation Card'; }
        public function get_icon() { return 'eicon-price-table'; }
        public function get_categories() { return ['general', 'basic']; }

        protected function register_controls() {
            $this->start_controls_section('content_section', [
                'label' => 'Campaign & Amounts',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]);

            $this->add_control('project_name', [
                'label' => 'Project Name',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Water - Well',
                'description' => 'Matches master project name for checkout.',
            ]);

            $this->add_control('card_heading', [
                'label' => 'Card Heading',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Give Water, Save Lives',
            ]);

            $repeater = new \Elementor\Repeater();
            $repeater->add_control('amount', [
                'label' => 'Amount (£)',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 600,
            ]);
            $repeater->add_control('label', [
                'label' => 'Button Sub-label',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Water Pump',
            ]);
            $repeater->add_control('impact', [
                'label' => 'Impact Description',
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => 'Could provide clean drinking water to a community in an emergency.',
            ]);

            $this->add_control('amounts_list', [
                'label' => 'Donation Preset Buttons',
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [
                    ['amount' => 600, 'label' => 'Water Pump', 'impact' => 'Provides a manual water hand-pump providing fresh water.'],
                    ['amount' => 1200, 'label' => 'Water Well', 'impact' => 'Installs a deep solar water well for families in Afghanistan.'],
                    ['amount' => 3600, 'label' => 'Community Tank', 'impact' => 'Builds a high-capacity solar water storage well for a whole village.'],
                ],
                'title_field' => '£{{{ amount }}} - {{{ label }}}',
            ]);

            $this->end_controls_section();

            // Button Section
            $this->start_controls_section('section_button', [
                'label' => esc_html__('Button', 'elementor'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]);

            $this->add_responsive_control('position', [
                'label' => esc_html__('Position', 'elementor'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'elementor'),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'elementor'),
                        'icon' => 'eicon-h-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'elementor'),
                        'icon' => 'eicon-h-align-right',
                    ],
                    'justify' => [
                        'title' => esc_html__('Justified', 'elementor'),
                        'icon' => 'eicon-h-align-stretch',
                    ],
                ],
                'default' => 'justify',
                'prefix_class' => 'elementor%s-align-',
                'selectors_dictionary' => [
                    'left'    => 'justify-content: flex-start; text-align: left;',
                    'center'  => 'justify-content: center; text-align: center;',
                    'right'   => 'justify-content: flex-end; text-align: right;',
                    'justify' => 'justify-content: stretch; text-align: center;',
                ],
                'selectors' => [
                    '{{WRAPPER}} .ads-card-btn-wrap' => 'display: flex; {{VALUE}}',
                    '{{WRAPPER}}.elementor-align-left .ads-card-donate-btn, {{WRAPPER}}.elementor-align-center .ads-card-donate-btn, {{WRAPPER}}.elementor-align-right .ads-card-donate-btn' => 'width: auto !important; display: inline-flex !important;',
                    '{{WRAPPER}}.elementor-align-justify .ads-card-donate-btn' => 'width: 100% !important; display: flex !important;',
                    '{{WRAPPER}}.elementor-align-tablet-left .ads-card-donate-btn, {{WRAPPER}}.elementor-align-tablet-center .ads-card-donate-btn, {{WRAPPER}}.elementor-align-tablet-right .ads-card-donate-btn' => 'width: auto !important; display: inline-flex !important;',
                    '{{WRAPPER}}.elementor-align-tablet-justify .ads-card-donate-btn' => 'width: 100% !important; display: flex !important;',
                    '{{WRAPPER}}.elementor-align-mobile-left .ads-card-donate-btn, {{WRAPPER}}.elementor-align-mobile-center .ads-card-donate-btn, {{WRAPPER}}.elementor-align-mobile-right .ads-card-donate-btn' => 'width: auto !important; display: inline-flex !important;',
                    '{{WRAPPER}}.elementor-align-mobile-justify .ads-card-donate-btn' => 'width: 100% !important; display: flex !important;',
                ],
            ]);

            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            $project = esc_attr($settings['project_name']);
            $heading = esc_attr($settings['card_heading']);
            
            $amounts_encoded = htmlspecialchars(json_encode($settings['amounts_list']), ENT_QUOTES, 'UTF-8');
            echo do_shortcode("[project_donation_card project=\"{$project}\" heading=\"{$heading}\" items_json='{$amounts_encoded}']");
        }
    }

    // 3. Master Checkout Widget
    class ADS_Master_Checkout_Widget extends \Elementor\Widget_Base {
        public function get_name() { return 'ads_master_checkout'; }
        public function get_title() { return 'Master Donation Checkout'; }
        public function get_icon() { return 'eicon-cart'; }
        public function get_categories() { return ['general', 'basic']; }

        protected function render() {
            echo do_shortcode('[aryana_donation_form]');
        }
    }

    // 4. Campaign Donation Box Widget
    class ADS_Campaign_Donation_Box_Widget extends \Elementor\Widget_Base {
        public function get_name() { return 'ads_campaign_donation_box'; }
        public function get_title() { return 'Campaign Donation Box'; }
        public function get_icon() { return 'eicon-form-vertical'; }
        public function get_categories() { return ['general', 'basic']; }
        public function get_style_depends() { return ['ads-donation-css', 'ads-modern-donation-css', 'ads-google-fonts']; }
        public function get_script_depends() { return ['ads-donation-js']; }

        protected function register_controls() {
            // TAB CONTENT
            $this->start_controls_section('content_section', [
                'label' => 'Campaign Selection',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]);

            $campaign_options = ['0' => '⚡ Auto-Detect (Current Project or Most Recent)'];
            $campaigns = get_posts([
                'post_type' => 'ads_campaign',
                'numberposts' => 100,
                'post_status' => 'publish',
            ]);
            foreach ($campaigns as $c) {
                $campaign_options[(string)$c->ID] = $c->post_title . " (ID: {$c->ID})";
            }

            $this->add_control('campaign_id', [
                'label' => 'Select Campaign',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $campaign_options,
                'default' => '0',
                'description' => 'Pick a specific campaign or leave Auto-Detect to automatically link to the current appeal page.',
            ]);

            $this->end_controls_section();

            // ==========================================
            // STYLE TAB 1: CARD CONTAINER (THE BOX)
            // ==========================================
            $this->start_controls_section('card_style_section', [
                'label' => 'Card Container (The Box)',
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]);

            $this->add_control('card_bg_color', [
                'label'     => 'Card Background Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#00473e',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-card' => 'background-color: {{VALUE}} !important; background: {{VALUE}} !important;',
                ],
            ]);

            $this->add_responsive_control('card_width', [
                'label'      => 'Width',
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vw'],
                'range'      => [
                    'px' => ['min' => 200, 'max' => 1400],
                    '%'  => ['min' => 10, 'max' => 100],
                    'vw' => ['min' => 10, 'max' => 100],
                ],
                'selectors'  => [
                    '{{WRAPPER}}' => 'width: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .elementor-widget-container' => 'width: 100%;',
                    '{{WRAPPER}} .aryana-donation-wrapper' => 'width: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .ads-campaign-card' => 'width: {{SIZE}}{{UNIT}} !important;',
                ],
            ]);

            $this->add_responsive_control('card_max_width', [
                'label'      => 'Maximum Width',
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vw'],
                'range'      => [
                    'px' => ['min' => 200, 'max' => 1400],
                    '%'  => ['min' => 10, 'max' => 100],
                    'vw' => ['min' => 10, 'max' => 100],
                ],
                'selectors'  => [
                    '{{WRAPPER}}' => 'max-width: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .elementor-widget-container' => 'width: 100%;',
                    '{{WRAPPER}} .aryana-donation-wrapper' => 'max-width: {{SIZE}}{{UNIT}} !important; width: 100%;',
                    '{{WRAPPER}} .ads-campaign-card' => 'max-width: {{SIZE}}{{UNIT}} !important; width: 100%;',
                ],
            ]);

            $this->add_responsive_control('card_alignment', [
                'label'   => 'Alignment',
                'type'    => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left'   => [
                        'title' => 'Left',
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => 'Center',
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'right'  => [
                        'title' => 'Right',
                        'icon'  => 'eicon-text-align-right',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-widget-container' => 'display: flex; justify-content: {{VALUE}};',
                    '{{WRAPPER}} .aryana-donation-wrapper' => 'display: flex; justify-content: {{VALUE}};',
                ],
                'selectors_dictionary' => [
                    'left'   => 'flex-start',
                    'center' => 'center',
                    'right'  => 'flex-end',
                ],
            ]);

            $this->add_responsive_control('card_padding', [
                'label'      => 'Padding',
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .ads-campaign-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]);

            $this->add_group_control(
                \Elementor\Group_Control_Border::get_type(),
                [
                    'name'     => 'card_border',
                    'selector' => '{{WRAPPER}} .ads-campaign-card',
                ]
            );

            $this->add_responsive_control('card_border_radius', [
                'label'      => 'Border Radius',
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .ads-campaign-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]);

            $this->add_group_control(
                \Elementor\Group_Control_Box_Shadow::get_type(),
                [
                    'name'     => 'card_box_shadow',
                    'selector' => '{{WRAPPER}} .ads-campaign-card',
                ]
            );

            $this->end_controls_section();

            // ==========================================
            // STYLE TAB: CARD HEADER & TITLES
            // ==========================================
            $this->start_controls_section('header_style_section', [
                'label' => 'Card Header & Titles',
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]);

            $this->add_control('script_title_color', [
                'label'     => 'Script Sub-heading Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#F5BA67',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-script-title' => 'color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name'     => 'script_title_typography',
                    'label'    => 'Script Typography',
                    'selector' => '{{WRAPPER}} .ads-campaign-script-title',
                ]
            );

            $this->add_control('heading_color', [
                'label'     => 'Campaign Heading Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-heading' => 'color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name'     => 'heading_typography',
                    'label'    => 'Campaign Heading Typography',
                    'selector' => '{{WRAPPER}} .ads-campaign-heading',
                ]
            );

            $this->end_controls_section();

            // ==========================================
            // STYLE TAB 2: FREQUENCY TOGGLE (Single / Regular)
            // ==========================================
            $this->start_controls_section('freq_style_section', [
                'label' => 'Payment Frequency Switcher',
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]);

            $this->add_control('freq_bar_bg', [
                'label'     => 'Track Background Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => 'transparent',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-freq-toggle' => 'background-color: {{VALUE}} !important; background: {{VALUE}} !important;',
                ],
            ]);

            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name'     => 'freq_typography',
                    'label'    => 'Typography',
                    'selector' => '{{WRAPPER}} .ads-campaign-freq-btn',
                ]
            );

            $this->start_controls_tabs('freq_tabs');

            // Active Tab
            $this->start_controls_tab('freq_tab_active', ['label' => 'Active Tab']);

            $this->add_control('freq_active_bg', [
                'label'     => 'Active Background Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#00875a',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-freq-btn.active' => 'background-color: {{VALUE}} !important; border-color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('freq_active_color', [
                'label'     => 'Active Text Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-freq-btn.active' => 'color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_group_control(
                \Elementor\Group_Control_Box_Shadow::get_type(),
                [
                    'name'     => 'freq_active_shadow',
                    'selector' => '{{WRAPPER}} .ads-campaign-freq-btn.active',
                ]
            );

            $this->end_controls_tab();

            // Inactive Tab
            $this->start_controls_tab('freq_tab_inactive', ['label' => 'Inactive Tab']);

            $this->add_control('freq_inactive_bg', [
                'label'     => 'Inactive Background Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => 'rgba(0, 48, 40, 0.55)',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-freq-btn:not(.active)' => 'background-color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('freq_inactive_color', [
                'label'     => 'Inactive Text Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-freq-btn:not(.active)' => 'color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_group_control(
                \Elementor\Group_Control_Box_Shadow::get_type(),
                [
                    'name'     => 'freq_inactive_shadow',
                    'selector' => '{{WRAPPER}} .ads-campaign-freq-btn:not(.active)',
                ]
            );

            $this->add_control('freq_inactive_hover_color', [
                'label'     => 'Hover Text Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#0f172a',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-freq-btn:not(.active):hover' => 'color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('freq_inactive_hover_bg', [
                'label'     => 'Hover Background',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => 'rgba(0,0,0,0.04)',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-freq-btn:not(.active):hover' => 'background-color: {{VALUE}} !important;',
                ],
            ]);

            $this->end_controls_tab();
            $this->end_controls_tabs();

            $this->add_responsive_control('freq_border_radius', [
                'label'      => 'Border Radius',
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .ads-campaign-freq-toggle, {{WRAPPER}} .ads-campaign-freq-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]);

            $this->add_responsive_control('freq_padding', [
                'label'      => 'Button Padding',
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px'],
                'selectors'  => [
                    '{{WRAPPER}} .ads-campaign-freq-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]);

            $this->end_controls_section();

            // ==========================================
            // STYLE TAB 3: PRESET AMOUNT PILLS
            // ==========================================
            $this->start_controls_section('pills_style_section', [
                'label' => 'Preset Amount Pills',
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]);

            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name'     => 'pills_amount_typography',
                    'label'    => 'Amount Typography',
                    'selector' => '{{WRAPPER}} .ads-campaign-pill, {{WRAPPER}} .ads-campaign-pill .ads-pill-amount',
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name'     => 'pills_sub_typography',
                    'label'    => 'Subtitle Typography',
                    'selector' => '{{WRAPPER}} .ads-campaign-pill .ads-pill-sub',
                ]
            );

            $this->start_controls_tabs('pills_state_tabs');

            // Active Pill
            $this->start_controls_tab('pills_tab_active', ['label' => 'Active Pill']);

            $this->add_control('pill_active_bg', [
                'label'     => 'Active Background Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-pill.active' => 'background-color: {{VALUE}} !important; background: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('pill_active_color', [
                'label'     => 'Active Text Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#00473e',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-pill.active, {{WRAPPER}} .ads-campaign-pill.active .ads-pill-amount, {{WRAPPER}} .ads-campaign-pill.active .ads-pill-sub' => 'color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('pill_active_border', [
                'label'     => 'Active Border Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-pill.active' => 'border-color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_group_control(
                \Elementor\Group_Control_Box_Shadow::get_type(),
                [
                    'name'     => 'pill_active_shadow',
                    'selector' => '{{WRAPPER}} .ads-campaign-pill.active',
                ]
            );

            $this->end_controls_tab();

            // Inactive Pill
            $this->start_controls_tab('pills_tab_inactive', ['label' => 'Inactive Pill']);

            $this->add_control('pill_inactive_bg', [
                'label'     => 'Background Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => 'transparent',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-pill:not(.active)' => 'background-color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('pill_inactive_color', [
                'label'     => 'Text Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-pill:not(.active), {{WRAPPER}} .ads-campaign-pill:not(.active) .ads-pill-amount, {{WRAPPER}} .ads-campaign-pill:not(.active) .ads-pill-sub' => 'color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('pill_inactive_border', [
                'label'     => 'Border Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#00875a',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-pill:not(.active)' => 'border-color: {{VALUE}} !important;',
                ],
            ]);

            $this->end_controls_tab();

            // Hover Pill
            $this->start_controls_tab('pills_tab_hover', ['label' => 'Hover']);

            $this->add_control('pill_hover_bg', [
                'label'     => 'Hover Background Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => 'rgba(255, 255, 255, 0.1)',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-pill:not(.active):hover' => 'background-color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('pill_hover_color', [
                'label'     => 'Hover Text Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-pill:not(.active):hover, {{WRAPPER}} .ads-campaign-pill:not(.active):hover .ads-pill-amount, {{WRAPPER}} .ads-campaign-pill:not(.active):hover .ads-pill-sub' => 'color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('pill_hover_border', [
                'label'     => 'Hover Border Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-pill:not(.active):hover' => 'border-color: {{VALUE}} !important;',
                ],
            ]);

            $this->end_controls_tab();
            $this->end_controls_tabs();

            $this->add_responsive_control('pill_border_radius', [
                'label'      => 'Border Radius',
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .ads-campaign-pill' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]);

            $this->add_responsive_control('pill_padding', [
                'label'      => 'Padding',
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px'],
                'selectors'  => [
                    '{{WRAPPER}} .ads-campaign-pill' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]);

            $this->add_responsive_control('pills_gap', [
                'label'      => 'Pills Grid Gap',
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 4, 'max' => 30]],
                'selectors'  => [
                    '{{WRAPPER}} .ads-campaign-pills-grid' => 'gap: {{SIZE}}{{UNIT}} !important;',
                ],
            ]);

            $this->add_responsive_control('pills_alignment', [
                'label'     => 'Alignment',
                'type'      => \Elementor\Controls_Manager::CHOOSE,
                'options'   => [
                    'flex-start' => [
                        'title' => 'Left',
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => 'Center',
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'flex-end' => [
                        'title' => 'Right',
                        'icon'  => 'eicon-text-align-right',
                    ],
                ],
                'default'   => 'center',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-pills-grid' => 'justify-content: {{VALUE}} !important;',
                ],
            ]);

            $this->end_controls_section();

            // ==========================================
            // STYLE TAB 4: DYNAMIC IMPACT STATEMENT BOX
            // ==========================================
            $this->start_controls_section('impact_style_section', [
                'label' => 'Dynamic Impact Statement Box',
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]);

            $this->add_control('impact_bg_color', [
                'label'     => 'Background Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-impact-box' => 'background-color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('impact_text_color', [
                'label'     => 'Text Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#00473e',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-impact-box' => 'color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name'     => 'impact_typography',
                    'label'    => 'Typography',
                    'selector' => '{{WRAPPER}} .ads-campaign-impact-box',
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Border::get_type(),
                [
                    'name'     => 'impact_border',
                    'selector' => '{{WRAPPER}} .ads-campaign-impact-box',
                ]
            );

            $this->add_responsive_control('impact_border_radius', [
                'label'      => 'Border Radius',
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .ads-campaign-impact-box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]);

            $this->add_responsive_control('impact_padding', [
                'label'      => 'Padding',
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px'],
                'selectors'  => [
                    '{{WRAPPER}} .ads-campaign-impact-box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]);

            $this->end_controls_section();

            // ==========================================
            // STYLE TAB 5: INPUTS & DROPDOWN (Custom Amount & Cause)
            // ==========================================
            $this->start_controls_section('fields_style_section', [
                'label' => 'Custom Amount & Dropdown Fields',
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]);

            $this->add_control('other_label_color', [
                'label'     => 'Other Amount Label Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ads-other-label' => 'color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('other_input_color', [
                'label'     => 'Other Input Text Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-other-input' => 'color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('other_border_color', [
                'label'     => 'Other Input Border Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#00875a',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-other-row' => 'border-color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('other_focus_color', [
                'label'     => 'Other Input Focus Border Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-other-row.focused, {{WRAPPER}} .ads-campaign-other-row:focus-within' => 'border-color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('select_heading', [
                'label'     => 'Dropdown Fields',
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]);

            $this->add_control('select_bg', [
                'label'     => 'Dropdown Background',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => 'rgba(0, 35, 30, 0.4)',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-type-select, {{WRAPPER}} .ads-campaign-location-select' => 'background-color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('select_color', [
                'label'     => 'Dropdown Text Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-type-select, {{WRAPPER}} .ads-campaign-location-select' => 'color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('select_border_color', [
                'label'     => 'Dropdown Border Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#00875a',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-type-select, {{WRAPPER}} .ads-campaign-location-select' => 'border-color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('select_arrow_color', [
                'label'     => 'Dropdown Arrow Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-select-arrow svg path, {{WRAPPER}} .ads-campaign-select-arrow svg' => 'fill: {{VALUE}} !important; stroke: {{VALUE}} !important;',
                ],
            ]);

            $this->add_responsive_control('select_border_radius', [
                'label'      => 'Dropdown Border Radius',
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .ads-campaign-type-select, {{WRAPPER}} .ads-campaign-location-select' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]);

            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name'     => 'select_typography',
                    'label'    => 'Dropdown Typography',
                    'selector' => '{{WRAPPER}} .ads-campaign-type-select, {{WRAPPER}} .ads-campaign-location-select',
                ]
            );

            $this->end_controls_section();

            // ==========================================
            // STYLE TAB 6: BUTTON
            // ==========================================
            $this->start_controls_section('button_style_section', [
                'label' => esc_html__('Button', 'elementor'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]);

            $this->add_responsive_control('position', [
                'label' => esc_html__('Position', 'elementor'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'elementor'),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'elementor'),
                        'icon' => 'eicon-h-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'elementor'),
                        'icon' => 'eicon-h-align-right',
                    ],
                    'justify' => [
                        'title' => esc_html__('Justified', 'elementor'),
                        'icon' => 'eicon-h-align-stretch',
                    ],
                ],
                'default' => 'justify',
                'prefix_class' => 'elementor%s-align-',
                'selectors_dictionary' => [
                    'left'    => 'justify-content: flex-start; text-align: left;',
                    'center'  => 'justify-content: center; text-align: center;',
                    'right'   => 'justify-content: flex-end; text-align: right;',
                    'justify' => 'justify-content: stretch; text-align: center;',
                ],
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-btn-wrap' => 'display: flex; {{VALUE}}',
                    '{{WRAPPER}}.elementor-align-left .ads-campaign-donate-btn, {{WRAPPER}}.elementor-align-center .ads-campaign-donate-btn, {{WRAPPER}}.elementor-align-right .ads-campaign-donate-btn' => 'width: auto !important; display: inline-flex !important;',
                    '{{WRAPPER}}.elementor-align-justify .ads-campaign-donate-btn' => 'width: 100% !important; display: flex !important;',
                    '{{WRAPPER}}.elementor-align-tablet-left .ads-campaign-donate-btn, {{WRAPPER}}.elementor-align-tablet-center .ads-campaign-donate-btn, {{WRAPPER}}.elementor-align-tablet-right .ads-campaign-donate-btn' => 'width: auto !important; display: inline-flex !important;',
                    '{{WRAPPER}}.elementor-align-tablet-justify .ads-campaign-donate-btn' => 'width: 100% !important; display: flex !important;',
                    '{{WRAPPER}}.elementor-align-mobile-left .ads-campaign-donate-btn, {{WRAPPER}}.elementor-align-mobile-center .ads-campaign-donate-btn, {{WRAPPER}}.elementor-align-mobile-right .ads-campaign-donate-btn' => 'width: auto !important; display: inline-flex !important;',
                    '{{WRAPPER}}.elementor-align-mobile-justify .ads-campaign-donate-btn' => 'width: 100% !important; display: flex !important;',
                ],
            ]);

            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name'     => 'btn_typography',
                    'label'    => 'Typography',
                    'selector' => '{{WRAPPER}} .ads-campaign-donate-btn',
                ]
            );

            $this->start_controls_tabs('btn_tabs');

            // Normal State
            $this->start_controls_tab('btn_tab_normal', ['label' => 'Normal']);

            $this->add_control('btn_text_color', [
                'label'     => 'Text Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-donate-btn' => 'color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('btn_bg_color', [
                'label'     => 'Background Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#E53935',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-donate-btn' => 'background-color: {{VALUE}} !important; background: {{VALUE}} !important;',
                ],
            ]);

            $this->add_group_control(
                \Elementor\Group_Control_Box_Shadow::get_type(),
                [
                    'name'     => 'btn_box_shadow',
                    'selector' => '{{WRAPPER}} .ads-campaign-donate-btn',
                ]
            );

            $this->end_controls_tab();

            // Hover State
            $this->start_controls_tab('btn_tab_hover', ['label' => 'Hover']);

            $this->add_control('btn_hover_text_color', [
                'label'     => 'Hover Text Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-donate-btn:hover' => 'color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('btn_hover_bg_color', [
                'label'     => 'Hover Background Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#D32F2F',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-donate-btn:hover' => 'background-color: {{VALUE}} !important; background: {{VALUE}} !important;',
                ],
            ]);

            $this->add_group_control(
                \Elementor\Group_Control_Box_Shadow::get_type(),
                [
                    'name'     => 'btn_hover_box_shadow',
                    'selector' => '{{WRAPPER}} .ads-campaign-donate-btn:hover',
                ]
            );

            $this->end_controls_tab();
            $this->end_controls_tabs();

            $this->add_group_control(
                \Elementor\Group_Control_Border::get_type(),
                [
                    'name'     => 'btn_border',
                    'selector' => '{{WRAPPER}} .ads-campaign-donate-btn',
                    'separator'=> 'before',
                ]
            );

            $this->add_responsive_control('btn_border_radius', [
                'label'      => 'Border Radius',
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .ads-campaign-donate-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]);

            $this->add_responsive_control('btn_padding', [
                'label'      => 'Padding',
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .ads-campaign-donate-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]);

            $this->end_controls_section();

            // ==========================================
            // STYLE TAB 7: FUNDRAISING GOAL BAR (Optional)
            // ==========================================
            $this->start_controls_section('goal_style_section', [
                'label' => 'Fundraising Goal Progress Bar',
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]);

            $this->add_control('goal_track_bg', [
                'label'     => 'Track Background Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#e2e8f0',
                'selectors' => [
                    '{{WRAPPER}} .ads-goal-progress-track' => 'background-color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('goal_fill_color', [
                'label'     => 'Progress Fill Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#038B5C',
                'selectors' => [
                    '{{WRAPPER}} .ads-goal-progress-fill' => 'background: {{VALUE}} !important; background-color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_responsive_control('goal_track_height', [
                'label'      => 'Track Height',
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 4, 'max' => 24]],
                'selectors'  => [
                    '{{WRAPPER}} .ads-goal-progress-track' => 'height: {{SIZE}}{{UNIT}} !important;',
                ],
            ]);

            $this->add_control('goal_info_color', [
                'label'     => 'Text Info Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#475569',
                'selectors' => [
                    '{{WRAPPER}} .ads-campaign-goal-info' => 'color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('goal_raised_color', [
                'label'     => 'Raised Text Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#038B5C',
                'selectors' => [
                    '{{WRAPPER}} .ads-goal-raised strong' => 'color: {{VALUE}} !important;',
                ],
            ]);

            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            $cid = intval($settings['campaign_id'] ?? 0);
            $accent = !empty($settings['accent_color']) ? esc_attr($settings['accent_color']) : '';
            echo do_shortcode("[campaign_donation_box id=\"{$cid}\" accent=\"{$accent}\"]");
        }
    }

    // 5. Standalone Customizable Donate Button Widget
    class ADS_Donate_Button_Elementor_Widget extends \Elementor\Widget_Base {
        public function get_name() { return 'ads_donate_button'; }
        public function get_title() { return 'Donation Button'; }
        public function get_icon() { return 'eicon-button'; }
        public function get_categories() { return ['general', 'basic']; }
        public function get_style_depends() { return ['ads-donation-css', 'ads-modern-donation-css', 'ads-google-fonts']; }
        public function get_script_depends() { return ['ads-donation-js']; }

        protected function register_controls() {
            // TAB CONTENT: Button Section
            $this->start_controls_section('section_button', [
                'label' => esc_html__('Button', 'elementor'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]);

            $this->add_responsive_control('position', [
                'label' => esc_html__('Position', 'elementor'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'elementor'),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'elementor'),
                        'icon' => 'eicon-h-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'elementor'),
                        'icon' => 'eicon-h-align-right',
                    ],
                    'justify' => [
                        'title' => esc_html__('Justified', 'elementor'),
                        'icon' => 'eicon-h-align-stretch',
                    ],
                ],
                'default' => 'left',
                'prefix_class' => 'elementor%s-align-',
                'selectors_dictionary' => [
                    'left'    => 'justify-content: flex-start; text-align: left;',
                    'center'  => 'justify-content: center; text-align: center;',
                    'right'   => 'justify-content: flex-end; text-align: right;',
                    'justify' => 'justify-content: stretch; text-align: center;',
                ],
                'selectors' => [
                    '{{WRAPPER}} .ads-btn-widget-wrapper' => 'display: flex; {{VALUE}}',
                    '{{WRAPPER}}.elementor-align-justify .ads-custom-donate-btn, {{WRAPPER}}.elementor-align-tablet-justify .ads-custom-donate-btn, {{WRAPPER}}.elementor-align-mobile-justify .ads-custom-donate-btn' => 'width: 100% !important; display: flex !important; justify-content: center !important;',
                    '{{WRAPPER}} .ads-custom-donate-btn.ads-btn-justify' => 'width: 100% !important; display: flex !important; justify-content: center !important;',
                ],
            ]);

            $this->add_control('label', [
                'label' => 'Button Text',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '£600 Water Pump',
                'placeholder' => 'e.g. £600 Water Pump or Donate £300',
            ]);

            $this->add_control('selected_icon', [
                'label' => esc_html__('Icon', 'elementor'),
                'type' => \Elementor\Controls_Manager::ICONS,
                'fa4compatibility' => 'icon',
                'skin' => 'inline',
                'label_block' => false,
                'default' => [
                    'value' => 'far fa-heart',
                    'library' => 'regular',
                ],
            ]);

            $this->add_control('icon_align', [
                'label' => esc_html__('Icon Position', 'elementor'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'left',
                'options' => [
                    'left' => esc_html__('Before', 'elementor'),
                    'right' => esc_html__('After', 'elementor'),
                ],
                'condition' => [
                    'selected_icon[value]!' => '',
                ],
            ]);

            $this->add_responsive_control('icon_indent', [
                'label' => esc_html__('Icon Spacing', 'elementor'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .ads-custom-donate-btn .ads-btn-icon-left' => 'margin-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ads-custom-donate-btn .ads-btn-icon-right' => 'margin-left: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'selected_icon[value]!' => '',
                ],
            ]);

            $this->add_control('size', [
                'label' => 'Size',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'medium',
                'options' => [
                    'small'  => 'Small',
                    'medium' => 'Medium',
                    'large'  => 'Large',
                    'xl'     => 'Extra Large',
                ],
            ]);

            $this->end_controls_section();

            // TAB CONTENT: Amount & Target Cause Section
            $this->start_controls_section('section_amount', [
                'label' => 'Amount & Target Cause',
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]);

            $this->add_control('amount', [
                'label' => 'Donation Amount (£)',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'placeholder' => 'Leave blank for project preset (£60/mo, £600 once, etc.)',
                'description' => 'Enter a custom amount, or leave blank to automatically use the preset amount from Active Projects Settings.',
            ]);

            $matrix = ads_get_projects_matrix();
            $proj_options = [
                '' => '-- None / Custom (Use Button Text or Tag) --',
            ];
            foreach (array_keys($matrix) as $p) {
                $proj_options[$p] = $p;
            }

            $this->add_control('project', [
                'label' => 'Project / Cause',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $proj_options,
                'default' => '',
                'description' => 'Select an active project, or choose "-- None / Custom --" to automatically use the Button Text or Package Tag as the project/appeal on the donate page.',
            ]);

            $this->add_control('frequency', [
                'label' => 'Payment Type / Frequency',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'single'  => 'Give Once (One-off)',
                    'monthly' => 'Give Regular / Monthly',
                ],
                'default' => 'single',
                'description' => 'Select whether clicking this button defaults to a Give Once or Give Regular/Monthly payment on checkout.',
            ]);

            $this->add_control('donation_type', [
                'label' => 'Donation Type',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'Sadaqah' => 'Sadaqah',
                    'Zakat' => 'Zakat',
                    'Lillah' => 'Lillah',
                    'Charitable Donation' => 'General / Charitable Donation',
                ],
                'default' => 'Sadaqah',
                'description' => 'Select donation type (Sadaqah, Zakat, etc.) to preset on checkout.',
            ]);

            $this->add_control('package_tag', [
                'label' => 'Package / Button Tag (for reports)',
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => 'e.g. Winter Pack (or leave blank to use Button Text)',
                'default' => '',
                'description' => 'Records in database that this donation came from this specific package. If Project is set to None/Custom, this tag (or Button Text) will be used as the Project on checkout.',
            ]);

            $this->end_controls_section();

            // TAB STYLE: Typography & Visual Appearance
            $this->start_controls_section('style_section', [
                'label' => 'Button Style & Typography',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]);

            // Typography Group Control (Font Family, Weight, Size, Transform, Line Height, Letter Spacing)
            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name'     => 'button_typography',
                    'label'    => 'Typography (Font Type & Size)',
                    'selector' => '{{WRAPPER}} .ads-custom-donate-btn',
                ]
            );

            // Normal & Hover Tabs for Colors
            $this->start_controls_tabs('tabs_button_style');

            // Normal State
            $this->start_controls_tab('tab_button_normal', ['label' => 'Normal']);

            $this->add_control('text_color', [
                'label' => 'Text Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ads-custom-donate-btn' => 'color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('icon_color', [
                'label' => 'Icon Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .ads-custom-donate-btn .ads-btn-icon' => 'color: {{VALUE}} !important; fill: {{VALUE}} !important;',
                    '{{WRAPPER}} .ads-custom-donate-btn .ads-btn-icon svg' => 'fill: {{VALUE}} !important;',
                    '{{WRAPPER}} .ads-custom-donate-btn .ads-btn-icon i' => 'color: {{VALUE}} !important;',
                ],
                'condition' => [
                    'selected_icon[value]!' => '',
                ],
            ]);

            $this->add_control('bg_color', [
                'label' => 'Background Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#D8005A',
                'selectors' => [
                    '{{WRAPPER}} .ads-custom-donate-btn' => 'background-color: {{VALUE}} !important; background: {{VALUE}} !important;',
                ],
            ]);

            $this->end_controls_tab();

            // Hover State
            $this->start_controls_tab('tab_button_hover', ['label' => 'Hover']);

            $this->add_control('hover_text_color', [
                'label' => 'Hover Text Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ads-custom-donate-btn:hover' => 'color: {{VALUE}} !important;',
                ],
            ]);

            $this->add_control('hover_icon_color', [
                'label' => 'Hover Icon Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .ads-custom-donate-btn:hover .ads-btn-icon' => 'color: {{VALUE}} !important; fill: {{VALUE}} !important;',
                    '{{WRAPPER}} .ads-custom-donate-btn:hover .ads-btn-icon svg' => 'fill: {{VALUE}} !important;',
                    '{{WRAPPER}} .ads-custom-donate-btn:hover .ads-btn-icon i' => 'color: {{VALUE}} !important;',
                ],
                'condition' => [
                    'selected_icon[value]!' => '',
                ],
            ]);

            $this->add_control('hover_bg_color', [
                'label' => 'Hover Background Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#be123c',
                'selectors' => [
                    '{{WRAPPER}} .ads-custom-donate-btn:hover' => 'background-color: {{VALUE}} !important; background: {{VALUE}} !important;',
                ],
            ]);

            $this->end_controls_tab();
            $this->end_controls_tabs();

            // Icon Size
            $this->add_responsive_control('icon_size', [
                'label'      => 'Icon Size',
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range'      => [
                    'px' => [
                        'min' => 6,
                        'max' => 60,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ads-custom-donate-btn .ads-btn-icon' => 'font-size: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .ads-custom-donate-btn .ads-btn-icon svg' => 'width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .ads-custom-donate-btn .ads-btn-icon i' => 'font-size: {{SIZE}}{{UNIT}} !important;',
                ],
                'condition'  => [
                    'selected_icon[value]!' => '',
                ],
                'separator'  => 'before',
            ]);

            // Border
            $this->add_group_control(
                \Elementor\Group_Control_Border::get_type(),
                [
                    'name'     => 'border',
                    'selector' => '{{WRAPPER}} .ads-custom-donate-btn',
                    'separator' => 'before',
                ]
            );

            $this->add_responsive_control('border_radius', [
                'label'      => 'Border Radius',
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors'  => [
                    '{{WRAPPER}} .ads-custom-donate-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
                'default'    => [
                    'top'      => 9999,
                    'right'    => 9999,
                    'bottom'   => 9999,
                    'left'     => 9999,
                    'unit'     => 'px',
                    'isLinked' => true,
                ],
            ]);

            // Box Shadow
            $this->add_group_control(
                \Elementor\Group_Control_Box_Shadow::get_type(),
                [
                    'name'     => 'box_shadow',
                    'selector' => '{{WRAPPER}} .ads-custom-donate-btn',
                ]
            );

            // Padding
            $this->add_responsive_control('padding', [
                'label'      => 'Padding',
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .ads-custom-donate-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]);

            $this->end_controls_section();
        }

        protected function render() {
            $s = $this->get_settings_for_display();
            $amt = floatval($s['amount'] ?? 0);
            $raw_prj = trim($s['project'] ?? '');
            $freq = !empty($s['frequency']) ? sanitize_key($s['frequency']) : 'single';

            $matrix = ads_get_projects_matrix();
            $projData = (!empty($raw_prj) && isset($matrix[$raw_prj])) ? $matrix[$raw_prj] : null;

            // Auto-resolve amount if frequency is monthly and amount was left at single preset (e.g. 600) or empty/0
            if ($freq === 'monthly' && $projData) {
                $monthlyPreset = floatval($projData['fixed_monthly'] ?? 0);
                $singlePreset = floatval($projData['fixed_single'] ?? 0);
                if ($monthlyPreset > 0 && ($amt <= 0 || abs($amt - $singlePreset) < 0.01)) {
                    $amt = $monthlyPreset;
                }
            } elseif ($freq === 'single' && $projData && $amt <= 0) {
                $amt = floatval($projData['fixed_single'] ?? 50);
            }
            
            // Text label (allow empty for icon-only button like in Elementor)
            $lbl = isset($s['label']) ? trim($s['label']) : ($amt > 0 ? "Donate £{$amt}" : "Donate");
            $pkg = !empty($s['package_tag']) && $s['package_tag'] !== '£600 Water Pump' 
                ? esc_attr(trim($s['package_tag'])) 
                : (!empty($lbl) ? esc_attr($lbl) : ($amt > 0 ? "Donation £{$amt}" : "Donation"));

            if ($freq === 'monthly' && $pkg === '£600 Water Pump' && $amt == 60) {
                $pkg = '£60/mo Water Pump';
            }

            // Project / Cause resolution:
            // 1. If an active project was explicitly selected (e.g. "Water - Pump"):
            if (!empty($raw_prj) && $raw_prj !== 'none' && $raw_prj !== 'custom') {
                $prj = esc_attr($raw_prj);
            } else {
                // 2. If None / Custom was chosen (or left blank):
                // Prefer Button Tag, then Button Text
                if (!empty($s['package_tag']) && $s['package_tag'] !== '£600 Water Pump') {
                    $prj = esc_attr(trim($s['package_tag']));
                } elseif (!empty($lbl)) {
                    $prj = esc_attr($lbl);
                } else {
                    $prj = 'General Donation';
                }
            }

            // Elementor Icon rendering with legacy string fallback
            $has_selected_icon = !empty($s['selected_icon']['value']);
            $has_legacy_icon = !$has_selected_icon && !empty($s['icon']);
            $icon_align = !empty($s['icon_align']) ? esc_attr($s['icon_align']) : 'left';
            $icon_indent_class = ($icon_align === 'right') ? 'ads-btn-icon-right' : 'ads-btn-icon-left';

            $icon_html = '';
            if ($has_selected_icon) {
                ob_start();
                \Elementor\Icons_Manager::render_icon($s['selected_icon'], ['aria-hidden' => 'true']);
                $raw_icon = ob_get_clean();
                if (!empty($raw_icon)) {
                    $icon_html = '<span class="ads-btn-icon ' . esc_attr($icon_indent_class) . '">' . $raw_icon . '</span>';
                }
            } elseif ($has_legacy_icon) {
                $icon_html = '<span class="ads-btn-icon ' . esc_attr($icon_indent_class) . '">' . esc_html($s['icon']) . '</span>';
            }

            $pos = esc_attr($s['position'] ?? ($s['align'] ?? 'left'));
            $size = esc_attr($s['size'] ?? 'medium');
            $is_justify = ($pos === 'justify') ? 'ads-btn-justify' : '';
            $is_icon_only = (!empty($icon_html) && empty($lbl)) ? 'ads-btn-icon-only' : '';

            $freq = !empty($s['frequency']) ? sanitize_key($s['frequency']) : 'single';
            $type = !empty($s['donation_type']) ? sanitize_text_field($s['donation_type']) : (!empty($s['type']) ? sanitize_text_field($s['type']) : 'Sadaqah');

            $checkout_url = home_url('/donate/');
            $query_args = [
                'project' => $prj,
                'amount'  => $amt,
                'package' => $pkg,
                'freq'    => $freq,
                'type'    => $type,
            ];
            $final_url = add_query_arg($query_args, $checkout_url);
            ?>
            <div class="ads-btn-widget-wrapper ads-btn-pos-<?php echo esc_attr($pos); ?>">
                <a href="<?php echo esc_url($final_url); ?>" 
                   class="ads-custom-donate-btn ads-btn-size-<?php echo esc_attr($size); ?> <?php echo esc_attr($is_justify); ?> <?php echo esc_attr($is_icon_only); ?>"
                   data-project="<?php echo esc_attr($prj); ?>"
                   data-amount="<?php echo esc_attr($amt); ?>"
                   data-package="<?php echo esc_attr($pkg); ?>"
                   data-freq="<?php echo esc_attr($freq); ?>"
                   data-type="<?php echo esc_attr($type); ?>">
                    <?php if ($icon_align !== 'right' && !empty($icon_html)): ?>
                        <?php echo $icon_html; ?>
                    <?php endif; ?>

                    <?php if (!empty($lbl)): ?>
                        <span class="ads-btn-label"><?php echo esc_html($lbl); ?></span>
                    <?php endif; ?>

                    <?php if ($icon_align === 'right' && !empty($icon_html)): ?>
                        <?php echo $icon_html; ?>
                    <?php endif; ?>
                </a>
            </div>
            <?php
        }
    }

    // 6. Preset Button Group Widget
    class ADS_Donate_Button_Group_Widget extends \Elementor\Widget_Base {
        public function get_name() { return 'ads_donate_button_group'; }
        public function get_title() { return 'Donation Buttons Group'; }
        public function get_icon() { return 'eicon-bullet-list'; }
        public function get_categories() { return ['general', 'basic']; }
        public function get_style_depends() { return ['ads-donation-css', 'ads-modern-donation-css', 'ads-google-fonts']; }
        public function get_script_depends() { return ['ads-donation-js']; }

        protected function register_controls() {
            $this->start_controls_section('content_section', [
                'label' => 'Buttons Configuration',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]);

            $matrix = ads_get_projects_matrix();
            $proj_options = [];
            foreach (array_keys($matrix) as $p) {
                $proj_options[$p] = $p;
            }

            $this->add_control('project', [
                'label' => 'Project / Cause',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $proj_options,
                'default' => array_keys($proj_options)[0] ?? 'Water - Well',
            ]);

            $this->add_control('frequency', [
                'label' => 'Payment Type / Frequency',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'single'  => 'Give Once (One-off)',
                    'monthly' => 'Give Regular / Monthly',
                ],
                'default' => 'single',
                'description' => 'Select whether these buttons default to Give Once or Give Regular/Monthly on checkout.',
            ]);

            $this->add_control('buttons_config', [
                'label' => 'Buttons (Amount:Label separated by commas)',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '600:Water Pump,1200:Water Well,3600:Community Tank',
                'description' => 'e.g. 600:Water Pump,1200:Water Well,3600:Community Tank',
            ]);

            $this->end_controls_section();

            // TAB CONTENT: Button Section
            $this->start_controls_section('section_button', [
                'label' => esc_html__('Button', 'elementor'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]);

            $this->add_responsive_control('position', [
                'label' => esc_html__('Position', 'elementor'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'elementor'),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'elementor'),
                        'icon' => 'eicon-h-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'elementor'),
                        'icon' => 'eicon-h-align-right',
                    ],
                    'justify' => [
                        'title' => esc_html__('Justified', 'elementor'),
                        'icon' => 'eicon-h-align-stretch',
                    ],
                ],
                'default' => 'center',
                'prefix_class' => 'elementor%s-align-',
                'selectors_dictionary' => [
                    'left'    => 'justify-content: flex-start; text-align: left;',
                    'center'  => 'justify-content: center; text-align: center;',
                    'right'   => 'justify-content: flex-end; text-align: right;',
                    'justify' => 'justify-content: stretch; text-align: center;',
                ],
                'selectors' => [
                    '{{WRAPPER}} .ads-btn-group-wrapper' => 'display: flex; flex-wrap: wrap; {{VALUE}}',
                    '{{WRAPPER}}.elementor-align-justify .ads-custom-donate-btn, {{WRAPPER}}.elementor-align-tablet-justify .ads-custom-donate-btn, {{WRAPPER}}.elementor-align-mobile-justify .ads-custom-donate-btn' => 'flex: 1 1 0; min-width: 140px; justify-content: center;',
                ],
            ]);

            $this->end_controls_section();

            // TAB STYLE
            $this->start_controls_section('style_section', [
                'label' => 'Buttons Style & Typography',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]);

            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name'     => 'group_typography',
                    'label'    => 'Typography',
                    'selector' => '{{WRAPPER}} .ads-custom-donate-btn',
                ]
            );

            $this->start_controls_tabs('tabs_group_style');

            // Normal
            $this->start_controls_tab('tab_group_normal', ['label' => 'Normal']);
            $this->add_control('text_color', [
                'label' => 'Text Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ads-custom-donate-btn' => 'color: {{VALUE}} !important;',
                ],
            ]);
            $this->add_control('bg_color', [
                'label' => 'Background Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#D8005A',
                'selectors' => [
                    '{{WRAPPER}} .ads-custom-donate-btn' => 'background-color: {{VALUE}} !important; background: {{VALUE}} !important;',
                ],
            ]);
            $this->end_controls_tab();

            // Hover
            $this->start_controls_tab('tab_group_hover', ['label' => 'Hover']);
            $this->add_control('hover_text_color', [
                'label' => 'Hover Text Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ads-custom-donate-btn:hover' => 'color: {{VALUE}} !important;',
                ],
            ]);
            $this->add_control('hover_bg_color', [
                'label' => 'Hover Background Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#be123c',
                'selectors' => [
                    '{{WRAPPER}} .ads-custom-donate-btn:hover' => 'background-color: {{VALUE}} !important; background: {{VALUE}} !important;',
                ],
            ]);
            $this->end_controls_tab();

            $this->end_controls_tabs();

            $this->add_responsive_control('group_border_radius', [
                'label'      => 'Border Radius',
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .ads-custom-donate-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
                'separator'  => 'before',
            ]);

            $this->add_responsive_control('group_padding', [
                'label'      => 'Padding',
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .ads-custom-donate-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]);

            $this->end_controls_section();
        }

        protected function render() {
            $s = $this->get_settings_for_display();
            $prj = esc_attr($s['project'] ?? 'Water - Well');
            $btns = esc_attr($s['buttons_config'] ?? '600:Water Pump,1200:Water Well,3600:Community Tank');
            $align = esc_attr($s['position'] ?? ($s['alignment'] ?? 'center'));
            $freq = !empty($s['frequency']) ? sanitize_key($s['frequency']) : 'single';
            echo do_shortcode("[donate_button_group project=\"{$prj}\" buttons=\"{$btns}\" alignment=\"{$align}\" freq=\"{$freq}\"]");
        }
    }

    $widgets_manager->register(new ADS_Quick_Donate_Widget());
    $widgets_manager->register(new ADS_Project_Donation_Card_Widget());
    $widgets_manager->register(new ADS_Master_Checkout_Widget());
    $widgets_manager->register(new ADS_Campaign_Donation_Box_Widget());
    $widgets_manager->register(new ADS_Donate_Button_Elementor_Widget());
    $widgets_manager->register(new ADS_Donate_Button_Group_Widget());
});

