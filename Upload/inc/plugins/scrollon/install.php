<?php
/*
 * Plugin Name: ScrollOn for MyBB 1.8.x
 * Copyright 2014 WildcardSearch
 * http://www.rantcentralforums.com
 *
 * This file contains the install functions
 */

// disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

/*
 * scrollon_info()
 *
 * information about the plugin used by MyBB for display as well as to connect with updates
 *
 * @return: (array) the plugin info
 */
function scrollon_info()
{
	global $mybb, $lang, $cp_style;

	if(!$lang->scrollon)
	{
		$lang->load('scrollon');
	}

	$extra_links = "<br />";
	$settings_link = scrollon_build_settings_link();
	if($settings_link)
	{
		$extra_links = <<<EOF
<ul>
	<li style="list-style-image: url(styles/{$cp_style}/images/scrollon/settings.gif)">
		{$settings_link}
	</li>
</ul>
EOF;

		$button_pic = "styles/{$cp_style}/images/scrollon/donate.gif";
		$border_pic = "styles/{$cp_style}/images/scrollon/pixel.gif";
		$scrollon_description = <<<EOF
<table width="100%">
	<tbody>
		<tr>
			<td>
				{$lang->scrollon_description}{$extra_links}
			</td>
			<td style="text-align: center;">
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="VA5RFLBUC4XM4">
					<input type="image" src="{$button_pic}" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
					<img alt="" border="0" src="{$border_pic}" width="1" height="1">
				</form>
			</td>
		</tr>
	</tbody>
</table>
EOF;
	} else {
		$scrollon_description = $lang->scrollon_description;
	}

	$name = <<<EOF
<span style="font-familiy: arial; font-size: 1.5em; color: #4AABFF; text-shadow: 2px 2px 2px #4AABFF;">{$lang->scrollon}</span>
EOF;
	$author = <<<EOF
</a></small></i><a href="http://www.rantcentralforums.com" title="Rant Central"><span style="font-family: Courier New; font-weight: bold; font-size: 1.2em; color: #0e7109;">Wildcard</span></a><i><small><a>
EOF;

	// This array returns information about the plugin, some of which was prefabricated above based on whether the plugin has been installed or not.
	return array(
		"name" => $name,
		"description" => $scrollon_description,
		"website" => 'https://github.com/WildcardSearch/ScrollOn',
		"author" => $author,
		"authorsite" => 'http://www.rantcentralforums.com',
		"version" => '0.0.3',
		"compatibility" => '18*',
		"guid" => '',
	);
}

/*
 * scrollon_is_installed()
 *
 * check to see if the plugin's settings group is installed-- assume the plugin is installed if so
 *
 * @return: (bool) true if installed, false if not
 */
function scrollon_is_installed()
{
	return scrollon_get_settingsgroup();
}

/*
 * scrollon_install()
 *
 * install the settings, templategroup, templates and stylesheet
 *
 * @return: n/a
 */
function scrollon_install()
{
	global $lang;

	if(!$lang->scrollon)
	{
		$lang->load('scrollon');
	}

	ScrollOnInstaller::getInstance()->install();
}

/*
 * scrollon_activate()
 *
 * make the template edits and check for upgrades
 *
 * @return: n/a
 */
function scrollon_activate()
{
	require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';
	find_replace_templatesets('showthread', "#" . preg_quote('{$quickreply}') . "#i", '{$scrollon}{$quickreply}');

	// if we just upgraded . . .
	$old_version = scrollon_get_cache_version();
	$info = scrollon_info();
	if(version_compare($old_version, $info['version'], '<'))
	{
		global $lang;
		if(!$lang->scrollon)
		{
			$lang->load('scrollon');
		}

		ScrollOnInstaller::getInstance()->install();
	}
	scrollon_set_cache_version();
}

/*
 * scrollon_deactivate()
 *
 * restore the templates edited by this plugin
 *
 * @return: n/a
 */
function scrollon_deactivate()
{
	require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';
	find_replace_templatesets('showthread', "#" . preg_quote('{$scrollon}') . "#i", '');
}

/*
 * scrollon_uninstall()
 *
 * remove the settings, templategroup, templates and stylesheet
 *
 * @return: n/a
 */
function scrollon_uninstall()
{
	ScrollOnInstaller::getInstance()->uninstall();

	// delete our cached version
	scrollon_unset_cache_version();
}

/*
 * settings
 */

/*
 * scrollon_get_settingsgroup()
 *
 * retrieves the plugin's settings group gid if it exists
 * attempts to cache repeat calls
 *
 * @return: (int) the setting groups id
 */
function scrollon_get_settingsgroup()
{
	static $scrollon_settings_gid;

	// if we have already stored the value
	if(isset($scrollon_settings_gid))
	{
		// don't waste a query
		$gid = (int) $scrollon_settings_gid;
	}
	else
	{
		global $db;

		// otherwise we will have to query the db
		$query = $db->simple_select("settinggroups", "gid", "name='scrollon_settings'");
		$gid = (int) $db->fetch_field($query, 'gid');
	}
	return $gid;
}

/*
 * scrollon_build_settings_url()
 *
 * builds the url to modify plugin settings if given valid info
 *
 * @param - $gid is an integer representing a valid settings group id
 * @return: (string) the URL to view the setting group
 */
function scrollon_build_settings_url($gid)
{
	if($gid)
	{
		return "index.php?module=config-settings&amp;action=change&amp;gid=" . $gid;
	}
}

/*
 * scrollon_build_settings_link()
 *
 * builds a link to modify plugin settings if it exists
 *
 * @return: (string) an HTML anchor element pointing to the plugin settings
 */
function scrollon_build_settings_link()
{
	global $lang;

	if(!$lang->scrollon)
	{
		$lang->load('scrollon');
	}

	$gid = scrollon_get_settingsgroup();

	// does the group exist?
	if($gid)
	{
		// if so build the URL
		$url = scrollon_build_settings_url($gid);

		// did we get a URL?
		if($url)
		{
			// if so build the link
			return "<a href=\"{$url}\" title=\"{$lang->scrollon_plugin_settings}\">{$lang->scrollon_plugin_settings}</a>";
		}
	}
	return false;
}

/* versioning */

/*
 * scrollon_get_cache_version()
 *
 * check cached version info
 * derived from the work of pavemen in MyBB Publisher
 *
 * @return: (int) the currently installed version
 */
function scrollon_get_cache_version()
{
	global $cache;

	// get currently installed version, if there is one
	$scrollon = $cache->read('scrollon');
	if($scrollon['version'])
	{
        return $scrollon['version'];
	}
    return 0;
}

/*
 * scrollon_set_cache_version()
 *
 * set cached version info
 * derived from the work of pavemen in MyBB Publisher
 *
 * @return: (bool) true on success
 */
function scrollon_set_cache_version()
{
	global $cache;

	// get version from this plugin file
	$scrollon_info = scrollon_info();

	// update version cache to latest
	$scrollon = $cache->read('scrollon');
	$scrollon['version'] = $scrollon_info['version'];
	$cache->update('scrollon', $scrollon);
    return true;
}

/*
 * scrollon_unset_cache_version()
 *
 * remove cached version info
 * derived from the work of pavemen in MyBB Publisher
 *
 * @return: (bool) true on success
 */
function scrollon_unset_cache_version()
{
	global $cache;

	$scrollon = $cache->read('scrollon');
	$scrollon = null;
	$cache->update('scrollon', $scrollon);
    return true;
}

?>
