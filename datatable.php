<?php
//require_once 'vendor/autoload.php';



class datatable implements ArrayAccess, Iterator, Countable
{
  public static $rename = array(); # Static class variable.
  public static $numcol = array(); # Static class variable.
  // private $ix = array();

  public function __construct($data) 
  {
    if($data instanceof datatable)	{
      // simply copy the members
      foreach($data as $key => $value) {
        $this->$key=$value;
      }
      return;
    }
    
    if(!is_array($data))	{
      $data=[$data];
    }
    if(is_array(reset($data)))	{
      $this->data=$data;
    }  else { // force to two dimensions
      $this->data=[];
      $this->data[]=$data;
    }
      //    $this->data = is_array($data) ? $data :[$data];
    //    $this->ncols=count($this->data[0]);
  }

/*    Title: 	rename_keys
      Purpose:	
      Created:	Mon Jul 19 17:38:58 2021
      Author: 	
*/
function rename_keys($rename=[])
{
  foreach($this->data as &$x) {
    foreach($rename as $old => $new) {
      if(isset($x[$old]))	{
        $x[$new]=$x[$old];
        unset($x[$old]);
      }
    }
    unset($x);
  }
} /* rename_keys */

  /*    Title: 	count_columns
        Purpose:	
        Created:	Mon Jul 19 17:53:14 2021
        Author: 	
  */
  function count_columns()
  {
    return count(reset($this->data));
  } /* count_columns */

  
/*    Title: 	jsonify_fields
      Purpose:	
      Created:	Sun Jun 27 09:10:52 2021
      Author: 	
*/
function jsonify_fields($options=[])
{
  $opts=useroptions(['json_fields' => 'json_fields',
                     'fields' => [],
                     'keep' => []],$options);
  extract($opts);

  if(empty($fields) && empty($keep))	{
    exit('ERROR: jsonify_fields both fields and keep options are empty');
  }

  if(!empty($fields) && !empty($keep))	{
    exit('ERROR: jsonify_fields both fields and keep options are set and not empty<br>'.
         'Make sure only one of these two is set and keep the other empty');
  }

  //  pre_r($keep,'$keep');
  $data=[];
  foreach($this->data as $Id => $x) {
    $json=[];
    $y=[];
    foreach($x as $key => $value) {
      if((!empty($fields) && !in_array($key,$fields)) || in_array($key,$keep))	{
        $y[$key]=$value;
      } else {
        $json[$key]=$value;
      }
    }
    $y[$json_fields]=json_encode($json);
    $data[]=$y;
  }
  $this->data=array_combine(array_keys($this->data),$data);
  //  pre_r($opts,'jsonify_fields $opts');
} /* jsonify_fields */

  /*    Title: 	unjsonify_fields
      Purpose:	
      Created:	Sun Jun 27 09:48:43 2021
      Author: 	
*/
function unjsonify_fields($options=[])
{
  $opts=useroptions(['json_fields' => 'json_fields',
                     'fields' => [],
                     'return_data' => false,
                     'keep' => []],$options);
  extract($opts);

  if($return_data==true)	{
    $data=[];
  }
  
  foreach($this->data as &$x) {
    $unjson=json_decode($x[$json_fields]);
    foreach($unjson as $key => $value) {
      $x[$key]=$value;
    }
    unset($x[$json_fields]);
    if($return_data)	{
      $data[]=$unjson;
    }
  }
  unset($x);

  if($return_data)	{
    return $data;
  }
} /* unjsonify_fields */


  
  /*    Title: 	is_associative
        Purpose:	returns true if $this->data is an associative array
        Created:	Tue May 18 10:21:43 2021
        Author: 	
  */
  function is_associative($dimension=1)
  {
    if (array() === $this->data) return false; // empty
    if($dimension==1)	{
      return array_keys($this->data) !== range(0, count($this->data) - 1);
    } elseif($dimension==2) {
      $x=reset($this->data);
      //    pre_r($x,'$x');
      return array_keys($x) !== range(0, count($x) - 1);
    } else {
      exit('ERROR datatable::is_associative $dimension must be 1 or 2');
    }
  } /* is_associative */

  
  /*    Title: 	vector
        Purpose:	returns data as vector of merged rows
        column_names are lost
        Created:	Mon May 17 10:06:58 2021
        Author: 	
  */
  function vector()
  {
    $data=[];
    foreach($this->data as $x) {
      $data[]=array_values($x);
    }
    return array_merge(...$data);
  } /* vector */

