#Geocoding for Kohana 3.x

This is a Geocoding module adapted from Gogeocode. It currently supports Google and Yahoo's Geocoding APIs.

##Get an API Key

* Google: http://code.google.com/apis/maps/signup.html
* Yahoo: http://developer.yahoo.com/maps/rest/V1/geocode.html

##Getting Started

Once you have your API key, copy config/geocode.php to your app directory, and edit it as follows:

	...
	return array(
		'default' => array(
			'service'	=> '<Google or Yahoo here>',
			'api_key'	=> '<API key here>',
		),
	);
	...

Now instantiate:

> $geocode = Geocode::instance();

or:

> $geocode = Geocode::factory();

Instantiate using your own config group (other than 'default'):

> $geocode = Geocode::instance('myconfig');

or: 

> $geocode = Geocode::factory('myconfig');

Now geocode an address:

> $white_house = $geocode->execute('1600 Pennsylvania Avenue, Washington, D.C. 20500');

This will return an array, the contents of $white_house will look something like this:

	Array
	(
	   [response] => Array
	       (
	           [status] => 200
	           [request] => geocode
	       )
	
	   [placemarks] => Array
	       (
	           [0] => Array
	               (
	                   [accuracy] => 9
	                   [country] => US
	                   [administrative_area] => DC
	                   [sub_administrative_area] => District of Columbia
	                   [locality] => Washington
	                   [postal_code] => 20500
	                   [latitude] => 38.89765
	                   [longitude] => -77.0356669
	               )
	       )
	)

##Definitions

 * response: The response from the web service.
 * * status: The status code returned. (200 = SUCCESS)
 * * request: The request type message.
 * placemarks: Address(es) matching the query.
 * * accuracy: The accuracy of the geocode. (1-10)
 * * country: The ISO country code of the geocode.
 * * administrative_area: The state or province of the geocode.
 * * sub_administrative_area: The county of the geocode. (Google only)
 * * locality: The city of the geocode.
 * * postal_code: The postal/zip code of the geocode.
 * * latitude: The latitude of the geocode.
 * * longitude: The longitude of the geocode.

##Address Queries

You don't need the full address to geocode it. All of the following will work:

> Geocode::factory()->execute('90210, USA');
> Geocode::factory()->execute('Beverly Hills, CA, USA');
> Geocode::factory()->execute('Sunset Strip, Beverly Hills, CA, USA');

Keep in mind that if you're geocoding a zip code, for example, that the latitude and longitude is roughly the center of this area.

##Placemarks

You may get a multiple results returned within the placemarks sub-array. Especially if the query is somewhat vague or there are multiple addresses matching the query. It will be sorted by the 'accuracy' of the geocodes.
