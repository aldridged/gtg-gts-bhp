<?php
//Connect to GTS Database
$link = mysql_connect('localhost','root','d@t@c0m#-db@s3');
if (!$link) {
  die("Cannot connect to GTS db");
};

// Select the GTS Database
if (!mysql_select_db('gts')) {
  die("Cannot select GTS db");
};

//Delete events older than 30 days
$res = mysql_query("DELETE FROM EventData WHERE timestamp<".(time()-2592000).";");

if (!$res) {
  die("Error connot delete events");
};

//Free up query
mysql_free_result($res);
?>
