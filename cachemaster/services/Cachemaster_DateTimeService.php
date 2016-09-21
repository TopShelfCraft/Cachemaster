<?php
namespace Craft;

/**
 * Cachemaster_DateTimeService
 *
 * @author    Michael Rog <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.cachemaster
 * @since     1.0
 */
class Cachemaster_DateTimeService extends BaseApplicationComponent
{

	private $_systemDateTimeZone;

	/**
	 * Returns a DateTimeZone object for the given timezone, or for the system DateTimeZone if no timezone is given.
	 *
	 * @param string $timezone
	 *
	 * @return \DateTimeZone
	 */
	public function DTZ($timezone = null)
	{

		if (isset($timezone) && is_string($timezone))
		{
			return new \DateTimeZone($timezone);
		}
		else
		{

			if (!isset($this->_systemDateTimeZone))
			{
				$this->_systemDateTimeZone = new \DateTimeZone(craft()->getTimeZone());
			}

			return $this->_systemDateTimeZone;

		}

	}

	/**
	 * Instantiates a new \Craft\DateTime object from the given input
	 *
	 * @param mixed $date
	 *
	 * @throws Exception If the input is invalid or insufficient for creating a new DateTime.
	 * @return DateTime
	 */
	public function DT($date)
	{

		// If I pass in a string, I want to init a DateTime corresponding to that time in the system DateTimeZone
		if (is_string($date))
		{
			$date = new DateTime($date, $this->DTZ());
			$date->setTimezone($this->DTZ());
			return $date;
		}

		// If I pass in a DateTime, create a clone and set the system DateTimeZone
		if ($date instanceof DateTime)
		{
			$date = (clone $date);
			return $date->setTimezone($this->DTZ());
		}

		// If I pass in a native PHP \DateTime, I want to turn it into a \Craft\DateTime object in the system DateTimeZone
		if ($date instanceof \DateTime)
		{
			$date = new DateTime('@'.$date->getTimestamp(), $this->DTZ());
			$date->setTimezone($this->DTZ());
			return $date;
		}

		// If we pass in a null value, use the current date
		if (empty($date))
		{
			$date = new DateTime('now', $this->DTZ());
			$date->setTimezone($this->DTZ());
			return $date;
		}

		// Otherwise (if $date is not a recognized type), throw an Exception
		throw new Exception("Unable to create a DateTime from an unrecognized format.");

	}

}



