<?php
/*
 * Plugin Name: ScrollOn for MyBB 1.8.x
 * Copyright 2014 WildcardSearch
 * http://www.rantcentralforums.com
 *
 * this file contains language for the ACP pages
 */

// plugin info
$l['scrollon'] = 'ScrollOn';
$l['scrollon_description'] = 'Allows users to load new posts without loading another page.';

// settings and descriptions
$l['scrollon_plugin_settings'] = "Plugin Settings";
$l['scrollon_settingsgroup_description'] = "various options for magic";

$l['scrollon_posts_per_title'] = 'Posts Per Refresh';
$l['scrollon_posts_per_desc'] = 'maximum number of posts to load on each update? (leave blank to use the default/user ppp settings)';

$l['scrollon_auto_title'] = 'Automatic Load';
$l['scrollon_auto_desc'] = 'YES to automatically load new posts when the user reaches the end of the page, NO (default) to wait for user input';

$l['scrollon_live_title'] = 'Live Mode';
$l['scrollon_live_desc'] = 'YES to keep looking for new posts after the end of the page has been reached (controlled by Refresh Rate)';

$l['scrollon_refresh_rate_title'] = 'Refresh Rate';
$l['scrollon_refresh_rate_desc'] = 'time in seconds between post checks (only effective if Live mode is set to YES)';

$l['scrollon_refresh_decay_title'] = 'Refresh Decay';
$l['scrollon_refresh_decay_desc'] = 'factor to multiply current rate by in seconds to increase time between post checks when none were found (only effective if Live mode is set to YES)';

$l['scrollon_thread_allow_list_title'] = 'Thread Allow List';
$l['scrollon_thread_allow_list_desc'] = '(optional) add a thread id or a comma-separated list of tids to be <strong>exclusively</strong> allowed to use ScrollOn (if the thread is not in the list, it will not be able to use ScrollOn)';

$l['scrollon_forum_allow_list_title'] = 'Forum Allow List';
$l['scrollon_forum_allow_list_desc'] = '(optional) add a forum id or a comma-separated list of fids to be <strong>exclusively</strong> allowed to use ScrollOn (if the forum is not in the list, it will not be able to use ScrollOn)';

$l['scrollon_forum_deny_list_title'] = 'Forum Deny List';
$l['scrollon_forum_deny_list_desc'] = '(optional) add a forum id or a comma-separated list of fids to be <strong>excluded</strong> from using ScrollOn';

$l['scrollon_thread_deny_list_title'] = 'Thread Deny List';
$l['scrollon_thread_deny_list_desc'] = '(optional) add a thread id or a comma-separated list of tids to be <strong>excluded</strong> from using ScrollOn';

?>
