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
	$mapID = isset($_GET["getmap"]) ? preg_replace('/^([a-zA-Z\-_]{1,50})/','$1',$_GET["getmap"]) : preg_replace('/^([a-zA-Z\-_]{1,50})/','$1',$_POST["getmap"]);
		for ($i = 0; $i < $max; $i++) {
			if($map_id_json_array[$i]['mapid'] == $mapID){
				$curl_url = $config['THIS_HOST']."/do_query.php?getmap=" . rawurlencode($mapID);
				// send curl with entry data to an sqlite db somewhere
				$map_data = do_curl($curl_url);
				$map_data_exists = true;
				//number of images in city map db
				$maxImages = count($map_data);
				break;
			}
			$map_data_exists = false;
		}
	}
	else{
		$mapID =null;
		$map_data_exists = false;
	}
	
?>
	
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Get Street View data</title>
<!--For Load Performance-->
<link media="ALL" rel="stylesheet" type="text/css" href="css/bootstrap.css"></link>
<style type="text/css">
body { 
	font-family: Arial, Verdana, Geneva;
	font-size: 90%; 
	background-image:url(img/1-1249480650QT9U.jpg);
	background-color: #cccccc;
}
.mapinput {
	font-size: 80%;
}
.title {
	text-align:center;
	padding: 10px;
}
#mapIntro b{
	font-size: 230%;
	background: default;
}
#formgetmap {
	display:none;}
#formnewmap{display:none;}
#cancelintroform{display:none;}
<?php if ($map_data_exists == false) { ?>
#mapIntro{
  display: inline-block;
  font-size: 140%;
  margin-top: 10em;
  background: #F8F8F8;
  border: solid;
  padding: 6em;
}
<?php } ?>
<?php if ($map_data_exists) { ?>
	#mapIntro{
		font-size: 100%;
		display: inline-block;
		margin-top: 0;
		background:inherit;
		padding: 0 3em;
		border: none;
	}
	#mapIntro b{
		text-decoration: none;
	}
	#intro{
		display: none;
	}

