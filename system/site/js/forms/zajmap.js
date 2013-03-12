//////////////////////////////////////////////////////////////////////////////
// zajlik.hu (c) 1999-2007 – google maps class
//////////////////////////////////////////////////////////////////////////////
// class: zajMap
// written by: hontalan /aron budinszky - hontalan@gmail.com/
// version: 1.2
//////////////////////////////////////////////////////////////////////////////
// copyright notice: use of this class is permitted, but requires a notice to
// be sent to webmester@zajlik.hu. (in other words, i just want to know if you
// found it and want to use it...thanks :))
//////////////////////////////////////////////////////////////////////////////
// this should work Firefox 1.5+, IE 5.5+, Opera 7+. send bugs to above email.
// check for new versions, go to hontalan.com.
//////////////////////////////////////////////////////////////////////////////
// version history.
// - 1.0 - initial release
// - 1.1 - many new functions added
// - 1.2 - info window
//////////////////////////////////////////////////////////////////////////////
/* usage:
REMEMBER: lattitude = y, longitude = x





EVENTS:
	- markerdragstart - fired right before a marker is dragged with the marker object passed
	- markerdragend - fired right after a marker is dragged with the marker object passed
	- markerrepositioned - fired after any repositioning of the marker

*/
//////////////////////////////////////////////////////////////////////////////
// zajAjax class
//var zajmap = new zajMap("map_canvas");


var zajMap = new Class({
	Implements: [Options, Events],
	
	options: {
		lat: 47.4984056,				// the default location on the map (lat)
		lng: 19.0407578,				// the default location on the map (lng)
		zoom: 13						// the default zoom
	},	
	
	//////////////////////////////////////////////////////////////////////////////
	// constructor
		initialize: function(divid, options){
			// set default options
				this.setOptions(options);
				this.canvas = $(divid);
			// check that GMap js has been loaded
				if(typeof GMap2 == 'function'){
					this.gmap = new GMap2(this.canvas);
					// now my defaults
					this.gmap.setCenter(new GLatLng(this.options.lat, this.options.lng), this.options.zoom);
					this.gmap.setUIToDefault();
					this.gmap.disableScrollWheelZoom();
					this.geocoder = new GClientGeocoder();
					this.marker = new GMarker(new GLatLng(this.options.lat, this.options.lng), {draggable: true});
					// add marker events
						var self = this;
						GEvent.addListener(this.marker, "dragstart", function() {
						  	self.fireEvent('markerdragstart',self.marker);
						  });
						
						GEvent.addListener(this.marker, "dragend", function() {
						  	self.fireEvent('markerdragend',self.marker);
						  });
					
				}
				else zajlib.log('failed to load map!');
		},

	//////////////////////////////////////////////////////////////////////////////
	// helpers and tools
		zoomto: function(zoomlevel){
			this.gmap.setZoom(zoomlevel);
		},
	//////////////////////////////////////////////////////////////////////////////
	// show the map at an address or lattitude/longitude
		showaddress: function(address){
			var self = this;
			this.geocoder.getLatLng(showgeo,function(point){ self.showgeo(point.lat(), point.lng()); });
		},
		showgeo: function(geolat, geolng){
			this.gmap.panTo(new GLatLng(geolat, geolng));					
		},
	//////////////////////////////////////////////////////////////////////////////
	// create a marker at address
		markcenter: function(draggable){
			var latlng = this.gmap.getCenter();
			this.markgeo(latlng.lat(),latlng.lng(), draggable);
		},
		markaddress: function(address, draggable){
			// search for the address and send to markgeo
				var self = this;
				this.geocoder.getLatLng(address,function(point){				
					self.markgeo(point.lat(), point.lng(), draggable);
				});
		},
		markgeo: function(geolat, geolng, draggable){
			// reposition marker
				this.marker.setLatLng(new GLatLng(geolat, geolng));
			// reset drag option
				if(!$defined(draggable) || draggable) this.marker.enableDragging();
				else this.marker.disableDragging();
			// add to gmap
				this.gmap.addOverlay(this.marker);
			// pan to that location
				this.showgeo(geolat, geolng);
			// now fire repositioning
				this.fireEvent('markerrepositioned',this.marker);
			// return
			return this.marker;
		},
	//////////////////////////////////////////////////////////////////////////////
	// create an info box at address
		infomarker: function(textORdiv){
			// if a div, then open that. else open a text window.
				if($chk($(textORdiv))) this.marker.openInfoWindow(textORdiv);
				else this.marker.openInfoWindowHtml(textORdiv);
		},
		infoaddress: function(address, textORdiv){
			// search for the address and send to markgeo
				var self = this;
				this.geocoder.getLatLng(address,function(point){				
					self.infogeo(point.lat(), point.lng(), textORdiv);
				});	
		},
		infogeo: function(geolat, geolng, textORdiv){
			// if a div, then open that. else open a text window.
				if($chk($(textORdiv))) this.gmap.openInfoWindow(new GLatLng(geolat, geolng), textORdiv);
				else this.gmap.openInfoWindowHtml(new GLatLng(geolat, geolng), textORdiv);
		}
});






