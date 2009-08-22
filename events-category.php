<?php
/*
Plugin Name: Events Category
Plugin URI: http://wordpress.org/extend/plugins/events-category/
Description: Seamless event calendar solution which extends the basic WordPress functionality to enable future-dated posts to be listed within the blog chronology when they are assigned to a particular post category. The a future-dated post's timestamp is used as the event date. <em>This plugin is developed at <a href="http://www.shepherd-interactive.com/" title="Shepherd Interactive specializes in web design and development in Portland, Oregon">Shepherd Interactive</a> for the benefit of the community.</em>
Version: 0.5
Author: Weston Ruter
Author URI: http://weston.ruter.net/
Copyright: 2008, Weston Ruter, Shepherd Interactive

GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * @todo We can use the originalEvent to provide a linkage between events (this event is part of a reoccuring )
 * @todo Prevent events posts from getting orphaned?
 */

###### Initialization ########################################################################

# Load up the localization file if we're using WordPress in a different language
# Place it in the "localization" folder and name it "events-category-[value in wp-config].mo"
load_plugin_textdomain('events-category', PLUGINDIR . '/events-category/i18n');

add_option('eventscategory_default_name', __('Events', 'events-category'));
add_option('eventscategory_default_slug', __('events', 'events-category'));
add_option('eventscategory_cat_id', 0);

$eventscategory_default_main_date_format = __('F jS, [Y @] g[:i][a]{[ - ][F ][j][S, ][Y,] g[:i]a} T', 'events-category');
add_option('eventscategory_date_format', $eventscategory_default_main_date_format);
#Including the year: M. j[, Y][, g][:i][a]{[ – ][M. ][j, ][Y, ]g[:i]a} T



/**
 * Activate Events Category plugin
 */
function eventscategory_activate(){
	print "eventscategory_activate\n";
	// Make sure that the system supports this plugin
	if(!class_exists('DOMDocument'))
		die(__("It appears that you are using PHP4 and thus do not have the DOMDocument class available; please upgrade to PHP5."));

	// Get the existing event category or create it
	$eventsCat = null;
	$eventsCatID = get_option('eventscategory_cat_id');
	if(!($eventsCatID && ($eventsCat = get_category($eventsCatID)))){
		// Get the events category by slug
		if($eventsCat = get_category_by_slug(get_option('eventscategory_default_slug'))){
			$eventsCatID = $eventsCat->term_id;
		}
		// Get the events category if it has the proper name
		else if($eventsCatID = get_cat_ID(get_option('eventscategory_default_name'))){
			$eventsCat = get_category($eventsCatID);
		}
		// Create the events category if it does not exist
		else {
			$eventsCatID = wp_insert_category(array(
				'cat_name' => get_option('eventscategory_default_name'),
				'category_nicename' => get_option('eventscategory_default_slug')
			));
			$eventsCat = get_category($eventsCatID);
		}
		update_option('eventscategory_cat_id', $eventsCatID);
	}
	
	// Now find all posts that are assigned to the events category or one of its
	// subcategories and change post_status from 'future' to 'publish'
	foreach(get_posts(array('category' => $eventsCatID)) as $eventPost){
		if($eventPost->post_status == 'future'){
			wp_publish_post($eventPost->ID);
		}
	}
	
	// Schedule an event to update the Google Calendar feeds
	wp_schedule_event(time()+3600, 'hourly', 'eventscategory_update_gcal');
	do_action('eventscategory_update_gcal');
}
register_activation_hook(__FILE__, 'eventscategory_activate');


/**
 * Cleanup when plugin deactivated
 */
function eventscategory_deactivate(){
	wp_clear_scheduled_hook('eventscategory_update_gcal');
}
register_deactivation_hook(__FILE__, 'eventscategory_deactivate');





###### UPDATING EVENT POSTS ##################################################################

/**
 * Whenever an event post is manually saved, delete the _gcal_updated postmeta
 * so that the next thme the gcal feed is fetched, the post won't be skipped
 */