  /*    Title: 	column_names
        Purpose:	returns keys of data columns
        Created:	Mon May 17 09:54:25 2021
        Author: 	
  */
  function column_names()
  {
    return array_keys(reset($this->data));
  } /* column_names */

  /*    Title: 	columns
        Purpose:	return datatable object with only selected columns
        Created:	Mon May 17 09:41:07 2021
        Author: 	
  */
  function columns($columns,$array=false)
  {
    if(!is_array($columns))	{
      $columns=[$columns];
    }
    $data=[];
    foreach($this->data as $row => $x) {
      foreach($columns as $column) {
        $data[$row][$column]=$x[$column];
      }
    }
    return $array==true ? $data : new datatable($data);
  } /* columns */

  
  /*    Title: 	select
        Purpose:	
        Created:	Tue Dec 01 12:48:03 2020
        Author: 	Adrie Dane
  */
  function select($idx,$field=NULL,$where=array())
  {
    if(!empty($where))	{
      $keys=array();
      foreach($idx as $i) {
        foreach($where as $k => $v) {
          if(!isset($this->data[$i][$k]) || 
             $this->data[$i][$k]!=$v)	{
            continue;
          }
        }
        $keys[]=$i;
      }
    } else {
      $keys=$idx;
    }

    $arr=array();
    if(is_null($field))	{
      foreach($keys as $k) {
        $arr[]=$this->data[$k];
      }
    } else {
      foreach($keys as $k) {
        $arr[$k]=$this->data[$k][$field];
      }
    }
    return $arr;
  } /* select */

  // PHP program to search for multiple
  // key=>value pairs in array
  //   key are in $keys
  //   value are the corresponding $A[key] values to look for
  // is devides this->data into two portions
  // 'absent': the rows not present in $A (based on $keys)
  // 'present': the rows present in $A (based on $keys)
  //   if $id is empty the key of each row is the corresponding key in $A
  //             else the key of each row is $A[$id] of the corresponding row $A

  function search($A,$keys=[])
  {
    echo "--->search";
    pre_r($keys);
    pre_r($A,'$A');
    if(empty($A))	{
      //      pre_r($A,'$A',true);
      exit("datatable->search empty datatable<br>". pre_r($A,'$A',true));
    }
    if(is_array($A))	{
      $A = new datatable($A);
    }
    // match all overlapping keys
    if(empty($keys))	{
      $keys=$this->column_names();
      //      pre_r($keys,'empty $keys');
    } elseif(!is_array($keys)) {
      $keys=[$keys];
      //      pre_r($keys,'!is_array $keys');
    }
    //    pre_r($keys,'$keys');

    $Akeys=$A->column_names();
    $keys=array_intersect($keys,$Akeys);
    if(empty($keys))	{
      pre_r($Akeys,'$Akeys');
      exit("datatable->search no overlapping keys");
    }

    // create two intermediate arrays with the same keys
    // $now they can be compared
    $this_columns=$this->columns($keys,true);
    $A_columns=$A->columns($keys,true);

    // pre_r($this_columns,'$this_columns');
    // pre_r($A_columns,'$A_columns');
    //exit('*****');
    // Create the result array
    $result = array();
    foreach($this_columns as $row => $x) {
      $matched=array_filter($A_columns,
                          function($a) use($x)
                          {
                            return $a==$x;
                          });
      // pre_r($matched,'$matched');
      if(empty($matched))	{
        $result['absent'][] = $this->data[$row];
      }elseif(count($matched)>1)	{
        ;
      }else	{
        $result['present'][key($matched)] = $this->data[$row];
      }
    }
    return $result;
  }
  
