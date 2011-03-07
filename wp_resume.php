<?php
/*
Plugin Name: WP Resume
Plugin URI: http://ben.balter.com/2010/09/12/wordpress-resume-plugin/
Description: Out-of-the-box plugin which utilizes custom post types and taxonomies to add a snazzy resume to your personal blog or Web site. 
Version: 1.6b
Author: Benjamin J. Balter
Author URI: http://ben.balter.com/
License: GPL2
*/

/**
 * @author Benjamin J. Balter
 * @shoutout Andrew Nacin (http://andrewnacin.com) for help with CPTs
 * @shoutout Andrew Norcross (http://andrewnorcross.com) for the drag-and-drop CSS
 */

/**
 * Registers the "resume block" custom post type and the the section and organization custom taxonomy
 * @since 1.0a
 */
function wp_resume_register_cpt_and_t() {
	
	//Custom post type labels array
	$labels = array(
    'name' => 'Resume',
    'singular_name' => 'Resume',
    'add_new' => _x('Add New Position', 'wp_resume_position'),
    'add_new_item' => 'Add Position',
    'edit_item' => 'Edit Position',
    'new_item' => 'New Position',
    'view_item' => 'View Position',
    'search_items' => 'Search Positions',
    'not_found' =>  'No Positions Found',
    'not_found_in_trash' => 'No Positions Found in Trash',
    'parent_item_colon' => ''
  );
  
  //Custom post type settings array
  $args = array(
    'labels' => $labels,
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true, 
    'query_var' => true,
	'rewrite' => false,
    'capability_type' => 'post',
    'hierarchical' => false,
    'menu_position' => null,
    'register_meta_box_cb' => 'wp_resume_meta_callback',
    'supports' => array( 'title', 'editor', 'revisions', 'custom-fields', 'page-attributes', 'author'),
    'taxonomies' => array('wp_resume_section', 'wp_resume_organization'),
  ); 
  
  //Register the "wp_resume_position" custom post type
  register_post_type( 'wp_resume_position', $args );
  
	//Section labels array
	 $labels = array(
 	   'name' => _x( 'Sections', 'taxonomy general name' ),
 	   'singular_name' => _x( 'Section', 'taxonomy singular name' ),
 	   'search_items' =>  __( 'Search Sections' ),
 	   'all_items' => __( 'All Sections' ),
 	   'parent_item' => __( 'Parent Section' ),
 	   'parent_item_colon' => __( 'Parent Section:' ),
 	   'edit_item' => __( 'Edit Section' ), 
 	   'update_item' => __( 'Update Section' ),
 	   'add_new_item' => __( 'Add New Section' ),
 	   'new_item_name' => __( 'New Section Name' ),
 	 ); 	
 	 
	//Register section taxonomy	
	register_taxonomy( 'wp_resume_section', 'wp_resume_position', array( 'hierarchical' => true, 'labels' => $labels,  'query_var' => true, 'rewrite' => false ) );
	
	//orgs labels array
	$labels = array(
 	   'name' => _x( 'Organizations', 'taxonomy general name' ),
 	   'singular_name' => _x( 'Organization', 'taxonomy singular name' ),
 	   'search_items' =>  __( 'Search Organizations' ),
 	   'all_items' => __( 'All Organizations' ),
 	   'parent_item' => __( 'Parent Organization' ),
 	   'parent_item_colon' => __( 'Parent Organization:' ),
 	   'edit_item' => __( 'Edit Organization' ), 
 	   'update_item' => __( 'Update Organization' ),
 	   'add_new_item' => __( 'Add New Organization' ),
 	   'new_item_name' => __( 'New Organization Name' ),
 	 ); 
 	 
	//Register organization taxonomy
	register_taxonomy( 'wp_resume_organization', 'wp_resume_position', array( 'hierarchical' => true, 'labels' => $labels,  'query_var' => true, 'rewrite' => false ) );
	
}
add_action( 'init', 'wp_resume_register_cpt_and_t', 10 );


/**
 * Customizes the edit screen for our custom post type
 * @since 1.0a
 */
function wp_resume_meta_callback() {

	//pull out the standard post meta box , we don't need it
	remove_meta_box( 'postcustom', 'wp_resume_position', 'normal' );
	
	//build our own section taxonomy selector using radios rather than checkboxes
	//We use the same callback for both taxonomies and just pass the taxonomy type as an argument
	add_meta_box( 'wp_resume_sectiondiv', 'Section', 'wp_resume_taxonomy_box', 'wp_resume_position', 'side', 'low', array('type'=>'wp_resume_section') );
	
	//same with orgs 
	add_meta_box( 'wp_resume_organizationdiv', 'Organization', 'wp_resume_taxonomy_box', 'wp_resume_position', 'side', 'low', array('type'=>'wp_resume_organization') ); 
	
	//build the date meta input box
	add_meta_box( 'dates', 'Date', 'wp_resume_date_box', 'wp_resume_position', 'normal', 'high');
	
	//build custom order box w/ helptext
	add_meta_box( 'pageparentdiv', 'Resume Order', 'wp_resume_order_box', 'wp_resume_position', 'side', 'low');
}

