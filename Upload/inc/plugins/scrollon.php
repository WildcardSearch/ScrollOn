<?php
/*
 * Plugin Name: ScrollOn for MyBB 1.6.x
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

// load the install/admin routines only if in ACP.
if(defined("IN_ADMINCP"))
{
    require_once MYBB_ROOT . "inc/plugins/scrollon/install.php";
}
else
{
	require_once MYBB_ROOT . "inc/plugins/scrollon/forum.php";
}

?>
