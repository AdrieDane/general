<?php

trait isetget 
{

/*    Title: 	iinit
      Purpose:	Inits isetget by setting 
                - $this->ixfields = $fields
      Created:	Wed Feb 24 08:26:13 2021
      Author: 	Adrie Dane
*/
function iinit($fields=array())
{
  if(!is_array($fields))	{
    $fields=array($fields);
  }
  if(empty($fields))	{
    exit('Empty $fields in iinit() isetget requires existing fields');
  }
  $this->ixfields=$fields;
} /* iinit */

/*    Title: 	iexit
      Purpose:	unsets $this->ix
      Created:	Wed Feb 24 08:35:57 2021
      Author: 	Adrie Dane
function iexit()
{
  $this->ix=array();
  unset($this->ix);
} iexit */



  /*    Title: 	iindex
   Purpose:	Sets member 'ix' which makes it possible to set or unset 
                properties in multiple members at once
   Created:	Mon Dec 21 11:24:49 2020
   Author: 	Adrie Dane
  */
  function iindex()
  {
    if(!isset($this->ixfields))	{
      exit("Absent 'ixfields' member use iinit to set 'ixfields' required for isetget functionality");
    }
    $ix=[];
    foreach($this->ixfields as $field) {
      foreach($this->data as $key => $x) {
	if(!isset($ix[$x[$field]]))	{
	  $ix[$x[$field]]=array();
	}
	$ix[$x[$field]][]=$key;
      }
    }
    return $ix;
  } /* iindex */

  /*    Title: 	iunset
   Purpose:	unsets data identified by ix
   Created:	Mon Dec 21 12:05:37 2020
   Author: 	Adrie Dane
  */
  function iunset($ifields = array())
  {
    $ix = $this->iindex();
    foreach($ifields as $field) {
      if(!isset($ix[$field]))	{
	continue;
      }
      $index = is_array($field) ? $field : $ix[$field];
      foreach($index as $idx) {
	unset($this->data[$idx]);
      }
      if(is_string($field))	{
	unset($ix[$field]);
      }
    }
    unset($ix);
  } /* iunset */

  /*    Title: 	iset
   Purpose:	
   Created:	Mon Dec 21 12:13:24 2020
   Author: 	Adrie Dane
  */
  function iset($ifields = array(),$kv=array())
  {
    $ix = $this->iindex();
    $arr=[];
    foreach($ifields as $field) {
      $arr[]= is_array($field) ? $field : $ix[$field];
    }
    //pre_r($arr,'arr');
    
    $idx = count($arr)==1 ? $arr[0] : array_intersect(...$arr);
    //pre_r($idx,'idx');
    foreach($idx as $i) {
      foreach($kv as $key => $value) {
	$this->data[$i][$key]=$value;
      }
    }
    unset($ix);
  } /* iset */

  /*    Title: 	iget
   Purpose:	
   Created:	Mon Dec 21 14:03:25 2020
   Author: 	Adrie Dane
  */
  function iget($ifields = array(),$kv=array())
  {
    $ix = $this->iindex();
    $arr=[];
    foreach($ifields as $field) {
      $arr[]= is_array($field) ? $field : $ix[$field];
    }
    $idx = count($arr)==1 ? $arr[0] : array_intersect(...$arr);
    $result=[];

    if(is_string($kv))	{
      if($kv=='filter')	{
	$arr=[];
	$result=clone $this;
	$result->data=array();
	foreach($idx as $i) {
	  //pre_r($i,'xx');
	  $result->data[$i]=$this->data[$i];
	}

	//	pre_r($idx,'idx_filter');
	//	$result->data=$arr;
	//	$result->iindex();
      } else {
	foreach($idx as $i) {
	  $result[$i]=$this->data[$i][$kv];
	}
      }
    } elseif(is_array($kv) && count($kv)==1) {
      $key=key($kv);
      foreach($idx as $i) {
	$result[$this->data[$i][$key]]=$this->data[$i][$kv[$key]];
      }
    }
    unset($ix);
    return $result;
  } /* iget */






}
?>