function wp_resume_order_box($post) {
?>
	<p><strong>Order</strong></p>
	<p>	
		<div style="float:right; width: 200px; padding-right:10px; margin-top: -1em; display: inline;">
			<i>Hint:</i> Your resume will be sorted based on this number (ascending). <a href="#" id="wp_resume_help_toggle">More</a><br /> <br />

			<div id="wp_resume_help">When you add a new position, feel free to leave this number at "0" and a best guess will be made based on the position's end date (reverse chronological order). <br /><br />Of Course, you can always <a href="edit.php?post_type=wp_resume_position&page=wp_resume_options#sections">fine tune your resume order</a> on the options page.</div>
		</div>
		<label class="screen-reader-text" for="menu_order">Order</label>
		<input type="text" name="menu_order" size="4" id="menu_order" value="<?php echo $post->menu_order; ?>">
	</p>
	<p style="clear: both; height: 5px;" id="wp_resume_clearfix"> </p>
	<script>
		jQuery(document).ready(function($){
			$('#wp_resume_help, #wp_resume_clearfix').hide();
			$('#wp_resume_help_toggle').click(function(){
				$('#wp_resume_help, #wp_resume_clearfix').toggle('fast');
				if ($(this).text() == "More")
					$(this).text('Less');
				else
					$(this).text('More');
				return false;
			});
		});
	</script>
<?php
}

/**
 * Generates the taxonomy radio inputs 
 * @since 1.0a
 * @params object $post the post object WP passes
 * @params object $box the meta box object WP passes (with our arg stuffed in there)
 */
function wp_resume_taxonomy_box( $post, $type ) {

	//pull the type out from the meta box object so it's easier to reference
	if ( is_array( $type) )
		$type = $type['args']['type'];
	
	//get the taxonomies details
	$taxonomy = get_taxonomy($type);
		
	//grab an array of all terms within our custom taxonomy, including empty terms
	$terms = get_terms( $type, array( 'hide_empty' => false ) );

	//garb the current selected term where applicable so we can select it
	$current = wp_get_object_terms( $post->ID, $type );
	
	//loop through the terms
	foreach ($terms as $term) {
		
		//build the radio box with the term_id as its value
		echo '<input type="radio" name="'.$type.'" value="'.$term->term_id.'" id="'.$term->slug.'"';
		
		//if the post is already in this taxonomy, select it
		if ( isset( $current[0]->term_id ) )
			checked( $term->term_id, $current[0]->term_id );
		
		//build the label
		echo '> <label for="'.$term->slug.'">' . $term->name . '</label><br />'. "\r\n";
	}
		echo '<input type="radio" name="'.$type.'" value="" id="none" ';
		checked( empty($current[0]->term_id) );
		echo '/> <label for="none">None</label><br />'. "\r\n"; ?>
		
		<a href="#" id="add_<?php echo $type ?>_toggle">+ <?php echo $taxonomy->labels->add_new_item; ?></a>
		<div id="add_<?php echo $type ?>_div" style="display:none">
			<label for="new_<?php echo $type ?>"><?php echo $taxonomy->labels->singular_name; ?>:</label> 
			<input type="text" name="new_<?php echo $type ?>" id="new_<?php echo $type ?>" /><br />
<?php if ($type == 'wp_resume_organization') { ?>
			<label for="new_<?php echo $type ?>_location" style="padding-right:24px;">Location:</label> 
			<input type="text" name="new_<?php echo $type ?>_location" id="new_<?php echo $type ?>_location" /><br />
<?php } ?>
			<input type="button" value="Add New" id="add_<?php echo $type ?>_button" />
			<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" id="<?php echo $type ?>-ajax-loading" style="display:none;" alt="" />
		</div>
		<script>
			jQuery(document).ready(function($){
				$('#add_<?php echo $type ?>_toggle').click(function(){
					$('#add_<?php echo $type ?>_div').toggle();
				});
				$('#add_<?php echo $type ?>_button').click(function() {
					$('#<?php echo $type ?>-ajax-loading').show();
					$.post('admin-ajax.php?action=add_<?php echo $type; ?>', $('#new_<?php echo $type; ?>, #new_<?php echo $type; ?>_location, #_ajax_nonce-add-<?php echo $type; ?>, #post_ID').serialize(), function(data) { 
						$('#<?php echo $type; ?>div .inside').html(data); 
						});
				});
			});
		</script>
<?php
	//nonce is a funny word
	wp_nonce_field( 'add_'.$type, '_ajax_nonce-add-'.$type );
	wp_nonce_field( 'wp_resume_taxonomy', 'wp_resume_nonce'); 
}

/**
 * Processes AJAX request to add new terms
 * @since 1.2
 */
