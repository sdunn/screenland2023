<?php

use \Elementor\Controls_Manager;

class Screenland_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'screenland_widget';
	}

	public function get_title() {
		return esc_html__( 'Screenland Widget', 'elementor-addon' );
	}

	public function get_icon() {
		return 'eicon-code';
	}

	public function get_categories() {
		return [ 'basic' ];
	}

	public function get_keywords() {
		return [ 'screenland' ];
	}

	protected function _register_controls() {
        // Add a control to toggle the display of the 'portfolio_types' custom taxonomy
        $this->start_controls_section(
            'section_settings',
            [
                'label' => esc_html__('Settings', 'elementor-addon'),
            ]
        );

        $this->add_control(
            'show_portfolio_types',
            [
                'label' => esc_html__('Display Portfolio Types', 'elementor-addon'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'elementor-addon'),
                'label_off' => esc_html__('Hide', 'elementor-addon'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'page_slider',
            [
                'label' => esc_html__('What page is slider on?', 'elementor-addon'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'home' => esc_html__('Home', 'elementor-addon'),
                    'av' => esc_html__('AV', 'elementor-addon'),
                    'social' => esc_html__('Social', 'elementor-addon'),
                    'graphics' => esc_html__('Graphics', 'elementor-addon'),
                ],
                'default' => 'home',
            ]
        );

		$this->add_control(
			'number_of_posts',
			[
				'label' => esc_html__('Number of Posts', 'elementor-addon'),
				'type' => Controls_Manager::NUMBER,
				'default' => 5,
			]
		);

        $this->end_controls_section();
    }

	protected function render() {
        $settings = $this->get_settings_for_display();
        $page_slider = $settings['page_slider'];

        // Map the page_slider value to the corresponding custom field name
        $custom_field_name = '';
        switch ($page_slider) {
            case 'home':
                $custom_field_name = 'add_to_home_page_slider';
                break;
            case 'av':
                $custom_field_name = 'add_to_av_page_slider';
                break;
            case 'social':
                $custom_field_name = 'add_to_social_page_slider';
                break;
            case 'graphics':
                $custom_field_name = 'add_to_graphics_page_slider';
                break;
        }

        $args = array(
            'post_type' => 'scld_portfolio',
            'posts_per_page' => $settings['number_of_posts'], // Control for the number of posts to display
            'orderby' => 'title', // Order the posts by title. You can change this to any other field.
            'order' => 'ASC', // Order the posts in ascending order. Change to 'DESC' for descending order.
            'meta_query' => array(
                array(
                    'key' => $custom_field_name,
                    'value' => '1',
                    'compare' => '=',
                ),
            ),
        );

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $page_thumb_url = get_the_post_thumbnail_url($query->ID, 'full');
                $permalink = get_the_permalink();

                // Check if we should display the 'portfolio-types' custom taxonomy
                $show_portfolio_types = $settings['show_portfolio_types'] === 'yes';

                // Retrieve the terms for the 'portfolio-types' taxonomy if needed
                $portfolio_types_list = '';
                if ($show_portfolio_types) {
                    $portfolio_types = get_the_terms($query->ID, 'portfolio_types');
                    if ($portfolio_types && !is_wp_error($portfolio_types)) {
                        $term_names = array();
                        foreach ($portfolio_types as $term) {
                            $term_names[] = $term->name;
                        }
                        $portfolio_types_list = implode(', ', $term_names);
                    }
                }
                ?>

                <a href="<?php echo esc_url($permalink); ?>">
                    <h1><?php the_title(); ?></h1>
                    <img src="<?php echo $page_thumb_url; ?>">
                    <?php if ($show_portfolio_types && !empty($portfolio_types_list)) : ?>
                        <p>Portfolio Types: <?php echo $portfolio_types_list; ?></p>
                    <?php endif; ?>
                </a>
	
			<?php
			}
			wp_reset_postdata();
		} else {
			echo 'No portfolio items found.';
		}
	}
}