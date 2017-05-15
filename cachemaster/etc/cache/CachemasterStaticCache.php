<?php
namespace Craft;

/**
 * CachemasterStaticCache
 *
 * @author    Michael Rog <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.cachemaster
 * @since     1.0
 */
class CachemasterStaticCache extends \CFileCache
{


	// Properties
	// =========================================================================


	/**
	 * @var string a string prefixed to every cache key so that it is unique.
	 */
	public $keyPrefix = 'Cachemaster__';

	/**
	 * @var boolean whether to md5-hash the cache key for normalization purposes.
	 **/
	public $hashKey = false;

	/**
	 * @var array|boolean the functions used to serialize and unserialize cached data.
	 */
	public $serializer = ['json_encode','json_decode'];


	/**
	 * @var string The directory to store cache files.
	 */
	public $cachePath;

	/**
	 * @var integer the permission to be set for directory to store cache files
	 * This value will be used by PHP chmod function.
	 */
	// TODO: Adjust this for better security?
	public $cachePathMode = 0777;

	/**
	 * @var string cache file suffix. Defaults to '.bin'.
	 */
	public $cacheFileSuffix = '.json';

	/**
	 * @var integer the permission to be set for new cache files.
	 * This value will be used by PHP chmod function.
	 */
	// TODO: Adjust this for better security?
	public $cacheFileMode = 0666;

	/**
	 * @var integer the level of sub-directories to store cache files.
	 */
	public $directoryLevel = 0;

	/**
	 * @var boolean whether cache entry expiration time should be embedded into a physical file.
	 * Defaults to false meaning that the file modification time will be used to store expire value.
	 * True value means that first ten bytes of the file would be reserved and used to store expiration time.
	 */
	public $embedExpiry = false;


	/**
	 * @var int
	 */
	private $_gcProbability = 100;

	/**
	 * @var bool
	 */
	private $_gced = false;

	/**
	 * @var
	 */
	private $_originalKey;


	// Public Methods
	// =========================================================================


	/**
	 * Override so we can set a custom file cache path.
	 *
	 * @return null
	 */
	public function init()
	{

		if (!$this->cachePath)
		{
			$this->cachePath = craft()->cachemaster_outputCache->getStaticCachePath();
		}

		parent::init();

	}


	/**
	 * Stores a value identified by a key into cache. If the cache already contains such a key, the existing value and
	 * expiration time will be replaced with the new ones.
	 *
	 * @param string             $id         The key identifying the value to be cached
	 * @param mixed              $value      The value to be cached
	 * @param int                $expire     The number of seconds in which the cached value will expire. 0 means never
	 *                                       expire.
	 * @param \ICacheDependency $dependency Dependency of the cached item. If the dependency changes, the item is
	 *                                       labeled invalid.
	 *
	 * @return bool true if the value is successfully stored into cache, false otherwise.
	 */
	public function set($id, $value, $expire = null, $dependency = null)
	{
		$this->_originalKey = $id;
		return parent::set($id, $value, $expire, $dependency);
	}


	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key. Nothing will be done if the
	 * cache already contains the key.
	 *
	 * @param string             $id         The key identifying the value to be cached
	 * @param mixed              $value      The value to be cached
	 * @param int                $expire     The number of seconds in which the cached value will expire. 0 means never
	 *                                       expire.
	 * @param \ICacheDependency $dependency Dependency of the cached item. If the dependency changes, the item is
	 *                                       labeled invalid.
	 *
	 * @return bool true if the value is successfully stored into cache, false otherwise.
	 */
	public function add($id, $value, $expire = null, $dependency = null)
	{
		$this->_originalKey = $id;
		return parent::add($id, $value, $expire, $dependency);
	}


	// Protected Methods
	// =========================================================================


	/**
	 * Stores a value identified by a key in cache. This is the implementation of the method declared in the parent
	 * class.
	 *
	 * @param string  $key    The key identifying the value to be cached
	 * @param string  $value  The value to be cached
	 * @param int     $expire The number of seconds in which the cached value will expire. 0 means never expire.
	 *
	 * @return bool true if the value is successfully stored into cache, false otherwise.
	 */
	protected function setValue($key, $value, $expire)
	{

		CachemasterPlugin::log("Writing value to static cache: {$key}");
		if (!$this->_gced && mt_rand(0, 1000000) < $this->getGCProbability())
		{
			$this->gc();
			$this->_gced = true;
		}

		if($expire <= 0)
		{
			$expire = 31536000; // 1 year
		}

		$expire += time();

		$cacheFile = $this->getCacheFile($key);

		if ($this->directoryLevel > 0)
		{
			IOHelper::createFolder(IOHelper::getFolderName($cacheFile));
		}

		if ($this->_originalKey == 'useWriteFileLock')
		{
			if (IOHelper::writeToFile($cacheFile, $value, true, false, true) !== false)
			{
				IOHelper::changePermissions($cacheFile, craft()->config->get('defaultFilePermissions'));
				return IOHelper::touch($cacheFile, $expire);
			}
			else
			{
				return false;
			}
		}
		else
		{
			if (IOHelper::writeToFile($cacheFile, $this->embedExpiry ? $expire.$value : $value) !== false)
			{
				IOHelper::changePermissions($cacheFile, craft()->config->get('defaultFilePermissions'));
				return $this->embedExpiry ? true : IOHelper::touch($cacheFile, $expire);
			}
			else
			{
				return false;
			}
		}

	}


	/**
	 * @param string $key
	 *
	 * @return string
	 */
	public function getCacheFile($key)
	{
		/*
		 * We need to generate a hash from our key, to prevent the possibility of the filename being too long for
		 * file_get_contents() to eventually open.
		 *
		 * Our keys include the path, and the upper bound on URL length (2083 chars)
		 * is way beyond the upper bound for filenames length that `fopen` will handle without complaining.
		 *
		 * TODO: Maybe do this earlier, via this->hashKey, instead?
		 */
		$name = CachemasterStaticHandler::cleanFilename($key);
		$name = sha1($name);
		return parent::getCacheFile($name);
	}





}