function wp_resume_ajax_add() {
	
	//pull the taxonomy type (section or organization) from the action query var
	$type = substr($_GET['action'],4);
	
	//pull up the taxonomy details
	$taxonomy = get_taxonomy($type);
	
	//check the nonce
	check_ajax_referer( $_GET['action'] , '_ajax_nonce-add-' . $taxonomy->name );
	
	//check user capabilities
	if ( !current_user_can( $taxonomy->cap->edit_terms ) )
		die('-1');

	//insert term
	$term = wp_insert_term( $_POST['new_'. $type], $type, array('description' => $_POST['new_'. $type . '_location']) );
	
	//associate position with new term
  	wp_set_object_terms( $_POST['post_ID'], $term['term_id'], 'wp_resume_section' );
  	
  	//get updated post to send to taxonomy box
	$post = get_post( $_POST['post_ID'] );
	
	//return the HTML of the updated metabox back to the user so they can use the new term
	wp_resume_taxonomy_box( $post, $type );
	exit();
}

add_action('wp_ajax_add_wp_resume_section', 'wp_resume_ajax_add');
add_action('wp_ajax_add_wp_resume_organization', 'wp_resume_ajax_add');

/**
 * Generates our date custom metadata box
 * @since 1.0a
 * @params object $post the post object WP passes
 */
function wp_resume_date_box( $post ) {	

	//pull the current values where applicable
	$from = get_post_meta( $post->ID, 'wp_resume_from', true );
	$to = get_post_meta( $post->ID, 'wp_resume_to', true );
	
	//format and spit out
	echo '<label for="from">From</label> <input type="text" name="from" id="from" value="'.$from.'" /> ';
	echo '<label for="to">to</label> <input type="text" name="to" id="to" value="'.$to.'" />';

}

/**
 * Saves our custom taxonomies and date metadata on post add/update
 * @since 1.0a
 * @params int $post_id the ID of the current post as passed by WP
 */
function wp_resume_save_wp_resume_position( $post_id ) {

	//Verify our nonce, also varifies that we are on the edit page and not updating elsewhere
  	if ( !isset( $_POST['wp_resume_nonce'] ) || !wp_verify_nonce( $_POST['wp_resume_nonce'], 'wp_resume_taxonomy' , 'wp_resume_nonce' ) )
  	  	return $post_id;
  	  	  	
  	//If we're autosaving we don't really care all that much about taxonomies and metadata
  	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
  	  	return $post_id;
  	  	
  	//If this is a post revision and not the actual post, kick
  	//(the save_post action hook gets called twice on every page save)
  	if ( wp_is_post_revision($post_id) )
  		return $post_id;
  	
  	//Verify user permissions
  	if ( !current_user_can( 'edit_post', $post_id ) )
  	    return $post_id;
     	   	
  	//Associate the wp_resume_position with our taxonomies
  	wp_set_object_terms( $post_id, (int) $_POST['wp_resume_section'], 'wp_resume_section' );
  	wp_set_object_terms( $post_id, (int) $_POST['wp_resume_organization'], 'wp_resume_organization' );
  	
 	//update the posts date meta
	update_post_meta( $post_id, 'wp_resume_from', $_POST['from'] );
  	update_post_meta( $post_id, 'wp_resume_to', $_POST['to'] );
 		 	  	
  	//If they did not set a menu order, calculate a best guess bassed off of chronology
  	//(menu order uses the posts's menu_order field and is 1 bassed by default)
  	if ($_POST['menu_order'] == 0) {
  		
  		//grab the DB Obj.
  		global $wpdb;
  	
  		//calculate the current timestamp
  		$timestamp = strtotime( $_POST['to'] );
  		if ( !$timestamp ) $timestamp = time();
		
		//set a counter
		$new_post_position = 1;
		
		//loop through posts 
		$section = get_term ($_POST['wp_resume_section'], 'wp_resume_section');
		$args = array(
			'post_type' => 'wp_resume_position',
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'numberposts' => -1,
			'wp_resume_section' =>	$section->slug,
			'exclude' => $post_id
		);
		$posts = get_posts( $args );

		foreach ($posts as $post) {
	
  			//build timestamp of post we're checking
  			$ts_check = strtotime( get_post_meta( $post->ID, 'wp_resume_to', true) );
  			if (!$ts_check) 
  				$ts_check = time();
  			
  			//If we've inserted our new post in the menu_order, increment all subsequent positions
  			if ($new_post_position != 1)
  				//manually update the post b/c calling wp_update_post would create a recurssion loop
		  		$wpdb->update($wpdb->posts,array('menu_order'=>$post->menu_order+1),array('ID'=>$post->ID));
  			
  			//If the new post's timestamp is earlier than the current post, stick the new post here
  			if ($timestamp <= $ts_check && $new_post_position == 1) 
  				$new_post_position = $post->menu_order + 1;	
  		
  		}
  		
		//manually update the post b/c calling wp_update_post would create a recurssion loop
  		$wpdb->update($wpdb->posts,array('menu_order'=>$new_post_position),array('ID'=>$post_id));
  		
   	}

}
add_action( 'save_post', 'wp_resume_save_wp_resume_position' );

/**
 * Function used to parse the date meta and move to human-readable format
 * @since 1.0a
 * @param int $ID post ID to generate date for
 */
