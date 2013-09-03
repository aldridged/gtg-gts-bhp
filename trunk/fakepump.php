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

//Free up query
mysql_free_result($res);

// Select the NRD_ARCHIVE Database
if (!mysql_select_db('nrd_archive')) {
  die("Cannot select NRD_ARCIHVE db");
};

// Query Events from nrd_archive database
$res = mysql_query("SELECT unique_id,timestamp,msg FROM event_msg_0 ORDER BY timestamp DESC LIMIT 3000;");
$res1 = mysql_query("SELECT unique_id,timestamp,msg FROM event_msg_1 ORDER BY timestamp DESC LIMIT 3000;");
$res2 = mysql_query("SELECT unique_id,timestamp,msg FROM event_msg_2 ORDER BY timestamp DESC LIMIT 3000;");
$res3 = mysql_query("SELECT unique_id,timestamp,msg FROM event_msg_3 ORDER BY timestamp DESC LIMIT 3000;");

if (!$res || !$res1 || !$res2 || !$res3) {
  die("Error cannot select events");
};

//Build Event Insert Array
$index=0;
while ($ar = mysql_fetch_array($res, MYSQL_BOTH)) {
  if(substr($ar["msg"],0,3)=="LAT") {
    $realtime = strtotime($ar["timestamp"]);
    $n = sscanf($ar["msg"], "LAT-LONG : [LAT = %fN LONG = %fW", $lat, $long);
    $long = 0-$long;
    if($n>1) {
      $eventinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=61472,latitude=".$lat.",longitude=".$long.";";
      $index++;
    };
  };
};

while ($ar = mysql_fetch_array($res1, MYSQL_BOTH)) {
  if(substr($ar["msg"],0,3)=="LAT") {
    $realtime = strtotime($ar["timestamp"]);
    $n = sscanf($ar["msg"], "LAT-LONG : [LAT = %fN LONG = %fW", $lat, $long);
    $long = 0-$long;
    if($n>1) {
      $eventinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=61472,latitude=".$lat.",longitude=".$long.";";
      $index++;
    };
  };
};

while ($ar = mysql_fetch_array($res2, MYSQL_BOTH)) {
  if(substr($ar["msg"],0,3)=="LAT") {
    $realtime = strtotime($ar["timestamp"]);
    $n = sscanf($ar["msg"], "LAT-LONG : [LAT = %fN LONG = %fW", $lat, $long);
    $long = 0-$long;
    if($n>1) {
      $eventinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=61472,latitude=".$lat.",longitude=".$long.";";
      $index++;
    };
  };
};

while ($ar = mysql_fetch_array($res3, MYSQL_BOTH)) {
  if(substr($ar["msg"],0,3)=="LAT") {
    $realtime = strtotime($ar["timestamp"]);
    $n = sscanf($ar["msg"], "LAT-LONG : [LAT = %fN LONG = %fW", $lat, $long);
    $long = 0-$long;
    if($n>1) {
      $eventinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=61472,latitude=".$lat.",longitude=".$long.";";
      $index++;
    };
  };
};

//Free up query
mysql_free_result($res);
mysql_free_result($res1);
mysql_free_result($res2);
mysql_free_result($res3);

// Query State Changes from nrd_archive database
$res = mysql_query("SELECT unique_id,timestamp,current_state,reason FROM state_change_log_0 ORDER BY timestamp DESC LIMIT 3000;");
$res1 = mysql_query("SELECT unique_id,timestamp,current_state,reason FROM state_change_log_1 ORDER BY timestamp DESC LIMIT 3000;");
$res2 = mysql_query("SELECT unique_id,timestamp,current_state,reason FROM state_change_log_2 ORDER BY timestamp DESC LIMIT 3000;");
$res3 = mysql_query("SELECT unique_id,timestamp,current_state,reason FROM state_change_log_3 ORDER BY timestamp DESC LIMIT 3000;");

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
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40000 where deviceID='".$ar["unique_id"]."';";
    $index++;
    }
  else if($ar["current_state"]=="WARNING") {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40001,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40001 where deviceID='".$ar["unique_id"]."';";
    $index++;
    }
  else {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40002,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40002 where deviceID='".$ar["unique_id"]."';";
    $index++;
    };
  };

while ($ar = mysql_fetch_array($res1, MYSQL_BOTH)) {
  if($ar["current_state"]=="OK") {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40000,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40000 where deviceID='".$ar["unique_id"]."';";
    $index++;
    }
  else if($ar["current_state"]=="WARNING") {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40001,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40001 where deviceID='".$ar["unique_id"]."';";
    $index++;
    }
  else {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40002,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40002 where deviceID='".$ar["unique_id"]."';";
    $index++;
    };
  };

while ($ar = mysql_fetch_array($res2, MYSQL_BOTH)) {
  if($ar["current_state"]=="OK") {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40000,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40000 where deviceID='".$ar["unique_id"]."';";
    $index++;
    }
  else if($ar["current_state"]=="WARNING") {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40001,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40001 where deviceID='".$ar["unique_id"]."';";
    $index++;
    }
  else {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40002,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40002 where deviceID='".$ar["unique_id"]."';";
    $index++;
    };
  };

while ($ar = mysql_fetch_array($res3, MYSQL_BOTH)) {
  if($ar["current_state"]=="OK") {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40000,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40000 where de
viceID='".$ar["unique_id"]."';";
    $index++;
    }
  else if($ar["current_state"]=="WARNING") {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40001,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40001 where deviceID='".$ar["unique_id"]."';";
    $index++;
    }
  else {
    $realtime = strtotime($ar["timestamp"]);
    $stateinsertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar["unique_id"]."',timestamp=".$realtime.",statusCode=40002,rawData='".$ar["reason"]."';";
    $index++;
    $stateinsertquery[$index] = "UPDATE Device SET lastInputState=40002 where deviceID='".$ar["unique_id"]."';";
    $index++;
    };
  };

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
$res = mysql_query("SELECT deviceID,lastInputState FROM Device;");

if (!$res) {
  die("Error cannot preserve input state");
};

//Build Input State Insert Array
$index=0;
while ($ar = mysql_fetch_array($res, MYSQL_BOTH)) {
  $curstateinsertquery[$index] = "UPDATE Device SET lastInputState=".$ar["lastInputState"]." WHERE accountID='gtg',deviceID='".$ar["deviceID"]."';";
  $index++;
};

//Free up query
mysql_free_result($res);

//Perform Device Inserts

  print_r($deviceinsertquery);

//Perform Location Inserts

  print_r($geolocinsertquery);

//Perform Event Inserts

  print_r($eventinsertquery);

//Restore previous state information

  print_r($curstateinsertquery);

//Perform State Change Inserts

  print_r($stateinsertquery);

//Free up NMS and GTS database connection
mysql_close($link);
?>
