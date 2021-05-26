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
  
  /*    Title: 	options
        Purpose:	sets and returns options
        Created:	Wed May 19 08:59:59 2021
        Author: 	
  */
  function queryoptions($options)
  {
    return useroptions(['single_row' => true,
                        'array_type' => MYSQLI_ASSOC,
                        'key_value' => false],$options);
  } /* options */

  /*    Title: 	update_column
      Purpose:	multi update using prepared statement
      Created:	Sat May 22 09:46:23 2021
      Author: 	
*/
  function update_column($table,$current,$new,$key,$id)
{
  $when=[];
  $in=[];
  $query="UPDATE $table SET $key = (case"; 
  foreach($new as $row => $x) {
    if($current[$row][$key] != $x[$key])	{
      $query .= " WHEN $id = ? THEN ?";
      $when[]=$current[$row][$id];
      $when[]=$x[$key];
      $in[]=$current[$row][$id];
    }
  }
  if(!empty($in))	{ //update
    $data = array_merge($when,$in);
    $nupdate=count($in);
    $questionmarks = array_fill(0,$nupdate,'?');
    // $questionmarks="(".implode(", ",$questionmarks).")";
    $query .= " END) WHERE $id in (".implode(", ",$questionmarks).")";
  } else { // nothing to update
    return;
  }
  $whentypestr=$this->type_str($table,[$id,$key]);
  $intypestr=substr($whentypestr,0,1);
  $typestr=str_repeat($whentypestr,$nupdate).str_repeat($intypestr,$nupdate);
  $this->prepared($query,$typestr,$data);
} /* update_column */

  
  /*    Title: 	prepared
        Purpose:	run query with prepared statement
        Created:	Wed May 19 08:34:09 2021
        Author: 	
  */
  function prepared($query='',$types,$data=[],$options=[])
  {
    $stmt = $this->prepare($query);
    // prepare() can fail because of syntax errors, missing privileges, ....
    if ( false===$stmt ) {
      // and since all the following operations need a valid/ready statement object
      // it doesn't make sense to go on
      // you might want to use a more sophisticated mechanism than die()
      // but's it's only an example
      die('prepare() failed: ' . htmlspecialchars($mysqli->error));
    }
    $rc = $stmt->bind_param($types, ...$data);
    // bind_param() can fail because the number of parameter doesn't match the placeholders
    // in the statement
    // or there's a type conflict(?), or ....
    if ( false===$rc ) {
      // again execute() is useless if you can't bind the parameters. Bail out somehow.
      die('bind_param() failed: ' . htmlspecialchars($stmt->error));
    }

    $rc = $stmt->execute();
    // execute() can fail for various reasons. And may it be as stupid as someone
    // tripping over the network cable
    // 2006 "server gone away" is always an option
    if ( false===$rc ) {
      die('execute() failed: ' . htmlspecialchars($stmt->error));
    }

    if(self::$verbose==true)	{
      echo nl2br("<b>Running Prepared Statement:</b>\n$query\n<b>Data</b>:\n",false);
      echo pre_r($data,"<b>data</b>",true);
    }

    $result=$stmt->get_result();
    
    if (!$result) {
      if(in_array(substr($query,0,6),["INSERT"]))	{
        $id = $this->insert_id;
        if(self::$verbose==true)	{
          echo nl2br("<b>Last Insert:</b> $id\n",false);
        }
        return $id; 
      }
      $affected_rows = $this->affected_rows;
      if(self::$verbose==true)	{
        echo nl2br("<b>Number of affected rows:</b> $affected_rows\n",false);
      }
      return $affected_rows;
    }
    return $this->format_result($stmt->get_result(),$this->queryoptions($options));
  } /* prepared */

  
  /*    Title: 	format_result
        Purpose:	creating return result
        Created:	Wed May 19 09:06:12 2021
        Author: 	
  */
  function format_result($result,$options)
  {
  

    // a query which does not return values
    if($result===true)	{
      $result = $this->insert_id>0 ? $this->insert_id>0 : $result;
      if(self::$verbose==true)	{
        pre_r($result,"<b>Query Result</b>");
        echo '<br>';
      }
      return $result;
    }

    $rows = $result->num_rows;
    extract($options);

    $A = $result->fetch_all($array_type);

    if(!empty($A) && count(reset($A))==2 && $key_value==true)	{
      $keys=array_keys(reset($A));
      $A=array_filter(array_combine(array_column($A,$keys[0]),
                                    array_column($A,$keys[1])));
    }
    if($result->num_rows==1 && $single_row==true)	{
      $A=reset($A);
    }
    if(self::$verbose==true)	{
      pre_r($A,"<b>Query Result</b>");
      echo '<br>';
    }
    
    /*if(self::$verbose==true)	{
      echo pre_r($A,true),"<br>";
      }*/
    $result->close();
    return $A;;
  } /* format_result */

  
  // a 1 dimensional array is returned when #rows =1
  // if $force_array=true forces 2 dimensional array
  // if $force_array=-1 forces single value
  function query($query='',$options=[])
  {
    if(self::$verbose==true)	{
      echo nl2br("<b>Running Query:</b>\n$query\n",false);
    }

    $result = parent :: query($query);
    
    if (!$result) die ("Database access failed:<br>\n" . 
                       $this->error . 
                       nl2br("\nQuery:\n<code>$query</code>"));

    $result = $this->format_result($result,$this->queryoptions($options));

    return $result;
  }