#formshow {
	display: inline-block;
}
#formadd { 
  display: inline-block;
}
#bottomformshow {
	display: inline-block;
}
.staticMapImg{
width:260px; height:180px;
}
.streetwrapper {
	width:100%;
	height:500px;
	text-align: center;
}
.panoblock {
	height: 420px;
	width:900px;
	margin-left:auto;
	margin-right:auto;
}
.pano {
	width: 420px; 
	height: 370px;
	padding:10px;
	float:left;
	display: inline-block;
}
.imageview {
	border:10px;
	width: 420px; 
	height: 370px;
	color: #7C7C68;
	float:left;
	display: inline;
}
<?php } ?>
</style>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/json2/20130526/json2.min.js"></script>
<script type="text/javascript" src="js/jquery-1.11.1.js"></script>
<script type="text/javascript" src="js/bootstrap.js"></script>
<script>
	var mapname = location.search.replace( '?getmap=', '' );
	$(document).ready(function() {
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
	
//Google Address Autocomplete Script
	var placeSearch, autocomplete;
	var componentForm = {
	  street_number: 'short_name',
	  route: 'long_name',
	  locality: 'long_name',
	  administrative_area_level_1: 'short_name',
	  country: 'long_name',
	  postal_code: 'short_name'
	};
	var cityDb = "<?php echo $mapID?>";

	function initialize(row) {
	  // Create the autocomplete object, restricting the search
	  // to geographical location types.
	  autocomplete = new google.maps.places.Autocomplete(
		  /** @type {HTMLInputElement}*/ (document.getElementById('autocomplete'+row)),
		  { types: ['geocode'] });
	}
	
	// [START region_geolocation]
	// Bias the autocomplete object to the user's geographical location,
	// as supplied by the browser's 'navigator.geolocation' object.
	function geolocate() {
	  if (navigator.geolocation) {
		navigator.geolocation.getCurrentPosition(function(position) {
		  var geolocation = new google.maps.LatLng(
			  position.coords.latitude, position.coords.longitude);
		  var circle = new google.maps.Circle({
			center: geolocation,
			radius: position.coords.accuracy
		  });
		  autocomplete.setBounds(circle.getBounds());
		});
	  }
	}
	// [END region_geolocation]

	function resetImage(row){
		document.getElementById("picturetitle"+row).style.display="none";
		document.getElementById("picturelocal"+row).style.display="none";
		document.getElementById("sizepic"+row).style.display="none";
		document.getElementById("imagepov"+row).style.display="none";
		document.getElementById("titleStreet"+row).style.display="inline";
		document.getElementById("itemlabel"+row).innerHTML = "Title: ";
		document.getElementById("itemlabel"+row).style.color='black';
		document.getElementById("itemtitle"+row).focus();
		document.getElementById("addressLabel"+row).style.display="none"; document.getElementById("autocomplete"+row).style.display="none";
		document.getElementById("convertAddress"+row).style.display="none";
	}
	
	function validateTitle(row){
		var title = $("#itemtitle"+row).val();
		var titlepresent = Boolean(title);
		if(titlepresent == false){
			document.getElementById("itemlabel"+row).innerHTML = "A Picture Title is Required";
			document.getElementById("itemlabel"+row).style.color='red';						
			document.getElementById("itemtitle"+row).focus();
		}
		else{
			document.getElementById("itemlabel"+row).innerHTML = "Title: ";
			document.getElementById("itemlabel"+row).style.color='black';
			if(row > <?php echo $max ?>){addressInput(row);}
		}
	}
	
	function addressInput(row){
		/*Add Line */
			document.getElementById("addressLabel"+row).style.display="inline"; document.getElementById("autocomplete"+row).style.display="inline"; document.getElementById("autocomplete"+row).focus(); document.getElementById("convertAddress"+row).style.display="inline"; document.getElementById("convertAddress"+row).style.display="inline";
		document.getElementById("cancelStreet"+row).style.diplay="inline";document.getElementById("submitGroup"+row).style.diplay="none";
	}
				
	function validateAddress(row){
		var streetAddress= $("#autocomplete"+row).val();
		var x = Boolean(streetAddress);
		if( (x == false) || (streetAddress.length <3)){
			document.getElementById("addressLabel"+row).innerHTML = "Complete Postal Address is Required";
			document.getElementById("addressLabel"+row).style.color='red';						
			document.getElementById("autocomplete"+row).focus();
		}
		else{
			var mygc = new google.maps.Geocoder();
			mygc.geocode({'address' : streetAddress }, function(results, status){
				place = autocomplete.getPlace();
				$('#lat_cell'+row).attr("value", results[0].geometry.location.lat());
				$('#lng_cell'+row).attr("value", results[0].geometry.location.lng());	
			});
			if(document.getElementById("addressLabel"+row).style.color ==  'red'){
				document.getElementById("addressLabel"+row).innerHTML = "Street Address:";
				document.getElementById("addressLabel"+row).style.color='black';
			}
		/**Add and Edit**/
			document.getElementById("picturetitle"+row).style.display="inline";
			document.getElementById("picturelocal"+row).style.display="inline";
			document.getElementById("sizepic"+row).style.display="inline";
			document.getElementById("convertAddress"+row).style.display="none";
			document.getElementById("picturelocal"+row).focus();
			if(row <= <?php echo $max ?>){document.getElementById("cancelStreet"+row).style.diplay="none";document.getElementById("submitGroup"+row).style.diplay="inline";}
		}
	}
				
	// for purposes of getting a scaled image from CDM reference URL
	function cdmpicture(row){
		// verify thatURL is in the form: http://[CONTENTdm home]/cdm/ref/collection/[alias]/id/[id]
		cdmrefurl = $("#picturelocal"+row).val();
		if ((Boolean(cdmrefurl) == false) || (cdmrefurl.length < 12) || (cdmrefurl.indexOf("<?php echo($config['CONTENTDM_HOME']) ?>/cdm/ref/collection") < 0)){
			document.getElementById("picturetitle"+row).innerHTML = "Need a valid CONTENTdm reference URL";
			document.getElementById("picturetitle"+row).style.color='red';
			document.getElementById("picturelocal"+row).value=null;
			document.getElementById("picturelocal"+row).focus();
		}
		else{
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
					$('#cdmurl_'+row).attr("value", imgPath);
					$('#identifier_'+row).attr("value", identifier);
					while( $("#cdmurl_"+row).length < 1){}
					imagestudio(imgPath, row);
				}				
			});
			document.getElementById("titleStreet"+row).style.display="none";
			document.getElementById("imagepov"+row).style.display="inline";
			document.getElementById("piclabel"+row).innerHTML=$("#itemtitle"+row).val();
			document.getElementById("picaddress"+row).innerHTML=$("#autocomplete").val(); 
			while( $("#cdmurl_"+row).length < 1){}
		}	
	}
	function imagestudio(picSource,row){
		$('#pano'+row).empty();
		$('#imageview'+row).empty();
		var lat = $('#lat_cell'+row).val();
		var lon = $('#lng_cell'+row).val();
		if (!parseInt(lat) || !parseInt(lon)) {
			alert("Need both a latitude and longitude");
			return false;
		}
		var povHead = Math.round($('#heading_cell'+row).val());
		var povPitch = Math.round($('#pitch_cell'+row).val());
		var povZoom = Math.round($('#zoom_cell'+row).val());
		$('#streetwrapper'+row).show();
		document.getElementById('imageview'+row).src=picSource;
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
		var panorama = new google.maps.StreetViewPanorama(document.getElementById("pano"+row), panoramaOptions);
		// listen for changes to panorama area and update input blank values as needed
		google.maps.event.addListener(panorama, 'position_changed', function() {
			$('#lat_cell'+row).val(panorama.getPosition().lat().toString());
			$('#lng_cell'+row).val(panorama.getPosition().lng().toString());      
		});
		google.maps.event.addListener(panorama, 'pov_changed', function() {
			$('#heading_cell'+row).val(panorama.getPov().heading);
			$('#pitch_cell'+row).val(panorama.getPov().pitch);
			$('#zoom_cell'+row).val(panorama.getPov().zoom);
		});
	}	
