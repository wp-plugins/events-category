<?php
/*
Plugin Name: Events Category
Plugin URI: http://wordpress.org/extend/plugins/events-category/
Description: Seamless event calendar solution which extends the basic WordPress functionality to enable future-dated posts to be listed within the blog chronology when they are assigned to a particular post category. The a future-dated post's timestamp is used as the event date.
Version: 0.3
Author: Weston Ruter
Author URI: http://weston.ruter.net/
Copyright: 2008, Weston Ruter

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

# Load up the localization file if we're using WordPress in a different language
# Place it in the "localization" folder and name it "events-category-[value in wp-config].mo"
load_plugin_textdomain('events-category', PLUGINDIR . '/events-category/i18n');

add_option('eventscategory_default_name', __('Events', 'events-category'));
add_option('eventscategory_default_slug', __('events', 'events-category'));
add_option('eventscategory_future_slug', __('future', 'events-category'));
add_option('eventscategory_past_slug', __('past', 'events-category'));
add_option('eventscategory_ID', 0);
add_option('eventscategory_timezone', __('PST', 'events-category'));
add_option('eventscategory_timezone_dst', __('PDT', 'events-category'));

setlocale(LC_TIME, __('0', 'events-category'));

$eventscategory_default_main_date_format = __('F jS, [Y @] g[:i][a]{[ - ][F ][j][S, ][Y,] g[:i]a} T', 'events-category');
$eventscategory_default_main_address_format = __("[%street-address%]\n[%extended-address%]\n[%locality%][, %region%][ %postal-code%]\n[%country-name%]", 'events-category');

add_option('eventscategory_date_format', $eventscategory_default_main_date_format);
add_option('eventscategory_address_format', $eventscategory_default_main_address_format);

#Including the year: M. j[, Y][, g][:i][a]{[ – ][M. ][j, ][Y, ]g[:i]a} T


$eventscategory_all_fieldnames = array(
	'fn_org',
	'extended-address',
	'street-address',
	'locality',
	'region',
	'postal-code',
	'country-name',
	'latitude',
	'longitude',
	'url'
);

function eventscategory_activate(){
	global $wpdb, $wp_rewrite;

	#Get the existing event category or create it
	$eventsCat = null;
	$eventsCatID = get_option('eventscategory_ID');
	if(!($eventsCatID && ($eventsCat = get_category($eventsCatID)))){
		#Get the events category by slug
		if($eventsCat = get_category_by_slug(get_option('eventscategory_default_slug'))){
			$eventsCatID = $eventsCat->term_id;
		}
		#Get the events category if it has the proper name
		else if($eventsCatID = get_cat_ID(get_option('eventscategory_default_name'))){
			$eventsCat = get_category($eventsCatID);
		}
		#Create the events category if it does not exist
		else {
			$eventsCatID = wp_insert_category(array(
				cat_name => get_option('eventscategory_default_name'),
				category_nicename => get_option('eventscategory_default_slug')
			));
			$eventsCat = get_category($eventsCatID);
		}
		
		update_option('eventscategory_ID', $eventsCatID);
	}
	
	#Now find all posts that are assigned to the events category or one of its subcategories and change post_status from 'future' to 'publish'
	foreach($wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_status = 'future'") as $post_id){
		eventscategory_publish_future_post($post_id);
	}
	
    $wp_rewrite->flush_rules();
	$wp_rewrite->wp_rewrite_rules();
}
register_activation_hook(__FILE__, 'eventscategory_activate');

function eventscategory_init(){
	if(is_admin())
		wp_enqueue_script( 'jquery' );
}
add_action('init', 'eventscategory_init');

#Find future post in the Events category and publish them when saved
function eventscategory_publish_future_post($postID){
	$post = get_post($postID);
	$eventsCategoryID = (int)get_option('eventscategory_ID');
	
	//Determine if one of the post categories is the event category or a descendent of the event category
	if(!empty($eventsCategoryID)){
		$isRelatedToEventCategory = false;
		foreach(wp_get_post_categories($postID) as $catID){
			if($catID == $eventsCategoryID || cat_is_ancestor_of($eventsCategoryID, (int)$catID)){
				$isRelatedToEventCategory = true;
				break;
			}
		}
	}
	
	if($post->post_status == 'future' && (!$eventsCategoryID || $isRelatedToEventCategory))
		wp_publish_post($postID);
}

//WE NEED TO MAKE SURE THAT THIS WILL WORK WITH AJAX SAVE! //TODO
#



#add_meta_box('pagecustomdiv', __('Custom Fields'), 'page_custom_meta_box', 'page', 'advanced', 'core');
function eventscategory_save_post($postID, $post){
	#Since publishing a post from the future causes the 'save_post' action to be done
	#   check to see if it is future and stop if so, so that this functions logic
	#   is only run once.
	if($post->post_status == 'future'){
		eventscategory_publish_future_post($postID);
		return;
	}
	global $wpdb, $eventscategory_all_fieldnames;
	
	#Note that only the end date is sent using custom fields; the start date is sent using the
	#  regular post timestamp variables; BUT, we should be putting them all in post_custom
	if(preg_match('/^\d\d\d\d-\d\d-\d\d$/', $_POST['eventscategory_dend'])){ #PROBLEM
		$dtstart = $post->post_date;
		
		#Find the end date and calculate and save the duration
		$dtend = $_POST['eventscategory_dend'];
		if(empty($_POST['eventscategory_allday']) && preg_match('/^(\d?\d):\d\d$/', $_POST['eventscategory_tend']))
			$dtend .= ' ' . $_POST['eventscategory_tend'] . ':00';
		if(empty($_POST['eventscategory_allday']))
			$duration = strtotime($dtend) - strtotime($dtstart);
		else
			$duration = 0;
		if($duration < 0)
			$duration = 0;
			
		if(!update_post_meta($postID, '_event_duration', $duration))
			add_post_meta($postID, '_event_duration', $duration, true);
	}
	
	#Update location
	foreach($eventscategory_all_fieldnames as $fieldName){
		$value = stripslashes($_POST['eventscategory-' . $fieldName]);
		if(!empty($value)){
			if(!update_post_meta($postID, '_event_' . $fieldName, $value))
				add_post_meta($postID, '_event_' . $fieldName, $value, true);
		}
		else {
			delete_post_meta($postID, '_event_' . $fieldName);
		}
	}
}
add_action('save_post', 'eventscategory_save_post', 10, 2);


#Modify the rewrite rules so that they will know about the special URLs for event categories
function eventscategory_category_rewrite_rules($rules){
	global $wp_query, $wp_rewrite;
	$eventsCategoryID = (int)get_option('eventscategory_ID');
	$eventCategoryPaths = array();
	$eventCats = array_merge(
		array(get_category($eventsCategoryID)),
		get_categories('child_of=' . $eventsCategoryID . '&hide_empty=0')
	);
	
	#Get the paths to all of the event categories
	foreach($eventCats as $eventCat){
		$node = $eventCat;
		$path = $node->category_nicename;
		while($node->parent){
			$node = get_category($node->parent);
			$path = $node->category_nicename . '/' . $path;
		}
		$eventCategoryPaths[] = $path;
	}
	
	$past_slug = get_option('eventscategory_past_slug');
	$future_slug = get_option('eventscategory_future_slug');
	
	$category_permastruct = trim($wp_rewrite->get_category_permastruct(), '/');
	$feed_permastruct = trim($wp_rewrite->get_feed_permastruct(), '/');
	
	#Get all feed types
	$feeds = (array)$wp_rewrite->feeds;
	array_push($feeds, 'ical');
	array_unique($feeds);
	
	$newRules = array();
	foreach($eventCategoryPaths as $catPath){
		### Upcoming events feeds (remember that iCal feeds return all) #####
		#With /feed/ base; rule was: "$cat_base/($catPath)/(?:$future_slug/)?$wp_rewrite->feed_base/(feed|rdf|rss|rss2|atom|ical)/?\$"
		$newRules[str_replace('%category%', "($catPath)/(?:(?:$future_slug|$past_slug)/)?", $category_permastruct)
		          . str_replace('%feed%', '(' . join('|', $feeds) . ')', $feed_permastruct)
		          . '/?$'] = 'index.php?category_name=$matches[1]&feed=$matches[2]&eventscategory-position=0';
		
		#Without feed base
		$newRules[str_replace('%category%', "($catPath)/(?:(?:$future_slug|$past_slug)/)?", $category_permastruct)
		          . str_replace('%feed%', '', $feed_permastruct)
		          . '/?$'] = 'index.php?category_name=$matches[1]&feed=feed&eventscategory-position=0';
		
		### WordPress category event pages ####
		#Upcoming events; rule was "$cat_base/($catPath)/$future_slug(/|[01]/?)?\$"
		$newRules[str_replace('%category%', "($catPath)/$future_slug(/|[01]/?)?\$", $category_permastruct)] = 'index.php?category_name=$matches[1]&eventscategory-position=0';
		
		#Future events; rule was "$cat_base/($catPath)/$future_slug/(\d+)/?\$"
		$newRules[str_replace('%category%', "($catPath)/$future_slug/(\d+)/?\$", $category_permastruct)] = 'index.php?category_name=$matches[1]&eventscategory-position=" . ((int)$matches[2]-1) . "';
		
		#Future events; rule was "$cat_base/($catPath)/$past_slug(/|[01]/?)?\$"
		$newRules[str_replace('%category%', "($catPath)/$past_slug(/|[01]/?)?\$", $category_permastruct)] = 'index.php?category_name=$matches[1]&eventscategory-position=-1';
		
		#Future events; rule was "$cat_base/($catPath)/$past_slug/(\d+)/?\$"
		$newRules[str_replace('%category%', "($catPath)/$past_slug/(\d+)/?\$", $category_permastruct)] = 'index.php?category_name=$matches[1]&eventscategory-position=" . (-(int)$matches[2]) . "';
	}
	return array_merge($newRules, $rules);
}
add_filter('category_rewrite_rules', 'eventscategory_category_rewrite_rules');


#Determine if this category is the events category or if the category is a subcategory
function is_events_category($catID = ''){
	global $wp_query;
	
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
		#This conditional is needed because if going to non-existent post under events category, it will think
		#   that it is not single
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
	$eventsCategoryID = (int)get_option('eventscategory_ID');
	
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


#Get the current position in the timeline
function eventscategory_request($request){
	global $wp;
	if(is_numeric($_GET['eventscategory-position'])){
		$request['eventscategory-position'] = (int)$_GET['eventscategory-position'];
	}
	else {
		parse_str($wp->matched_query, $ruleQuery);
		if(is_numeric($ruleQuery['eventscategory-position']))
			$request['eventscategory-position'] = (int)$ruleQuery['eventscategory-position'];
	}
	return $request;
}
add_filter('request', 'eventscategory_request');


#Tag the SQL query so that modifications can be made by the 'posts_request' filter
function eventscategory_posts_fields($sql){
	return is_events_category() ? "/*EVENTS-FIELDS*/ $sql" : $sql;
}
add_filter('posts_fields', 'eventscategory_posts_fields');

