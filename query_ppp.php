<?php
// Email Alerts Function
function email_alert($message, $subject)
{
system("echo '$message' | mail -s '$subject' trouble@getdatacom.com -- -f alerts@mydatacomgts.com");
};

// Read old states from file
$oldstate = parse_ini_file("/root/oldstatefile.txt");

// Get current states from Mikrotik
require('/usr/local/gts/routeros_api.class.php');
$API = new routeros_api();
$API->debug = false;

// Query Mikrotik
if ($API->connect('204.9.216.102', 'admin', 'd@t@c0m#')) {
   $API->write('/ppp/active/print');
   $ARRAY = $API->read();
   $API->disconnect();

   foreach($ARRAY as $item) {
     $currentstate[$item['name']] = $item['caller-id'];
    };

  $savefile = "";

  foreach ($currentstate as $name=>$ip) {
    $savefile .= $name."=".$ip."\n";
    };

  file_put_contents("/root/oldstatefile.txt",$savefile);
};

// Compare old to new arrays
$newchange = array_diff_assoc($currentstate,$oldstate);
$oldchange = array_diff_assoc($oldstate,$currentstate);

// Figure out changed IPs and Tunnel Ups
$tunnelchange = "";
$tunnelup = "";
foreach ($newchange as $name=>$ip) {
  if(isset($oldstate[$name])) {
    $tunnelchange .= "Tunnel connecting IP change for ".$name." from ".$oldstate[$name]. " to ".$ip."\r\n";
  } else {
    $tunnelup .= "Tunnel ".$name." came up with connecting IP ".$ip."\r\n";
  };
};

// Figure out tunnel downs
$tunneldown = "";
foreach ($oldchange as $name=>$ip) {
  if(!isset($currentstate[$name])) $tunneldown .= "Tunnel ".$name." is down - last connecting IP ".$ip."\r\n";
};

// Email the alerts
if(!$tunnelup=="") email_alert($tunnelup,"MikroTik Tunnels UP");
if(!$tunneldown=="") email_alert($tunneldown, "MikroTik Tunnels DOWN");
if(!$tunnelchange=="") email_alert($tunnelchange, "MikroTik Tunnels CHANGED IP");
?>

