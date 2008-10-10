<?php

/** Template tags ********************************************************/

function eventscategory_the_time($dt_format = ''){
	echo eventscategory_get_the_time($dt_format);
}

function eventscategory_get_the_time($dt_format = ''){
	global $eventscategory_default_main_date_format;
	if(!$dt_format)
		$dt_format = get_option('eventscategory_date_format'); #$dt_format = $eventscategory_default_main_date_format;
	
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
		$durationPM = get_post_custom_values('_event_duration');
		if(!empty($durationPM))
			$duration = (int)$durationPM[0];
		if($duration){
			#$string_time - get_option('gmt_offset') * 3600
			#TODO: Should this be revised? Will ever $duration be greater than PHP_INT_MAX?
			
			#TODO: Adjust duration to account for daylight savings time!
			#$endTimestamp = (int)get_post_time('U', true) + get_option('gmt_offset')*3600 + $duration; # - (int)get_post_time('I')*3600

			$endTimestamp = mktime(
				(int)get_post_time('H'), //G for some reason is erroneous
				(int)get_post_time('i'),
				(int)get_post_time('s')+$duration,
				(int)get_post_time('m'),
				(int)get_post_time('d'),
				(int)get_post_time('Y')
			);

			//We need to set
			//$tomorrow  = mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"));
			//$lastmonth = mktime(0, 0, 0, date("m")-1, date("d"),   date("Y"));
			//$nextyear  = mktime(0, 0, 0, date("m"),   date("d"),   date("Y")+1);
			//Note: This can be more reliable than simply adding or subtracting the number of seconds in a day or month to a timestamp because of daylight saving time.

			//echo "<br><font color=blue>" . date('r T', mktime(
			//	(int)get_post_time('G'),
			//	(int)get_post_time('i'),
			//	(int)get_post_time('s'),
			//	(int)get_post_time('m'),
			//	(int)get_post_time('d'),
			//	(int)get_post_time('Y')
			//)) . '</font><br>';
			//
			//echo "<font color=green>" . date('r T', mktime(
			//	(int)get_post_time('G'),
			//	(int)get_post_time('i'),
			//	(int)get_post_time('s') + $duration,
			//	(int)get_post_time('m'),
			//	(int)get_post_time('d'),
			//	(int)get_post_time('Y')
			//)) . '</font><br>';
			
			#$start_DST = date('', $endTimestamp);
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
		$output .= '<time class="dtstart" datetime="' . get_post_time('Ymd\THis', true) . 'Z">';
		#echo "<br><font color=green>" . get_post_time('Ymd\THis', true) . '</font>';
		$output .= get_the_time($dtstart);
		$output .= '</time>';
		#echo $duration;
		#dtend: Remove all formatting characters which are redundant
		if($duration){
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
			
			$output .= '<time class="dtend" datetime="' . date('Ymd\THis', (int)get_post_time('U', true) + intval($duration)) . 'Z">';
			$output .= date($dtend, $endTimestamp);
			$output .= '</time>';
		}
		
		$gmt_offset = get_option('gmt_offset');
		$timezone = get_option('eventscategory_timezone');
		$timezone_dst = get_option('eventscategory_timezone_dst');
		
		//Big issue: We need to be able to determine if an arbitrary date is in daylight savings
		//We need to automatically update gmt_offset when DST starts and ends
		//We need to automatically set daylight savings time!!! This is a core feature.
		
		if($duration)
			$is_dst = date('I', $endTimestamp);
		else
			$is_dst = get_the_time('I');
		
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
				$output .= get_the_time($dttz);
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

function eventscategory_the_location($before = '', $after = '', $adr_format = ''){
	echo eventscategory_get_the_location($before, $after, $adr_format);
}

function eventscategory_get_the_location($before = '', $after = '', $adr_format = ''){
	global $post, $eventscategory_all_fieldnames, $eventscategory_default_main_address_format;
		
	$output = '';
	
	$fieldValues = array();
	foreach($eventscategory_all_fieldnames as $fieldName){
		list($value) = get_post_custom_values('_event_' . $fieldName, $post->ID);
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
	
	if(!$is_hcard && !$is_geo && !$is_url && !$is_adr)
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
			#list($field) = get_post_custom_values('_event_' . $fieldName, $post->ID);
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







?>