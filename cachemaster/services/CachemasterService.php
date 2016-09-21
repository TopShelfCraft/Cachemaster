<?php
namespace Craft;

/**
 * CachemasterService
 *
 * @author    Michael Rog <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.cachemaster
 * @since     1.0
 */
class CachemasterService extends BaseApplicationComponent
{


	// Properties
	// =========================================================================


	/**
	 * Whether caching is enabled (CachemasterService sets this value once, during `init()`.)
	 *
	 * @var bool
	 */
	private $_isEnabled = false;

	/**
	 * The cache components CacheMaster has loaded
	 *
	 * @var array
	 */
	private $_cacheComponents = array();

	/**
	 * The current request's path as it will be recorded in the cache keys
	 *
	 * @var string
	 */
	private $_path;



	// Public Methods
	// =========================================================================


	/**
	 * Initializes the Cachemaster component
	 */
	public function init()
	{

		parent::init();

		$isEnabled = !(craft()->isConsole()) && !(craft()->config->get('disableAllCaching', 'cachemaster'));
		$this->_isEnabled = $isEnabled;

		if (!craft()->isConsole())
		{
			CachemasterPlugin::log("Cachemaster is " . ($this->_isEnabled ? 'enabled!' : 'not enabled.'));
		}

	}


	/**
	 * Returns whether Cachemaster caching is enabled
	 *
	 * @return bool
	 */
	public function getIsEnabled()
	{
		return $this->_isEnabled;
	}


	/**
	 * Returns the loaded cache driver for the provided method,
	 * or for the default method if the provided method name is empty or unrecognized
	 *
	 * @var string $methodName The cache method name
	 * @return \CCache
	 */
	public function getDriver($methodName = null)
	{

		// If no method name is provided, use the current default.

		if (empty($methodName))
		{
			$methodName = craft()->config->get('defaultDriver', 'cachemaster');
		}

		// If this driver is already loaded, just return it.

		if (isset($this->_cacheComponents[$methodName]))
		{
			return $this->_cacheComponents[$methodName];
		}

		// This driver has not been loaded yet. Let's take care of that...

		$driver = null;

		switch ($methodName)
		{

			case CachemasterCacheMethod::APC:
			{
				$driver = new ApcCache();
				break;
			}

			case CachemasterCacheMethod::Db:
			{
				$driver = new DbCache();
				$driver->gCProbability = craft()->config->get('gcProbability', ConfigFile::DbCache);
				$driver->cacheTableName = craft()->db->getNormalizedTablePrefix().craft()->config->get('cacheTableName', ConfigFile::DbCache);
				$driver->autoCreateCacheTable = true;
				break;
			}

			case CachemasterCacheMethod::Dummy:
			{
				$driver = new \CDummyCache();
				break;
			}

			case CachemasterCacheMethod::EAccelerator:
			{
				$driver = new EAcceleratorCache();
				break;
			}

			case CachemasterCacheMethod::File:
			{
				$driver = new FileCache();
				$driver->cachePath = craft()->config->get('cachePath', ConfigFile::FileCache);
				$driver->gCProbability = craft()->config->get('gcProbability', ConfigFile::FileCache);
				break;
			}

			case CachemasterCacheMethod::MemCache:
			{
				$driver = new MemCache();
				$driver->servers = craft()->config->get('servers', ConfigFile::Memcache);
				$driver->useMemcached = craft()->config->get('useMemcached', ConfigFile::Memcache);
				break;
			}

			case CachemasterCacheMethod::Redis:
			{
				$driver = new RedisCache();
				$driver->hostname = craft()->config->get('hostname', ConfigFile::RedisCache);
				$driver->port = craft()->config->get('port', ConfigFile::RedisCache);
				$driver->password = craft()->config->get('password', ConfigFile::RedisCache);
				$driver->database = craft()->config->get('database', ConfigFile::RedisCache);
				$driver->timeout = craft()->config->get('timeout', ConfigFile::RedisCache);
				break;
			}

			case CachemasterCacheMethod::WinCache:
			{
				$driver = new WinCache();
				break;
			}

			case CachemasterCacheMethod::XCache:
			{
				$driver = new XCache();
				break;
			}

			case CachemasterCacheMethod::ZendData:
			{
				$driver = new ZendDataCache();
				break;
			}

			case CachemasterCacheMethod::CachemasterStatic:
			{
				$driver = new CachemasterStaticCache();
				$driver->cachePath = craft()->config->get('staticCachePath', 'cachemaster');
				$driver->setGCProbability(craft()->config->get('gcProbability', ConfigFile::FileCache));
				break;
			}

			default:
			{
				$driver = new \CDummyCache();
				break;
			}

		}

		// Init the new cache component...
		$driver->init();

		// ...save it for later...
		$this->_cacheComponents[$methodName] = $driver;

		// ...and return it.
		return $this->_cacheComponents[$methodName];

	}


