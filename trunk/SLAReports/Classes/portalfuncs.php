<?php
// Common functions for portal

// Function to output contents as downloadable
function DownloadAttachment($file_name,$content) {
  header("Content-Type: application/octet-stream");
  header('Content-Disposition: attachment; filename="'.$file_name.'"');
  header("Pragma: public");
  header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
  print($content);
  flush();
}

// Function to send Rest calls to sugar
function RestCall($method,$parameters) {
  $url = 'http://dcmaster.mydatacom.com/service/v2/rest.php';
  $curl = curl_init($url);
  curl_setopt($curl,CURLOPT_POST,true);
  curl_setopt($curl,CURLOPT_HEADER,false);
  curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
  $json = json_encode($parameters);
  $postArgs = 'method='.$method.'&input_type=JSON&response_type=JSON&rest_data='.$json;
  curl_setopt($curl,CURLOPT_POSTFIELDS,$postArgs);
  $response = curl_exec($curl);
  curl_close($curl);
  $result = json_decode($response,true);
  return($result);
}

?>
