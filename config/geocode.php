<?php defined('SYSPATH') or die('No direct script access.');
/**
 * @package  Geocode
 *
 * Geocode configuration is defined in groups which allows you to easily switch
 * between different Geocode settings for different geocoding web services.
 *
 * Group Options:
 *  service		The name of the web service class ('Google' or 'Yahoo')
 *  api_key		The API key for this web service
 */

return array(
	'default' => array(
		'service'	=> 'Google',
		'api_key'	=> '',
	),
);
