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
// get map data
if ( isset($_POST['getmap']) || isset($_GET['getmap']) ) {
	$mapref = isset($_GET["getmap"]) ? preg_replace('/^([a-zA-Z\-_]{1,50})/','$1',$_GET["getmap"]) : preg_replace('/^([a-zA-Z\-_]{1,50})/','$1',$_POST["getmap"]);
	include(ABS_PATH . '/conf/config_'.$mapref.'.php');
	$curl_url = $config['THIS_HOST']."/do_query.php?getmap=" . rawurlencode($mapref);
	// send curl with entry data to an sqlite db somewhere
	$map_data = do_curl($curl_url);
	$map_data_exists = true; 
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
		
		// when "add line" is clicked either start a new map or add a new line
		$("#formadd").submit(function(e) {
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
						location.href = "<?php echo($config['THIS_HOST']) ?>/get_data.php?getmap=" + mapname;
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
#streetwrapper {
	/*float:left;*/
	width:100%;
	text-align: center;
}
#panoInfo {
	/*width: 420px;*/ 
	/*height: 370px;*/
	/*float:left;*/
}
#panoblock {

}
#pano {
	width: 420px; 
	height: 370px;
	padding:10px;
	display: inline-block;
}
#imageview {
	padding:10px;
	display: inline-block;
}
th {
	text-align: left;
}
</style>
</head>
<body>
	<div id="output"></div>
	<div class="title">
		<form id="formgetmap" name="formgetmap" method="POST" action="get_data.php">
			<b>"Then and Now" Map Helper</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<input type="text" class="mapinput" name="getmap" id="getmap" size="10" value="<?php echo $map_data_exists ? $mapref : '' ?>">
			<input type="submit" name="getdata" value="Get Map Data"> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		</form>
		<form id="formshow" name="formshow">
			<input type="hidden" id="showmap" name="showmap" value="<?php echo $map_data_exists ? $mapref : '' ?>">
			<input type="submit" id="showsubmit" name="showsubmit" value="Show Map">
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		</form>
		<form id="formadd" name="formadd" method="POST" action="do_query.php">
			<input type="hidden" name="getmap" value="<?php echo $map_data_exists ? $mapref : '' ?>">
			<input type="submit" name="addline" value="Add Line">
		</form>
		<form id="formsave" name="formsave">
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<input type="hidden" id="savemap" name="savemap" value="<?php echo $map_data_exists ? $mapref : '' ?>">
			<input type="submit" id="savesubmit" name="savesubmit" value="Save Map Data">&nbsp;&nbsp;<span id="saved"></span>
		</form>
	</div>

	<table>
			<?php 
				if ($map_data_exists) {
					echo('<tr><th></th><th>Latitude:</th><th>Longitude:</th><th>Title:</th><th></th><th>CDM scaled image:</th><th>Identifier:</th><th></th><th>Heading:</th><th>Pitch:</th><th>Zoom:</th></tr>');
					$max = count($map_data);
					for ($i = 0; $i < $max; $i++) {		
			?>
			
						<tr class="mapdatarow">
						<td><input type="submit" class="getcoordinates" id="getcoords_<?php echo $i ?>" value="Get lat/lng"></td>
						<td><input title="Map Record <?php echo $i ?>" class="mapinput" type="text" name="latitude" id="latitude_<?php echo $i ?>" value="<?php echo $map_data[$i]["latitude"] ?>" size="10"></td>
						<td><input title="Map Record <?php echo $i ?>" class="mapinput" type="text" name="longitude" id="longitude_<?php echo $i ?>" value="<?php echo $map_data[$i]["longitude"] ?>" size="10"> </td>
						<td><input title="Map Record <?php echo $i ?>" class="mapinput" type="text" name="itemtitle" id="itemtitle_<?php echo $i ?>" value="<?php echo $map_data[$i]["itemtitle"]; ?>" size="50"> </td>
						<td><input type="submit" class="getscaled" id="getscaled_<?php echo $i ?>" value="Scale image"></td>
						<td><input title="Map Record <?php echo $i ?>" class="mapinput" type="text" name="cdmurl" id="cdmurl_<?php echo $i ?>" value="<?php echo $map_data[$i]["cdmurl"] ?>"> </td>
						<td><input title="Map Record <?php echo $i ?>" class="mapinput" type="text" name="identifier" id="identifier_<?php echo $i ?>" value="<?php echo $map_data[$i]["identifier"] ?>"> </td>
						<td><input type="submit" class="changeRecord" id="change_<?php echo $i ?>" value="Set orientation"></td>
						<td><input title="Map Record <?php echo $i ?>" class="mapinput" type="text" name="heading" id="heading_<?php echo $i ?>" value="<?php echo $map_data[$i]["heading"] ?>" size="5"> </td>
						<td><input title="Map Record <?php echo $i ?>" class="mapinput" type="text" name="pitch" id="pitch_<?php echo $i ?>" value="<?php echo $map_data[$i]["pitch"] ?>" size="5"> </td>
						<td><input title="Map Record <?php echo $i ?>" class="mapinput" type="text" name="zoom" id="zoom_<?php echo $i ?>" value="<?php echo $map_data[$i]["zoom"] ?>" size="2"> </td>
						<td><form method="POST" action="do_query.php" onsubmit="return confirmDelete()"><input type="hidden" name="getmap" value="<?php echo $map_data_exists ? $mapref : '' ?>"><input type="hidden" name="recordno" value="<?php echo $i ?>"><input type="submit" name="deldata" value="Delete"></a></form></td></tr>
				
		<?php	} 
					
				} ?>
			
	</table>
	
	<?php if ($map_data_exists) { ?>
	
	<hr/>
	<div id="streetwrapper">
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
	
	<?php } ?>
	
</body>
</html>