</script>
<?php if ($map_data_exists) { ?>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<!--Google autocomplete-->
<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places"></script>
<?php } ?>
</head>
	<body>
	<div class = "container-fluid"><!--bootstrap!-->
	<div id="output"></div>
	<div class="title">
		<div id="mapIntro" class="title">
		
			<b>"Then and Now" Map Helper</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<br><br>
			<p id="intro">
			"Then and Now" is an application designed to bring together Google Street Views
with historic photos stored in the Ohio Memory Collection.<!--a CONTENTdm collection. It makes use of an SQLite
database (but could easily be substituted by any other means of storing/retrieving 
JSON data).--><br><br>
	<form class="form-inline" role="form" id="formgetmap" name="formgetmap" method="POST" action="index.php">
			Please choose the name of the city to retrieve the relevant images and markers.</p>
			<div class="btn-group" role="group">
			<div class="btn-group" role="group" aria-label="...">
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
			</div>
			<button type="submit" class="btn btn-default" name="getdata" value="Get Map Data">Get Map Data</button> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			</div>
		</form>
		<form class="form-inline" role="form" id="formnewmap" name="formnewmap" method="POST" action="do_query.php"><br/>
		<div class="form-group">
			<p>Type the name of the new city.</p>
			<label id="newCityLabel">City Name:</label>
			<input type="text" class="form-control"id="cityName" name="cityName" onchange="clearwarning()">
			<button type="submit" class="btn btn-default" name="createmap" onfocus="newCity()" onmouseover="newCity()" value="Create Map">Create Map</button>
		</div>
		</form>
		<script>
	function clearwarning(){
		document.getElementById("newCityLabel").innerHTML = "City Name:";
		document.getElementById("newCityLabel").style.color='black';
	}
	function newCity(){
	<?php for ($i = 0; $i < $max; $i++) {
					if($map_id_json_array[$i]['mapid'] <> $mapID){
						echo 'var cityExist = "'.$map_id_json_array[$i]['mapid'].'";';?>
						var cityInput = $("input:text").val();
						if(cityExist == cityInput.toLowerCase()){
							document.getElementById("newCityLabel").innerHTML = "City Already Exists";
							document.getElementById("newCityLabel").style.color='red';
							document.getElementById("cityName").value=null;
							document.getElementById("cityName").focus();		
						}
					<?php }
				}?>	
	}
	
		</script>
	<br/><br/><div id="introSelection" class="btn-group" role="group">
	<button id="mapexistform" class="btn btn-default" onclick="document.getElementById('formgetmap').style.display='inline'; document.getElementById('mapexistform').style.display='none';document.getElementById('formnewmap').style.display='none'; document.getElementById('newmapform').style.display='inline';document.getElementById('cancelintroform').style.display='inline';">Select A Saved Map</button> <button id="newmapform" class="btn" onclick="document.getElementById('formnewmap').style.display='inline'; document.getElementById('formgetmap').style.display='none'; document.getElementById('newmapform').style.display='none';document.getElementById('mapexistform').style.display='inline';document.getElementById('cancelintroform').style.display='inline';document.getElementById('cityName').focus();">Create New Map</button>
	<button id="cancelintroform" class="btn" onclick="document.getElementById('formnewmap').style.display='none'; document.getElementById('formgetmap').style.display='none'; document.getElementById('newmapform').style.display='inline';document.getElementById('mapexistform').style.display='inline';document.getElementById('cancelintroform').style.display='none';">Cancel</button>
