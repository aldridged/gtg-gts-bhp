<?php
/* Datapump - Devices from NMS to GTS */

// Open link to NMS database
$link = mysql_connect('10.110.254.11','noc','datacom');
if (!$link) {
  die("Cannot connect to NMS db");
};

// Select the NMS Database
if (!mysql_select_db('nms')) {
  die("Cannot select NMS db");
};

// Query Devices from NMS Database
$res = mysql_query("SELECT NetModemId,ModemSn,NetModemName,LatDegrees,LatMinutes,LatSeconds,LongDegrees,LongMinutes,LongSeconds FROM NetModem,GeoLocation WHERE LocationID=GeoLocationID;");

if (!$res) {
  die("Error cannot select devices");
};

//Build Device Insert Array
$index=0;
while ($ar = mysql_fetch_array($res, MYSQL_BOTH)) {
  $gid=explode(" ",$ar["NetModemName"]);
  $deviceinsertquery[$index] = "REPLACE INTO Device SET accountID='gtg',deviceID='".$ar["NetModemId"]."',groupID='".$gid[0]."',equipmentType='netmodem',vehicleID='".$ar["ModemSn"]."',uniqueID='".$ar["NetModemId"]."',displayName='".$ar["NetModemName"]."',description='".$ar["NetModemName"]."',isActive=1,lastUpdateTime=".time().",lastInputState=40000;";
  $geolocinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["NetModemId"]."',timestamp=".time().",statusCode=61472,latitude=".($ar["LatDegrees"]+($ar["LatMinutes"]/60)+($ar["LatSeconds"]/3600)).",longitude=".(0-($ar["LongDegrees"]+($ar["LongMinutes"]/60)+($ar["LongSeconds"]/3600))).";";
  $index++;
};

// Query Disabled Devices from NMS Database
$res = mysql_query("SELECT NetModemId FROM NetModem WHERE ActiveStatus=0;");

if (!$res) {
  die("Error cannot select disabled devices");
};

//Build Disabled Device Insert Array
$index=0;
while ($ar = mysql_fetch_array($res, MYSQL_BOTH)) {
  $disabledinsertquery[$index] = "UPDATE Device SET lastInputState=39999 WHERE deviceID='".$ar["NetModemId"]."';";
  $index++;
};

//Free up query
mysql_free_result($res);

$res = mysql_query("SELECT unique_id,timestamp,current_state,reason FROM state_change_log_0 ORDER BY timestamp ASC LIMIT 3000;");
$res1 = mysql_query("SELECT unique_id,timestamp,current_state,reason FROM state_change_log_1 ORDER BY timestamp ASC LIMIT 3000;");
$res2 = mysql_query("SELECT unique_id,timestamp,current_state,reason FROM state_change_log_2 ORDER BY timestamp ASC LIMIT 3000;");
$res3 = mysql_query("SELECT unique_id,timestamp,current_state,reason FROM state_change_log_3 ORDER BY timestamp ASC LIMIT 3000;");

if (!$res || !$res1 || !$res2 || !$res3) {
  die("Error cannot select state changes");
};

//Build State Insert Array
$index=0;
while ($ar = mysql_fetch_array($res, MYSQL_BOTH)) {
  if($ar["current_state"]=="OK") {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40000,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40000,lastReason='".$ar["reason"]."' where deviceID='".$ar["unique_id"]."';";
    $index++;
    }
  else if($ar["current_state"]=="WARNING") {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40001,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40001,lastReason='".$ar["reason"]."' where deviceID='".$ar["unique_id"]."';";
    $index++;
    }
  else {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40002,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40002,lastReason='".$ar["reason"]."' where deviceID='".$ar["unique_id"]."';";
    $index++;
    };
  };

while ($ar = mysql_fetch_array($res1, MYSQL_BOTH)) {
  if($ar["current_state"]=="OK") {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40000,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40000,lastReason='".$ar["reason"]."' where deviceID='".$ar["unique_id"]."';";
    $index++;
    }
  else if($ar["current_state"]=="WARNING") {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40001,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40001,lastReason='".$ar["reason"]."' where deviceID='".$ar["unique_id"]."';";
    $index++;
    }
  else {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40002,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40002,lastReason='".$ar["reason"]."' where deviceID='".$ar["unique_id"]."';";
    $index++;
    };
  };

while ($ar = mysql_fetch_array($res2, MYSQL_BOTH)) {
  if($ar["current_state"]=="OK") {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40000,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40000,lastReason='".$ar["reason"]."' where deviceID='".$ar["unique_id"]."';";
    $index++;
    }
  else if($ar["current_state"]=="WARNING") {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40001,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40001,lastReason='".$ar["reason"]."' where deviceID='".$ar["unique_id"]."';";
    $index++;
    }
  else {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40002,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40002,lastReason='".$ar["reason"]."' where deviceID='".$ar["unique_id"]."';";
    $index++;
    };
  };

while ($ar = mysql_fetch_array($res3, MYSQL_BOTH)) {
  if($ar["current_state"]=="OK") {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40000,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40000,lastReason='".$ar["reason"]."' where deviceID='".$ar["unique_id"]."';";
    $index++;
    }
  else if($ar["current_state"]=="WARNING") {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40001,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40001,lastReason='".$ar["reason"]."' where deviceID='".$ar["unique_id"]."';";
    $index++;
    }
  else {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40002,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40002,lastReason='".$ar["reason"]."' where deviceID='".$ar["unique_id"]."';";
    $index++;
    };
  };