function wp_resume_format_date( $ID ) {

	//Grab from and to post meta
	$from = get_post_meta( $ID, 'wp_resume_from', true ); 
	$to = get_post_meta( $ID, 'wp_resume_to', true ); 
	
	//if we have a start date, format as "[from] - [to]" (e.g., May 2005 - May 2006)
	if ( $from ) return '<span class="dtstart">' . $from . '</span> &ndash; <span class="dtend">' . $to . '</span>';
	
	//if we only have a to, just pass back the to (e.g., "May 2015")
	if ( $to ) return '<span class="dtend">' . $to . '</span>';
	
	//If there's no date meta, just pass back an empty string so we dont generate errors
	return '';
}

/**
 * Takes the section term taxonomy and re-keys it to the user specified order
 * @returns array of term objects in user-specified order
 * @since 1.0a
 */
function wp_resume_get_sections( $hide_empty = true, $author = '' ) {

	//init array
	$output = array();
	
	//set default author
	if ($author == '') {
		$user = get_current_user();
		$author = $user->user_login;
	}

	//get all sections ordered by term_id (order added)
	$sections = get_terms( 'wp_resume_section', array('hide_empty' => $hide_empty ) );
			
	//get the plugin options array to pulll the user-specified order
	$options = wp_resume_get_options();
	
	//pull out the order array (form: term_id => order)
	$section_order = $options[$author][ 'order' ];
		
	//Loop through each section
	foreach( $sections as $ID => $section ) {
		
		//if the term is in our order array
		if ( is_array($section_order) && array_key_exists( $section->term_id, $section_order ) ) { 
		
			//push the term object into the output array keyed to it's order
			$output[ $section_order[$section->term_id] ] = $section;
		
			//pull the term out of the original array
			unset($sections[$ID]);
		
		}
	}
	
	//for those terms that we did not have a user-specified order, stick them at the end of the output array
	foreach($sections as $section) $output[] = $section;
	
	//sort by key
	ksort($output);
				
	//return the new array keyed to order
	return $output;
	
}

/**
 * Queries for all the resume blocks within a given section
 * @params string $section section slug
 * @returns array array of post objects
 * @since 1.0a
 */
function wp_resume_query( $section, $author = '' ) {
	global $wp_resume_author;
	
	//if the author isn't passed as a function arg, see if it has been set by the shortcode
	if ( $author == '' && isset( $wp_resume_author ) )
 		$author = $wp_resume_author;
 		 		
	//build our query
	$args = array(
		'post_type' => 'wp_resume_position',
		'orderby' => 'menu_order',
		'order' => 'ASC',
		'nopaging' => true,
		'wp_resume_section' => $section,
		'author_name' => $author
	);
		
	//query and return
	$query = new wp_query($args);
	return $query;
}

/**
 * Retrieves the org associated with a given position
 * @since 1.1a
 */
function wp_resume_get_org( $postID ) {

	$organization = wp_get_object_terms( $postID, 'wp_resume_organization' );
	if ( !is_array( $organization ) ) return false;
	return $organization[0];
	
}

/**
 * Get's the options, filters HTML
 * @since 1.2
 */
function wp_resume_get_options() {
	$options = get_option('wp_resume_options');
	return $options;
}


/**
 * Adds custom CSS to WP's queue
 * Checks to see if file 'resume-style.css' exists in the current template directory, otherwise includes default
 * @since 1.0a
 */
function wp_resume_enqueue() {

    if ( file_exists ( get_theme_root() . '/' . get_template() . '/resume-style.css' ) )
    	wp_enqueue_style('wp-resume-custom-stylesheet', get_bloginfo('template_directory') . '/resume-style.css' );
	else 
		wp_enqueue_style('wp-resume-default-stylesheet', plugins_url(  'resume-style.css', __FILE__ ) );
}

add_action( 'wp_print_styles', 'wp_resume_enqueue' );

/**
 * Adds an options submenu to the resume menu in the admin pannel
 * @since 1.0a
 */
function wp_resume_menu() {
	
	add_submenu_page( 'edit.php?post_type=wp_resume_position', 'Resume Options', 'Options', 'manage_options', 'wp_resume_options', 'wp_resume_options' );

}
add_action( 'admin_menu', 'wp_resume_menu' );

/**
 * Tells WP that we're usign a custom setting
 */
