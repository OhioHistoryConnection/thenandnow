<?php

/**
  * Script to manage "Then and Now" map data in an SQLite database
  * Phil Sager <psager@ohiohistory.org>.
  * 
*/

$lat = "";
$lon = "";
$CDM_link = "";
$map_data_exists = false;
$mapPath="columbus";
// curl for getting JSON map data
function do_curl($curl_url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $curl_url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 240); // timeout in seconds, no longer than php.ini timeout
	$data_json = curl_exec($ch);
	curl_close($ch);
	return json_decode($data_json, true);
}

// path to thenandnow folder
define("ABS_PATH", dirname(__FILE__));

//call mapID for drop down and $_POST comparison
try	{
	include(ABS_PATH . '/conf/config_'.$mapPath.'.php');

		$dbh = new PDO('sqlite:'.$config['PATH_TO_SQLITE']);
		$sql = $dbh->prepare("SELECT mapid FROM maprecord");# where mapdata <>NULL");
		$sql->execute();
		$map_id_json_array = $sql->fetchAll(); //[0]['mapdata'];
		$map_id_json = $map_id_json_array[0]['mapid'];#[0]['mapdata'];
		$max = count($map_id_json_array);
}
catch(Exception $e){
				print 'Exception : '.$e->getMessage();
}
// get map data
if ( isset($_POST['getmap']) || isset($_GET['getmap']) ) {
	if(isset($_POST['getmap'])){
		$mapID=$_POST['getmap'];
	}
	else if(isset($_GET['getmap'])){
		$mapID= $_GET['getmap'];
	}
	else{
		$mapID =null;
		echo("<script> alert(\"Please enter a valid city to continue.\");</script>");
		$map_data_exists = false;
	}
	if($mapID != null){
		$mapID = isset($_GET["getmap"]) ? preg_replace('/^([a-zA-Z\-_]{1,50})/','$1',$_GET["getmap"]) : preg_replace('/^([a-zA-Z\-_]{1,50})/','$1',$_POST["getmap"]);
		for ($i = 0; $i < $max; $i++) {
			if($map_id_json_array[$i]['mapid'] == $mapID){
				$curl_url = $config['THIS_HOST']."/do_query.php?getmap=" . rawurlencode($mapID);
				// send curl with entry data to an sqlite db somewhere
				$map_data = do_curl($curl_url);
				$map_data_exists = true;
				break;
			}
			$map_data_exists = false;
		}
	}
	
}

?>
	
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Get Street View data</title>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/json2/20130526/json2.min.js"></script>
<script type="text/javascript" src="js/jquery-1.11.1.js"></script>
<script type="text/javascript" src="js/bootstrap.js"></script>
<!--<script type="text/css" src="css/bootstrap.css"></script>-->


