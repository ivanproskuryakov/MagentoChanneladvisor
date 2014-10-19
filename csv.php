<?php
ini_set("auto_detect_line_endings", true);
set_time_limit(0);
error_reporting(E_ALL);
include_once 'settings.php';
include_once 'csv.scan.class.php';
include_once 'states.php';


$Scan = new Scan();
//$result = $Scan->updateCsvFromRemoteFTP(FTP_SERVER,FTP_USER,FTP_PASSWORD); // FTP DOES NOT WORKING 
//exit();


$csv_files = $Scan->ScanDirByTime(IMPORT_DIR);
if (!$csv_files) exit('no files');
foreach ($csv_files as $_file) {
    break;
}
define('IMPORT_FILE', $_file);
$CSV = $Scan->BuildArrayFromCSV(IMPORT_DIR . '/' . IMPORT_FILE);
unset($CSV[0]);

$log = '';


/* ########################################
 * SYNC SKUS
 */
$client = new SoapClient("http://api.channeladvisor.com/ChannelAdvisorAPI/v7/InventoryService.asmx?WSDL");
$headersData = array('DeveloperKey' => DEVELOPERKEY, 'Password' => PASSWORD);
$head = new SoapHeader("http://api.channeladvisor.com/webservices/", "APICredentials", $headersData);
$client->__setSoapHeaders($head);
foreach ($CSV as $_item) {

    $line = array();
    $line['SKU'] = $_item[6];
    $line['PRODUCT_NAME'] = $_item[7];

    $item = array();
    $item['Sku'] = $line['SKU'];
    $item['Title'] = $line['PRODUCT_NAME'];


    // check sku exists
    $result = $client->DoesSkuExist(array('accountID' => ACCOUNTID, 'sku' => '1' . $item['Sku']));
    $skuExists = $result->DoesSkuExistResult->ResultData;

    // synch sku 
    if ($skuExists == false) {
        $result = $client->SynchInventoryItem(array('accountID' => ACCOUNTID, 'item' => $item));
        $log .= 'SKU=>' . $line['SKU'] . ' STATUS=> ' . $result->SynchInventoryItemResult->Status . '<br/>';
    }


}


/*##################################################
 * MERGE LINES
 */
$sales = array();
foreach ($CSV as $_item) {
    $line = array();
    $line['ORDER_ID'] = $_item[0];
    $line['ORDER_ITEM_ID'] = $_item[1];
    $line['PURCHASE_DATE'] = $_item[2];
    $line['PAYMENTS_DATE'] = $_item[3];

    if (!$line['PAYMENTS_DATE']) $line['PAYMENTS_DATE'] = $line['PURCHASE_DATE'];
    $line['CUSTOMER_NAME'] = $_item[4];
    $line['CUSTOMER_PHONE_NUMBER'] = $_item[5];
    $_cartLine = array();
    $_cartLine['ORDER_ITEM_ID'] = $_item[6];
    $_cartLine['SKU'] = $_item[6];
    $_cartLine['SKU'] = $_item[6];
    $_cartLine['SKU'] = $_item[6];
    $_cartLine['PRODUCT_NAME'] = $_item[7];
    $_cartLine['QUANTITY_PURCHASED'] = $_item[8];
    $_cartLine['ITEM_PRICE'] = $_item[9];
    $_cartLine['ITEM_TAX'] = $_item[10];
    $_cartLine['SHIPPING_PRICE'] = $_item[11];
    $_cartLine['SHIPPING_TAX'] = $_item[12];
    $line['CART'][] = $_cartLine;
    $line['SHIP_SERVICE_LEVEL'] = $_item[13];
    $line['RECEPIENT_NAME'] = $_item[14];
    $line['SHIP_ADDRESS_1'] = $_item[15];
    $line['SHIP_ADDRESS_2'] = $_item[16];
    $line['SHIP_ADDRESS_3'] = $_item[17];
    $line['SHIP_CITY'] = $_item[18];
    $line['SHIP_STATE'] = $_item[19];
    $line['SHIP_POSTAL_CODE'] = $_item[20];
    $line['SHIP_COUNTRY'] = $_item[21];
    $line['SHIP_TO_PHONE_NUMBER'] = $_item[22];

    $_found = false;
    foreach ($sales as $k => $_sale) {

        if ($_sale['ORDER_ID'] == $line['ORDER_ID']) {
            $_sale['CART'][] = $line['CART'][0];
            $sales[$k]['CART'] = $_sale['CART'];
            $_found = true;
        }
    }
    if (!$_found) $sales[] = $line;

}