function wp_resume_options_init() {
    
    register_setting( 'wp_resume_options', 'wp_resume_options', 'wp_resume_options_validate' );
	$options = wp_resume_get_options();

	//If they just activated, make sure they have some sections
	//This is a work around b/c register_acivation hook is having issues recognizing the custom taxonmomy
	if ( isset($options['just_activated']) && $options['just_activated'] ) {

		//upgrade DB after init so we have our CPTs
		$options = wp_resume_upgrade_db();
		
		//grab current user
		$current_user = wp_get_current_user();
		
		//check to see if we have any sections
		if ( sizeof( wp_resume_get_sections(false, $current_user->user_login ) ) == 0 ) {
			//add the sections
			wp_insert_term( 'Education', 'wp_resume_section');
			wp_insert_term( 'Experience', 'wp_resume_section' );
			wp_insert_term( 'Awards', 'wp_resume_section' );
		}

		//get rid of the flag
		$options['just_activated'] = false;

		//set default order if none exists
		$i = 0;
		foreach ( wp_resume_get_sections( false, $current_user->user_login ) as $section) {
			if ( empty ( $options[$current_user->user_login]['order'][$section->term_id] ) )
				$options[$current_user->user_login]['order'][$section->term_id] = $i++;
		}
		
		//store the new options
		update_option('wp_resume_options',$options);
		
	
	} 
	
	//If we are on the wp_resume_options page, enque the tinyMCE editor
	if ( !empty ($_GET['page'] ) && $_GET['page'] == 'wp_resume_options' ) {
		wp_enqueue_script('editor');
		add_thickbox();
		wp_enqueue_script('media-upload');
		wp_enqueue_script('post');
		add_action( 'admin_print_footer_scripts', 'wp_tiny_mce', 25 );
		wp_enqueue_style('wp_resume_admin_stylesheet', plugins_url(  'admin-style.css', __FILE__ ) );
		wp_enqueue_script( array("jquery", "jquery-ui-core", "interface", "jquery-ui-sortable", "wp-lists", "jquery-ui-sortable") );
	}
}

add_action( 'admin_init', 'wp_resume_options_init' );


/**
 * Valdidates options submission data and stores position order
 * @params array $data post data
 * @since 1.5
 * @returns array of validated data (without position order)
 */
function wp_resume_options_validate($data) {
	
	//grab the existing options, we must hand WP back a complete option array, including other users
	$output = wp_resume_get_options();
	
	//make sure we're POSTing
	if ( empty($_POST) )
		return $data;
	
	//strip html from fields
	$data['name'] = wp_filter_post_kses( $data['name'] );

	foreach ($data['contact_info_field'] as $id=>$value) {
		if ( !$value ) continue;
		
		$field = explode('|',$data['contact_info_field'][$id]);
		if ( sizeof($field) == 1)
		    $data['contact_info'][$field[0]] = wp_filter_post_kses( $data['contact_info_value'][$id] );
		else
		    $data['contact_info'][$field[0]][$field[1]] = wp_filter_post_kses( $data['contact_info_value'][$id] );

	}
	
	//sanitize section order data
	foreach ($data['order'] as &$section)
		$section = intval( $section );
		
	//store position order data
	foreach ($data['position_order'] as $positionID => $order) {
		$post['ID'] = intval( $positionID );
		$post['menu_order'] = intval( $order );
		wp_update_post( $post );
 	}
	unset ( $data['position_order'] );
	
	//move user-specific fields to user sub-array
	$authors = explode(',', wp_list_authors( array('exclude_admin' => false, 'hide_empty' => false, 'echo' => false, 'html'=> false ) ) );
	if ( sizeof($authors) == 1 ) {
		$current_author = $authors[0];
	} else if ( $_POST['old_user'] != $_POST['user'] ) {
		//if this is an auto save as a result of the author dropdown changing, 
		//save as old author, not author we're moving to
		$current_author = $_POST['old_user'];
		
		//Because we post to options.php and then get redirected, 
		//trick WP into appending the user as a parameter so we can update the dropdown
		$_REQUEST['_wp_http_referer'] .= '&user=' . $_POST['user'];
		
	} else {
		//if this is a normal submit, just grab the author from the dropdown
		$current_author = $_POST['user'];
	}
	
	//move user-specific fields to output array
	$fields = array('name', 'summary', 'contact_info', 'order');
	foreach ($fields as $field) {
		$output[$current_author][$field] = $data[$field];
	}
	
	//move site-wide fields to output array
	$fields = array('fix_ie');
	foreach ($fields as $field) {
		$output[$field] = $data[$field];
	}

	return $output;
}

function wp_resume_contact_fields() {
	$fields['email'] = 'E-Mail';
	$fields['tel'] = 'Phone';
	$fields['other'] = 'Other';	
	$fields['adr']['street-address'] = "Street Address";
	$fields['adr']['locality'] = "City/Locality";
	$fields['adr']['region'] = 'State/Region';
	$fields['adr']['postal-code'] = 'Zip/Postal Code';
	$fields['adr']['country-name'] = 'Country';
	return $fields;
}


function wp_resume_contact_info_row( $value = '', $field_id = '' ) { ?>
	<li id="contact_info_row[]" class="contact_info_row">
	    <select name="wp_resume_options[contact_info_field][]" id="contact_info_field[]">
	    <option></option>
	    <?php 	foreach (wp_resume_contact_fields() as $id => $field) { ?>
				<?php 	if ( is_array($field) ) {
							foreach ($field as $subid => $subfield) { ?>
								<option value="<?php echo $id . '|' . $subid; ?>" <?php if ($field_id == $subid) echo "SELECTED"; ?>>
									<?php echo $subfield; ?>
								</option>				
							<?php }
						} else { ?>
							<option value="<?php echo $id; ?>" <?php if ($field_id == $id) echo "SELECTED"; ?>><?php echo $field; ?></option>	
						<?php } ?>
	    <?php } ?>
	    </select>
	    <input type="text" name="wp_resume_options[contact_info_value][]" id="contact_info_value[]" value="<?php echo $value; ?>"/> <br />
	</li>
<?php } 

