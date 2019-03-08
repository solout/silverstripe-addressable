<?php
/**
 * A utility class for geocoding addresses using the google maps API.
 *
 * @package silverstripe-addressable
 */
class GoogleGeocoding implements GeocodeServiceInterface
{

    /**
     * Convert an address into a latitude and longitude.
     *
     * @param string $address
     * @param null   $region
     * @return array
     * @throws \Exception
     */
    public function addressToPoint($address, $region = null): array
    {
        // Get the URL for the Google API (and check for legacy config)
        $url = Config::inst()->get(__CLASS__, 'google_api_url')
            ?? Config::inst()->get('GeocodeService', 'google_api_url');
        $key = Config::inst()->get(__CLASS__, 'google_api_key')
            ?? Config::inst()->get('GeocodeService', 'google_api_key');

        // Query the Google API
        $service = new RestfulService($url);
        $service->setQueryString(array(
            'address' => $address,
            'sensor'  => 'false',
            'region'  => $region,
            'key'       => $key
        ));
        if ($service->request()->getStatusCode() === 500) {
            $errorMessage = '500 status code, Are you sure your SSL certificates are properly setup? You can workaround this locally by setting CURLOPT_SSL_VERIFYPEER to "false", however this is not recommended for security reasons.';
            if (Director::isDev()) {
                throw new Exception($errorMessage);
            } else {
                user_error($errorMessage, E_USER_WARNING);
            }
            return false;
        }
        if (!$service->request()->getBody()) {
            // If blank response, ignore to avoid XML parsing errors.
            return false;
        }
        $response = $service->request()->simpleXML();

        if ($response->status != 'OK') {
            return false;
        }

        $location = $response->result->geometry->location;
        return array(
            'lat' => (float) $location->lat,
            'lng' => (float) $location->lng
        );
    }
}
