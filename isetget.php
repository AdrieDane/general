<?php

trait isetget 
{
  /* isetget 
     iinit() must be called
     - Sets member 'ixfields' which makes it possible to set/get or unset
     properties in multiple members at once these ixfields are 
     aggregation variables
     iget($ifields = array(),$fv=array()) 
     -get data based on grouping

     Always preserves keys of original ->data

     $ifields is an array of aggregation group members
     eg. ['QC', 'NPpos'] works on all QC samples measured in NPpos
     $ifields is an array of indices
     eg. ['LIPIDS_20210417_005', 'LIPIDS_20210417_014']
     works on 2 samples identified by indices: 
     'LIPIDS_20210417_005', 'LIPIDS_20210417_014'

     $fv is 'filter' creates a filtered clone
     $fv is not 'filter' returns a associative array  => value
     $fv of type v => value renames the values (v =>value)


  */
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
      //            pre_r($field,'$field');
      foreach($this->data as $key => $x) {
        //               pre_r($x,'$x');
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

  /*    Title: 	igetkeys
        Purpose:	
        Created:	Tue May 25 16:53:13 2021
        Author: 	
  */
  function igetkeys($ifields = array())
  {
    // create associative array
    // $ix[field] => $idx 
    // in which $idx is an array of indices matching field
    $ix = $this->iindex();
    //    pre_r($ix,'$ix');

    // reformat fields uniformly into array $arr [] if field not in $ix
    $arr=[];
    foreach($ifields as $field) {
      if(is_array($field))	{ // field was already an array of indices
        $arr[] = $field;
      } else {
        $arr[]= in_array($field,array_keys($ix)) ? $ix[$field] : [];
      }
    }
    $idx = count($arr)==1 ? reset($arr) : array_intersect(...$arr);

    return $idx;
  } /* igetkeys */





  /*    Title: 	iget
        Purpose:	
        Created:	Mon Dec 21 14:03:25 2020
        Author: 	Adrie Dane
  */
  function iget($ifields = array(),$fv=array())
  {
    $idx = $this->igetkeys($ifields);
    
    // At this point $idx is an array of indices into datatable (or class derived thereof)

    $result=[];

    if(is_string($fv))	{
      if($fv=='filter')	{
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
          $result[$i]=$this->data[$i][$fv];
        }
      }
    } elseif(is_array($fv) && count($fv)==1) {
      $field=key($fv);
      $val=reset($fv);
      foreach($idx as $i) {
        $result[$this->data[$i][$field]]=$this->data[$i][$val];
      }
    }
    return $result;
  } /* iget */



  
  /*    Title: 	iset
        Purpose:	
        Created:	Mon Dec 21 12:13:24 2020
        Author: 	Adrie Dane
  */
  function iset($ifields = array(),$fv=array())
  {
    $idx = $this->igetkeys($ifields);

    //    echo "iset";
    //    pre_r($idx);
    //    pre_r($fv);

    
    foreach($idx as $i) {
      foreach($fv as $field => $value) {
        $this->data[$i][$field]=$value;
      }
    }
  } /* iset */

  /*    Title: 	iupdate
        Purpose:	updating using an iget() result (after that result has changed)
        Created:	Tue May 25 17:08:03 2021
        Author: 	
  */
  function iupdate($iget,$field)
  {
    foreach($iget as $key => $value) {
      $this->data[$key][$field]=$value;
    }
  } /* iupdate */



}
?>