  public function update($idx=array(),$key_value=array(),$where=array()) 
  {
    if(empty($key_value))	{
      return;
    }
    if(empty($idx))	{
      $idx=array_keys($this->data);
    }
    foreach($idx as $i) {
      $update=TRUE;
      foreach($where as $k => $v) {
        if(!isset($this->data[$i][$k]) || 
           $this->data[$i][$k]!=$v)	{
          $update=FALSE;
          break;
        }
      }
      if($update==TRUE)	{
        foreach($key_value as $k => $v) {
          $this->data[$i][$k]=$v;
        }
      }
    }
  }

  // if intersect==TRUE: removes all fields not present in both tables
  function append($table2,$intersect=FALSE) 
  {
    if($intersect==TRUE)	{
      $data=array();
      $keys=array_intersect(array_keys($this->data[0]),
                            array_keys($table2->data[0]));
      $sets=array($this->data,$table2->data);

      foreach($sets as $set) {
        foreach($set as $x) {
          $y=array();
          foreach($keys as $key) {
            $y[$key]=$x[$key];
          }
          $data[]=$y;
        }
      }
      $this->data=$data;
      /*
        foreach($this->data as $x) {
        $y=array();
        foreach($keys as $key) {
        $y[$key]=$x[$key];
        }
        $data[]=$y;
        }
      */
      $this->data=$data;
    } else {
      $this->data=array_merge($this->data,$table2->data);
    }
    //    $this->nrows=count($this->data);
  }
  
  /*    Title: 	transpose
        Purpose:	returns transposed data as an array
        Created:	Tue Jun 22 17:52:43 2021
        Author: 	
  */
  function transpose()
  {
    $data=[];
    foreach($this->data as $row => $x) {
      foreach($x as $column => $value) {
        $data[$column][$row]=$value;
      }
    }
    return $data;
  } /* transpose */


  // $in_place = TRUE overwrites input
  // $in_place = FALSE clones input and applies filter
  function filter($fun,$in_place=FALSE,$reset_keys=TRUE) 
  {
    if($in_place)	{
      $a=&$this;
    } else {
      $a= clone $this;
    }

    $a->data=array_filter($this->data,$fun);
    if($reset_keys==TRUE)	{
      $a->data=array_values($a->data);
    }
    //    $a->nrows=count($a->data);
    
    return $a;
  }


  // adds "filtered" member containg references to
  //            filtered "data" member
  function set_filter($fun,$field="filtered") 
  {
    $this->filtered=array();

    foreach($this->data as &$x) {
      if($fun($x))	{
        $this->$field[]=$x;
      }
    }
  }


  function print($field=NULL,$pre=FALSE)
  {
    if($pre==TRUE)	{
      echo "<pre>";
    }
    if(is_null($field))	{
      print_r($this);
    } else {
      echo get_class($this) . " Object -> $field:\n";
      print_r($this->$field);
    }
    if($pre==TRUE)	{
      echo "</pre>";
    }
  } /* print */

  /*
    function group_by_old($key,$by_reference=TRUE,$field='data') 
    {
    $result = array();

    if(!is_array($key))	{
    $key=array($key);
    }

    $levels=array();
    foreach($key as $k) {
    $levels[]=array_unique(array_column($this->$field,$k));
    }
    
    }
  */


