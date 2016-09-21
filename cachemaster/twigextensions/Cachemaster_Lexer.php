<?php
namespace Craft;

/**
 * Cachemaster_Lexer
 *
 * @author    Michael Rog <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.cachemaster
 * @since     1.0
 */
class Cachemaster_Lexer extends \Twig_Lexer
{

	/**
	 * Cachemaster_Lexer constructor.
	 *
	 * @param \Twig_Environment $env
	 * @param array $options
	 */
	public function __construct(\Twig_Environment $env, array $options = array())
	{

		parent::__construct($env, $options);
		$this->regexes = array_merge(
			$this->regexes,
			array(
				'lex_block_raw' => '/\s*(raw|verbatim|cmlater)\s*(?:'.preg_quote($this->options['whitespace_trim'].$this->options['tag_block'][1], '/').'\s*|\s*'.preg_quote($this->options['tag_block'][1], '/').')/As',
			)
		);

	}

	/**
	 * @param $tag
	 *
	 * @throws Twig_Error_Syntax
	 */
	protected function lexRawData($tag)
	{
		if (!in_array($tag, ['cmlater'])) {
			return parent::lexRawData($tag);
		}

		if (!preg_match(str_replace('%s', $tag, $this->regexes['lex_raw_data']), $this->code, $match, PREG_OFFSET_CAPTURE, $this->cursor)) {
			throw new Twig_Error_Syntax(sprintf('Unexpected end of file: Unclosed "%s" block.', $tag), $this->lineno, $this->filename);
		}

		$text = substr($this->code, $this->cursor, $match[0][1] - $this->cursor);
		$this->moveCursor($text.$match[0][0]);

		if (false !== strpos($match[1][0], $this->options['whitespace_trim'])) {
			$text = rtrim($text);
		}

		$text = "{% cachemasterlater %}" . $text . "{% endcachemasterlater %}";

		$this->pushToken(\Twig_Token::TEXT_TYPE, $text);
	}

}