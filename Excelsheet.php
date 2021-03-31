<?php
  //require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
// use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Excelsheet
{
  public function __construct($file,$options=array())  {

    $this->wb = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
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


    $this->set_sheet($opts['sheet']);
    
  }

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

/*    Title: 	data
      Purpose:	At this moment returns data from complete sheet
      Created:	Wed Mar 31 08:56:08 2021
      Author: 	Adrie Dane
*/
function data($options=[])
{
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


}
?>