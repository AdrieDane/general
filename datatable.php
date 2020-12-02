<?php
require_once 'vendor/autoload.php';

class datatable
{
  public static $rename = array(); # Static class variable.
  public static $numcol = array(); # Static class variable.

  public function __construct($data,$con=NULL) 
  {
    $this->data=$data;

    $this->nrows=count($this->data);
    $this->ncols=count($this->data[0]);
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
    $this->nrows=count($this->data);
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
    if($reset_keys)	{
      $a->data=array_values($a->data);
    }
    $a->nrows=count($a->data);
    
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
  


  function json($pretty=FALSE,$field="data")
  {
    if($pretty)	{
      $json= json_encode($this->$field,JSON_PRETTY_PRINT);
    } else {
      $json= json_encode($this->$field);
    }
    return $json;
  }

  function bootstraptable($id="table")
  {
    $table = <<<_TABLE

<table id='$id'>
  <thead>
    <tr>
_TABLE;

    foreach(array_keys($this->data[0]) as $column) {
      //      $col=str_replace(' ','_',$column);
      $col=$column;
      $table .= "\n      <th data-field='$col'>$column</th>";
    }

    $table .= <<<_TABLE
    </tr>
  </thead>
</table>

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
    $data=array(implode($sep,array_keys($this->$field[0])));
    foreach($this->$field as $row) {
      array_push($data,implode($sep,array_values($row)));
    }
    return implode("\n",$data);
  }

  //('','0','Item 0','$0',''),
  function sql_old($table,$field='data')
  {
    $sql =  "INSERT INTO `$table` (".join(', ',array_keys($this->$field[0])).") ";
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
      $columns=array_keys($this->$field[0]);
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
  


}

?>