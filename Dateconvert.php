<?php
  // Excel date numbers represent the number of days that have passed since January 1, 1900
  // MATLAB date numbers represent the number of days that have passed since January 1, 0000
define("EXCEL_DATE_OFFESET" , 25569);
define("SECONDS_PER_DAY" , 86400);
define("PHP_DATE_START" , 5000000);
define("FORM_DATE_FORMAT", "Y-m-d");

define("MATLAB_DATE_START" , 200000);
define("EXCEL_TO_MATLAB" , 693960);

class Dateconvert
{
  public function __construct($indate,$informat='auto') 
  {
    $this->indate = $indate;

    if($informat=='auto')	{
      if(is_numeric($indate)) {
	if($indate>PHP_DATE_START)	{
	  $informat = 'PHP';
	} elseif($indate>MATLAB_DATE_START)	{
	  $informat = 'matlab';
	} else {
	  $informat = 'excel';
	}
      } else { // not recomended
	$informat = FORM_DATE_FORMAT;
      }
    }

    if(is_string($indate))	{
      $t=date_create_from_format($informat,$indate);
      $this->PHP = $t->getTimestamp();
    } else {
      $this->$informat=$indate;
    }
    
    // Either $this->PHP or $this->excel is now set
    if(isset($this->PHP))	{
      $this->excel=($this->PHP/SECONDS_PER_DAY)+EXCEL_DATE_OFFESET;
      $this->matlab=$this->excel+EXCEL_TO_MATLAB;
    } elseif(isset($this->excel))	{
      $this->matlab=$this->excel+EXCEL_TO_MATLAB;
      $this->PHP=($this->excel-EXCEL_DATE_OFFESET)*SECONDS_PER_DAY;
    } elseif(isset($this->matlab))	{
      $this->excel=$this->matlab-EXCEL_TO_MATLAB;
      $this->PHP=($this->excel-EXCEL_DATE_OFFESET)*SECONDS_PER_DAY;
    }
    
  }

/*    Title: 	as_string
      Purpose:	convert timestamp to formatted string
      Created:	Sat Apr 10 11:22:45 2021
      Author: 	Adrie Dane
*/
function as_string($fmt=FORM_DATE_FORMAT)
{
  return date($fmt,$this->PHP);
} /* as_string */

  
}

?>