function eventscategory_action_save_post($post_id, $post){
	// Whenever an event post is manually saved, delete the _gcal_updated postmeta
	// so that the next thme the gcal feed is fetched, the post won't be skipped
	delete_post_meta($post_id, '_gcal_updated');
	
	// Ensure that if the post has a future status, that it gets changed to publish
	// so that it will appear in the WP Queries
	$eventsCatID = get_option('eventscategory_cat_id'); #TODO: There could potentially be multiple event categories each associated with a different GCal
	if($post->post_status == 'future'){
		foreach(wp_get_post_categories($post_id) as $catID){
			if($catID == $eventsCatID || cat_is_ancestor_of($eventsCatID, (int)$catID)){
				wp_publish_post($post_id);
				return;
			}
		}
	}
}
add_action('save_post', 'eventscategory_action_save_post', 10, 2);


/**
 * Scheduled function which gets the Google Calendar events
 */
function eventscategory_update_gcal_action(){
	global $wpdb;
	file_put_contents(TEMPLATEPATH . '/temp.txt', 'eventscategory_update_gcal_action : '.  date('r') . "\n", FILE_APPEND);
	if(!class_exists('DOMDocument'))
		die(__("DOMDocument not available. Please ensure using PHP5.", 'events-category'));
	
	// Remove filter that deletes _gcal_updated post meta (since we're not manually saving posts)
	remove_action('save_post', 'eventscategory_action_save_post_delete_gcal_updated');
	
	$eventsCatID = get_option('eventscategory_cat_id');
	$eventsCat = get_category($eventsCatID);
	if(!$eventsCat)
		die(__("Events category not supplied (option eventscategory_cat_id).", 'events-category'));
	
	$feedQueryArgs = array(
		'singleevents' => 'true',
		'start-max' => date('Y-m-d', time()+3600*24*365),
		'max-results' => 1000000
	);
	if($ctz = get_option('timezone_string'))
		$feedQueryArgs['ctz'] = str_replace(' ', '_', $ctz);
	$feedURL = add_query_arg($feedQueryArgs, preg_replace('/\?.*/', '', get_option('eventscategory_gcal_feed_url')));
	//$feedURL = 'http://www.google.com/calendar/feeds/.../public/full-noattendees';
	if(!$feedURL)
		die(__("No Google Calendar feed URL provided.", 'events-category'));
	
	$doc = new DOMDocument();
	$xml = file_get_contents($feedURL);
	#$xml = file_get_contents(ABSPATH . '/wp-content/gcal.xml');
	if(!$doc->loadXML($xml))
		die(sprintf(__("Unable to parse Google Calendar XML feed: %s", 'events-category'), $feedURL));
	$xpath = new DOMXPath($doc);
	$xpath->registerNamespace('atom', 'http://www.w3.org/2005/Atom');
	$xpath->registerNamespace('gCal', 'http://schemas.google.com/gCal/2005');
	
	$gcal_feed_id = trim($xpath->query('./atom:id', $doc->documentElement)->item(0)->textContent);	
	
	foreach($xpath->query('//atom:entry') as $entry){
		//GCal ID becomes WordPress post guid
		$gcal_id = trim($xpath->query('.//atom:id', $entry)->item(0)->textContent);
		$post = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts p WHERE p.guid = %s", $gcal_id), ARRAY_A);
		if(!$post)
			$post = array();
		$postmeta = array(
			'_gcal_updated' => trim($xpath->query('.//atom:updated', $entry)->item(0)->textContent),
			'_gcal_feed_id' => $gcal_feed_id
		);
		
		// If WP GCal updated time is exactly the same as this updated time, then skip;
		// note that whenever someone saves a post manually, this postmeta is deleted so
		// that this will not get skipped
		if(!empty($post['ID']) && get_post_meta($post['ID'], '_gcal_updated', true) == $postmeta['_gcal_updated']){
			#print "SKIPPING: " . trim($xpath->query('.//atom:title', $entry)->item(0)->textContent) . "<br>";
			continue;
		}
		
		//Google Calendar overrides the title and time, but not the description
		if(empty($post['post_title']))
			$post['post_title'] = trim($xpath->query('.//atom:title', $entry)->item(0)->textContent);
		#if(strpos($post['post_title'], 'GCal') === false)
		#	$post['post_title'] = '[GCal] ' . $post['post_title'];
		$post['post_type'] = 'post';
		$post['guid'] = $gcal_id;
		
		//Set the creator of the post
		if(empty($post['post_author'])){
			$admin = get_userdatabylogin('admin');
			if($admin)
				$post['post_author'] = $admin->ID;
		}
		
		//WordPress overrides post status
		if(empty($post['post_status'] ))
			$post['post_status'] = 'publish';
		
		//<gd:when startTime='2008-02-21T19:00:00.000-08:00' endTime='2008-02-21T22:00:00.000-08:00'/>
		$when = $xpath->query('.//gd:when', $entry)->item(0);
		if($when){
			$post['post_date'] = str_replace('T', ' ', $when->getAttribute('startTime')); #expects localtime from Google
			$post['post_date_gmt'] = get_gmt_from_date($post['post_date']);
			$postmeta['_gcal_starttime'] = $when->getAttribute('startTime'); //str_replace('T', ' ', $when->getAttribute('startTime'));
			$postmeta['_gcal_endtime'] = $when->getAttribute('endTime'); //str_replace('T', ' ', $when->getAttribute('endTime'));
			
			//Duration calculated and saved here so that events currently in progress can still be returned in upcoming events
			$postmeta['_gcal_duration'] = max(0, strtotime($postmeta['_gcal_endtime']) - strtotime($postmeta['_gcal_starttime']));
		}
		
		//<gd:where valueString='2214 NE Oregon St., Portland, OR (Urban Grind)'/>
		$where = $xpath->query('.//gd:where', $entry)->item(0);
		if($where)
			$postmeta['_gcal_where'] = $where->getAttribute('valueString');
		
		//<link rel='alternate' type='text/html' href='http://www.google.com/calendar/event?eid=NjNoZGZsYWoyMG5ocWYzanZvczFubWh2MWcgbmV3d2luZUBtdWx0bm9tYWguZWR1' title='alternate'/>
		$gcalLink = $xpath->query('.//atom:link[@rel="alternate" and @type="text/html"]', $entry)->item(0);
		if($gcalLink)
			$postmeta['_gcal_alternate_link'] = $gcalLink->getAttribute('href');
		
		//<content type='text'>: NOTE: Must add a the_content filter to supply from _gcal_content postmeta if post_content is blank
		$content = $xpath->query('.//atom:content', $entry)->item(0);
		if($content)
			$postmeta['_gcal_content'] = trim($content->textContent);
		
		//<gd:originalEvent id='ouaia8m2nved9t1d80vl88kopo' href='http://www.google.com/calendar/feeds/.../public/full/ouaia8m2nved9t1d80vl88kopo'>
		$originalEvent = $xpath->query('.//gd:originalEvent', $entry)->item(0);
		if($originalEvent)
			$postmeta['_gcal_originalevent_id'] = $originalEvent->getAttribute('href');
		
		//The post is always going to be in the Events category... must not clobber any existing categories though
		$post['post_category'] = array();
		if(!empty($post['ID']))
			$post['post_category'] = wp_get_post_categories($post['ID']);
		if(!in_array($eventsCatID, $post['post_category']))
			$post['post_category'][] = $eventsCatID;
		
		//Update/Insert post
		#print '<pre>';
		#print $post['post_title'] . "\n";
		#print $post['post_date'] . "\n";
		#print $gcal_id . "\n\n";
		#print '</pre>';
		#print '<hr>';
		//continue;
		
		//continue;
		//print '<pre>';
		//print_r($post);
		//print_r($postmeta);
		//print '</pre>';
		//print '<hr>';
		
		$post['ID'] = wp_update_post($post);
		if($post['ID']){
			foreach($postmeta as $meta_key => $meta_value ){
				update_post_meta($post['ID'], $meta_key, $meta_value);
			}
		}
	}
}
add_action('eventscategory_update_gcal', 'eventscategory_update_gcal_action');


