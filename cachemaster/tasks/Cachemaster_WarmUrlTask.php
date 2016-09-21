<?php
namespace Craft;

/**
 * Cachemaster_WarmUrlTask
 *
 * @author    Michael Rog <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.cachemaster
 * @since     1.0
 */
class Cachemaster_WarmUrlTask extends BaseTask
{

	private $_url;

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'url' => array(AttributeType::String),
		);
	}

	/**
	 * Returns the default description for this task.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return 'Fetching content to warm the cache...';
	}

	/**
	 * Gets the total number of steps for this task.
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		return 1;
	}

	/**
	 * Runs a task step.
	 *
	 * @param int $step
	 * @return bool
	 */
	public function runStep($step)
	{

		CachemasterPlugin::log($this->getDescription() . ' -- ' . $this->getSettings()->url);
		$startTime = time();

		// Do the needful.

		$success = false;

		try
		{
			$success = craft()->cachemaster_warming->fetchUrl($this->getSettings()->url, null, true);
		}
		catch(Exception $e)
		{
			CachemasterPlugin::log("Error during task: " . $e->getMessage(), LogLevel::Error);
			return false;
		}

		// Wrap up.

		$endTime = time();
		$elapsedTime = $endTime - $startTime;
		CachemasterPlugin::log("Task complete. ({$elapsedTime} seconds)");

		return $success;

	}
}