  function group_by($key,$by_reference=TRUE,$field='data') 
  {
    $result = array();

    if(!is_array($key))	{
      $key=array($key);
    }


    switch(count($key))	{
    case 1:	$key=$key[0];
      if($by_reference==TRUE)	{
        foreach ($this->$field as &$x) {
          $result[$x[$key]][] = $x;
        }
      } else {
        foreach ($this->$field as $x) {
          $result[$x[$key]][] = $x;
        }
      }
      break;
    case 2:	;
      if($by_reference==TRUE)	{
        foreach ($this->$field as &$x) {
          $result[$x[$key[0]]][$x[$key[1]]][] = $x;
        }
      } else {
        foreach ($this->$field as $x) {
          $result[$x[$key[0]]][$x[$key[1]]][] = $x;
        }
      }
      break;
    case 3:	;
      if($by_reference==TRUE)	{
        foreach ($this->$field as &$x) {
          $result[$x[$key[0]]][$x[$key[1]]][$x[$key[2]]][] = $x;
        }
      } else {
        foreach ($this->$field as $x) {
          $result[$x[$key[0]]][$x[$key[1]]][$x[$key[2]]][] = $x;
        }
      }
      break;


    default: echo "ERROR importtable group_by maximum 3 levels<br>";
      $result=NULL;
      
    }
    /*
      $result['data']=array();
      if($by_reference==TRUE)	{
      foreach ($this->$field as &$x) {
      $result['data'][]= $x;
      }
      } else {
      foreach ($this->$field as $x) {
      $result['data'][]= $x;
      }
      }
    */
    if($by_reference==TRUE)	{
      $ref_result = &$result;
      return $ref_result;
    } else {
      return $result;
    }
    
  }
  
  /*    Title: 	sort
        Purpose:	sort datatable by fields in ascending order
        Created:	Fri Feb 26 08:04:01 2021
        Author: 	Adrie Dane
  */
  function sort($keys)
  {
    usort($this->data,
          function($a, $b) use($keys){
            foreach($keys as $key) {
              $retval = $a[$key] <=> $b[$key];
              if($retval != 0)	{
                return $retval;
              }
            }
            return $retval;
          });
  } /* sort */


  /*    Title: 	rsort
        Purpose:	sort datatable by fields in descending order
        Created:	Fri Feb 26 08:04:01 2021
        Author: 	Adrie Dane
  */
  function rsort($keys)
  {
    usort($this->data,
          function($a, $b) use($keys){
            foreach($keys as $key) {
              $retval = $b[$key] <=> $a[$key];
              if($retval != 0)	{
                return $retval;
              }
            }
            return $retval;
          });
  } /* rsort */

  /*    Title: 	msort
        Purpose:	sort datatable by fields in mixed order depending on '-' sign
        $keys['field1','-field2','field3']
        sorts by field1 (ascending), 
        field2 (descending) and 
        field3 (ascending)
        Created:	Fri Feb 26 08:04:01 2021
        Author: 	Adrie Dane
  */
  function msort($keys)
  {
    usort($this->data,
          function($a, $b) use($keys){
            foreach($keys as $key) {
              if(substr($key,0,1) == '-')	{
                $k=substr($key,1);
                $retval = $b[$k] <=> $a[$k];
              } else {
                $retval = $a[$key] <=> $b[$key];
              }
              if($retval != 0)	{
                return $retval;
              }
            }
            return $retval;
          });
  } /* msort */


  function json($pretty=FALSE,$field="data")
  {
    if($pretty)	{
      $json= json_encode($this->$field,JSON_PRETTY_PRINT);
    } else {
      $json= json_encode($this->$field);
    }
    return $json;
  }


  function bootstraptable($id="table",$field="data")
  {
    $table = <<<_TABLE

<small>
<table id='$id'>
  <thead>
    <tr>
_TABLE;

    foreach(array_keys(reset($this->$field)) as $column) {
      //      $col=str_replace(' ','_',$column);
      $col=$column;
      $table .= "\n      <th data-field='$col'>$column</th>";
    }

    $table .= <<<_TABLE
    </tr>
  </thead>
</table>
</small>
<script>

_TABLE;

    $table .= '  var $table = $('."'#$id')\n";

    $table .= <<<_TABLE

  $(function() {
    var data = 
_TABLE;
    
    $table .= $this->json(TRUE)."\n    ";
    
    $table .= '$table.bootstrapTable({data: data})';

    $table .= <<<_TABLE

  })
</script>

_TABLE;

    return $table;
  }




