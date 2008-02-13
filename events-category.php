<?php
/*
Plugin Name: Events Category
Plugin URI: http://wordpress.org/extend/plugins/events-category/
Description: Seamless event calendar solution which extends the basic WordPress functionality to enable future-dated posts to be listed within the blog chronology when they are assigned to a particular post category. The a future-dated post's timestamp is used as the time stamp.
Version: 0.1 (alpha)
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
load_plugin_textdomain('events-category', PLUGINDIR . '/events-category/localization');

add_option('eventscategory_default_name', __('Events', 'events-category'));
add_option('eventscategory_default_slug', __('events', 'events-category'));
add_option('eventscategory_future_slug', __('future', 'events-category'));
add_option('eventscategory_past_slug', __('past', 'events-category'));
add_option('eventscategory_ID', 0);

$eventscategory_default_main_date_format = __('F jS, [Y @] g[:i][a]{[ - ][F ][j][S, ][Y,] g[:i]a} T', 'events-category');
$eventscategory_default_main_address_format = __("[%street-address%]\n[%extended-address%]\n[%locality%][, %region%][ %postal-code%]\n[%country-name%]", 'events-category');

add_option('eventscategory_date_format', $eventscategory_default_main_date_format);
add_option('eventscategory_address_format', $eventscategory_default_main_address_format);

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


#Functionality to enable Events Category to inform the action and filter hooks which query object to use
//$eventscategory_wp_query = null;
//function eventscategory_register_query(&$query){
//	global $wp_query, $eventscategory_wp_query;
//	if(!$query)
//		$query = &$wp_query;
//	#else
//	$eventscategory_wp_query = &$query;
//}
//function eventscategory_unregister_query(){
//	global $eventscategory_wp_query;
//	//OOOOPS! Is this going to cause a problem???
//	$eventscategory_wp_query = null;
//}
//function &eventscategory_get_query(){
//	global $wp_query, $eventscategory_wp_query;
//	#For some strange reason, returning a value from an if-statement works differently
//	#   from returning a value from a conditional operator. The custom members of the $query
//	#   were not persisting.
//	if(!empty($eventscategory_wp_query))
//		return $eventscategory_wp_query;
//	else
//		return $wp_query;
//	#return !empty($eventscategory_wp_query) ? $eventscategory_wp_query : $wp_query;
//}


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
#add_action('save_post', 'eventscategory_publish_future_post');
#add_action('publish_to_future');
#add_action('draft_to_future');
#add_action('new_to_future');
#add_action('pending_to_future');
#add_action('private_to_future');
#add_action('status_save_pre');


function eventscategory_category_rewrite_rules($rules){
	$eventsCategoryID = (int)get_option('eventscategory_ID');
	$eventCategoryPaths = array();
	$eventCats = array_merge(
					array(get_category($eventsCategoryID)),
					get_categories('child_of=' . $eventsCategoryID . '&hide_empty=0')
				); //array(get_category($eventsCategoryID));
	
	#while(count($eventCats)){
	#	$child = array_shift($eventCats);
	foreach($eventCats as $eventCat){
		#$descendents = get_categories('child_of=' . $eventCat->cat_ID . '&hide_empty=0');
		#$eventCats = array_merge($eventCats, $descendents);
		
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
	
	$newRules = array();
	foreach($eventCategoryPaths as $catPath){
		#Upcoming events feeds
		$newRules["category/($catPath)/(?:$future_slug/)?feed/(feed|rdf|rss|rss2|atom)/?\$"] = 'index.php?category_name=$matches[1]&feed=$matches[2]&eventscategory-position=0';
		$newRules["category/($catPath)/(?:$future_slug/)?(feed|rdf|rss|rss2|atom)/?\$"] = 'index.php?category_name=$matches[1]&feed=$matches[2]&eventscategory-position=0';
		#iCal feeds return all events
		$newRules["category/($catPath)/(?:(?:$past_slug|$future_slug)/)?feed/ical/?\$"] = 'index.php?category_name=$matches[1]&feed=ical';
		$newRules["category/($catPath)/(?:(?:$past_slug|$future_slug)/)?ical/?\$"] = 'index.php?category_name=$matches[1]&feed=ical';
		
		#WordPress category event pages
		$newRules["category/($catPath)/$future_slug(/|[01]/?)?\$"] = 'index.php?category_name=$matches[1]&eventscategory-position=0';
		$newRules["category/($catPath)/$future_slug/(\d+)/?\$"] = 'index.php?category_name=$matches[1]&eventscategory-position=" . ((int)$matches[2]-1) . "';
		#$newRules["category/($catPath)/$future_slug/(\d+)/?\$"] = 'index.php?category_name=$matches[1]&eventscategory-position={$matches[2]-1}';
		$newRules["category/($catPath)/$past_slug(/|[01]/?)?\$"] = 'index.php?category_name=$matches[1]&eventscategory-position=-1';
		$newRules["category/($catPath)/$past_slug/(\d+)/?\$"] = 'index.php?category_name=$matches[1]&eventscategory-position=" . (-(int)$matches[2]) . "';
		#$newRules["category/($catPath)/?\$"] = 'index.php?category_name=$matches[1]&eventscategory-position=0';
		#$newRules["category/($catPath)/$past_slug/(\d+)/?\$"] = 'index.php?category_name=$matches[1]&eventscategory-position={$matches[2]*-1}';
		
		#Question: would there be any advantage to doing &eventscategory-future=1 or &eventscategory-past=1 ???
	}
	return array_merge($newRules, $rules);
}
add_filter('category_rewrite_rules', 'eventscategory_category_rewrite_rules');


#Determine if this category is the events category or if the category is a subcategory
function is_events_category($catID = ''){
	global $wp_query;
	
	$catIDs = array();
	if(is_numeric($catID)){
		$catIDs[] = (int)$catID;
	}
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
		$query = &$wp_query;
		
		#This conditional is needed because if going to non-existent post under events category, it will think
		#   that it is not single
		if($query->get('name'))
			return false;
		
		$category__in = $query->get('category__in');
		if(!empty($category__in))
			$catIDs = $category__in;
		if($query->get('cat'))
			$catIDs[] = (int)$query->get('cat');
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


function eventscategory_request($request){
	global $wp;
	
	#Get the current position in the timeline
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


//function eventscategory_parse_query_action($query){
//		//PROBLEM: this is not called when there are no query vars (i.e. when is home)
//	print '<pre>';
//	print_r($query);
//	print '</pre>';
//	
//	if(
//		!$query->is_admin &&
//		!$query->is_search &&
//		(
//			($query->is_category && $query->get('cat') != get_option('eventscategory_ID'))
//			||
//			($query->is_home && get_option('show_on_front') != 'page' && !get_option('page_on_front'))
//		)
//	){
//		print_r($query);
//		
//		print "SET NO EVENTS";
//		#$request['cat'] = '-' . get_option('eventscategory_ID');
//		#If we aren't looking for a specific category, exclude the events category (and children???) from the list
//	}
//}
//add_action('parse_query', 'eventscategory_parse_query_action');


#Tag the SQL query so that modifications can be made by the 'posts_request' filter
function eventscategory_posts_fields($sql){
	return is_events_category() ? "/*EVENTS-FIELDS*/ $sql" : $sql;
}
add_filter('posts_fields', 'eventscategory_posts_fields');