#Tag the SQL query so that modifications can be made by the 'posts_request' filter
function eventscategory_posts_where($sql){
	global $wp_query, $wpdb;
	#From wp-includes/query.php line #658
	if ( !( $wp_query->is_singular || $wp_query->is_archive || $wp_query->is_search || $wp_query->is_trackback || $wp_query->is_404 || $wp_query->is_admin || $wp_query->is_comments_popup ) ){
		#Modify the WHERE clause if is_home so that the future posts aren't displayed
		$sql .= " AND $wpdb->posts.post_date < '" . current_time('mysql') . "'";
	}
	return "/*EVENTS-WHERE*/$sql";
}
add_filter('posts_where', 'eventscategory_posts_where');

#Tag the SQL query so that modifications can be made by the 'posts_request' filter
function eventscategory_filter_posts_groupby($sql){
	return '/*EVENTS-GROUP-BY*/' . $sql;
}
add_filter('posts_groupby', 'eventscategory_filter_posts_groupby');

#Tag the SQL query so that modifications can be made by the 'posts_request' filter
function eventscategory_filter_post_limits($sql){
	return "/*EVENTS-LIMIT*/ $sql";
}
add_filter('post_limits', 'eventscategory_filter_post_limits');


#Modify the posts query to get the events properly
function eventscategory_filter_posts_request($sql){
	global $wpdb, $paged, $wp_query;
	
	#PROBLEM: We must revisit is_events_category() to go up call stack or to be a new method of WP_Query()
	#     Can we create a new Global $eventscategory_wp_query??? Which is set if the query is events category. Then the function is_events_category can just check to see if it exists
	if(!is_admin() && !($wp_query->is_year || $wp_query->is_month || $wp_query->is_day) && is_events_category()){ #PROBLEM: this is not getting the value in the 
		#This SQL query should ideally be resdeigned to use COUNT(*)... but that would be very complicated
		$countFutureSQL = #preg_replace('{(?<=SELECT)\s+SQL_CALC_FOUND_ROWS}',
		                  #' ',
						  preg_replace('{/\*EVENTS-FIELDS\*/.+?(?=FROM)}',
		                               ' ID ',
						  preg_replace('{/\*EVENTS-WHERE\*/}',
		                               ' AND post_date >= NOW() ',
						  preg_replace('{/\*EVENTS-LIMIT\*/\s*LIMIT\s*\d+(\s*,\s*\d*+)?}',
									   '',
						  #preg_replace('{GROUP BY\s*/\*EVENTS-GROUP-BY\*/.+?(?=ORDER BY|LIMIT)}',
		                  #'',
						  $sql
						  )
						  #)
						  )
						  #)
						  );
		$wpdb->query($countFutureSQL);
		$wp_query->eventscategory_future_found_posts = $wpdb->get_var('SELECT FOUND_ROWS()');
		
		$posts_per = (int)$wp_query->get('posts_per_page');
		$futurePageCount = ceil($wp_query->eventscategory_future_found_posts/$posts_per);
		
		$position = (int)$wp_query->get('eventscategory-position');
		
		if(get_query_var('nopaging') || get_query_var('feed') == 'ical'){
			$sql = preg_replace('{/\*EVENTS-LIMIT\*/\s*LIMIT\s*\d+(\s*,\s*\d*+)?}', '', $sql);
		}
		else {
			$wp_query->eventscategory_future_remainder_count = ($wp_query->eventscategory_future_found_posts % $posts_per);
			$limit1 = $wp_query->eventscategory_future_found_posts-$posts_per*($position+1);
			
			#If we're too far in the future for results
			if($limit1+$posts_per <= 0){
				$limit1 = 0;
				$limit2 = 0;
			}
			#If we are on the last page of future results
			else if($limit1 < 0){
				$limit1 = 0;
				$limit2 = $wp_query->eventscategory_future_remainder_count;
			}
			#Normal case
			else {
				$limit2 = $posts_per;
			}
			
			$paged = $wp_query->query_vars['paged'] = ceil($limit1/$posts_per)+1;
			$sql = preg_replace('{/\*EVENTS-LIMIT\*/\s*LIMIT\s*\d+(\s*,\s*\d*+)?}', "LIMIT $limit1, $limit2", $sql);
		}
	}
	
	$sql = preg_replace('{GROUP BY\s*/\*EVENTS-GROUP-BY\*/\s*(?=ORDER BY|LIMIT)}', '', $sql);
	return $sql;
}
add_filter('posts_request', 'eventscategory_filter_posts_request');