function temppppp(){
	//if(get_option('newwine_migrated')){
		do_action('eventscategory_update_gcal');
		exit;
	//}
}
#add_action('init', 'temppppp');


////// HELPER FUNCTIONS //////////////////////////////////////////////////////////////////////////////

/**
 * Determine if this category is the events category or if the category is a subcategory
 */
function is_events_category($catID = ''){
	global $wp_query;
	//return (is_category($eventsCatID) || in_category($eventsCatID) || (is_category() && cat_is_ancestor_of($eventsCatID, $wp_query->get_queried_object_id())))
	
	$catIDs = array();
	if(is_numeric($catID))
		$catIDs[] = (int)$catID;
	
	if(is_array($catID) && !empty($catID)){
		$cats = array_values($catID);
		if(is_object($cats[0]) && $cats[0]->cat_ID){
			foreach($cats as $cat){
				$catIDs[] = (int)$cat->cat_ID;
			}
		}
		else if(is_numeric($cat[0])){
			$catIDs = array_map('intval', $catID);
		}
	}
	
	if(empty($catIDs)) {
		// This conditional is needed because if going to non-existent post
		// under events category, it will think that it is not single
		if($wp_query->get('name'))
			return false;
		
		$category__in = $wp_query->get('category__in');
		if(!empty($category__in))
			$catIDs = $category__in;
		if($wp_query->get('cat'))
			$catIDs[] = (int)$wp_query->get('cat');
	}
	if(empty($catIDs))
		return false;
	$eventsCategoryID = (int)get_option('eventscategory_cat_id');
	
	foreach($catIDs as $catID){
		#This category is exactly the events category
		if ( $eventsCategoryID == $catID)
			return true;
		
		#This category is a descendent of the events category
		if(cat_is_ancestor_of($eventsCategoryID, $catID))
			return true;
	}
	return false;
}


