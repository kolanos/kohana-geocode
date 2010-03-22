<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Geocode using Google's geocoding API
 *
 * @package		Geocode
 * @subpackage	Geocode_Google
 * @author		Michael Lavers
 * @author		http://github.com/coderjoe/gogeocode/blob/master/AUTHORS
 * @license		http://github.com/coderjoe/gogeocode/blob/master/LICENSE
 */
class Geocode_Google extends Geocode
{
	/*
	 * Status code information grokked from:
	 * http://code.google.com/apis/maps/documentation/reference.html#GGeoStatusCode
	 */

	/**
	 * Status Code:
	 * No errors occurred; the address was successfully parsed and its geocode has been returned.
	 * @var int
	 * @access public
	 */
	const SUCCESS = 200;

	/**
	 * Status Code:
	 * HTTP Status Code 404 Not Found
	 * @var int
	 * @access public
	 */
	const NOT_FOUND = 404;


	/**
	 * Status Code:
	 * A directions request could not be successfully parsed.
	 * @var int
	 * @access public
	 */
	const BAD_REQUEST = 400;

	/**
	 * Status Code:
	 * A geocoding or directions request could not be successfully processed,
	 * yet the exact reason for the failure is not known.
	 * @var int
	 * @access public
	 */
	const SERVER_ERROR = 500;

	/**
	 * Status Code:
	 * The HTTP q parameter was either missing or had no value.
	 * For geocoding requests, this means that an empty address was specified as input.
	 * For directions requests, this means that no query was specified in the input.
	 * @var int
	 * @access public
	 */
	const MISSING_QUERY = 601;

	/**
	 * Status Code:
	 * Synonym for MISSING_QUERY.
	 * @var int
	 * @access public
	 */
	const MISSING_ADDRESS = 601;

	/**
	 * Status Code:
	 * No corresponding geographic location could be found for the specified address.
	 * This may be due to the fact that the address is relatively new, or it may be incorrect.
	 * @var int
	 * @access public
	 */
	const UNKNOWN_ADDRESS = 602;

	/**
	 * Status Code:
	 * The geocode for the given address or the route for the given directions query
	 * cannot be returned due to legal or contractual reasons.
	 * @var int
	 * @access public
	 */
	const UNAVAILABLE_ADDRESS = 603;

	/**
	 * Status Code:
	 * The GDirections object could not compute directions between the points mentioned
	 * in the query. This is usually because there is no route available between the two
	 * points, or because we do not have data for routing in that region.
	 * @var int
	 * @access public
	 */
	const UNKNOWN_DIRECTIONS = 604;

	/**
	 * Status Code:
	 * The given key is either invalid or does not match the domain for which it was given.
	 * @var int
	 * @access public
	 */
	const BAD_KEY = 610;

	/**
	 * Status Code:
	 * The given key has gone over the requests limit in the 24 hour period.
	 * @var int
	 * @access public
	 */
	const TOO_MANY_QUERIES = 620;

	/**
	 * Geocode the provided API. See BaseGeocode::geocode for detailed information
	 * about this function's return type.
	 *
	 * @param string $address The string address to retrieve geocode information about
	 * @return array An empty array on server not found. Otherwise an array of geocoded location information.
	 */
	public function execute($address)
	{
		$ret_val = array();
		$url = "http://maps.google.com/maps/geo?q=";
		$url .= urlencode($address)."&output=xml&oe=UTF-8&key=".$this->api_key;

		$ns_kml = 'http://earth.google.com/kml/2.0';
		$ns_urn = 'urn:oasis:names:tc:ciq:xsdschema:xAL:2.0';

		$file = $this->load_xml($url);

		if (empty($file))
		{
			return $ret_val;
		}

		$ret_val['response'] = array
		(
			'status' => (int) $file['response'],
			'request' => 'geo'
		);

		if ($file['response'] == 200)
		{
			$xml = new SimpleXMLElement($file['contents']);

			$xml->registerXPathNamespace('kml', $ns_kml);
			$xml->registerXPathNamespace('urn', $ns_urn);

			// Now that we have the google request, and we succeeded in getting a response
			// from the server, lets replace oure response portion with the google response
			$ret_val['response']['status'] = (int) $xml->Response->Status->code;
			$ret_val['response']['request'] = (string) $xml->Response->Status->request;

			$ret_val['placemarks'] = array();
			if ($xml and $ret_val['response']['status'] == Geocode_Google::SUCCESS)
			{
				$placemarks = $xml->xpath('//kml:Placemark');
				$countries = $xml->xpath('//urn:CountryNameCode');
				$admin_areas = $xml->xpath('//urn:AdministrativeAreaName');
				$sub_admin_areas = $xml->xpath('//urn:SubAdministrativeAreaName');
				$localities = $xml->xpath('//urn:LocalityName');
				$thoroughfares = $xml->xpath('//urn:ThoroughfareName');
				$postal_codes = $xml->xpath('//urn:PostalCodeNumber');

				for ($i = 0; $i < count($placemarks); $i++)
				{
					list($longitude, $latitude) = explode(',', $placemarks[$i]->Point->coordinates);
					$attributes = $placemarks[$i]->AddressDetails->attributes();

					$ret_val['placemarks'][$i] = array();
					$ret_val['placemarks'][$i]['accuracy']	= (int) $attributes['Accuracy'];
					$ret_val['placemarks'][$i]['country'] = (string) $countries[$i];

					if (count($admin_areas) > $i)
					{
						$ret_val['placemarks'][$i]['administrative_area'] = (string) $admin_areas[$i];
					}

					if (count($sub_admin_areas) > $i)
					{
						$ret_val['placemarks'][$i]['sub_administrative_area'] = (string) $sub_admin_areas[$i];
					}

					if (count( $localities) > $i)
					{
						$ret_val['placemarks'][$i]['locality'] = (string) $localities[$i];
					}

					if (count( $thoroughfares ) > $i)
					{
						$ret_val['placemarks'][$i]['thoroughfare'] = (string)$thoroughfares[$i];
					}

					if (count($postal_codes) > $i)
					{
						$ret_val['placemarks'][$i]['postal_code'] = (string) $postal_codes[$i];
					}

					$ret_val['placemarks'][$i]['latitude']= (double) $latitude;
					$ret_val['placemarks'][$i]['longitude'] = (double) $longitude;
				}
			}
		}
		
		return $ret_val;
	}
	
} // End Geocode_Google Class
