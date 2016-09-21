<?php
namespace Craft;

/**
 * Cachemaster_CachemasterparseNode
 *
 * @author    Michael Rog <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.cachemaster
 * @since     1.0
 */
class Cachemaster_CachemasterparseNode extends \Twig_Node
{

	// Public Methods
	// =========================================================================

	/**
	 * @param \Twig_Compiler $compiler
	 *
	 * @return null
	 */
	public function compile(\Twig_Compiler $compiler)
	{

		CachemasterPlugin::log('Compiling {% cachemasterparse %}');

		$body = $this->getNode('body')->getAttribute('data');
		CachemasterPlugin::log($body);

		$compiler
			->addDebugInfo($this)
			->write('echo ')
			->string('{% cachemasterparse %}')
			->raw(".")
			->string($body)
			->raw(".")
			->string('{% endcachemasterparse %}')
			->raw(";\n")
		;

	}

}