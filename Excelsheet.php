<?php
define("EXCEL_DATE_OFFESET" , 25569);
define("SECONDS_PER_DAY" , 86400);
define("PHP_DATE_START" , 5000000);
define("FORM_DATE_FORMAT", "Y-m-d");
  //require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Excelsheet
{
  use Optionsarray;

  public function __construct($file=NULL,$options=array())  {

    if(is_null($file))	{
      // $this->filename="No file loaded just an interface to public functions";
      return;
    }

    // shortcut enter active sheet with default options
    if(!is_array($options))	{
      $options=['sheet' => $options];
    }

    $opts = $this->useroptions(['sheet' => '',
				'dataonly' => true],$options);
    try {
      $this->filename='';
      $this->sheet='';
      /**  Identify the type of $file  **/
      $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file);
      /**  Create a new Reader of the type defined in $inputFileType  **/
      $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
      if($opts['dataonly']==true)	{
	/**  Advise the Reader that we only want to load cell data  **/
	$reader->setReadDataOnly($opts['dataonly']);
      }
      /**  Load $file to a Spreadsheet Object  **/
      $this->wb = $reader->load($file);
      //      $this->wb = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
    } catch(\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
      die('Error loading file: '.$e->getMessage());
    }

    /*
    $sheet = $opts->sheet;
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
    */

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

/*    Title: 	column_convert
 Purpose:	converts between column number and column letter
 Created:	Fri Apr 02 11:15:58 2021
 Author: 	Adrie Dane
*/
function column_convert($c,$options=[])
{
  $opts=['uppercase' => true,
	 'base' => 1];
  if(!empty($options))	{
    foreach($options as $key => $value) {
      $opts[$key]=$value;
    }
  }

  extract($opts);
  
  $A=ord('A');

  if(is_numeric($c))	{
    $c = intval($c)-$base+1;
    
    if ($c <= 0) return '';

    $letter = '';
    while($c != 0){
      $p = ($c - 1) % 26;
      $c = intval(($c - $p) / 26);
      $letter = chr($A + $p) . $letter;
    }
    return $uppercase==true ? $letter : strtolower($letter);
  } else {
    // subtract 1
    $A -= 1;
    $pow=0;
    $num=0;
    $chars=array_reverse(str_split(strtoupper($c)));
    foreach($chars as $char) {
      $num += (ord($char)-$A)*pow(26,$pow);
      $pow++;
    }
    return $num+$base-1;
  }
  

} /* column_convert */

/*    Title: 	set_data
      Purpose:	
      Created:	Thu Apr 08 12:16:02 2021
      Author: 	Adrie Dane
*/
 function set_data($top_left,$data)
 {
   if(is_array($data))	{
     if(is_array($top_left))	{
       $col=$this->column_convert($top_left[1],['base' => 0]);
       $top_left[0]++;
       $top_left=$col.$top_left[0];
     }
     $this->sheet->fromArray($data,NULL,$top_left);
   } else {
     if(is_array($top_left))	{
       $this->sheet->setCellValueByColumnAndRow($top_left[1]+1,$top_left[0]+1, $data);
     } else {
       $this->sheet->setCellValue($top_left,$data);
     }
   }
 } /* set_data */



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

/*    Title: 	update
      Purpose:	
      Created:	Tue Apr 06 14:35:16 2021
      Author: 	Adrie Dane
*/
function download()
{
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment;filename="myfile.xlsx"');
  header('Cache-Control: max-age=0');
//  header('Content-Disposition: attachment; filename="'. urlencode($this->filename).'"');
  $writer = new Xlsx($this->wb);
  $writer->save('php://output');
} /* update */


}
?>