#Rewrite the rules when a category is touched
function eventscategory_rewrite_rules(){
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
	$wp_rewrite->wp_rewrite_rules();
}
add_action('create_category', 'eventscategory_rewrite_rules');
add_action('delete_category', 'eventscategory_rewrite_rules');
add_action('edit_category', 'eventscategory_rewrite_rules');


#Here we need to fix max_num_pages: we recalculate max_num_pages because there may
#    be an extra page due to the present time splitting the result pages
#    We can't use the 'wp' action because it is not called by embedded queries
#    nor can we use the 'found_posts' filter because it occurs before max_num_pages is set
#    And of course we can't use pre_get_posts because this is also before either found_posts is
#    set and before everything else. So we use the_posts in order to fix max_num_pages
#    at the last second.
function eventscategory_recalculate_max_num_pages(&$args){
	global $wp_query;
	if(!is_admin() && is_events_category()){
		if(!$wp_query->get('nopaging')){
			$past_found_posts = $wp_query->found_posts - $wp_query->eventscategory_future_found_posts;
			$posts_per = (int)$wp_query->get('posts_per_page');
			$wp_query->max_num_pages =
						  ceil($past_found_posts/$posts_per)
						  +
						  ceil($wp_query->eventscategory_future_found_posts/$posts_per);
						  
		}
	}
	return $args; //pass through
}
add_filter('the_posts', 'eventscategory_recalculate_max_num_pages');


