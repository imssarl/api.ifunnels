<?php

/**
 * Parent class for all UsageRecord subclasses
 * @author Kevin Burke <kevin@twilio.com>
 * @license  http://creativecommons.org/licenses/MIT/ MIT
 * @link     http://pear.php.net/package/Core_Services_Twilio
 */
class Core_Services_Twilio_UsageResource extends Core_Services_Twilio_ListResource
{
	public function getResourceName($camelized = false)
	{
		$this->instance_name = 'Core_Services_Twilio_Rest_UsageRecord';
		return $camelized ? 'UsageRecords' : 'usage_records';
	}

	public function __construct($client, $uri)
	{
		$uri = preg_replace("#UsageRecords#", "Usage/Records", $uri);
		parent::__construct($client, $uri);
	}
}

