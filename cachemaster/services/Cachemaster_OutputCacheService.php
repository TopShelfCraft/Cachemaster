<?php
namespace Craft;
use Dompdf\Image\Cache;

/**
 * Cachemaster_OutputCacheService
 *
 * @author    Michael Rog <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.cachemaster
 * @since     1.0
 */
class Cachemaster_OutputCacheService extends BaseApplicationComponent
{


	// Properties
	// =========================================================================


	/**
	 * Whether the Output Cache is enabled
	 *
	 * We set this value once, during `init()`.
	 *
	 * @var bool
	 */
	private $_isEnabled = false;

	/**
	 * Whether the Output Cache is active for the current request
	 *
	 * @var bool
	 */
	private $_isActive = false;

	/**
	 * The current request's path as it will be recorded in the cache keys
	 *
	 * @var string
	 */
	private $_path;

	/**
	 * The current request's cache key
	 *
	 * @var string
	 */
	private $_fullKey;

	/**
	 * The DateTime when this output entry will expire
	 *
	 * @var DateTime
	 */
	private $_expiryDate;

	/**
	 * The handle of the cache method to use
	 *
	 * @var string
	 */
	private $_method = null;



	// Public Methods
	// =========================================================================


	/**
	 * Initializes the component
	 */
	public function init()
	{

		parent::init();

		$this->_path = craft()->cachemaster->getRequestPath();
		$this->_fullKey = craft()->cachemaster->getFullKey(null, false, null, null, CachemasterCacheType::Output);

		$isUncachable = craft()->request->isLivePreview() || craft()->request->isCpRequest();

		$this->_isEnabled =
			!$isUncachable
			&& craft()->cachemaster->getIsEnabled()
			&& craft()->config->get('enableOutputCaching', 'cachemaster');

		CachemasterPlugin::log("Output Cache is " . ($this->_isEnabled ? 'enabled!' : 'not enabled') . " for {$this->_path}");

	}


	/**
	 * Returns whether output caching is enabled
	 *
	 * @return bool
	 */
	public function getIsEnabled()
	{
		return $this->_isEnabled;
	}


	/**
	 * Returns whether output caching is active
	 *
	 * @return bool
	 */
	public function getIsActive()
	{
		return $this->_isEnabled && $this->_isActive;
	}


	/**
	 * Enable the Output Cache
	 *
	 * @return bool
	 */
	public function activate()
	{
		CachemasterPlugin::log("Activating output cache.");
		$this->_isActive = true;
	}


	/**
	 * Enable the Output Cache
	 *
	 * @return bool
	 */
	public function deactivate()
	{
		$this->_isActive = false;
	}


	/**
	 * Get the expiry date
	 *
	 * @return DateTime
	 */
	public function getExpiryDate()
	{

		if (empty($this->_expiryDate))
		{
			$this->_expiryDate = CachemasterHelper::getExpiryDate();
		}

		return $this->_expiryDate;

	}


	/**
	 * Set the expiry date
	 *
	 * @param mixed $expiryDate
	 */
	public function setExpiryDate($expiryDate = null)
	{

		if (empty($expiryDate))
		{
			return;
		}
		elseif (!($expiryDate instanceof DateTime))
		{
			$expiryDate = CachemasterHelper::getExpiryDate($expiryDate);
		}

		$this->_expiryDate = $expiryDate;

	}


	/**
	 * @param array $settings
	 */
	public function applySettings($settings = [])
	{

		if (!is_array($settings)) return;

		foreach ($settings as $k => $v)
		{
			switch ($k)
			{

				case 'duration':
					$this->setExpiryDate($v);
					break;

				case 'expiryDate':
					$expiryDate = craft()->cachemaster_dateTime->DT($v);
					$this->setExpiryDate($expiryDate);
					break;

				case 'method':
					$this->_method = $v;
					break;

				default:
					break;

			}
		}

	}


	/**
	 * Get the full output cache key for the current request
	 *
	 * @return string
	 */
	public function getFullKey()
	{
		return $this->_fullKey;
	}


	/**
	 * Runs when CachemasterPlugin is initialized.
	 *
	 * @return bool
	 */
	public function onCachemasterInit()
	{

		if ($this->_isEnabled)
		{

			if ($entry = craft()->cachemaster->get($this->_fullKey))
			{

				HeaderHelper::setHeader($entry['headers']);

				$output = $this->maybeAddDebugInfo($entry['content'], $entry);
				echo $output;

				craft()->end();

			}

			$this->begin();

		}

	}


	/**
	 * Runs when Yii triggers its onEndRequest event.
	 *
	 * @return bool
	 */
	public function onEndRequest()
	{

		if ($this->_isEnabled)
		{
			$this->end();
		}
	}


	/**
	 * Begin the Output Cache process
	 *
	 * @return bool
	 */
	public function begin()
	{
		// Start an output buffer
		ob_start();
	}


	/**
	 * End the Output Cache process
	 */
	public function end()
	{

		// Close the output buffer

		$content = ob_get_contents();
		// ob_end_flush();

		// Bypass the cache if the content contains unresolved image transforms.

		if (CachemasterHelper::containsTransformUrl($content))
		{
			return;
		}

		// Write the cache

		if ($this->getIsActive())
		{

			$headers = array_merge(CachemasterHelper::getHeaders(), [
				'X-CacheMaster-Key' => $this->_fullKey,
				'X-CacheMaster-CachedUntil' => $this->getExpiryDate()->w3c(),
			]);

			$seconds = CachemasterHelper::getExpirySeconds($this->getExpiryDate());

			$entry = [
				'key' => null,
				'path' => $this->_path,
				'fullKey' => $this->_fullKey,
				'expiryTime' => $this->getExpiryDate()->getTimestamp(),
				'debug:expiryDate' => $this->getExpiryDate()->w3c(),
				'debug:expirySeconds' => $seconds,
				'tags' => [],
				'content' => $content,
				'headers' => $headers,
			];

			if ($this->_method == CachemasterCacheMethod::CachemasterStatic)
			{

				$staticKey = CachemasterStaticHandler::getStaticKey();

				$this->_fullKey = $staticKey;
				$entry['static_key'] = $staticKey;
				$entry['headers']['X-CacheMaster-Key'] = $staticKey;

			}

			craft()->cachemaster->set($this->_fullKey, $entry, $seconds, null, $this->_method);

		}

	}


	/**
	 * @param string $content The cache content
	 * @param array $entry The full cache entry
	 *
	 * @return string The cache content, with debug info added if desired
	 */
	public function maybeAddDebugInfo($content = null, $entry = null)
	{

		if (empty($entry) || !(craft()->config->get('addOutputCacheDebugInfo', 'cachemaster'))) return $content;

		// TODO: Expand this to work with other (non-XML) content types?

		$debugInfo = "<!-- Cachemaster status: Served from cache. -->"
			. "<!-- Cachemaster key: {$entry['fullKey']} -->"
			. !empty($entry['static_key']) ? "<!-- Cachemaster static key: " . $entry['static_key'] . " -->" : ''
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


	/**
	 * Returns the path to the static cache folder.
	 *
	 * This will be located at craft/storage/runtime/cachemaster/static/ by default,
	 * but that can be overridden with the 'staticCachePath' config setting in craft/config/cachemaster.php.
	 *
	 * @return string The path to the static cache folder.
	 */
	public function getStaticCachePath()
	{

		$path = craft()->config->get('staticCachePath', 'cachemaster');

		if (!$path)
		{
			$path = craft()->path->getRuntimePath() . 'cachemaster/static';
		}

		IOHelper::ensureFolderExists($path);

		return $path;

	}


}