/*    Title: 	column_types
      Purpose:	
      Created:	Sat May 22 08:31:59 2021
      Author: 	
*/
  function column_types($table,$keys=[])
{
  $str = empty($keys) ? "" : " AND COLUMN_NAME IN ('".implode("', '",$keys)."')";
  return $this->query("SELECT COLUMN_NAME, DATA_TYPE ".
                        "FROM INFORMATION_SCHEMA.COLUMNS " .
                        "WHERE ".
                        "TABLE_NAME = '$table'$str",
                      ['key_value' => true,
                       'single_row' => false]);
  
} /* column_types */

  /*    Title: 	type_str
      Purpose:	return type string for prepared queries
      Created:	Sat May 22 09:05:31 2021
      Author: 	
*/
function type_str($table,$keys=[])
{
  $types=$this->column_types($table,$keys);
  $prepared='';
  //  pre_r($types,'$types');
  foreach($keys as $key) {
    if(in_array($types[$key],['int','bit','timestamp']))	{
      $prepared .= 'i';
    } elseif($types[$key]=='blob')	{
      $prepared .= 'b';
    } elseif(in_array($types[$key],['double','float']))	{
      $prepared .= 'd';
    } elseif(in_array($types[$key],['varchar','char']))	{
      $prepared .= 's';
    } else {
      exit("Qdb type_str unknown type: ".$types[$key]);
    }
  }
  return $prepared;
} /* type_str */

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

  /*    Title: 	primary_key
        Purpose:	returns primary key of table
        Created:	Fri May 21 10:01:14 2021
        Author: 	
  */
  function primary_key($table)
  {
    $result=$this->query("SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'");
    return $result['Column_name'];
  } /* primary_key */

  
  /*    Title: 	update_or_insert
        Purpose:	
        Created:	Fri May 21 09:25:25 2021
        Author: 	
  */
  function update_or_insert($table,$A,$keys=[],$where=[],$options=[])
  {

    $A = $this->map_keys($table,$A,$keys);

    $prim=$this->primary_key($table);

    //pre_r($A,'$A');

    // get all table data
    $X=$this->query("SELECT * FROM $table");

    // check wheather new data was already present and just needs an update
    // otherwise it should be inserted
    $split=$A->search($X,$where,$prim);
    //pre_r($split,'$split');
    if(!empty($split))	{
      $keys=array_intersect(array_keys(reset($X)),
                            $keys);
    }
    extract($split);
    if(isset($absent) && !empty($absent))	{
      $Ainsert=new datatable($absent);
      $absent=$Ainsert->columns($keys);
      //pre_r($Ainsert,'$absent');
      $this->insert($table,$Ainsert);
      
    }

    if(isset($present) && !empty($present))	{
      if(empty($keys))	{
        $keys=array_keys(reset($X));
      }
      $keys=array_diff($keys,$where);
      //pre_r($present,'$present');
      //pre_r($keys,'$keys');
      foreach($keys as $key) {
        //        pre_r($key,'update: $key');
        $this->update_column($table,$X,$present,$key,$prim);
      }
    }
  } /* update_or_insert */

  /*    Title: 	map_keys
        Purpose:	helper function to prepare insert/update data
        Returns a datatable object with keys that are present in $table
        Created:	Sat May 22 11:44:48 2021
        Author: 	
  */
  function map_keys($table,$A,$keys)
  {
    // Make sure $A is a valid datatable
    if(is_array($A))	{
      //$a is the first element of $A
      $a=reset($A);
      if(!is_array($a))	{
        $a=$A;
        $A=[$A];
      }
      $A = new datatable($A);
    }

    // data with numerical fields can only be used when number of fields match
    if(!$A->is_associative())	{
      //      pre_r($A,'$A');
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
      $field_keys=array_column($fieldinfo,'name');
      foreach($A->data as &$x) {
        $x = array_combine($field_keys,$x);
      }
      return $A;
    }


    // At this point we have an associative datatable
    if(!empty($keys))	{
      $A=$A->columns($keys);
      $tmp_keys = new datatable($keys);
      // rename data keys so they match $table keys
      if($tmp_keys->is_associative())	{
        $field_keys=array_keys($keys);
        foreach($A->data as &$x) {
          $x = array_combine($field_keys, array_values($x));
        }
      }
    }
    return $A;
  } /* map_keys */


  
  /*    Title: 	insert
        Purpose:	insert query
        Created:	Sat May 15 10:58:00 2021
        Author: 	
  */
  function insert($table,$A,$keys=[],$options=[])
  {

    $A=$this->map_keys($table,$A,$keys);
    
    // get type string
    $type_str = $A->gettype([],true);
    $field_keys=$A->column_names();
    $nkeys = count($field_keys);
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
      $v=$A->vector();
      $this->prepared($prepare_str,$type_str,$v);
      /*      
      $stmt = $this->prepare($prepare_str);
      $stmt->bind_param($type_str,...$v);
      $stmt->execute();
      $stmt->close();
      */
    }
    
    //    pre_r($prepare_str,'$prepare_str');
    //    pre_r($type_str,'$type_str');
    //    pre_r($v,'$v');
    return $this->insert_id;
  } /* insert */

  /*  function __destruct() {
    //print "Destroying \n";
    $this->close();
    }*/
} /* Qdb */
?>
