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
$new_record="";
$mapdata = "";
$map_data_json = "";

if (isset($_GET["getmap"]) || isset($_POST["getmap"])) {
	$mapID="columbus"; 
	#$mapID = isset($_GET["getmap"]) ? preg_replace('/^([a-zA-Z\-_]{1,50})/','$1',$_GET["getmap"]) : preg_replace('/^([a-zA-Z\-_]{1,50})/','$1',$_POST["getmap"]);
	include(ABS_PATH . '/conf/config_'.$mapID.'.php');

	try
	{
	
		$dbh = new PDO('sqlite:'.$config['PATH_TO_SQLITE']);
		$sql = $dbh->prepare("SELECT mapdata FROM maprecord where mapid =	'".$mapID."'");
		$sql->execute();
		$map_data_json_array = $sql->fetchAll(); //[0]['mapdata']
		$map_data_json = $map_data_json_array[0]['mapdata'];
	
		if (isset($_POST["addline"])){
			$new_record = '{"latitude":"'.strip_tags($_POST["lat_cell"]).'","longitude":"'.strip_tags($_POST["lng_cell"]).'","itemtitle":"'.strip_tags($_POST["title"]).'","cdmurl":"'.strip_tags($_POST["cdmurl_new"]).'","identifier":"'.strip_tags($_POST["identifier_new"]).'","heading":"'.strip_tags($_POST["heading_cell"]).'","pitch":"'.strip_tags($_POST["pitch_cell"]).'","zoom":"'.strip_tags($_POST["zoom_cell"]).'"}';
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
			header('Location: '.$config['THIS_HOST'].'/index.php?getmap='.$mapID);
		} else if (isset($_POST["updateLine"])) {
			$offset = $_POST["recordno"];
			$update_line ='{"latitude":"'.strip_tags($_POST["lat_cell".$offset]).'","longitude":"'.strip_tags($_POST["lng_cell".$offset]).'","itemtitle":"'.strip_tags($_POST["title".$offset]).'","cdmurl":"'.strip_tags($_POST["cdmurl_".$offset]).'","identifier":"'.strip_tags($_POST["identifier_".$offset]).'","heading":"'.strip_tags($_POST["heading_cell".$offset]).'","pitch":"'.strip_tags($_POST["pitch_cell".$offset]).'","zoom":"'.strip_tags($_POST["zoom_cell".$offset]).'"}';
			$map_data_array = json_decode($map_data_json, true);
			$mapDataTop = json_encode(array_slice($map_data_array, 0, $offset));
			$mapDataAppend = preg_replace('/(^.*)\]$/', '$1,'.$update_line."]", $mapDataTop);
			$newDataTop = json_decode($mapDataAppend, true);
			$new_map_data = json_encode (array_merge($newDataTop, array_slice($map_data_array, $offset+1)));
			$sql = "INSERT OR REPLACE INTO maprecord (mapid, mapdata) VALUES ('".$mapID."', '".$new_map_data."');";
			$dbh->exec($sql);
			header('Location: '.$config['THIS_HOST'].'/index.php?getmap='.$mapID);
		} else if (isset($_POST["deldata"])) {
			$offset = $_POST["recordno"];
			$map_data = json_decode($map_data_json, true);
			$new_map_data = json_encode (array_merge(array_slice($map_data, 0, $offset), array_slice($map_data, $offset+1)));
			$sql = "INSERT OR REPLACE INTO maprecord (mapid, mapdata) VALUES ('".$mapID."', '".$new_map_data."');";
			$dbh->exec($sql);
			header('Location: '.$config['THIS_HOST'].'/index.php?getmap='.$mapID);
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

else{
if(isset($GET_["createmap"]) || isset($_POST["createmap"])){
$mapPath="columbus";
$mapID=strip_tags($_POST["cityName"]);
$mapdata=null;
//call mapID for drop down and $_POST comparison
try	{
	include(ABS_PATH . '/conf/config_'.$mapPath.'.php');

		$dbh = new PDO('sqlite:'.$config['PATH_TO_SQLITE']);
		$sql = $dbh->prepare("SELECT mapid FROM maprecord");# where mapdata <>NULL");
		$sql->execute();
		$map_id_json_array = $sql->fetchAll(); //[0]['mapdata'];
		$map_id_json = $map_id_json_array[0]['mapid'];#[0]['mapdata'];
		$max = count($map_id_json_array);
		
		$sql = "INSERT INTO maprecord (mapid, mapdata) VALUES ('".$mapID."', '".$mapdata."');";
		$dbh->exec($sql);
		header('Location: '.$config['THIS_HOST'].'/index.php?getmap='.$mapID);
}
catch(Exception $e){
				print 'Exception : '.$e->getMessage();
}
$dbh = NULL;
}
else{echo "crap";} 
}

?>
