<?php

class Blob
{
/*    Title: 	__construct
      Purpose:	
      Created:	Sat Mar 06 11:40:58 2021
      Author: 	Adrie Dane
*/
  public function __construct($input,$opts=[])
{
  $file_keys=['name','type','tmp_name'];
  $nkeys=count($file_keys);
  
  if(is_array($input) && 
     count(array_intersect($file_keys,array_keys($input)))==$nkeys) {
    $this->Id       = 0;
    $this->name     = $input['name'];
    $this->type     = $input['type'];
    $this->size     = $input['size'];
    $this->object = !empty($opts) && in_array('object',array_keys($opts)) ?
      $opts['object'] :
      '';
    $this->contents = file_get_contents($input['tmp_name']);

  } elseif(is_integer($input)) {
    $con = !empty($opts) && in_array('con',array_keys($opts)) ?
      $opts['con'] :
      NULL;
    if(is_null($con))	{
      exit("Trying to init Blob from SQL database. Database connector not set in options $blob = new Blob($Id,['con' => value])");
    }
    $table = !empty($opts) && in_array('table',array_keys($opts)) ?
      $opts['table'] :
      'file';
    $query = "SELECT `fileId`,`name`,`type`,`contents`,`size`,`object` FROM $table WHERE `fileId`=$input";
    $result = $con->query($query);
    $this->Id = $result['fileId'];
    $fields = ['name','type','contents','size','object'];
    foreach($fields as $field) {
      $this->$field = $result[$field];
    }
  }
  

} /* __construct */

/*    Title: 	to_db
      Purpose:	
      Created:	Sun Mar 07 10:16:01 2021
      Author: 	Adrie Dane
*/
  function to_db($con,$opts=[])
{
    if(is_null($con))	{
      exit("Trying to send Blob to Database connector is NULL");
    }
    $table = !empty($opts) && in_array('table',array_keys($opts)) ?
      $opts['table'] :
      'file';
    $blob = quickdb::$con->real_escape_string($this->contents);
    $query  = "INSERT INTO `file`(`name`, `type`, `contents`, `size`, `object`) VALUES (";
    $query .= "'".$this->name."', ";
    $query .= "'".$this->type."', ";
    $query .= "'".$blob."', ";
    $query .= $this->size.", ";
    $query .= "'".$this->object."')";
    $con->query($query);
    
    $this->Id=quickdb::$con->insert_id;

    return $this->Id;
} /* to_db */




/*    Title: 	as_object
      Purpose:	
      Created:	Sat Mar 06 12:37:34 2021
      Author: 	Adrie Dane
*/
  function as_object($args=[],$obj_str='')
{

  $dir = sys_get_temp_dir();
  $fname = $dir.'/'.$this->name;

  $obj_str = empty($obj_str) ? $this->object : $obj_str;
  if(empty($obj_str))	{
    exit("Blob->as_object() Object type/class unknown");
  }

  if ( file_put_contents($fname, $this->contents)
       ===FALSE) {
    exit("Could not create $obj_str from BLOB");
  }else{
    $obj = new $obj_str($fname,...$args);
    unlink($fname);
    return $obj;
  }
} /* as_object */



}

?>