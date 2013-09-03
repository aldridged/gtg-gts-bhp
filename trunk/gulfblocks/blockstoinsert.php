<?php

// Take ascii block definitions and changes to geozone insert statements

// Read the data and coordinate files into arrays
echo "Opening data files...\n";
$datafile = file('blocks.data');
$coordfile = file('blocks.gen');

// Loop through the data and extract the ID and block names
echo "Extracting block names...\n";
foreach ($datafile as $line_num => $line) {
  if($line_num % 10 == 0) $namekey=(trim($line))-1;
  if($line_num % 10 == 6) $insertdata[$namekey]['blockname']=trim(trim($line),'"');
};

// Loop through the coordinates
echo "Extracting coordinates...\n";
$coordkey = 1;
foreach ($coordfile as $line_num => $line) {
  $contents = preg_split("/[\s]+/",$line);
  if(count($contents)==5) {
    $coordkey = 1;
    $namekey=trim($contents[1]);
    // Ignore first set of coordinates - they are the center of the block
  }
  else if (count($contents)==4) {
    $latkey = "lat".$coordkey;
    $longkey = "long".$coordkey;
    $lat=substr(trim($contents[2]),2,2).".".substr(trim($contents[2]),4,13);
    $long="-".substr(trim($contents[1]),3,2).".".substr(trim($contents[1]),5,13);
    $insertdata[$namekey][$latkey]=$lat;
    $insertdata[$namekey][$longkey]=$long;
    $coordkey++;
  }
  else {
    $insertdata[$namekey]['count']=($coordkey-2);
    // Set to -2 to drop last coordinate - which is also center of block
  };
};

//Create the insert query
echo "Creating insert query...\n";
$index=1;
foreach ($insertdata as $blockrecord) {
  $coordindex = 1;
  $minLat = $blockrecord['lat1'];
  $maxLat = $blockrecord['lat1'];
  $minLong = $blockrecord['long1'];
  $maxLong = $blockrecord['long1'];
  $insertquery[$index] = "REPLACE INTO Geozone SET accountID='gtg',geozoneID='".strtolower($blockrecord['blockname'])."',reverseGeocode=1,arrivalZone=1,departureZone=1,zoneType=3,displayName='".$blockrecord['blockname']."',description='".$blockrecord['blockname']."'";
  while ($coordindex <= $blockrecord['count']) {
    $insertquery[$index] .= ",latitude".$coordindex."=".$blockrecord['lat'.$coordindex].",longitude".$coordindex."=".$blockrecord['long'.$coordindex];
    if($blockrecord['lat'.$coordindex]<$minLat) $minLat = $blockrecord['lat'.$coordindex];
    if($blockrecord['lat'.$coordindex]>$maxLat) $maxLat = $blockrecord['lat'.$coordindex];
    if($blockrecord['long'.$coordindex]<$minLong) $minLong = $blockrecord['long'.$coordindex];
    if($blockrecord['long'.$coordindex]>$maxLong) $maxLong = $blockrecord['long'.$coordindex];
    $coordindex++;
  };
  $insertquery[$index] .= ",minLatitude=".$minLat.",maxLatitude=".$maxLat.",minLongitude=".$minLong.",maxLongitude=".$maxLong;
  $insertquery[$index] .= ",lastUpdateTime=".time().",creationTime=".time().";";
  $index++;
};
    
//Insert data into GTS database
echo "Inserting geozones into GTS database...\n";

//Connect to GTS Database
$link = mysql_connect('localhost','root','d@t@c0m#-db@s3');
if (!$link) {
  die("Cannot connect to GTS db");
};

// Select the GTS Database
if (!mysql_select_db('gts')) {
  die("Cannot select GTS db");
};

//Perform Geozone Inserts
foreach($insertquery as $querytext)
  {
  $res = mysql_query($querytext);
  };

//Free GTS database connection
mysql_close($link);

//Done
echo "Geozone insert complete.\n";
?>
