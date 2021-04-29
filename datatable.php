<?php
  //require_once 'vendor/autoload.php';



class datatable implements ArrayAccess, Iterator, Countable
{
  public static $rename = array(); # Static class variable.
  public static $numcol = array(); # Static class variable.
  // private $ix = array();

  public function __construct($data) 
  {
    $this->data=$data;
    //    $this->ncols=count($this->data[0]);
  }

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

/*    Title: 	excel
      Purpose:	writes data to current sheet
      Created:	Wed Apr 28 07:25:59 2021
      Author: 	Adrie Dane
*/
function excel($xlsx,$field="data",$top_left='A1',$head=true)
{
  if($head==true)	{
    $data=array_unshift(array_keys(reset($this->$field)),$this->field);
  }
  if(isset($this->sheet) && !empty($this->sheet))	{
    $xlsx->set_sheet($this->sheet);
  }
  $xlsx->set_data($top_left,$data);
} /* excel */


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