<?php

///Function to calculate Bearing between two coordinates
function calculateBearing($lat1,$long1,$lat2,$long2) {
  $y = $lat2-$lat1;
  $x = $long2-$long1;
  
  if($x==0 AND $y==0){ return 0; };
  return ($x < 0) ? rad2deg(atan2($x,$y))+360 : rad2deg(atan2($x,$y)); 
 };

//Function to calculate speed of movement between two coordinates in 10 minutes
function calculateSpeed($lat1,$long1,$lat2,$long2,$deltatime) {
  $dist = acos(sin($lat1)*sin($lat2)+cos($lat1)*cos($lat2)*cos($long2-$long1))*6.371;
  return($dist/($deltatime/3600));
  };

$bearing = calculateBearing(28.88,-91.82,28.93,-91.92);
$speed = calculateSpeed(28.88,-91.82,28.93,-91.92,1200);
echo "Bearing:$bearing  Speed:$speed\n"  
?>