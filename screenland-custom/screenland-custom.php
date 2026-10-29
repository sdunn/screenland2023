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

//FILTER POST BLOCKS ADVANCED
function scld_thumbnails_home_filter($query_args){
	$query_args['meta_key'] = 'show_in_thumbnails';
	$query_args['meta_value'] = 1;
	return $query_args;
}
add_filter('scld_home_filter', 'scld_thumbnails_home_filter');

function scld_thumbnails_av_filter($query_args){
	$query_args['meta_key'] = 'show_in_thumbnails_av';
	$query_args['meta_value'] = 1;
	return $query_args;
}
add_filter('scld_av_filter', 'scld_thumbnails_av_filter');

function scld_thumbnails_social_filter($query_args){
	$query_args['meta_key'] = 'show_in_thumbnails_social';
	$query_args['meta_value'] = 1;
	return $query_args;
}
add_filter('scld_social_filter', 'scld_thumbnails_social_filter');

function scld_thumbnails_graphics_filter($query_args){
	$query_args['meta_key'] = 'show_in_thumbnails_graphics';
	$query_args['meta_value'] = 1;
	return $query_args;
}
add_filter('scld_graphics_filter', 'scld_thumbnails_graphics_filter');

function scld_thumbnails_broadway_filter($query_args){
        $query_args['meta_key'] = 'show_in_thumbnails_broadway';
        $query_args['meta_value'] = 1;
        return $query_args;
}
add_filter('scld_broadway_filter', 'scld_thumbnails_broadway_filter');

function scld_thumbnails_creative_content_filter($query_args){
        $query_args['meta_key'] = 'show_in_thumbnails_creative_content';
        $query_args['meta_value'] = 1;
        return $query_args;
}
add_filter('scld_creative_content_filter', 'scld_thumbnails_creative_content_filter');

function scld_thumbnails_graphics_dooh_filter($query_args){
    $query_args['meta_key'] = 'show_in_thumbnails_graphics_dooh';
    $query_args['meta_value'] = 1;
    return $query_args;
}

add_filter('scld_graphics_dooh_filter', 'scld_thumbnails_graphics_dooh_filter');

function scld_thumbnails_animation_filter($query_args){
    $query_args['meta_key'] = 'show_in_thumbnails_animation';
    $query_args['meta_value'] = 1;
    return $query_args;
}

add_filter('scld_animation_filter', 'scld_thumbnails_animation_filter');


// PORTFOLIO POST ORDER - Place newly published Portfolio posts at the top

add_action('wp_after_insert_post', function(
    $post_id, $post, $update, $post_before
) {
    if (
        $post->post_type !== 'scld_portfolio' ||
        $post->post_status !== 'publish'
    ) {
        return;
    }

    // Don't change the order when editing published posts.
    if (
        $post_before &&
        $post_before->post_status === 'publish'
    ) {
        return;
    }

    global $wpdb;

    $minimum = $wpdb->get_var($wpdb->prepare(
        "SELECT MIN(menu_order)
         FROM {$wpdb->posts}
         WHERE post_type = %s
         AND post_status = 'publish'
         AND ID != %d",
        'scld_portfolio',
        $post_id
    ));

    if ($minimum === null) {
        return;
    }

    $wpdb->update(
        $wpdb->posts,
        ['menu_order' => (int) $minimum - 1],
        ['ID' => $post_id],
        ['%d'],
        ['%d']
    );

    clean_post_cache($post_id);

}, 20, 4);


// APTO / ELEMENTOR SORT MATCHING

/**
 * Single Portfolio Type pages (Broadway, Social, etc.):
 * Elementor can wrap a single term selection in a redundant OR group.
 * Flatten it to one plain clause so APTO matches that term's sort view.
 *
 * Multi-term pages (AV, Graphics/DOOH) are handled by the
 * apto/query_match_sort_id filter below, which reads nested groups itself.
 */