#Disable canonical redirection
function eventscategory_wp_action(&$query){
	if(!is_admin() && is_events_category()){
		//Issue: when loading initially, the paged variable is set to enable navigation; the canonical navigation
		//       redirects to the necessary canonical category page including /page/X/; this must be disabled.
		remove_filter('template_redirect', 'redirect_canonical');
	}
}
add_action('wp', 'eventscategory_wp_action');


//Note: when we are in future events, we should reverse the posts
function eventscategory_the_posts($posts){
	global $wp_query;
	if(!is_admin() && !$wp_query->get('nopaging') && $wp_query->get('feed') != 'ical' && is_events_category() && $wp_query->get('eventscategory-position') >= 0){
		return array_reverse($posts);
	}
	return $posts;
}
add_filter('the_posts', 'eventscategory_the_posts');


#Since by default, categoryevents-position == 0, when there are no longer any future posts, WordPress
#   will handle it as a 404 because no posts are returned. We need to prevent this behavior by forcing
#   the category template to show up.
function eventscategory_template_redirect(){
	global $wp_query;
	if(is_events_category() && is_404()){
		#Allow the next_posts_link to appear
		if($wp_query->found_posts > 0)
			$wp_query->max_num_pages++;
			
		$wp_query->is_category = true;
		$wp_query->is_archive = true;
		
		if($template = get_category_template()){
			include($template);
			exit;
		}
	}
}
add_action('template_redirect', 'eventscategory_template_redirect');



