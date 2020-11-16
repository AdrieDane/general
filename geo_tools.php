<?php

function distanceGeoPoints ($lat1, $lng1, $lat2, $lng2) {

    $earthRadius = 3958.75;

    $dLat = deg2rad($lat2-$lat1);
    $dLng = deg2rad($lng2-$lng1);


    $a = sin($dLat/2) * sin($dLat/2) +
       cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
       sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $dist = $earthRadius * $c;

    // from miles
    $meterConversion = 1609;
    $geopointDistance = $dist * $meterConversion;

    return $geopointDistance;
}
/*
function traverse( DomNode $node, $level ){
  handle_node( $node, $level );
 if ( $node->hasChildNodes() ) {
   $children = $node->childNodes;
   foreach( $children as $kid ) {
     if ( $kid->nodeType == XML_ELEMENT_NODE ) {
       traverse( $kid, $level+1 );
     }
   }
 }
}

function handle_node( DomNode $node, $level ) {
  for ( $x=0; $x<$level; $x++ ) {
    print " ";
  }
  if ( $node->nodeType == XML_ELEMENT_NODE ) {
    print $node->tagName."<br />\n";
  }
}
*/
function find_coords( DomNode $node, &$route ){
  handle_node( $node, $route );
 if ( $node->hasChildNodes() ) {
   $children = $node->childNodes;
   foreach( $children as $kid ) {
     if ( $kid->nodeType == XML_ELEMENT_NODE ) {
       find_coords( $kid, $route );
     }
   }
 }
}

function handle_node( DomNode $node, &$route ) {
  if ( $node->nodeType == XML_ELEMENT_NODE ) {
    if($node->tagName=='coordinates')
      $route['coordinates']=get_coord_array($node->nodeValue);
  }
}

// KML
function readkml($fname) 
{
  // echo('KML'.$fname);
  if(!file_exists($fname))
    return NULL;
  
  $xml = new DOMDocument();
  $xml->load($fname);
  $places=$xml->getElementsByTagName('Placemark');

  $points=array();
  $route=array();
  $i=0;
  $j=0;
  foreach($places as $place)
    {
      $icon='';
      foreach ($place->childNodes AS $item) {
	// print $item->nodeName  . "<br>";
	if($item->nodeName=='name') {
	  $id=$item->nodeValue;
	}
	if($item->nodeName=='description') {
	  //print $item->nodeName  . "<br>";
	  print $item->nodeName . " => " . $item->nodeValue . "<br>";
	  $icon=$item->nodeValue;
	}
	if($item->nodeName=='Point') {
	  foreach($item->childNodes as $it) {
	    //print $it->nodeName . " => " . $it->nodeValue . "<br>";
	    if($it->nodeName=='coordinates') {
	      //echo $coords;
	      $points[$i]['id']=$id;
	      $points[$i]['icon']=$icon;
	      $points[$i]['coordinates']=get_coord_array($it->nodeValue);
	   
	    }
	  }
	  $i=$i+1;
	}
	elseif($item->nodeName=='LineString' || $item->nodeName=='LinearRing'  || $item->nodeName=='Polygon' ) {
	  $coordsfound=false;
	  foreach($item->childNodes as $it) {
	  
	    if($it->nodeName=='coordinates') {
	      $route[$j]['id']=trim($id);
	      $route[$j]['coordinates']=get_coord_array($it->nodeValue);
	      $coordsfound=true;
	    }
	  }
	  if(!$coordsfound)	{
	    $route[$j]['id']=trim($id);
	    find_coords($item,$route[$j]);
	  }
	  $j=$j+1;
	}
      }
    }

  $A=array();
  $A['route']=$route;
  /* $A['id']=$route[$j]['id'];
	  echo "<br><br>XX";
	  print_r($A['route']);
	  echo "XX<br><br>";
  */  
  $A['points']=$points;
  return $A;
}


