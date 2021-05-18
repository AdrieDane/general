<?php
/*
numerics
-------------16,1,1,2,9,3,8,8,4,5,246,246,246,

dates
------------10,12,7,11,13,

strings & binary
------------254,253,254,254,254,253,252,252,252,252,252,252,252,
*/
/*    Title: 	quickdb
      Purpose:	interface to quick database
      Created:	Sun Jul 16 11:26:48 2017
      Author: 	Adrie Dane
*/
class Qdb extends mysqli
{
  static $verbose=false;
  static $login='login.php';

  function __construct()
  {
    require_once(self::$login);
 
    parent :: __construct( $db_hostname, $db_username, $db_password, $db_database);
    
    if ($this->connect_error) 
      die("Connection failed: " . $this->connect_error);
  }

  // a 1 dimensional array is returned when #rows =1
  // if $force_array=true forces 2 dimensional array
  // if $force_array=-1 forces single value
  function query($query='',$options=[])
  {
    if(self::$verbose==true)	{
      echo nl2br("$query\n",false);
    }

    $result = parent :: query($query);

    if(self::$verbose==true)	{
      print_r($result);
      echo '<br>';
    }
    if (!$result) die ("Database access failed:<br>\n" . 
                       $this->error . 
                       nl2br("\nQuery:\n<code>$query</code>"));

    // a query which does not return values
    if($result===true)	{
      return $result;
    }

    $rows = $result->num_rows;
    
    $opts=useroptions(['single_row' => true,
                       'array_type' => MYSQLI_ASSOC,
                       'key_value' => false],$options);
    extract($opts);

    $A = $result->fetch_all($array_type);
    //    pre_r($A,'$A');
    if(!empty($A) && count(reset($A))==2 && $key_value==true)	{
      $keys=array_keys(reset($A));
      $A=array_filter(array_combine(array_column($A,$keys[0]),
                                    array_column($A,$keys[1])));
    }
    if($result->num_rows==1 && $single_row==true)	{
      $A=reset($A);
    }
    
    if(self::$verbose==true)	{
      echo pre_r($A,true),"<br>";
    }
    $result->close();
    return $A;
  }

  /*    Title: 	fieldinfo
        Purpose:	
        Created:	Sat May 15 12:04:04 2021
        Author: 	
  */
  function fieldinfo($table,$keys=[])
  {
    $fields = empty($keys) ? "*" : implode(", ",$keys);
    $query="SELECT $fields FROM $table LIMIT 1";
    $row=parent::query($query);
    $fieldinfo=$row->fetch_fields();
    foreach($fieldinfo as $field => &$info) {
      if(in_array($info->type,[16,1,2,9,3,8,246]))	{
        $info->prepared='i';
        $info->typestr='int';
      } elseif(in_array($info->type,[4,5]))	{
        $info->prepared='d';
        $info->typestr='double';
      } elseif(in_array($info->type,[10,12,7,11,13]))	{
        $info->prepared='x';
        $info->typestr='date';
      } elseif(in_array($info->type,[254,253]))	{
        $info->prepared='s';
        $info->typestr='string';
      } elseif(in_array($info->type,[252]))	{
        $info->prepared='b';
        $info->typestr='blob';
      } else {
        $info->prepared='s';
        $info->typestr='string';
      }
      
      if($info->flags & 2)	{
        $info->primary = true;
      }
      if($info->flags & 1024)	{
        $info->timestamp = true;
      }
    }
    return array_combine(array_column($fieldinfo,'name'),
                         $fieldinfo);
  } /* fieldinfo */

  
  /*    Title: 	insert
        Purpose:	insert query
        Created:	Sat May 15 10:58:00 2021
        Author: 	
  */
function insert($table,$A,$keys=[])
  {
    if(is_array($A))	{
      //$a is the first element of $A
      $a=reset($A);
      if(!is_array($a))	{
        $a=$A;
        $A=[$A];
      }
      $A = new datatable($A);
    }
    if($A->is_associative())	{
      if(!empty($keys))	{
        $A=$A->columns($keys);
      } else {
        $keys=$A->column_names();
      }
      $tmp_keys = new datatable($keys);
      if($tmp_keys->is_associative())	{
        $field_keys=array_keys($keys);
      } else {
        $fieldinfo = $this->fieldinfo($table,$keys);
        $field_keys = array_intersect(array_column($fieldinfo,'name'),$keys);
        // only update overlapping keys
        if(count($keys)!=count($field_keys))	{
          $A=$A->columns($field_keys);
          //$keys=$field_keys;
        }
      }
    } else {
      $nkeys=count(reset($A->data));
      $fieldinfo = $this->fieldinfo($table);
      if(count($fieldinfo)>$nkeys)	{
        $flag=false;
        $extra_fields = ['primary','timestamp'];
        foreach($extra_fields as $extra) {
          $fieldinfo=array_filter($fieldinfo,
                                  function($a) use($extra)
                                  {return !isset($a->$extra);});
          if(count($fieldinfo)==$nkeys)	{
            $flag=true;
            break;
          }
        }
        if($flag==false)	{
          exit('Qdb insert cannot insert array with numeric keys number '.
               'of fields does not match');
        }
      }
      // pre_r($fieldinfo,'$fieldinfo');
      $field_keys=array_column($fieldinfo,'name');
    }
    
    // get type string
    $type_str = $A->gettype([],true);
    $questionmarks = array_fill(0,$nkeys,'?');
    $questionmarks="(".implode(", ",$questionmarks).")";
    $values_str = " VALUES".$questionmarks;
    $fields = implode(", ",$field_keys);

    if(false)	{
      // 100 times takes around 16 seconds
      $prepare_str="INSERT INTO $table ($fields)".$values_str;
      $stmt = $this->prepare($prepare_str);
      foreach($A->data as $x) {
        $v=array_values($x);
        $stmt->bind_param($type_str,...$v);
        $stmt->execute();
      }
      $stmt->close();
    } else {
      // 100 times takes around 5 seconds
      $nrows=count($A->data);
      $type_str = array_fill(0,$nrows,$type_str);
      $type_str=implode('',$type_str);
      $questionmarks = implode(", ",array_fill(0,$nrows,$questionmarks));
      $values_str = " VALUES".$questionmarks;
      $prepare_str="INSERT INTO $table ($fields)".$values_str;
      $stmt = $this->prepare($prepare_str);
      $v=$A->vector();
      $stmt->bind_param($type_str,...$v);
      $stmt->execute();
      $stmt->close();
    }
    
    pre_r($prepare_str,'$prepare_str');
    pre_r($type_str,'$type_str');
    pre_r($v,'$v');
  } /* insert */

  /*  function __destruct() {
    //print "Destroying \n";
    $this->close();
    }*/
} /* Qdb */
?>
