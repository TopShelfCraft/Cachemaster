<?php
namespace Craft;

/**
 * CachemasterCacheType
 *
 * An abstract class that defines all of the cache types that are available in Cachemaster.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author    Michael Rog <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.cachemaster
 * @since     1.0
 */
abstract class CachemasterCacheType extends BaseEnum
{
	// Constants
	// =========================================================================

	const Fragment  = 'fragment';
	const Output    = 'output';
	const System    = 'system';

}