</div>
</div></div><br><br>
				<?php if ($map_data_exists) { ?>
					<div class="title">
						<form id="formshow" name="formshow">
							<input type="hidden" id="showmap" name="showmap" value="<?php echo $mapID?>">
							<button type="submit" class="btn btn-default" id="showsubmit" name="showsubmit" value="<?php echo $mapID?>">Show <?php echo ucfirst($mapID)?> Map</button>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						</form>
					</div><br>
					<div class="panel-group" id="accordion">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h4 class="panel-title">
							<a data-toggle="collapse" data-parent="#accordion" href="#collapseImage">
								Add Image
							</a>					
							</h4>
						</div>
						<div id="collapseImage" class="panel-collapse collapse">
							<div class="panel-body">
					
					<label>This is a 2 part process which includes inputting the picture information and then replicating the picture's focal point on Google street view.</label><br><br>
					<form role="form" id="formadd" name="formadd" onreset="resetImage(<?php echo ($maxImages+1); ?>)" method="POST" action="do_query.php">
					<!--Form Part 1-->
					<div id="titleStreet<?php echo ($maxImages+1); ?>" class="input-group">
					<label>Input picture title, street address, and copy paste the URL address of the ContentDM picture.</label><br><br>						
						<div class="form-group">
							<label id="itemlabel<?php echo ($maxImages+1); ?>" for="itemtitle">Title: </label>
							<input type="text" class="form-control" id="itemtitle<?php echo ($maxImages+1); ?>" name="title" placeholder="The Ohio Statehouse" required pattern="a-zA-Z\ \" onkeydown='document.getElementById("addressLabel<?php echo ($maxImages+1); ?>").style.display="inline"; document.getElementById("autocomplete<?php echo ($maxImages+1); ?>").style.display="inline"; document.getElementById("convertAddress<?php echo ($maxImages+1); ?>").style.display="inline"; initialize(<?php echo ($maxImages+1); ?>)' onblur="validateTitle(<?php echo ($maxImages+1); ?>)" autofocus>
							</div>
							<div class="form-group">
							<label id="addressLabel<?php echo ($maxImages+1); ?>" for="getLatLong" style="display:none">Street Address: </label>
							<input id="autocomplete<?php echo ($maxImages+1); ?>" class="form-control" name="address<?php echo ($maxImages+1); ?>" placeholder="1 capitol square, Columbus, OH"  type="text" autocomplete="off" onFocus="geolocate()"  style="display:none"required pattern="[a-zA-Z\d\s\-\,\#\.\+]+" role="group"></input>							
							</div>
							<button type="button" class="btn btn-default" id="convertAddress<?php echo ($maxImages+1); ?>" onclick="validateAddress(<?php echo ($maxImages+1); ?>)" style="display:none"role="group">Get Lat Long</button>
							<div class="form-group">
							<label id="picturetitle<?php echo ($maxImages+1); ?>" for="getscaled_autocomplete"style="display:none">ContentDM Image Upload: </label>
							<input type="url" class="form-control" id="picturelocal<?php echo ($maxImages+1); ?>" placeholder="Please enter a CONTENTdm reference URL" style="display:none" role="group" required>
							</div>
							<button type="button" class="btn btn-default" id="sizepic<?php echo ($maxImages+1); ?>" onclick="cdmpicture(<?php echo ($maxImages+1); ?>)" style="display:none"role="group">Get Picture</button>
							</div>
							<!--Form Part 2-->
							<div id="imagepov<?php echo ($maxImages+1); ?>" style="display:none">
							<label id="piclabel<?php echo ($maxImages+1); ?>"></label><br/>
							<label id="picaddress<?php echo ($maxImages+1); ?>"></label><br/>
							<input id="cdmurl_<?php echo ($maxImages+1); ?>" name="cdmurl_new"type="text" style="display:none" required>
							<input id="identifier_<?php echo ($maxImages+1); ?>" name="identifier_new"type="text" style="display:none" required>
							<label>Adjust the Google Viewpoint to match the image.</label>
							<div class="streetwrapper" id="streetwrapper<?php echo ($maxImages+1); ?>">
		<div id="panoInfo<?php echo ($maxImages+1); ?>">
	  	<input type="text" id="lat_cell<?php echo ($maxImages+1); ?>" name="lat_cell" size="10" style="display:none">
		<input type="text" id="lng_cell<?php echo ($maxImages+1); ?>" name="lng_cell" size="10" style="display:none">
	  	<input type="text" id="heading_cell<?php echo ($maxImages+1); ?>" name="heading_cell" size="9" value="0" style="display:none">
	  	<input type="text" id="pitch_cell<?php echo ($maxImages+1); ?>" name="pitch_cell" size="9" value="0" style="display:none">
	  	<input type="text" id="zoom_cell<?php echo ($maxImages+1); ?>" name="zoom_cell" size="1" value="0" style="display:none">
	  </div>
	  <br/>
		<div class="panoblock" id="panoblock<?php echo ($maxImages+1); ?>">	
		  <div class="pano" id="pano<?php echo ($maxImages+1); ?>"></div>
		  <img class="imageview" id="imageview<?php echo ($maxImages+1); ?>"></img>
		</div>
	</div>
							<input type="hidden" name="getmap" value="<?php echo $map_data_exists ? $mapID : '' ?>">
				<button type="submit" class="btn btn-default" name="addline" value="Add Line">Add Line</button>
							</div>
	<input type="reset" class="btn">
	<!--Cancel Function Option
	<button class="btn" onclick="document.getElementById('formadd').reset(); document.getElementById('collapseImage').display=none;">Cancel</button>-->
						</form>
						</div>
					</div>
			<?php for ($i = 0; $i < $maxImages; $i++) {	?>
						<div class="panel panel-default">
						<div class="panel-heading">
							<h4 class="panel-title">
							<img src="<?php echo $map_data[$i]["cdmurl"] ?>" width="123" height="90">&nbsp&nbsp
							<a data-toggle="collapse" data-parent="#accordion" href="#collapse<?php echo $i ?>">
						  <?php echo $map_data[$i]["itemtitle"]; ?>
							</a>							
							</h4>
						</div>
						<div id="collapse<?php echo $i ?>" class="panel-collapse collapse">
							<div class="panel-body">
								
								<div id="maindisplay" class="panel-body">
									<div id="dbpics">	
									 
									  <div id="imagepreview<?php echo $i ?>">
											<img src="<?php echo $map_data[$i]["cdmurl"] ?>" width="260" height="180">
									
									  <img class="staticMapImg" id="staticMapImg<?php echo $i ?>">
<script>
	//convert zoom to FOV for static map view
	var zoom = Number(<?php echo $map_data[$i]["zoom"]?>);
	var fovNum = Math.min(Math.max((Math.round(3.9018*Math.pow(zoom,2) - 42.432*zoom + 123)),10),120);
	var staticUrl = "https://maps.googleapis.com/maps/api/streetview?location=<?php echo $map_data[$i]['latitude']?>,<?php echo $map_data[$i]['longitude']?>&pitch=<?php echo $map_data[$i]['pitch'] ?>&heading=<?php echo $map_data[$i]['heading'] ?>&fov="+fovNum+"&size=260x180";
	document.getElementById("staticMapImg<?php echo $i ?>").src=staticUrl;
</script>
									</div>
									</div>
									<div id="editButtons<?php echo $i ?>" class="btn-group">
									<form method="POST" action="do_query.php" onsubmit="return confirmDelete()"><input type="hidden" name="getmap" value="<?php echo $map_data_exists ? $mapID : '' ?>"><input type="hidden" name="recordno" value="<?php echo $i ?>">
									<button class="btn" type="button" onclick="document.getElementById('imagepreview<?php echo $i ?>').style.display = 'none';document.getElementById('titleStreet<?php echo $i ?>').style.display = 'inline';imagestudio('<?php echo $map_data[$i]["cdmurl"] ?>', <?php echo $i ?>); document.getElementById('submitGroup<?php echo $i ?>').style.display = 'inline'; document.getElementById('editButtons<?php echo $i ?>').style.display = 'none';">Edit Meta Data</button>
									<button class="btn" type="button" onclick="document.getElementById('imagepreview<?php echo $i ?>').style.display = 'none';document.getElementById('imagepov<?php echo $i ?>').style.display = 'inline';imagestudio('<?php echo $map_data[$i]["cdmurl"] ?>', <?php echo $i ?>); document.getElementById('editButtons<?php echo $i ?>').style.display = 'none'; document.getElementById('submitGroup<?php echo $i ?>').style.display = 'inline';">Edit Picture View</button>
									<button class="btn" type="submit" name="deldata" value="Delete">Delete</button></a>
									</form>
									</div>
								</div>
								
								
					<form role="form" id="updateLine" name="updateLine" onreset="resetImage(<?php echo ($maxImages+1); ?>)" method="POST" action="do_query.php">
					<input type="hidden" name="recordno" value="<?php echo $i ?>"> 
								<!--Edit Form-->
								<div id="titleStreet<?php echo $i ?>" class="panel-body" style="display:none">			<div class="form-group">
							<label id="itemlabel<?php echo $i ?>" for="itemtitle">Title: </label>
							<input type="text" class="form-control" id="itemtitle<?php echo $i ?>" name="title<?php echo $i ?>" value="<?php echo $map_data[$i]["itemtitle"]; ?>"  pattern="a-zA-Z\ \" onchange="validateTitle()">
							</div>
							<button class="btn" type="button" id="displayAddress<?php echo $i ?>" onclick="addressInput(<?php echo $i ?>); document.getElementById('displayPicture<?php echo $i ?>').style.display='inline';" role="group">Change Address</button>
							<button class="btn" type="button" id="displayPicture<?php echo $i ?>" onclick="document.getElementById('displayAddress<?php echo $i ?>').style.display='inline'; document.getElementById('picturetitle<?php echo $i ?>').style.display='inline'; document.getElementById('picturelocal<?php echo $i ?>').style.display='inline'; document.getElementById('sizepic<?php echo $i ?>').style.display='inline'; document.getElementById('addressLabel<?php echo $i ?>').style.display='none'; document.getElementById('autocomplete<?php echo $i ?>').style.display='none'; document.getElementById('convertAddress<?php echo $i ?>').style.display='none';" role="group">Change CONTENTdm URL</button><!--document.getElementById('cancelStreet<?php echo $i ?>').style.display='inline';-->
							<div class="form-group">
							<label id="addressLabel<?php echo $i ?>" style="display:none;" for="getLatLong">Street Address: </label>
							<input id="autocomplete<?php echo $i ?>" class="form-control" style="display:none;" name="address<?php echo $i ?>" placeholder="Enter new address"  type="text" autocomplete="off" onFocus="geolocate()"   pattern="[a-zA-Z\d\s\-\,\#\.\+]+" role="group"></input>							
							</div>
							<button class="btn" type="button" id="convertAddress<?php echo $i ?>" style="display:none;" onclick="myFunction()" role="group">Get Lat Long</button>
							<!--Feature Option: Change CDM picture-->
							<div class="form-group">
							<label id="picturetitle<?php echo $i ?>" for="getscaled_autocomplete" style="display:none;">ContentDM Image Upload: </label>
							<input type="url" class="form-control" id="picturelocal<?php echo $i ?>" placeholder="New CDM URL if image needs changed" role="group" style="display:none;" required>
							</div>
							<button type="button" class="btn" id="sizepic<?php echo $i ?>" onclick="cdmpicture(<?php echo $i ?>)" role="group" style="display:none;">Get Picture</button>
							</div>
							<!--Form Part 2-->
							<div id="imagepov<?php echo $i ?>" style="display:none;">
							<label id="piclabel<?php echo $i ?>"></label><br/>
							<label id="picaddress<?php echo $i ?>"></label><br/>
							<input id="cdmurl_<?php echo $i ?>" name="cdmurl_<?php echo $i ?>"type="text" value="<?php echo $map_data[$i]["cdmurl"] ?>" style="display:none;" required>
							<input id="identifier_<?php echo $i ?>" name="identifier_<?php echo $i ?>"type="text" value="<?php echo $map_data[$i]["identifier"] ?>" style="display:none;" required>
							<label>Adjust the Google Viewpoint to match the image.</label>
							<div id="streetwrapper<?php echo $i ?>">
		<div id="panoInfo<?php echo $i ?>">
	  	latitude: <input type="text" id="lat_cell<?php echo $i ?>" name="lat_cell<?php echo $i ?>" value="<?php echo $map_data[$i]["latitude"] ?>" size="10">, longitude: <input type="text" id="lng_cell<?php echo $i ?>" name="lng_cell<?php echo $i ?>" value="<?php echo $map_data[$i]["longitude"] ?>" size="10"><br/>
	  	heading: <input type="text" id="heading_cell<?php echo $i ?>" name="heading_cell<?php echo $i ?>" value="<?php echo $map_data[$i]["heading"] ?>" size="9">, 
	  	pitch: <input type="text" id="pitch_cell<?php echo $i ?>" name="pitch_cell<?php echo $i ?>" size="9"  value="<?php echo $map_data[$i]["pitch"] ?>">, 
	  	zoom: <input type="text" id="zoom_cell<?php echo $i ?>" name="zoom_cell<?php echo $i ?>" size="1"  value="<?php echo $map_data[$i]["zoom"] ?>">
	  </div>
	  <br/>
		<div class="panoblock" id="panoblock<?php echo $i ?>">	
		  <div class="pano" id="pano<?php echo $i ?>"></div>
		  <img class="imageview" id="imageview<?php echo $i ?>"></img>
		</div>		
	</div>
							<input type="hidden" name="getmap" value="<?php echo $map_data_exists ? $mapID : '' ?>">
							</div>
							<div id="submitGroup<?php echo $i ?>" style="display:none;">
		<br/><button class="btn"type="submit" name="updateLine">Update Image</button>
		<button id="cancelUpdate<?php echo $i ?>"  class="btn" type="button" onclick="document.getElementById('titleStreet<?php echo $i ?>').style.display = 'none'; document.getElementById('imagepreview<?php echo $i ?>').style.display = 'inline'; document.getElementById('editButtons<?php echo $i ?>').style.display = 'inline';">Cancel</button>
		</div>
							<!--End of Edit--->
							</form>
							</div>
							</div>
						</div>
					</div>
				
			<?php	} 
				} ?>
	</div>
	<?php if ($map_data_exists && ($maxImages > 4)) { ?>
	
	<hr/>
	<div class="title">
		<form id="bottomformshow" name="bottomformshow">
				<input type="hidden" id="bottomshowmap" name="bottomshowmap" value="<?php echo $mapID?>">
				<button type="submit" class="btn btn-default" id="showsubmit" name="showsubmit" value="<?php echo $mapID?>">Show <?php echo ucfirst($mapID)?> Map</button>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			</form>
	</div>
	<br>
	</div>
	<?php } ?>
	
</body>
</html>