<script>
	var mapname = location.search.replace( '?getmap=', '' );
	$(document).ready(function() {
		
		// for purposes of getting a scaled image from CDM reference URL
		$('.getscaled').click(function() {
			var recid = $(this).attr("id");
			var recidno = recid.replace(/.*_(.*)/,"$1");
			// verify thatURL is in the form: http://[CONTENTdm home]/cdm/ref/collection/[alias]/id/[id]
			var cdmrefurl = prompt("Please enter a CONTENTdm reference URL","<?php echo($config['CDMURL_PROMPT']) ?>");
			if (cdmrefurl.indexOf("<?php echo($config['CONTENTDM_HOME']) ?>/cdm/ref/collection") < 0) {
				alert("Need a valid CONTENTdm reference URL");
				return false;
			}
			// get collection alias and id as an array
			var coll_id = cdmrefurl.replace(/^.*collection\/(.*?)\/id\/(.*).*$/,"$1,$2");
			var refvals = coll_id.split(",");
			// use proxy to get image width and height, then scale and fill in
			$.ajax({
				type: "POST",
				data: { coll: refvals[0], ptr: refvals[1], getmap: mapname },
				url: "do_cdm_curl.php",
				success: function(xml) {
					var imgwidth = $(xml).find('width').text();
					var imgheight = $(xml).find('height').text();
					var identifier = $(xml).find('identifier').text();
					var img_size = 340;
					if (imgwidth > imgheight) { img_size = 400; }
					var longest_side = (imgwidth > imgheight) ? imgwidth : imgheight;
					var trimmed_scale = "20";
					var scale = img_size/longest_side;
					var targ_w = (imgwidth*scale).toFixed(2);
					var targ_h = (imgheight*scale).toFixed(2);
					var formatted_scale = (scale * 100).toFixed(2);
					if (imgwidth < img_size) { formatted_scale = 100; }
					var imgPath = "<?php echo($config['CONTENTDM_HOME']) ?>/utils/ajaxhelper/?CISOROOT=" + refvals[0] + '&CISOPTR=' + refvals[1] + '&action=2&DMSCALE=' + formatted_scale + '&DMWIDTH=' + targ_w + '&DMHEIGHT=' + targ_h + '&DMX=0&DMY=0';
					$('#cdmurl_' + recidno).attr("value", imgPath);
					$('#identifier_' + recidno).attr("value", identifier);
				}
				
			});
			
		});
		
		// use Google Geocoder to get latitude and longitude based on an address
		$('.getcoordinates').click(function() {
			var recid = $(this).attr("id");
			var recidno = recid.replace(/.*_(.*)/,"$1");
			var streetAddress = prompt("Please enter a street address","88 South High Street, Columbus, OH");
			var mygc = new google.maps.Geocoder();
			mygc.geocode({'address' : streetAddress}, function(results, status){
		    $('#latitude_' + recidno).attr("value", results[0].geometry.location.lat());
		    $('#longitude_' + recidno).attr("value", results[0].geometry.location.lng());
			});
		});
		
		// creates side by side view for historic image and Google Street View
		$('.changeRecord').click(function() {
			
			$('#pano').empty();
			$('#imageview').empty();
			
			var recid = $(this).attr("id");
			var recidno = recid.replace(/.*_(.*)/,"$1");
			var maplat = "#latitude_" + recidno;
			
			var lat = $('#latitude_' + recidno).val();
			var lon = $('#longitude_' + recidno).val();

			if (!parseInt(lat) || !parseInt(lon)) {
				alert("Need both a latitude and longitude");
				return false;
			}
			
			var povHead = 0;
			var povPitch = 0;
			var povZoom = 0;
			var imgPath = $('#cdmurl_' + recidno).val();
			if (imgPath.indexOf("<?php echo($config['CONTENTDM_HOME']) ?>/utils/ajaxhelper/?CISOROOT=") < 0) {
				alert("Need a valid CONTENTdm scaled image URL");
				return false;
			}
			
			$('#streetwrapper').show();
			
      var thumbImg = $('<img />', {
        src: imgPath
      });
      $('#imageview').append(thumbImg);
			var mapPos = new google.maps.LatLng(lat,lon);
			var panoramaOptions = {
	      position: mapPos,
	      pov: {
	        heading: povHead,
	        pitch: povPitch,
	        zoom: povZoom
	      },
	      visible: true
	    };
	    var panorama = new google.maps.StreetViewPanorama(document.getElementById("pano"), panoramaOptions);
			
			// listen for changes to panorama area and update input blank values as needed
	    google.maps.event.addListener(panorama, 'position_changed', function() {
	      $('#lat_cell').val(panorama.getPosition().lat().toString());
	      $('#lng_cell').val(panorama.getPosition().lng().toString());      
	    });
	    google.maps.event.addListener(panorama, 'pov_changed', function() {
	      $('#heading_cell').val(panorama.getPov().heading);
	      $('#pitch_cell').val(panorama.getPov().pitch);
	      $('#zoom_cell').val(panorama.getPov().zoom);
	    });
				
		});
		
		// serialize all values and store them somewhere (in this case SQLite)
		$('#formsave').submit(function(e) {
			e.preventDefault();
			if ($('#savemap') == null || $('#savemap').val().length == 0) { 
				alert("Please enter a map name"); 
				return false; 
			}
			var mapname = $("#savemap").val();
			var numRows = $(".mapdatarow").length;
			var mapDataArray = new Array(numRows);
			for (i = 0; i < numRows; i++) {
				var mapDataRow = {};
				mapDataRow.latitude = $('#latitude_' + i).val();
				mapDataRow.longitude = $('#longitude_' + i).val();
				mapDataRow.itemtitle = $('#itemtitle_' + i).val();
				mapDataRow.cdmurl = $('#cdmurl_' + i).val();
				mapDataRow.identifier = $('#identifier_' + i).val();
				mapDataRow.heading = $('#heading_' + i).val();
				mapDataRow.pitch = $('#pitch_' + i).val();
				mapDataRow.zoom = $('#zoom_' + i).val();
				mapDataArray[i] = mapDataRow;
			}
			
			var mapDataArrayJSON = JSON.stringify(mapDataArray);
			
			var request = $.ajax({
				type: "POST",
				data: { getmap: mapname, savedata: mapDataArrayJSON },
				url: "do_query.php",
				success: function(data) {
					$('#saved').html('<b>saved!</b>');
					setTimeout("$('#saved').empty()",1000);
				}
			});
			
		});
		
		$('#bottomformsave').submit(function(e) {
			e.preventDefault();
			if ($('#savemap') == null || $('#savemap').val().length == 0) { 
				alert("Please enter a map name"); 
				return false; 
			}
			var mapname = $("#savemap").val();
			var numRows = $(".mapdatarow").length;
			var mapDataArray = new Array(numRows);
			for (i = 0; i < numRows; i++) {
				var mapDataRow = {};
				mapDataRow.latitude = $('#latitude_' + i).val();
				mapDataRow.longitude = $('#longitude_' + i).val();
				mapDataRow.itemtitle = $('#itemtitle_' + i).val();
				mapDataRow.cdmurl = $('#cdmurl_' + i).val();
				mapDataRow.identifier = $('#identifier_' + i).val();
				mapDataRow.heading = $('#heading_' + i).val();
				mapDataRow.pitch = $('#pitch_' + i).val();
				mapDataRow.zoom = $('#zoom_' + i).val();
				mapDataArray[i] = mapDataRow;
			}
			
			var mapDataArrayJSON = JSON.stringify(mapDataArray);
			
			var request = $.ajax({
				type: "POST",
				data: { getmap: mapname, savedata: mapDataArrayJSON },
				url: "do_query.php",
				success: function(data) {
					$('#saved').html('<b>saved!</b>');
					setTimeout("$('#saved').empty()",1000);
				}
			});
			
		});
		
		// set input value on blur
		$('.mapinput').blur(function() {
			var setDomVal = $(this).val();
			$(this).attr("value", setDomVal);
		});
		
		// show the "Then and Now" map in a separate window
		$("#formshow").submit(function() {
			if ($('#showmap') == null || $('#showmap').val().length == 0) { 
				alert("Please enter a map name"); 
				return false; 
			} 
			window.open("<?php echo($config['THIS_HOST']) ?>/thenandnow.php?getmap=" + $('#showmap').val());
    	return false; 
		});
		$("#bottomformshow").submit(function() {
			if ($('#bottomshowmap') == null || $('#bottomshowmap').val().length == 0) { 
				alert("Please enter a map name"); 
				return false; 
			} 
			window.open("<?php echo($config['THIS_HOST']) ?>/thenandnow.php?getmap=" + $('#bottomshowmap').val());
    	return false; 
		});
		// when "add line" is clicked either start a new map or add a new line
		$("#formadd").submit(function (e) {
			if ($('#getmap') == null || $('#getmap').val().length == 0) { 
				var mapname = prompt("Type the name of your map (no spaces or punctuation)", "MyMap");
			} else {
				var mapname = $('#getmap').val();
			}
			if (mapname != '' && mapname != null) {
				$.ajax({
					type: "POST",
					data: { getmap: mapname, addline: "addline" },
					url: "do_query.php",
					success: function(data) {
						location.href = "<?php echo($config['THIS_HOST']) ?>/index.php?getmap=" + mapname;
					}
				});	
			} else {
				return false;
			}
			return false;
		});
		$("#bottomformadd").submit(function (e) {
			if ($('#getmap') == null || $('#getmap').val().length == 0) { 
				var mapname = prompt("Type the name of your map (no spaces or punctuation)", "MyMap");
			} else {
				var mapname = $('#getmap').val();
			}
			if (mapname != '' && mapname != null) {
				$.ajax({
					type: "POST",
					data: { getmap: mapname, addline: "addline" },
					url: "do_query.php",
					success: function(data) {
						location.href = "<?php echo($config['THIS_HOST']) ?>/index.php?getmap=" + mapname;
					}
				});	
			} else {
				return false;
			}
			return false;
		});
		// make sure map name is entered
		$("#formgetmap").submit(function() {
			if ($('#getmap') == null || $('#getmap').val().length == 0) { 
				alert("Please enter a map name");
				return false;
			}
		});
			
	});
	
	// delete a line
	function confirmDelete() {
		var answer = confirm("Are you sure?");
		if (answer) {
			return true;
		} else {
			return false;
		}
	}
	