function get_coord_array( $coords)
{
  $A=array_map('str_getcsv', str_getcsv($coords,"\n"));
  if(count($A[0])<2)
    array_shift($A);

  if(count($A[count($A)-1])<2)
    array_pop($A);

  if(count($A)==1)
    return $A[0];
  else
    return $A;
}
function kml2leaf($con,$fname,$reverse=false) 
{
  if(!is_array($fname))	{
    $fname=array($fname);
  }

  $nroutes=count($fname);

  $colors=array('#3388ff','#008000','#ff2c2c','#c20cc2','#767676','#ff8a14','#9224ff','#44ff44','#3030ff','#c2c2c2','#38ffff','#3ccece','#c7c720','#ff8cc6');
  $ncolors=count($colors);
  $mnlat=$mnlon=1000;
  $mxlat=$mxlon=0;

  $routes="\nvar route = L.featureGroup([";
  $large = "\nvar highlights = L.layerGroup([\n";
  $small = "\nvar smallhighlights = L.layerGroup([\n";
  $idx=0;
  foreach($fname as $f) {
    
    $kml=readkml($f);
    if(is_null($kml))
      return '';

    $kroute=$kml['route'];
    
    /*
    if($reverse==true) {
      foreach($kroute as &$r) {
	array_reverse($r['coordinates']);
      }
    }
    if($reverse==true)
      $route=array_reverse($kml['route']);
    else
      $route=$kml['route'];
    */
    $points=$kml['points'];
    echo("KROUTE<br>\n");
    print_r($kroute);
    echo("KROUTE<br>\n");
    $ttl=$kroute[0]['id'];

    $data=$con->named('tourdata',$ttl);
    if($data) {
      echo("\n<br> <br>Associated Data:  <br>\n");
      $ttl=$data['Title'];

    }  else {
      echo("\n<br> <br>NoAssociated Data: <br>\n");
    }
    
    print_r($data);
    echo("<br>\n");
    

    foreach($kroute as $k) {
      
      if($reverse==true)
	$route=array_reverse($k['coordinates']);
      else
	$route=$k['coordinates'];
      $ttl=$k['id'];
      

      echo "Q<br>\n";
    print_r($route);
      echo "Q<br>\n";



      foreach($route as $coord) {
	if($coord[0]>$mxlon)
	  $mxlon=$coord[0];
	if($coord[0]<$mnlon)
	  $mnlon=$coord[0];

	if($coord[1]>$mxlat)
	  $mxlat=$coord[1];
	if($coord[1]<$mnlat)
	  $mnlat=$coord[1];
      }


      // write route
      $routes .= "\nL.polyline([";
      foreach($route as $coord) {
	$y=$coord[0];
	$x=$coord[1];
	$routes .= "[$x, $y],";
      }
      $routes=substr($routes, 0, -1);
    
      $c=$colors[$idx%$ncolors];
      //    echo "IDX: $idx c: $c";
      $routes .= "], {color: '$c'}).bindTooltip('$ttl'),\n";
      $idx+=1;
    }



    // write points
  
    $paths=$con->select_name_value('paths','Name','Path');
  
    // $iconpath=$paths['ICONPATH'];
  
    // $large='';
    // $small='';
    foreach($points as $point) {
      $id=$point['id'];
      $lat=$point['coordinates'][0];
      $lon=$point['coordinates'][1];
      $A=$con->query('SELECT * FROM `highlights` WHERE `highlights`.`HighlightId` = '.$id);
      //    echo "Icon: ".$point['icon'];
    
      if($point['icon']=='')
	$png=$A['Icon'];
      else
	$png=$point['icon'];
      $descr=$A['Name'];
      //if($A->num_rows==0)
      // print_r($A);
      if($descr=="") {
	$small .= "L.marker([$lon, $lat], {icon: new SmallIcon({iconUrl: iconpath.concat('/$png.png')})}),\n";
	$large .= "L.marker([$lon, $lat], {icon: new LeafIcon({iconUrl: iconpath.concat('/$png.png')})}),\n";
      } else {
	$small .= "L.marker([$lon, $lat], {icon: new SmallIcon({iconUrl: iconpath.concat('/$png.png')})}).bindTooltip('$descr'),\n";
	$large .= "L.marker([$lon, $lat], {icon: new LeafIcon({iconUrl: iconpath.concat('/$png.png')})}).bindTooltip('$descr'),\n";
      }
    }
    
  }

    $dw=($mxlon-$mnlon)/10;
    $mnlon -= $dw;
    $mxlon += $dw;
    $w=($mxlon-$mnlon);

    $dh=($mxlat-$mnlat)/10;
    $mnlat -= $dh;
    $mxlat += $dh;
    $h=($mxlat-$mnlat);

    // YOUR CODE HERE
    $wkm=distanceGeoPoints($mxlat,$mnlon,$mxlat,$mxlon);
    $hkm=distanceGeoPoints($mnlat,$mxlon,$mxlat,$mxlon);
    //  echo 'Wkm'.$wkm;
    //  echo 'Hkm'.$hkm;
    // $HWratio=$hkm/$wkm;
    $HWratio=min(1.2,$hkm/$wkm);
  

    $nroutes=count($fname);

    $A = array('nroutes' => $nroutes,
	       'mnlat' => $mnlat,
	       'mxlat' => $mxlat,
	       'avglat' => ($mnlat+$mxlat)/2,
	       'mnlon' => $mnlon,
	       'mxlon' => $mxlon,
	       'avglon' => ($mnlon+$mxlon)/2,
	       'HWratio' => $HWratio,
	       'MXheight' => round(45/1000*$hkm),
	       'hkm' => $hkm/1000,
	       'wkm' => $wkm/1000);


  $leaflet='';
  // write constants
  foreach($A as $key => $value) {
    $leaflet .= "$key=$value;\n";
  }  

  $leaflet .= substr($large, 0, -2)."]);";

  $leaflet .= substr($small, 0, -2)."]);\n";

  $leaflet .= substr($routes, 0, -2)."]);";






  return $leaflet;
}

?>