// Query downstream stats from nrd_archive database
$res = mysql_query("(SELECT unique_id,MAX(timestamp) as MT,snr_cal,power_in_dbm FROM nms_remote_status_0 GROUP BY unique_id) UNION (SELECT unique_id,MAX(timestamp) as MT,snr_cal,power_in_dbm FROM nms_remote_status_1 GROUP BY unique_id) UNION (SELECT unique_id,MAX(timestamp) as MT,snr_cal,power_in_dbm FROM nms_remote_status_2 GROUP BY unique_id) UNION (SELECT unique_id,MAX(timestamp) as MT,snr_cal,power_in_dbm FROM nms_remote_status_3 GROUP BY unique_id) ORDER BY MT;");

if (!$res) {
  die("Error cannot select downstream stats");
};

//Build Downstream Stats Insert Array
$index=0;
while ($ar = mysql_fetch_array($res, MYSQL_BOTH)) {
  $dstatsinsertquery[$index] = "UPDATE Device SET lastSnrCalDown=".$ar["snr_cal"].",lastPowerDbm=".$ar["power_in_dbm"]." WHERE deviceID='".$ar["unique_id"]."';";
  $index++;
  };

// Free result sets
mysql_free_result($res);

// Query upstream stats from nrd_archive database
$res = mysql_query("(SELECT unique_id,MAX(timestamp) AS MT,snr_cal FROM nms_ucp_info_0 GROUP BY unique_id) UNION (SELECT unique_id,MAX(timestamp) AS MT,snr_cal FROM nms_ucp_info_1 GROUP BY unique_id) UNION (SELECT unique_id,MAX(timestamp) AS MT,snr_cal FROM nms_ucp_info_2 GROUP BY unique_id) UNION (SELECT unique_id,MAX(timestamp) AS MT, snr_cal FROM nms_ucp_info_3 GROUP BY unique_id) ORDER BY MT;");

if (!$res) {
  die("Error cannot select upstream stats");
};

//Build Upstream Stats Insert Array
$index=0;
while ($ar = mysql_fetch_array($res, MYSQL_BOTH)) {
  $ustatsinsertquery[$index] = "UPDATE Device SET lastSnrCalUp=".$ar["snr_cal"]." WHERE deviceID='".$ar["unique_id"]."';";
  $index++;
  };

// Free result sets
mysql_free_result($res);

// Query latency stats from nrd_archive database

$res = mysql_query("(SELECT unique_id,MAX(timestamp) AS MT,rtt FROM lat_stats_0 GROUP BY unique_id) UNION (SELECT unique_id,MAX(timestamp) AS MT,rtt FROM lat_stats_1 GROUP BY unique_id) UNION (SELECT unique_id,MAX(timestamp) AS MT,rtt FROM lat_stats_2 GROUP BY unique_id) UNION (SELECT unique_id,MAX(timestamp) AS MT, rtt FROM lat_stats_3 GROUP BY unique_id) ORDER BY MT;");

if (!$res) {
  die("Error cannot select latency stats");
};

//Build Latency Stats Insert Array
$index=0;
while ($ar = mysql_fetch_array($res, MYSQL_BOTH)) {
  if($ar["rtt"]<0) $ar["rtt"] = 0;
  $lstatsinsertquery[$index] = "UPDATE Device SET lastRtt=".$ar["rtt"]." WHERE deviceID='".$ar["unique_id"]."';";
  $index++;
  };

// Free result sets
mysql_free_result($res);

//Connect to GTS Database
$link = mysql_connect('localhost','root','d@t@c0m#-db@s3');
if (!$link) {
  die("Cannot connect to GTS db");
};

// Select the GTS Database
if (!mysql_select_db('gts')) {
  die("Cannot select GTS db");
};

//Preserve Current Device Status
$res = mysql_query("SELECT deviceID,lastInputState,lastReason FROM Device;");

if (!$res) {
  die("Error cannot preserve input state");
};

//Build Input State Insert Array
$index=0;
while ($ar = mysql_fetch_array($res, MYSQL_BOTH)) {
  $curstateinsertquery[$index] = "UPDATE Device SET lastInputState=".$ar["lastInputState"].",lastReason='".$ar["lastReason"]."' WHERE accountID='gtg',deviceID='".$ar["deviceID"]."';";
  $index++;
};

//Free up query
mysql_free_result($res);


//Perform Device Inserts
foreach($deviceinsertquery as $querytext)
  {
  $res = mysql_query($querytext);
  };

//Perform Location Inserts
foreach($geolocinsertquery as $querytext)
  {
  $res = mysql_query($querytext);
  };

//Perform Event Inserts
foreach($eventinsertquery as $querytext)
  {
  $res = mysql_query($querytext);
  };

//Restore previous state information
foreach($curstateinsertquery as $querytext)
  {
  $res = mysql_query($querytext);
  };

//Perform State Change Inserts
foreach($stateinsertquery as $querytext)
  {
  $res = mysql_query($querytext);
  };

//Perform Stats Inserts
foreach($dstatsinsertquery as $querytext)
  {
  $res = mysql_query($querytext);
  };

foreach($ustatsinsertquery as $querytext)
  {
  $res = mysql_query($querytext);
  };

foreach($lstatsinsertquery as $querytext)
  {
  $res = mysql_query($querytext);
  };

//Disable disabled devices
foreach($disabledinsertquery as $querytext)
  {
  $res = mysql_query($querytext);
  };

//Free up NMS and GTS database connection
mysql_close($link);
?>
