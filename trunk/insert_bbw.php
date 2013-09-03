<?php
// Insert Broadband Wireless Device into GTS database

// Check number of arguments are correct, notify user otherwise
if(count($argv)<6)
  die("Usage: insert_bbw.php deviceID deviceName IPAddress Latitude Longitude\n");

// Connect to GTS Database
$link = mysql_connect('localhost','root','d@t@c0m#-db@s3');
if (!$link) {
  die("Cannot connect to GTS db");
};

// Select the GTS Database
if (!mysql_select_db('gts')) {
  die("Cannot select GTS db");
};

// Create queries to insert device
$devicequery = "INSERT INTO Device (accountID,deviceID,groupID,equipmentType,vehicleID,uniqueID,displayName,description,ipAddressCurrent,isActive,lastUpdateTime) VALUES ('gtg','".$argv[1]."','bbw','netmodem','".$argv[2]."','".$argv[1]."','".$argv[2]."','".$argv[2]."','".$argv[3]."',1,".time().") ON DUPLICATE KEY UPDATE groupID=VALUES(groupID),lastUpdateTime=VALUES(lastUpdateTime),ipAddressCurrent=VALUES(ipAddressCurrent);";
$eventquery = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$argv[1]."',timestamp=".time().",statusCode=61472,latitude=".$argv[4].",longitude=".$argv[5].";";
$groupquery = "INSERT INTO DeviceList (accountID,groupID,deviceID) values ('gtg','bbw','".$argv[1]."');";

// Perform inserts
$res = mysql_query($devicequery);
$res = mysql_query($eventquery);
$res = mysql_query($groupquery);

// Notify User
echo("Device sucessfully inserted into database\n");

// Free GTS database connection
mysql_close($link);
?>
