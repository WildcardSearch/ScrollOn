<?php
/**
 * Wildcard Helper Classes - Plugin Installer
 * plugin specific extension
 */

class ScrollOnInstaller extends WildcardPluginInstaller010301
{
	static public function getInstance()
	{
		static $instance;

		if (!isset($instance)) {
			$instance = new ScrollOnInstaller(MYBB_ROOT . 'inc/plugins/scrollon/install_data.php');
		}
		return $instance;
	}
}

?>
