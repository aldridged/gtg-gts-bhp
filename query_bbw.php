<?php
// Query all broadband wireless units

// Load Mikrotik API Class
require('/usr/local/gts/routeros_api.class.php');

// Convert results to integer MS
function convertms($resulttime) {
  $result1 = explode(":",$resulttime);
  $result2 = explode(".",$result1[2]);
  
  $msval = ($result1[0]*60*60*1000)+($result1[1]*60*1000)+($result2[0]*1000)+$result2[1];
  
  return($msval);
  }

// Function to make mt ping tunnel end point and get real pub ip
function mtStatusPing($host) {
  // Default ping target
  $pingtarg = '199.87.117.108';

  // Instanciate MT API call
  $API = new routeros_api();
  $API->debug = false;

  // Query Mikrotik
  if ($API->connect($host, 'admin', 'DataCom')) {
    $API->write('/interface/sstp-client/print');
    $TARGET = $API->read();
    if(empty($TARGET)) {
      $API->write('/interface/l2tp-client/print');
      $TARGET = $API->read();
      $TARGET[0]['connect-to'].=":0";
      };
    $tunuser = $TARGET[0]['user'];
    $pingtargarr = explode(':',$TARGET[0]['connect-to']);
    $pingtarg = $pingtargarr[0];
    //$API->write('/ping',false);
    //$API->write('=address='.$pingtarg,false);
    //$API->write('=count=10');
    //$ARRAY = $API->read();
    $API->disconnect();
    };
  
  // Query Tunnel End point for real public ip
  if ($API->connect($pingtarg, 'admin', 'd@t@c0m!')) {
    $API->write('/ppp/active/print');
    $TUNNELS = $API->read();
    $pubip = '0.0.0.0';
    foreach($TUNNELS as $item) {
      if($item['name']==$tunuser) $pubip = $item['caller-id'];
    };
    $API->disconnect();
    };
  
  // Query SW for current stats
  $res = mssql_query("select [RTD].NodeID, [Nodes].IP_Address, [RTD].DateTime, [RTD].MinResponseTime-20 as RTT, [RTD].PercentLoss from ResponseTime_Detail as RTD left join Nodes on [Nodes].NodeID=[RTD].NodeID where IP_Address='".$host."' and DateTime = (select max(DateTime) from ResponseTime_Detail where NodeID=[RTD].NodeID);");
  $ar = mssql_fetch_array($res);
  
  // Output results
  $avgpingtime = $ar['RTT'];
  $packetloss = $ar['PercentLoss'];
  
  // Handle failed connects
  if(!isset($ar)) {
    $avgpingtime = 0;
    $packetloss = 100;
    };

  if ($packetloss>50)
    $status = 40002;
  else if ($packetloss>20 || $avgpingtime>250)
    $status = 40001;
  else
    $status = 40000;

  return(array($status,$avgpingtime,$packetloss,$pubip));
}

// Function to extract uptime from returned system stats
function ExtractUptime($latuptime) {
  $results1 = explode(":",$latuptime);
  $results2 = explode("d",str_replace("w","d",$results1[0]));

  $realuptime = ($results2[2]*3600)+($results2[1]*3600*24)+($results2[0]*3600*24*7);
  $realuptime += ($results1[1]*60)+$results1[2];

  return($realuptime);
  }

// Function to get availability over period samples
/* MySQL version
function Avail($devid,$period) {
  $res = mysql_query("select ROUND(AVG(x.AV),2) as AV2 from (select 100-IF((substring(rawData,locate(':',rawData,10)+1,(locate('%',rawData)-locate(':',rawData,10)-1)))<100,0,100) as AV from EventData where accountID='gtg' and deviceID='".$devid."' and rawData is not null order by timestamp DESC limit ".$period.") as x;");

  if (!$res) {
    die("Error cannot get availability information");
  };

  $ar = mysql_fetch_array($res, MYSQL_BOTH);
  return($ar['AV2']);
  };
*/
function Avail($devip,$periodstart) {
	$res = mssql_query("select ROUND(AVG(Availability),4) as AverageAvail from (select [RTD].Availability from ResponseTime_Detail as RTD left join Nodes on [Nodes].NodeID=[RTD].NodeID where IP_Address='".$devip."' and DateTime >= '".$periodstart."' and Archive=0) as alist;");
	if (!$res) {
		die("Error cannot get availability information");
	};

	$ar = mssql_fetch_array($res);
	return($ar['AverageAvail']);
	};
	
// Function to get latency over period samples
/* MySQL version
function Latency($devid,$period) {
  $res = mysql_query("select ROUND(AVG(x.LT),2) as LT2 from (select substring(rawData,locate(':',rawData)+1,(locate(' ',rawData)-locate(':',rawData)-1)) as LT from EventData where accountID='gtg' and deviceID='".$devid."' and rawData is not null order by timestamp DESC limit ".$period.") as x;");

  if (!$res) {
    die("Error cannot get latency information");
  };

  $ar = mysql_fetch_array($res, MYSQL_BOTH);
  return($ar['LT2']);
  };
*/
function Latency($devip,$periodstart) {
	$res = mssql_query("select ROUND(AVG(MRT),4) as AverageRTT from (select [RTD].MinResponseTime-20 as MRT from ResponseTime_Detail as RTD left join Nodes on [Nodes].NodeID=[RTD].NodeID where IP_Address='".$devip."' and DateTime >= '".$periodstart."' and Archive=0) as alist;");
	if (!$res) {
		die("Error cannot get latency information");
	};

	$ar = mssql_fetch_array($res);
	return($ar['AverageRTT']);
	};
	
