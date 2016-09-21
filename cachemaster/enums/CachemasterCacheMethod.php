<?php
namespace Craft;

/**
 * CachemasterCacheMethod
 *
 * An abstract class that defines all of the cache methods that are available in Cachemaster.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author    Michael Rog <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.cachemaster
 * @since     1.0
 */
abstract class CachemasterCacheMethod extends BaseEnum
{
	// Constants
	// =========================================================================

	const APC          = 'apc';
	const Db           = 'db';
	const Dummy        = 'dummy';
	const EAccelerator = 'eaccelerator';
	const File         = 'file';
	const MemCache     = 'memcache';
	const Redis        = 'redis';
	const WinCache     = 'wincache';
	const XCache       = 'xcache';
	const ZendData     = 'zenddata';

	const CachemasterStatic = 'static';
}
