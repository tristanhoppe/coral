<?php


$orderid = $_GET['orderid'];
$ilsClient = (new ILSClientSelector())->select();
$order = $ilsClient->getOrder($orderid);
print $order['orderstatus'];

?>
