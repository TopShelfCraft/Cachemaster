<?php

/**
 * CacheMaster config
 *
 * @author    Michael Rog <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.cachemaster
 * @since     1.0
 */

return array(

	'defaultDriver' => 'file',
	'enableCpIndex' => true,

	'disableAllCaching' => false,

	'enableOutputCaching' => true,
	'addOutputCacheDebugInfo' => true,

	'staticCachePath' => null,

);
