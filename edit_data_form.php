
<fieldset id='eventscategory_data_editor'>
	<legend><?php _e('Event Details', 'events-category') ?></legend>
	<div class='form'>
		<div class='datetime'>
			<label for="eventscategory_dstart"><?php _e('Time: ', 'events-category') ?></label>
			<input type="date" id="eventscategory_dstart" name="eventscategory_dstart" value="" maxlength="10" required="required" />
			<input type="time" id="eventscategory_tstart" name="eventscategory_tstart" value="" maxlength="5" required="required" />
			<label for="eventscategory_dend"><?php _e('to', 'events-category') ?></label>
			<input type="date" id="eventscategory_dend" name="eventscategory_dend" value="" maxlength="10" required="required" />
			<input type="time" id="eventscategory_tend" name="eventscategory_tend" value="" maxlength="5" required="required" />
			
			<input type="checkbox" id="eventscategory_allday" name="eventscategory_allday" <?php
			#$duration = get_post_meta($_GET['post'], 'event-duration');
			#if(!empty($duration) && $duration[0]){
			#	echo ' checked="checked" ';
			#}
			?>/>
			<label for="eventscategory_allday">All day</label>
		</div>
		<div id='eventscategory_reoccurance_form' class='no'>
			<div id='eventscategory_repeats_form' class='reoccuranceRow'>
				<label for="eventscategory_repeats" class='main'><?php _e('Repeats:', 'events-category') ?></label>
				<select id="eventscategory_repeats" name="eventscategory_repeats">
					<option value="no"><?php _e('Does not repeat', 'events-category') ?></option>
					<option value="daily"><?php _e('Daily', 'events-category') ?></option>
					<option value="weekly"><?php _e('Weekly', 'events-category') ?></option>
					<option value="monthly"><?php _e('Monthly', 'events-category') ?></option>
					<option value="yearly"><?php _e('Yearly', 'events-category') ?></option>
				</select>
			</div>
			<div id='eventscategory_repeatevery_form' class='reoccuranceRow'>
				<label class='main' for="eventscategory_repeatevery"><?php _e('Repeat every:') ?></label>
				<select id="eventscategory_repeatevery" name="eventscategory_repeatevery">
				<?php
				foreach(range(1,14) as $value){
					echo "<option>$value</option>"; #selected???
				}
				?>
				</select>
				<span class='day'><?php _e('day', 'events-category') ?></span>
				<span class='days'><?php _e('days', 'events-category') ?></span>
				<span class='week'><?php _e('week', 'events-category') ?></span>
				<span class='weeks'><?php _e('weeks', 'events-category') ?></span>
				<span class='month'><?php _e('month', 'events-category') ?></span>
				<span class='months'><?php _e('months', 'events-category') ?></span>
				<span class='year'><?php _e('year', 'events-category') ?></span>
				<span class='years'><?php _e('years', 'events-category') ?></span>
			</div>
			<div id="eventscategory_repeaton_form" class='reoccuranceRow'>
				<label class='main'><?php _e('Repeat on:', 'events-category') ?></label>
				<?php
				$i = (int)get_option('start_of_week');
				$weekDays = array(
					__('Sun', 'events-category'),
					__('Mon', 'events-category'),
					__('Tue', 'events-category'),
					__('Wed', 'events-category'),
					__('Thu', 'events-category'),
					__('Fri', 'events-category'),
					__('Sat', 'events-category')
				);
				foreach(range($i, $i+6) as $j){
					$weekDay = $j%7;
					echo "<label class='weekDay'><input type='checkbox' name='eventscategory_repeaton[]' value='$weekDay' /> $weekDays[$weekDay] </label> ";
				}
				?>
			</div>
			<div id="eventscategory_repeatby_form" class='reoccuranceRow'>
				<label class='main'><?php _e('Repeat by:', 'events-category') ?></label>
			
				<input type="radio" name="eventscategory_repeatby" id="eventscategory_repeatby_monthday" value="monthday" />
				<label for="eventscategory_repeatby_monthday"><?php _e('day of the month', 'events-category') ?></label>
				<input type="radio" name="eventscategory_repeatby" id="eventscategory_repeatby_weekday" value="weekday" />
				<label for="eventscategory_repeatby_weekday"><?php _e('day of the week', 'events-category') ?></label>
			</div>
			<div id="eventscategory_range_form" class='reoccuranceRow'>
				<span class='starts'>
					<label class='main'><?php _e('Range:', 'events-category') ?></label>
				
					<label for="eventscategory_range_start"><?php _e('Starts:', 'events-category') ?></label>
					<input id="eventscategory_range_start" name="eventscategory_range_start" type="date" value="" />
					
				</span>
				<span class='ends'>
					<?php _e('Ends:', 'events-category') ?>
					<input id="eventscategory_range_end_never_option" name="eventscategory_range_end_option" type="radio" value="never" />
					<label for="eventscategory_range_end_never_option"><?php _e('Never', 'events-category') ?></label>
				
					<input id="eventscategory_range_end_until_option" name="eventscategory_range_end_option" type="radio" value="until" />
					<label for="eventscategory_range_end_until_option"><?php _e('Until', 'events-category') ?></label>
					<input id="eventscategory_range_end" name="eventscategory_range_end" type="date" value="" />
				</span>
			</div>
		</div>
		<div class='location'>
			<?php //_e('Location: ', 'events-category') ?>
			<?php
			global $eventscategory_default_main_address_format;
			
			$fieldsLocale = array(
				'fn_org'          => array(
					'label-text'  => __('Place name: ', 'events-category'),
					'input-style' => __('width:22ex;', 'events-category'),
					'label-style' => __('float:left; width:11ex; text-align:right; padding-right:5px;', 'events-category')
				),
				//'post-office-box',
				'street-address'  => array(
					'label-text'  => __('Address: ', 'events-category'),
					'input-style' => __('width:22ex;', 'events-category'),
					'label-style' => __('float:left; width:11ex; text-align:right; padding-right:5px;', 'events-category')
				),
				'extended-address'=> array(
					'label-text'  => __('&nbsp;', 'events-category'),
					'input-style' => __('width:22ex;', 'events-category'),
					'label-style' => __('float:left; width:11ex; text-align:right; padding-right:5px;', 'events-category')
				),
				'locality'        => array(
					'label-text'  => __('City: ', 'events-category'),
					'input-style' => __('width:22ex;', 'events-category'),
					'label-style' => __('float:left; width:11ex; text-align:right; padding-right:5px;', 'events-category')
				),
				'region'          => array(
					'label-text'  => __('State/Province: ', 'events-category'),
					'input-style' => __('width:15ex;', 'events-category'),
					'label-style' => __('', 'events-category')
				),
				'postal-code'     => array(
					'label-text'  => __('Postal Code: ', 'events-category'),
					'input-style' => __('width:7ex;', 'events-category'),
					'label-style' => __('', 'events-category')
				),
				'country-name'    => array(
					'label-text'  => __('Country: ', 'events-category'),
					'input-style' => __('width:22ex;', 'events-category'),
					'label-style' => __('float:left; width:11ex; text-align:right; padding-right:5px;', 'events-category')
				),
				'url'             => array(
					'label-text'  => __('URL: ', 'events-category'),
					'input-style' => __('width:90ex; font-size:8pt;', 'events-category'),
					'label-style' => __('float:left; width:11ex; text-align:right; padding-right:5px;', 'events-category'),
					'input-type'  => 'url'
				),
				'latitude'        => array(
					'label-text'  => __('Latitude: ', 'events-category'),
					'input-style' => __('width:20ex;', 'events-category'),
					'label-style' => __('float:left; width:11ex; text-align:right; padding-right:5px;', 'events-category')
				),
				'longitude'       => array(
					'label-text'  => __('Longitude: ', 'events-category'),
					'input-style' => __('width:20ex;', 'events-category'),
					'label-style' => __('', 'events-category')
				)
			);
			
			global $post;
			#Manually add place name, URL and latitude/longitude
			$format = preg_replace("/(?:\[([^\[\]]*?))?%(?:fn_org|url|latitude|longitude)%(?:([^\[\]]*?)\])?/", '', $eventscategory_default_main_address_format);
			$format = "[%fn_org%]\n" . $format;
			$format .= "\n[%url%]";
			$format .= "\n[%latitude%][, %longitude%]";
			
			foreach(array_keys($fieldsLocale) as $fieldName){
				list($field) = get_post_custom_values('event-' . $fieldName, $post->ID);
				//if(empty($field)){
				//	#If no field provided, then remove the placeholder and the surrounding brackets
				//	$format = str_replace("%$fieldName%", '', preg_replace("/\[[^\[\]]*?%$fieldName%[^\[\]]*?\]/s", '', $format));
				//}
				//else {
					#If field provided, replace placeholder with it and inject microformats
					$format = preg_replace("/(?:\[([^\[\]]*?))?%$fieldName%(?:([^\[\]]*?)\])?/s", "$1<label class='eventscategory-$fieldName' for='eventscategory-$fieldName' style=\"{$fieldsLocale[$fieldName]['label-style']}\">{$fieldsLocale[$fieldName]['label-text']} </label><input id='eventscategory-$fieldName' name='eventscategory-$fieldName' style='{$fieldsLocale[$fieldName]['input-style']}' type='" . ($fieldsLocale[$fieldName]['input-type'] ? $fieldsLocale[$fieldName]['input-type'] : 'text') . "' class='$fieldName' value=\"" . htmlspecialchars($field) . "\" />$2", $format);
				//}
			}
			$format = join("<br />\n", preg_split("/[\n\r]+/", trim($format)));
			if($format){
				echo "<div id='eventscategory_location'>$format</div>";
			}
			
			#echo $eventscategory_default_main_address_format;
			?>
		</div>
	</div>
</fieldset>