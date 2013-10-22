<?php
// Check all devices looking for poweroff holes

// Connect to GTS Database
$link = mysql_connect('localhost','root','d@t@c0m#');
if (!$link) {
  die("Cannot connect to GTS db");
};

// Select the GTS Database
if (!mysql_select_db('gts')) {
  die("Cannot select GTS db");
};

// Query Broadband Devices from GTS Database
$res = mysql_query("SELECT deviceID,ipAddressCurrent FROM Device WHERE isActive=1 AND deviceID like 'BBW%';");

if (!$res) {
  die("Error cannot select devices");
};

// Build EventData update queries
$index=0;

// Loop through Devices
while ($ar = mysql_fetch_array($res, MYSQL_BOTH)) {
  $res2 = mysql_query("select timestamp,substring(rawData,locate(':',rawData)+1,(locate(' ',rawData)-(locate(':',rawData))-1)) as Latency,substring(rawData,locate(':',rawData,10)+1,(locate('%',rawData)-locate(':',rawData,10)-1)) as PacketLoss,substring(rawData,locate(':',rawData,35)+1,(locate(' ',rawData,35)-locate(':',rawData,35)-1)) as PublicIP,substring(rawData,locate(':',rawData,40)+1) as Uptime from EventData where accountID='gtg' and deviceID='".$ar['deviceID']."' and rawData is not null order by timestamp desc limit 144;");
  
  // Get initial conditions
  $ic = mysql_fetch_array($res2, MYSQL_BOTH);
  $prevts = $ic['timestamp'];
  $prevlat = $ic['Latency'];
  $prevpl = $ic['PacketLoss'];
  $prevup = $ic['Uptime'];
  $prevpubip = $ic['PublicIP'];
  
  // Loop through events for devices
  while ($ar2 = mysql_fetch_array($res2, MYSQL_BOTH)) {
    $curts = $ar2['timestamp'];
	$curlat = $ar2['Latency'];
	$curpl = $ar2['PacketLoss'];
	$curup = $ar2['Uptime'];
	$curpubip = $ar2['PublicIP'];
	
	if(($curup==0)||($curpl==100)) {
	  //Ignore 0 uptimes or 100% packet loss
	  continue;
	} else if ($curup <= $prevup) {
	  // Normal condition uptime is descending
	  $prevts = $curts;
	  $prevlat = $curlat;
	  $prevpl = $curpl;
	  $prevup = $curup;
	  $prevpubip = $curpubip;
	} else if ($curup > $prevup) {
	  // Abnormal condition uptime is ascending indicates reboot
	  $update_query[$index] = "update EventData set rawData='Latency:".$prevlat." Packet Loss:".$prevpl."% Public IP:".$prevpubip." Uptime:".$prevup."' where deviceID='".$ar['deviceID']."' and timestamp < ".$prevts." and timestamp > ".$curts.";";
	  $index++;
	  $prevts = $curts;
	  $prevlat = $curlat;
	  $prevpl = $curpl;
	  $prevup = $curup;
	  $prevpubip = $curpubip;
	  };
	};
  };
  
  print_r($update_query);
  ?>