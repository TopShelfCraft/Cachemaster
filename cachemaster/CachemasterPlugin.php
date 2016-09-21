<?php
namespace Craft;

/**
 * CachemasterPlugin
 *
 * @author    Michael Rog <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.cachemaster
 * @since     1.0
 */
class CachemasterPlugin extends BasePlugin
{

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'CacheMaster';
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return 'Powerful advanced CraftCMS caching, mastered.';
	}


	/**
	 * Return the plugin developer's name
	 *
	 * @return string
	 */
	public function getDeveloper()
	{
		return 'Top Shelf Craft (Michael Rog)';
	}


	/**
	 * Return the plugin developer's URL
	 *
	 * @return string
	 */
	public function getDeveloperUrl()
	{
		return 'https://topshelfcraft.com';
	}


	/**
	 * Return the plugin's Documentation URL
	 *
	 * @return string
	 */
	public function getDocumentationUrl()
	{
		return false;
	}


	/**
	 * Return the plugin's current version
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return '0.1.0';
	}


	/**
	 * Return the plugin's db schema version
	 *
	 * @return string|null
	 */
	public function getSchemaVersion()
	{
		return '0.1.0.0';
	}


	/**
	 * Return the plugin's Release Feed URL
	 *
	 * @return string
	 */
	public function getReleaseFeedUrl()
	{
		return false;
	}


	/**
	 * @return bool
	 */
	public function hasCpSection()
	{
		return false;
	}


	/**
	 * Make sure requirements are met before installation.
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function onBeforeInstall()
	{
		return true;
	}


	/**
	 * @return CachemasterTwigExtension
	 * @throws \Exception
	 */
	public function addTwigExtension()
	{
		if (!craft()->cachemaster->getIsEnabled())
		{
			return;
		}
		return new CachemasterTwigExtension();
	}


	/**
	 * @throws \Exception
	 */
	public function init()
	{

		parent::init();

		// Skip all this stuff if CacheMaster isn't enabled for this request

		if (!craft()->cachemaster->getIsEnabled())
		{
			return;
		}

		Craft::import('plugins.cachemaster.twigextensions.*');
		Craft::import('plugins.cachemaster.etc.cache.*');
		Craft::import('plugins.cachemaster.CachemasterStaticHandler');

		// Output caching
		craft()->cachemaster_outputCache->onCachemasterInit();

		Craft::app()->onEndRequest = function(\CEvent $event) {
			craft()->cachemaster_outputCache->onEndRequest();
		};

	}


	/**
	 * @param string $msg
	 * @param string $level
	 * @param bool $force
	 *
	 * @return mixed
	 */
	public static function log($msg, $level = LogLevel::Profile, $force = false)
	{
		if (is_string($msg)) $msg = "\n" . $msg . "\n\n";
		else $msg = "\n" . print_r($msg, true) . "\n\n";
		Craft::log($msg, $level, $force, 'CacheMaster', 'cachemaster');
	}

}
