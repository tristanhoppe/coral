<?php

require '../resources/api_client/vendor/autoload.php';
require 'ILSClient.php';

class KohaClient implements ILSClient {

    function addVendor($vendor) {
        $server = "http://pro.koha.local/api/v1/";
        $headers = array("Accept" => "application/json");
        $body = Unirest\Request\Body::json($this->_vendorToKoha($vendor));
        $response = Unirest\Request::post($server . "/acquisitions/vendors", $headers, $body);
        return ($response->body->id) ? $response->body->id : null;
    }

    function getVendor() {
        $response = Unirest\Request::get($server . "/acquisitions/vendors/");
        return "Getting vendor from koha";
    }

    function getILSName() {
        return "Koha";
    }
    

    function getILSURL() {
        #return $this->server;
    }

    private function _vendorToKoha($vendor) {
        $coralToKohaKeys = array("companyURL" => "url", "noteText" => "notes");
        foreach ($coralToKohaKeys as $coralKey => $kohaKey) {
            if (array_key_exists($coralKey, $vendor)) {
                $vendor[$kohaKey] = $vendor[$coralKey];
                unset($vendor[$coralKey]);
            }
        }
        return $vendor;
    }

}

?>