</script>
<style type="text/css">
body { 
	font-family: Arial, Verdana, Geneva;
	font-size: 90%; 
	background-image:url(img/1-1249480650QT9U.jpg);
	/* picture src: http://www.publicdomainpictures.net/view-image.php?image=3560&picture=paper-background&large=1 */
	background-color: #cccccc;
}
.mapinput {
	font-size: 80%;
}
.title {
	text-align:center;
	padding: 10px;
}
th {
	font-size: 90%;
}
#formgetmap { 
  display: inline-block;
  font-size: 140%;
  margin-top: 10em;
  background: #F8F8F8;
  border: solid;
  padding: 6em;
}
#formgetmap b{
	font-size: 230%;
	background: default;
}
#formshow {
	display: inline-block;
}
#formadd { 
  display: inline-block;
}
#formsave {
	display: inline-block;
}
#bottomformshow {
	display: inline-block;
}
#bottomformadd { 
  display: inline-block;
}
#bottomformsave {
	display: inline-block;
}

#streetwrapper {
	/*float:left;*/
	width:100%;
	height:500px;
	text-align: center;
}
#panoInfo {
	/*width: 420px;*/ 
	/*height: 370px;*/
	/*float:left;*/
}
#panoblock {
	height: 420px;
	width:900px;
	margin-left:auto;
	margin-right:auto;
	/*display: inline;*/
}
#pano {
	width: 420px; 
	height: 370px;
	padding:10px;
	/*margin-right: -800px;*/
	float:left;
	display: inline-block;
}
#imageview {
	padding:10px;
	width: 420px; 
	height: 370px;
	float:left;
	display: inline;
	/*display: inline-block;*/
}
th {
	text-align: left;
}
</style>
<link media="ALL" rel="stylesheet" type="text/css" href="css/bootstrap.css"></link>