  function csv($sep=",",$field="data")
  {
    $data=array(implode($sep,array_keys(reset($this->$field))));
    foreach($this->$field as $row) {
      array_push($data,implode($sep,array_values($row)));
    }
    return implode("\n",$data);
  }

  /*    Title: 	htmltable
        Purpose:	
        Created:	Tue Feb 02 17:31:11 2021
        Author: 	Adrie Dane
  */
  function htmltable($field="data")
  {
    $cls="table";
    $str='';
  
    $str.= "<table class='$cls'>\n";
    $str.= "  <thead>\n";
    $str.= "    <tr>\n";
    $str.= "      <th  scope='col'>";
    $str.= implode("</th>\n      <th  scope='col'>",array_keys(reset($this->$field)));
    $str.= "</th>\n";
    $str.= "    </tr>\n";
    $str.= "  </thead>\n";

    $str.= "  <tbody>\n";
    foreach($this->$field as $x) {
      $str.= "    <tr>\n";
      $str.= "      <td>";
      $str.= implode("</td>\n      <td>",$x);
      $str.= "</td>\n";
      $str.= "    </tr>\n";
    }
    $str.= "  </tbody>\n";


    $str.= "</table>\n";

    return $str;
  
  } /* htmltable */


  //('','0','Item 0','$0',''),
  function sql_old($table,$field='data')
  {
    $sql =  "INSERT INTO `$table` (".join(', ',array_keys(reset($this->$field))).") ";
    $vals=array();
    foreach($this->$field as $x) {
      $v=array();
      foreach($x as $key => $value) {
        if(is_numeric($value))
          array_push($v,"$value");
        else
          array_push($v,"'$value'");
      }
      array_push($vals,"(".join(', ',$v).")");
    }
    $sql .= "VALUES(\n".join(",\n",$vals).")";

    return($sql);
  }
  

  //('','0','Item 0','$0',''),
  function sql($table,$columns=array(),$field='data')
  {
    if(empty($columns))	{
      $columns=array_keys(reset($this->$field));
    }
    $sql =  "INSERT INTO `$table` (".join(', ',$columns).") ";
    $vals=array();
    foreach($this->$field as $x) {
      $v=array();
      foreach($columns as $column) {
        $value=$x[$column];
        if(is_numeric($value))
          array_push($v,"$value");
        else
          array_push($v,"'$value'");
      }
      array_push($vals,"(".join(', ',$v).")");
    }
    $sql .= "VALUES\n".join(",\n",$vals)."";

    return($sql);
  }

