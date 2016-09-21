<?php
namespace Craft;

/**
 * CachemasterVariable
 *
 * @author    Michael Rog <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.cachemaster
 * @since     1.0
 */
class CachemasterVariable
{


	/**
	 * @param array $settings
	 */
	public function cacheOutput($settings = [])
	{
		craft()->cachemaster_outputCache->applySettings($settings);
		craft()->cachemaster_outputCache->activate();
	}

	/**
	 * @param string $dateString
	 */
	public function getDateTime($dateString)
	{

	}


}
