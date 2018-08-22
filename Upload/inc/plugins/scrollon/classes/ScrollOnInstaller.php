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
			$instance = new ScrollOnInstaller();
		}
		return $instance;
	}

	/**
	 * link the installer to our data file
	 *
	 * @param  string path to the install data
	 * @return void
	 */
	public function __construct($path = '')
	{
		parent::__construct(MYBB_ROOT . 'inc/plugins/scrollon/install_data.php');
	}
}

?>
