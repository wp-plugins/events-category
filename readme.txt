=== Events Category ===
Contributors: westonruter
Tags: events, calendar, Google Calendar, upcoming events
Tested up to: 2.8
Requires at least: 2.8
Stable tag: 0.4

Seamless event calendar solution which imports events from a Google Calendar and
stores them in WordPress as published posts in an "Events" category with publish
dates equal to the event's start time. First page of Events category displays all
future events in ascending order. Second page shows <var>posts_per_page</var>
most recently passed events in descending order: last page of Events category
shows the oldest event. Requires PHP 5.1.

== Description ==

Seamless event calendar solution which imports events from a Google Calendar and
stores them in WordPress as published posts in an "Events" category with publish
dates equal to the event's start time. First page of Events category displays all
future events in ascending order. Second page shows <code>posts_per_page</code>
most recently passed events in descending order: last page of Events category
shows the oldest event. *Requires PHP 5.1!* <em>This plugin is developed at
<a href="http://www.shepherd-interactive.com/" title="Shepherd Interactive specializes in web design and development in Portland, Oregon">Shepherd Interactive</a>
for the benefit of the community.</em>

More information about how the plugin works can be found in the source code of the plugin itself.
See also the "Events Category" options page that is added to the Settings admin menu.

== Changelog ==

= 2009-09-03: 0.5 =
* Complete re-write for WordPress 2.8, integration and dependence on Google Calendar.

= 2008-10-10: 0.4 =
* Large re-write and re-development for WordPress 2.6

= 2008-02-13: 0.1 (beta) =
* <code>is_events_category()</code> now accepts arrays of category IDs or category objects so that <code>is_events_category(get_the_category())</code> can be used in the single.php template.
* Future events posts now no longer appear on the default posts page nor in the main posts feed.
* Event feeds (RSS2 and iCal) are now automatically added to each page.
* Event location in iCal feed has been improved

= 2008-02-12: 0.1 (alpha) =
* Initial version released