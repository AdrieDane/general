<?php

/* activate reporting */
$driver = new mysqli_driver();
$driver->report_mode = MYSQLI_REPORT_STRICT;
/*
class QdbException extends Exception {
  public function errorMessage() {
    $trace = $this->getTrace();
    $str = "Trace: <br>\n";
    foreach($trace as $x) {
      $str .= $x['file'].'('.$x['line'].')';
      $str .= isset($x['class']) && !empty($x['class'])
           ? $x['class'].$x['type'].$x['function'].'()<br>'
           : $x['function'].'()<br>';
    }
    //error message
    $errorMsg = 'Error on line '.$this->getLine().' in '.$this->getFile()
    .': <b>'.$this->getMessage().'</b> is not a valid E-Mail address';
    return $errorMsg;
  }
}
*/

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
#[\AllowDynamicProperties]
class Qdb extends mysqli
{
  static $verbose=false;
  static $login='login.php';

  function __construct()
  {
    require_once(self::$login);
 
    parent :: __construct( $db_hostname, $db_username, $db_password, $db_database);
    
    $this->set_charset("utf8");

    if ($this->connect_error) 
      die("Connection failed: " . $this->connect_error);
  }

  /*    Title: 	truncate
        Purpose:	truncate $table
        Created:	Tue Jul 20 12:08:41 2021
        Author: 	
  */
  function truncate($table)
  {
    return $this->query("TRUNCATE TABLE $table");
  } /* truncate */


