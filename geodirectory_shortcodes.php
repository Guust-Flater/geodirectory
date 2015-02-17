<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
require_once( 'geodirectory-functions/shortcode_functions.php' );

add_shortcode( 'add_listing', 'geodir_sc_add_listing' );
function geodir_sc_add_listing( $atts ) {
	ob_start();
	$defaults = array(
		'pid'          => '',
		'listing_type' => 'gd_place',
		'login_msg' => __('You must login to post.',GEODIRECTORY_TEXTDOMAIN),
		'show_login' => false,
	);
	$params = shortcode_atts( $defaults, $atts );

	foreach ( $params as $key => $value ) {
		$_REQUEST[ $key ] = $value;
	}

	$user_id = get_current_user_id();
	if(!$user_id){
		echo $params['login_msg'];
		if($params['show_login']) {
			echo "<br />";
			$defaults = array(
				'before_widget' => '',
				'after_widget' => '',
				'before_title' => '',
				'after_title' => '',
			);

			geodir_loginwidget_output($defaults, $defaults);
		}


	}else {
		###### MAIN CONTENT ######
		// this adds the mandatory message
		do_action('geodir_add_listing_page_mandatory');
		// this adds the add listing form
		do_action('geodir_add_listing_form');
	}
	$output = ob_get_contents();

	ob_end_clean();

	return $output;
}

add_shortcode( 'homepage_map', 'geodir_sc_home_map' );
function geodir_sc_home_map( $atts ) {
	ob_start();
	$defaults = array(
		'width'          => '960',
		'height'         => '425',
		'maptype'        => 'ROADMAP',
		'zoom'           => '13',
		'autozoom'       => '',
		'child_collapse' => '0',
		'scrollwheel'    => '0',
	);

	$params = shortcode_atts( $defaults, $atts );

	$params = gdsc_validate_map_args( $params );

	$map_args = array(
		'map_canvas_name'           => 'gd_home_map',
		'width'                     => apply_filters( 'widget_width', $params['width'] ),
		'height'                    => apply_filters( 'widget_heigh', $params['height'] ),
		'maptype'                   => apply_filters( 'widget_maptype', $params['maptype'] ),
		'scrollwheel'               => apply_filters( 'widget_scrollwheel', $params['scrollwheel'] ),
		'zoom'                      => apply_filters( 'widget_zoom', $params['zoom'] ),
		'autozoom'                  => apply_filters( 'widget_autozoom', $params['autozoom'] ),
		'child_collapse'            => apply_filters( 'widget_child_collapse', $params['child_collapse'] ),
		'enable_cat_filters'        => true,
		'enable_text_search'        => true,
		'enable_post_type_filters'  => true,
		'enable_location_filters'   => apply_filters( 'geodir_home_map_enable_location_filters', false ),
		'enable_jason_on_load'      => false,
		'enable_marker_cluster'     => false,
		'enable_map_resize_button'  => true,
		'map_class_name'            => 'geodir-map-home-page',
		'is_geodir_home_map_widget' => true,
	);

	geodir_draw_map( $map_args );

	add_action( 'wp_footer', 'geodir_home_map_add_script', 100 );

	$output = ob_get_contents();

	ob_end_clean();

	return $output;
}

