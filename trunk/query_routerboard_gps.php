<?php

require('/usr/local/gts/routeros_api.class.php');

$API = new routeros_api();

$API->debug = false;

//Query Botruc-41

if ($API->connect('216.226.76.91', 'admin', 'P@55w0rd!')) {

   $API->write('/system/gps/monitor');
   $API->write('=once=');
   $ARRAY = $API->read();
   $API->write('/cancel');

   $cleanlat=explode(" ",$ARRAY[0]['latitude']);
   $cleanlong=explode(" ",$ARRAY[0]['longitude']);
   
   $botruc41['latitude']=$cleanlat[1]+(trim($cleanlat[2],"'")/60)+(trim(trim($cleanlat[3],"'"),"'")/3600);
   $botruc41['longitude']=0-$cleanlong[1]-(trim($cleanlong[2],"'")/60)-(trim(trim($cleanlong[3],"'"),"'")/3600);

   $API->disconnect();

};

//Query Botruc-40

if ($API->connect('216.226.75.139', 'admin', 'P@55w0rd!')) {

   $API->write('/system/gps/monitor');
   $API->write('=once=');
   $ARRAY = $API->read();
   $API->write('/cancel');

   $cleanlat=explode(" ",$ARRAY[0]['latitude']);
   $cleanlong=explode(" ",$ARRAY[0]['longitude']);

   $botruc40['latitude']=$cleanlat[1]+(trim($cleanlat[2],"'")/60)+(trim(trim($cleanlat[3],"'"),"'")/3600);
   $botruc40['longitude']=0-$cleanlong[1]-(trim($cleanlong[2],"'")/60)-(trim(trim($cleanlong[3],"'"),"'")/3600);


   $API->disconnect();

};

//Build device insert query
$insertquery[0] = "INSERT INTO Device (accountID,deviceID,groupID,equipmentType,vehicleID,uniqueID,displayName,description,isActive,lastUpdateTime,lastInputState) VALUES ('gtg','Botruc40','lmbotruc','netmodem','Botruc40','Botruc40','Botruc40','Botruc40',1,".time().",40000) ON DUPLICATE KEY UPDATE groupID=VALUES(groupID),lastUpdateTime=VALUES(lastUpdateTime),displayName=VALUES(displayName),description=VALUES(description);";

$insertquery[1] = "INSERT INTO Device (accountID,deviceID,groupID,equipmentType,vehicleID,uniqueID,displayName,description,isActive,lastUpdateTime,lastInputState) VALUES ('gtg','Botruc41','lmbotruc','netmodem','Botruc41','Botruc41','Botruc41','Botruc41',1,".time().",40000) ON DUPLICATE KEY UPDATE groupID=VALUES(groupID),lastUpdateTime=VALUES(lastUpdateTime),displayName=VALUES(displayName),description=VALUES(description);";

//Build location insert queries
$insertquery[2] = "REPLACE INTO EventData SET accountID='gtg',deviceID='Botruc40',timestamp=".time().",statusCode=61472,latitude=".$botruc40['latitude'].",longitude=".$botruc40['longitude'].";";

$insertquery[3] = "REPLACE INTO EventData SET accountID='gtg',deviceID='Botruc41',timestamp=".time().",statusCode=61472,latitude=".$botruc41['latitude'].",longitude=".$botruc41['longitude'].";";

//Connect to GTS Database
$link = mysql_connect('localhost','root','d@t@c0m#-db@s3');
if (!$link) {
  die("Cannot connect to GTS db");
};

// Select the GTS Database
if (!mysql_select_db('gts')) {
  die("Cannot select GTS db");
};

//Perform Device and location Inserts
foreach($insertquery as $querytext)
  {
  $res = mysql_query($querytext);
  };

//Free GTS database connection
mysql_close($link);
?>
