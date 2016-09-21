<?php
namespace Craft;

/**
 * CachemasterHelper
 *
 * Defines some helper methods for Cachemaster
 *
 * @author    Michael Rog <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.cachemaster
 * @since     1.0
 */
class CachemasterHelper
{


	/**
	 * @return bool
	 */
	private function _isTemplateCachingEnabled()
	{
		if (craft()->config->get('enableTemplateCaching'))
		{
			return true;
		}
	}


	/**
	 * @return array
	 */
	public static function getHeaders()
	{

		$headers = [];

		// Loop through each of the headers
		foreach (headers_list() as $header)
		{
			// Split it into its trimmed key/value
			$parts = array_map('trim', explode(':', $header, 2));

			// Make sure we have a full header and exclude some headers for security.
			if (isset($parts[1]) && !in_array($parts[0], ['Set-Cookie']))
			{
				$headers[$parts[0]] = $parts[1];
			}
		}

		return $headers;

	}


	/**
	 * @return array
	 */
	public static function getXmlMimeTypes()
	{

		// There may be others, yes, I understand...
		// (...but can they love you like I can?)

		return [
			'text/html',
			'application/xhtml+xml',
			'application/atom+xml',
			'application/rss+xml'
		];

	}


	/**
	 * @param null $duration
	 *
	 * @return DateTime
	 */
	public static function getExpiryDate($duration = null)
	{

		$expiryDate = DateTimeHelper::currentUTCDateTime();

		if (is_numeric($duration))
		{
			// We were given a number. We assume it's a number of seconds.
			CachemasterPlugin::log("Creating expiry date: {$duration} seconds...");
			$expiryDate->add(new DateInterval("PT{$duration}S"));
		}
		elseif ($duration instanceof DateTime)
		{
			// We were given a DateTime. We can use it directly.
			$expiryDate = $duration;
		}
		elseif (empty($duration) || !is_string($duration))
		{
			// We weren't given a number or string, so use the default.
			$i = new DateInterval(craft()->config->get('defaultTokenDuration'));
			$expiryDate->add($i);
		}
		else
		{
			// We have a valid string, which we assume is a Date String...
			$i = DateInterval::createFromDateString($duration);
			$expiryDate->add($i);
		}

		CachemasterPlugin::log("Generated expiry date: " . $expiryDate->w3c());
		return $expiryDate;

	}


	/**
	 * @param null $duration
	 *
	 * @return int
	 */
	public static function getExpirySeconds($duration = null)
	{

		$expiryDate = DateTimeHelper::currentUTCDateTime();

		if (is_numeric($duration))
		{
			// We were given a number. We assume it's a number of seconds.
			// We can short-circuit here because seconds is what we're looking for.
			return intval($duration);
		}
		elseif ($duration instanceof DateTime)
		{
			// If we're given a date, we can use it directly.
			$expiryDate = $duration;
		}
		elseif (empty($duration) || !is_string($duration))
		{
			// We weren't given a number or string, so use the default.
			$i = new DateInterval(craft()->config->get('defaultTokenDuration'));
			$expiryDate->add($i);
		}
		else
		{
			// We have a valid string, which we assume is a Date String...
			$i = DateInterval::createFromDateString($duration);
			$expiryDate->add($i);
		}

		// Calculate seconds
		$seconds = $expiryDate->getTimestamp() - DateTimeHelper::currentUTCDateTime()->getTimestamp();
		CachemasterPlugin::log("Generated expiry seconds: " . $seconds);
		return $seconds;

	}


}
