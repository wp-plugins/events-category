<?php
/*
iCal Feed for Events Category Plugin
http://wordpress.org/extend/plugins/events-category/

Copyright 2008 Weston Ruter, http://weston.ruter.net/

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

Comments in this file are taken from RFC 2445 http://tools.ietf.org/html/rfc2445
Copyright (C) The Internet Society (1998).  All Rights Reserved.
*/

header('Content-Type: text/calendar; charset=' . get_option('blog_charset'), true);
#header('Content-Type: text/plain; charset=' . get_option('blog_charset'), true);

#ESCAPED-CHAR = "\\" / "\;" / "\," / "\N" / "\n")
#   ; \\ encodes \, \N or \n encodes newline
#   ; \; encodes ;, \, encodes ,
function eventscategory_escape_ical_text($text){
	$replacements = array(
		'\\' => '\\\\',
		';'  => '\\;',
		','  => '\\,',
		"\r\n" => '\\n',
		"\n" => '\\n'
	);
	return '"' . preg_replace('{(' . join('|', array_keys($replacements)) . ')}e', '$replacements["$1"]', $text) . '"';
}


echo "BEGIN:VCALENDAR\n";
echo "VERSION:2.0\n";
echo "PRODID:-//Weston Ruter//Events Category Plugin for WordPress//EN\n";
echo "X-WR-CALNAME:";
	#print get_bloginfo_rss('name') . ' ' . get_the_title_rss();
	#$catName = get_category(get_query_var('category_name'));
	
	echo eventscategory_escape_ical_text(get_bloginfo_rss('name') . wp_title('-', false)) . "\n";
	
	#foreach(get_the_category() as $eventCat){
	#	$eventcategoryList[] = $eventCat->name;
	#}
	#print eventscategory_escape_ical_text(sprintf(__("%s << %s", 'events-category'),
	#											  #join(__(', ', 'events-category'), $catName->name),
	#											  $catName->name,
	#											  get_bloginfo('name', 'display'))) . "\n";
print "CALSCALE:GREGORIAN\n";

print "\n";
while(have_posts()){
	the_post(); 
	echo "BEGIN:VEVENT\n";
	
	#QUESTION: Should we be including the timezone in the DT fields? TZOFFSETFROM?
	#   e.g. DTSTART;TZID=America/Los_Angeles:20060509T160000 ... get_post_time('e:Ymd\THis', false)
	
	#UID: This property defines the persistent, globally unique identifier for the calendar component.
	#echo 'UID:' . eventscategory_escape_ical_text($post->ID) . "\n";
	
	#DTSTART: This property specifies when the calendar component begins.
	echo 'DTSTART:' . get_post_time('Ymd\THis', true) . "Z\n";
	
	#DTEND: This property specifies the date and time that a calendar component ends.
	#       This is used instead of DURATION because the exact duration in seconds
	#       is stored, so the exact end time can be found.
	$duration = get_post_custom_values('event-duration');
	if(!empty($duration))
		echo 'DTEND: ' . date('Ymd\THis', (int)get_post_time('U', true) + intval($duration[0])) . "Z\n";
	else
		echo "DTEND:" . get_post_time('Ymd\THis', true) . "Z\n";
	echo 'DTSTAMP:' . get_post_time('Ymd\THis', true) . "Z\n";
	
	#LOCATION: The property defines the intended venue for the activity
	#          defined by a calendar component.
	//$location = get_post_custom_values('event-location');
	//if(!empty($location))
	//	echo "LOCATION:" . eventscategory_escape_ical_text($location[0]) . "\n";
	
	#GEO: This property specifies information related to the global
	#     position for the activity specified by a calendar component.
	list($latitude) = get_post_custom_values('event-latitude');
	list($longitude) = get_post_custom_values('event-longitude');
	if(!empty($latitude) && !empty($longitude) && is_numeric($latitude) && is_numeric($longitude)){
		echo "GEO:$latitude;$longitude\n";
	}
	
	#SUMMARY: This property defines a short summary or subject for the calendar component.
	echo "SUMMARY:" . eventscategory_escape_ical_text(get_the_title()) . "\n";
	
	#DESCRIPTION: This property provides a more complete description of the calendar component,
	#             than that provided by the "SUMMARY" property.
	$description = apply_filters('the_content', trim((get_option('rss_use_excerpt') || !(strlen( $post->post_content ) > 0 )
				   ?
				   get_the_excerpt()
				   :
				   get_the_content())));
	if($description)
		echo 'DESCRIPTION:' . eventscategory_escape_ical_text($description) . "\n"; #preg_replace('{([\.,])}', '\\\\$1', $description)
	
	#URL: This property defines a Uniform Resource Locator (URL) associated with the iCalendar object.
	echo "URL;VALUE=URI:" . get_permalink() . "\n";
	
	#ORGANIZER: The property defines the organizer for a calendar component.
	$organizer = get_userdata($post->post_author);
	echo "ORGANIZER:" . $organizer->user_nicename . "\n";
	
	#ATTACH: The property provides the capability to associate a document object with a calendar component.
	#Attach the enclosures? #ATTACH;FMTTYPE=application/postscript:ftp://xyzCorp.com/pub/reports/r-960812.ps

	#COMMENT: This property specifies non-processing information intended to provide a comment to the calendar user.
	foreach(get_approved_comments($post->ID) as $comment){
		
		echo 'COMMENT:' . eventscategory_escape_ical_text(
			sprintf(__("<cite>%s</cite>: %s", 'events-category'),
				htmlspecialchars($comment->comment_author),
				apply_filters('comment_text', $comment->comment_content)
			)) . "\n";
	}
	
	#TODO Right here in the future: we can lookup in postmeta to see how the comment author is RSVP
	#ATTENDEE (we can extend the comments to include wether they are coming, might come, or not coming!)
	# ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=TENTATIVE;CN=Henry Cabot
	#  :MAILTO:hcabot@host2.com
	# ATTENDEE;ROLE=REQ-PARTICIPANT;DELEGATED-FROM="MAILTO:bob@host.com"
	#  ;PARTSTAT=ACCEPTED;CN=Jane Doe:MAILTO:jdoe@host1.com
	
	#EXDATE and EXRULE and RDATE and RRULE???
	#When creating a new post, we could specify RECURRENCE; it would then automatically
	#	create subsequent posts. When one of these subsequent posts is deleted, this EXDATE
	#	could be set as event-recurrence-exceptions postmeta.
	#When one of the subsequent items has their time change, we could prompt: change time for this instance
	#	only, for all following, or for only this instance.
	
	#CREATED: This property specifies the date and time that the calendar information was created by the
	#         calendar user agent in the calendar store. Note: This is analogous to the creation date and time for a file
    #         in the file system.
    #Get from postmeta event-time-created

	#LAST-MODIFIED: Purpose: The property specifies the date and time that the information associated with
	#               the calendar component was last revised in the calendar store. Note: This is analogous
	#               to the modification date and time for a file in the file system.
	#Get from postmeta event-time-modified
	
	do_action('ical_item');
	print "END:VEVENT\n\n";
}
print "END:VCALENDAR\n";