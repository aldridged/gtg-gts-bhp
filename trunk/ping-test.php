<?php
// Test of getting ping from tik

// Load Mikrotik API Class
require('/usr/local/gts/routeros_api.class.php');
$host="172.17.10.60";

// Convert results to integer MS
function convertms($resulttime) {
  $result1 = explode(":",$resulttime);
  $result2 = explode(".",$result1[2]);
  
  $msval = ($result1[0]*60*60*1000)+($result1[1]*60*1000)+($result2[0]*1000)+$result2[1];
  
  return($msval);
  }

// Instanciate MT API call
$API = new routeros_api();
$API->debug = false;

// Query Mikrotik
if ($API->connect($host, 'admin', 'DataCom')) {
  $API->write('/interface/sstp-client/print');
  $TARGET = $API->read();
  $tunuser = $TARGET[0]['user'];
  $pingtargarr = explode(':',$TARGET[0]['connect-to']);
  $pingtarg = $pingtargarr[0];
  echo("Ping Target: ".$pingtarg."\n");
  $API->write('/ping',false);
  $API->write('=address='.$pingtarg,false);
  $API->write('=count=10');
  $ARRAY = $API->read();
  $API->disconnect();
  };
  
// Query Tunnel End point for real public ip
if ($API->connect($pingtarg, 'admin', 'D@t@c0m#')) {
  $API->write('/ppp/active/print');
  $TUNNELS = $API->read();
  $pubip = '0.0.0.0';
  foreach($TUNNELS as $item) {
    if($item['name']==$tunuser) $pubip = $item['caller-id'];
  };
  $API->disconnect();
  };
  
// Output results
$depth = count($ARRAY)-1;

$avgping = convertms($ARRAY[$depth]['avg-rtt']);
$packetloss = $ARRAY[$depth]['packet-loss'];

echo "Public IP is ".$pubip."\n";
echo "Average ping is ".$avgping." ms with ".$packetloss." % packet loss\n";

?>