// Function to handle ping
function ping($host, $timeout = 1) {
  $package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
  $socket  = socket_create(AF_INET, SOCK_RAW, 1);
  socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
  socket_connect($socket, $host, null);

  $ts = microtime(true);
  socket_send($socket, $package, strLen($package), 0);
  if (socket_read($socket, 255))
    $result = microtime(true) - $ts;
  else
    $result = false;
  socket_close($socket);
  return $result;
  };

// Function to do 10 pings to given host and return status code, latency and packetloss
function statusping($host) {
  $pingtime = 0;
  $avgpingtime = 0;
  $packetloss = 0;

  for($i=1;$i<=10;$i++) {
    $pingtime = ping($host,2);
    if ($pingtime==FALSE)
      $packetloss++;
    else
      $avgpingtime += $pingtime;
    };

  if ($packetloss==10)
    $avgpingtime = 0;
  else
    $avgpingtime = round(($avgpingtime / (10-$packetloss))*1000);

  if ($packetloss>5)
    $status = 40002;
  else if ($packetloss>2 || $avgpingtime>250)
    $status = 40001;
  else
    $status = 40000;
    
  $packetloss *= 10; 

  return(array($status,$avgpingtime,$packetloss));
  };
  
// Function to query Mikrotik for current uptime
function mtUptime($host) {

  // Instanciate MT API call
  $API = new routeros_api();
  $API->debug = false;
  
  // Set a default return value if connection to MT fails
  $mtuptime = 0;
  
  // Query Mikrotik
  if ($API->connect($host, 'admin', 'DataCom')) {
    $API->write('/system/resource/print');
    $ARRAY = $API->read();
    $API->disconnect();
    $mtuptime = ExtractUptime($ARRAY[0]['uptime']);
    $rawuptime = $ARRAY[0]['uptime'];
    };

  // Return Uptime
  return(array($mtuptime,$rawuptime));
}
  
// Function to query Mikrotiks for current public IP address
function mtPublicIP($host) {

  // Instanciate MT API call
  $API = new routeros_api();
  $API->debug = false;
  
  // Set a default value to return if connection to MT fails
  $ipaddr = "0.0.0.0";

  // Query Mikrotik
  if ($API->connect($host, 'admin', 'DataCom')) {
    $API->write('/ip/dhcp-client/print');
    $ARRAY = $API->read();
    $API->disconnect();

    $ipaddr = substr($ARRAY['0']['address'],0,-3);

    if ($ipaddr=="") {
      $API->connect($host, 'admin', 'DataCom');
      $API->write('/ip/address/print');
      $ARRAY = $API->read();
      $API->disconnect();
    
      foreach($ARRAY as $addr) {
        if ($addr['interface']=="ether1") {
          $ipaddr = substr($addr['address'],0,-3);
          };
        };
      };
    };

  return($ipaddr);
};

//connection to the database
$dbhandle = mssql_connect("solarwinds.mydatacom.com", "bhpportal", "DataCom!")
  or die("Couldn't connect to SQL Server on solarwinds");

//select a database to work with
$selected = mssql_select_db("DC_Network", $dbhandle)
  or die("Couldn't open database DC_Network");

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

// Build Device Status Insert Array
$index=0;

// Update timestamp on broadband locations to stop it falling off the end during cleanup
$insertquery[$index] = "UPDATE EventData SET timestamp=".time()." WHERE statusCode=61472 and deviceID like 'BBW%';";
$index++;

// Loop though devices getting status information and building inserts
while ($ar = mysql_fetch_array($res, MYSQL_BOTH)) {
  list($curstat,$latency,$pl,$pubip) = mtStatusPing($ar['ipAddressCurrent']);
  list($uptime,$rawuptime) = mtUptime($ar['ipAddressCurrent']);
  if($ar['ipAddressCurrent']=='0.0.0.0') $curstat=39999;
  $date24h = date("Y-m-d H:i:s",(time() - (24 * 60 * 60)));
  $date7d = date("Y-m-d H:i:s",(time() - (7 * 24 * 60 * 60)));
  $date30d = date("Y-m-d H:i:s",(time() - (30 * 24 * 60 * 60)));
  $avail24h = Avail($ar['ipAddressCurrent'],$date24h);
  $avail7d = Avail($ar['ipAddressCurrent'],$date7d);
  $avail30d = Avail($ar['ipAddressCurrent'],$date30d);
  $latency24h = Latency($ar['ipAddressCurrent'],$date24h);
  $latency7d = Latency($ar['ipAddressCurrent'],$date7d);
  $latency30d = Latency($ar['ipAddressCurrent'],$date30d);
  $insertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$ar['deviceID']."',timestamp=".time().",statusCode=".$curstat.",rawData='Latency:".$latency." Packet Loss:".$pl."% Public IP:".$pubip." Uptime:".$uptime."';";
  $index++;
  $insertquery[$index] = "UPDATE Device SET lastInputState=".$curstat.",lastRtt=".$latency.",notes='<br>24 Hour Availability: ".$avail24h."%<br>7 Day Availability: ".$avail7d."%<br>30 Day Availability: ".$avail30d."%<br>Packet Loss: ".$pl."%<br>24 Hour Latency: ".$latency24h." ms<br>7 Day Latency: ".$latency7d." ms<br>30 Day Latency: ".$latency30d." ms<br>Current Public IP: ".$pubip."<br>Uptime: ".$rawuptime."s<br>' WHERE deviceID='".$ar['deviceID']."';";
  $index++;
};

// Perform Status and Location inserts
foreach($insertquery as $querytext)
  {
  $res = mysql_query($querytext);
  };

// Free GTS database connection
mysql_close($link);

?>
