<?php
/*
Plugin Name: Screenland Custom Code
Description: Custom post types and other custom code for the Screenland website.
Author: Shannon Dunn
Author URI: http://www.juusui.com
Textdomain: screenland
*/

// CUSTOM TAXONOMIES

function scld_custom_taxonomies()
{

// project taxonomy

    $labels = [
        'name'              => _x('Projects', 'taxonomy general name'),
		'singular_name'     => _x('Project', 'taxonomy singular name'),
		'search_items'      => __('Search Projects'),
		'all_items'         => __('All Projects'),
		'parent_item'       => __('Parent Project'),
		'parent_item_colon' => __('Parent Project:'),
		'edit_item'         => __('Edit Project'),
		'update_item'       => __('Update Project'),
		'add_new_item'      => __('Add New Project'),
		'new_item_name'     => __('New Project Name'),
		'menu_name'         => __('Projects'),
	];
	$args = [
		'hierarchical'      => true, // make it hierarchical (like categories)
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite' 			=> array('slug' => 'projects', 'with_front' => false),
	];

	register_taxonomy('projects', 'post', $args);

// portfolio type taxonomy

    $labels = [
        'name'              => _x('Portfolio Type', 'taxonomy general name'),
        'singular_name'     => _x('Portfolio Type', 'taxonomy singular name'),
        'search_items'      => __('Search Portfolio Types'),
        'all_items'         => __('All Portfolio Types'),
        'parent_item'       => __('Parent Portfolio Type'),
        'parent_item_colon' => __('Parent Portfolio Type:'),
        'edit_item'         => __('Edit Portfolio Type'),
        'update_item'       => __('Update Portfolio Type'),
        'add_new_item'      => __('Add New Portfolio Type'),
        'new_item_name'     => __('New Portfolio Type Name'),
        'menu_name'         => __('Portfolio Types'),
    ];
    $args = [
        'hierarchical'      => true, // make it hierarchical (like categories)
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite' 			=> array('slug' => 'portfolio_types', 'with_front' => false),
    ];

    register_taxonomy('portfolio_types', 'post', $args);

} 


add_action('init', 'scld_custom_taxonomies');


// CUSTOM POST TYPES

function scld_custom_post_types() {

// Add Portfolio custom post type

	$labels = array(
		'name' => __( 'Portfolio', 'screenland' ),
		'singular_name' => __( 'Portfolio', 'screenland' ),
		'add_new' => __( 'New Portfolio Post', 'screenland' ),
		'edit_item' => __( 'Edit Portfolio Post', 'screenland' ),
		'new_item' => __( 'New Portfolio Post', 'screenland' ),
		'view_item' => __( 'View Portfolio Post', 'screenland' ),
		'search_items' => __( 'Search Portfolios Posts', 'screenland' ),
		'not_found' =>  __( 'No Portfolios Posts found', 'screenland' ),
		'not_found_in_trash' => __( 'No Portfolio Posts found in Trash', 'screenland' ),
	);

	$args = array(
		'labels' => $labels,
		'has_archive' => true,
		'public' => true,
		'menu_position' => 20,
		'menu_icon' => 'dashicons-portfolio',
		'hierarchical' => false,
		'supports' => array(
			'title',
			'editor',
			'excerpt',
			'custom-fields',
			'thumbnail',
			'page-attributes',
		),
		'taxonomies' => array('category', 'post_tag', 'projects', 'portfolio_types'),
		'rewrite' => array( 'slug' => 'portfolio', 'with_front' => false ),
		'show_in_rest' => true
	);
	
	register_post_type( 'scld_portfolio', $args );

}

add_action( 'init', 'scld_custom_post_types' );


// SECURITY

// Disable xmlrpc
add_filter( 'xmlrpc_enabled', '__return_false' );

// Disable some REST endpoints
add_filter( 'rest_endpoints', function( $endpoints ){
    if ( isset( $endpoints['/wp/v2/users'] ) ) {
        unset( $endpoints['/wp/v2/users'] );
    }
    if ( isset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] ) ) {
        unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
    }
    if ( isset( $endpoints['/wp/v2/pages'] ) ) {
        unset( $endpoints['/wp/v2/pages'] );
    }
    if ( isset( $endpoints['/wp/v2/pages/(?P<id>[\d]+)'] ) ) {
        unset( $endpoints['/wp/v2/pages/(?P<id>[\d]+)'] );
    }
    return $endpoints;
});


//SHORTCODE

//Related link
function sl_related_link_shortcode() {
	$terms = get_the_terms(get_the_ID(), 'projects'); // Retrieve the terms for the 'projects' taxonomy

    if (!empty($terms) && !is_wp_error($terms)) { // Check if there are terms assigned to the post
        $term = array_shift($terms); // Retrieve the first term
        $term_name = $term->name; // Get the name of the term
        $term_link = get_term_link($term); // Get the link to the term archive

        if (!is_wp_error($term_link)) { // Check if the term link is valid
            return '<h5 class="sl-related-head"><a href="' . esc_url($term_link) . '">Related Content</a></h5>'; // Return the linked term name
        } else {
            return esc_html($term_name); // Return the term name without the link if the link is invalid
        }
    } else {
        return ''; // Return an empty string if no term is assigned
    }
}
add_shortcode('sl_related_link', 'sl_related_link_shortcode');

//ELEMENTOR CUSTOM WIDGETS


function register_screenland_widget( $widgets_manager ) {

	require_once( __DIR__ . '/widgets/screenland-widget.php' );

	$widgets_manager->register( new \Screenland_Widget() );

}
add_action( 'elementor/widgets/register', 'register_screenland_widget' );