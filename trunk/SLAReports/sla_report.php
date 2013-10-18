#!/usr/bin/php
<?php
/* Report BHP weekly SLA Data */
/* Load required classes*/
require_once('Classes/portalfuncs.php');
require_once('Classes/class.phpmailer.php');
require_once('Classes/PHPExcel.php');

/* Connect to GTS database */
$link = mysql_connect('localhost','root','d@t@c0m#');
if (!$link) {
  die("Cannot connect to GTS db");
};

if (!mysql_select_db('gts')) {
  die("Cannot select GTS db");
};

$res = mysql_query("SELECT Device.deviceID,DeviceGroup.description,Device.displayName,Device.ipAddressCurrent,Device.jobNumber,Device.notes FROM Device,DeviceList,DeviceGroup WHERE Device.deviceID=DeviceList.deviceID AND DeviceList.groupID=DeviceGroup.groupID AND DeviceList.groupID in ('hainesville','blackhawk','eagleford','hawkville','permian') AND Device.isActive=1 AND Device.deviceID like 'BBW%' ORDER BY DeviceGroup.description,Device.displayName;");

if (!$res) {
  die("Error cannot select devices");
};

/* Connect to CRM Portal */
$result = RestCall('login',array('user_auth' => array('user_name' => 'Admin', 'password' => md5('D@t@c0m#'))));
$session = $result['id'];

/* Create Excel sheet in memory */
$excelreport = new PHPExcel();

$excelreport->getProperties()->setCreator("Datacom LLC")
							 ->setLastModifiedBy("Reporting Service")
							 ->setTitle("Weekly SLA Report")
							 ->setSubject("Weekly SLA Report")
							 ->setDescription("Weekly SLA Report")
							 ->setKeywords("SLA,Report")
							 ->setCategory("Report");

$excelreport->setActiveSheetIndex(0)
			->setCellValue('A2', 'PU')
            ->setCellValue('B2', 'Device Name')
            ->setCellValue('C2', 'Device IP Address')
			->setCellValue('D2', 'Type')
			->setCellValue('E2', 'Job Number')
			->setCellValue('F2', 'Open Cases')
            ->setCellValue('G2', '30 Day')
			->setCellValue('H1', 'Availability (%)')
            ->setCellValue('H2', '7 Day')
			->setCellValue('I2', '1 Day')
			->setCellValue('J2', '30 Day')
			->setCellValue('K1', 'Latency (ms)')
			->setCellValue('K2', '7 Day')
			->setCellValue('L2', '1 Day')
			->setCellValue('M2', '30 Day')
			->setCellValue('N1', 'Packet Loss (%)')
			->setCellValue('N2', '7 Day')
			->setCellValue('O2', '1 Day');
			
$excelreport->getActiveSheet()->setTitle('Datacom SLA Data');
			
/* Loop through devices filling info to sheet */
$i = 2;
while ($ar = mysql_fetch_array($res, MYSQL_BOTH)) {
	$i++;
	
	$splitnotes = explode(":",$ar['notes']);
	list($Avail1d,$trash) = explode("%",$splitnotes[1]);
	list($Avail7d,$trash) = explode("%",$splitnotes[2]);
	list($Avail30d,$trash) = explode("%",$splitnotes[3]);
	list($Lat1d,$trash) = explode("m",$splitnotes[5]);
	list($Lat7d,$trash) = explode("m",$splitnotes[6]);
	list($Lat30d,$trash) = explode("m",$splitnotes[7]);
	$Pl1d = 100-$Avail1d;
	$Pl7d = 100-$Avail7d;
	$Pl30d = 100-$Avail30d;
	$type = "ipole";
	if(strpos($ar['deviceID'],"oib")!==FALSE) {
		$type = "oib";
	} else if(strpos($ar['deviceID'],"cib")!==FALSE) {
		$type = "cib";
	};
	
	/* Find CRM Project */
	$result = RestCall('get_entry_list',array('session' => $session, 'module_name' => 'Project', 'query' => 'name="'.$ar['jobNumber'].'"', 'order_by' => '', 'offset' => 0, 'select_fields' => array('id'), 'link_name_to_fields_array' => array(), 'max_results' => 1, 'deleted' => 0));
	$jobid = $result['entry_list'][0]['id'];
 	/* Count open cases */
	$result = RestCall('get_relationships',array('session' => $session, 'module_name' => 'Project', 'module_id' => $jobid, 'link_field_name' => 'cases', 'related_module_query' => '', 'related_fields' => array('id','name','case_number','status','date_entered','resolveddate_c'), 'related_module_link_name_to_fields_array' => array(), 'deleted' => 0));
	$numcases = 0;
	foreach($result['entry_list'] as $entry) {
		if(($entry['name_value_list']['status']['value']!="Closed")&&($entry['name_value_list']['status']['value']!="Resolved")) {
			$numcases++;
		};
	};
 
	$excelreport->getActiveSheet()->setCellValue("A".$i,trim($ar['description']))
				->setCellValue("B".$i,trim($ar['displayName']))
				->setCellValue("C".$i,trim($ar['ipAddressCurrent']))
				->setCellValue("D".$i,$type)
				->setCellValue("E".$i,trim($ar['jobNumber']))
				->setCellValue("F".$i,$numcases)
				->setCellValue("G".$i,trim($Avail30d))
				->setCellValue("H".$i,trim($Avail7d))
				->setCellValue("I".$i,trim($Avail1d))
				->setCellValue("J".$i,trim($Lat30d))
				->setCellValue("K".$i,trim($Lat7d))
				->setCellValue("L".$i,trim($Lat1d))
				->setCellValue("M".$i,trim($Pl30d))
				->setCellValue("N".$i,trim($Pl7d))
				->setCellValue("O".$i,trim($Pl1d));
};

/* Set Column Widths */
$excelreport->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
$excelreport->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
$excelreport->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
$excelreport->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
$excelreport->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
$excelreport->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
//$excelreport->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
//$excelreport->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
//$excelreport->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);

/* Set Formatting on Sheet */
$excelreport->getActiveSheet()->getStyle('A1:O2')->getFont()->setBold(true);
$excelreport->getActiveSheet()->getStyle('A2:O2')->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THICK);
$excelreport->getActiveSheet()->getStyle('G1:I'.$i)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCCCCC');
$excelreport->getActiveSheet()->getStyle('M1:O'.$i)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCCCCC');

/* Write it out to a temp file */
$objWriter = PHPExcel_IOFactory::createWriter($excelreport, 'Excel2007');
$objWriter->save('/tmp/SLA-Report.xlsx');

/* Email it to the BHP group */
$mail = new PHPMailer();
$mail->IsSendmail();
$mail->AddAddress('bhpteam@globalgroup.us', 'BHP Team');
$mail->SetFrom('reports@dcportal.mydatacom.com', 'DC Portal Reports');
$mail->Subject = 'Weekly SLA Report';
$mail->AltBody = 'Please find the BHP Weekly SLA Report Attached.';
$mail->Body = 'Please find the BHP Weekly SLA Report Attached.';
$mail->AddAttachment('/tmp/SLA-Report.xlsx');
$mail->Send();

/* Clean up our mess */
unlink('/tmp/SLA-Report.xlsx');
$result = RestCall('logout',array('session' => $session));
?>