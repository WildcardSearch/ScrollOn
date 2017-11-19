<?php
/*
 * Plugin Name: ScrollOn for MyBB 1.8.x
 * Copyright 2014 WildcardSearch
 * http://www.rantcentralforums.com
 *
 * the forum-side routines start here
 */

// only add the necessary hooks and templates
scrollon_initialize();

/*
 * scrollon_showthread_end()
 *
 * check settings and display ScrollOn if applicable
 *
 * @return: n/a
 */
function scrollon_showthread_end()
{
	global $fid, $tid;

	if (!scrollon_permissions_check($fid, $tid)) {
		// this forum cannot use ScrollOn per settings
		return;
	}

	global $page, $pages, $mybb, $templates, $db, $lang;
	global $pids, $headerinclude, $postcounter, $scrollon;

	if (!$lang->scrollon) {
		$lang->load('scrollon');
	}

	// grab the list of pids displayed on this page
	$pid_list = str_replace("'", '', substr($pids, 8, strlen($pids) - 9));

	// grab the last pid from the list
	$pid_array = explode(",", $pid_list);
	$pid = (int) $pid_array[count($pid_array) - 1];

	// and then grab its dateline
	$query = $db->simple_select('posts', 'dateline', "pid='{$pid}'", array("limit" => 1));
	if ($db->num_rows($query) == 0) {
		// something went wrong
		return;
	}
	$dateline = (int) $db->fetch_field($query, 'dateline');

	// get the posts per page setting from somewhere
	$ppp = 20;
	$default_ppp = (int) $mybb->settings['postsperpage'];
	if ((int) $mybb->settings['scrollon_posts_per'] > 0) {
		$ppp = (int) $mybb->settings['scrollon_posts_per'];
	} elseif((int) $mybb->settings['postsperpage'] > 0) {
		$ppp = $default_ppp;
	}

	$auto = 'false';
	if ($mybb->settings['scrollon_auto']) {
		$auto = 'true';
	}
	$live = 'false';
	if ($mybb->settings['scrollon_live'] &&
		(int) $mybb->settings['scrollon_refresh_rate'] > 0) {
		$live = 'true';
	}

	$refresh_rate = 30;
	if ((int) $mybb->settings['scrollon_refresh_rate'] > 0) {
		$refresh_rate = (int) $mybb->settings['scrollon_refresh_rate'];
	}

	$refresh_decay = 1.1;
	if ($mybb->settings['scrollon_refresh_decay'] >= 1) {
		$refresh_decay = (float) $mybb->settings['scrollon_refresh_decay'];
	}

	// show no posts if we are at the end of the thread
	$no_post_style = ' style="display: none;"';
	if (count($pid_array) < $ppp ||
		$page == $pages) {
		$no_post_style = $show_more = '';
	} else {
		// this link will be used if auto is off but will be removed by JS otherwise
		$next_page = get_thread_link($tid, $page + 1);
		eval("\$show_more = \"" . $templates->get('scrollon_show_link') . "\";");
	}
	eval("\$scrollon = \"" . $templates->get('scrollon') . "\";");

	// set up the client-side
	$headerinclude .= <<<EOF
	<script type="text/javascript" src="jscripts/scrollon_thread.js?version=100"></script>
	<script type="text/javascript">
	<!--
		threadScroller.setup({
			tid: {$tid},
			fid: {$fid},
			lastPid: {$pid},
			lastPostDate: {$dateline},
			postCounter: {$postcounter},
			defaultPostsPer: {$default_ppp},
			postsPer: {$ppp},
			auto: {$auto},
			live: {$live},
			refreshTime: {$refresh_rate},
			refreshDecay: {$refresh_decay}
		}, {
			showMore: '{$lang->scrollon_more}'
		});
	// -->
	</script>
EOF;
}

/*
 * scrollon_xmlhttp()
 *
 * the AJAX post loading server-side functionality
 *
 * @return: n/a
 */
