<?php
set_time_limit(0);
error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);
ini_set('memory_limit', '1024M');
include_once 'settings.php';
include_once 'states.php';
include_once "../app/Mage.php";

umask(0);
$app = Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
$orders = Mage::getModel('sales/order')->getCollection();
$log = '';


/*
 * SYNC SKUS
 */
$client = new SoapClient("http://api.channeladvisor.com/ChannelAdvisorAPI/v7/InventoryService.asmx?WSDL");
$headersData = array('DeveloperKey' => DEVELOPERKEY, 'Password' => PASSWORD);
$head = new SoapHeader("http://api.channeladvisor.com/webservices/", "APICredentials", $headersData);
$client->__setSoapHeaders($head);
foreach ($orders as $_order) {


    if (($_order->getStatus() == 'canceled') || ($_order->getStatus() == 'closed') || ($_order->getStatus() == 'complete')) continue;
    if (($_order->getData('ext_order_id') == 'ORDER_EXPORTED_CA')) continue;
//    if (($_order->getIncrementId() != 100000260) && ($_order->getIncrementId() != 100000261) && ($_order->getIncrementId() != 100000353)) continue; // ONLY FOR TESTING 


    $items = $_order->getItemsCollection();
    foreach ($items AS $itemid => $_item) {

        $item = array();
        $item['Sku'] = $_item->getSku();
        $item['Title'] = $_item->getName();

        // check sku exists
        $result = $client->DoesSkuExist(array('accountID' => ACCOUNTID, 'sku' => $item['Sku']));
        $skuExists = $result->DoesSkuExistResult->ResultData;

        // synch sku 
        if ($skuExists == false) {
            $result = $client->SynchInventoryItem(array('accountID' => ACCOUNTID, 'item' => $item));
            $log .= 'SKU=>' . $item['Sku'] . ' NAME=>' . $item['Title'] . ' STATUS=> ' . $result->SynchInventoryItemResult->Status . '<br/>';
        }

    }
}
//echo $log;
//exit();


/*
 * ADD SALES
 */
