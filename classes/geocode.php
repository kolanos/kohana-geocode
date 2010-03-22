<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Geocode abstract class.
 *
 * This module is based on Gogeocode.
 *
 * @package		Geocode
 * @author		Michael Lavers
 * @author		http://github.com/coderjoe/gogeocode/blob/master/AUTHORS
 * @license		http://github.com/coderjoe/gogeocode/blob/master/LICENSE
 */
abstract class Geocode
{
	/**
	 * The service API key as set by the user
	 * @var string
	 * @access protected
	 */
	protected $api_key;

	/**
	 * The earth's radius in a given unit system
	 * The default is 3963.1676 miles. The user can override this
	 * value with another value in annother system of measurement through
	 * the set_earth_radius() function.
	 *
	 * @var float
	 * @access protected
	 */
	protected $earth_radius;

	/**
	 * Singleton instance
	 *
	 * @chainable
	 * @param string $service The geocoding web service class name ('Google', 'Yahoo')
	 * @param string $key The API key for the web service being used
	 * @return object
	 */
	public static function instance($config = 'default')
	{
		static $instance = NULL;
		
		if ($instance === NULL)
		{
			// Load the configuration for this group
			$config = Kohana::config('geocode')->get($config);
		
			$class = 'Geocode_'.ucfirst($config['service']);
			$instance = new $class($config['api_key']);
		}
		
		return $instance;
	}
	
	/**
	 * Singleton factory
	 *
	 * @chainable
	 * @param string $service The geocoding web service class name ('Google', 'Yahoo')
	 * @param string $key The API key for the web service being used
	 * @return object
	 */
	public static function factory($config = 'default')
	{
		// Load the configuration for this group
		$config = Kohana::config('geocode')->get($config);
	
		// Set class name
		$class = 'Geocode_'.ucfirst($config['service']);

		return new $class($config['api_key']);
	}

	/**
	 * Basic public constructor which accepts an API key.
	 * The public constructor also sets the earth's radius to its default value
	 * in miles.
	 *
	 * @param string $key The geocoding service's API key
	 */
	public function __construct($key = NULL)
	{
		// We need an API key to proceed
		if (is_null($key)) return FALSE;
	
		// Default to default unit of miles
		// by providing the earth radius in miles
		$this->set_earth_radius(3963.1676);
		$this->set_key($key);
	}

	/**
	 * Modifier for the earth mean radius
	 *
	 * @param float $rad The new radius of the earth to use.
	 * @access public
	 */
	public function set_earth_radius($rad)
	{
		$this->earth_radius = $rad;
		return $this;
	}

	/**
	 * Modifier for the API key
	 *
	 * @param string $key The geocoding service API key to use.
	 * @access public
	 */
	public function set_key($key)
	{
		$this->api_key = $key;
		return $this;
	}

	/**
	 * Load XML from an address
	 *
	 * @param string $address The address representing the XML source
	 * @access protected
	 */
	protected function load_xml($address)
	{
		$ret_val = array();
		$contents = file_get_contents($address);

		if ( ! empty($http_response_header))
		{
			$code = $http_response_header[0];
			$matches = array();
			preg_match('/^HTTP\/\d+\.\d+\s+(\d+)\s+[\w\s]+$/', $code, $matches);

			$ret_val['response'] = $matches[1];
			$ret_val['contents'] = $contents;
		}

		return $ret_val;
	}