  /*    Title: 	update_column
        Purpose:	multi update using prepared statement
                  returns primary_keys of updated records
        Created:	Sat May 22 09:46:23 2021
        Author: 	
  */
  function update_column($table,$new,$column,$options=[])
  {
    $opts=useroptions(['id' => '',
                       'append' => false,
                       'Xdb' => []],$options);
    extract($opts);
    
    if(empty($id))	{
      $id=$this->primary_key($table);
    }
    if(empty($Xdb))	{
      $Xdb=$this->query("SELECT $id, $column FROM $table");
    }
    
    // make sure it is key => value
    $Xdb=array_combine(array_column($Xdb,$id),$Xdb);
    $when=[];
    $in=[];

    // check whether updates are required and build quiry (prepared statement)
    $query="UPDATE $table SET $column = (case"; 
    foreach($new as $row => $x) {
      if($Xdb[$row][$column] != $x[$column])	{
        $query .= " WHEN $id = ? THEN ?";
        $when[]=$Xdb[$row][$id];
        if($append==false)	{
          $when[]=$x[$column];
        } else {
          $val=explode(',',$Xdb[$row][$column].','.implode(',',$x[$column]));
          $val=implode(',',array_unique($val));
        }
        $in[]=$Xdb[$row][$id];
      }
    }

   //nothing to update
    if(empty($in))	{
      return [];
    }
    
    /* this is for debugging only
   if($table=='samples')	{
        return ['update_column' => 'nothing to update',$id => $in,'Xdb' => $Xdb];
        }
    */

    // create the data $when is appended by $in is this correct ?
    $data = array_merge($when,$in);

    // finalize query
    $nupdate=count($in);
    $questionmarks = array_fill(0,$nupdate,'?');
    $query .= " END) WHERE $id in (".implode(", ",$questionmarks).")";
    // now the query is ready

    // create type string for all questionmarks
    $whentypestr=$this->type_str($table,[$id,$column]);
    $intypestr=substr($whentypestr,0,1);
    $typestr=str_repeat($whentypestr,$nupdate).str_repeat($intypestr,$nupdate);

    // run the update query with correct types and data
    $this->prepared($query,$typestr,$data);
    return $in;

      /*   
    */
    } /* update_column */

  
  /*    Title: 	prepared
        Purpose:	run query with prepared statement
        Created:	Wed May 19 08:34:09 2021
        Author: 	
  */
  function prepared($query,$types,$data=[],$options=[])
  {
                        
    if(!is_array($data))	{
      $data=[$data];
    }
    if(self::$verbose==true)	{
      echo nl2br("<b>Running Prepared Statement:</b>\n$query\n",false);
      echo pre_r($types,"<b>Types</b>",true);
      echo pre_r($data,"<b>Data</b>",true);
    }

    $stmt = $this->prepare($query);
    // prepare() can fail because of syntax errors, missing privileges, ....
    if ( false===$stmt ) {
      // and since all the following operations need a valid/ready statement object
      // it doesn't make sense to go on
      // you might want to use a more sophisticated mechanism than die()
      // but's it's only an example
      die('prepare() failed: ' . htmlspecialchars($stmt->error));
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
    //    pre_r($result,'$result');
    return $this->format_result($result,$options);
  } /* prepared */

  
  /*    Title: 	format_result
        Purpose:	creating return result
        Created:	Wed May 19 09:06:12 2021
        // a 1 dimensional array is returned when #rows =1
        // if $force_array=true forces 2 dimensional array
        // if $force_array=-1 forces single value
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
    $opts = useroptions(['table' => '',
                         'single_row' => true,
                         'force_array' => false,
                         'array_type' => MYSQLI_ASSOC,
                         'key_value' => false,       // no empty values allowed
                         'key_value_empty' => false, // empty values allowed
                         'dimensions' => null,
                         'array_keys' => '',
                         'ignore' => []],$options);
    extract($opts);

    $A = $result->fetch_all($array_type);

    if(!empty($A) && count(reset($A))==2 &&
       ($key_value==true || $key_value_empty==true))	{
      $keys=array_keys(reset($A));
      $A = $key_value==true
         ? array_filter(array_combine(array_column($A,$keys[0]),
                                      array_column($A,$keys[1])))
         : array_combine(array_column($A,$keys[0]),
                       array_column($A,$keys[1]));
    }
    if($result->num_rows==1 && $single_row==true)	{
      $A=reset($A);
    }

    if(is_array(reset($A)) && $dimensions==1)	{
      $A=array_merge(...$A);
    }

    if(!empty($table) && empty($array_keys))	{
      $array_keys=$this->primary_key($table);
    }
    if(is_array($A) && !empty($array_keys) && $key_value==false)	{
      //      pre_r($array_keys);
      $A=array_combine(array_column($A,$array_keys),$A);
    }
    if(!empty($A) && !empty($ignore))	{
      foreach($ignore as $key) {
        if(isset($A[$key]))	{
          unset($A[$key]);
        }
      }
    }
    if(self::$verbose==true)	{
      pre_r($A,"<b>Query Result</b>");
      echo '<br>';
    }
    
    /*if(self::$verbose==true)	{
      echo pre_r($A,true),"<br>";
      }*/
    $result->close();
    return $A;
  } /* format_result */
  
  #[\ReturnTypeWillChange]
  function query($query='',$options=[])
  {
    if(self::$verbose==true)	{
      echo nl2br("<b>Running Query:</b>\n$query\n",false);
    }

    try {
      $result = parent :: query($query);
      if(!$result)	{
        throw new mysqli_sql_exception();
      }
    } catch(mysqli_sql_exception $e) {
      $opts = useroptions(['empty_on_error' => false],$options);
      if($opts['empty_on_error']==true)	{
        return [];
      }
      echo error_message($e,nl2br("\nQuery:\n<code>$query</code>\n"));
      die();
    }
    $result = $this->format_result($result,$options);

    
    return $result;
  }

