<?php
/*
 * Plugin Name: ScrollOn for MyBB 1.8.x
 * Copyright 2014 WildcardSearch
 * http://www.rantcentralforums.com
 *
 * this file contains data used by classes/installer.php
 */

$settings = array(
	'scrollon_settings' => array(
		'group' => array(
			'name' => 'scrollon_settings',
			'title' => $lang->scrollon,
			'description' => $lang->scrollon_settingsgroup_description,
			'disporder' => '101',
			'isdefault' => 0
		),
		'settings' => array(
			'scrollon_posts_per' => array(
				'name' => 'scrollon_posts_per',
				'title' => $lang->scrollon_posts_per_title,
				'description' => $lang->scrollon_posts_per_desc,
				'optionscode' => 'text',
				'value' => '',
				'disporder' => '10'
			),
			'scrollon_auto' => array(
				'name' => 'scrollon_auto',
				'title' => $lang->scrollon_auto_title,
				'description' => $lang->scrollon_auto_desc,
				'optionscode' => 'yesno',
				'value' => '0',
				'disporder' => '20'
			),
			'scrollon_live' => array(
				'name' => 'scrollon_live',
				'title' => $lang->scrollon_live_title,
				'description' => $lang->scrollon_live_desc,
				'optionscode' => 'yesno',
				'value' => '0',
				'disporder' => '30'
			),
			'scrollon_refresh_rate' => array(
				'name' => 'scrollon_refresh_rate',
				'title' => $lang->scrollon_refresh_rate_title,
				'description' => $lang->scrollon_refresh_rate_desc,
				'optionscode' => 'text',
				'value' => '30',
				'disporder' => '40'
			),
			'scrollon_refresh_decay' => array(
				'name' => 'scrollon_refresh_decay',
				'title' => $lang->scrollon_refresh_decay_title,
				'description' => $lang->scrollon_refresh_decay_desc,
				'optionscode' => 'text',
				'value' => '1.1',
				'disporder' => '50'
			),
			'scrollon_thread_allow_list' => array(
				'name' => 'scrollon_thread_allow_list',
				'title' => $lang->scrollon_thread_allow_list_title,
				'description' => $lang->scrollon_thread_allow_list_desc,
				'optionscode' => 'text',
				'value' => '',
				'disporder' => '60'
			),
			'scrollon_forum_allow_list' => array(
				'name' => 'scrollon_forum_allow_list',
				'title' => $lang->scrollon_forum_allow_list_title,
				'description' => $lang->scrollon_forum_allow_list_desc,
				'optionscode' => 'text',
				'value' => '',
				'disporder' => '70'
			),
			'scrollon_thread_deny_list' => array(
				'name' => 'scrollon_thread_deny_list',
				'title' => $lang->scrollon_thread_deny_list_title,
				'description' => $lang->scrollon_thread_deny_list_desc,
				'optionscode' => 'text',
				'value' => '',
				'disporder' => '80'
			),
			'scrollon_forum_deny_list' => array(
				'name' => 'scrollon_forum_deny_list',
				'title' => $lang->scrollon_forum_deny_list_title,
				'description' => $lang->scrollon_forum_deny_list_desc,
				'optionscode' => 'text',
				'value' => '',
				'disporder' => '90'
			),
		)
	)
);

$templates = array(
	'scrollon' => array(
		'group' => array(
			'prefix' => 'scrollon',
			'title' => $lang->scrollon,
		),
		'templates' => array(
			'scrollon_top' => <<<EOF
	<div id="scrollonTop" style="display: none;">
		<span>{\$showMore}</span><span id="scrollonSpinnerTop" style="display: none;"><img src="{\$theme['imgdir']}/scrollon/spinner.gif" alt="{\$lang->scrollon_loading}"/></span>
	</div>

EOF
			,
			'scrollon_bottom' => <<<EOF
	<div id="scrollonBottom" style="display: none;">
		<span>{\$showMore}</span><span id="scrollonNoPosts"{\$noPostStyle}>{\$lang->scrollon_no_new_posts}</span><span id="scrollonSpinnerBottom" style="display: none;"><img src="{\$theme['imgdir']}/scrollon/spinner.gif" alt="{\$lang->scrollon_loading}"/></span>
	</div>

EOF
			,
			'scrollon_show_link' => <<<EOF
<a id="{\$linkId}" href="{\$pageLink}">{\$lang->scrollon_more}</a>
EOF
		),
	),
);

$styleSheets = array(
	'folder' => 'scrollon',
	'forum' => array(
		'scrollon' => array(
			'attachedto' => 'showthread.php',
			'stylesheet' => <<<EOF
#scrollonTop, #scrollonBottom {
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

#scrollonTop a, #scrollonBottom a {
	text-decoration: none;
}

#scrollonSpinnerTop, #scrollonSpinnerBottom {
	float: right;
}

#scrollonNoPosts {
	color: grey;
	font-style: italic;
}

#scrollonShowLinkTop, #scrollonShowLinkBottom {
	font-weight: bold;
}

EOF
		),
	),
);

$images = array(
	'folder' => 'scrollon',
	'forum' => array(
		'spinner.gif' => array(
			'image' => <<<EOF
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
EOF
		),
	),
	'acp' => array(
		'donate.gif' => array(
			'image' => <<<EOF
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
EOF
		),
		'pixel.gif' => array(
			'image' => <<<EOF
R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==
EOF
		),
		'settings.gif' => array(
			'image' => <<<EOF
R0lGODlhEAAQAOMLAAAAAAMDAwYGBgoKCg0NDRoaGh0dHUlJSVhYWIeHh5aWlv///////////////////yH5BAEKAA8ALAAAAAAQABAAAARe8Mn5lKJ4nqRMOtmDPBvQAZ+IIQZgtoAxUodsEKcNSqXd2ahdwlWQWVgDV6JiaDYVi4VlSq1Gf87L0GVUsARK3tBm6LAAu4ktUC6yMueYgjubjHrzVJ2WKKdCFBYhEQA7
EOF
		),
	),
);

?>