add_shortcode( 'listing_map', 'geodir_sc_listing_map' );
function geodir_sc_listing_map( $atts ) {
	ob_start();
	add_action( 'wp_head', 'init_listing_map_script' ); // Initialize the map object and marker array

	add_action( 'the_post', 'create_list_jsondata' ); // Add marker in json array

	add_action( 'wp_footer', 'show_listing_widget_map' ); // Show map for listings with markers

	$defaults = array(
		'width'          => '294',
		'height'         => '370',
		'zoom'           => '13',
		'autozoom'       => '',
		'sticky'         => '',
		'showall'        => '0',
		'scrollwheel'    => '0',
		'maptype'        => 'ROADMAP',
		'child_collapse' => 0,
	);

	$params = shortcode_atts( $defaults, $atts );

	$params = gdsc_validate_map_args( $params );

	$map_args = array(
		'map_canvas_name'          => 'gd_listing_map',
		'width'                    => $params['width'],
		'height'                   => $params['height'],
		'zoom'                     => $params['zoom'],
		'autozoom'                 => $params['autozoom'],
		'sticky'                   => $params['sticky'],
		'showall'                  => $params['showall'],
		'scrollwheel'              => $params['scrollwheel'],
		'child_collapse'           => 0,
		'enable_cat_filters'       => false,
		'enable_text_search'       => false,
		'enable_post_type_filters' => false,
		'enable_location_filters'  => false,
		'enable_jason_on_load'     => true,
	);

	if ( is_single() ) {

		global $post;
		$map_default_lat            = $address_latitude = $post->post_latitude;
		$map_default_lng            = $address_longitude = $post->post_longitude;
		$mapview                    = $post->post_mapview;
		$map_args['zoom']           = $post->post_mapzoom;
		$map_args['map_class_name'] = 'geodir-map-listing-page-single';

	} else {
		$default_location = geodir_get_default_location();

		$map_default_lat            = isset( $default_location->city_latitude ) ? $default_location->city_latitude : '';
		$map_default_lng            = isset( $default_location->city_longitude ) ? $default_location->city_longitude : '';
		$map_args['map_class_name'] = 'geodir-map-listing-page';
	}

	if ( empty( $mapview ) ) {
		$mapview = 'ROADMAP';
	}

	// Set default map options
	$map_args['ajax_url']          = geodir_get_ajax_url();
	$map_args['latitude']          = $map_default_lat;
	$map_args['longitude']         = $map_default_lng;
	$map_args['streetViewControl'] = true;
	$map_args['maptype']           = $mapview;
	$map_args['showPreview']       = '0';
	$map_args['maxZoom']           = 21;
	$map_args['bubble_size']       = 'small';

	geodir_draw_map( $map_args );

	$output = ob_get_contents();

	ob_end_clean();

	return $output;
}

add_shortcode( 'listing_slider', 'geodir_sc_listing_slider' );
function geodir_sc_listing_slider( $atts ) {
	ob_start();
	$defaults = array(
		'post_type'          => 'gd_place',
		'category'           => '0',
		'post_number'        => '5',
		'slideshow'          => '0',
		'animation_loop'     => 0,
		'direction_nav'      => 0,
		'slideshow_speed'    => 5000,
		'animation_speed'    => 600,
		'animation'          => 'slide',
		'order_by'           => 'latest',
		'show_title'         => '',
		'show_featured_only' => '',
		'title'				 => '',
	);

	$params = shortcode_atts( $defaults, $atts );



	/*
	 *
	 * Now we begin the validation of the attributes.
	 */
	// Check we have a valid post_type
	if ( ! ( gdsc_is_post_type_valid( $params['post_type'] ) ) ) {
		$params['post_type'] = 'gd_place';
	}

	// Check we have a valid sort_order
	$params['order_by'] = gdsc_validate_sort_choice( $params['order_by'] );

	// Match the chosen animation to our options
	$animation_list = array( 'slide', 'fade' );
	if ( ! ( in_array( $params['animation'], $animation_list ) ) ) {
		$params['animation'] = 'slide';
	}

	// Post_number needs to be a positive integer
	$params['post_number'] = absint( $params['post_number'] );
	if( 0 == $params['post_number'] ){
		$params['post_number'] = 1;
	}

	// Manage the entered categories
	if ( 0 != $params['category'] || '' != $params['category'] ) {
		$params['category'] = gdsc_manage_category_choice( $params['post_type'], $params['category'] );
	}
	// Convert show_title to a bool
	$params['show_title'] = intval( gdsc_to_bool_val( $params['show_title'] ) );

	// Convert show_featured_only to a bool
	$params['show_featured_only'] = intval( gdsc_to_bool_val( $params['show_featured_only'] ) );

	/*
	 * Hopefully all attributes are now valid, and safe to pass forward
	 */

	// redeclare vars after validation

	if(isset($params['direction_nav'])){$params['directionNav'] = $params['direction_nav'];}
	if(isset($params['animation_loop'])){$params['animationLoop'] = $params['animation_loop'];}
	if(isset($params['slideshow_speed'])){$params['slideshowSpeed'] = $params['slideshow_speed'];}
	if(isset($params['animation_speed'])){$params['animationSpeed'] = $params['animation_speed'];}
	if(isset($params['order_by'])){$params['list_sort'] = $params['order_by'];}

	$query_args = array(
		'post_number' 	 => $params['post_number'],
		'is_geodir_loop' => true,
		'post_type'      => $params['post_type'],
		'order_by'       => $params['order_by']
	);

	if ( 1 == $params['show_featured_only'] ) {
		$query_args['show_featured_only'] = 1;
	}

	if ( 0 != $params['category'] && '' != $params['category'] ) {
		$category_taxonomy = geodir_get_taxonomies( $params['post_type'] );
		$tax_query         = array(
			'taxonomy' => $category_taxonomy[0],
			'field'    => 'id',
			'terms'    => $params['category'],
		);

		$query_args['tax_query'] = array( $tax_query );
	}

	$defaults = array(
		'before_widget' => '',
		'after_widget'	=> '',
		'before_title'  => '',
		'after_title'   => '',
	);

	$query_args = array_merge($query_args, $params);

	geodir_listing_slider_widget_output($defaults, $query_args);

	$output = ob_get_contents();

	ob_end_clean();

	return $output;
}

