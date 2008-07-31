<?php
/*
Widgets for Events Category WordPress Plugin <http://wordpress.org/extend/plugins/events-category/>
By Weston Ruter <http://weston.ruter.net/>
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

$eventscategory_default_widget_date_format = __('M. jS[, g][:i][a]{[ - ][M. ][j][S,] g[:i]a} T', 'events-category');
$eventscategory_default_widget_address_format = __("[%street-address%]\n[%extended-address%]\n[%locality%][, %region%][ %postal-code%]\n[%country-name%]", 'events-category');
add_option('eventscategory_widget_upcoming', array(
	'number' => 1,
	'defaults' => array(
		'title' => __('Upcoming Events', 'events-category'),
		'show_posts' => 3,
		'display_location' => true,
		'date_format' => $eventscategory_default_widget_date_format,
		'address_format' => $eventscategory_default_widget_address_format,
		'selected_categories' => array()
	)
));

function eventscategory_widget_upcoming($args, $number = 1) { 
    global $wpdb, $post, $wp_query, $wp_rewrite, $eventscategory_feed_names, $eventscategory_feed_mime_types;
	extract($args);
	$options = get_option('eventscategory_widget_upcoming');

	#Build and run query
	$query_vars = array(
		showposts => $options[$number]['show_posts']
	);
	#print_r($options[$number]);
	if(!empty($options[$number]['selected_categories']))
		$query_vars['category__in'] = $options[$number]['selected_categories'];
	else
		$query_vars['cat'] = get_option('eventscategory_ID');
	
	#$query = new WP_Query($query_vars);
	#eventscategory_register_query(&$query);
	#$events = $query->query($query_vars);
	#if ($query->have_posts()){
	$old_wp_query = (object)$wp_query;
	$old_post = (object)$post;
	query_posts($query_vars);
	if(have_posts()){
		echo $before_widget; 
		echo $before_title;
		
		#echo '<a href="' . get_category_link(get_option('eventscategory_ID')) . '">';
		echo htmlspecialchars($options[$number]['title']);
		#echo  '</a>';
		echo $after_title;
		
		echo '<ol>';
		#while($query->have_posts()){ $query->the_post();
		while(have_posts()){ the_post();
			echo '<li class="vevent">';
			echo '<a href="' . get_permalink() . '" rel="bookmark" class="summary">';
			the_title();
			echo '</a>';
			
			echo "<div class='datetime'>";
			eventscategory_the_time($options[$number]['date_format']);
			echo '</div>';
			
			#Display the address
			if($options[$number]['display_location'])
				eventscategory_the_location('', '', $options[$number]['address_format']);
			echo '</li>';
		}
		echo '</ol>';
		
		$queried_cats = array();
		if(!empty($query_vars['category__in']))
			$queried_cats = $query_vars['category__in'];
		else
			$queried_cats = array(get_option('eventscategory_ID'));
		
		if(count($queried_cats) == 1){
			echo '<div class="feeds">';
			
			if ($catPermaStruct = $wp_rewrite->get_category_permastruct()) {
				$link = get_category_link($queried_cats[0]);
				$link = trailingslashit($link);
			} else {
				$link = get_option('home') . '?cat=' . $queried_cats[0] . '&amp;feed=';
			}
			
			#Get all feed types
			$feeds = (array)$wp_rewrite->feeds;
			array_push($feeds, 'ical');
			array_unique($feeds);
			
			#Write out a link for each of the feed types
			foreach($feeds as $feed){
				if($feed == 'feed')
					continue; #do this???
				
				echo "\t\t<a class='$feed feed' rel='feed'";
				if(!empty($eventscategory_feed_mime_types[$feed]))
					echo " type=\"$eventscategory_feed_mime_types[$feed]\" ";
				echo "title=\"" . __(sprintf('Subscribe to %s Feed', (empty($eventscategory_feed_names[$feed]) ? $feed : $eventscategory_feed_names[$feed])), 'events-category') . "\" href=\"";
				if($catPermaStruct)
					echo apply_filters('category_feed_link', $link . user_trailingslashit('feed/' . $feed, 'feed'));
				else
					echo apply_filters('category_feed_link', $link . $feed);
				echo "\"><span class='text'>";
				echo (empty($eventscategory_feed_names[$feed]) ? $feed : $eventscategory_feed_names[$feed]);
				echo "</span></a> ";
			}
			echo '</div>';
		}
		echo '<div class="more"><a href="' . get_category_link(get_option('eventscategory_ID')) . '">' . __("More &raquo;", 'events-category') . '</a></div>';
		#previous_posts_link('Newer Entries &raquo;');
		echo $after_widget; 
	}
	#eventscategory_unregister_query();
	$wp_query = $old_wp_query;
	$post = $old_post;
	setup_postdata($post);
}

function eventscategory_widget_upcoming_control($number){
	global $eventscategory_default_widget_date_format, $eventscategory_default_widget_address_format;
	
	$options = $newoptions = get_option('eventscategory_widget_upcoming');
	if ( !is_array($options) )
		$options = $newoptions = array();
	
	if($_POST["eventscategory-widget-submit$number"]){
		$newoptions[$number]['title'] = strip_tags(stripslashes($_POST["eventscategory-widget-title$number"]));
		$newoptions[$number]['show_posts'] = intval($_POST["eventscategory-widget-show_posts$number"]);
		if(empty($newoptions[$number]['show_posts']))
			$newoptions['show_posts'][$number] = $options['show_posts'];
		$newoptions[$number]['date_format'] = strip_tags(stripslashes($_POST["eventscategory-widget-date_format$number"]));
		$newoptions[$number]['display_location'] = !empty($_POST["eventscategory-widget-display_location$number"]);
		$newoptions[$number]['address_format'] = strip_tags(stripslashes($_POST["eventscategory-widget-address_format$number"]));
		$newoptions[$number]['selected_categories'] = $_POST["eventscategory-widget-selected_categories$number"];
	}
	if($options != $newoptions){
		$options = $newoptions;
		update_option('eventscategory_widget_upcoming', $options);
	}
	$title = htmlspecialchars($options[$number]['title'], ENT_QUOTES);
	$show_posts = htmlspecialchars($options[$number]['show_posts'], ENT_QUOTES);
	$date_format = htmlspecialchars($options[$number]['date_format'], ENT_QUOTES);
	$display_location = $options[$number]['display_location'];
	$address_format = htmlspecialchars($options[$number]['address_format'], ENT_QUOTES);
	
	echo "<div style='text-align:left'>";
	echo "<p><label>" . __('Title: ', 'events-category') . "<input type='text' id='eventscategory-widget-title$number' name='eventscategory-widget-title$number' value=\"$title\" required='required' /></label></p>";
	echo "<p><label>" . __('Upcoming events to show: ', 'events-category') . "<input type='number' id='eventscategory-widget-show_posts$number' name='eventscategory-widget-show_posts$number' value=\"$show_posts\" size=\"3\" required='required' min='1' /></label></p>";
	echo "<p><label>" . __('Date format: ', 'events-category') . " <span style='color:gray; font-size:smaller;'>" . __('(using hybrid PHP date format)', 'events-category');
	echo "     <a href='javascript:void(0);' onclick='document.getElementById(\"eventscategory-widget-date_format$number\").value = \"" . addslashes($eventscategory_default_widget_date_format) . "\"'>" . __('reset', 'events-category') ."</a></span>";
	echo "<br /><input type='text' id='eventscategory-widget-date_format$number' name='eventscategory-widget-date_format$number' value=\"$date_format\" required='required' style='width:100%' /></label></p>";
	
	echo "<p><label><input type='checkbox' id='eventscategory-widget-show-all-subcats$number' " . ($display_location ? " checked='checked' " : '') . "  onclick=\"";
	echo   "var span = document.getElementById('eventscategory-widget-p-selected_categories$number');";
	echo   "var select = document.getElementById('eventscategory-widget-selected_categories$number'); ";
	echo   "for(var i = 0; i < select.length; i++){ select.options[i].selected = !this.checked } ";
	echo   "select.onchange();";
	#echo   "span.style.display = (this.checked ? 'none' : 'block');";
	#echo   "document.getElementById('eventscategory-widget-p-address_format$number').style.display = this.checked ? 'block' : 'none'; ";
	echo "\"/> " . __('Include all posts under Events category', 'events-category') . "</label></p>";
	
	echo "<p style='display:block' id='eventscategory-widget-p-selected_categories$number'>";
	echo "<label>" . __('Show events from the following categories:', 'events-category');
	
	$r = array(
		'show_option_all' => '',
		'show_option_none' => '',
		'orderby' => 'ID',
		'order' => 'ASC',
		'show_last_update' => 0,
		'show_count' => 0,
		'hide_empty' => 0,
		'child_of' => 0,
		'exclude' => '',
		'echo' => 1,
		'selected' => 0,
		'hierarchical' => 1,
		'name' => 'cat',
		'class' => 'postform'
	);
	$r['include_last_update_time'] = $r['show_last_update'];
	$categories = get_categories($r);
	$r['selected'] = $newoptions[$number]['selected_categories'];
	$walker = new EventsCategory_Walker_CategoryDropdown();
	echo "<select id='eventscategory-widget-selected_categories$number' name='eventscategory-widget-selected_categories{$number}[]' multiple='multiple' size='4' onchange=\"";
	echo   "var selected = false; for(var i = 0; i < this.length; i++){ if(this.options[i].selected){ selected = true; break; }} ";
	echo   "if(!selected){ document.getElementById('eventscategory-widget-show-all-subcats$number').checked = true; this.parentNode.parentNode.style.display = 'none'; }";
	echo   "else { document.getElementById('eventscategory-widget-show-all-subcats$number').checked = false; this.parentNode.parentNode.style.display = 'block'; }"; 
	echo "\">\n";
	echo $walker->walk($categories, 0, $r);
	echo "</select>";
	echo "</label></p>";
	echo "<script type='text/javascript'>document.getElementById('eventscategory-widget-selected_categories$number').onchange()</script>";
	
	echo "<p><label><input type='checkbox' id='eventscategory-widget-display_location$number' name='eventscategory-widget-display_location$number' value=\"1\" " . ($display_location ? " checked='checked' " : '') . "  onclick=\"";
	echo   "document.getElementById('eventscategory-widget-address_format$number').disabled = !this.checked; document.getElementById('eventscategory-widget-p-address_format$number').style.display = this.checked ? 'block' : 'none'; ";
	echo "\"/> " . __('Display location', 'events-category') . "</label></p>";
	
	echo "<p id='eventscategory-widget-p-address_format$number' " . (!$display_location ? " style='display:none' " : '') . ">";
	echo "<label>" . __('Address format: ', 'events-category');
	echo "<span style='font-size:smaller'><a href='javascript:void(0);' onclick='document.getElementById(\"eventscategory-widget-address_format$number\").value = \"" . addslashes($eventscategory_default_widget_address_format) . "\"'>" . __('reset', 'events-category') ."</a></span>";
	
	echo "<br /><textarea id='eventscategory-widget-address_format$number' name='eventscategory-widget-address_format$number' rows='4'  " . (!$display_location ? " disabled='disabled' " : '') . " style='width:100%'>$address_format</textarea></label></p>";
	echo "<input type='hidden' id='eventscategory-widget-submit$number' name='eventscategory-widget-submit$number' value='1' />";
	
	echo "</div>";
}
function eventscategory_widget_upcoming_setup() {
	$options = $newoptions = get_option('eventscategory_widget_upcoming');
	if ( isset($_POST['eventscategory-widget-number-submit']) ) {
		$number = (int) $_POST['eventscategory-widget-number'];
		if ( $number > 9 ) $number = 9;
		if ( $number < 1 ) $number = 1;
		$newoptions['number'] = $number;
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('eventscategory_widget_upcoming', $options);
		eventscategory_widget_upcoming_register($options['number']);
	}
}

function eventscategory_widget_upcoming_page() {
	$options = $newoptions = get_option('eventscategory_widget_upcoming');
?>
	<div class="wrap">
		<form method="POST">
			<h2><?php _e('Upcoming Events Widgets', 'events-category'); ?></h2>
			<p style="line-height: 30px;"><?php _e('How many upcoming events widgets would you like?', 'events-category'); ?>
			<select id="eventscategory-widget-number" name="eventscategory-widget-number" value="<?php echo $options['number']; ?>">
<?php for ( $i = 1; $i < 10; ++$i ) echo "<option value='$i' ".($options['number']==$i ? "selected='selected'" : '').">$i</option>"; ?>
			</select>
			<span class="submit"><input type="submit" name="eventscategory-widget-number-submit" id="eventscategory-widget-number-submit" value="<?php echo attribute_escape(__('Save')); ?>" /></span></p>
		</form>
	</div>
<?php
}

function eventscategory_widget_upcoming_register(){
	$newoptions = $options = get_option('eventscategory_widget_upcoming');
	$number = $options['number'];
	if ( $number < 1 ) $number = 1;
	if ( $number > 9 ) $number = 9;
	$dims = array('width' => 300, 'height' => 445);
	$class = array('classname' => 'eventscategory_widget_upcoming');
	for ($i = 1; $i <= 9; $i++) {
		$name = sprintf(__('Upcoming Events %d', 'events-category'), $i);
		$id = "eventscategory_widget_upcoming-$i"; // Never never never translate an id
		wp_register_sidebar_widget($id, $name, $i <= $number ? 'eventscategory_widget_upcoming' : /* unregister */ '', $class, $i);
		wp_register_widget_control($id, $name, $i <= $number ? 'eventscategory_widget_upcoming_control' : /* unregister */ '', $dims, $i);
		if($i <= $number){
			if(!isset($options[$i]))
				$newoptions[$i] = $options['defaults'];
		}
		else
			unset($newoptions[$i]);
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('eventscategory_widget_upcoming', $options);
	}
		
	add_action('sidebar_admin_setup', 'eventscategory_widget_upcoming_setup');
	add_action('sidebar_admin_page', 'eventscategory_widget_upcoming_page');
}


function eventscategory_plugins_loaded(){
	//register_sidebar_widget('Akismet', 'widget_akismet', null, 'akismet');
	//register_widget_control('Akismet', 'widget_akismet_control', 300, 75, 'akismet');
	#$options = get_option('eventscategory_widget_upcoming');
	#for($i = 1; $i <= $options['number']; $i++){
	#	eventscategory_widget_upcoming_register($i);
	#}
	eventscategory_widget_upcoming_register();
}
add_action('plugins_loaded', 'eventscategory_plugins_loaded');




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




?>