/*
function zajMap(myMapDiv){
	// define variables
		var myDiv = myMapDiv;
		var myMap = new GMap2(document.getElementById(myDiv));
		var myGeocoder = new GClientGeocoder();
		var myPoint;
		var myCenter;
	// markers
		var myMarkers = new Array();
		var numofmarkers = 0;
	// initialize map
		var myMapControl = new GSmallMapControl();
		var myTypeControl = new GMapTypeControl();
		myMap.addControl(myMapControl);
		myMap.addControl(myTypeControl);
	// add event listeners
		GEvent.addListener(myMap, "moveend", function() { myCenter = myMap.getCenter(); } );
		//GEvent.addListener(myMap, "click", function() { this.getDistance(); });
	
	// define member functions
	this.showMap = zajShowMapAddress;
	this.setZoom = zajMapSetZoom;
	this.addMarker = zajMapAddMarker;
	this.getDistance = zajGetDistance;
	this.removeMapControl = zajRemoveMapControl;
	this.removeTypeControl = zajRemoveTypeControl;
	this.removeMarkers = zajRemoveMarkers;
	this.resetMap = zajResetMap;
	this.panTo = zajPanToLocation;

	// define values
	this.isLoaded = function(){ return myMap.isLoaded(); }
	
	// create marker points
		var blueIcon = new GIcon(G_DEFAULT_ICON);
		blueIcon.image = "http://labs.google.com/ridefinder/images/mm_20_blue.png";
		blueIcon.iconSize = new GSize(12, 20);
		blueIcon.shadow = "http://labs.google.com/ridefinder/images/mm_20_shadow.png";
		blueIcon.shadowSize = new GSize(22, 20);
		var greenIcon = new GIcon(G_DEFAULT_ICON);
		greenIcon.image = "http://labs.google.com/ridefinder/images/mm_20_green.png";
		greenIcon.iconSize = new GSize(12, 20);
		greenIcon.shadow = "http://labs.google.com/ridefinder/images/mm_20_shadow.png";
		greenIcon.shadowSize = new GSize(22, 20);
		var redIcon = new GIcon(G_DEFAULT_ICON);
		redIcon.image = "http://labs.google.com/ridefinder/images/mm_20_red.png";
		redIcon.iconSize = new GSize(12, 20);
		redIcon.shadow = "http://labs.google.com/ridefinder/images/mm_20_shadow.png";
		redIcon.shadowSize = new GSize(22, 20);
		var yellowIcon = new GIcon(G_DEFAULT_ICON);
		yellowIcon.image = "http://labs.google.com/ridefinder/images/mm_20_yellow.png";
		yellowIcon.iconSize = new GSize(12, 20);
		yellowIcon.shadow = "http://labs.google.com/ridefinder/images/mm_20_shadow.png";
		yellowIcon.shadowSize = new GSize(22, 20);
	

	//////////////////////////////////////////////////////////////////////////////
	// This shows the map...either geocode or address entry accepted
	function zajShowMapAddress(addressORgeolat, geolng){
		// if this is an address entry
		if(typeof geolng == "undefined" || geolng == ""){
			myGeocoder.getLatLng(addressORgeolat,zajShowMapLocation);
		}
		// if this is a lat long entry
		else{
			// create point
			myPoint = new GLatLng(addressORgeolat,geolng);
			zajShowMapLocation(myPoint);
		}
	}
	//////////////////////////////////////////////////////////////////////////////
	// This shows the map...geocode point needed
	function zajShowMapLocation(point){
		myMap.setCenter(point, 15);			
		myPoint = point;
		myCenter = point;
		myMap.addOverlay(new GMarker(point));
	}
	//////////////////////////////////////////////////////////////////////////////
	// This pans the map...geocode point needed
	function zajPanToLocation(geolat,geolng,addMarker){
		// create the point and pan to it
			point = new GLatLng(geolat,geolng);
			myMap.panTo(point);
		// add marker? default = yes
			if(typeof addMarker == "undefined" || addMarker == true) myMap.addOverlay(new GMarker(point));
		// set current point
			myCenter = point;
		return true;
	}
	// goes back to center
	function zajResetMap(){
		zajPanToLocation(myPoint.lat(),myPoint.lng());
	}
	

	//////////////////////////////////////////////////////////////////////////////
	// Sets zoom level
	function zajMapSetZoom(level){
		myMap.setZoom(level);
	}
	
	function zajMapAddMarker(addressORgeolat,geolng,color,myMarkerText,callBackOnSuccess,callBackOnError){
		// set default color
			switch(color){
				case "red": icon = redIcon; break;
				case "green": icon = greenIcon; break;
				case "yellow": icon = yellowIcon; break;
				default: icon = blueIcon;
			}

		// if this is an address entry
		if(typeof geolng == "undefined" || geolng == ""){
			myGeocoder.getLatLng(addressORgeolat,function(point){zajMapAddMarkerProcess(point,icon,addressORgeolat,myMarkerText,callBackOnSuccess,callBackOnError);});
		}
		// if this is a lat long entry
		else{
			// create point
			var point = new GLatLng(addressORgeolat,geolng);
			zajMapAddMarkerProcess(point,icon,'',myMarkerText);
		}
	}
	function zajMapAddMarkerProcess(point,currenticon,addressORgeolat,myMarkerText,callBackOnSuccess,callBackOnError){
		if(typeof callBackOnError == "function" && point == null) callBackOnError(addressORgeolat);
		else{		
			var markerOptions = { icon:currenticon };
			myMarkers[numofmarkers] = new GMarker(point,markerOptions);
			myMap.addOverlay(myMarkers[numofmarkers]);
			if(myMarkerText != ''){
				GEvent.addListener(myMarkers[numofmarkers], "click", function() { this.openInfoWindow(myMarkerText);  } );
			}
			numofmarkers++;
			if(typeof callBackOnSuccess == "function") callBackOnSuccess(addressORgeolat,point);
		}		
	}
	
	//////////////////////////////////////////////////////////////////////////////
	// Get the distance to another point
	function zajGetDistance(geolat,geolng){
		// if error then return
			if(typeof myPoint == "undefined" || myPoint == "") return false;

		var dobj = new GDirections();
		// point or two coordinates given?
			if(typeof geolng != "undefined" || geolng == ""){
				endpoint = new GLatLng(geolat,geolng);
			}
			else{
				endpoint = geolat;
				geolat = endpoint.lat();
				geolng = endpoint.lng();
			}
		// add start waypoints
			//starttxt = "("+myPoint.lat()+", "+myPoint.lng()+")";
			//endtxt = "("+geolat+", "+geolng+")";
		// calculate distance and return			
			return myPoint.distanceFrom(endpoint);
	}


	//////////////////////////////////////////////////////////////////////////////
	// Manipulate controls
	function zajRemoveMapControl(){
		myMap.removeControl(myMapControl);
	}
	function zajRemoveTypeControl(){
		myMap.removeControl(myTypeControl);
	}
	function zajRemoveMarkers(){
		myMap.clearOverlays();
	}
	
}

///////////////////////////////////////////////////////////////////////////////////////
// Helper functions - GetAddress returns a dropdown whose values are gmap coordinates
var globalAddressResponseDiv,globalAddressResponseProcessFunction,globalAddressDefault;
function zajGetAddress(address,responsediv,onchange,defaultaddress){
	// set response div
		globalAddressResponseDiv = responsediv;
	// set process function
		globalAddressResponseProcessFunction = onchange;
	// set default address
		if(typeof defaultaddress != "undefined") globalAddressDefault = defaultaddress;
		else globalAddressDefault = '';
	// create geocoder and send address text
		var gcoder = new GClientGeocoder();
		gcoder.getLocations(address, zajReturnAddress);
}
function zajReturnAddress(response){
	var myAddressArray = new Array();
	// handle address response
	if (!response || response.Status.code != 200) myAddressArray['error'] = '--';
	else{
		for(var i=0; i < response.Placemark.length; i++){
			place = response.Placemark[i];
			myAddressArray[place.Point.coordinates[1]+", "+place.Point.coordinates[0]] = place.address;		
		}
		if(response.Placemark.length == 0) myAddressArray['error'] = '--';
	}
  // now create a dropdown for these addresses
	  dropdown = selectMe('addresses',myAddressArray,globalAddressDefault,globalAddressResponseProcessFunction);
	  document.getElementById(globalAddressResponseDiv).innerHTML = "";
	  document.getElementById(globalAddressResponseDiv).appendChild(dropdown);
	  if(typeof globalAddressResponseProcessFunction != "undefined" && globalAddressResponseProcessFunction != '') globalAddressResponseProcessFunction();
}

///////////////////////////////////////////////////////////////////////////////////////
// Helper functions - GetDistance returns the distance between two coordinate points
function zajGetDistance(slat,slng,elat,elng){
	startpoint = new GLatLng(slat,slng);
	endpoint = new GLatLng(elat,elng);
	return startpoint.distanceFrom(endpoint);
}

///////////////////////////////////////////////////////////////////////////////////////
// Helper functions - zajSearchAddress returns text of addresses
///////////////////////////////////////////////////////////////////////////////////////
// zajSearchAddress(address,responsediv,onclick)
// onclick is a function with two paramteres
//		onclick(addressFormatedText, addressDetailsArray);
var globalSearchResponseDiv,globalSearchResponseProcessFunction;
function zajSearchAddress(address,responsediv,onclick){
	// set response div
		globalSearchResponseDiv = responsediv;
	// set process function
		globalSearchResponseProcessFunction = onclick;
	// set default address
		if(typeof defaultaddress != "undefined") globalAddressDefault = defaultaddress;
		else globalAddressDefault = '';
	// create geocoder and send address text
		var gcoder = new GClientGeocoder();
		gcoder.getLocations(address, zajReturnSearchAddress);
}


// global vars to store address info
var myDetailsArray, myAddressArray, myResultElements;
function zajReturnSearchAddress(response){
	myDetailsArray = new Array();
	myAddressArray = new Array();
	myResultElements = new Array();
	
	// handle address response
	if (!response || response.Status.code != 200) myAddressArray['error'] = '--';
	else{
		for(var i=0; i < response.Placemark.length; i++){
			place = response.Placemark[i];
			myAddressArray[place.Point.coordinates[1]+", "+place.Point.coordinates[0]] = place.address;		
			myDetailsArray[place.Point.coordinates[1]+", "+place.Point.coordinates[0]] = zajProcessAddress(place);
		}
		if(response.Placemark.length == 0) myAddressArray['error'] = '--';
	}
    // now create a list for these addresses
 	document.getElementById(globalSearchResponseDiv).innerHTML = "";
  	for(addressKey in myAddressArray){
  		var newelement = addElement(globalSearchResponseDiv,addressKey);
  		newelement.innerHTML = "<div class='zajsearchresult' onClick=\"zajCallSearchResponseProcessFunction('"+newelement.id+"');\">"+myAddressArray[addressKey]+"</div>";
  	}
}
// this is an extra step required by stupid internet explorer
function zajCallSearchResponseProcessFunction(id){
	globalSearchResponseProcessFunction(myAddressArray[id],myDetailsArray[id]);
}

////////////////////////////////////////////////////////////////////////////////////////////
// Helper functions - ProcessAddress returns an array of info based on the Placemark array
function zajProcessAddress(placemark){
	// new address array
		var address = new Array();

	// for pre parse debug purposes
		//var rtext = print_r(placemark,true);
		//rtext = rtext.replace(/\n/g,"<br>");
		//document.getElementById('debug').innerHTML += "<br><br>"+rtext;

	// parse based on accuracy
		var accuracy = placemark.AddressDetails.Accuracy;
		var details = placemark.AddressDetails;

	// get the country
		address['country'] = details.Country.CountryNameCode;
		address['lat'] = placemark.Point.coordinates[1];
		address['lng'] = placemark.Point.coordinates[0];
		address['accuracy'] = accuracy;

	// figure out the general area
		if(typeof details.Country.AdministrativeArea != "undefined"){
			var area = details.Country.AdministrativeArea;
			// if city in state / county
				if(typeof details.Country.AdministrativeArea.SubAdministrativeArea != "undefined"){
					var area = details.Country.AdministrativeArea.SubAdministrativeArea;
					address['state'] = details.Country.AdministrativeAreaName;
				}
		}

	// now get street and postal code info (if available)
		// street level accuracy
		if(accuracy >= 6 && accuracy < 9){
			// regular address (county or country)
				if(typeof area.AdministrativeAreaName != "undefined") address['city'] = area.AdministrativeAreaName;
				if(typeof area.Locality.LocalityName != "undefined") address['city'] = area.Locality.LocalityName;
				if(typeof area.Locality.Thoroughfare != "undefined") address['street'] = area.Locality.Thoroughfare.ThoroughfareName;
				if(typeof area.Locality.PostalCode != "undefined") address['postal'] = area.Locality.PostalCode.PostalCodeNumber;
			
			// districts support
				if(typeof area.Locality.DependentLocality != "undefined"){
					if(typeof area.Locality.DependentLocality.Thoroughfare != "undefined") address['street'] = area.Locality.DependentLocality.Thoroughfare.ThoroughfareName;
					if(typeof area.Locality.DependentLocality.PostalCode != "undefined") address['postal'] = area.Locality.DependentLocality.PostalCode.PostalCodeNumber;
				}
		}
		// premise level accuracy
		if(accuracy == 9){
			// premise support
				if(typeof details.Country.Premise != "undefined") address['street'] = details.Country.Premise.PremiseName;
		}
	
	// for post parse debug purposes
		//var rtext = print_r(address,true);
		//rtext = rtext.replace(/\n/g,"<br>");
		//document.getElementById('debug').innerHTML += "<br><br>"+rtext;
	
	// return address array
		return address;
}*/