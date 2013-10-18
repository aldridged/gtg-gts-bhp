#!/usr/bin/php
<?php
/* Report BHP weekly SLA Data */
/* Load required classes*/
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

$res = mysql_query("SELECT Device.deviceID,DeviceGroup.description,Device.displayName,Device.ipAddressCurrent,Device.jobNumber,Device.notes FROM Device,DeviceList,DeviceGroup WHERE Device.deviceID=DeviceList.deviceID AND DeviceList.groupID=DeviceGroup.groupID AND DeviceList.groupID in ('hainesville','blackhawk','eagleford','permian','cib','oib','ipole') AND Device.isActive=1 AND Device.deviceID like 'BBW%' ORDER BY DeviceGroup.description,Device.displayName;");

if (!$res) {
  die("Error cannot select devices");
};

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
			->setCellValue('A2', 'Group')
            ->setCellValue('B2', 'Device Name')
            ->setCellValue('C2', 'Device IP Address')
            ->setCellValue('D2', '30 Day')
			->setCellValue('E1', 'Availability')
            ->setCellValue('E2', '7 Day')
			->setCellValue('F2', '1 Day')
			->setCellValue('G2', '30 Day')
			->setCellValue('H1', 'Latency')
			->setCellValue('H2', '7 Day')
			->setCellValue('I2', '1 Day');
			
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
	
	$excelreport->getActiveSheet()->setCellValue("A".$i,trim($ar['description']))
				->setCellValue("B".$i,trim($ar['displayName']))
				->setCellValue("C".$i,trim($ar['ipAddressCurrent']))
				->setCellValue("D".$i,trim($Avail30d))
				->setCellValue("E".$i,trim($Avail7d))
				->setCellValue("F".$i,trim($Avail1d))
				->setCellValue("G".$i,trim($Lat30d))
				->setCellValue("H".$i,trim($Lat7d))
				->setCellValue("I".$i,trim($Lat1d));
};

/* Set Column Widths */
$excelreport->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
$excelreport->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
$excelreport->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
//$excelreport->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
//$excelreport->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
//$excelreport->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
//$excelreport->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
//$excelreport->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
//$excelreport->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);

/* Write it out to a temp file */
$objWriter = PHPExcel_IOFactory::createWriter($excelreport, 'Excel2007');
$objWriter->save('/tmp/SLA-Report.xlsx');

/* Email it to the BHP group */
$mail = new PHPMailer();
$mail->IsSendmail();
$mail->AddAddress('daldridge@globalgroup.us', 'Aldridge, David');
$mail->SetFrom('reports@dcportal.mydatacom.com', 'DC Portal Reports');
$mail->Subject = 'Weekly SLA Report';
$mail->AltBody = 'Please find the BHP Weekly SLA Report Attached.';
$mail->Body = 'Please find the BHP Weekly SLA Report Attached.';
$mail->AddAttachment('/tmp/SLA-Report.xlsx');
$mail->Send();

/* Clean up our mess */
unlink('/tmp/SLA-Report.xlsx');
?>