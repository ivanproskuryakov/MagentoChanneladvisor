<?php

//To run magento code outside magento
require_once 'app/Mage.php';
umask(0);
Mage::app();
error_reporting(E_ERROR | E_WARNING | E_PARSE);

$developerKey = '2e3bd48b-fb15-4c1b-af51-a1670b5bc209';
$password = 'Channel0714!';
$accountID = 'de820fda-f1eb-42e1-a3be-4b5ee02c897c';


//////////////////////// building soap request //////////////////////////////////////////////////////////
$client = new SoapClient("https://api.channeladvisor.com/ChannelAdvisorAPI/v6/ShippingService.asmx?WSDL");

$headersData = array('DeveloperKey' => $developerKey, 'Password' => $password);
$head = new SoapHeader("http://api.channeladvisor.com/webservices/", "APICredentials", $headersData);
$client->__setSoapHeaders($head);

$date1 = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d") - 8, date("Y"))) . 'T00:00:00';
$date2 = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . 'T23:59:59';

//For updating simple productproduct
$connection = Mage::getSingleton('core/resource')->getConnection('core_write');


$a = $connection->query("select * from sales_flat_order where created_at between '$date1' and '$date2' and issync=1 ");
$t = 0;
while ($roes = $a->fetch(PDO::FETCH_ASSOC)) {

    $orderId = $roes['entity_id'];
    $caorderid = $roes['ca_orderid'];
    $order = Mage::getModel('sales/order')->load($orderId);
    $orderstatu = $order->getStatus();
    if ($orderstatu != 'complete') {
        $shippingcost = $order->getShippingAmount();

        /*$arrData = array(
        'accountID'=>$accountID,
        'ShipmentList'=>array(
            'ShipmentList'=>array(
                'OrderId'=>$caorderid,
            'ShipmentType'=>'Full',
            'FullShipment'=>array(
                'shipmentCost'=>$shippingcost,
                )
            )

            )
        );
        echo '<pre>';print_r($arrData);echo '</pre>';
        $result=$client->SubmitOrderShipmentList($arrData);
        echo '<pre>';print_r($result);echo '</pre>';*/

        $arrData = array(
            'accountID' => $accountID,
            'orderIDList' => array($caorderid)
        );
        //echo '<pre>';print_r($arrData);echo '</pre>';
        $result = $client->GetOrderShipmentHistoryList($arrData);
        //echo '<pre>';print_r($result);echo '</pre>';

        $resultdata = $result->GetOrderShipmentHistoryListResult->ResultData;

        $response = $resultdata->OrderShipmentHistoryResponse;
        $ShippingStatus = $response->ShippingStatus;
        if (!($ShippingStatus == 'Shipped')) {
            echo 'Not shipped order : increament id ' . $roes['increment_id'] . ' => magento Order id : ' . $roes['entity_id'] . ' => CA orderid ' . $caorderid . '<br/>';
        } else {
            $shippingdata = $response->OrderShipments->OrderShipmentResponse;

            $shippingCost = $response->ShipmentCost;

            $order->setShippingAmount($shippingCost);
            $order->setBaseShippingAmount($shippingCost);
            $order->setShippingMethod('customshippingrate');

            $shippingTitle = "";
            $shippingcode = $shippingdata->ClassCode;
            if ($shippingcode == "Standard") {
                $shippingTitle = "Ground";
            } else if ($shippingcode == "Ground") {
                $shippingTitle = "Ground";
            } else if ($shippingcode == "Expedited") {
                $shippingTitle = "2nd Day Air";
            } else {
                $shippingTitle = "Ground";
            }

            $order->setShippingDescription($shippingTitle);

            $orderIncrementId = $order->getIncrementId();
            $shipmentCarrierCode = $shippingdata->CarrierCode;

            $shipmentTrackingNumber = $shippingdata->TrackingNumber;
            $shipmentCarrierTitle = $shippingTitle;

            echo $shipmentTrackingNumber . '=>' . $shipmentCarrierTitle . '=>' . $shipmentCarrierCode . '<br/>';

            $orderStatus = $order->getStatus();

            try {

                $order->save();
            } catch (Mage_Core_Exception $e) {
                print_r($e);
            }
            try {
                if (!$order->canInvoice()) {
                    Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));
                }

                $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

                if (!$invoice->getTotalQty()) {
                    Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));
                }

                $invoice->setRequestedCaptureCase(
                    Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE
                );
                $invoice->register();
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());

                $transactionSave->save();
                echo "invoice create for " . $orderId . '<br/>';
            } catch (Mage_Core_Exception $e) {
                print_r($e);
            }
            try {
                if (!$order->canShip()) {
                    Mage::throwException(Mage::helper('core')->__('Cannot create an shipping.'));
                }

                echo "order shipp : " . $orderId . '<br/>';
                $order = Mage::getModel('sales/order')->load($orderId);
                $itemQty = $order->getItemsCollection()->count();
                $shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($itemQty);

                $shipment = new Mage_Sales_Model_Order_Shipment_Api();
                $shipmentId = $shipment->create($order->getIncrementId());
                try {

                    $shipment->addTrack($shipmentId, 'custom', $shipmentCarrierTitle, $shipmentTrackingNumber);
                    echo "shippment create for " . $orderId . '<br/>';
                } catch (Mage_Core_Exception $e) {
                    print_r($e);
                }


                //echo $shipmentId;
            } catch (Mage_Core_Exception $e) {
                print_r($e);
            }
            echo 'shipping Upadted order: increament id ' . $roes['increment_id'] . ' => magento Order id : ' . $roes['entity_id'] . ' => CA orderid ' . $caorderid . '<br/>';
            unset($orderStatus, $convertOrder, $shipment, $items, $arrTracking);
            unset($track, $saveTransaction);
            unset($order);
        }

    } else {
        echo 'Order alreasy shipped : increament id ' . $roes['increment_id'] . ' => magento Order id : ' . $roes['entity_id'] . ' => CA orderid ' . $caorderid . '<br/>';
    }

    //exit(0);
}

?>