/**
 * Creates the options sub-panel
 * @since 1.0a
 */
function wp_resume_options() { 	
?>
<div class="wrap">
	<h2>Resume Options</h2>
	<form method="post" action='options.php' id="wp_resume_form">
<?php 
		
//provide feedback
settings_errors();

//Tell WP that we are on the wp_resume_options page
settings_fields( 'wp_resume_options' ); 

//Pull the existing options from the DB
$options = wp_resume_get_options();

//set up the current author
$authors = explode(', ', wp_list_authors( array('exclude_admin' => false, 'hide_empty' => false, 'echo' => false, 'html'=> false ) ) );

if ( sizeof($authors) == 1 ) {
	//if there's only one author, that's our author
	$current_author = $authors[0];
} else if ( isset($_GET['user'] ) ) {
	//if there's multiple authors, look for post data from author drop down
	$current_author = $_GET['user'];
} else {
	//otherwise, assume the current user
	$current_user = wp_get_current_user();
	$current_author = $current_user->user_login;

}

?>
	<table class="form-table">
		<tr valign="top">
			<th scope="row">Usage</label></th>
			<td>
				<strong>To use WP Resume...</strong>
				<ol>
					<li>Add content to your resume through the menus on the left</li>
					<li>If you wish, add your name, contact information, summary, and order your resume below</li>
					<li>Create a new page as you would normally
					<li>Add the text <code>[wp_resume]</code> to the page's body</li>
					<li>Your resume will now display on that page.</li>
				</ol><br />
				<strong>Want to have multiple resumes on your site?</strong> <a href="#" id="toggleMultiple">Yes!</a><br />
				<div id="multiple">
				WP Resume associates each resume with a user. To create a second resume...
				<ol>
					<li style="font-size: 11px;">Simply <a href="user-new.php">add a new user</a> (or select an existing user in step two).</li>
					<li style="font-size: 11px;"><a href="post-new.php?post_type=wp_resume_position">Add positions</a> as you would normally, being sure to select that user as the position's author. You may need to display the author box by enabling it in the "Screen Options" toggle in the top-right corner of the position page.</li>
					<li style="font-size: 11px;">Select the author from the drop down below and fill in the name, contact info, and summary fields (optional).</li>
					<li style="font-size: 11px;"><a href="post-new.php?post_type=page">Create a new page</a> and add the <code>[wp_resume]</code> shortcode, similar to above, but set the page author to the resume's author (the author from step two). Again, you may need to enable the author box.</li>
				</ol>
 				 <em>Note:</em> To embed multiple resumes on the same page, you can alternatively use the syntax <code>[wp_resume user="user_login"]</code> where <code>user_login</code> is the username of the resume's author.
 				 </div>
			</td>
		</tr>
		<?php 
			if (sizeof($authors) > 1) {
			?>
		<tr valign="top">
			<th scope="row">User</label></th>
			<td>
				<select name="user" id="user">
					<?php foreach ($authors as $author) { ?>
					<option <?php selected($author, $current_author); ?>><?php echo $author; ?></option>
					<?php } ?>
				</select>
				<input type="hidden" name="old_user" value="<?php echo $current_author; ?>" />
			</td>
		</tr>
		<?php } ?>
		<tr valign="top">
			<th scope="row"><label for="wp_resume_options[name]">Name</label></th>
			<td>
				<input name="wp_resume_options[name]" type="text" id="wp_resume_options[name]" value="<?php if ( isset( $options[$current_author]['name'] ) ) echo $options[$current_author]['name']; ?>" class="regular-text" /><BR />
				<span class="description">Your name -- displays on the top of your resume.</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Contact Information</th>
			<td>
				<ul class="contact_info_blank" style="display:none;">
					<?php wp_resume_contact_info_row(); ?>
				</ul>
				<ul id="contact_info">
					<?php array_walk_recursive($options[$current_author]['contact_info'], 'wp_resume_contact_info_row'); ?>
				</ul>
				<a href="#" id="add_contact_field">+ Add Field</a><br />
				<script>
					jQuery(document).ready(function($){
						$('#contact_info').append( $('.contact_info_blank').html() );
						$('.contact_info_row:last').show();
						$('#add_contact_field').click(function(){
							$('#contact_info').append( $('.contact_info_blank').html() );
							$('.contact_info_row:last').fadeIn();						
							return false;
						});
					});
				</script>
				<span class="description">(optional) Add any contact info you would like included in your resume.</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="wp_resume_options[summary]">Summary</label></th>
			<td id="poststuff">
			<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">
				<?php the_editor( ( isset($options[$current_author]['summary'] ) ) ? $options[$current_author]['summary'] : '', "wp_resume_options[summary]" ); ?>	
			</div>
			<span class="description">(optional) Plain-text summary of your resume, professional goals, etc. Will appear on your resume below your contact information before the body.</div>	
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Resume Order</th>
			<td>
			<ul id="sections">
<?php foreach ( wp_resume_get_sections( false, $current_author ) as $section ) { ?>

				<li class="section" id="<?php echo $section->term_id; ?>">
<?php 				echo $section->name; ?>
					<ul class="organizations">
<?php				$current_org='';
					$posts = wp_resume_query( $section->slug, $current_author );
					if ( $posts->have_posts() ) : while ( $posts->have_posts() ) : $posts->the_post();
						$organization = wp_resume_get_org( get_the_ID() ); 
						if ($organization && $organization->term_id != $current_org) {
							if ($current_org != '') { 
?>								
									</ul><!-- .positions -->
								</li><!-- .organization -->
<?php 						} 
							$current_org = $organization->term_id; 
?>
							<li class="organization" id="<?php echo $organization->term_id; ?>">
								<?php echo $organization->name; ?>
								<ul class="positions">
<?php						}  ?>
								<li class="position" id="<?php the_ID(); ?>">
									<?php echo the_title(); ?> <?php if ($date = wp_resume_format_date( get_the_ID() ) ) echo "($date)"; ?>
								</li><!-- .position -->
<?php 				endwhile; ?>
								</ul><!-- .positions -->				
<?php				endif;	 ?>
						</li><!-- .organization -->
					</ul><!-- .organizations -->
				</li><!-- .section -->
				<?php } ?>
			</ul><!-- #sections -->
			<span class="description">New positions are automatically displayed in reverse chronological order, but you can fine tune that order by rearranging the elements in the list above.</span>
			</td>
		</tr>
		<tr valign="top">
			<th scrope="row">Customizing WP Resume</th>
			<td>
				<Strong>Style Sheets</strong><br />
				Although some styling is included by default, you can customize the layout by modifying <a href='theme-editor.php'>your theme's stylesheet</a>.<br /><br />
				
				<strong>Templates</strong> <br />
				Any WP Resume template file (resume.php, resume-style.css, resume-text.php, etc.) found in your theme's directory will override the plugin's included template. Feel free to copy the file from the plugin directory into your theme's template directory and modify the file to meet your needs.<br /><br />
				
				<strong>Feeds</strong> <br />
				WP Resume allows you to access your data in three machine-readable formats. By default, the resume outputs in an <a href="http://microformats.org/wiki/hresume">hResume</a> compatible format. A JSON feed can be generated by appending <code>?feed=json</code> to your resume page's URL and a plain-text alternative (useful for copying and pasting into applications and forms) is available by appending <code>?feed=text</code> to your resume page's URL.<br /><br />
			</td>
		</tr>
		<tr valign="top">
			<th scrope="row">Force IE HTML5 Support</th>
			<td>
				<input type="radio" name="wp_resume_options[fix_ie]" id="fix_ie_yes" value="1" <?php checked($options['fix_ie'], 1); ?>/> <label for="fix_ie_yes">Yes</label><br />
				<input type="radio" name="wp_resume_options[fix_ie]" id="fix_ie_yes" value="0" <?php checked($options['fix_ie'], 0); ?>/> <label for="fix_ie_no">No</label><br />
				<span class="description">If Internet Explorer breaks your resume's formatting, conditionally including a short Javascript file should force IE to recognize html5 semantic tags.</span>
			</td>
		</tr>
	</table>
	<script>
	jQuery(document).ready(function($) {
	
	$('#multiple').hide();
	$('#toggleMultiple').click(function() {
		$('#multiple').toggle('fast');
		if ($(this).text() == "Yes!")
			$(this).text('No.');
		else
			$(this).text('Yes!');
		return false;
	});

    $("#sections, .positions, .organizations").sortable({
    	axis:'y', 
    	containment: 'parent',
    	opacity: .5,
    	update: function(){},
		placeholder: 'placeholder',
		forcePlaceholderSize: 'true'
    });
    $("#sections").disableSelection();
	$('.button-primary').click(function(){
		var i = 0;
		$('.section').each(function(){
			$('#wp_resume_form').append('<input type="hidden" name="wp_resume_options[order]['+$(this).attr('id')+']" value="' + i + '">');
			i = i +1;
		});
		var i = 1;
		$('.position').each(function(){
			$('#wp_resume_form').append('<input type="hidden" name="wp_resume_options[position_order]['+$(this).attr('id')+']" value="' + i + '">');
			i = i +1;
		});
	});
	$('#user').change(function(){
		$('.button-primary').click();		
	}); 
	
});
	</script>
	<p class="submit">
         <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	</p>
	</form>
</div>
<?php
}

