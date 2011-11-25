<?php
/**
 * @package BMS_FAQ
 * @author Mike Lathrop
 * @version 1.5.1
 */
/*
Plugin Name: BMS Events
Plugin URI: http://bigmikestudios.com
Description: Sets up an Event Custom Post Type and Shortcodes
Depends: bms_showhide/bms_showhide.php
Author: Mike Lathrop
Version: 0.0.1
Author URI: http://bigmikestudios.com
*/

// =============================================================================

//////////////////////////
//
// HELPER FUNCTIONS
//
//////////////////////////

function bms_events_date_format($starttime, $endtime, $allday) {
	$dateformat = ($allday == "true") ? 'M j, Y' : 'M j, Y, g:ia';
	

	if (trim($endtime) == "") {
		// just the starttime, please!
		return(date($dateformat, strtotime($starttime)));
	} else {
		// start and end on same day?
		if ( date('M j, Y', strtotime($starttime)) == date('M j, Y', strtotime($endtime)) ){
			// return one date, two times
			return(date($dateformat, strtotime($starttime))."&ndash;".date('g:ia', strtotime($endtime)));
		} else {
			// return two dates, two times
			return(date($dateformat, strtotime($starttime))." &ndash; ".date($dateformat, strtotime($endtime)));
		}
	}
	$dateformat = ($allday == "true") ? 'M j, Y' : 'M j, Y, g:ia';
	return(date($dateformat, strtotime($timestamp)));
}

// =============================================================================

//////////////////////////
//
// CUSTOM POST TYPE
//
//////////////////////////

// create the custom post type
add_action( 'init', 'register_cpt_event' );

function register_cpt_event() {

    $labels = array( 
        'name' => _x( 'Events', 'event' ),
        'singular_name' => _x( 'Event', 'event' ),
        'add_new' => _x( 'Add New', 'event' ),
        'add_new_item' => _x( 'Add New Event', 'event' ),
        'edit_item' => _x( 'Edit Event', 'event' ),
        'new_item' => _x( 'New Event', 'event' ),
        'view_item' => _x( 'View Event', 'event' ),
        'search_items' => _x( 'Search Events', 'event' ),
        'not_found' => _x( 'No events found', 'event' ),
        'not_found_in_trash' => _x( 'No events found in Trash', 'event' ),
        'parent_item_colon' => _x( 'Parent Event:', 'event' ),
        'menu_name' => _x( 'Events', 'event' ),
    );

    $args = array( 
        'labels' => $labels,
        'hierarchical' => false,
        
        'supports' => array( 'title', 'editor' ),
        
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
  
        'show_in_nav_menus' => false,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'has_archive' => true,
        'query_var' => true,
        'can_export' => true,
        'rewrite' => true,
        'capability_type' => 'post',
		'menu_icon' => WP_PLUGIN_URL .'/bms_events/images/icon.png', // 16px16
    );

    register_post_type( 'event', $args );
}

// =============================================================================

//////////////////////////
//
// ADD DATEPICKER
//
//////////////////////////