#Tag the SQL query so that modifications can be made by the 'posts_request' filter
function eventscategory_posts_where($sql){
	global $wp_query, $wpdb;
	
	$query = &$wp_query;
	
	#From wp-includes/query.php line #658
	if ( !( $query->is_singular || $query->is_archive || $query->is_search || $query->is_trackback || $query->is_404 || $query->is_admin || $query->is_comments_popup ) ){
		#Modify the WHERE clause if is_home so that the future posts aren't displayed
		$sql .= " AND $wpdb->posts.post_date < '" . current_time('mysql') . "'";
	
	//  #(Note: the preceding line is much more efficient)
	//	#Modify the WHERE clause if is_home so that the future posts in the events category don't pollute the home feed
	//	$cat = (int)get_option('eventscategory_ID');
	//	$category__not_in = array($cat);
	//	$category__not_in = array_merge($category__not_in, get_term_children($cat, 'category'));
	//	
	//	#From wp-includes/query.php line #941
	//	if ( !empty($category__not_in) ) {
	//		$ids = get_objects_in_term($category__not_in, 'category');
	//		if(!is_wp_error($ids) && is_array($ids) && count($ids > 0)){
	//			$out_posts = "'" . implode("', '", $ids) . "'";
	//			$sql .= " AND $wpdb->posts.ID NOT IN ($out_posts)";
	//		}
	//	}
	//	
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
	$query = &$wp_query;
	
	#PROBLEM: We must revisit is_events_category() to go up call stack or to be a new method of WP_Query()
	#Can we create a new Global $eventscategory_wp_query??? Which is set if the query is events category. Then the function is_events_category can just check to see if it exists
	#if(!is_admin() && $query->get('cat') == get_option('eventscategory_ID')){ #PROBLEM: this is not getting the value in the 
	if(!is_admin() && !($query->is_year || $query->is_month || $query->is_day) && is_events_category()){ #PROBLEM: this is not getting the value in the 
		#This SQL query needs to be resdeigned to work with COUNT(*)?
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
		$query->eventscategory_future_found_posts = $wpdb->get_var('SELECT FOUND_ROWS()');
		
		$posts_per = (int)$query->get('posts_per_page');
		$futurePageCount = ceil($query->eventscategory_future_found_posts/$posts_per);
		
		$position = (int)$query->get('eventscategory-position');
		
		if(get_query_var('nopaging') || get_query_var('feed') == 'ical'){
			$sql = preg_replace('{/\*EVENTS-LIMIT\*/\s*LIMIT\s*\d+(\s*,\s*\d*+)?}', '', $sql);
		}
		else {
			$query->eventscategory_future_remainder_count = ($query->eventscategory_future_found_posts % $posts_per);
			$limit1 = $query->eventscategory_future_found_posts-$posts_per*($position+1);
			
			#If we're too far in the future for results
			if($limit1+$posts_per <= 0){
				$limit1 = 0;
				$limit2 = 0;
			}
			#If we are on the last page of future results
			else if($limit1 < 0){
				$limit1 = 0;
				$limit2 = $query->eventscategory_future_remainder_count;
			}
			#Normal case
			else {
				$limit2 = $posts_per;
			}
			
			$paged = $query->query_vars['paged'] = ceil($limit1/$posts_per)+1;
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
		$query = &$wp_query;
		if(!$query->get('nopaging')){
			$past_found_posts = $query->found_posts - $query->eventscategory_future_found_posts;
			$posts_per = (int)$query->get('posts_per_page');
			$query->max_num_pages =
						  ceil($past_found_posts/$posts_per)
						  +
						  ceil($query->eventscategory_future_found_posts/$posts_per);
						  
		}
	}
	return $args; //pass through
}
add_filter('the_posts', 'eventscategory_recalculate_max_num_pages');


#Disable canonical redirection and fix max_num_pages
function eventscategory_wp_action(&$query){
	if(!is_admin() && is_events_category()){
		//Issue: when loading initially, the paged variable is set to enable navigation; the canonical navigation
		//       redirects to the necessary canonical category page including /page/X/; this must be disabled.
		remove_filter('template_redirect', 'redirect_canonical');
	}
}
add_action('wp', 'eventscategory_wp_action');


//function eventscategory_posts_results_filter($posts){
//	return $posts;
//}
//add_filter('posts_results', 'eventscategory_posts_results_filter');


//Note: when we are in future events, we should reverse the posts
function eventscategory_the_posts ($posts){
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
		$query = &$wp_query;
		
		if($query->found_posts > 0)
			$query->max_num_pages++;
			
		$query->is_category = true;
		$query->is_archive = true;
		
		if($template = get_category_template()){
			include($template);
			exit;
		}
	}
}
add_action('template_redirect', 'eventscategory_template_redirect');



#Filter the URLs for the next and previous posts links
function eventscategory_clean_url($url, $original_url, $context){
	global $wp_query;
	$query = &$wp_query;
	
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
			$posts_per = (int)$query->get('posts_per_page');
			if($new_position > 0){
				$newest_position = ceil($query->eventscategory_future_found_posts/$posts_per)-1;
				if($new_position > $newest_position){
					$new_position = $newest_position;
				}
			}
			#Determine if the current page's position is too far in the past
			else if($new_position < 0){
				$oldest_position = -ceil(($query->found_posts - $query->eventscategory_future_found_posts)/$posts_per);
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


require(dirname(__FILE__) . '/widgets.php');


/***** Feed Functions ******************************************************************************/
function eventscategory_do_feed_ical($is_comments){
	load_template(dirname(__FILE__) . '/feed-ical.php');
}
add_action('do_feed_ical', 'eventscategory_do_feed_ical');

function eventscategory_the_title_rss($title){
	global $post;
	if(is_events_category())
		return get_the_time('F jS, g:ia') . ": " . $title;
	else
		return $title;
}
add_filter('the_title_rss', 'eventscategory_the_title_rss');

function eventscategory_the_content_rss($content){
	global $post;
	if(is_events_category() && is_feed() && get_query_var('feed') != 'ical'){
		$date = '<p>' . eventscategory_get_the_time() . "</p>\n\n";
		$location = eventscategory_get_the_location('', "\n\n");
		return $date . $location . $content;
	}
	return $content;
}
add_filter('the_content', 'eventscategory_the_content_rss');


//function eventscategory_rss2_item(){
//	print "\n\t<Vevent xmlns=\"http://www.w3.org/2002/12/cal#\">\n";
//	print "\t\t<dtstart>" . get_post_time('Y-m-d\TH:i:s', true) . "Z</dtstart>\n";
//	$duration = get_post_custom_values('event-duration');
//	if(!empty($duration)){
//		print "\t\t<dtend>" . date('Ymd\THis', (int)get_post_time('U', true) + intval($duration[0])) . "Z</dtend>\n";
//	}
//	print "\t\t<summary>" . get_the_title_rss() . "</summary>\n";
//	#$pmLocation = get_post_custom_values('event-location');
//	#if(!empty($pmLocation)){
//	#	print "\t\t<location>$pmLocation[0]</location>\n";
//	#}
//	print "\t</Vevent>\n";
//}
//add_action('rss2_item', 'eventscategory_rss2_item');
#Note: we should also add xCal 
#xmlns="http://www.ietf.org/internet-drafts/draft-ietf-calsch-many-xcal-01.txt"



function eventscategory_head_add_feeds(){
	global $wp_rewrite;
	$catLink = get_category_link(get_option('eventscategory_ID'));
	
	$rel = 'feed';
	if(is_home() || is_page(get_option('page_on_front')) || is_events_category())
		$rel = 'alternate';
	else if(is_single() && is_events_category(get_the_category()))
		$rel = 'home';
	
	#TODO: It may be that a different feed slug is used than 'feed'
	if($wp_rewrite->get_category_permastruct()){
		$rssParam = 'feed/';
		$icalParam = 'feed/ical/';
	}
	else {
		$rssParam = '&feed=rss2';
		$icalParam = '&feed=ical';
	}
	
	echo "\n";
	echo "\t\t<link rel='$rel' type='application/rss+xml' title=\"" . __('Upcoming Events Feed', 'events-category') . "\" href=\"$catLink$rssParam\" />\n"; //feed/
	echo "\t\t<link rel='$rel' type='text/calendar' title=\"" . __('Events Calendar iCal Feed', 'events-category') . "\" href=\"$catLink$icalParam\" />\n"; //feed/ical/
	echo "\n";
}
add_action('wp_head', 'eventscategory_head_add_feeds');


#http://tools.ietf.org/html/draft-royer-calsch-xcal-03
#http://www.w3.org/TR/2005/NOTE-rdfcal-20050929/


/** Template tags ********************************************************/

function eventscategory_the_time($dt_format = ''){
	echo eventscategory_get_the_time($dt_format);
}

function eventscategory_get_the_time($dt_format = ''){
	global $eventscategory_default_main_date_format;
	if(!$dt_format)
		$dt_format = $eventscategory_default_main_date_format;
	
	$output = '';
	
	if(preg_match('/^(.+?){(?:\[(.+?)\])?(.+?)}(.+)?$/', $dt_format, $matches)){
		$dtstart = $matches[1];
		$dtseparator = $matches[2];
		$dtend = $matches[3];
		$dttz = $matches[4];
		
		$formatChars = 'dDjlNSwzWFmMntLoYyaABgGhHisueIOPTZcrU';
		
		#$cs = preg_split('//', 'dDjlNSwzWFmMntLoYyaABgGhHisueIOPTZcrU'); #all of the valid PHP date formatting characters

		#NOTE: Seconds should not be allowed
		
		$current = array();
		foreach(preg_split('//', $formatChars) as $c){
			$current[$c] = date($c);
		}
		
		$start = array(); #keep track of the values used in the dtstart
		foreach(preg_split('//', $formatChars) as $c){
			$start[$c] = get_the_time($c);
		}
		
		$end = array();
		$endTimestamp = 0;
		$durationPM = get_post_custom_values('event-duration');
		if(!empty($durationPM))
			$duration = (int)$durationPM[0];
		if($duration){
			#$string_time - get_option('gmt_offset') * 3600
			#TODO: Should this be revised? Will ever $duration be greater than PHP_INT_MAX?
			
			#TODO: Adjust duration to account for daylight savings time!
			$endTimestamp = (int)get_post_time('U', true) + get_option('gmt_offset')*3600 + $duration; # - (int)get_post_time('I')*3600
			#$start_DST = date('', $endTimestamp);
			#if()
			
			#echo '<div>' .  (int)get_post_time('U', true) . ' + ' . (int)get_post_time('Z') . ' - ' . (int)get_post_time('I')*3600 . ' + ' . $duration . '</div>';
			#echo '<div>' .  (int)get_post_time('U', true) . ' + ' . get_option('gmt_offset')*3600 . ' - ' . (int)get_post_time('I')*3600 . ' + ' . $duration . '</div>';
			
			
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
				   (preg_match("/[a]/", $c) && $start[$c] == $end[$c] && $duration && $is_same_day) #remove AM/PM specifier if same as end time
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
		$output .= '<abbr class="dtstart" title="' . get_post_time('Ymd\THis', true) . 'Z">';
		$output .= get_the_time($dtstart);
		$output .= '</abbr>';
		
		#dtend: Remove all formatting characters which are redundant
		if($duration){
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
			
			$output .= '<abbr class="dtend" title="' . date('Ymd\THis', (int)get_post_time('U', true) + intval($duration[0])) . 'Z">';
			$output .= date($dtend, $endTimestamp);
			$output .= '</abbr>';
		}
		$output .= '<span class="timezone">';
		$output .= get_the_time($dttz);
		$output .= '</span>';
		return $output;
	}
	else {
		trigger_error('<em style="color:red">' . sprintf(__('Invalid date format: %s', 'events-category'), $options[$number]['date_format']) . '</span>');
		return false;
	}
}

function eventscategory_the_location($before = '', $after = '', $adr_format = ''){
	echo eventscategory_get_the_location($before, $after, $adr_format);
}

function eventscategory_get_the_location($before = '', $after = '', $adr_format = ''){
	global $post, $eventscategory_all_fieldnames, $eventscategory_default_main_address_format;
		
	$output = '';
	
	$fieldValues = array();
	foreach($eventscategory_all_fieldnames as $fieldName){
		list($value) = get_post_custom_values('event-' . $fieldName, $post->ID);
		if(!empty($value))
			$fieldValues[$fieldName] = $value;
	}
	$fields = preg_grep("/^(url|latitude|longitude)$/", $eventscategory_all_fieldnames, PREG_GREP_INVERT);
	
	$is_adr = @($fieldValues['extended-address']
			   || $fieldValues['street-address']
			   || $fieldValues['locality']
			   || $fieldValues['region']
			   || $fieldValues['postal-code']
			   || $fieldValues['country-name']);
	$is_geo = @(is_numeric($fieldValues['latitude']) && is_numeric($fieldValues['longitude']));
	$is_hcard = !empty($fieldValues['fn_org']);
	$is_url = !empty($fieldValues['url']);
	
	if(!$is_hcard && !$is_geo && !$is_url)
		return;
	
	$output .= $before;
	$output .= "<div class='location";
	
	if($is_hcard)
		$output .= ' vcard';
	else if($is_adr)
		$output .= ' adr';
	else if($is_geo)
		$output .= ' geo';
	$output .= "'>";
	
	if($is_hcard){
		$output .= "<span class='fn org'>";
		if($is_url)
			$output .= '<a class="url" href="' . htmlspecialchars($fieldValues['url']) . '">';
		if($is_geo)
			$output .= "<abbr class='geo' title='$fieldValues[latitude];$fieldValues[longitude]'>";
		$output .= htmlspecialchars($fieldValues['fn_org']);
		if($is_geo)
			$output .= '</abbr>';
		if($is_url)
			$output .= "</a>";
		$output .= '</span>';
	}
	if($is_adr){
		if($is_hcard)
			$output .= '<br /><span class="adr">';
		else if($is_url)
			$output .= '<a class="url" href="' . htmlspecialchars($fieldValues['url']) . '">';
		if($is_geo && !$is_hcard)
			$output .= "<abbr class='geo' title='$fieldValues[latitude];$fieldValues[longitude]'>";
		if(!$adr_format)
			$adr_format = $eventscategory_default_main_address_format;
		foreach($fields as $fieldName){
			#list($field) = get_post_custom_values('event-' . $fieldName, $post->ID);
			if(empty($fieldValues[$fieldName])){
				#If no field provided, then remove the placeholder and the surrounding brackets
				$adr_format = str_replace("%$fieldName%", '', preg_replace("/\[[^\[\]]*?%$fieldName%[^\[\]]*?\]/s", '', $adr_format));
			}
			else {
				#If field provided, replace placeholder with it and inject microformats
				$className = str_replace('_', ' ', $fieldName);
				$adr_format = preg_replace("/(?:\[([^\[\]]*?))?%$fieldName%(?:([^\[\]]*?)\])?/s", "$1<span class='$className'>" . $fieldValues[$fieldName] . "</span>$2", $adr_format);
			}
		}
		$output .= join("<br />\n", preg_split("/[\n\r]+/", trim($adr_format)));
		
		if($is_geo && !$is_hcard)
			$output .= '</abbr>';
		if($is_hcard)
			$output .= '</span>';
		else if($is_url)
			$output .= '</a>';
	}
	if($is_geo && !$is_hcard && !$is_adr){
		$output .= "<span class='latitude'>$fieldValues[latitude]</span>, <span class='longitude'>$fieldValues[latitude]</span>";
	}
	
	$output .= "</div>";
	$output .= $after;
	return $output;
}






class EventsCategory_Walker_CategoryDropdown extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); //TODO: decouple this

	function start_el($output, $category, $depth, $args) {
		if(!is_events_category($category->cat_ID))
			return $output;
		
		$pad = str_repeat('&nbsp;', $depth * 3);

		$cat_name = apply_filters('list_cats', $category->name, $category);
		$output .= "\t<option value=\"".$category->term_id."\"";
		if ( in_array($category->term_id, $args['selected']) )
			$output .= ' selected="selected"';
		$output .= '>';
		$output .= $pad.$cat_name;
		if ( $args['show_count'] )
			$output .= '&nbsp;&nbsp;('. $category->count .')';
		if ( $args['show_last_update'] ) {
			$format = 'Y-m-d';
			$output .= '&nbsp;&nbsp;' . gmdate($format, $category->last_update_timestamp);
		}
		$output .= "</option>\n";

		return $output;
	}
}




/********* ADMIN ****************************************************/

function eventscategory_add_admin_style(){
	echo '<link rel="stylesheet" type="text/css" href="../' . PLUGINDIR . '/events-category/admin.css" />';
}
add_action('admin_head', 'eventscategory_add_admin_style');

function eventscategory_insert_data_form(){
	global $eventscategory_all_fieldnames;
	if((basename($_SERVER['PHP_SELF']) == 'post.php' || basename($_SERVER['PHP_SELF']) == 'post-new.php') && @include('edit_data_form.php')){
		echo '<script type="text/javascript">';
		
		echo 'var eventscategory_ID = ' . get_option('eventscategory_ID') . ';';
		echo 'var eventscategory_IDs = [eventscategory_ID];';
		$cats = get_categories('child_of=' . get_option('eventscategory_ID') . '&hide_empty=0');
		foreach($cats as $cat){
			echo 'eventscategory_IDs.push(' . $cat->cat_ID . ');';
		}
		echo 'var eventscategory_posttimestamp_note = ' . json_encode(__('Modify the post time (the event time) above.', 'events-category')) . ';';
		echo 'var eventscategory_all_fieldnames = {"' . join('":1,"', $eventscategory_all_fieldnames) . '":1};';
		echo 'eventscategory_all_fieldnames["duration"] = 1;';
		echo '</script>';
		echo '<script type="text/javascript" src="../' . PLUGINDIR . '/events-category/admin.js"></script>';
	}
}
add_action('admin_footer', 'eventscategory_insert_data_form');
#add_action('admin_footer', 'eventscategory_insert_data_form');

function eventscategory_save_post($postID, $post){
	#Since publishing a post from the future causes the 'save_post' action to be done
	#   check to see if it is future and stop if so, so that this functions logic
	#   is only run once.
	if($post->post_status == 'future'){
		eventscategory_publish_future_post($postID);
		return;
	}
	global $wpdb, $eventscategory_all_fieldnames;
	
	if(empty($_POST['eventscategory_dstart']))
		return;
	$updatedPost = array();
	
	if(preg_match('/^\d\d\d\d-\d\d-\d\d$/', $_POST['eventscategory_dstart']) && preg_match('/^\d\d\d\d-\d\d-\d\d$/', $_POST['eventscategory_dend'])){
		#Create new post_date
		$updatedPost['post_date'] = $_POST['eventscategory_dstart'];
		if(empty($_POST['eventscategory_allday']) && preg_match('/^\d?\d:\d\d$/', $_POST['eventscategory_tstart']))
			$updatedPost['post_date'] .= ' ' . $_POST['eventscategory_tstart'] . ':00';
		else
			$updatedPost['post_date'] .= ' 00:00:00';
		$updatedPost['post_date_gmt'] = get_gmt_from_date($updatedPost['post_date']);
		$wpdb->query("UPDATE $wpdb->posts SET post_date = '$updatedPost[post_date]', post_date_gmt = '$updatedPost[post_date_gmt]' WHERE ID = $postID");
		
		#Find the end date and calculate and save the duration
		$enddate = $_POST['eventscategory_dend'];
		if(empty($_POST['eventscategory_allday']) && preg_match('/^(\d?\d):\d\d$/', $_POST['eventscategory_tend']))
			$enddate .= ' ' . $_POST['eventscategory_tend'] . ':00';
		if(empty($_POST['eventscategory_allday']))
			$duration = strtotime($enddate) - strtotime($updatedPost['post_date']);
		else
			$duration = 0;
		if($duration < 0)
			$duration = 0;
			
		if(!update_post_meta($postID, 'event-duration', $duration))
			add_post_meta($postID, 'event-duration', $duration, true);
	}
	
	#Update location
	foreach($eventscategory_all_fieldnames as $fieldName){
		$value = stripslashes($_POST['eventscategory-' . $fieldName]);
		if(!empty($value)){
			if(!update_post_meta($postID, 'event-' . $fieldName, $value))
				add_post_meta($postID, 'event-' . $fieldName, $value, true);
		}
		else {
			delete_post_meta($postID, 'event-' . $fieldName);
		}
	}
}
add_action('save_post', 'eventscategory_save_post', 10, 2);


?>