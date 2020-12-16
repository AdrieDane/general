<?php


/*    Title: 	quickdb
      Purpose:	interface to quick database
      Created:	Sun Jul 16 11:26:48 2017
      Author: 	Adrie Dane
*/
class quickdb
{
  static $con;
  static $named_queries;
  static $verbose=false;
  static $login='login.php';

  function __construct($named_queries=TRUE)
  {
    require_once(self::$login);
    self::$con = new mysqli($db_hostname, $db_username, $db_password, $db_database);
    if (self::$con->connect_error)
      echo "error connecting";
    
    if (self::$con->connect_error) die(self::$con->connect_error);

    if($named_queries==TRUE)	{
      self::$named_queries=$this->select_name_value('queries','Name','Value');
    }
  }

  function named($name,$val='',$force_array=false)
  {

    if(self::$verbose==true)	{
      echo nl2br("Named Query: $name\n");
    }
    $query=self::$named_queries[$name];

    if($val!='') {
      if(is_array($val)) {
	$query=vsprintf($query,$val);
      }
      else {
	$query=sprintf($query,$val);
      }
    }
    return $this->query($query,$force_array);
  }
  

  // a 1 dimensional array is returned when #rows =1
  // if $force_array=true forces 2 dimensional array
  // if $force_array=-1 forces single value
  function query($query='',$force_array=false)
  {
    if(self::$verbose==true)	{
      echo nl2br("$query\n");
    }

    $result = self::$con->query($query);

    if(self::$verbose==true)	{
      print_r($result);
      echo '<br>';
    }
    if (!$result) die ("Database access failed:<br>\n" . 
		       self::$con->error . 
		       "<br>\nQuery:<br>\n<xmp>$query</xmp>");

    # a query which doesn't return values
    if($result===true)	{
      return $result;
    }

    $rows = $result->num_rows;
    
    if ($rows==1 && $force_array==false)
      $A = $result->fetch_array(MYSQLI_ASSOC);
    elseif ($rows==1 && $force_array==-1)
      $A = $result->fetch_row()[0];
    elseif ($rows>0) {
      $A=array();
      while($row = $result->fetch_array(MYSQLI_ASSOC)) {
	$A[] = $row;
      }
    }
    else
      $A=array();

    if(self::$verbose==true)	{
      echo "<pre>";
      
      print_r($A);
      echo "</pre>";
      echo '<br>';
    }
    $result->close();
    return $A;
  }

  function select_name_value($table,$name,$value)
  {
    $A=$this->query("SELECT $name,$value FROM $table",true);

    $nvA=array();
    foreach($A as $a) {
      $nvA[$a[$name]]=$a[$value];
    }
    return $nvA;
  }
  
  function named_name_value($qname,$name,$value,$val='')
  {
    $A=$this->named($qname,$val,true);

    $nvA=array();
    foreach($A as $a) {
      $nvA[$a[$name]]=$a[$value];
    }
    return $nvA;
  }
  
  function __destruct() {
    //print "Destroying \n";
    self::$con->close();
  }

} /* quickdb */

?>