if (is_admin()) {
	global $post;
	function bms_events_admin_init() {
		$pluginfolder = get_bloginfo('url') . '/' . PLUGINDIR . '/' . dirname(plugin_basename(__FILE__));
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-datepicker', $pluginfolder . '/jquery.ui.datepicker.min.js', array('jquery', 'jquery-ui-core') );
		wp_enqueue_script('jquery-ui-draggable', $pluginfolder . '/jquery.ui.draggable.js', array('jquery', 'jquery-ui-core') );
		wp_enqueue_script('jquery-ui-droppable', $pluginfolder . '/jquery.ui.droppable.js', array('jquery', 'jquery-ui-core') );
		wp_enqueue_script('jquery-ui-selectable', $pluginfolder . '/jquery.ui.selectable.js', array('jquery', 'jquery-ui-core') );
		wp_enqueue_script('jquery-ui-slider', $pluginfolder . '/jquery.ui.slider.min.js', array('jquery', 'jquery-ui-core', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-selectable') );
		wp_enqueue_script('jquery-ui-timepicker', $pluginfolder . '/jquery-ui-timepicker-addon.js', array('jquery', 'jquery-ui-core', 'jquery-ui-slider', 'jquery-ui-datepicker') );
		
		wp_enqueue_style('jquery.ui.theme', $pluginfolder . '/smoothness/jquery-ui-1.8.16.custom.css');
		wp_enqueue_style('jquery.ui.theme', $pluginfolder . '/jquery-ui-timepicker.css');
		
		wp_register_script('bms_events', $pluginfolder . '/bms_events.js', array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker'));
		wp_enqueue_script('bms_events');
		
	}
	add_action('admin_init', 'bms_events_admin_init');	
}

// =============================================================================

//////////////////////////
//
// ADD META BOX
//
//////////////////////////

if (is_admin()) {
	if (!class_exists('SmartMetaBox')) {
		require_once("../wp-content/plugins/bms_smart_meta_box/SmartMetaBox.php");
	}
	
	new SmartMetaBox('bms_events', array(
		'title'     => 'BMS Events',
		'pages'     => array('event'),
		'context'   => 'normal',
		'priority'  => 'high',
		'fields'    => array(
			array(
				'name' => 'Start Time',
				'id' => 'bms_events_starttime',
				'default' => '',
				'desc' => '',
				'type' => 'text',
			),
			array(
				'name' => 'End Time',
				'id' => 'bms_events_endtime',
				'default' => '',
				'desc' => '',
				'type' => 'text',
			),
			array(
				'name' => 'All Day',
				'id' => 'bms_events_allday',
				'default' => '',
				'desc' => '',
				'type' => 'checkbox',
			),
		)
	));
}

// =============================================================================

//////////////////////////
//
// LISTING SHORTCODE
//
//////////////////////////

function bms_events_listing($atts, $content=null) {
	extract( shortcode_atts( array(
		'limit' => '999',
	), $atts ) );
	
	$args = array(
		"post_type" => "event",
		"meta_key" => "_smartmeta_bms_events_starttime",
		"orderby" => "meta_value",
		"order" => "ASC",
		"numberposts" => $limit,
	);
	$my_posts = get_posts($args);
	
	foreach($my_posts as $my_post) {
		$allday = get_post_meta($my_post->ID, '_smartmeta_bms_events_allday', true);
		$starttime = get_post_meta($my_post->ID, '_smartmeta_bms_events_starttime', true);
		$endtime = get_post_meta($my_post->ID, '_smartmeta_bms_events_endtime', true);
		$permalink = get_permalink($my_post->ID);
		
		$time = bms_events_date_format($starttime, $endtime, $allday);

		$my_content = " ";
		$my_content .= "<small><a href='".$permalink."'>Permalink</a>";
		if( current_user_can('edit_posts') ) $my_content .= "| <a href=".get_edit_post_link($my_post->ID).">Edit</a>";
		$my_content .= "</small>"."\r\n"."\r\n";
		
		$my_content = wpautop($my_content.$my_post->post_content);
		$my_content = do_shortcode($my_content);
		$my_tag =  "[bms_showhide label='<h4>".htmlspecialchars($my_post->post_title, ENT_QUOTES)."</h4>".$time."']".$my_content."[/bms_showhide]";
		$return .= do_shortcode($my_tag);
	}

	return $return;
}

add_shortcode('bms_events_listing', 'bms_events_listing');

// =============================================================================

function bms_events_listing_short($atts, $content=null) {
	extract( shortcode_atts( array(
		'limit' => '999',
	), $atts ) );
	
	$args = array(
		"post_type" => "event",
		"meta_key" => "_smartmeta_bms_events_starttime",
		"orderby" => "meta_value",
		"order" => "ASC",
		"numberposts" => $limit,
	);
	$my_posts = get_posts($args);
	
	$my_content = "<ul class='bms_events_short_listing'>"."\r\n";
	foreach($my_posts as $my_post) {
		$allday = get_post_meta($my_post->ID, '_smartmeta_bms_events_allday', true);
		$starttime = get_post_meta($my_post->ID, '_smartmeta_bms_events_starttime', true);
		$endtime = get_post_meta($my_post->ID, '_smartmeta_bms_events_endtime', true);
		$permalink = get_permalink($my_post->ID);
		
		$time = bms_events_date_format($starttime, $endtime, $allday);

		$my_content .= "<li>"."\r\n";
		$my_content .= "<a href='".$permalink."'><small>$time</small><br />$my_post->post_title</a>";
		$my_content .= "</li>"."\r\n";
	}
	$my_content .= "</ul>"."\r\n";
	return $my_content;
}

add_shortcode('bms_events_listing_short', 'bms_events_listing_short');

// =============================================================================

//////////////////////////
//
// ADD DISPLAY
//
//////////////////////////

add_filter('the_content', 'bms_events_the_content');
function bms_events_the_content($c) {
	global $post;
	
	if ($post->post_type == "event") {
		$starttime = get_post_meta($post->ID, '_smartmeta_bms_events_starttime', true);
		$endtime = get_post_meta($post->ID, '_smartmeta_bms_events_endtime', true);
		$allday = get_post_meta($post->ID, '_smartmeta_bms_people_allday', true);
		
		$time = bms_events_date_format($starttime, $endtime, $allday);
		
		$return .= "<div class='bms-events event-".$post->ID."'>"."\r\n";
		$return .= $time;
		$return .= "</div>"."\r\n";
		
		$c = $return . $c;
	}
	
	return $c;
}

// =============================================================================

//////////////////////////
//
// WIDGET
//
//////////////////////////

class Bms_event_widget extends WP_Widget {
/** constructor */
	function __construct() {
		parent::WP_Widget( 
			/* Base ID */'bms_event_widget', 
			/* Name */'BMS Events', 
			array( 'description' => 'A widget to display discussion questions' ) );
	}

	/** @see WP_Widget::widget */
	function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		$limit = apply_filters( 'widget_title', $instance['limit'] );
		$limit = (is_numeric($limit)) ? array('numberposts' => $limit) : array('numberposts' => 999);
		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title; ?>
		
        <ul>
        <?php
			$args = array(
				"post_type" => "event",
				"meta_key" => "_smartmeta_bms_events_starttime",
				"orderby" => "meta_value",
				"order" => "ASC",
			);
			$args = array_merge($args, $limit);
			$my_posts = get_posts($args);
			foreach($my_posts as $my_post) {
				
				$allday = get_post_meta($my_post->ID, '_smartmeta_bms_events_allday', true);
				$starttime = get_post_meta($my_post->ID, '_smartmeta_bms_events_starttime', true);
				$endtime = get_post_meta($my_post->ID, '_smartmeta_bms_events_endtime', true);
				$permalink = get_permalink($my_post->ID);
				
				$time = bms_events_date_format($starttime, $endtime, $allday);
				
				?>
				<li><a href='<?php echo $permalink;?>'><small><?php echo $time;?></small><br /><?php echo $my_post->post_title?></a></li>
				<?php
		
			}
		?>
        </ul>
        <?php
		echo $after_widget; 
	}

	/** @see WP_Widget::update */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['limit'] = strip_tags($new_instance['limit']);
		return $instance;
	}

	/** @see WP_Widget::form */
	function form( $instance ) {
		if ( $instance ) {
			$title = esc_attr( $instance[ 'title' ] );
			$limit = esc_attr( $instance[ 'limit' ] );
		}
		else {
			$title = __( 'Events', 'text_domain' );
			$limit = "";
		}
		?>
		<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
        <p>
		<label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('How many questions to display (leave blank for no limit):'); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="text" value="<?php echo $limit; ?>" />
		</p>
		<?php 
	}

} // class Foo_Widget
add_action( 'widgets_init', create_function( '', 'register_widget("Bms_event_widget");' ) );

?>