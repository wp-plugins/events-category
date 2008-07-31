<?php

/***** Feed Functions ******************************************************************************/
//function eventscategory_do_feed_ical($is_comments){ //TODO
//	load_template(dirname(__FILE__) . '/feed-ical.php');
//}
//add_action('do_feed_ical', 'eventscategory_do_feed_ical');

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
//	$duration = get_post_custom_values('_event_duration');
//	if(!empty($duration)){
//		print "\t\t<dtend>" . date('Ymd\THis', (int)get_post_time('U', true) + intval($duration[0])) . "Z</dtend>\n";
//	}
//	print "\t\t<summary>" . get_the_title_rss() . "</summary>\n";
//	#$pmLocation = get_post_custom_values('_event_location');
//	#if(!empty($pmLocation)){
//	#	print "\t\t<location>$pmLocation[0]</location>\n";
//	#}
//	print "\t</Vevent>\n";
//}
//add_action('rss2_item', 'eventscategory_rss2_item');
#Note: we should also add xCal 
#xmlns="http://www.ietf.org/internet-drafts/draft-ietf-calsch-many-xcal-01.txt"

$eventscategory_feed_mime_types = array(
	'feed' => 'application/rss+xml',
	'rdf' => 'application/rdf+xml',
	'rss' => 'text/xml',
	'rss2' => 'application/rss+xml',
	'atom' => 'application/atom+xml',
	'ical' => 'text/calendar'
);
$eventscategory_feed_names = array(
	'feed' => __('RSS 2.0', 'events-category'),
	'rss2' => __('RSS 2.0', 'events-category'),
	'rdf' => __('RDF', 'events-category'),
	'rss' => __('RSS', 'events-category'),
	'atom' => __('Atom', 'events-category'),
	'ical' => __('iCal', 'events-category'),
);

function eventscategory_head_add_feeds(){
	global $wp_rewrite, $eventscategory_feed_mime_types, $eventscategory_feed_names;
	
	#Get the link relationship
	$rel = 'feed';
	if(is_home() || is_page(get_option('page_on_front')) || is_events_category())
		$rel = 'alternate';
	else if(is_single() && is_events_category(get_the_category()))
		$rel = 'home';

	if ($catPermaStruct = $wp_rewrite->get_category_permastruct()) {
		$link = get_category_link(get_option('eventscategory_ID'));
		$link = trailingslashit($link);
	} else {
		$link = get_option('home') . '?cat=' . get_option('eventscategory_ID') . '&amp;feed=';
	}
	
	echo "\n";
	
	#Get all feed types
	$feeds = (array)$wp_rewrite->feeds;
	array_push($feeds, 'ical');
	array_unique($feeds);
	
	#Write out a link for each of the feed types
	foreach($feeds as $feed){
		if($feed == 'feed')
			continue; #do this???
		
		echo "\t\t<link rel='$rel'";
		if(!empty($eventscategory_feed_mime_types[$feed]))
			echo " type=\"$eventscategory_feed_mime_types[$feed]\" ";
		echo "title=\"" . __(sprintf('Upcoming Events %s Feed', (empty($eventscategory_feed_names[$feed]) ? $feed : $eventscategory_feed_names[$feed])), 'events-category') . "\" href=\"";
		if($catPermaStruct)
			echo apply_filters('category_feed_link', $link . user_trailingslashit('feed/' . $feed, 'feed'));
		else
			echo apply_filters('category_feed_link', $link . $feed);
		echo "\" />\n";
	}
	echo "\n";
}
add_action('wp_head', 'eventscategory_head_add_feeds');


#http://tools.ietf.org/html/draft-royer-calsch-xcal-03
#http://www.w3.org/TR/2005/NOTE-rdfcal-20050929/

?>