function scrollon_xmlhttp()
{
	global $mybb;

	if ($mybb->input['action'] != 'scrollon' ||
		!$mybb->input['tid']) {
		return;
	}

	global $db, $postcounter, $fid, $forum, $forumpermissions;
	global $ismod, $thread, $ignored_users, $mybb;

	$fid = (int) $mybb->input['fid'];
	$tid = (int) $mybb->input['tid'];
	$last_post_date = (int) $mybb->input['lastPostDate'];
	$postcounter = (int) $mybb->input['postCounter'];

	$ismod = is_moderator($fid);
	$visible = " AND visible='1'";
	if ($ismod) {
		$visible = " AND (visible='0' OR visible='1')";
	}

	if ((int) $mybb->settings['scrollon_posts_per'] > 0) {
		$ppp = (int) $mybb->settings['scrollon_posts_per'];
	} elseif((int) $mybb->settings['postsperpage'] > 0) {
		$ppp = (int) $mybb->settings['postsperpage'];
	} else {
		$ppp = 20;
	}

	// get the posts made since last check (or since page load)
	$query_where = "tid='{$tid}' AND dateline > {$last_post_date}{$visible}";
	$query = $db->simple_select('posts', 'pid', $query_where, array("order_by" => 'dateline', "order_dir" => 'ASC', "limit" => $ppp));
	if ($db->num_rows($query) == 0) {
		// no posts, just exit with no output to trigger EOT for client-side
		exit;
	}

	require_once MYBB_ROOT . 'inc/functions_post.php';

	// get the pid list and build the SQL WHERE
	$sep = $pids = '';
	while ($pid = $db->fetch_field($query, 'pid')) {
		$pids .= "{$sep}{$pid}";
		$sep = ',';
	}
	$where = "pid IN({$pids})";

	// now actually query for the posts
	$posts = '';
	$query = $db->query("
		SELECT u.*, u.username AS userusername, p.*, f.*, eu.username AS editusername
		FROM {$db->table_prefix}posts p
		LEFT JOIN {$db->table_prefix}users u ON (u.uid=p.uid)
		LEFT JOIN {$db->table_prefix}userfields f ON (f.ufid=u.uid)
		LEFT JOIN {$db->table_prefix}users eu ON (eu.uid=p.edituid)
		WHERE {$where}
		ORDER BY p.dateline
	");

	// to build the posts, we'll need a few more things
	$forum = get_forum($fid);
	$forumpermissions = forum_permissions($fid);
	$thread = get_thread($tid);
	$ignored_users = array();
	if ($mybb->user['uid'] > 0 &&
		$mybb->user['ignorelist'] != "") {
		$ignore_list = explode(',', $mybb->user['ignorelist']);
		foreach ($ignore_list as $uid) {
			$ignored_users[$uid] = 1;
		}
	}

	// build the posts
	while ($post = $db->fetch_array($query)) {
		if ($thread['firstpost'] == $post['pid'] &&
			$thread['visible'] == 0) {
			$post['visible'] = 0;
		}
		$posts .= build_postbit($post);
		$last_dateline = (int) $post['dateline'];
		$post = '';
	}

	$info = json_encode(array(
		"lastPostDate" => $last_dateline,
		"posts" => $posts,
		"postCounter" => $postcounter,
		"pids" => $pids
	));

	// send all the info back to the client
	header('Content-type: application/json');
	echo($info);
	exit;
}

/*
 * scrollon_initialize()
 *
 * add the appropriate hooks and caches any templates that will be used
 *
 * @return: n/a
 */
function scrollon_initialize()
{
	global $mybb, $plugins, $templatelist;

	switch (THIS_SCRIPT) {
	case 'showthread.php':
		$plugins->add_hook('showthread_end', 'scrollon_showthread_end');
		$templatelist .= ',scrollon';
		break;
	case 'xmlhttp.php':
		if ($mybb->input['action'] == 'scrollon') {
			$plugins->add_hook('xmlhttp', 'scrollon_xmlhttp');
		}
		break;
	}
}

/*
 * scrollon_permissions_check()
 *
 * allow/disallow usage based on admin settings
 *
 * @param - $fid - (int) the forum id
 * @param - $tid - (int) the thread id
 * @return: (bool) true if allowed, false if not
 */
function scrollon_permissions_check($fid, $tid)
{
	global $mybb;

	foreach (array("fid" => 'forum', "tid" => 'thread') as $id => $item) {
		foreach (array('allow', 'deny') as $key) {
			$variable = "{$item}_{$key}_list";
			$$variable = (array) explode(',', trim($mybb->settings["scrollon_{$variable}"]));

			if (empty($$variable) ||
				(int) ${$variable}[0] <= 0) {
				continue;
			}

			if ($variable == 'thread_allow_list') {
				if (!in_array($$id, $$variable)) {
					// get out
					return false;
				}
				return true;
			}

			$condition = in_array($$id, $$variable);
			if ($key == 'allow') {
				$condition = !$condition;
			}

			if ($condition) {
				return false;
			}
		}
	}
	return true;
}

?>