/**
 * Initializes plugin on activation (sets default values, flushes rewrite rules)
 * @since 1.0a
 */
function wp_resume_activate() {
			
	//get current options incase this is a reactivate
	$options = wp_resume_get_options();
	
	//Set the update flag
	$options['just_activated'] = true;
	
	//set our new options
	update_option('wp_resume_options', $options );
	
	//flush rewrite rules
	global $wp_rewrite;
   	$wp_rewrite->flush_rules();

} 
   
register_activation_hook( __FILE__, 'wp_resume_activate' );


/**
 * Updates posts, taxonomies, and options from 1.1 to 1.2 and pre 1.5 to 1.5
 * @since 1.2
 */
function wp_resume_upgrade_db() {
		
	//get the Database object
	global $wpdb;

	//get our options
	$options = wp_resume_get_options();
	
	//set order array
	if ( !isset( $options['order'] ) ) 
		$options['order'] = array();
		
	//add fix ie flag to options array
	if ($options['db_version'] < '1.53') 
		$options['fix_ie'] = true;
	
	//add multi-user support
	if ($options['db_version'] < '1.6') {
		$current_user = wp_get_current_user();
		
		//abstract $options[field] to $options[user_login][field] and kill original
		$fields = array('name', 'summary', 'contact_info', 'order');
		foreach ($fields as $field) {
			if ( isset( $options[$field] ) ) {
				$options[$current_user->user_login][$field] = $options[$field];
				unset($options[$field]);
			}
		}
	

	}
	
		
	//DB Versioning
	$options['db_version'] = '1.53';
	
	//store updated options
	update_option( 'wp_resume_options', $options );
  	
  	//pass the upgraded options back
  	return $options;

}


