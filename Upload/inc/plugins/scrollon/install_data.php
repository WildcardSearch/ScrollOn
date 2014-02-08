<?php
/*
 * Plugin Name: ScrollOn for MyBB 1.6.x
 * Copyright 2014 WildcardSearch
 * http://www.rantcentralforums.com
 *
 * this file contains data used by classes/installer.php
 */

$settings = array(
	"scrollon_settings" => array(
		"group" => array(
			"name" => 'scrollon_settings',
			"title" => $lang->scrollon,
			"description" => $lang->scrollon_settingsgroup_description,
			"disporder" => '101',
			"isdefault" => 0
		),
		"settings" => array(
			"scrollon_posts_per" => array(
				"sid" => "NULL",
				"name" => 'scrollon_posts_per',
				"title" => $lang->scrollon_posts_per_title,
				"description" => $lang->scrollon_posts_per_desc,
				"optionscode" => 'text',
				"value" => '',
				"disporder" => '10'
			),
			"scrollon_auto" => array(
				"sid" => "NULL",
				"name" => 'scrollon_auto',
				"title" => $lang->scrollon_auto_title,
				"description" => $lang->scrollon_auto_desc,
				"optionscode" => 'yesno',
				"value" => '0',
				"disporder" => '20'
			),
			"scrollon_live" => array(
				"sid" => "NULL",
				"name" => 'scrollon_live',
				"title" => $lang->scrollon_live_title,
				"description" => $lang->scrollon_live_desc,
				"optionscode" => 'yesno',
				"value" => '0',
				"disporder" => '30'
			),
			"scrollon_refresh_rate" => array(
				"sid" => "NULL",
				"name" => 'scrollon_refresh_rate',
				"title" => $lang->scrollon_refresh_rate_title,
				"description" => $lang->scrollon_refresh_rate_desc,
				"optionscode" => 'text',
				"value" => '30',
				"disporder" => '40'
			),
			"scrollon_refresh_decay" => array(
				"sid" => "NULL",
				"name" => 'scrollon_refresh_decay',
				"title" => $lang->scrollon_refresh_decay_title,
				"description" => $lang->scrollon_refresh_decay_desc,
				"optionscode" => 'text',
				"value" => '1.1',
				"disporder" => '50'
			),
			"scrollon_thread_allow_list" => array(
				"sid" => "NULL",
				"name" => 'scrollon_thread_allow_list',
				"title" => $lang->scrollon_thread_allow_list_title,
				"description" => $lang->scrollon_thread_allow_list_desc,
				"optionscode" => "text",
				"value" => '',
				"disporder" => '60'
			),
			"scrollon_forum_allow_list" => array(
				"sid" => "NULL",
				"name" => 'scrollon_forum_allow_list',
				"title" => $lang->scrollon_forum_allow_list_title,
				"description" => $lang->scrollon_forum_allow_list_desc,
				"optionscode" => "text",
				"value" => '',
				"disporder" => '70'
			),
			"scrollon_thread_deny_list" => array(
				"sid" => "NULL",
				"name" => 'scrollon_thread_deny_list',
				"title" => $lang->scrollon_thread_deny_list_title,
				"description" => $lang->scrollon_thread_deny_list_desc,
				"optionscode" => "text",
				"value" => '',
				"disporder" => '80'
			),
			"scrollon_forum_deny_list" => array(
				"sid" => "NULL",
				"name" => 'scrollon_forum_deny_list',
				"title" => $lang->scrollon_forum_deny_list_title,
				"description" => $lang->scrollon_forum_deny_list_desc,
				"optionscode" => "text",
				"value" => '',
				"disporder" => '90'
			),
		)
	)
);

$templates = array(
	"scrollon" => array(
		"group" => array(
			"prefix" => 'scrollon',
			"title" => $lang->scrollon,
		),
		"templates" => array(
			"scrollon" => <<<EOF
	<div id="scrollon" style="display: none;">
		<span>{\$show_more}</span><span id="scrollon_no_posts"{\$no_post_style}>{\$lang->scrollon_no_new_posts}</span><span id="scrollon_spinner" style="display: none;"><img src="{\$mybb->settings['bburl']}/inc/plugins/scrollon/images/spinner.gif" alt="{\$lang->scrollon_loading}"/></span>
	</div>

EOF
			,
			"scrollon_show_link" => <<<EOF
<a id="scrollon_show_link" href="{\$next_page}">{\$lang->scrollon_more}</a>
EOF
		),
	),
);

$style_sheets = array(
	"scrollon" => array(
		"attachedto" => 'showthread.php',
		"stylesheet" => <<<EOF
#scrollon {
	width: 225px;
	margin: 20px auto;
	border: 2px outset black;
	background: lightgray;
	color: black;
	text-align: center;
	padding: 8px;
	font-family: "Trebuchet MS", "Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", Tahoma, sans-serif;
	font-size: 1.2em;
}

#scrollon a {
	text-decoration: none;
}

#scrollon_spinner {
	float: right;
}

#scrollon_no_posts {
	color: grey;
	font-style: italic;
}

#scrollon_show_link {
	font-weight: bold;
}

EOF
	),
);

?>
