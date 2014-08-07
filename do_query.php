<?php

/**
  * Scripts to manage "Then and Now" map data in an SQLite database
  * Phil Sager <psager@ohiohistory.org>.
  * 
  * Proxy for communicating with sqlite
*/

// path to thenandnow folder
define("ABS_PATH", dirname(__FILE__));

// single blank record
$new_record = '{"latitude":"0","longitude":"0","itemtitle":"Enter a title","cdmurl":"Enter a CDM reference URL","identifier":"Enter a local identifier","heading":"0","pitch":"0","zoom":"0"}';
$mapdata = "";
$map_data_json = "";

if (isset($_GET["getmap"]) || isset($_POST["getmap"])) {
	 
	$mapID = isset($_GET["getmap"]) ? preg_replace('/^([a-zA-Z\-_]{1,50})/','$1',$_GET["getmap"]) : preg_replace('/^([a-zA-Z\-_]{1,50})/','$1',$_POST["getmap"]);
	include(ABS_PATH . '/conf/config_'.$mapID.'.php');

	try
	{
	
		$dbh = new PDO('sqlite:'.$config['PATH_TO_SQLITE']);
		$sql = $dbh->prepare("SELECT mapdata FROM maprecord where mapid =	'".$mapID."'");
		$sql->execute();
		$map_data_json_array = $sql->fetchAll(); //[0]['mapdata']
		$map_data_json = $map_data_json_array[0]['mapdata'];
	
		if (isset($_POST["addline"])){
			// if a record already exists
			if (preg_match('/\{/', $map_data_json)) {
				// append a new record
				$mapdata = preg_replace('/(^.*)\]$/', '$1,'.$new_record."]", $map_data_json);
			} else {
				// insert first record
				$mapdata = "[".$new_record."]";
			}
			$sql = "INSERT OR REPLACE INTO maprecord (mapid, mapdata) VALUES ('".$mapID."', '".$mapdata."');";
			$dbh->exec($sql);
		} else if (isset($_POST["savedata"])) {
			// strips all tags, <script>, html or otherwise. Don't put angle brackets in data...
			$mapdata = strip_tags($_POST["savedata"]);
			$sql = "UPDATE maprecord SET mapdata = '".$mapdata."' WHERE mapid = '".$mapID."';";
			$dbh->exec($sql);
			header('Location: '.$config['THIS_HOST'].'/get_data.php?getmap='.$mapID);
		} else if (isset($_POST["deldata"])) {
			$offset = $_POST["recordno"];
			$map_data = json_decode($map_data_json, true);
			$new_map_data = json_encode (array_merge(array_slice($map_data, 0, $offset), array_slice($map_data, $offset+1)));
			$sql = "INSERT OR REPLACE INTO maprecord (mapid, mapdata) VALUES ('".$mapID."', '".$new_map_data."');";
			$dbh->exec($sql);
			header('Location: '.$config['THIS_HOST'].'/get_data.php?getmap='.$mapID);
		} else {
	    print($map_data_json);
	  } 
	  $dbh = NULL;
	  
	}
	catch(Exception $e)
	{
		print 'Exception : '.$e->getMessage();
	}

}

?>
