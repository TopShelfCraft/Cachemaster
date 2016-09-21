<?php
namespace Craft;


/**
 * CachemasterCommand
 *
 * @author    Michael Rog <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.cachemaster
 * @since     1.0
 */
class CachemasterCommand extends BaseCommand
{

	// Public Methods
	// =========================================================================

	/**
	 *
	 * This method is invoked right before an action is to be executed.
	 *
	 * @param string $action The name of the action to run.
	 * @param array  $params The parameters to be passed to the action's method.
	 *
	 * @return bool Whether the action should be executed or not.
	 */
	public function beforeAction($action, $params)
	{
		echo "\n\n====== Cachemaster CLI tool... ======";
		echo "\n------ {$action} ... \n\n";
		return true;
	}

	/**
	 *
	 * This method is invoked right after an action is to be executed.
	 *
	 * @param string $action The name of the action to run.
	 * @param array  $params The parameters to be passed to the action's method.
	 *
	 * @return bool Whether the action should be executed or not.
	 */
	public function afterAction($action, $params, $exitCode = 0)
	{
		echo "\n\n------- / {$action} : code {$exitCode}";
		echo "\n================================================\n\n";
		return parent::afterAction($action, $params, $exitCode);
	}


	/**
	 * Warms the cache by fetching a piece of content.
	 *
	 * Accepts a single parameter: the target content to fetch.
	 *
	 * cachemaster warm all
	 * cachemaster warm 1
	 * cachemaster warm [path]
	 *
	 * @param $args
	 *
	 * @return int
	 */
	public function actionWarm($args)
	{

		if (!empty($args[0]) && $args[0] == 'all')
		{
			craft()->cachemaster_warming->warmEntireCache();
		}
		else if (!empty($args[0]))
		{
			craft()->cachemaster_warming->warmContent($args[0]);
		}

		return 0;

	}


	/**
	 * @param string $str
	 */
	private function _l($str = "")
	{
		echo "\n" . $str;
	}

}
