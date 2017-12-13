<?php

interface ILSClient {
    function getFunds();
    function getFund($fundid);
    function getILSName();
    function getILSURL();
    function placeOrder($order);
    function updateOrder($order);
    function getOrder($orderid);
}

?>
