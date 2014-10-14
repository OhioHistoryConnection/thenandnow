<?php

/**
  * Script to display a "Then and Now" map data
  * Phil Sager <psager@ohiohistory.org>.
  * 
*/

// path to thenandnow folder
define("ABS_PATH", dirname(__FILE__));
$mapID="columbus";
include(ABS_PATH . '/conf/config_'.$mapID.'.php'); 
#include(ABS_PATH . '/conf/config_'.$mapref.'.php'); 

?>

<html>
<head> 
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" /> 
<meta http-equiv="content-type" content="text/html; charset=UTF-8"/> 
<title>Google Maps JavaScript API v3 Example: Common Loader</title> 
<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?v=3&amp;sensor=false"></script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/json2/20130526/json2.min.js"></script>
<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/redmond/jquery-ui.css" rel="stylesheet" type="text/css"/>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
<script type="text/javascript" src="http://google-maps-utility-library-v3.googlecode.com/svn/trunk/infobox/src/infobox.js"></script>
  
<script type="text/javascript"> 
 
	var infowindow;
	var map;
	var mapArrays = new Array();
	var cellsPerRow = 8;

	$(function() {
		var mapname = location.search.replace( '?getmap=', '' );
	    var myLatlng = new google.maps.LatLng(0, 0);
	    var myOptions = {
			zoom: 10,
			center: myLatlng,
			streetViewControl: false,
			mapTypeId: google.maps.MapTypeId.ROADMAP
    	}
		map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
		
		// get map data via SQL query proxy php
		$.ajax({
		    type: 'GET',
		    url: 'do_query.php',
		    data: { getmap: mapname },
		    success: function(data){	
		    	var results = JSON.parse(data);    
		      	// creates a variable used to hold the outer dimensions of map
	    		var bounds = new google.maps.LatLngBounds();
	    		for (var i = 0; i < results.length; i++) {
		      	var latlng = new google.maps.LatLng(parseFloat(results[i].latitude), parseFloat(results[i].longitude));
		      	// get pano data
		      	var SVdata = new Array(results[i].heading, results[i].pitch, results[i].zoom);
		      	// set up InfoWindow html
		      	var text = [
			        '<div id="InfoText">',
			        '<div class="tabs"><ul><li><a href="#tab1">Historic View</a></li>',
			        '<li><a href="#tab2" id="SV">Modern View</a></li></ul>',
			        '<div id="tab1">',
			        '<b>' + results[i].itemtitle + '</b> (',
			        '<i><a href="<?php echo($config["CONTENTDM_HOME"]) ?>/cdm/ref/collection/' + results[i].cdmurl.replace(/.*CISOROOT=(.+?)&CISOPTR=.*/,'$1') + '/id/' + results[i].cdmurl.replace(/.*CISOPTR=(.+?)&action=.*/,'$1') + '" target="_blank">' + results[i].identifier + '</a></i>)<BR>',
			        '<img src="' + results[i].cdmurl  + '">',
			        '</div>',
			        '<div id="tab2">',
			        '<div id="pano"><div>', 
			        '</div>',
			        '</div>'
			      	].join('');
					// create markers
			        var marker = createMarker(results[i].itemtitle, text, latlng, SVdata);
			        bounds.extend(latlng);
			        map.fitBounds(bounds);  
	    		}
		    }
  		}); 
	});

	function createMarker(markerTitle, text, latlng, SVdata) {
	
		var marker = new google.maps.Marker({
			position: latlng, 
			map: map
		});
	
		var boxText = document.createElement("div");
		boxText.style.cssText = "font-weight: bold; border: 1px solid black; margin-top: 8px; background: #FFFF6A; padding: 5px;";
		boxText.innerHTML = markerTitle.replace(/ /g,"&nbsp;");
	
		var myOptions = {
			content: boxText,
			disableAutoPan: false,
			maxWidth: 0,
			pixelOffset: new google.maps.Size(-10, 0),
			zIndex: null,
			boxStyle: { 
				opacity: 0.75   
			},
			closeBoxMargin: "10px 2px 2px 2px",
			closeBoxURL: "",
			infoBoxClearance: new google.maps.Size(1, 1),
			isHidden: false,
			pane: "floatPane",
			enableEventPropagation: false
		};
		google.maps.event.addListener(marker, 'mouseout', function() {
			ib.close();
		});
		google.maps.event.addListener(marker, "mouseover", function() {
			ib.open(map, marker);
		});  
		var ib = new InfoBox(myOptions);
	             
		google.maps.event.addListener(marker, "click", function() {
			if (infowindow) infowindow.close();
			infowindow = new google.maps.InfoWindow({content: text});
			infowindow.open(map, marker);
			var panoramaOptions = {
				position: marker.position,
				navigationControl: false,
				enableCloseButton: false,
				addressControl: false,
				visible: true,
				linksControl: false
			};
		 
			google.maps.event.addListener(infowindow, 'domready', function() {       
				//$('#pano').css("width:400px;height: 275px;");
				//$('#pano').css("width:420px;height: 370px;");
				$(".tabs").tabs();
				$('#SV').click(function() {
							
					var panorama = new google.maps.StreetViewPanorama(document.getElementById("pano"),panoramaOptions);  
					panorama.setPov({heading:parseInt(SVdata[0]), pitch:parseInt(SVdata[1]), zoom:parseInt(SVdata[2])});
					var pov = panorama.getPov();
					
					// I don't know why this is necessary (cached?), but sometimes 
					// Street View does not render without altering a pano value slightly
					var cur_zoom = pov.zoom;
					if (cur_zoom < 3) {
						cur_zoom = cur_zoom + .1;
					} else {
						cur_zoom = cur_zoom - .1;
					}
					
					panorama.setPov(pov);	
					panorama.setVisible(true); 
					map.setStreetView(panorama);
				  
				});
			}); 
		});
	
		return marker;
	
	}
	
</script> 
<style>
html, body {
  height: 100%;
  margin: 0;
  padding: 0;
}
#map_canvas {
  height: 100%;
}
#infotext {
	font-size: 12px;
	width: 550px; /* 480 550 */
	height: 450px; /*  450 */
}
.tabs {
	width: 520px; /* -30 450 520 */
	height: 420px; /* -30 350 420 */
}
#pano {
	width: 420px; /* -130 350 420 */
	height: 370px; /* -130 250 370 */
}
.maptitle {
	background-color:white;
	position:absolute;
	top:40px;
	right:14px;
	padding:4px;
}
</style>
</head> 
<body> 
	<div id="map_canvas" style="width:100%; height:100%;"></div>
	
	<?php echo($config['MAP_TITLE'] ? '<div class="maptitle">'.$config['MAP_TITLE'].'</div>' : "") ?>

  <!-- <div id='pano' style='position: absolute: top: 40px; left: 2px; width: 380px; height:290px;'></div> --> 
</body> 
</html> 