/*##################################################
 * ADD SALES
 */

$client = new SoapClient("http://api.channeladvisor.com/ChannelAdvisorAPI/v7/OrderService.asmx?WSDL");
$headersData = array('DeveloperKey' => DEVELOPERKEY, 'Password' => PASSWORD);
$head = new SoapHeader("http://api.channeladvisor.com/webservices/", "APICredentials", $headersData);
$client->__setSoapHeaders($head);
foreach ($sales as $line) {


    $order = array();

    /*
     * Time
     */
    $_date = date('Y-m-d', strtotime($line['PURCHASE_DATE']));
    $_time = date('H:i:s', strtotime($line['PURCHASE_DATE']));
    $_datetime = $_date . 'T' . $_time;
    $order['OrderTimeGMT'] = $_datetime;
    $order['ClientOrderIdentifier'] = $line['ORDER_ID'];

    /*
     * Status
     */
    $OrderStatus = array();
    $OrderStatus['CheckoutStatus'] = 'Completed';
    $OrderStatus['CheckoutDateGMT'] = $_datetime;
    $OrderStatus['PaymentStatus'] = 'Cleared';
    $OrderStatus['PaymentDateGMT'] = $_datetime;
    $OrderStatus['ShippingStatus'] = 'Unshipped';
    $OrderStatus['ShippingDateGMT'] = $_datetime;

    $order['OrderStatus'] = $OrderStatus;

    /*
     * ....
     */
    $order['BuyerEmailAddress'] = 'noemail@inthis.csv';
    $order['EmailOptIn'] = 'false';


    /*
     * Billing
     */
    $BillingInfo = array();
    $BillingInfo['AddressLine1'] = $line['SHIP_ADDRESS_1'];
    $BillingInfo['AddressLine2'] = $line['SHIP_ADDRESS_2'];
    $BillingInfo['City'] = $line['SHIP_CITY'];

    $state = $line['SHIP_STATE'];
    if (strlen($line['SHIP_STATE']) > 2) {
        $state = convert_state(ucfirst($line['SHIP_STATE']), 'abbrev');
    }

    $BillingInfo['Region'] = $state;
    $BillingInfo['PostalCode'] = $line['SHIP_POSTAL_CODE'];
    $BillingInfo['CountryCode'] = 'US';
//    $_name= explode(' ',$line['CUSTOMER_NAME']);
//    $BillingInfo['FirstName'] = $line['CUSTOMER_NAME'];;
//    $BillingInfo['LastName'] = '';
    $_name = explode(' ', $line['CUSTOMER_NAME']);
    $BillingInfo['FirstName'] = $_name[0];
    $BillingInfo['LastName'] = $_name[1];
    $BillingInfo['PhoneNumberDay'] = $line['SHIP_TO_PHONE_NUMBER'];
    $BillingInfo['PhoneNumberEvening'] = $line['SHIP_TO_PHONE_NUMBER'];
    $order['BillingInfo'] = $BillingInfo;


    /*
     * Payment
     */
    $PaymentInfo = array();
    $PaymentInfo['PaymentType'] = 'PO';
    $PaymentInfo['PaymentTransactionID'] = $line['ORDER_ID'];
    $order['PaymentInfo'] = $PaymentInfo;


    /*
     * ShoppingCart
     */
    $ShoppingCart = array();
    $ShoppingCart['CartID'] = 0;
    $ShoppingCart['CheckoutSource'] = 'Unspecified';
//    $ShoppingCart['VATTaxCalculationOption'] = 'VAT_INCLUSIVE';
//    $ShoppingCart['VATShippingOption'] = 'VAT_INCLUSIVE';
    $ShoppingCart['VATGiftWrapOption'] = 'Unspecified';

    $LineItemSKUList = array();
    foreach ($line['CART'] as $_cartItem) {
        $OrderLineItemItem = array();
        $OrderLineItemItem['UnitPrice'] = $_cartItem['ITEM_PRICE'];
        $OrderLineItemItem['LineItemID'] = $_cartItem['ORDER_ITEM_ID'];
        $OrderLineItemItem['AllowNegativeQuantity'] = 'false';
        $OrderLineItemItem['Quantity'] = $_cartItem['QUANTITY_PURCHASED'];
        $OrderLineItemItem['SKU'] = $_cartItem['SKU'];
        $OrderLineItemItem['BuyerFeedbackRating'] = '';
        $OrderLineItemItem['VATRate'] = 0;
        $OrderLineItemItem['TaxCost'] = floatval($_cartItem['ITEM_TAX']);
        $OrderLineItemItem['ShippingCost'] = floatval($_cartItem['SHIPPING_PRICE']);
        $OrderLineItemItem['ShippingTaxCost'] = floatval($_cartItem['SHIPPING_TAX']);

//                $OrderLineItemItem['TaxCost'] = $_cartItem['ITEM_TAX'];
//                $OrderLineItemItem['ShippingCost'] = $_cartItem['SHIPPING_PRICE'] + $_cartItem['SHIPPING_TAX'];                
        $LineItemSKUList['OrderLineItemItem'][] = $OrderLineItemItem;
    }

    $ShoppingCart['LineItemSKUList'] = $LineItemSKUList;

    $OrderLineItemInvoice2 = array();
    $OrderLineItemInvoice2['LineItemType'] = 'Shipping';
    $OrderLineItemInvoice2['UnitPrice'] = '4.99';
    $ShoppingCart['LineItemInvoiceList'] = array();
    $ShoppingCart['LineItemInvoiceList'][] = $OrderLineItemInvoice2;

    $order['ShoppingCart'] = $ShoppingCart;


    /*
     * Invoices
     */
    $LineItemInvoiceList = array();

    foreach ($line['CART'] as $_cartItem) {
        $_item = array();
        $_item['SalesTax'] = floatval($_cartItem['ITEM_TAX']);
        $_item['Shipping'] = floatval($_cartItem['SHIPPING_PRICE']);
        $_item['VATShipping'] = floatval($_cartItem['SHIPPING_TAX']);
        $LineItemInvoiceList[] = $_item;
    }

    $order['LineItemInvoiceList'] = $LineItemInvoiceList;

    /*
     * ShippingInfo
     */
    $ShippingInfo = array();
    $ShippingInfo['AddressLine1'] = $line['SHIP_ADDRESS_1'];
    $ShippingInfo['AddressLine2'] = $line['SHIP_ADDRESS_2'];
    $ShippingInfo['City'] = $line['SHIP_CITY'];
    $ShippingInfo['Region'] = $state;
    $ShippingInfo['PostalCode'] = $line['SHIP_POSTAL_CODE'];
    $ShippingInfo['CountryCode'] = 'US';
    $_name = explode(' ', $line['RECEPIENT_NAME']);
    $ShippingInfo['FirstName'] = $_name[0];
    $ShippingInfo['LastName'] = $_name[1];
    $ShippingInfo['PhoneNumberDay'] = $line['SHIP_TO_PHONE_NUMBER'];
    $ShippingInfo['PhoneNumberEvening'] = $line['SHIP_TO_PHONE_NUMBER'];
    $order['ShippingInfo'] = $ShippingInfo;
//    

    $result = $client->SubmitOrder(array('accountID' => ACCOUNTID, 'order' => $order));
    $log .= 'ORDER=>' . $line['ORDER_ID'] . ' STATUS=> ' . $result->SubmitOrderResult->Status . '<br/>';

//    var_dump($order);
//    var_dump($log);
//    exit();    


}


/*
 * MOVE FILE TO HISTORY FOLDER
 * 
 */
rename(IMPORT_DIR . IMPORT_FILE, IMPORT_DIR_HISTORY . IMPORT_FILE);
$log .= 'Total sales: ' . count($CSV) . '<br/>';
echo $log;
mail(EMAIL_TO, EMAIL_SUBJECT, $log, EMAIL_HEADERS);

exit();
?>