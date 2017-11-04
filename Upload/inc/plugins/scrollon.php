<?php
/*
 * Plugin Name: ScrollOn for MyBB 1.8.x
 * Copyright 2014 WildcardSearch
 * http://www.rantcentralforums.com
 *
 * the main plugin file; splits forum and ACP scripts to decrease footprint
 */

// disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// register custom class autoloader
spl_autoload_register('scrollOnClassAutoLoad');

// load the install/admin routines only if in ACP.
if(defined("IN_ADMINCP"))
{
    require_once MYBB_ROOT . "inc/plugins/scrollon/install.php";
}
else
{
	require_once MYBB_ROOT . "inc/plugins/scrollon/forum.php";
}

/**
 * class autoloader
 *
 * @param string the name of the class to load
 */
function scrollOnClassAutoLoad($className) {
	$path = MYBB_ROOT . "inc/plugins/scrollon/classes/{$className}.php";

	if (file_exists($path)) {
		require_once $path;
	}
}

?>
