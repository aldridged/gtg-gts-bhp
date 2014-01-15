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

//If cannot find the gts database
if (!mysql_select_db('gts')) {
  die("Cannot select GTS db");
};

//Main query
$res = mysql_query("SELECT Device.deviceID,DeviceGroup.description,Device.displayName,Device.ipAddressCurrent,Device.jobNumber,Device.notes FROM Device,DeviceList,DeviceGroup WHERE Device.deviceID=DeviceList.deviceID AND DeviceList.groupID=DeviceGroup.groupID AND DeviceList.groupID in ('haynesville','blackhawk','eagleford','hawkville','permian') AND Device.isActive=1 AND Device.deviceID like 'BBW%' ORDER BY DeviceGroup.description,Device.displayName;");

//If the query gathers no data
if (!$res) {
  //quit
  die("Error cannot select devices");
};

/* Connect to CRM Portal */
$result = RestCall('login',array('user_auth' => array('user_name' => 'Admin', 'password' => md5('d@t@c0m!'))));
$session = $result['id'];

/* Create Excel sheet in memory */
$excelreport = new PHPExcel();

//Set properties for excel sheet
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
            //->setCellValue('G2', '30 Day')
			->setCellValue('G1', 'Availability (%)')
            ->setCellValue('G2', '7 Day')
			//->setCellValue('I2', '1 Day')
			//->setCellValue('J2', '30 Day')
			->setCellValue('H1', 'Latency (ms)')
			->setCellValue('H2', '7 Day')
			//->setCellValue('L2', '1 Day')
			//->setCellValue('M2', '30 Day')
			->setCellValue('I1', 'Packet Loss (%)')
			->setCellValue('I2', '7 Day');
			//->setCellValue('O2', '1 Day');
			
//set title of the current sheet to "Datacom SLA Data"
$excelreport->getActiveSheet()->setTitle('Datacom SLA Data');
			
/* Loop through devices filling info to sheet */
/* Main Loop */

//index of cell
$i = 2;
//fetch both numerical index and associative index
while ($ar = mysql_fetch_array($res, MYSQL_BOTH)) {
	//increment cell index
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
	
	//determine type of device
	$type = "ipole";
	if(strpos($ar['deviceID'],"oib")!==FALSE) {
		$type = "oib";
	} else if(strpos($ar['deviceID'],"cib")!==FALSE) {
		$type = "cib";
	} else if(strpos($ar['deviceID'],"ccib")!==FALSE) {
		$type = "ccib";
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
	/* End CRM */
 
 /* Fill Data into Cells */
	$excelreport->getActiveSheet()->setCellValue("A".$i,trim($ar['description']))
				->setCellValue("B".$i,trim($ar['displayName']))
				->setCellValue("C".$i,trim($ar['ipAddressCurrent']))
				->setCellValue("D".$i,$type)
				->setCellValue("E".$i,trim($ar['jobNumber']))
				->setCellValue("F".$i,$numcases)
				//->setCellValue("G".$i,trim($Avail30d))
				->setCellValue("G".$i,trim($Avail7d))
				//->setCellValue("I".$i,trim($Avail1d))
				//->setCellValue("J".$i,trim($Lat30d))
				->setCellValue("H".$i,trim($Lat7d))
				//->setCellValue("L".$i,trim($Lat1d))
				//->setCellValue("M".$i,trim($Pl30d))
				->setCellValue("I".$i,trim($Pl7d));
				//->setCellValue("O".$i,trim($Pl1d));
				
	//RGB(214, 212, 202), ("Tan, Background 2, Darker 10%"), hawkville 
	if(strpos($ar['description'],"Hawk")!==FALSE) {
		$excelreport->getActiveSheet()->getStyle('G'.$i.':'.'I'.$i)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFD6D4CA');
	};
	
	//RGB(229, 223, 235), ("Purple, Accent 4, Lighter 80%"), permian
	if(strpos($ar['description'],"Perm")!==FALSE) {
	$excelreport->getActiveSheet()->getStyle('G'.$i.':'.'I'.$i)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFE5DFEB');
	};
	
	//RGB(234, 241, 221), ("Olive Green, Accent 3, Lighter 80%"), haynesville
	if(strpos($ar['description'],"Haines")!==FALSE) {
	$excelreport->getActiveSheet()->getStyle('G'.$i.':'.'I'.$i)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFEAF1DD');
	};
}; 
/* End of main loop */

/* Set Column Widths */
$excelreport->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
$excelreport->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
$excelreport->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
$excelreport->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
$excelreport->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
$excelreport->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
$excelreport->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
$excelreport->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
$excelreport->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);

/* Set Formatting on Sheet */
//Set Bold Text for Column Labels
$excelreport->getActiveSheet()->getStyle('A1:I2')->getFont()->setBold(true);
//Set Thick Line on bottom of row 2
$excelreport->getActiveSheet()->getStyle('A2:I2')->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THICK);




/* Write it out to a temp file */
$objWriter = PHPExcel_IOFactory::createWriter($excelreport, 'Excel2007');
$objWriter->save('/tmp/SLA-Report.xlsx');

/* Email it to Jacob */
$mail = new PHPMailer();
$mail->IsSendmail();
$mail->AddAddress('jacobm@globalgroup.us', 'Murphy, Jacob');
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