add_shortcode( 'login_box', 'geodir_sc_login_box' );
function geodir_sc_login_box( $atts ) {
	ob_start();

	$defaults = array(
		'before_widget' => '',
		'after_widget'	=> '',
		'before_title'  => '',
		'after_title'   => '',
	);

	geodir_loginwidget_output($defaults , $defaults );

	$output = ob_get_contents();

	ob_end_clean();

	return $output;
}

add_shortcode( 'popular_post_category', 'geodir_sc_popular_post_category' );
function geodir_sc_popular_post_category( $atts ) {
	ob_start();
	global $geodir_post_category_str;
	$defaults = array(
		'category_limit' => 15,
		'before_widget'=> '',
		'after_widget'=> '',
		'before_title'=> '',
		'after_title'=> '',
		'title'=> '',
	);

	$params = shortcode_atts( $defaults, $atts ,'popular_post_category');
	$params['category_limit'] = absint( $params['category_limit'] );
	geodir_popular_post_category_output($params,$params);

	$output = ob_get_contents();

	ob_end_clean();

	return $output;
}

add_shortcode( 'popular_post_view', 'geodir_sc_popular_post_view' );
function geodir_sc_popular_post_view( $atts ) {
	ob_start();
	$defaults = array(
		'post_type'             => 'gd_place',
		'category'              => '0',
		'post_number'           => '5',
		'layout'                => 'gridview_onehalf',
		'add_location_filter'   => '0',
		'list_sort'             => 'latest',
		'use_viewing_post_type' => '1',
		'character_count'       => '20',
		'listing_width'         => '',
		'show_featured_only'    => '0',
		'show_special_only'     => '0',
		'with_pics_only'        => '0',
		'with_videos_only'      => '0',
		'before_widget'			=> '',
		'after_widget'			=> '',
		'before_title'			=> '<h3 class="widget-title">',
		'after_title'			=> '</h3>',
		'title'					=> '',
		'category_title'		=> '',
	);

	$params = shortcode_atts( $defaults, $atts );

	/**
	 * Validate our incoming params
	 */

	// Validate the selected post type, default to gd_place on fail
	if ( ! ( gdsc_is_post_type_valid( $params['post_type'] ) ) ) {
		$params['post_type'] = 'gd_place';
	}

	// Validate the selected category/ies - Grab the current list based on post_type
	$category_taxonomy = geodir_get_taxonomies( $params['post_type'] );
	$categories        = get_terms( $category_taxonomy, array( 'orderby' => 'count', 'order' => 'DESC', 'fields' => 'ids' ) );

	// Make sure we have an array
	if ( ! ( is_array( $params['category'] ) ) ) {
		$params['category'] = explode( ',', $params['category'] );
	}

	// Array_intersect returns only the items in $params['category'] that are also in our category list
	// Otherwise it becomes empty and later on that will mean "All"
	$params['category'] = array_intersect( $params['category'], $categories );

	// Post_number needs to be a positive integer
	$params['post_number'] = absint( $params['post_number'] );
	if( 0 == $params['post_number'] ){
		$params['post_number'] = 1;
	}

	// Validate our layout choice
	// Outside of the norm, I added some more simple terms to match the existing
	// So now I just run the switch to set it properly.
	$params['layout'] = gdsc_validate_layout_choice( $params['layout'] );

	// Validate our sorting choice
	$params['list_sort'] = gdsc_validate_sort_choice( $params['list_sort'] );

	// Validate character_count
	$params['character_count'] = absint( $params['character_count'] );
	if ( 20 > $params['character_count'] ) {
		$params['character_count'] = 20;
	}

	// Validate Listing width, used in the template widget-listing-listview.php
	// The context is in width=$listing_width% - So we need a positive number between 0 & 100
	$params['listing_width'] = gdsc_validate_listing_width( $params['listing_width'] );

	// Validate the checkboxes used on the widget
	$params['add_location_filter']   = gdsc_to_bool_val( $params['add_location_filter'] );
	$params['show_featured_only']    = gdsc_to_bool_val( $params['show_featured_only'] );
	$params['show_special_only']     = gdsc_to_bool_val( $params['show_special_only'] );
	$params['with_pics_only']        = gdsc_to_bool_val( $params['with_pics_only'] );
	$params['with_videos_only']      = gdsc_to_bool_val( $params['with_videos_only'] );
	$params['use_viewing_post_type'] = gdsc_to_bool_val( $params['use_viewing_post_type'] );

	/**
	 * End of validation
	 */

	geodir_popular_postview_output($params, $params);


	$output = ob_get_contents();

	ob_end_clean();

	return $output;
}