add_action('pre_get_posts', function($query) {
    if (is_admin() || $query->get('post_type') !== 'scld_portfolio') {
        return;
    }

    $tax_query = $query->get('tax_query');
    if (!is_array($tax_query)) {
        return;
    }

    // Elementor can use either an outer OR group or a nested, single OR group.
    if (count($tax_query) === 1 && isset($tax_query[0]) && is_array($tax_query[0])
        && strtoupper((string) ($tax_query[0]['relation'] ?? '')) === 'OR') {
        $group = $tax_query[0];
    } elseif (strtoupper((string) ($tax_query['relation'] ?? '')) === 'OR') {
        $group = $tax_query;
    } else {
        return;
    }

    // Only act on a group holding exactly one real condition.
    $clauses = array();
    foreach ($group as $key => $value) {
        if ($key === 'relation') {
            continue;
        }
        if (!is_int($key) || !is_array($value)) {
            return;
        }
        $clauses[] = $value;
    }

    if (count($clauses) !== 1) {
        return;
    }

    $clause = $clauses[0];
    if (($clause['taxonomy'] ?? '') === 'portfolio_types'
        && strtoupper((string) ($clause['operator'] ?? 'IN')) === 'IN'
        && in_array(($clause['field'] ?? ''), array('term_id', 'slug'), true)
        && in_array(count((array) ($clause['terms'] ?? array())), array(1, 2), true)) {
        $query->set('tax_query', array($clause));
    }
}, 1);


/**
 * Multi-term pages (AV, Graphics/DOOH):
 * The ACF meta conditions on the slider and Post Loop queries stop APTO
 * from matching the page's Advanced Sort (post type + Portfolio Types, IN).
 * For Portfolio queries with 2+ Portfolio Types, look up the sort using
 * post type + terms only, and use it when one exists.
 */
function scld_portfolio_type_ids_from_tax_query($tax_query) {
    $ids = array();
    if (!is_array($tax_query)) return $ids;
    foreach ($tax_query as $key => $clause) {
        if ($key === 'relation' || !is_array($clause)) continue;
        if (!isset($clause['taxonomy'])) { // nested group (Elementor)
            $ids = array_merge($ids, scld_portfolio_type_ids_from_tax_query($clause));
            continue;
        }
        if ($clause['taxonomy'] !== 'portfolio_types'
            || strtoupper($clause['operator'] ?? 'IN') !== 'IN') continue;
        $field = $clause['field'] ?? 'term_id';
        foreach ((array) ($clause['terms'] ?? array()) as $t) {
            if (in_array($field, array('term_id', 'id'), true)) {
                $term = get_term((int) $t, 'portfolio_types');
            } else {
                $by   = ($field === 'term_taxonomy_id') ? 'term_taxonomy_id' : 'slug';
                $term = get_term_by($by, $t, 'portfolio_types');
            }
            if ($term instanceof WP_Term) $ids[] = (int) $term->term_id;
        }
    }
    return array_values(array_unique($ids));
}

add_filter('apto/query_match_sort_id', function ($sort_view_id, $orderBy, $query, $sorts_match_filter) {
    static $running = false;
    if ($running || is_admin() || !($query instanceof WP_Query)
        || $query->get('post_type') !== 'scld_portfolio'
        || !class_exists('APTO_functions')) {
        return $sort_view_id;
    }

    $ids = scld_portfolio_type_ids_from_tax_query($query->get('tax_query'));
    if (count($ids) < 2) return $sort_view_id; // single-term + Home: unchanged

    $running = true; // guard against re-entering this filter
    $match = APTO_functions::get_sort_view_id_by_arguments(array(
        'post_type' => 'scld_portfolio',
        'tax_query' => array(array(
            'taxonomy' => 'portfolio_types',
            'field'    => 'term_id',
            'terms'    => $ids,
            'operator' => 'IN',
        )),
    ));
    $running = false;

    return $match ? $match : $sort_view_id;
}, 10, 4);