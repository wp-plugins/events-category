<?php


function eventscategory_admin_init(){
	$filename = basename(preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']));
	if(is_admin() && ($_REQUEST['page'] == 'events-category-options' || $filename == 'post-new.php' || $filename == 'post.php')){
		#add_action('wp_head', 'eventscategory_add_admin_style');
		wp_enqueue_script('events-category', get_option('siteurl') . '/' . PLUGINDIR . '/events-category/admin.js', array('jquery'));
		wp_enqueue_style('events-category', get_option('siteurl') . '/' . PLUGINDIR . '/events-category/admin.css');
	}
}
add_action('init', 'eventscategory_admin_init');

//function eventscategory_add_admin_style(){
//	#print '<!--';
//	#print_r($GLOBALS);
//	#print '-->';
//	#echo '<link rel="stylesheet" type="text/css" href="/' . PLUGINDIR . '/events-category/admin.css" />';
//}
//add_action('admin_head', 'eventscategory_add_admin_style');



function eventscategory_modify_menu(){
	add_options_page(
		__('Events Category', 'events-category'),
		__('Events Category', 'events-category'),
		8,
		'events-category-options',
		'eventscategory_options_page'
	);
	add_meta_box('eventscategorydiv', __('Event Details', 'events-category'), 'eventscategory_add_meta_box', 'post', 'normal', 'high');
}
add_action('admin_menu', 'eventscategory_modify_menu');


function eventscategory_add_meta_box($post){
	
	#only if $object->ID is in eventscategory?
	
	#echo "Events category!";
	include(dirname(__FILE__) . '/admin_meta_box.php');
}


//function eventscategory_insert_data_form(){
//	global $eventscategory_all_fieldnames;
//	if((basename($_SERVER['PHP_SELF']) == 'post.php' || basename($_SERVER['PHP_SELF']) == 'post-new.php') && @include('edit_data_form.php')){
//		echo '<script type="text/javascript">';
//		
//		echo 'var eventscategory_ID = ' . get_option('eventscategory_ID') . ';';
//		echo 'var eventscategory_IDs = [eventscategory_ID];';
//		$cats = get_categories('child_of=' . get_option('eventscategory_ID') . '&hide_empty=0');
//		foreach($cats as $cat){
//			echo 'eventscategory_IDs.push(' . $cat->cat_ID . ');';
//		}
//		echo 'var eventscategory_posttimestamp_note = "' . addslashes(__('Modify the post time (the event time) above.', 'events-category')) . '";';
//		echo 'var eventscategory_all_fieldnames = {"' . join('":1,"', $eventscategory_all_fieldnames) . '":1};';
//		echo 'eventscategory_all_fieldnames["duration"] = 1;';
//		echo '</script>';
//		echo '<script type="text/javascript" src="../' . PLUGINDIR . '/events-category/admin.js"></script>';
//	}
//}
//add_action('admin_footer', 'eventscategory_insert_data_form');
#add_action('admin_footer', 'eventscategory_insert_data_form');


//function eventscategory_edit_category_form(){ //TODO
//	echo "<p>This category is an 'Events' category. If you would like to customize its configuration, visit the <a href=''>Options page</a>.</p>";
//}
//add_action('edit_category_form', 'eventscategory_edit_category_form');



function eventscategory_options_page(){
	$timezone = get_option( 'eventscategory_timezone' );
	$timezone_dst = get_option( 'eventscategory_timezone_dst' );
	
	if(@$_POST['action'] == 'update'){
		check_admin_referer('update-options-eventscategory');
		
		$timezone = $_POST['eventscategory_timezone'];
		update_option('eventscategory_timezone', $timezone);
		
		$timezone_dst = $_POST['eventscategory_timezone_dst'];
		update_option('eventscategory_timezone_dst', $timezone_dst);
		
		
		//* Timezone displayed now reflects what is set in gmt_offset
		//
		//* Add configuration page to modify the options
		//	* Option to automatically prepend the location to the_content, in both is_feed() and !is_feed()
		//	* Option to prepend the time to the_content in both is_feed() and !is_feed()
		//	* Prepended data in the_content includes hCalendar microformats
		//	* Option to prepend a date format to the titles in non-iCal feeds
		//	* Change the default format to be used by the datetime and location template tag functions
		
		
		?><div id='message' class="updated fade"><p><strong><?php _e('Options saved.', 'events-category' ); ?></strong></p></div><?php
	}
	
	?>
	<div class='wrap' id='events-category-options-form'>
		<h2><?php _e('Events Category Options', 'events-category') ?></h2>
		<form action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="post">
			<?php
			if(function_exists('wp_nonce_field'))
				wp_nonce_field('update-options-eventscategory');
			?>
			<input type="hidden" name="action" value="update" />

			<h3>Timezone settings</h3>
			<p>The following two options are only applicable if the format characters 'T' or 'e' (treated identically) appear in your date-time format string:</p>
			<table class='form-table'>
				<tr>
					<th>
						<label for='eventscategory_timezone'><?php _e("Events timezone: ", 'events-category') ?></label>
					</th>
					<td>
						<input type="text" size="10" name="eventscategory_timezone" id='eventscategory_timezone' value="<?php echo htmlspecialchars($timezone) ?>" />
						<span class='tip'><?php printf(__("(For example, PST or GMT; this should correspond to your GMT offset: %d)", 'events-category'), get_option('gmt_offset')) ?></span>
					</td>
				</tr>
			
				<tr>
					<th>
						<label for='eventscategory_timezone_dst'><?php _e("Events daylight-savings timezone: ", 'events-category') ?></label>
					</th>
					<td>
					<input type="text" size="10" name="eventscategory_timezone_dst" id="eventscategory_timezone_dst" value="<?php echo htmlspecialchars($timezone_dst) ?>" />
					<span class='tip'><?php printf(__("(For example, PDT; same as above, but displayed when it is daylight savings time)", 'events-category'), get_option('gmt_offset')) ?></span>
					</td>
				</tr>
			
			</table>

			<p class="submit">
				<input type="submit" name="Submit" value="<?php _e('Update Options', 'events-category' ) ?>" />
			</p>
		</form>
	</div>
	<?php
}


?>