  /*    Title: 	sql_insert
        Purpose:	returns sql INSERT statement
        Created:	Mon May 17 11:35:54 2021
        Author: 	
  */
  function sql_insert($con=null,$table,$columns=array(),$table_columns=[])
  {
    if(!empty($keys))	{
      $A=$this->columns($keys);
    } else {
      $A=$this;
      $keys=$A->column_names();
    }
    
    if(empty($table_columns) && !is_null($con))	{
      $fieldinfo = $con->fieldinfo($table,$keys);
      $field_keys = array_intersect(array_column($fieldinfo,'name'),$keys);
      if(count($keys)!=count($field_keys))	{
        $A=$A->columns($field_keys);
        $keys=$field_keys;
      }
    }
  
    $fields = empty($table_columns) ? implode(", ",$keys) : implode(", ",$table_columns);

    $col_types=$A->gettype();
    $insert =[];
    foreach($A->data as $x) {
      $vals=[];
      foreach($x as $col => $value) {
        $vals[] = in_array($col_types[$col],['integer','double','boolean']) ?
                $value : "'$value'";
      }
      $insert[] = "(".implode(",",$vals).")";
    }

    $str = "INSERT INTO $table ($fields) VALUES ".implode(", ",$insert);
    pre_r($str,'query');

    if(!is_null($con))	{
      $con->query($str);
    }
  } /* sql_insert */





  
  /*    Title: 	excel
        Purpose:	writes data to current sheet
        Created:	Wed Apr 28 07:25:59 2021
        Author: 	Adrie Dane
  */
  function excel($xlsx=null,$field="data",$top_left='A1',$head=true)
  {
    if(is_null($xlsx))	{
      $xlsx=new Excelsheet();
    }
    /*
      if($head==true)	{
      $data=$this->$field;
      $keys=array_keys(reset($data));
      $data=array_unshift($keys,$data);
      }*/
    $data=$this->$field;
    if(isset($this->sheet) && !empty($this->sheet))	{
      $xlsx->set_sheet($this->sheet);
    }
    $xlsx->set_data($top_left,$data);
    //pre_r($data);
    //exit;
    return $xlsx;
  } /* excel */

  /*    Title: 	gettype
        Purpose:	Get the type of columns
        Arguments:$columns if non empty only gets types for specified columns
                  $prepared if true returns string for prepared statements
                            if any of the types is "NULL" returns NULL
        Created:	Mon May 17 08:21:59 2021
        Author: 	
  */
  function gettype($columns=[],$prepared=false)
  {
    $columns = empty($columns) ? array_keys(reset($this->data)) : $columns;
    //    pre_r($columns,'$columns');
    $types=[];
    $atom_types=['boolean','integer','string','double'];
    foreach($columns as $column) {
      $X=array_column($this->data,$column);
      foreach($X as $row => $x) {
        $type = gettype($x);
        if(in_array($type,$atom_types))	{
          if($type=='integer')	{
            for(	;	$row<count($X);	$row++)	{
              if(gettype($X[$row])=='double')	{
                $type='double';
                break;
              }
            }
          }
          $types[$column]=$type;
          break;
        }
      }
      if(!in_array($type,$atom_types))	{
        foreach($X as $x) {
          $type = gettype($x);
          if($type!="NULL")	{
            $types[$column]=$type;
            break;
          }
        }
        if($type=="NULL")	{
          $types[$column]=$type;
        }
      }
    }
    if($prepared==true)	{
      $str='';
      foreach($types as $type) {
        if(in_array($type,['boolean','integer']))	{
          $str .= 'i';
        } elseif($type=='double') {
          $str .= 'd';
        } elseif($type=='string') {
          $str .= 's';
        } elseif($type=='NULL') {
          return null;
        }else	{
          $str .= 'b';
        }
      }
      return $str;
    }
    return $types;
  } /* gettype */


  // START Iterator interface
  function rewind() {
    reset($this->data);
  }

  function current() {
    return current($this->data);
  }

  function key() {
    return key($this->data);
  }

  function next() {
    next($this->data);
  }

  function valid() {
    return key($this->data) !== null;
  }
  // END Iterator interface 

  // START ArrayAccess interface
  public function offsetSet($offset, $value) {
    if (is_null($offset)) {
      $this->data[] = $value;
    } else {
      $this->data[$offset] = $value;
    }
  }

  public function offsetExists($offset) {
    return isset($this->data[$offset]);
  }

  public function offsetUnset($offset) {
    unset($this->data[$offset]);
  }

  public function offsetGet($offset) {
    return isset($this->data[$offset]) ? $this->data[$offset] : null;
  }
  // END ArrayAccess interface //

  // START Countable interface
  public function count() {
    return count($this->data);
  }
  // END Countable interface

  public function __toString() {
    return get_class($this).": ".count($this->data)." elements";
  }
  

}
?>
