<?php
/*
 * Plugin Name: ScrollOn for MyBB 1.8.x
 * Copyright 2014 WildcardSearch
 * http://www.rantcentralforums.com
 *
 * the forum-side routines start here
 */

// only add the necessary hooks and templates
scrollonInitialize();

/**
 * check settings and display ScrollOn if applicable
 *
 * @return void
 */
function scrollonStart()
{
	global $fid, $tid;

	if (!scrollonPermissionsCheck($fid, $tid)) {
		// this forum cannot use ScrollOn per settings
		return;
	}

	global $page, $pages, $mybb, $templates, $db, $lang;
	global $pids, $headerinclude, $postcounter, $scrollon;

	if (!$lang->scrollon) {
		$lang->load('scrollon');
	}

	// grab the list of pids displayed on this page
	$pidList = str_replace("'", '', substr($pids, 8, strlen($pids) - 9));

	// grab the last pid from the list
	$pidArray = explode(',', $pidList);
	$pid = (int) $pidArray[count($pidArray) - 1];

	// and then grab its dateline
	$query = $db->simple_select('posts', 'dateline', "pid='{$pid}'", array('limit' => 1));
	if ($db->num_rows($query) == 0) {
		// something went wrong
		return;
	}
	$dateline = (int) $db->fetch_field($query, 'dateline');

	// set version code
	$version = SCROLLON_VERSION_CODE;

	// get the posts per page setting from somewhere
	$ppp = 20;
	$defaultPpp = (int) $mybb->settings['postsperpage'];
	if ((int) $mybb->settings['scrollon_posts_per'] > 0) {
		$ppp = (int) $mybb->settings['scrollon_posts_per'];
	} elseif((int) $mybb->settings['postsperpage'] > 0) {
		$ppp = $defaultPpp;
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

	$refreshRate = 30;
	if ((int) $mybb->settings['scrollon_refresh_rate'] > 0) {
		$refreshRate = (int) $mybb->settings['scrollon_refresh_rate'];
	}

	$refreshDecay = 1.1;
	if ($mybb->settings['scrollon_refresh_decay'] >= 1) {
		$refreshDecay = (float) $mybb->settings['scrollon_refresh_decay'];
	}

	// show no posts if we are at the end of the thread
	$noPostStyle = ' style="display: none;"';
	if (count($pidArray) < $ppp ||
		$page == $pages) {
		$noPostStyle = $showMore = '';
	} else {
		// this link will be used if auto is off but will be removed by JS otherwise
		$nextPage = get_thread_link($tid, $page + 1);
		eval("\$showMore = \"{$templates->get('scrollon_show_link')}\";");
	}
	eval("\$scrollon = \"{$templates->get('scrollon')}\";");

	// set up the client-side
	$headerinclude .= <<<EOF
	<script type="text/javascript" src="jscripts/scrollon/thread.js?version={$version}"></script>
	<script type="text/javascript">
	<!--
		threadScroller.setup({
			tid: {$tid},
			fid: {$fid},
			lastPid: {$pid},
			lastPostDate: {$dateline},
			postCounter: {$postcounter},
			defaultPostsPer: {$defaultPpp},
			postsPer: {$ppp},
			auto: {$auto},
			live: {$live},
			refreshTime: {$refreshRate},
			refreshDecay: {$refreshDecay}
		}, {
			showMore: '{$lang->scrollon_more}'
		});
	// -->
	</script>
EOF;
}

/**
 * the AJAX post loading server-side functionality
 *
 * @return void
 */
function scrollonXmlhttp()
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
	$lastPostDate = (int) $mybb->input['lastPostDate'];
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
	$where = "tid='{$tid}' AND dateline > {$lastPostDate}{$visible}";
	$query = $db->simple_select('posts', 'pid', $where, array('order_by' => 'dateline', 'order_dir' => 'ASC', 'limit' => $ppp));
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

	// now actually query for the posts
	$posts = '';
	$query = $db->query("
		SELECT u.*, u.username AS userusername, p.*, f.*, eu.username AS editusername
		FROM {$db->table_prefix}posts p
		LEFT JOIN {$db->table_prefix}users u ON (u.uid=p.uid)
		LEFT JOIN {$db->table_prefix}userfields f ON (f.ufid=u.uid)
		LEFT JOIN {$db->table_prefix}users eu ON (eu.uid=p.edituid)
		WHERE pid IN({$pids})
		ORDER BY p.dateline
	");

	// to build the posts, we'll need a few more things
	$forum = get_forum($fid);
	$forumpermissions = forum_permissions($fid);
	$thread = get_thread($tid);
	$ignored_users = array();
	if ($mybb->user['uid'] > 0 &&
		$mybb->user['ignorelist'] != '') {
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
		$lastPost = $post;
	}

	$info = json_encode(array(
		'lastPostDate' => (int) $lastPost['dateline'],
		'posts' => $posts,
		'postCounter' => $postcounter,
		'pids' => $pids
	));

	// send all the info back to the client
	header('Content-type: application/json');
	echo($info);
	exit;
}

/**
 * add the appropriate hooks and caches any templates that will be used
 *
 * @return void
 */
function scrollonInitialize()
{
	global $mybb, $plugins, $templatelist;

	switch (THIS_SCRIPT) {
	case 'showthread.php':
		$plugins->add_hook('showthread_end', 'scrollonStart');
		$templatelist .= ',scrollon';
		break;
	case 'xmlhttp.php':
		if ($mybb->input['action'] == 'scrollon') {
			$plugins->add_hook('xmlhttp', 'scrollonXmlhttp');
		}
		break;
	}
}

/**
 * allow/disallow usage based on admin settings
 *
 * @param  int the forum id
 * @param  int the thread id
 * @return bool true if allowed, false if not
 */
function scrollonPermissionsCheck($fid, $tid)
{
	global $mybb;

	foreach (array('fid' => 'forum', 'tid' => 'thread') as $id => $item) {
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