  /*    Title: 	join_columns
        Purpose:	
        Created:	Mon Jan 30 17:14:37 2023
        Author: 	
  */
  function join_columns($tables_str,$out='implode')
  {
    $table_column=false; // tables only
    if(in_array($out,['table.column','implode']))	{
      $table_column=true;
    }
    $tables=explode(',',$tables_str);
    $arr = [];
    foreach($tables as $table) {
      $t=trim($table);
      $cols = $this->query("SELECT COLUMN_NAME, TABLE_NAME ".
                           "FROM INFORMATION_SCHEMA.COLUMNS " .
                           "WHERE ".
                           "TABLE_NAME = '$t'",
                           ['key_value' => true,
                            'single_row' => false]);
      foreach($cols as $k => &$v) {
        if($table_column==true)	{
          $v .= '.'.$k;
        }
        if(!in_array($k,array_keys($arr)))	{
          $arr[$k] = $v;
        }
      }
      unset($v);
    }
    
    if($out=='implode')	{
      return implode(', ',$arr);
    }else	{
      return $arr;
    }
  } /* join_columns */

  /*    Title: 	column_types
        Purpose:	
        Created:	Sat May 22 08:31:59 2021
        Author: 	
  */
  function column_types($table,$keys=[])
  {
    $str = empty($keys) ? "" : " AND COLUMN_NAME IN ('".implode("', '",$keys)."')";

    $column_types  =$this->query("SELECT COLUMN_NAME, DATA_TYPE ".
                                 "FROM INFORMATION_SCHEMA.COLUMNS " .
                                 "WHERE ".
                                 "TABLE_NAME = '$table'",
                                 ['key_value' => true,
                                  'single_row' => false]);
    if(empty($keys))	{
      return $column_types;
    }
    $column_names=array_keys($column_types);
    //  pre_r($column_types,'$column_types*');
    $data=[];
    foreach($keys as $key) {
      if(!in_array($key,$column_names))	{
        exit("ERROR Qdb column_types: $key not present in database table $table");
      }
      $data[$key]=$column_types[$key];
    }

  
    return $data;
  
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
    // pre_r($table,'$table');
    // pre_r($keys,'$keys');
    // pre_r($types,'$types');
    foreach($keys as $key) {
      if(in_array($types[$key],['int','bit','timestamp','tinyint','bigint']))	{
        $prepared .= 'i';
      } elseif($types[$key]=='blob')	{
        $prepared .= 'b';
      } elseif(in_array($types[$key],['double','float']))	{
        $prepared .= 'd';
      } elseif(in_array($types[$key],['varchar','char','longtext','mediumtext','date','text']))	{
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
    unset($info);
    return array_combine(array_column($fieldinfo,'name'),
                         $fieldinfo);
  } /* fieldinfo */
  
  /*    Title: 	metadata
        Purpose:	get metadata for table
        Created:	Thu Oct 20 08:35:04 2022
        Author: 	
  */
  function metadata($table,$options=[],$keys=[])
  {
    $defaults=false;
    if(isset($options['defaults']) && $options['defaults']==true)	{
      //      $keys=['COLUMN_NAME', 'COLUMN_DEFAULT'];
      $defaults=true;
      unset($options['defaults']);
    }
    $fields = empty($keys)? "*" : implode(", ",$keys);
    
    if(count($keys)==2 && !isset($options['key_value_empty']))	{
      $opts = useroptions(['key_value_empty' => true,
                           'ignore' => ['UpdateStamp']],$options);
    }else	{
      $opts = useroptions(['key_value' => false,
                           'array_keys' => 'COLUMN_NAME',
                           'ignore' => ['UpdateStamp']],
                          $options);
    }
                         
    $sql = "SELECT $fields ".
         "FROM INFORMATION_SCHEMA.COLUMNS " .
         "WHERE ".
         "TABLE_NAME = '$table'";
    $result = $this->query($sql,$opts);

    if($defaults==true)	{
      $arr=[];
      foreach($result as $key => $x) {
          echo "\n<br>".$key."(".gettype($x['COLUMN_DEFAULT']).") => ".$x['COLUMN_DEFAULT'];
        if(is_string($x['COLUMN_DEFAULT']))	{
          if($x['COLUMN_DEFAULT']=='NULL' && in_array($x['DATA_TYPE'],['date','varchar']))	{
            $x['COLUMN_DEFAULT']=='';
          }
          $x['COLUMN_DEFAULT'] = $x['DATA_TYPE']=='datexx'
                               ? $x['COLUMN_DEFAULT']
                               : str_replace("'","",$x['COLUMN_DEFAULT']);

          if(($x['COLUMN_DEFAULT']=='NULL' || is_null($x['COLUMN_DEFAULT'])) &&
              in_array($x['DATA_TYPE'],['date','varchar']))	{
            $x['COLUMN_DEFAULT']=='';
          }
          $arr[$key] = $x['COLUMN_DEFAULT'];
        }else	{
          $arr[$key] = $x['COLUMN_DEFAULT'];
        }
      }
      return $arr;
    }

    
    return $result;
  } /* metadata */

  /*    Title: 	primary_key
        Purpose:	returns primary key of table
        Created:	Fri May 21 10:01:14 2021
        Author: 	
  */
  function primary_key($table)
  {
    $result=$this->query("SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'");
    try {
      if(!isset($result['Column_name']))	{
        throw new Exception(); 
      }
    }

    catch (exception $e) {
      // echo "Exception: No primary key for table: ".$table;
      return '';
    }
    return $result['Column_name'];
  } /* primary_key */

  /*    Title: 	update
        Purpose:	as update_or_insert with stricter rules
        Created:	Mon Jul 19 17:45:14 2021
        Author: 	
  */
  function update($table,$A,$keys=[],$where=[],$options=[])
  {
    /*    return ['table' => $table,
            'A' => $A,
            'keys' => $keys,
            'where' => $where,
            'options' => $options]; */

    // make sure $A is a datatable
    if(!$A instanceof datatable)	{
      $A = new datatable($A);
    }

    // make sure $where is an array
    if(!is_array($where))	{
      $where=[$where];
    }

    // get the user options
    $opts = useroptions(['map' => []],$options);

    
    // rename keys in new array so they match the database table
    if(!empty($opts['map']))	{
      $A = clone $A;
      //      pre_r($A,'$A voor');
      $A->rename_keys($opts['map']);
      //      pre_r($A,'$A na');
    }

    // get the column SQL types
    $column_types=$this->column_types($table);
    // pre_r($column_types,'$column_types');

    // get and corresponding column SQL names
    $column_names=array_keys($column_types);

    // SOMETHING HAPPENS TWICE HERE
    if(count($where) != count(array_intersect($column_names,$where)))	{
      //pre_r($where,'$where');
      //pre_r($column_names,'$column_names');
      //    return ['error' => 'fout'];
      exit("ERROR Qdb: update not all wherekeys are present in database table."
           .pre_r($where,'$where keys',true));
    }

    // make sure all columns to check for update are present in SQL table if not: return error
    if(empty($keys))	{
      $keys=array_intersect($A->column_names(),$column_names);
    } else {
      if(count($keys) != count(array_intersect($keys,$column_names)))	{
        //pre_r($keys,'$keys');
        //pre_r($column_names,'$column_names');
        exit("ERROR Qdb: update not all keys are present in database table.".
             pre_r(array_diff($keys,$column_names),'diffs',true));
      }
      
      $column_names=$A->column_names();
      if(count($keys) != count(array_intersect($keys,$column_names)))	{
          pre_r($A,'$A');
        pre_r($keys,'$keys');
        pre_r($column_names,'$column_names');
        exit("ERROR Qdb: update not all keys are present in data.".
             pre_r($A,'$data',true));
      }
    }

    $column_names=$A->column_names();
    if(count($where) != count(array_intersect($column_names,$where)))	{
      //pre_r($where,'$where');
      //pre_r($column_names,'$column_names');
      exit("ERROR Qdb: update not all wherekeys are present in data.".
           pre_r($where,'$where keys',true).
             pre_r($A,'$data',true));
    }
    // SOMETHING HAPPENS TWICE HERE 

    // get all table data and use primary key as keys
    $Xdb=$this->query("SELECT * FROM $table",['single_row' => false,
                                              'table' => $table]);
    if(empty($Xdb))	{
      $absent=$A->data;
      $split=[];
    } else {
      // check wheather new data was already present and just needs an update
      // otherwise it should be inserted
      // when appropiate it splits the data into present absent and ambiguous
      // if nothing present $split['present'] will not be set etc.
      // ambiguous means present more than once: $where should be unique
      $split=$A->search($Xdb,$where);
      // pre_r($split,'$split');
      //      return $split;
      // create $absent and $present
      extract($split);
    }
    $nchecks = count($A->data);
    $Id=$this->primary_key($table);
    $retval=['table' => $table, 'primary' => $Id,
             'lastId' => 0,
             'nchecks' => $nchecks,
             'inserted' => [],
             'updated' => [],
             'unchanged' => [],
             'ambiguous' => [], 
             'log' => 'Update Table: ' . $table . '<ul>',
             'split' => $split];
    // REMOVED BECAUSE NUMBERS DON'T ADD UP (unknown reason)
    //   $retval['log'] .= '<li>Checked: '.$nchecks.' records';
    //    $retval['log'] .= '<li>Checked: '.count($Xdb).' records';
    if(isset($absent) && !empty($absent))	{
      $retval['log'] .= '<li>Insertions: '.count($absent).' records';
      $Ainsert=new datatable($absent);
      $Id=$insertId=$this->insert($table,$Ainsert->columns($keys));
      $retval['inserted']=range($Id,count($absent)-1+$Id);
    }
    $updated=[];
    if(isset($present) && !empty($present))	{
      //      $retval['updateId']=count($present);
      //      $retval['log'] .= '<li>Checks and/or Updates: '.count($present).' records';
      $keys=array_diff($keys,$where);
      foreach($keys as $column) {
        // this returns prim key of updates
        $column_updated = $this->update_column($table,$present,
                                               $column,['Xdb' => $Xdb]);
        
        $updated = empty($column_updated) ? $updated : array_merge($updated,$column_updated);
      }
      $retval['updated']=array_unique($updated);
      $retval['log'] .= '<li>Updates: '.count($updated).' records';
      $retval['unchanged']=array_diff(array_keys($present),$retval['updated']);
      $retval['log'] .= '<li>Unchanged: '.count($retval['unchanged']).' records';
      //      $Id=$updateId = count($present)>1 ? array_keys($present) : key($present);
    }
    foreach(['inserted','updated','unchanged'] as $field) {
      if(!isset($retval[$field]) || empty($retval[$field]))	{
        continue;
      }
      $mx=max($retval[$field]);
      if($mx>$retval['lastId'])	{
        $retval['lastId']=$mx;
      }
    }
    
    if(isset($ambiguous) && !empty($ambiguous))	{
      $retval['ambiguous']=new datatable($ambiguous);
    }
    $retval['log'] .= '</ul>';
    $retval['split']=$split;
    return $retval;
  } /* update */

  
  /*    Title: 	update_or_insert
        Purpose:	
        $A either a datatable or 1D or 2D array
        Created:	Fri May 21 09:25:25 2021
        Author: 	
  */
  function update_or_insert($table,$A,$keys=[],$where=[],$options=[])
  {
    // make sure keys are present in the SQL table
    $A = $this->map_keys($table,$A,$keys);
    //   pre_r($A,'$A');

    //pre_r($A,'$A');

    // get all table data
    $Xdb=$this->query("SELECT * FROM $table",['single_row' => false]);
    if(empty($Xdb))	{
      //pre_r($Xdb,'$Xdb');
      $absent=$A->data;
    } else {
      $prim=$this->primary_key($table);
      // check wheather new data was already present and just needs an update
      // otherwise it should be inserted
      $split=$A->search($Xdb,$where);
      // pre_r($split,'$split');
      if(!empty($split))	{
        $keys=array_intersect(array_keys(reset($Xdb)),
                              $keys);
      }
      extract($split);
    }

    if(isset($absent) && !empty($absent))	{
      if(empty($keys))	{
        $keys=array_keys(reset($absent));
      }
      // pre_r($keys,'$keys');
      $Ainsert=new datatable($absent);
      $absent=$Ainsert->columns($keys);
      //pre_r($Ainsert,'$absent');
      $this->insert($table,$Ainsert);
    }

    if(isset($present) && !empty($present))	{
      if(empty($keys))	{
        $keys=array_keys(reset($Xdb));
      }
      $keys=array_diff($keys,$where);
      //pre_r($present,'$present');
      //pre_r($keys,'$keys');
      foreach($keys as $key) {
        //        pre_r($key,'update: $key');
        $this->update_column($table,$present,$key,['id' => $prim,
                                                   'Xdb' => $Xdb]);
      }
    }
  } /* update_or_insert */

  /*    Title: 	map_keys
        Purpose:	helper function to prepare insert/update data
        Steps: 1 convert $A into a 2 dimensional datatable
        2 test whether columns in A are numeric
        Returns a datatable object with keys that are present in $table
        Created:	Sat May 22 11:44:48 2021
        Author: 	
  */
  function map_keys($table,$A,$keys)
  {
    // step 1 Make sure $A is a valid 2D datatable
    if(!$A instanceof datatable)	{
      $A = new datatable($A);
    }
    
    if(!empty($keys))	{ // only preserve keys to update
      $A=$A->columns($keys);
    }
    //    pre_r($keys,'$keys');
    //    pre_r($A,'$A');
    
    // step 2 data with numerical fields can only be used when number of fields match
    if(!$A->is_associative(2))	{
      //      pre_r($A,'$A');
      $nkeys=count(reset($A->data));
      $fieldinfo = $this->fieldinfo($table);
      // pre_r($fieldinfo,'$fieldinfo');
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
      unset($x);
      return $A;
    }

    //    pre_r($A,'$A');
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
    //    pre_r($A,'$A');
    //    exit;
    // get type string from datatable
    // $type_str = $A->gettype([],true);
    $field_keys=$A->column_names();
    $type_str = $this->type_str($table,$field_keys);
    pre_r($type_str,'$type_str');
    $nkeys = count($field_keys);
    $questionmarks = array_fill(0,$nkeys,'?');
    $questionmarks="(".implode(", ",$questionmarks).")";
    $values_str = " VALUES".$questionmarks;
    $fields = implode(", ",$field_keys);

    // 100 times takes around 5 seconds
    $nrows=count($A->data);
    $type_str = array_fill(0,$nrows,$type_str);
    $type_str=implode('',$type_str);
    $questionmarks = implode(", ",array_fill(0,$nrows,$questionmarks));
    $values_str = " VALUES".$questionmarks;
    $prepare_str="INSERT INTO $table ($fields)".$values_str;
    $v=$A->vector();
    $this->prepared($prepare_str,$type_str,$v);
    
    $id = $nrows>1 ? range($this->insert_id-$nrows+1,$this->insert_id) : $this->insert_id;

    return $this->insert_id;
  } /* insert */

  /*    Title: 	source
        Purpose:	run sql commands from a file
        Created:	Thu Jul 21 16:00:36 2022
        Author: 	
  */
  function source($file)
  {
    // Temporary variable, used to store current query
    $sql = '';
    // Read in entire file
    $lines=file($file);
    if(self::$verbose==true)	{
      echo "<b>Running SQL queries from: '$file'</b><br>\n";
    }
    
    // Loop through each line
    foreach ($lines as $line)
    {
      // Skip it if it's a comment
      if (substr($line, 0, 2) == '--' || $line == '')
        continue;

      // Add this line to the current segment
      $sql .= $line;
      // If it has a semicolon at the end, it's the end of the query
      if (substr(trim($line), -1, 1) == ';')
      {
        // Perform the query
        $this->query($sql) or print('Error performing query \'<strong>' . $sql . '\': <br /><br />');
        // Reset temp variable to empty
        $sql = '';
      }
    }
    if(self::$verbose==true)	{
      echo "<b>Queries from: '$file' ran successfully</b><br>\n";
    }
  } /* source */

} /* Qdb */
?>
