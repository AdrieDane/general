<?php
define("EXCEL_DATE_OFFESET" , 25569);
define("SECONDS_PER_DAY" , 86400);
define("PHP_DATE_START" , 5000000);
define("FORM_DATE_FORMAT", "Y-m-d");
  //require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
// use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Excelsheet
{
  public function __construct($file=NULL,$options=array())  {

    if(is_null($file))	{
      $this->filename="No file loaded just an interface to public functions";
      return;
    }

    try {

      /**  Identify the type of $file  **/
      $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file);
      /**  Create a new Reader of the type defined in $inputFileType  **/
      $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
      /**  Advise the Reader that we only want to load cell data  **/
      $reader->setReadDataOnly(true);
      /**  Load $file to a Spreadsheet Object  **/
      $this->wb = $reader->load($file);
      //      $this->wb = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
    } catch(\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
      die('Error loading file: '.$e->getMessage());
    }

    if(!is_array($options))	{
      $sheet=$options;
      $options=['sheet' => $sheet];
    }
    $opts=['sheet' => ''];

    // get user options
    if(!empty($options))	{
      $keys=array_intersect(array_keys($opts),array_keys($options));
      foreach($keys as $key) {
	$opts[$key]=$options[$key];
      }
    }

    $this->filename=pathinfo($file,PATHINFO_BASENAME);
    $this->set_sheet($opts['sheet']);
    
  }

/*    Title: 	date_convert
      Purpose:	Converts Excel date to PHP date and visa versa
      Created:	Fri Apr 02 09:09:17 2021
      Author: 	Adrie Dane
*/
  function date_convert($value)
{

  if($value>PHP_DATE_START)	{
    // convert to Excel
    return ($value/SECONDS_PER_DAY)+EXCEL_DATE_OFFESET;
      } else {
    // convert to PHP
    return ($value-EXCEL_DATE_OFFESET)*SECONDS_PER_DAY;
  }
} /* date_convert */

/*    Title: 	form_dateconvert
      Purpose:	Converts Excel date to PHP date and visa versa
      Created:	Fri Apr 02 09:09:17 2021
      Author: 	Adrie Dane
*/
function form_dateconvert($value)
{
  if(is_string($value))	{
    $t=date_create_from_format(FORM_DATE_FORMAT,$value);
    return $this->date_convert($t->getTimestamp());
  } else {
    return date(FORM_DATE_FORMAT, $this->date_convert($value));
  }
} /* form_dateconvert */





/*    Title: 	sheets
      Purpose:	
      Created:	Wed Mar 31 08:33:12 2021
      Author: 	Adrie Dane
*/
function sheets()
{
  return $this->wb -> getSheetNames();
} /* sheets */

/*    Title: 	set_sheet
      Purpose:	
      Created:	Wed Mar 31 08:35:25 2021
      Author: 	Adrie Dane
*/
function set_sheet($sheet='')
{
  if(empty($sheet))	{
    $sheet=0;
  }
  if(is_numeric($sheet))	{
    $this->wb->setActiveSheetIndex(intval($sheet));
  } else {
    $sheets = $this->sheets();
    if(in_array($sheet,$sheets))	{
      $this->wb->setActiveSheetIndexByName($sheet);
    } else {
      $str = "ERROR in Excelsheet:<br>No sheet named: $sheet in this workbook<br".
        pre_r($sheet,'Valid Names');
      exit($str);
    }
  }
 
  $this->sheet = $this->wb->getActiveSheet();

  return $this->name();
} /* set_sheet */

/*    Title: 	name
      Purpose:	
      Created:	Wed Mar 31 08:52:53 2021
      Author: 	Adrie Dane
*/
function name()
{
  return $this->sheet->getTitle();
} /* name */

/*    Title: 	getdate
      Purpose:	
      Created:	Thu Apr 01 14:58:17 2021
      Author: 	Adrie Dane
*/
function getdate($cell)
{
  $value=$this->sheet->getCell($cell)->getValue();
    $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($value);

    return $date;
    
} /* getdate */


/*    Title: 	data
      Purpose:	At this moment returns data from complete sheet
      Created:	Wed Mar 31 08:56:08 2021
      Author: 	Adrie Dane
*/
function data($options=[])
{
  /*if(isset($options['cell']))	{
    $value=$this->sheet->getValue();
    $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($value);
    return $date;
    } */

  $data = $this->sheet->toArray();

  $opts=['remove_empty'=>true]; // strip empty rows and columns from the end
  
  // get user options
  if(!empty($options))	{
    $keys=array_intersect(array_keys($opts),array_keys($options));
    foreach($keys as $key) {
      $opts[$key]=$options[$key];
    }
  }

  // remove empty rows and columns from the end
  $row=count($data)-1;
  while(!array_filter($data[$row]))	{
    unset($data[$row]);
    $row--;
  }
  $col=count($data[0])-1;
  while(!array_filter(array_column($data,$col)))	{
    foreach($data as &$x) {
      unset($x[$col]);
    }
    $col--;
  }

  return $data;
} /* data */

/*    Title: 	all_data
      Purpose:	returns data from all sheets in an associative array in which the keys are the sheetnames
      Created:	Thu Apr 01 09:28:55 2021
      Author: 	Adrie Dane
*/
function all_data()
{
  $sheets=$this->sheets();
  $data=array();
  foreach($sheets as $sheet) {
    $this->set_sheet($sheet);
    $data[$sheet]=$this->data();
  }
  return $data;
} /* all_data */


}
?>