$total = 0;
$client = new SoapClient("http://api.channeladvisor.com/ChannelAdvisorAPI/v7/OrderService.asmx?WSDL");
$headersData = array('DeveloperKey' => DEVELOPERKEY, 'Password' => PASSWORD);
$head = new SoapHeader("http://api.channeladvisor.com/webservices/", "APICredentials", $headersData);
$client->__setSoapHeaders($head);
foreach ($orders as $_order) {


//    if (($_order->getStatus() != 'pending') && ($_order->getStatus() != 'processing')) continue;    
    if (($_order->getStatus() == 'canceled') || ($_order->getStatus() == 'closed') || ($_order->getStatus() == 'complete')) continue;
    if (($_order->getData('ext_order_id') == 'ORDER_EXPORTED_CA')) continue;
//    if (($_order->getIncrementId() != 100000260) && ($_order->getIncrementId() != 100000261) && ($_order->getIncrementId() != 100000353)) continue; // ONLY FOR TESTING

    $total++;
    $shippingAddress = $_order->getShippingAddress();
    $billingAddress = $_order->getBillingAddress();
    $items = $_order->getItemsCollection();
    $_order->setData('ext_order_id', 'ORDER_EXPORTED_CA');

    /* 
     * ###################################################################################################
     * 
     * Time
     */
    $_date = date('Y-m-d', strtotime($_order->getCreatedAt()));
    $_time = date('H:i:s', strtotime($_order->getCreatedAt()));
    $_datetime = $_date . 'T' . $_time;
    $order['OrderTimeGMT'] = $_datetime;
    $order['ClientOrderIdentifier'] = $_order->getIncrementId();


    /*
     * Status
     */
    $OrderStatus = array();

    if ($_order->getStatus() == 'processing') {
        $_paymentStatus = 'Cleared';
    }
    if (($_order->getStatus() == 'pending') || ($_order->getStatus() == 'holded') ||
        ($_order->getStatus() == 'fraud') || ($_order->getStatus() == 'pending_payment') ||
        ($_order->getStatus() == 'pending_paypal') || ($_order->getStatus() == 'holded')
    ) {
        $_paymentStatus = 'NotSubmitted';
    }

    if ($_order->getStatus() == 'processing') {
        $_checkoutStatus = 'Completed';
    }
    if (($_order->getStatus() == 'pending') || ($_order->getStatus() == 'holded') ||
        ($_order->getStatus() == 'fraud') || ($_order->getStatus() == 'pending_payment') ||
        ($_order->getStatus() == 'pending_paypal') || ($_order->getStatus() == 'holded')
    ) {
        $_checkoutStatus = 'OnHold';
    }

//    var_dump($_order->getStatus());
//    var_dump($_paymentStatus);
//    var_dump($_checkoutStatus);

    $OrderStatus['CheckoutStatus'] = 'Completed';
    $OrderStatus['CheckoutDateGMT'] = $_datetime;
    $OrderStatus['PaymentStatus'] = $_paymentStatus;
    $OrderStatus['PaymentDateGMT'] = $_datetime;
    $OrderStatus['ShippingStatus'] = 'Unshipped';
    $OrderStatus['ShippingDateGMT'] = $_datetime;
    $order['OrderStatus'] = $OrderStatus;


    /*
     * Customer Email
     */
    $order['BuyerEmailAddress'] = $_order->getCustomerEmail();
    $order['EmailOptIn'] = 'false';


    /*
     * Billing
     */
    $BillingInfo = array();
    $BillingInfo['AddressLine1'] = $billingAddress->getStreet(1);
    $BillingInfo['AddressLine2'] = $billingAddress->getStreet(2);
    $BillingInfo['City'] = $billingAddress->getCity();
    $BillingInfo['Region'] = convert_state(ucfirst($billingAddress->getRegion()), 'abbrev');
    $BillingInfo['PostalCode'] = $billingAddress->getPostcode();
    $BillingInfo['CountryCode'] = $billingAddress->getCountryId();
    $BillingInfo['FirstName'] = $billingAddress->getFirstname();
    $BillingInfo['LastName'] = $billingAddress->getLastname();
    $BillingInfo['PhoneNumberDay'] = $billingAddress->getTelephone();
    $BillingInfo['PhoneNumberEvening'] = $billingAddress->getTelephone();
    $order['BillingInfo'] = $BillingInfo;


    /*
     * Payment
     */
    //    PayPal    TEST = 100000260
    //    CC        TEST = 100000261
    //    GG        TEST = 100000353

    $PaymentInfo = array();
    $PaymentInfo['PaymentType'] = $_order->getIncrementId();
    $PaymentInfo['PaymentTransactionID'] = $_order->getIncrementId();
    if ($_order->getPayment()->getMethod() == 'paypal_standard') {
        $PaymentInfo['MerchantReferenceNumber'] = $_order->getIncrementId();                             //MerchantReferenceNumber
        $PaymentInfo['PaymentType'] = 'PP';                                                              //PaymentType 
        $PaymentInfo['PaymentTransactionID'] = $_order->getPayment()->getLastTransId();                  //PaymentTransactionID    
        $PaymentInfo['PaypalID'] = $_order->getPayment()->getAdditionalInformation('paypal_payer_id');   //PaypalID   
    }
    if ($_order->getPayment()->getMethod() == 'authorizenet') {
//        var_dump($_order->getPayment()->getData());
        $PaymentInfo['MerchantReferenceNumber'] = $_order->getIncrementId();                             //MerchantReferenceNumber
        $_authorize_cards = reset($_order->getPayment()->getAdditionalInformation('authorize_cards'));
        $_cc_type = $_authorize_cards['cc_type'];
        if ($_cc_type == 'AE') $_cc_type = 'AX';
        $PaymentInfo['PaymentType'] = $_cc_type;                                                          //PaymentType          
        $PaymentInfo['PaymentTransactionID'] = $_authorize_cards['last_trans_id'];                       //PaymentTransactionID  
        $PaymentInfo['CreditCardLast4'] = $_authorize_cards['cc_last4'];                                 //CreditCardLast4  
    }
    if ($_order->getPayment()->getMethod() == 'googlecheckout') {
        $PaymentInfo['MerchantReferenceNumber'] = $_order->getIncrementId();                             //MerchantReferenceNumber
        $PaymentInfo['PaymentType'] = 'GG';                                                              //PaymentType
        $PaymentInfo['CreditCardLast4'] = $_order->getPayment()->getData('cc_last4');                    //CreditCardLast4          
        $_googlecheckoutTransId = str_replace("-capture", "", $_order->getPayment()->getData('last_trans_id'));
        $PaymentInfo['PaymentTransactionID'] = $_googlecheckoutTransId;                                  //PaymentTransactionID          
    }
    $order['PaymentInfo'] = $PaymentInfo;

    /*
     * ShoppingCart
     */
    $ShoppingCart = array();
    $ShoppingCart['CartID'] = 0;
    $ShoppingCart['CheckoutSource'] = 'Unspecified';
    $ShoppingCart['VATTaxCalculationOption'] = 'Unspecified';
    $ShoppingCart['VATShippingOption'] = 'Unspecified';
    $ShoppingCart['VATGiftWrapOption'] = 'Unspecified';

    $LineItemSKUList = array();
    foreach ($items AS $itemid => $item) {
        $OrderLineItemItem = array();
        $OrderLineItemItem['ItemSaleSource'] = 'MAGENTO_ENTERPRISE';
        $OrderLineItemItem['UnitPrice'] = $item->getPrice();
        $OrderLineItemItem['LineItemID'] = $item->getProductId();
        $OrderLineItemItem['AllowNegativeQuantity'] = 'false';
        $OrderLineItemItem['Quantity'] = $item->getQtyOrdered();
        $OrderLineItemItem['SKU'] = $item->getSku();
        $OrderLineItemItem['BuyerFeedbackRating'] = '';
        $OrderLineItemItem['VATRate'] = 0;
        $OrderLineItemItem['TaxCost'] = $item->getData('tax_amount');
        $LineItemSKUList['OrderLineItemItem'][] = $OrderLineItemItem;
    }
    $ShoppingCart['LineItemSKUList'] = $LineItemSKUList;

    $OrderLineItemInvoice1 = array();
    $OrderLineItemInvoice1['LineItemType'] = 'SalesTax';
    $OrderLineItemInvoice1['UnitPrice'] = $_order->getTaxAmount();
    $OrderLineItemInvoice2 = array();
    $OrderLineItemInvoice2['LineItemType'] = 'Shipping';
    $OrderLineItemInvoice2['UnitPrice'] = $_order->getShippingAmount();
    $OrderLineItemInvoice3 = array();
    $OrderLineItemInvoice3['LineItemType'] = 'VATShipping';
    $OrderLineItemInvoice3['UnitPrice'] = $_order->getShippingTaxAmount();

    $ShoppingCart['LineItemInvoiceList'] = array();
    $ShoppingCart['LineItemInvoiceList'][] = $OrderLineItemInvoice1;
    $ShoppingCart['LineItemInvoiceList'][] = $OrderLineItemInvoice2;
    $ShoppingCart['LineItemInvoiceList'][] = $OrderLineItemInvoice3;

    $order['ShoppingCart'] = $ShoppingCart;

    /*
     * ShippingInfo
     */
    $ShippingInfo = array();
    $ShippingInfo['AddressLine1'] = $shippingAddress->getStreet(1);
    $ShippingInfo['AddressLine2'] = $shippingAddress->getStreet(2);
    $ShippingInfo['City'] = $shippingAddress->getCity();
    $ShippingInfo['Region'] = convert_state(ucfirst($shippingAddress->getRegion()), 'abbrev'); // IL
    $ShippingInfo['PostalCode'] = $shippingAddress->getPostcode();
    $ShippingInfo['CountryCode'] = $shippingAddress->getCountryId();
    $ShippingInfo['FirstName'] = $shippingAddress->getFirstname();
    $ShippingInfo['LastName'] = $shippingAddress->getLastname();
    $ShippingInfo['PhoneNumberDay'] = $shippingAddress->getTelephone();
    $ShippingInfo['PhoneNumberEvening'] = $shippingAddress->getTelephone();
    $order['ShippingInfo'] = $ShippingInfo;


    $_order->setStatus('complete');
    $_order->save();
//    var_dump($order);
//    exit();


    $result = $client->SubmitOrder(array('accountID' => ACCOUNTID, 'order' => $order));
    $log .= 'ORDER[SUBMIT]=>' . $order['ClientOrderIdentifier'] . ' STATUS=> ' . $result->SubmitOrderResult->Status . '<br/>';

//    var_dump($order);
//    var_dump($result);
//    exit();


}

/*
 * EMAIL
 * 
 */
$log .= 'Total sales: ' . $total . '<br/>';
echo $log;
mail(EMAIL_TO, EMAIL_SUBJECT, $log, EMAIL_HEADERS);
exit('EXIT');


?>


<!--    
//[0]=>  "canceled" => "Canceled"
//[1]=>  "closed" => "Closed"
//[2]=>  "complete" => "Complete"
//[3]=>  "fraud" => "Suspected Fraud"
//[4]=>  "holded" => "On Hold"
//[5]=>  "payment_review" =>  "Payment Review"
//[6]=>  "pending" => "Pending"
//[7]=>  "pending_payment" => "Pending Payment"
//[8]=>  "pending_paypal" => "Pending PayPal"
//[9]=>  "processing" =>  "Processing"-->