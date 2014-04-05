<?php
include '../config.php';

define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

/** Include PHPExcel */
require_once dirname(__FILE__) . '/Classes/PHPExcel.php';

// Read the request from the URL
if( isset($_GET['form']) ){
	$form = "form_".trim($_GET['form']);
} else {
	header("HTTP/1.0 411 Length Required");
	echo "<h1>411 Length Required</h1>Cannot Process Request";
	die();
}

$toFind = hasAccess();

// If the user is not logged in, kill the download script
if( $toFind->access_level == -1 ){
	header("HTTP/1.0 403 Forbidden");
	echo "<h1>403 Forbidden</h1>Request does not contain the proper credentials.";
	die();
}

// Query the database for the form information
$formInfo = site_queryCIE("SELECT * FROM masterform WHERE form_id=?",[$form]);
$formInfo = $formInfo[0];

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

// Set document properties
$objPHPExcel->getProperties()->setCreator("Automated CIE")
							 ->setTitle($formInfo->form_name." - Export")
							 ->setDescription($formInfo->form_description)
							 ->setCategory("CIE Data Export");

$formHeaders = site_queryCIE("SELECT * FROM ".$form."_meta ","query");
$formTitles;

// Create the header data
foreach ($formHeaders as $key => $value) {
	// For each form header, add an element to the top of the row
	$cell = chr(65+$key)."1";
	$cellValue = ucwords(str_replace("_", " ", $value->element_name));
	$objPHPExcel->setActiveSheetIndex(0)->setCellValue($cell, $cellValue);

	// Also, make this column auto width
	$objPHPExcel->getActiveSheet()->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($key))->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getStyle($cell)->getFont()->setBold(true);

	$formTitles[] = $value->element_name;
}


// Create a new query for our data
$formData = site_queryCIE("SELECT * FROM ".$form,"query");
// Clear out the cell 
$cell = "";

// Drop in all the data!
foreach ($formData as $key => $entry) {
	for ($i=0; $i < sizeof($formTitles); $i++) { 
		// Create cell address
		$cell = chr(65+$i).strval($key+2);
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue($cell, $entry->$formTitles[$i]);
	}
}

// Redirect output to a client’s web browser (Excel2007)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$formInfo->form_name.'.xlsx"');
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header ('Pragma: public'); // HTTP/1.0

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
?>