/**
 * Modifies the add organization page to provide help text and better descriptions
 * @since 1.2
 * @disclaimer it's not pretty, but it get's the job done.
 */
function wp_resume_org_helptext() { ?>
	<script>
		jQuery(document).ready(function($){
			$('#parent, #tag-slug').parent().hide();
			$('#tag-name').siblings('p').text('The name of the organization as you want it to appear');
			$('#tag-description').attr('rows','1').siblings('label').text('Location').siblings('p').text('Traditionally the location of the organization (optional)');
		});
	</script>
	<noscript>
		<h4>Help</h4>
		<p><strong>Name</strong>: The name of the organization</p>
		<p><strong>Parent</strong>: Do not add a parent</p>
		<p><strong>Description</strong>: You can put the location of the organization here (optional)</p>
	</noscript>
<?php }

add_action('wp_resume_organization_add_form','wp_resume_org_helptext');

/**
 * Removes extra fields from add section form
 * @since 1.2
 */
function wp_resume_section_helptext() { ?>
	<script>
		jQuery(document).ready(function($){
			$('#parent').parent().hide();
			$('#tag-description, #tag-slug').parent().hide();
		});
	</script>
<?php }

add_action('wp_resume_section_add_form','wp_resume_section_helptext');

/**
 * Includes resume template on shortcode use 
 * @since 1.3
 */
function wp_resume_shortcode( $atts ) {
	ob_start();
	wp_resume_include_template('resume.php');
	$resume = ob_get_contents();
	ob_end_clean();
	
	return $resume;
}

add_shortcode('wp_resume','wp_resume_shortcode');


/**
 * Adds feed support to the resume 
 * @since 1.5
 */
function wp_resume_add_feeds() {
	global $post;
	if ( preg_match( '/\[wp_resume([^\]]*)]/i', $post->post_content ) === FALSE) 
		return;
	add_feed('text', 'wp_resume_plain_text');
	add_feed('json', 'wp_resume_json');
	add_action('wp_head', 'wp_resume_header');
}

add_action('template_redirect','wp_resume_add_feeds');

function wp_resume_header() { 
	$options = wp_resume_get_options(); ?>
		<link rel="profile" href="http://microformats.org/profile/hcard" />
		<?php if ($options['fix_ie']) { ?>
		<!--[if lt IE 9]>
			<script type="text/javascript" src="<?php echo plugins_url(  'html5.js', __FILE__ ); ?>"></script>
		<![endif]-->
		<?php } ?>
<?php }

/**
 * Includes the plain text template
 * @since 1.5
 */
function wp_resume_plain_text() {
	header('Content-Type: text/html; charset='. get_bloginfo('charset') );
	wp_resume_include_template('resume-text.php');
}

/**
 * Includes the json template
 * @since 1.5
 */
function wp_resume_json() {
	header('Content-type: application/json; charset='. get_bloginfo('charset') );
	wp_resume_include_template('resume-json.php');
}

/**
 * Includes a wp_resume template file
 * First looks in current theme directory for file, otherwise includes defaults
 * @since 1.5
 */
function wp_resume_include_template( $template ) {

	if ( file_exists( get_theme_root() . '/' . get_template() . '/' . $template ) )
		include ( get_theme_root() . '/' . get_template() . '/' . $template ) ;
	else 
		include ( $template );
						
}

/**
 * Fuzzy gets author for current resume page
 * Looks at:
 * 1) Attributes of shorcode (user="[username]")
 * 2) Author of page that calls the shortcode
 * 
 * @param array $atts attributes passed from shortcode callback
 * @since 1.6
 */
function wp_resume_get_author( $atts = array() ) {
	
	//if user is passed as an attribute, that's our author
	if ( isset( $atts['user'] ) ) 
		return $atts['user'];
	
	//otherwise grab the author from the post
	global $post;
	$user = get_userdata($post->post_author);
	return $user->user_login;
}
?>