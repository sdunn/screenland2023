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
        
        $this->start_controls_section(
            'section_settings',
            [
                'label' => esc_html__('Settings', 'elementor-addon'),
            ]
        );

        // Show / hide portfolio type 
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

        // Display page
        $this->add_control(
            'page_slider',
            [
                'label' => esc_html__('What page is slider on?', 'elementor-addon'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'home' => esc_html__('Home', 'elementor-addon'),
    				'animation' => esc_html__('Animation', 'elementor-addon'),
                    'av' => esc_html__('AV', 'elementor-addon'),
                    'broadway' => esc_html__('Broadway', 'elementor-addon'),
                    'creative_content' => esc_html__('Creative Content', 'elementor-addon'),
                    'graphics_dooh' => esc_html__('Graphics/DOOH', 'elementor-addon'),
                    'social' => esc_html__('Social', 'elementor-addon'),
                    'graphics' => esc_html__('Graphics', 'elementor-addon'),
                ],
                'default' => 'home',
            ]
        );

        // Number of posts
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
			case 'animation':
    			$custom_field_name = 'add_to_animation_page_slider';
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
            case 'broadway':
    		        $custom_field_name = 'add_to_broadway_page_slider';
    		        break;
	          case 'creative_content':
	          case 'creative-content':
    		        $custom_field_name = 'add_to_creative_content_page_slider';
    		        break;
            case 'graphics_dooh':
            case 'graphics-dooh':
                $custom_field_name = 'add_to_graphics_dooh_page_slider';
                break;
        }

        $args = array(
            'post_type' => 'scld_portfolio',
            'posts_per_page' => $settings['number_of_posts'], // Control for the number of posts to display
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'meta_query' => array(
                array(
                    'key' => $custom_field_name,
                    'value' => '1',
                    'compare' => '=',
                ),
            ),
        );

        // APTO: Give each page-specific slider the matching Portfolio Type context.
        // The ACF field above still controls which posts are included.
        // Home is intentionally omitted so it uses the global Portfolio order.
        $slider_term_slugs = array(
            'animation'        => 'animation',
            'broadway'         => 'broadway',
            'creative_content' => 'creative-content',
            'creative-content' => 'creative-content',
            'social'           => 'social',
            'graphics'         => 'graphics',
        );

        if (isset($slider_term_slugs[$page_slider])) {
            $portfolio_type_term = get_term_by(
                'slug',
                $slider_term_slugs[$page_slider],
                'portfolio_types'
            );

            if ($portfolio_type_term instanceof \WP_Term) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'portfolio_types',
                        'field'    => 'term_id',
                        'terms'    => array((int) $portfolio_type_term->term_id),
                        'operator' => 'IN',
                    ),
                );
            }
            // If the term slug doesn't exist, keep the prior ACF-only behavior.
            // Verify term slugs with: wp term list portfolio_types --fields=name,slug,term_id
        }

        // AV and Graphics/DOOH combine two Portfolio Type terms each.
        // These are term NAMES (not guessed slugs), as specified for Screenland.
        // Both terms must resolve before adding the restriction, so a missing
        // term never silently removes items from an existing slider.
        $combined_slider_terms = array(
            'av'            => array('Trailer', 'TV'),
            'graphics_dooh' => array('DOOH', 'Graphics'),
            'graphics-dooh' => array('DOOH', 'Graphics'),
        );

        if (isset($combined_slider_terms[$page_slider])) {
            $combined_names = $combined_slider_terms[$page_slider];
            $combined_ids   = array();

            foreach ($combined_names as $term_name) {
                $term = get_term_by('name', $term_name, 'portfolio_types');

                // Also support a matching slug if the display name differs.
                if (!($term instanceof \WP_Term)) {
                    $term = get_term_by('slug', sanitize_title($term_name), 'portfolio_types');
                }

                if (!($term instanceof \WP_Term)) {
                    $combined_ids = array();
                    break;
                }

                $combined_ids[] = (int) $term->term_id;
            }

            if (count(array_unique($combined_ids)) === count($combined_names)) {
                // IN means either term, not both. Keep the existing ACF filter.
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'portfolio_types',
                        'field'    => 'term_id',
                        'terms'    => $combined_ids,
                        'operator' => 'IN',
                    ),
                );
            }
        }

        // Run the query once. The main slider loop below also collects
        // thumbnail URLs for the thumb nav, which is output after it.
        $query = new WP_Query($args);
        $thumbnail_urls = array();
        $show_portfolio_types = $settings['show_portfolio_types'] === 'yes';

