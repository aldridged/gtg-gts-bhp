<?php
// Query KVH units from KML file
// Added Geozone support
// Added heading support

//Function to calculate Bearing between two coordinates
function calculateBearing($lat1,$long1,$lat2,$long2) {
  $y = $lat2-$lat1;
  $x = $long2-$long1;
    
  if($x==0 AND $y==0){ return 0; };
  return ($x < 0) ? rad2deg(atan2($x,$y))+360 : rad2deg(atan2($x,$y)); 
 };

// Function to handle inserting heading data
function handleHeading($link,$devid,$lat,$long) {
  // Find previous location event for devid
  $res = mysql_query("SELECT statusCode,timestamp,latitude,longitude FROM EventData WHERE (deviceID='".$devid."' AND (latitude<>".$lat." OR longitude<>".$long.") AND statusCode=61472) ORDER BY timestamp DESC LIMIT 1",$link);
  
  // If there is a previous location
  if(mysql_num_rows($res)>0) {
    list($statusCode,$lasttime,$lastlat,$lastlong) = mysql_fetch_row($res);
 
    // calculate heading and speed
    $heading = calculateBearing($lastlat,$lastlong,$lat,$long);
    };
    
  if(isset($heading)) { return($heading); } else { return(0); };
  }  

// Function to handle inserting Geozone arrival/departure codes
function handleGeozone($link,$devid,$lat,$long) {
  // Find previous geozone events for devid
  $res = mysql_query("SELECT geozoneID,statusCode,address FROM EventData WHERE (deviceID='".$devid."' AND (statusCode=61968 OR statusCode=62000) AND geozoneID IS NOT NULL) ORDER BY timestamp DESC LIMIT 1",$link);

  // If there are events
  if(mysql_num_rows($res)>0) {
    list($geozoneID,$statusCode,$oldaddr) = mysql_fetch_row($res);

    // If the previous event is an arrival
    if($statusCode==61968) {
      $res = mysql_query("SELECT geozoneID,displayName FROM Geozone WHERE (minLatitude<=".$lat." AND maxLatitude>=".$lat." AND minLongitude<=".$long." AND maxLongitude>=".$long.") LIMIT 1",$link);
      if(mysql_num_rows($res)>0) {
        list($newgeozoneID,$geodesc) = mysql_fetch_row($res);
        if($newgeozoneID<>$geozoneID) { 
          // Insert departure from previous and arrival in current
          $insert = "INSERT INTO EventData (accountID,deviceID,geozoneID,statusCode,address,timestamp) VALUES (";
          $insert .= "'gtg','".$devid."','".$geozoneID."',62000,'".$oldaddr."',".time()."),(";
          $insert .= "'gtg','".$devid."','".$newgeozoneID."',61968,'".$geodesc."',".(time()+10).")";
          $res = mysql_query($insert,$link);
          }
        } else {
        // Insert departure for current geozone
	      $insert = "INSERT INTO EventData (accountID,deviceID,geozoneID,statusCode,address,timestamp) VALUES ('gtg','".$devid."','".$geozoneID."',62000,'".$oldaddr."',".time().")";
	      $res = mysql_query($insert,$link);
        }
      }  else {
      // Last event was a departure so insert arrival information
      $res = mysql_query("SELECT geozoneID,displayName FROM Geozone WHERE (minLatitude<=".$lat." AND maxLatitude>=".$lat." AND minLongitude<=".$long." AND maxLongitude>=".$long.") LIMIT 1",$link);
      if(mysql_num_rows($res)>0) {
        list($newgeozoneID,$geodesc) = mysql_fetch_row($res);
        $insert = "INSERT INTO EventData (accountID,deviceID,geozoneID,statusCode,address,timestamp) VALUES ('gtg','".$devid."','".$newgeozoneID."',61968,'".$geodesc."',".time().")";
	    $res = mysql_query($insert,$link);
        }
      }
    }  else {
      // First geozone event so insert arrival information
      $res = mysql_query("SELECT geozoneID,displayName FROM Geozone WHERE (minLatitude<=".$lat." AND maxLatitude>=".$lat." AND minLongitude<=".$long." AND maxLongitude>=".$long.") LIMIT 1",$link);
      if(mysql_num_rows($res)>0) {
        list($newgeozoneID,$geodesc) = mysql_fetch_row($res);
        $insert = "INSERT INTO EventData (accountID,deviceID,geozoneID,statusCode,address,timestamp) VALUES ('gtg','".$devid."','".$newgeozoneID."',61968,'".$geodesc."',".time().")";
	    $res = mysql_query($insert,$link);
        }
    };
    
  if(isset($geodesc)) { return($geodesc); } else { return(""); };
}

