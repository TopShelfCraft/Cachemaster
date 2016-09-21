<?php
namespace Craft;

/**
 * Cachemaster_CachemasterparseTokenParser
 *
 * @author    Michael Rog <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.cachemaster
 * @since     1.0
 */
class Cachemaster_CachemasterparseTokenParser extends \Twig_TokenParser
{

	// Public Methods
	// =========================================================================

	/**
	 * @return string
	 */
	public function getTag()
	{
		return 'cachemasterparse';
	}

	/**
	 * Parses {% cachemasterparse %}...{% endcachemasterparse %} tags.
	 *
	 * @param \Twig_Token $token
	 *
	 * @return Cache_Node
	 */
	public function parse(\Twig_Token $token)
	{

		$lineno = $token->getLine();
		$stream = $this->parser->getStream();

		$nodes = array(
			'body' => null,
		);

		$attributes = array(
		);

		$stream->expect(\Twig_Token::BLOCK_END_TYPE);
		$nodes['body'] = $this->parser->subparse(array($this, 'decideCacheEnd'), true);
		$stream->expect(\Twig_Token::BLOCK_END_TYPE);

		return new Cachemaster_CachemasterparseNode($nodes, $attributes, $lineno, $this->getTag());

	}

	/**
	 * @param \Twig_Token $token
	 *
	 * @return bool
	 */
	public function decideCacheEnd(\Twig_Token $token)
	{
		return $token->test('endcachemasterparse');
	}

}
