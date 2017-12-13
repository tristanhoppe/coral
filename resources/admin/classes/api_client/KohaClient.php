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
        $this->coralToKohaKeys = array(
            'shortName' => 'name',
            'fundID' => 'id',
            'fundCode' => 'code',
            'ilsOrderlineID' => 'ordernumber',
            'priceTaxExcluded' => 'rrp_tax_excluded',
            'priceTaxIncluded' => 'rrp_tax_included',
            'taxRate' => 'tax_rate_on_ordering'
        );
        $this->kohaToCoralKeys = array_flip($this->coralToKohaKeys);
    }

    /**
     * Gets funds from the ILS
     * @return key-value array with fund description
     */
    function getFunds() {
        $loginID = CoralSession::get('loginID');
        $borrowernumber = $this->getBorrowernumber($loginID);
        $request = $this->api . "/acquisitions/funds/";
        if ($borrowernumber) $request .= "?budget_owner_id=$borrowernumber";
        $response = Unirest\Request::get($request);
        # Array of StdClass Objects to array of associative arrays
        $funds = json_decode(json_encode($response->body), TRUE);
        $funds = array_map(array($this, '_vendorToCoral'), $funds);
        return $funds;
    }

    function getFund($fundid) {
        $request = $this->api . "/acquisitions/funds/?id=$fundid";
        $response = Unirest\Request::get($request);
        # Array of StdClass Objects to array of associative arrays
        $fund = json_decode(json_encode($response->body), TRUE);
        $fund = $this->_vendorToCoral($fund);
        return $fund[0];
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

    function placeOrder($order) {
        error_log("placing order");
        $headers = array('Accept' => 'application/json');
        $request = $this->api . "/acquisitions/orders/";
        // Koha expects tax rate in decimal rather than in percentage: 5.5% => 0.0550
        if ($order['taxRate']) $order['taxRate'] = $order['taxRate'] / 100;
        $body = Unirest\Request\Body::json($this->_vendorToKoha($order));
        $response = Unirest\Request::post($request, $headers, $body);
        return $response->body->ordernumber ? $response->body->ordernumber : null;
    }

    function updateOrder($order) {
        $headers = array('Accept' => 'application/json');
        $request = $this->api . "/acquisitions/orders/" . $order['ilsOrderlineID'];
        // Koha expects tax rate in decimal rather than in percentage: 5.5% => 0.0550
        if ($order['taxRate']) $order['taxRate'] = $order['taxRate'] / 100;
        $body = Unirest\Request\Body::json($this->_vendorToKoha($order));
        $response = Unirest\Request::put($request, $headers, $body);
    }

    function getOrder($orderid) {
        error_log("getting order $orderid");
        $response = Unirest\Request::get($this->api . "/acquisitions/orders/$orderid");
        $order = json_decode(json_encode($response->body), TRUE);
        return isset($order['ordernumber']) ? $order : null;
    }

    private function getBorrowernumber($loginID) {
        $response = Unirest\Request::get($this->api . "/patrons/?userid=$loginID");
        $borrowers = json_decode(json_encode($response->body), TRUE);
        return isset($borrowers[0]) ? $borrowers[0]['borrowernumber'] : null;
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
        if (!is_array($array)) return null;
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
