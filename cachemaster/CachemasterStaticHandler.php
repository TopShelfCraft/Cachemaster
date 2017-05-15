<?php
namespace Craft;


// Require Craft's IOHelper and HeaderHelper
// using the $craftPath set in index.php if necessary

$D = DIRECTORY_SEPARATOR;
$realpath = realpath($_SERVER['DOCUMENT_ROOT']);
$basePath = rtrim(str_replace('\\', $D, $realpath), $D);

$defaultCraftPath = empty($craftPath) ? '' : rtrim(realpath($basePath . $D . $craftPath), $D);
defined('CACHEMASTER_CRAFT_PATH') || define('CACHEMASTER_CRAFT_PATH', $defaultCraftPath);

$defaultCachemasterPluginPath = defined('CRAFT_PLUGINS_PATH') ? rtrim(CRAFT_PLUGINS_PATH, $D) . $D . 'cachemaster' : $defaultCraftPath . $D . 'plugins' . $D . 'cachemaster';
defined('CACHEMASTER_PLUGIN_PATH') || define('CACHEMASTER_PLUGIN_PATH', $defaultCachemasterPluginPath);

require_once CACHEMASTER_CRAFT_PATH . $D . 'app' . $D . 'helpers' . $D . 'IOHelper.php';
require_once CACHEMASTER_CRAFT_PATH . $D . 'app' . $D . 'helpers' . $D . 'HeaderHelper.php';
require_once CACHEMASTER_PLUGIN_PATH . $D . 'helpers' . $D . 'CachemasterHelper.php';

// TODO: Clean up this ugliness ^^^

// Make sure settings constants are defined

defined('CACHEMASTER_STATIC_DEBUG') || define('CACHEMASTER_STATIC_DEBUG', false);


/**
 * CachemasterStaticHandler
 *
 * @author    Michael Rog <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.cachemaster
 * @since     1.0
 */
class CachemasterStaticHandler
{

	/**
	 * @return string
	 */
	public static function getStaticKey()
	{

		$uri = strtolower(trim($_SERVER['REQUEST_URI'], '/'));
		if (empty($uri)) $uri = 'index';

		$locale = defined('CRAFT_LOCALE') ? CRAFT_LOCALE : '';

		$staticKey = 'static' . ' >>> ' . $locale . ' >>> ' . $uri;

		return $staticKey;

	}

	/**
	 * Get a clean filename from a key
	 *
	 * @param $name
	 *
	 * @return string
	 */
	public static function cleanFilename($name)
	{
		$name = IOHelper::cleanFilename($name, false);
		if (empty($name)) $name = '-';
		return $name;
	}


	public static function tryCache()
	{

		$key = static::getStaticKey();
		$filename = 'Cachemaster__' . static::cleanFilename($key);
		$filename = sha1($filename);

		$D = DIRECTORY_SEPARATOR;

		$cachePath = defined('CACHEMASTER_STATIC_CACHE_PATH') ? rtrim(CACHEMASTER_STATIC_CACHE_PATH, $D) : null;
		if (!$cachePath)
		{

			if (defined('CRAFT_STORAGE_PATH'))
			{
				$cachePath = rtrim(CRAFT_STORAGE_PATH, $D) . $D . 'runtime' . $D . 'cachemaster' . $D . 'static';
			}
			else
			{
				$cachePath = rtrim(CACHEMASTER_CRAFT_PATH, $D) . $D . 'storage' . $D . 'runtime' . $D . 'cachemaster' . $D . 'static';
			}

		}

		if (file_exists($cacheFile = $cachePath . $D . $filename . '.json'))
		{

			$entry = json_decode(file_get_contents($cacheFile), true)[0];

			// Bypass expired entries
			if ($entry['expiryTime'] < time())
			{
				return;
			}

			// Check for bypass-triggering cookies
			foreach ($entry['bypassCookies'] as $cookie)
			{
				if (isset($_COOKIE[$cookie]))
				{
					HeaderHelper::setHeader(["X-Cachemaster-BypassCookie: {$cookie}"]);
					return;
				}
			}

			HeaderHelper::setHeader($entry['headers']);

			$output = static::addDebugInfo($entry['content'], $entry);
			echo $output;

			exit;

		}

		return;

	}

	/**
	 * @param string $content The cache content
	 * @param array $entry The full cache entry
	 *
	 * @return string The cache content, with debug info added if desired
	 */
	public static function addDebugInfo($content = null, $entry = null)
	{

		if (!CACHEMASTER_STATIC_DEBUG )
		{
			return $content;
		}

		// TODO: Expand this to work with other (non-XML) content types?

		$debugInfo = "<!-- Served from Cachemaster Static Cache -->"
			. (!empty($entry['static_key']) ? "<!-- Cachemaster static key: " . $entry['static_key'] . " -->" : '')
			. "<!-- Cachemaster path: {$entry['path']} -->"
			. "<!-- Cachemaster expiration: {$entry['debug:expiryDate']} -->";

		foreach ($entry['headers'] as $k => $v)
		{
			$v = explode(';', $v);
			if ($k == 'Content-Type' && in_array($v[0], CachemasterHelper::getXmlMimeTypes()))
			{
				return $content . $debugInfo;
			}

		}

		return $content;

	}

}
