<?php
namespace Craft;

/**
 * CachemasterTwigExtension
 *
 * @author    Michael Rog <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.cachemaster
 * @since     1.0
 */
class CachemasterTwigExtension extends \Twig_Extension
{


	// Public Methods
	// =========================================================================


	/**
	 * Returns the name of the Twig extension.
	 *
	 * @return string The extension name
	 */
	public function getName()
	{
		return 'cachemaster';
	}


	/**
	 * {@inheritdoc}
	 *
	 * @deprecated since 1.23 (to be removed in 2.0), implement Twig_Extension_GlobalsInterface instead
	 */
	public function getGlobals()
	{
		return array(
			'cachemaster' => new CachemasterVariable(),
		);
	}


	/**
	 * {@inheritdoc}
	 */
	public function getTokenParsers()
	{
		return array(
			// new Cachemaster_TokenParser(),
		);
	}


}