	/**
	 * Returns the current request path
	 *
	 * @return string
	 */
	public function getRequestPath()
	{

		if (!isset($this->_path))
		{

			// getPath doesn't include the CP trigger, so we need to add it if for some strange reason we're caching a CP request.
			if (craft()->request->isCpRequest())
			{
				$this->_path = craft()->config->get('cpTrigger') . "/";
			}
			else
			{
				$this->_path = '';
			}

			$this->_path .= craft()->request->getPath();

			if (($pageNum = craft()->request->getPageNum()) != 1)
			{
				$this->_path .= '/' . craft()->config->get('pageTrigger') . $pageNum;
			}

			// TODO: Alphabetize the query params to prevent redundant cache entries?
			// TODO: Add whitelist/blacklist configs to filter query params for cache-keying purposes

			if ($queryString = craft()->request->getQueryStringWithoutPath())
			{

				$queryString = trim($queryString, '&');

				if ($queryString)
				{
					$this->_path .= '?'.$queryString;
				}

			}

		}

		return $this->_path;

	}


	/**
	 * Returns the full encoded key for a new cache entry
	 *
	 * @param null $key
	 * @param bool $global
	 * @param null $path
	 * @param null $locale
	 * @param string $type
	 *
	 * @return string
	 */
	public function getFullKey($key = null, $global = false, $path = null, $locale = null, $type = CachemasterCacheType::Fragment)
	{

		if (!is_string($key))
		{
			$key = '';
		}

		if ($global === true)
		{
			$context = 'global';
			$path = '';
		}
		else
		{
			$context = 'path';
			$path = is_string($path) ? $path : $this->getRequestPath();
		}

		if (empty($locale))
		{
			$locale = craft()->language;
		}

		if (!in_array($type, [
			CachemasterCacheType::Fragment,
			CachemasterCacheType::Output,
			CachemasterCacheType::System,
		]))
		{
			$type = CachemasterCacheType::Fragment;
		}

		$fullKey = "cachemaster >>> {$type} >>> {$locale} >>> {$context} >>> {$path} >>> {$key}";

		return $fullKey;

	}


	/**
	 * Returns an array containing the components of a cache key
	 *
	 * The full key will be of the form:
	 * 'cachemaster >>> (fragment|output|system) >>> locale >>> (path|global) >>> path >>> key'
	 *
	 * @param string $fullKey
	 *
	 * @return array
	 */
	public function parseFullKey($fullKey = null)
	{

		$parts = explode(' >>> ', $fullKey, 6);

		return array(
			'fullKey' => $fullKey,
			'type' => $parts[1],
			'locale' => $parts[2],
			'context' => $parts[3],
			'path' => $parts[4],
			'key' => $parts[5],
		);

	}


	/**
	 * Stores a value identified by a key into cache.
	 * If the cache already contains such a key, the existing value and
	 * expiration time will be replaced with the new ones.
	 *
	 * @param string $key The key identifying the value to be cached.
	 * @param mixed $value The value to be cached.
	 * @param int $expire The number of seconds in which the cached value will expire. (0 means never expire.)
	 * @param \ICacheDependency $dependency Dependency of the cached item. If the dependency changes, the item is labeled invalid.
	 * @param string $method The cache method name, if a driver other than the default is desired
	 *
	 * @return bool true if the value is successfully stored into cache, false otherwise.
	 */
	public function set($key, $value, $expire = null, $dependency = null, $method = null)
	{
		if ($expire === null)
		{
			$expire = craft()->config->getCacheDuration();
		}

		return $this->getDriver($method)->set($key, $value, $expire, $dependency);
	}


	/**
	 * Retrieves a value from cache with a specified key.
	 *
	 * @param string $key A key identifying the cached value
	 * @param string $method The cache method name, if a driver other than the default is desired
	 *
	 * @return mixed The value stored in cache, false if the value is not in the cache, expired if the dependency has changed.
	 */
	public function get($key, $method = null)
	{
		// In case there is a problem un-serializing the data.
		try
		{
			$value = $this->getDriver($method)->get($key);
		}
		catch (\Exception $e)
		{
			Craft::log('There was an error retrieving a value from cache with the key: '.$key.'. Error: '.$e->getMessage());
			$value = false;
		}

		return $value;
	}


	/**
	 * Deletes a value with the specified key from cache.
	 *
	 * @param string $key The key of the value to be deleted.
	 * @param string $method The cache method name, if a driver other than the default is desired
	 *
	 * @return bool If no error happens during deletion.
	 */
	public function delete($key, $method = null)
	{
		return $this->getDriver($method)->delete($key);
	}


	/**
	 * Deletes all values from cache. Be careful of performing this operation if the cache is shared by multiple
	 * applications.
	 *
	 * @param string $method The cache method name, if a driver other than the default is desired
	 *
	 * @return bool Whether the flush operation was successful.
	 */
	public function flush($method = null)
	{
		return $this->getDriver($method)->flush();
	}


}