// Function to really search an array
function strinarray($searchtext, $dataarray)
{
$i=0;
foreach($dataarray as $linedata) {
  if(strstr($linedata,$searchtext)) break;
  $i++;
  };
return $i;
}

// Connect and retrieve KML data
$ch = curl_init('http://208.83.165.114/KMLs/BDA44F00-6C56-4337-819D-DF4E8D6DE462/p45.kml');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.10(KHTML, like Gecko) Chrome/8.0.552.237 Safari/534.10');
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, 'datacom:#ebbflow!');
curl_setopt($ch, CURLOPT_PORT, 8080);
$result = curl_exec($ch);

// Convert returned XML to array
$xml = simplexml_load_string($result);

//Connect to GTS Database
$link = mysql_connect('localhost','root','d@t@c0m#-db@s3');
if (!$link) {
  die("Cannot connect to GTS db");
};

// Select the GTS Database
if (!mysql_select_db('gts')) {
  die("Cannot select GTS db");
};

// Parse returned XML
$index=0;
foreach($xml->Document->Placemark as $data) {
  $cleancoords=explode(",",$data->Point->coordinates);
  $cleanname=explode(" ",$data->name);
  $cleandesc=explode("\n",$data->description);
  $statuskey=strinarray('Status',$cleandesc);
  $cleanstatus=strip_tags($cleandesc[$statuskey]);
  $statuscode=explode(" ",$cleanstatus);
  $idkey=strinarray('ID',$cleandesc);
  $speedkey=strinarray('Current speed',$cleandesc);
  $notekey1=strinarray('Modem has been', $cleandesc);
  $notekey2=strinarray('Eb',$cleandesc);
  $cleanid=explode(" ",$cleandesc[$idkey]);
  $cleanspeed=explode(" ",$cleandesc[$speedkey]);
  $kvhdata[$index]['name']=$cleanname[2];
  $kvhdata[$index]['latitude']=$cleancoords[1];
  $kvhdata[$index]['longitude']=$cleancoords[0];
  $kvhdata[$index]['speed']=$cleanspeed[3]*1.852;
  $kvhdata[$index]['ipaddr']=$cleanname[0];
  $kvhdata[$index]['id']=strip_tags($cleanid[2]);
  $kvhdata[$index]['status']=$cleanstatus;
  if($statuscode[4]=='In') { 
    $kvhdata[$index]['statuscode']="40000";
  } else $kvhdata[$index]['statuscode']="40002";
  $kvhdata[$index]['notes']=strip_tags($cleandesc[$speedkey])."<br />".strip_tags($cleandesc[$notekey1])."<br />".strip_tags($cleandesc[$notekey2]);
  $kvhdata[$index]['address']=handleGeozone($link,strip_tags($cleanid[2]),$cleancoords[1],$cleancoords[0]);
  $kvhdata[$index]['heading']=handleHeading($link,strip_tags($cleanid[2]),$cleancoords[1],$cleancoords[0]);
  $index++;
};

//Build Device and Location Insert Query
$index=0;
foreach($kvhdata as $data) {
  $insertquery[$index] = "INSERT INTO Device (accountID,deviceID,groupID,equipmentType,vehicleID,uniqueID,displayName,description,ipAddressCurrent,isActive,lastUpdateTime,lastInputState,notes) VALUES ('gtg','".$data['id']."','kvh','netmodem','".$data['name']."','".$data['id']."','".$data['name']."','".$data['name']."','".$data['ipaddr']."',1,".time().",".$data['statuscode'].",'".$data['status']."<br />".$data['notes']."') ON DUPLICATE KEY UPDATE groupID=VALUES(groupID),lastUpdateTime=VALUES(lastUpdateTime),ipAddressCurrent=VALUES(ipAddressCurrent),lastInputState=VALUES(lastInputState),notes=VALUES(notes);";
  $index++;
  $insertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$data['id']."',timestamp=".time().",statusCode=61472,latitude=".$data['latitude'].",longitude=".$data['longitude'].",speedKPH=".$data['speed'].",address='".$data['address']."',heading=".$data['heading'].";";
  $index++;
  $insertquery[$index] = "REPLACE INTO EventData SET accountID='gtg',deviceID='".$data['id']."',timestamp=".time().",statusCode=".$data['statuscode'].",rawData='".$data['status']."';";
  $index++;
};

//Perform Device and location Inserts
foreach($insertquery as $querytext)
  {
  $res = mysql_query($querytext);
  };

//Free GTS database connection
mysql_close($link);
?>