?>

<!-- Swiper CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
<style>
  html,
  body {
    position: relative;
    height: 100%;
  }

  body {
    background: #eee;
    font-family: Helvetica Neue, Helvetica, Arial, sans-serif;
    font-size: 14px;
    color: #000;
    margin: 0;
    padding: 0;
  }

  .swiper {
    width: 100%;
    height: 100%;
  }

  .swiper-slide {
    text-align: center;
    font-size: 18px;
    background: #fff;
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .swiper-slide img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  body {
    background: #000;
    color: #000;
  }

  .swiper {
    width: 100%;
    height: 300px;
    margin-left: auto;
    margin-right: auto;
  }

  .swiper-slide {
    background-size: cover;
    background-position: center;
  }

  .mySwiper2 {
    height: 80%;
    width: 100%;
  }

  .mySwiper {
    height: 20%;
    box-sizing: border-box;
    padding: 10px 0;
  }

  .mySwiper .swiper-slide {
    width: 25%;
    height: 100%;
  }

  .swiper-slide img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
</style>

<!-- Swiper container -->
<div style="--swiper-navigation-color: #fff; --swiper-pagination-color: #fff" class="swiper mySwiper2">
    <div class="swiper-wrapper">

<!-- Portfolio posts query -->
<?php

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $page_thumb_url = get_the_post_thumbnail_url($post_id, 'full');
                $permalink = get_the_permalink();

                // Collect the thumbnail URL for the thumb nav
                $thumbnail_urls[] = $page_thumb_url;

                // Retrieve the terms for the 'portfolio_types' taxonomy if needed
                $portfolio_types_list = '';
                if ($show_portfolio_types) {
                    $portfolio_types = get_the_terms($post_id, 'portfolio_types');
                    if ($portfolio_types && !is_wp_error($portfolio_types)) {
                        $term_names = array();
                        foreach ($portfolio_types as $term) {
                            $term_names[] = $term->name;
                        }
                        $portfolio_types_list = implode(', ', $term_names);
                    }
                }
?>

<!-- start Swiper slides -->
    <div class="swiper-slide sl-slide" style="background-image: url(/wp-content/uploads/2023/06/1px-slider-screen.png), url(<?php echo esc_url($page_thumb_url); ?>)">
      <a class="sl-slider-link" href="<?php echo esc_url($permalink); ?>">
        <div class="sl-slide-wrap">
          <?php if ($show_portfolio_types && !empty($portfolio_types_list)) : ?>
            <h3 class="sl-slider-portfolio-type"><?php echo esc_html($portfolio_types_list); ?></h3>
          <?php endif; ?>
          <h1 class="sl-slider-title"><?php the_title(); ?></h1>
          <div class="sl-slider-cta">Watch Now</div>
        </div>
      </a>
    </div>

<?php
			}
			wp_reset_postdata();
		} else {
			echo 'No portfolio items found.';
		}
?>

<!-- arrow nav -->
    </div>
    <div class="swiper-button-next"></div>
    <div class="swiper-button-prev"></div>
</div>

<!-- Swiper thumb nav -->
<div class="sl-slider-thumbs-wrapper">
  <div thumbsSlider="" class="swiper mySwiper sl-slider-thumbs">
    <div class="swiper-wrapper">
    <?php foreach( $thumbnail_urls as $thumbnail_url ): ?>
      <div class="swiper-slide">
        <img class="sl-slider-thumb" src="<?php echo esc_url($thumbnail_url); ?>">
      </div>
    <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>

<script>
  var swiper = new Swiper(".mySwiper", {
    loop: true,
    spaceBetween: 10,
    slidesPerView: 4,
    freeMode: true,
    watchSlidesProgress: true,
  });
  var swiper2 = new Swiper(".mySwiper2", {
    loop: true,
    spaceBetween: 10,
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },
    thumbs: {
      swiper: swiper,
    },
    speed: 1000,
    autoplay: {
      delay: 4000,
      disableOnInteraction: false,
    },
  });
</script>

<?php

	}
}