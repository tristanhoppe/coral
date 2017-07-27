<?php

require '../resources/api_client/vendor/autoload.php';
require 'ILSClient.php';

/**
 * KohaClient
 */
class KohaClient implements ILSClient {

    private $server;
    private $api;
    private $coralToKohaKeys;
    private $kohaToCoralKeys;

    function __construct() {
        $config = new Configuration();
        $this->server = $config->ils->ilsAdminUrl;
        $this->api = $config->ils->ilsApiUrl;
        $this->coralToKohaKeys = array("companyURL" => "url", "noteText" => "notes", "accountDetailText" => "accountnumber");
        $this->kohaToCoralKeys = array_flip($this->coralToKohaKeys);
    }

    /**
     * Adds a vendor in the ILS
     * @param $vendor key-value array with vendor description
     * @return id of the vendor in the ils
     */
    function addVendor($vendor) {
        $headers = array("Accept" => "application/json");
        $body = Unirest\Request\Body::json($this->_vendorToKoha($vendor));
        $response = Unirest\Request::post($this->api . "/acquisitions/vendors", $headers, $body);
        return ($response->body->id) ? $response->body->id : null;
        //return ($response->body->id) ? $response->body->id : $response->raw_body;
    }

    /**
     * Gets a vendor from the ILS
     * @param id of the vendor in the ils
     * @return key-value array with vendor description
     */
    function getVendor($id) {
        $response = Unirest\Request::get($this->api . "/acquisitions/vendors/$id");
        return $this->_vendorToCoral((array) $response->body);
    }

     /**
     * Gets a vendor from the ILS
     * @param name of the vendor in the ils
     * @return key-value array with vendor description
     */
    function getVendorByName($name) {
        $response = Unirest\Request::get($this->api . "/acquisitions/vendors/?name=$name");
        return $this->_vendorToCoral((array) $response->body);
    }

    /**
     * Gets a vendor from the ILS
     * @param name of the vendor in the ils
     * @return key-value array with vendor description
     */
    function getVendorByExactName($name) {
        $response = Unirest\Request::get($this->api . "/acquisitions/vendors/?exactname=$name");
        return $this->_vendorToCoral((array) $response->body);
    }

    /**
     * Does a vendor exist in the ILS?
     * @param name of the vendor in the ils
     * @return boolean
     */
    function vendorExists($name) {
        $response = Unirest\Request::get($this->api . "/acquisitions/vendors/?exactname=$name");
        return (count((array) $response->body) > 0) ? true : false;
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
     * Gets the ILS Vendor url
     * @return the ILS Vendor url
     */
    function getVendorURL() {
        return $this->server . "/cgi-bin/koha/acqui/supplier.pl?booksellerid=";
    }

    /**
     * Changes the keys of a vendor array from Koha keys to Coral keys
     */
    private function _vendorToCoral($vendor) {
        $kohaToCoralKeys = $this->kohaToCoralKeys;
        return $this->_changeKeys($vendor, $kohaToCoralKeys);
    }

    /**
     * Changes the keys of a vendor array from Coral keys to Koha keys
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