	/**
	 * Abstract function which will accept a string address
	 * and return an array of geocoded information for the given address.
	 *
	 * Return types for this function are mixed based on HTTP Response Codes:
	 *      Server not found: array()
	 *
	 *      404: array( 'Response' => array(
	 *                             'Status' => 404,
	 *                             'Request' => the subclass specific request
	 *                             )
	 *           );
	 *
	 *      200: The returned geocode information will be presented in the following format
	 *           While the example below only contains a single result, multiple results for a single
	 *           geocode request are possible and should be supported by subclasses
	 *
	 *           array( 'Response' => array(
	 *                             'Status' => ...
	 *                             'Request' => ...
	 *                             ),
	 *                  'Placemarks' => array(
	 *                                      array(
	 *                                        'Accuracy' => ...,
	 *                                        'Country'  => ...,
	 *                                        'AdministrativeArea' => ...,
	 *                                        'SubAdministrativeArea => ...,
	 *                                        'Locality' => ...,
	 *                                        'Thoroughfare' => ...,
	 *                                        'PostalCode' => ...,
	 *                                        'Latitude' => ...,
	 *                                        'Longitude' => ...
	 *                                      ),
	 *                                      array(
	 *                                        'Accuracy' => ...,
	 *                                        'Country' => ...,
	 *                                        .
	 *                                        .
	 *                                        .
	 *                                      )
	 *                                 )
	 *               )
	 *
	 * @param string $address A string representing the address the user wants decoded.
	 * @return array This function returns an array of geocoded location information for the given address.
	 * @access public
	 */
	abstract public function execute($address);

	/**
	 * Find the distance between the two latitude and longitude coordinates
	 * Where the latitude and longitude coordinates are in decimal degrees format.
	 *
	 * This function uses the haversine formula as published in the article
	 * "Virtues of the Haversine", Sky and Telescope, vol. 68 no. 2, 1984, p. 159
	 *
	 * References:
	 *         http://en.wikipedia.org/w/index.php?title=Haversine_formula&oldid=176737064
	 *         http://www.movable-type.co.uk/scripts/gis-faq-5.1.html
	 *
	 * @param float $lat1 The first coordinate's latitude
	 * @param float $ong1 The first coordinate's longitude
	 * @param float $lat2 The second coordinate's latitude
	 * @param float $long2 The second coordinate's longitude
	 * @return float The distance between the two points in the same unit as the earth radius as set by set_earth_radius() (default miles).
	 * @access public
	 */
	public function haversine_distance($lat1, $long1, $lat2, $long2)
	{
		$lat1 = deg2rad($lat1);
		$lat2 = deg2rad($lat2);
		$long1 = deg2rad($long1);
		$long2 = deg2rad($long2);

		$dlong = $long2 - $long1;
		$dlat = $lat2 - $lat1;

		$sinlat = sin($dlat/2);
		$sinlong = sin($dlong/2);

		$a = ($sinlat * $sinlat) + cos($lat1) * cos($lat2) * ($sinlong * $sinlong);
		$c = 2 * asin(min(1, sqrt($a)));

		return $this->earth_radius * $c;
	}

	/**
	 * Find the distance between two latitude and longitude points using the
	 * spherical law of cosines.
	 *
	 * @param float $lat1 The first coordinate's latitude
	 * @param float $ong1 The first coordinate's longitude
	 * @param float $lat2 The second coordinate's latitude
	 * @param float $long2 The second coordinate's longitude
	 * @return float The distance between the two points in the same unit as the earth radius as set by set_earth_radius() (default miles).
	 * @access public
	 */
	public function spherical_law_of_cosines_distance($lat1, $long1, $lat2, $long2)
	{
		$lat1 = deg2rad($lat1);
		$lat2 = deg2rad( $lat2);
		$long1 = deg2rad($long1);
		$long2 = deg2rad($long2);

		return $this->earth_radius * acos(sin($lat1) * sin($lat2) + cos($lat1) * cos($lat2) * cos($long2 - $long1));
	}

	/**
	 * Find the distance between two latitude and longitude coordinates
	 * Where the latitude and the longitude coordinates are in decimal degrees format.
	 *
	 * @param float $lat1 The first coordinate's latitude
	 * @param float $ong1 The first coordinate's longitude
	 * @param float $lat2 The second coordinate's latitude
	 * @param float $long2 The second coordinate's longitude
	 * @return float The distance between the two points in the same unit as the earth radius as set by set_earth_radius() (default miles).
	 * @access public
	 */
	public function distance_between($lat1, $long1, $lat2, $long2)
	{
		return $this->haversine_distance($lat1, $long1, $lat2, $long2);
	}
	
} // End Geocode Class
