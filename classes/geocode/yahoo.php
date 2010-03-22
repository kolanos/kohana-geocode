<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Geocode using Yahoo's geocoding API
 *
 * @package		Geocode
 * @subpackage	Geocode_Yahoo
 * @author		Michael Lavers
 * @author		http://github.com/coderjoe/gogeocode/blob/master/AUTHORS
 * @license		http://github.com/coderjoe/gogeocode/blob/master/LICENSE
 */
class Geocode_Yahoo extends Geocode
{
	/*
	 * Yahoo status codes grokked from:
	 * http://developer.yahoo.com/search/errors.html
	 */

	/**
	 * Status Code:
	 * HTTP Status 200 Success!
	 * @var int
	 * @access public
	 */
	const SUCCESS = 200;

	/**
	 * Status Code:
	 * HTTP Status 404 Not Found
	 * @var int
	 * @access public
	 */
	const NOT_FOUND = 404;


	/**
	 * Status Code:
	 * Bad request. The parameters passed to the service did not match as expected.
	 * The Message should tell you what was missing or incorrect.
	 * (Note: BaseGeocode does not return the error message)
	 * @var int
	 * @access public
	 */
	const BAD_REQUEST = 400;

	/**
	 * Status Code:
	 * Forbidden. You do not have permission to access this resource, or are over your rate limit.
	 * @var int
	 * @access public
	 */
	const BAD_KEY = 403;

	/**
	 * Status Code:
	 * Forbidden. You do not have permission to access this resource, or are over your rate limit.
	 * @var int
	 * @access public
	 */
	const TOO_MANY_QUERIES = 403;

	/**
	 * Status Code:
	 * Service unavailable. An internal problem prevented us from returning data to you.
	 * @var int
	 * @access public
	 */
	const SERVER_ERROR = 503;

	/**
	 * Geocode the given address. See BaseGeocode::geocode for detailed information
	 * about this function's return type.
	 *
	 * @param string $address The string address to retrieve geocode information about
	 * @return array An empty array on server not found. Otherwise an array of request and geocoded location information.
	 */
	public function execute($address)
	{
		$ret_val = array();

		$url_base = 'http://api.local.yahoo.com';
		$service_name = '/MapsService';
		$version = '/V1';
		$method = '/geocode';

		$request = $url_base;
		$request .= $service_name;
		$request .= $version;
		$request .= $method.'?location='.urlencode($address).'&appid='.$this->api_key;

		$file = $this->load_xml($request);

		if (empty($file))
		{
			return $ret_val;
		}

		$ret_val['response'] = array
		(
			'status' => $file['response'],
			'request' => 'geocode'
		);

		if ($ret_val['response']['status'] == Geocode_Yaho::SUCCESS)
		{
			$xml = new SimpleXMLElement($file['contents']);

			$xml->registerXPathNamespace('urn', 'urn:yahoo:maps');

			$ret_val['placemarks'] = array();
			if ($xml)
			{
				$results = $xml->xpath('//urn:Result');
				$countries = $xml->xpath('//urn:Country');
				$admin_areas = $xml->xpath('//urn:State');
				// Yahoo Geocoding has no Sub-Administrative Area (County) support.
				$localities = $xml->xpath('//urn:City');
				$thoroughfares = $xml->xpath('//urn:Address');
				$postal_codes = $xml->xpath('//urn:Zip');
				$latitudes = $xml->xpath('//urn:Latitude');
				$longitudes = $xml->xpath('//urn:Longitude');

				if ($results)
				{
					for ($i = 0; $i < count($results); $i++)
					{
						$attributes = $results[$i]->attributes();

						$ret_val['placemarks'][$i]['accuracy'] = (string) $attributes['precision'];
						$ret_val['placemarks'][$i]['country'] = (string) $countries[$i];

						if (count($admin_areas) > $i and ! empty($admin_areas[$i]))
						{
							$ret_val['placemarks'][$i]['administrative_area'] = (string) $admin_areas[$i];
						}

						if (count($localities) > $i and ! empty($localities[$i]))
						{
							$ret_val['placemarks'][$i]['locality'] = (string) $localities[$i];
						}

						if (count($thoroughfares) > $i and ! empty($thoroughfares[$i]))
						{
							$ret_val['placemarks'][$i]['thoroughfare'] = (string) $thoroughfares[$i];
						}

						if (count($postal_codes) > $i and ! empty($postal_codes[$i]))
						{
							$postal_code = explode('-', $postal_codes[$i]);
							$ret_val['placemarks'][$i]['postal_code'] = (string) $postal_code[0];
						}

						$ret_val['placemarks'][$i]['latitude'] = (double) $latitudes[$i];
						$ret_val['placemarks'][$i]['longitude'] = (double) $longitudes[$i];
					}
				}
			}
		}
		
		return $ret_val;
	}
	
} // End Geocode_Yahoo class
