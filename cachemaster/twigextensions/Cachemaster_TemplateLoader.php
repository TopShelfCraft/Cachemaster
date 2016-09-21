<?php
namespace Craft;

/**
 * Cachemaster_TemplateLoader
 *
 * @author    Michael Rog <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.cachemaster
 * @since     1.0
 */
class Cachemaster_TemplateLoader extends TemplateLoader
{
	// Public Methods
	// =========================================================================

	/**
	 * Gets the source code of a template.
	 *
	 * @param  string $name The name of the template to load, or a StringTemplate object.
	 *
	 * @throws Exception
	 * @return string The template source code.
	 */
	public function getSource($name)
	{

		if (is_string($name))
		{
			$template = $this->_findTemplate($name);

			if (IOHelper::isReadable($template))
			{
				$template = IOHelper::getFileContents($template);
			}
			else
			{
				throw new Exception(Craft::t('Tried to read the template at {path}, but could not. Check the permissions.', array('path' => $template)));
			}
		}
		else
		{
			$template = $name->template;
		}

		$source = craft()->cachemaster->preparseSource($template);

		return $source;

	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the path to a given template, or throws a TemplateLoaderException.
	 *
	 * @param $name
	 *
	 * @throws TemplateLoaderException
	 * @return string $name
	 */
	private function _findTemplate($name)
	{
		$template = craft()->templates->findTemplate($name);

		if (!$template)
		{
			throw new TemplateLoaderException($name);
		}

		return $template;
	}

}
