<?php
/**
 * Events Category admin interface
 */


/**
 * Add Events Category options page to menu
 */
function eventscategory_action_admin_menu() {
	add_options_page(
		__('Events Category Options', EVENTSCATEGORY_TEXT_DOMAIN),
		__('Events Category', EVENTSCATEGORY_TEXT_DOMAIN),
		10, //admin
		'events-category-options',
		'eventscategory_admin_options'
	);
}


/**
 * Register Events Category settings
 */
function eventscategory_register_settings(){
	register_setting( 'eventscategory-group', 'eventscategory_gcal_feed_url' );
}


if(is_admin()){
	add_action('admin_menu', 'eventscategory_action_admin_menu');
	add_action('admin_init', 'eventscategory_register_settings' );
}

/**
 * Events Category options page
 */
function eventscategory_admin_options() {
	//$page_options = array(
	//	'eventscategory_gcal_feed_url'
	//);
	//register_setting( 'my_options_group', 'my_option_name', 'intval' );
	?>
	<div class="wrap">
		<h2><?php _e(__('Events Category Options', EVENTSCATEGORY_TEXT_DOMAIN)) ?></h2>
		<form method="post" action="options.php">
			<?php settings_fields( 'eventscategory-group' ); ?>
			<input type="hidden" name="action" value="update" />
<?php _e(<<<ECTEXT
			<p>Please ensure that you have
			<a href="options-general.php#timezone_string" title="General Settings &gt; Timezone">set your timezone</a>
			so that the event times and event timezone will be shown properly.
			Specifically, try to select an appropriate timezone string (e.g. <kbd>Los Angeles</kbd>) instead of a
			timzezone hour offset (e.g. <kbd>UTC -7:00</kbd>) so that any daylight savings time will be automatically
			accounted for (this timezone string will be provided as the <code>ctz</code>
			<a href="http://code.google.com/apis/calendar/docs/2.0/reference.html" target="_blank" title="Google Calendar API Reference Guide">parameter</a> to the Google Calendar XML Feed).</p>
ECTEXT
, EVENTSCATEGORY_TEXT_DOMAIN); ?>
			
			<?php if ( !wp_timezone_supported() ): ?>
<?php _e(<<<ECTEXT
			<p><strong>Notice:</strong> Your system does not have "magic timezone support", and because of this you cannot select
			a timezone string instead of a timezone offset. You must manually
			indicate your timezone string in your theme's <code>functions.php</code> for example as follows (see
			<a href="http://us.php.net/manual/en/timezones.php" target="_blank">list of supported timezones</a>):</p>
<pre><code class='php'>if(function_exists('date_default_timezone_set'))
	date_default_timezone_set('America/Los_Angeles');
else
	putenv("TZ=America/Los_Angeles")
</code></pre>
ECTEXT
, EVENTSCATEGORY_TEXT_DOMAIN); ?>
			<?php endif; ?>
			
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="eventscategory_gcal_feed_url"><?php _e("Google Calendar XML Feed URL", EVENTSCATEGORY_TEXT_DOMAIN) ?></label></th>
					<td>
						<input type="url" id="eventscategory_gcal_feed_url" name="eventscategory_gcal_feed_url" size="100" value="<?php echo esc_attr(get_option('eventscategory_gcal_feed_url')); ?>" /><br />
						<span class="description">
<?php _e(<<<ECTEXT
							Make sure this feed URL has a <strong><code>full</code></strong>
							<a href="http://code.google.com/apis/calendar/docs/2.0/reference.html#Projection" target="_blank" title="Google Calendar API Reference Guide">projection value</a>
							and not a <code>basic</code> one, for example:
							<code>http://www.google.com/calendar/feeds/jsmith%40example.com/public/full</code>.
							<a href="http://www.google.com/support/calendar/bin/answer.py?answer=37648" target="_blank" title="View from other applications @ Google Calendar Help">Where do I find the URL?</a>
							Replace "basic" with "full".
ECTEXT
, EVENTSCATEGORY_TEXT_DOMAIN); ?>
						</span>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
		</form>
	</div>
	<?php
}
