<?php
// Email Alerts Function
function email_alert($device, $status, $to)
{
$subject = "Datacom GTS Status Change Alert";
$mes = "The status for device ".$device." has changed.\n\n";
$mes = $mes."The status is now : ".$status."\n\n";
system("echo '$mes' | mail -s '$subject' $to -b trouble@getdatacom.com --f alerts@mydatacomgts.com");
};

// Open link to GTS database
$link = mysql_connect('localhost','root','d@t@c0m#-db@s3');
if (!$link) {
  die("Cannot connect to GTS db");
};

// Select the GTS Database
if (!mysql_select_db('gts')) {
  die("Cannot select GTS db");
};

// Query Devices with contact emails from the GTS database
$res = mysql_query("SELECT Device.deviceID,Device.displayName,contactEmail FROM User,GroupList,DeviceList,Device WHERE Device.deviceID=DeviceList.deviceID AND User.userID=GroupList.userID AND GroupList.groupID=DeviceList.groupID AND User.contactEmail like '%@%';");
if (!$res) {
  die("Error cannot select devices");
};

// Parse out status changes for listed deviceIDs
while ($ar = mysql_fetch_array($res, MYSQL_BOTH)) {
  $dev = $ar['deviceID'];
  $devname = $ar['displayName'];
  $email = $ar['contactEmail'];
  $stati = mysql_query("SELECT deviceID,timestamp,statusCode,rawData FROM EventData WHERE deviceID='".$dev."' AND statusCode<>61472 AND statusCode<>64816 ORDER BY timestamp DESC LIMIT 2;");
  $idx = 0;
  while ($sr = mysql_fetch_array($stati,MYSQL_BOTH)) { 
    $returnstatus[$idx][0]=$sr['statusCode'];
    $returnstatus[$idx][1]=$sr['rawData'];
    $returnstatus[$idx][2]=$sr['timestamp'];
    $idx++;
    };
    
// If last two statuses are not the same and this happened in the last 5 minutes (300 seconds) then email
  if(($returnstatus[0][0]<>$returnstatus[1][0])&&(time()-$returnstatus[0][2]<300)) email_alert($devname,$returnstatus[0][1],$email);

// DEBUG - Remove after you figure out whats wrong
/*  echo($devname." - ".$email." :\n");
  print_r($returnstatus);
  echo("\n\n");
*/
// Clean up our mess
  unset($returnstatus);
  mysql_free_result($stati); 
};

// Clean up hanging connecions
mysql_free_result($res);
mysql_close($link);
?>
