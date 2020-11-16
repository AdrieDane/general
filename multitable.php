<?php

class multitable extends datatable
{
  public function __construct(&$arr,$con=NULL) 
  {
    $this->tables=$arr;
    $this->data=array();
    foreach($this->tables[0] as $key => &$value) {
      $this->$key=array();
    }
    $i=0;
    foreach($this->tables as &$lst) {
      foreach($lst->data as &$x) {
	$x['table']=$i;
      }
      foreach($lst as $key => &$value) {
	if($key=="data")	{
	  if(empty($this->data))	{
	    $this->data=$value;
	  } else {
	    foreach($value as &$x) {
	      $this->data[]=$x;
	    }
	  }
	} else {
	  $this->$key[]=$value;
	}
      }
      $i++;
    }
    // having this makes is easier to create forms
    $this->max_label_length=
      max(array_map('strlen',array_column($this->data,'lcms_name')));
  }
}


?>