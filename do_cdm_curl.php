<?php

/**
  * Scripts to manage "Then and Now" map data in an SQLite database
  * Phil Sager <psager@ohiohistory.org>.
  * 
  * Proxy for communicating with CONTENTdm API to get image width, height, and item id
*/

// path to thenandnow folder
define("ABS_PATH", dirname(__FILE__));
if ( isset($_POST['getmap']) || isset($_GET['getmap']) ) {
	$mapref = isset($_GET["getmap"]) ? preg_replace('/^([a-zA-Z\-_]{1,50})/','$1',$_GET["getmap"]) : preg_replace('/^([a-zA-Z\-_]{1,50})/','$1',$_POST["getmap"]);
}
include(ABS_PATH . '/conf/config_'.$mapref.'.php');

$coll = isset($_GET["coll"]) ? $_GET["coll"] : $_POST["coll"];
$ptr = isset($_GET["ptr"]) ? $_GET["ptr"] : $_POST["ptr"];

// get image width and height via dmGetImageInfo
$curl_url = $config['CDM_WEBSERVICES_HOME']."/dmwebservices/index.php?q=dmGetImageInfo/".$coll."/".$ptr."/xml";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $curl_url);
curl_setopt($ch, CURLOPT_HEADER, 0); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$cdm_data_xml = curl_exec($ch);
curl_close($ch);

// get identifier via dmGetItemInfo
$ch = curl_init();
$curl_url = $config['CDM_WEBSERVICES_HOME']."/dmwebservices/index.php?q=dmGetItemInfo/".$coll."/".$ptr."/json";
curl_setopt($ch, CURLOPT_URL, $curl_url);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$cdm_data_json = curl_exec($ch);
curl_close($ch);
$cdm_data_json = str_replace(array("]","["), "", $cdm_data_json);
$cdm_item_array = json_decode($cdm_data_json, true);
$file_name = str_ireplace('.tif','',$cdm_item_array['identi']);

// send it back in one xml doc
$cdm_data_xml = preg_replace('/<\/title><\/imageinfo>/','</title><identifier>'.$file_name.'</identifier></imageinfo>',$cdm_data_xml);
print($cdm_data_xml);

?>