/**
 * Determine if we are in an Events Category
 */
function in_events_category($post_id = 0){
	global $post;
	if(!$post_id)
		$post_id = $post->ID;
	if(!$post_id)
		return;
	
	foreach(wp_get_post_categories($post_id) as $catID){
		if(is_events_category($catID))
			return true;
	}
	return false;
}


////// FILTERS FOR MODIFYING THE QUERY ////////////////////////////////////////////////


/**
 * Add the _gcal_duration to the query
 * @see eventscategory_filter_posts_where
 */
function eventscategory_filter_posts_join($join){
	global $wpdb;
	if(!is_admin() && is_events_category()){
		$join .= " LEFT JOIN $wpdb->postmeta eventscategory_duration ON eventscategory_duration.post_id = $wpdb->posts.ID AND eventscategory_duration.meta_key = '_gcal_duration' ";
	}
	return $join;
}
add_filter('posts_join', 'eventscategory_filter_posts_join');


/**
 * Make sure that when querying events category, that if not paged, only the future posts are returned
 * @see eventscategory_filter_posts_join
 */
function eventscategory_filter_posts_where($where){
	global $wpdb;
	if(!is_admin() && is_events_category()){
		#TODO: We need to factor in the duration as well!
		$now = "'2008-04-12 08:30:00'";
		$endtime = "IF(eventscategory_duration.meta_value IS NULL, $wpdb->posts.post_date, DATE_ADD($wpdb->posts.post_date, INTERVAL eventscategory_duration.meta_value SECOND))";
		
		if(!is_paged())
			$where .= " AND $endtime >= $now";
		else 
			$where .= " AND $endtime < $now";
	}
	return $where;
}
add_filter('posts_where', 'eventscategory_filter_posts_where');


#add_filter('posts_request', create_function('$a', 'print $a; return $a;'));

/**
 * When we are in future events, we should reverse the posts?
 * @todo Is this better than filtering the order by?
 */
//function eventscategory_the_posts($posts){
//	global $wp_query;
//	if(!is_admin() && !$wp_query->get('nopaging') && $wp_query->get('feed') != 'ical' && is_events_category() && $wp_query->get('eventscategory-position') >= 0){
//		return array_reverse($posts);
//	}
//	return $posts;
//}
//add_filter('the_posts', 'eventscategory_the_posts');


/**
 * When in an events category, change order to be ASC
 */
function eventscategory_filter_posts_orderby($orderby){
	global $wpdb;
	if(!is_admin() && !is_paged() && is_events_category())
		$orderby = "$wpdb->posts.post_date ASC";
	return $orderby;
}
add_filter('posts_orderby', 'eventscategory_filter_posts_orderby');


/**
 * Ensure that all future posts are returned, that none are paged; if paged,
 * then past events are being queried, and change the paged limit to start from
 * zero because when viewing past events, no future posts are included in the results
 * and thus the paged is offset by two.
 *
 * @todo Does this only work with $wp_query and not when making new WP_Query instances?
 * @see eventscategory_filter_posts_where()
 * @see eventscategory_recalculate_max_num_pages()
 */
function eventscategory_filter_post_limits_request($limits){
	//global $wp_query;
	if(!is_admin() && is_events_category()){
		if(is_paged()){
			$limits = 'LIMIT ' .
			           (get_query_var('paged') - 2) * get_query_var('posts_per_page') .
					   ', ' .
					   get_query_var('posts_per_page');
		}
		else {
			$limits = '';
		}
	}
	return $limits;
}
add_filter('post_limits_request', 'eventscategory_filter_post_limits_request');


