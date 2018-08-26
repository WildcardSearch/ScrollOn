<?php
/*
 * Plugin Name: ScrollOn for MyBB 1.8.x
 * Copyright 2014 WildcardSearch
 * http://www.rantcentralforums.com
 *
 * This file contains the install functions
 */

// disallow direct access to this file for security reasons
if (!defined('IN_MYBB')) {
	die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

/**
 * information about the plugin used by MyBB for display as well as to connect with updates
 *
 * @return array the plugin info
 */
function scrollon_info()
{
	global $mybb, $lang, $cp_style;

	if (!$lang->scrollon) {
		$lang->load('scrollon');
	}

	$$extraLinks = '<br />';
	$settingsLink = scrollonBuildSettingsLink();
	if ($settingsLink) {
		$$extraLinks = <<<EOF
<ul>
	<li style="list-style-image: url(styles/{$cp_style}/images/scrollon/settings.gif)">
		{$settingsLink}
	</li>
</ul>
EOF;

		$buttonPic = "styles/{$cp_style}/images/scrollon/donate.gif";
		$borderPic = "styles/{$cp_style}/images/scrollon/pixel.gif";
		$scrollonDescription = <<<EOF
<table width="100%">
	<tbody>
		<tr>
			<td>
				{$lang->scrollon_description}{$$extraLinks}
			</td>
			<td style="text-align: center;">
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="VA5RFLBUC4XM4">
					<input type="image" src="{$buttonPic}" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
					<img alt="" border="0" src="{$borderPic}" width="1" height="1">
				</form>
			</td>
		</tr>
	</tbody>
</table>
EOF;
	} else {
		$scrollonDescription = $lang->scrollon_description;
	}

	$name = <<<EOF
<span style="font-familiy: arial; font-size: 1.5em; color: #4AABFF; text-shadow: 2px 2px 2px #4AABFF;">{$lang->scrollon}</span>
EOF;
	$author = <<<EOF
</a></small></i><a href="http://www.rantcentralforums.com" title="Rant Central"><span style="font-family: Courier New; font-weight: bold; font-size: 1.2em; color: #0e7109;">Wildcard</span></a><i><small><a>
EOF;

	// This array returns information about the plugin, some of which was prefabricated above based on whether the plugin has been installed or not.
	return array(
		'name' => $name,
		'description' => $scrollonDescription,
		'website' => 'https://github.com/WildcardSearch/ScrollOn',
		'author' => $author,
		'authorsite' => 'http://www.rantcentralforums.com',
		'version' => SCROLLON_VERSION,
		'compatibility' => '18*',
		'guid' => '',
	);
}

/**
 * check to see if the plugin's settings group is installed-- assume the plugin is installed if so
 *
 * @return bool true if installed, false if not
 */
function scrollon_is_installed()
{
	return scrollonGetSettingsGroup();
}

/**
 * install the settings, templategroup, templates and stylesheet
 *
 * @return void
 */
function scrollon_install()
{
	global $lang;

	if (!$lang->scrollon) {
		$lang->load('scrollon');
	}

	ScrollOnInstaller::getInstance()->install();
}

/**
 * make the template edits and check for upgrades
 *
 * @return void
 */
function scrollon_activate()
{
	require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';
	find_replace_templatesets('showthread', '#' . preg_quote('{$header}') . '#i', '{$header}{$scrollonTop}');
	find_replace_templatesets('showthread', '#' . preg_quote('{$quickreply}') . '#i', '{$scrollonBottom}{$quickreply}');

	// if we just upgraded . . .
	$oldVersion = scrollonGetCacheVersion();
	$info = scrollon_info();
	if (version_compare($oldVersion, $info['version'], '<')) {
		global $lang;
		if (!$lang->scrollon) {
			$lang->load('scrollon');
		}

		ScrollOnInstaller::getInstance()->install();
	}
	scrollonSetCacheVersion();
}

/**
 * restore the templates edited by this plugin
 *
 * @return void
 */
function scrollon_deactivate()
{
	require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';
	find_replace_templatesets('showthread', '#' . preg_quote('{$scrollonTop}') . '#i', '');
	find_replace_templatesets('showthread', '#' . preg_quote('{$scrollonBottom}') . '#i', '');
}

/**
 * remove the settings, templategroup, templates and stylesheet
 *
 * @return void
 */
function scrollon_uninstall()
{
	ScrollOnInstaller::getInstance()->uninstall();

	// delete our cached version
	scrollonUnsetCacheVersion();
}

/*
 * settings
 */

/**
 * retrieves the plugin's settings group gid if it exists
 * attempts to cache repeat calls
 *
 * @return int the setting groups id
 */
function scrollonGetSettingsGroup()
{
	static $gid = null;

	// if we have already stored the value
	if ($gid === null) {
		global $db;

		// otherwise we will have to query the db
		$query = $db->simple_select('settinggroups', 'gid', "name='scrollon_settings'");
		$gid = (int) $db->fetch_field($query, 'gid');
	}
	return $gid;
}

/**
 * builds the url to modify plugin settings if given valid info
 *
 * @param  in settings group id
 * @return string the URL to view the setting group
 */
function scrollonBuildSettingsUrl($gid)
{
	if (!$gid) {
		return;
	}
	return 'index.php?module=config-settings&amp;action=change&amp;gid=' . $gid;
}

/**
 * builds a link to modify plugin settings if it exists
 *
 * @return string plugin settings link HTML
 */
function scrollonBuildSettingsLink()
{
	global $lang;

	if (!$lang->scrollon) {
		$lang->load('scrollon');
	}

	$gid = scrollonGetSettingsGroup();

	// does the group exist?
	if ($gid) {
		// if so build the URL
		$url = scrollonBuildSettingsUrl($gid);

		// did we get a URL?
		if ($url) {
			// if so build the link
			return "<a href=\"{$url}\" title=\"{$lang->scrollon_plugin_settings}\">{$lang->scrollon_plugin_settings}</a>";
		}
	}
	return false;
}

/* versioning */

/**
 * check cached version info
 *
 * @return: (int) the currently installed version
 */
function scrollonGetCacheVersion()
{
	global $cache;

	// get currently installed version, if there is one
	$scrollon = $cache->read('scrollon');
	if ($scrollon['version']) {
        return $scrollon['version'];
	}
    return 0;
}

/*
 * set cached version info
 *
 * @return bool true on success
 */
function scrollonSetCacheVersion()
{
	global $cache;

	// update version cache to latest
	$scrollon = $cache->read('scrollon');
	$scrollon['version'] = SCROLLON_VERSION;
	$cache->update('scrollon', $scrollon);
    return true;
}

/**
 * remove cached version info
 *
 * @return bool true on success
 */
function scrollonUnsetCacheVersion()
{
	global $cache;

	$cache->update('scrollon', null);
    return true;
}

?>