</head>
<body>
	<div class = "container-fluid"><!--bootstrap!-->
	<div id="output"></div>
	<div class="title">
		<form class="form-inline" role="form" id="formgetmap" name="formgetmap" method="POST" action="index.php">
			<b>"Then and Now" Map Helper</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<br><br>
			<p id="intro">
			"Then and Now" is an application designed to bring together Google Street Views
with historic photos stored in the Ohio Memory Collection.<!--a CONTENTdm collection. It makes use of an SQLite
database (but could easily be substituted by any other means of storing/retrieving 
JSON data).--><br><br>

			Please choose the name of the city to retrieve the relevant images and markers.</p>
			<div class="input-append">
			<?php
				echo"<select class=\"form-control\" name=\"getmap\" id=\"getmap\">";
				if($map_data_exists == true){
					echo"<option value= \"". $mapID ."\" selected><strong>Current Map: ". ucfirst($mapID) ."</strong></option>";
					echo"<option class=\"hr\" disabled=\"disabled\">-----------------------------</option>";
					echo"<option class=\"hr\" disabled=\"disabled\"><em>Maps Available</em></option>";
				}
				for ($i = 0; $i < $max; $i++) {
					if($map_id_json_array[$i]['mapid'] <> $mapID){
						echo "<option value= \"".$map_id_json_array[$i]['mapid']."\">".ucfirst($map_id_json_array[$i]['mapid'])."</option>";
					}
				}
				echo "</select>";
			?>
			<button type="submit" class="btn btn-default" name="getdata" value="Get Map Data">Get Map Data</button> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<!--<input type="submit" name="getdata" value="Get Map Data"> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-->
			</div>
		</form>
	</div><br><br>
				<?php 
				if ($map_data_exists) { ?>
										
					<style>
						#formgetmap{
							font-size: 100%;
							display: inline-block;
							margin-top: 0;
							background:inherit;
							padding: 0 3em;
							border: none;
						}
						#formgetmap b{
						text-decoration: none;
						}
						#intro{
							display: none;
						}
						
					</style>
					<div class="title">
						<form id="formshow" name="formshow">
							<input type="hidden" id="showmap" name="showmap" value="<?php echo $mapID?>">
							<button type="submit" class="btn btn-default" id="showsubmit" name="showsubmit" value="<?php echo $mapID?>">Show Map</button>
							<!--<input type="submit" id="showsubmit" name="showsubmit" value="Show Map">-->
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						</form>
						<form id="formadd" name="formadd" method="POST" action="do_query.php">
							<input type="hidden" name="getmap" value="<?php echo $map_data_exists ? $mapID : '' #$mapref : '' ?>">
							<button type="submit" class="btn btn-default" name="addline" value="Add Line">Add Line</button>
							<!--<input type="submit" name="addline" value="Add Line">-->
						</form>
						<form id="formsave" name="formsave">
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<input type="hidden" id="savemap" name="savemap" value="<?php echo $map_data_exists ? $mapID : '' #$mapref : '' ?>">
							<button type="submit" class="btn btn-default" id="savesubmit" name="savesubmit" value="Save Map Data">Save Map Data</button>&nbsp;&nbsp;<span id="saved"></span>
							<!--<input type="submit" id="savesubmit" name="savesubmit" value="Save Map Data">&nbsp;&nbsp;<span id="saved"></span>-->
						</form>
					</div><br>
					<div class="table table-responsive">
					<table class="table table-hover"><!--twitter bootstrap-->

			<?php
					
					echo('<tr class="info"><th></th><th>Latitude:</th><th>Longitude:</th><th>Title:</th><th></th><th>CDM scaled image:</th><th>Identifier:</th><th></th><th>Heading:</th><th>Pitch:</th><th>Zoom:</th><th></th></tr>');
					$max = count($map_data);
					for ($i = 0; $i < $max; $i++) {		
			?>
			
						<tr class="mapdatarow">
						<td><input type="submit" class="getcoordinates" id="getcoords_<?php echo $i ?>" value="Get lat/lng"></td>
						<td><input title="Map Record <?php echo $i ?>" class="mapinput" type="text" name="latitude" id="latitude_<?php echo $i ?>" value="<?php echo $map_data[$i]["latitude"] ?>" size="10"></td>
						<td><input title="Map Record <?php echo $i ?>" class="mapinput" type="text" name="longitude" id="longitude_<?php echo $i ?>" value="<?php echo $map_data[$i]["longitude"] ?>" size="10"> </td>
						<td><input title="Map Record <?php echo $i ?>" class="mapinput" type="text" name="itemtitle" id="itemtitle_<?php echo $i ?>" value="<?php echo $map_data[$i]["itemtitle"]; ?>"  style="wordwrap:initial; width:20 height:60 px"> <!--size="50">--> </td>
						<td><input type="submit" class="getscaled" id="getscaled_<?php echo $i ?>" value="Scale image"></td>
						<td><input title="Map Record <?php echo $i ?>" class="mapinput" type="text" name="cdmurl" id="cdmurl_<?php echo $i ?>" value="<?php echo $map_data[$i]["cdmurl"] ?>"> </td>
						<td><input title="Map Record <?php echo $i ?>" class="mapinput" type="text" name="identifier" id="identifier_<?php echo $i ?>" value="<?php echo $map_data[$i]["identifier"] ?>"> </td>
						<td><input type="submit" class="changeRecord" id="change_<?php echo $i ?>" value="Set orientation"></td>
						<td><input title="Map Record <?php echo $i ?>" class="mapinput" type="text" name="heading" id="heading_<?php echo $i ?>" value="<?php echo $map_data[$i]["heading"] ?>" size="5"> </td>
						<td><input title="Map Record <?php echo $i ?>" class="mapinput" type="text" name="pitch" id="pitch_<?php echo $i ?>" value="<?php echo $map_data[$i]["pitch"] ?>" size="5"> </td>
						<td><input title="Map Record <?php echo $i ?>" class="mapinput" type="text" name="zoom" id="zoom_<?php echo $i ?>" value="<?php echo $map_data[$i]["zoom"] ?>" size="2"> </td>
						<td><form method="POST" action="do_query.php" onsubmit="return confirmDelete()"><input type="hidden" name="getmap" value="<?php echo $map_data_exists ? $mapID : '' ?>"><input type="hidden" name="recordno" value="<?php echo $i ?>"><input type="submit" name="deldata" value="Delete"></a></form></td></tr>
				
			<?php	} 
					echo '<tr class="info"><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th></tr>';
				} ?>
			
	</table>
	</div>
	<?php if ($map_data_exists) { ?>
	
	<hr/>
	<div class="title">
		<form id="bottomformshow" name="bottomformshow">
				<input type="hidden" id="bottomshowmap" name="bottomshowmap" value="<?php echo $mapID?>">
				<button type="submit" class="btn btn-default" id="showsubmit" name="showsubmit" value="Show Map">Show Map</button>
				<!--<input type="submit" id="showsubmit" name="showsubmit" value="Show Map">-->
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			</form>
			<form id="bottomformadd" name="bottomformadd" method="POST" action="do_query.php">
				<input type="hidden" name="getmap" value="<?php echo $map_data_exists ? $mapID : '' ?>">
				<button type="submit" class="btn btn-default" name="addline" value="Add Line">Add Line</button>
				<!--<input type="submit" name="addline" value="Add Line">-->
			</form>
			<form id="bottomformsave" name="bottomformsave">
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<input type="hidden" id="savemap" name="savemap" value="<?php echo $map_data_exists ? $mapID : '' #$mapref : '' ?>">
				<button type="submit" class="btn btn-default" id="savesubmit" name="savesubmit" value="Save Map Data">Save Map Data</button>&nbsp;&nbsp;<span id="saved"></span>
				<!--<input type="submit" id="savesubmit" name="savesubmit" value="Save Map Data">&nbsp;&nbsp;<span id="saved"></span>-->
			</form>
	</div>
	<br>
	<div id="streetwrapper" style="display:none">
		<div id="panoInfo">
	  	&nbsp;&nbsp;&nbsp;latitude: <input type="text" id="lat_cell" size="10">, longitude: <input type="text" id="lng_cell" size="10">
	  	&nbsp;&nbsp;&nbsp;heading: <input type="text" id="heading_cell" size="9">, 
	  	pitch: <input type="text" id="pitch_cell" size="9">, 
	  	zoom: <input type="text" id="zoom_cell" size="1">
	  </div>
	  <br/>
		<div id="panoblock">	
		  <div id="pano"></div>
		  <div id="imageview"></div>
	  </div>
	</div>
	</div><!--container-fluid!-->
	<?php } ?>
	
</body>
</html>


