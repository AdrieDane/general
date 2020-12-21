<?php

trait isetget 
{

  /*    Title: 	iindex
   Purpose:	
   Created:	Mon Dec 21 11:24:49 2020
   Author: 	Adrie Dane
  */
  function iindex($fields = NULL)
  {
    if(is_null($fields) && isset($this->ixfields))	{
      $fields=$this->ixfields;
      unset($this->ix);
    }
    $this->ix=[];
    $this->ixfields=$fields;
    foreach($fields as $field) {
      foreach($this->data as $key => $x) {
	if(!isset($this->ix[$x[$field]]))	{
	  $this->ix[$x[$field]]=array();
	}
	$this->ix[$x[$field]][]=$key;
      }
    }
  } /* iindex */

  /*    Title: 	iunset
   Purpose:	
   Created:	Mon Dec 21 12:05:37 2020
   Author: 	Adrie Dane
  */
  function iunset($ifields = array())
  {
    foreach($ifields as $field) {
      if(!isset($this->ix[$field]))	{
	continue;
      }
      $index = is_array($field) ? $field : $this->ix[$field];
      foreach($index as $idx) {
	unset($this->data[$idx]);
      }
      if(is_string($field))	{
	unset($this->ix[$field]);
      }
    }
    $this->iindex($this->ixfields);
  } /* iunset */

  /*    Title: 	iset
   Purpose:	
   Created:	Mon Dec 21 12:13:24 2020
   Author: 	Adrie Dane
  */
  function iset($ifields = array(),$kv=array())
  {
    $arr=[];
    foreach($ifields as $field) {
      $arr[]= is_array($field) ? $field : $this->ix[$field];
    }
    //pre_r($arr,'arr');
    
    $idx = count($arr)==1 ? $arr[0] : array_intersect(...$arr);
    //pre_r($idx,'idx');
    foreach($idx as $i) {
      foreach($kv as $key => $value) {
	$this->data[$i][$key]=$value;
      }
    }
  } /* iset */

  /*    Title: 	iget
   Purpose:	
   Created:	Mon Dec 21 14:03:25 2020
   Author: 	Adrie Dane
  */
  function iget($ifields = array(),$kv=array())
  {
    $arr=[];
    foreach($ifields as $field) {
      $arr[]= is_array($field) ? $field : $this->ix[$field];
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
	$result->iindex();
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
    return $result;
  } /* iget */






}
?>