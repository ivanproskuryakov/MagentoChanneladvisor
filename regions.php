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


$countryName = Mage::getModel('directory/country')->load('US')->getName(); //get country name


$states = Mage::getModel('directory/country')->load('US')->getRegions();

//state names

foreach ($states as $state) {
    echo "	array('name'=>'" . $state->getCode() . "', 'abbrev'=>'" . $state->getName() . "'),<br/>";
}

?>
