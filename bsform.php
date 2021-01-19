<?php
trait bsform
{
  /*    Title: 	bsfinput
      Purpose:	bootstrap form for editting
      Created:	Mon Jan 18 15:46:49 2021
      Author: 	Adrie Dane
*/
  function bsfinput($types=array())
  {
    $arr=array();
    foreach($this->data as $x) {
      // do label columns first
      foreach($types as $field => $type) {
	if($type=='label')	{
	  $y[$field]="<b>".$x[$field]."</b>";
	}
      }
      foreach($types as $field => $type) {
	if($type!='label')	{
	  $value=$x[$field];
	  $y[$field]="<input type='$type' name='$field[]' value='$value'></input>";
	}
      }
    }

  } /* bsfinput */



?>