<?php

require '../resources/api_client/vendor/autoload.php';
require 'ILSClient.php';

/**
 * KohaClient
 */
class KohaClient implements ILSClient {

    private $api;
    private $coralToKohaKeys;
    private $kohaToCoralKeys;

    function __construct() {
        $config = new Configuration();
        $this->api = $config->ils->ilsApiUrl;
        $this->coralToKohaKeys = array("shortName" => "name", "fundID" => "id", "fundCode" => "code");
        $this->kohaToCoralKeys = array_flip($this->coralToKohaKeys);
    }

    /**
     * Gets funds from the ILS
     * @return key-value array with fund description
     */
    function getFunds() {
        $response = Unirest\Request::get($this->api . "/acquisitions/funds");
        # Array of StdClass Objects to array of associative arrays
        $funds = json_decode(json_encode($response->body), TRUE);
        $funds = array_map(array($this, '_vendorToCoral'), $funds);
        return $funds;
    }

    /**
     * Gets the ILS name
     * @return the ILS name
     */
    function getILSName() {
        return "Koha";
    }

    /**
     * Gets the ILS API url
     * @return the ILS API url
     */
    function getILSURL() {
        return $this->api;
    }

    /**
     * Changes the keys of a fund array from Koha keys to Coral keys
     */
    private function _vendorToCoral($vendor) {
        $kohaToCoralKeys = $this->kohaToCoralKeys;
        return $this->_changeKeys($vendor, $kohaToCoralKeys);
    }

    /**
     * Changes the keys of a fund array from Coral keys to Koha keys
     */
    private function _vendorToKoha($vendor) {
        $coralToKohaKeys = $this->coralToKohaKeys;
        return $this->_changeKeys($vendor, $coralToKohaKeys);
    }

    /**
     * Changes the keys of an array
     * @param $array a key/value array
     * @param $keys an array containing $oldKey => $newKey key/values
     * @return the modified array with the new keys
     */
    private function _changeKeys($array, $keys) {
        foreach ($keys as $oldKey => $newKey) {
            if (array_key_exists($oldKey, $array)) {
                $array[$newKey] = $array[$oldKey];
                unset($array[$oldKey]);
            }
        }
        return $array;
    }

}

?>
