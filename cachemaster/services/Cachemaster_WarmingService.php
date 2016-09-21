<?php
namespace Craft;
use Dompdf\Image\Cache;

/**
 * Cachemaster_WarmingService
 *
 * @author    Michael Rog <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.cachemaster
 * @since     1.0
 */
class Cachemaster_WarmingService extends BaseApplicationComponent
{

	private $_transformUrlPattern;

	/**
	 *
	 */
	public function init()
	{

		parent::init();

		$this->_transformUrlPattern = UrlHelper::getSiteUrl( craft()->config->get('resourceTrigger') . '/transforms' );

	}

	/**
	 *
	 */
	public function warmEntireCache()
	{

		$uris = craft()->db->createCommand()
			->select('uri')
			->from('elements_i18n')
			->where('uri IS NOT NULL')
			->queryColumn();

		CachemasterPlugin::log("Warming the entire cache...");
		CachemasterPlugin::log($uris);

		$urls = [];

		foreach ($uris as $uri)
		{
			$urls[] = ($uri === '__home__' ? UrlHelper::getSiteUrl('') : UrlHelper::getSiteUrl($uri));
		}

		$this->warmUrl($urls);

	}

	/**
	 * Accepts an Element ID, a BaseElementModel, a ElementCriteriaModel, a path, a URL, or an array of these.
	 *
	 * @param mixed $targets
	 */
	public function warmContent($targets = array())
	{

		if (!is_array($targets))
		{
			$targets = [$targets];
		}

		$urls = [];

		foreach ($targets as $target)
		{

			if (is_int($target))
			{

				// TODO: Query element by ID and see if it has a URI in the current craft()->language

			}
			elseif ($target instanceof BaseElementModel)
			{

				// Try to get a URL from the element

				if (!empty($target->getUrl()))
				{
					$urls[] = $target->getUrl();
				}

			}
			elseif ($target instanceof ElementCriteriaModel)
			{

				// TODO: Get URLs from ElementCriteriaModel and query URIs from that.

			}
			elseif (UrlHelper::isAbsoluteUrl($target))
			{

				// Easy-peasy.
				$urls[] = $target;

			}
			elseif (is_string($target))
			{

				// Assume we have a path and try to make a site URL out of it.
				$urls[] = UrlHelper::getSiteUrl($target);

			}

		}

		$this->warmUrl($urls);

	}


	/**
	 * @param array $urls
	 */
	public function warmUrl($urls = array())
	{

		if (!is_array($urls))
		{
			$urls = [$urls];
		}

		foreach ($urls as $url)
		{
			craft()->tasks->createTask('Cachemaster_WarmUrl', null, array(
				'url' => $url,
			));
		}

		// TODO: Throw an Exception if $url isn't actually a URL?

	}

	/**
	 * @param string $url
	 * @param array $options
	 * @param bool $checkForTransformUrls
	 *
	 * @return bool
	 */
	public function fetchUrl($url = null, $options = null, $checkForTransformUrls = false)
	{

		if (empty($url)) return false;
		CachemasterPlugin::log("Fetching {$url}");

		$defaultOptions = array(
			'timeout'         => 60, // TODO: make configurable
			'connect_timeout' => 60, // TODO: make configurable
			'allow_redirects' => true,
		);

		$client = new \Guzzle\Http\Client();

		$userAgent = 'Craft/'.craft()->getVersion().'.'.craft()->getBuild();
		$client->setUserAgent($userAgent, true);

		if (!empty($options) && is_array($options))
		{
			$options = array_merge($defaultOptions, $options);
		}

		$request = $client->get($url, null, $options);

		// TODO: Potentially long-running request, so close session to prevent session blocking on subsequent requests?
		// craft()->session->close();

		$response = $request->send();

		if ($response->isSuccessful())
		{

			CachemasterPlugin::log($response->getStatusCode() . ' ' . $response->getReasonPhrase());

			/*
			 * If this is an image request (or something else we don't care to check for transform URLs and whatnot)
			 * then we're done!
			 */
			if (!$checkForTransformUrls) { return true; }
			CachemasterPlugin::log("Checking for transform URLs...");

			// CachemasterPlugin::log($response->getBody(true));

			/*
			 * Otherwise, we're going to check to see if there are any transform generation URLs in the body.
			 * If so, we need to fetch them all (to trigger actual file generation), and then try the original URL again.
			 */

			$body = $response->getBody(true);

			if ($this->_containsTransformUrl($body))
			{

				CachemasterPlugin::log("Found transform generation URLs in {$url}");

				preg_match_all('!https?://\S+!', $body, $matches);
				foreach($matches[0] as $foundUrl)
				{
					if ($this->_containsTransformUrl($foundUrl))
					{
						$this->fetchUrl($foundUrl, null, false);
					}
				}

				return $this->fetchUrl($url, null, false);

			}
			else
			{
				return true;
			}

		}
		else
		{
			CachemasterPlugin::log("Problem fetching URL: {$url}");
			return false;
		}

	}

	private function _containsTransformUrl($str = '')
	{

		/**
		 * stripslashes($body) in case the URL has been JS-encoded or something.
		 * Can't use getResourceUrl() here because that will append ?d= or ?x= to the URL.
		 * (This is imitating logic from TemplateCacheService.)
		 */
		// CachemasterPlugin::log("Checking {$str} against " . $this->_transformUrlPattern . " ...");
		return strpos(stripslashes($str), $this->_transformUrlPattern) !== false;

	}

}
