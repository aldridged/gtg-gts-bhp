<?php
// Check all devices looking for poweroff holes

// Load required classes for email
require_once('Classes/class.phpmailer.php');

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
	} else if (($curup > $prevup)&&(($prevts-$curts)>700)) {
	  // Abnormal condition uptime is ascending indicates reboot, with a >11 minute difference in time
	  $update_query[$index] = "update EventData set rawData='Latency:".$prevlat." Packet Loss:".$prevpl."% Public IP:".$prevpubip." Uptime:".$prevup."' where deviceID='".$ar['deviceID']."' and timestamp < ".$prevts." and timestamp > ".$curts.";";
	  $english_update[$index] = "Setting latency to ".$prevlat.", packet loss to ".$prevpl."%, public IP to ".$prevpubip." and uptime to ".$prevup." for ".$ar['deviceID']." from ".date('m/d/Y H:i',$prevts)." to ".date('m/d/Y H:i',$curts).".<br>"; 
	  $index++;
	  $prevts = $curts;
	  $prevlat = $curlat;
	  $prevpl = $curpl;
	  $prevup = $curup;
	  $prevpubip = $curpubip;
	} else {
	  $prevts = $curts;
	  $prevlat = $curlat;
	  $prevpl = $curpl;
	  $prevup = $curup;
	  $prevpubip = $curpubip;
	  };
	};
  };
  
//If we have output, then apply the updates
if (isset($update_query)) {
  foreach($update_query as $query) {
    $res = mysql_query($query);
	};
  };
  
//and email english version to BHP Team
if (isset($english_update)) {
  foreach($english_query as $query) {
    $updates .= $query;
	};
  $mail = new PHPMailer();
  $mail->IsSendmail();
  $mail->AddAddress('bhpteam@globalgroup.us', 'BHP Team');
  $mail->SetFrom('reports@dcportal.mydatacom.com', 'DC Portal Reports');
  $mail->Subject = 'SLA Modification Report';
  $mail->AltBody = 'The SLA modification routine made changes to the recorded stats to fill in suspected power outages:\n'.str_replace("<br>","\n",$updates);
  $mail->Body = 'The SLA modification routine made changes to the recorded stats to fill in suspected power outages:<br>'.$updates;
  $mail->Send();
  };
?>