/**
 * Here we ensure that past event pages get provided
 */
function eventscategory_recalculate_max_num_pages(&$query){
	if(!is_admin() && $query->is_category && is_events_category($query->get_queried_object_id())){
		$query->max_num_pages += !$query->is_paged ? 2 : 1; #if not is_paged, then the page is 0
	}
}
add_action('loop_start', 'eventscategory_recalculate_max_num_pages');






////// FILTERS AND FUNCTIONS FOR MODIFYING THE DISPLAY ////////////////////////////////////////////////

/**
 * If no content is provided and the current post does have _gcal_content
 */
function eventscategory_filter_the_content($content){
	global $post;
	if(!trim($content)){
		$content = get_post_meta($post->ID, '_gcal_content', true);
		$content = wpautop(wptexturize($content)); #apply_filters('the_content', $content)
		
		//Do we need to wpautop?
	}
	return $content;
}
add_filter('the_content', 'eventscategory_filter_the_content');


/**
 * Filter the_date to provide the event time?
 */




$eventscategory_default_main_date_format = 'F jS, [Y @] g[:i][a]{[ - ][F ][j][S, ][Y,] g[:i]a} T';
function eventscategory_the_time($dt_format = ''){
	echo eventscategory_get_the_time($dt_format);
}

function eventscategory_get_the_time($dt_format = ''){
	global $post, $eventscategory_default_main_date_format;
	if(!$dt_format)
		$dt_format = $eventscategory_default_main_date_format; #$dt_format = $eventscategory_default_main_date_format;
	
	$output = '';
	
	if(preg_match('/^(.+?){(?:\[(.+?)\])?(.+?)}(.+)?$/', $dt_format, $matches)){
		$dtstart = $matches[1];
		$dtseparator = $matches[2];
		$dtend = $matches[3];
		$dttz = $matches[4];
		
		$formatChars = 'dDjlNSwzWFmMntLoYyaABgGhHisueIOPTZcrU';
		
		$gcal_startTime = get_post_meta($post->ID, '_gcal_starttime', true);
		$gcal_endTime = get_post_meta($post->ID, '_gcal_endtime', true);
		$startTimestamp = $gcal_startTime ? strtotime($gcal_startTime) : (int)get_the_time('U');
		$endTimestamp = $gcal_endTime ? strtotime($gcal_endTime) : (int)get_the_time('U');

		#NOTE: Seconds should not be allowed
		
		$current = array();
		foreach(preg_split('//', $formatChars) as $c){
			$current[$c] = date($c);
		}
		
		$start = array(); #keep track of the values used in the dtstart
		foreach(preg_split('//', $formatChars) as $c){
			$start[$c] = date($c, $startTimestamp);
		}
		
		$end = array();
		if($startTimestamp != $endTimestamp){
			foreach(preg_split('//', $formatChars) as $c){
				$end[$c] = date($c, $endTimestamp);
			}
		}
		$is_same_day = ($start['z'] === $end['z'] && $start['Y'] === $end['Y']);
		
		#dtstart: Find all formatting characters which are optional
		if(preg_match_all("/\[[^\[\]]*?(?<!\\\\)([$formatChars])[^\[\]]*?\]/", $dtstart, $matches)){
			foreach($matches[1] as $c){
				if((preg_match("/[oYy]/", $c) && $current[$c] == $start[$c]) #remove year if same as current
					 ||
				   (preg_match("/[a]/", $c) && $start[$c] == $end[$c] && ($startTimestamp != $endTimestamp) && $is_same_day) #remove AM/PM specifier if same as end time
					 ||
				   (preg_match("/[i]/", $c) && preg_match('/^0*$/', $start[$c])) #remove minutes if they are zero
				   # ||
				   #(preg_match("/[BsU]/", $c) && !$duration) #Why did this have [i] and [Aa]??? hH gG; 
				){	
					#If the value is the same as the value current time, then remove the and the surrounding brackets
					$dtstart = preg_replace("/\[[^\[\]$formatChars]*${c}[^\[\]$formatChars]*\]/s", '', $dtstart);
				}
				else {
					#If field provided, remove optional-delimiters and inject microformats
					$dtstart = preg_replace("/\[([^\[\]$formatChars]*)$c([^\[\]$formatChars]*)\]/s", "$1$c$2", $dtstart);
				}
			}
		}
		$output .= '<time class="dtstart" datetime="' . gmdate('Ymd\THis', $startTimestamp) . 'Z">';
		#echo "<br><font color=green>" . get_post_time('Ymd\THis', true) . '</font>';
		$output .= date($dtstart, $startTimestamp);
		$output .= '</time>';
		#echo $duration;
		#dtend: Remove all formatting characters which are redundant
		if($startTimestamp != $endTimestamp){
			if($dtseparator)
				$output .= "<span class='separator'>$dtseparator</span>";
			
			if(preg_match_all("/\[[^\[\]]*?(?<!\\\\)([$formatChars])[^\[\]]*?\]/", $dtend, $matches)){
				foreach($matches[1] as $c){
					if(#Remove year if it is the same as the start, and same as current, or if the year is the same day of the same year
					   (preg_match("/[oYy]/", $c) && (($end[$c] === $start[$c] && $end[$c] == $current[$c]) || $is_same_day)) #when doing $dtend, will be FmMn; remember we need to keep track
						 ||
					   #Remove days of month and months if same day of same year
					   ($is_same_day && preg_match('/[FmMndDjlNSw]/', $c))
						 ||
					   #Remove minutes if they are zero
					   (preg_match("/[i]/", $c) && preg_match('/^0*$/', $end[$c])))
					{
						#If the value is the same as the start time, then remove the and the surrounding brackets
						$dtend = preg_replace("/\[[^\[\]$formatChars]*${c}[^\[\]$formatChars]*\]/s", '', $dtend);
					}
					else {
						#If field provided, remove optional-delimiters and inject microformats
						$dtend = preg_replace("/\[([^\[\]$formatChars]*)$c([^\[\]$formatChars]*)\]/s", "$1$c$2", $dtend);
					}
				}
			}
			$dtend = trim($dtend);
			
			#echo "<br><font color=blue>" . date('Ymd\THis', (int)get_post_time('U', true) + intval($duration)) . '</font><br>';
			
			$output .= '<time class="dtend" datetime="' . date('Ymd\THis', $endTimestamp) . 'Z">';
			$output .= date($dtend, $endTimestamp);
			$output .= '</time>';
		}
		
		$gmt_offset = get_option('gmt_offset');
		$timezone = get_option('eventscategory_timezone');
		$timezone_dst = get_option('eventscategory_timezone_dst');
		
		//Big issue: We need to be able to determine if an arbitrary date is in daylight savings
		//We need to automatically update gmt_offset when DST starts and ends
		//We need to automatically set daylight savings time!!! This is a core feature.
		$is_dst = date('I', $endTimestamp);
		
		#T or e: Timezone identifiers
		$dttz = preg_replace('{(?<!\\\\)[Te]}', '\\' . join('\\', preg_split('//', $is_dst ? $timezone_dst : $timezone)), $dttz);
		#Z: Timezone offset in seconds. The offset for timezones west of UTC is always negative, and for those east of UTC is always positive.
		$dttz = preg_replace('{(?<!\\\\)Z}', $gmt_offset*3600, $dttz);
		#O: Difference to Greenwich time (GMT) in hours
		$offsetStr = ($gmt_offset < 0 ? '-' : '+') . sprintf("%04d", abs($gmt_offset) * 100);
		$dttz = preg_replace('{(?<!\\\\)O}', $offsetStr, $dttz);
		#P: Difference to Greenwich time (GMT) with colon between hours and minutes
		$offsetStr = substr($offsetStr, 0, strlen($offsetStr)-2) . ':' . substr($offsetStr, strlen($offsetStr)-2);
		$dttz = preg_replace('{(?<!\\\\)P}', $offsetStr, $dttz);
		
		if($dttz){
			$output .= '<span class="timezone">';
			#if($duration)
			#	$output .= date($dttz, $endTimestamp);
			#else
				$output .= date($dttz, $endTimestamp);
			$output .= '</span>';
		}
		#echo "<font color=red>$duration</font>";
		return $output;
	}
	else {
		trigger_error('<em style="color:red">' . sprintf(__('Invalid date format: %s', 'events-category'), $options[$number]['date_format']) . '</span>');
		return false;
	}
}





#require(dirname(__FILE__) . '/widgets.php'); #TODO
#require(dirname(__FILE__) . '/feeds.php'); #TODO
#require(dirname(__FILE__) . '/admin.php'); #TODO
#require(dirname(__FILE__) . '/template-tags.php');