#Filter the URLs for the next and previous posts links; this is a bit janky because we have
#  to look at the function's call stack to know whether or not the URL being passed in
#  is for the next or previous posts page
function eventscategory_clean_url($url, $original_url, $context){
	global $wp_query;
	if(!is_admin() && is_events_category()){
		$is_future = $is_past = false;
		
		#Determine if we are trying to get the next link or previous link
		foreach(debug_backtrace() as $caller){
			if(strpos($caller['function'], 'previous_') === 0){ //Question: should we be more explicit?
				$is_future = true;
				break;
			}
			else if(strpos($caller['function'], 'next_') === 0){ //Question: should we be more explicit?
				$is_past = true;
				break;
			}
		}
		if($is_future || $is_past){
			$position = (int)get_query_var('eventscategory-position');
			$new_position = $is_past ? $position - 1 : $position + 1;
			
			#Determine if the current page's position is too far into the future
			#$posts_per = is_feed() ? (int)get_option('posts_per_rss') : (int)get_option('posts_per_page');
			$posts_per = (int)$wp_query->get('posts_per_page');
			if($new_position > 0){
				$newest_position = ceil($wp_query->eventscategory_future_found_posts/$posts_per)-1;
				if($new_position > $newest_position){
					$new_position = $newest_position;
				}
			}
			#Determine if the current page's position is too far in the past
			else if($new_position < 0){
				$oldest_position = -ceil(($wp_query->found_posts - $wp_query->eventscategory_future_found_posts)/$posts_per);
				if($new_position < $oldest_position){
					$new_position = $oldest_position;
				}
			}
			
			list($path, $search) = split('\?', $original_url);
			
			#Compose next and previous permalinks
			if(get_option('permalink_structure')){
				$search = preg_replace('{(\&)?eventscategory-position=-?\d+}', '$1', $search);
				$search = preg_replace('{^&}', '', $search);
				
				$past_slug = get_option('eventscategory_past_slug');
				$future_slug = get_option('eventscategory_future_slug');
				
				$path = preg_replace('{(?<=/)page/(\d+/)?$}', '', $path);
				$path = preg_replace("{(?<=/)($future_slug|$past_slug)/(\d+/)?$}", '', $path);
				
				#Next upcoming events page has no future or past slug
				if($new_position == 0)
					$original_url = "$path?$search";
				#Second page of future events has slug 'future' added; third page and above of future events has future/[paged]
				if($new_position > 0)
					$original_url = $path . $future_slug . '/' . ($new_position >= 1 ? $new_position+1 . '/' : '') . "?$search";
				#Second page of [ast events has slug 'past' added; third page and above of past events has past/[paged]
				else if($new_position < 0)
					$original_url = $path . $past_slug . '/' . ($new_position < -1 ? abs($new_position) . '/' : '') . "?$search";
				
				$original_url = preg_replace('{\?$}', '', $original_url);
			}
			#Compose links when permalinks aren't enabled
			else {
				$search = preg_replace('{&?(eventscategory-position|paged|cat|page_id)=[^&]*}', '', $search);
				
				$original_url = "$path?cat=" . get_query_var('cat');
				if($new_position)
					$original_url .= '&eventscategory-position=' . $new_position;
				if($search)
					$original_url .= "&$search";
			}
		}
	}
	return $original_url;
}
add_filter('clean_url', 'eventscategory_clean_url', 10, 3);


#require(dirname(__FILE__) . '/widgets.php'); #TODO
#require(dirname(__FILE__) . '/feeds.php'); #TODO
require(dirname(__FILE__) . '/admin.php'); #TODO
require(dirname(__FILE__) . '/template-tags.php');

?>