add_shortcode( 'recent_reviews', 'geodir_sc_recent_reviews' );
function geodir_sc_recent_reviews( $atts ) {
	ob_start();
	$defaults = array(
		'count' => 5,
	);

	$params = shortcode_atts( $defaults, $atts );

	$count = absint( $params['count'] );
	if ( 0 == $count ) {
		$count = 1;
	}

	$comments_li = geodir_get_recent_reviews( 30, $count, 100, false );

	if ( $comments_li ) {
		?>
		<div class="geodir_sc_recent_reviews_section">
			<ul class="geodir_sc_recent_reviews"><?php echo $comments_li; ?></ul>
		</div>
	<?php
	}
	$output = ob_get_contents();

	ob_end_clean();

	return $output;
}

add_shortcode( 'related_listings', 'geodir_sc_related_listings' );
function geodir_sc_related_listings( $atts ) {
	ob_start();
	$defaults = array(
		'post_number'         => 5,
		'relate_to'           => 'category',
		'layout'              => 'gridview_onehalf',
		'add_location_filter' => 0,
		'listing_width'       => '',
		'list_sort'           => 'latest',
		'character_count'     => 20,
		'is_widget'         => 1,
		'before_title'      => '<style type="text/css">.geodir_category_list_view li{margin:0px!important}</style>',
	);
	// The "before_title" code is an ugly & terrible hack. But it works for now. I should enqueue a new stylesheet.

	$params = shortcode_atts( $defaults, $atts );

	/**
	 * Begin validating parameters
	 */

	// Validate that post_number is a number and is 1 or higher
	$params['post_number'] = absint( $params['post_number'] );
	if ( 0 === $params['post_number'] ) {
		$params['post_number'] = 1;
	}

	// Validate relate_to - only category or tags
	$params['relate_to'] = strtolower( $params['relate_to'] );
	if ( 'category' != $params['relate_to'] && 'tags' != $params['relate_to'] ) {
		$params['relate_to'] = 'category';
	}

	// Validate layout selection
	$params['layout'] = gdsc_validate_layout_choice( $params['layout'] );

	// Validate sorting option
	$params['list_sort'] = gdsc_validate_sort_choice( $params['list_sort'] );

	// Validate add_location_filter
	$params['add_location_filter'] = gdsc_to_bool_val( $params['add_location_filter'] );

	// Validate listing_width
	$params['listing_width'] = gdsc_validate_listing_width( $params['listing_width'] );

	// Validate character_count
	$params['character_count'] = absint( $params['character_count'] );
	if ( 20 > $params['character_count'] ) {
		$params['character_count'] = 20;
	}

	if ( $related_display = geodir_related_posts_display( $params ) ) {
		echo $related_display;
	}
	$output = ob_get_contents();

	ob_end_clean();

	return $output;
}

add_shortcode( 'advanced_search', 'geodir_sc_advanced_search' );
function geodir_sc_advanced_search( $atts ){
	ob_start();
	geodir_get_template_part('listing','filter-form');
	$output = ob_get_contents();

	ob_end_clean();

	return $output;
}
