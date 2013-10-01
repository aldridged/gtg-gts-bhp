<?php
//Connect to GTS Database
$link = mysql_connect('localhost','root','d@t@c0m#');
if (!$link) {
  die("Cannot connect to GTS db");
};

// Select the GTS Database
if (!mysql_select_db('gts')) {
  die("Cannot select GTS db");
};

//Delete all events 
$res = mysql_query("TRUNCATE TABLE EventData;");

if (!$res) {
  die("Error connot delete events");
};

//Free up query
mysql_free_result($res);
?>
