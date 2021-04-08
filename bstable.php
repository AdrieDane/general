<?php
  // require_once 'vendor/autoload.php';



/*
 html bootstrap table interface
 $options
    - 'small' [true|false]
        use small fonts
    - 'header' true|false
        skip header row
    - 'hide_column' array('key','tooltip','td','required')
*/
class bstable extends datatable
{
  
  public function __construct($data,$options=[]) 
  {
    $opts=['small' => true,
	   'header' => true,
	   'hide_column' => [],
	   'show_column' => [],
	   'id' => 'table',
	   'cls' => 'table-sm table-hover',
	   'column_width' => [],
	   'form' => false];

    foreach($options as $key => $value) {
      $opts[$key]=$value;
    }
    
    parent::__construct($data);
    //    $this->ncols=count($this->data[0]);
    $this->_data=array();

    if(!empty($data))	{
      $hdrs=array_keys(array_values($data)[0]);
      $this->hdrs=array_combine(str_replace(' ','_',$hdrs),$hdrs);
    } else {
      $this->hdrs=array();
    }
    $this->options=$opts;
  }

/*    Title: 	update_data
      Purpose:	
      Created:	Mon Jan 18 17:27:30 2021
      Author: 	Adrie Dane
*/
function update_data($post)
{
  $hdrs=$this->hdrs;
  $data_keys=array_keys($hdrs);
  foreach($post as $key => $values) {
    if(in_array($key,$data_keys))	{
      $i=0;
      foreach($this->data as &$x) {
	$x[$hdrs[$key]]=$values[$i];
	$i++;
      }
    }
  }

  $this->_data=[];
  return $this->data;
} /* update_data */



  /*    Title: 	set_inputs
      Purpose:	bootstrap form for editting
      Created:	Mon Jan 18 15:46:49 2021
      Author: 	Adrie Dane
*/
function set_inputs($types=array())
  {
    $arr=array();
    $hdrs=array();

    $this->_data = $this->data;
 
    foreach($types as $field => &$type) {
      // create dropdown
      if($type=='select')	{
	$vals=array_unique(array_column($this->_data,$field));
	$type=$vals;
      }
    }

    foreach($this->_data as &$x) {
      // do label columns first
      foreach($types as $field => &$type) {
	if(!is_array($type) && $type=='label')	{
	  $x[$field]="<b>".$x[$field]."</b>";
	  $hdrs[]=$field;
	}
      }
      foreach($types as $field => &$type) {
	if(is_array($type))	{
	  $str = "<select name='".$field."[]'>";
	  foreach($type as $value) {
	    $str .= "<option value='$value'";
	    $str .= $value==$x[$field] ?
	      " selected>" : ">";
	    $str .= "$value</option>";
	  }
	  $x[$field] = $str;
	  $hdrs[]=$field;
	}
	elseif($type!='label')	{
	  $value=$x[$field];
	  $x[$field] = "<input type='$type' name='".$field."[]' value='$value'></input>";
	  $hdrs[]=$field;
	}
      }
    }
    $keys=str_replace(' ','_',$hdrs);
    $this->hdrs=array_combine($keys,$hdrs);
  } /* set_inputs */

  
/*    Title: 	htmltable
      Purpose:	
      Created:	Tue Feb 02 17:31:11 2021
      Author: 	Adrie Dane
*/
function html($field="data")
{
  $field=empty($this->_data) ? "data" : "_data";
  // pre_r($this->options,'opts');
  
  extract($this->options);

  $str='';
  $str .= $small==true ? "<small>\n" : "";
  $str .= "<table id='$id' class='table $cls'>\n";

  $cols = array_keys(reset($this->$field));

  if(!empty($hide_column))	{
    $cols = array_diff($cols,$hide_column);
  }

  if(!empty($show_column))	{
    $cols = array_intersect($cols,$show_column);
  }

  if($header==true)	{
    $str.= "  <thead>\n";
    $str.= "    <tr>\n";
    $str.= "      <th  scope='col'>";
    //    $str.= implode("</th>\n      <th  scope='col'>",array_keys(reset($this->$field)));
    if(!empty($width))	{
      ;
    }
    $str.= implode("</th>\n      <th  scope='col'>",$cols);
    $str.= "</th>\n";
    $str.= "    </tr>\n";
    $str.= "  </thead>\n";
  }

  $str.= "  <tbody>\n";
  foreach($this->$field as $x) {
    /*    $y=$x;
    if(!empty($hide_column))	{
      foreach($x as $key => $value) {
	if(!in_array($key, $hide_column))	{
	  $y[$key]=$value;
	}
      }
      } */
    $y=[];
    foreach($cols as $col) {
      $y[$col]=$x[$col];
    }
    $td = isset($x['td']) ? $x['td'] : 'td';
    $str.= "    <tr>\n";
    if(isset($column_width) && !empty($column_width))	{
      $i = 0;
      foreach($y as $key => $value) {
	$str.= "      <$td style='width:".$column_width[$i]."%;'>$value</$td>\n";
	$i++;
      }
    } else {
      $str.= "      <$td>";
      $str.= implode("</$td>\n      <$td>",$y);
      $str.= "</$td>\n";
    }
    
    $str.= "    </tr>\n";
  }
  $str.= "  </tbody>\n";


  $str.= "</table>\n";
  $str .= $small==true ? "</small>\n" : "";

  return $str;
  
} /* htmltable */

/*

  function bootstraptable($id="table",$field="data")
  {

    $field=empty($this->_data) ? "data" : "_data";


    $table = <<<_TABLE

<small>
<table id='$id' class="table table-sm">
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
</small>
<script>

_TABLE;

    $table .= '  var $table = $('."'#$id')\n";

    $table .= <<<_TABLE

  $(function() {
    var data = 
_TABLE;
    
    $table .= $this->json(TRUE,$field)."\n    ";
    
    $table .= '$table.bootstrapTable({data: data})';

    $table .= <<<_TABLE

  })
</script>

_TABLE;

    return $table;
  }
*/
}
?>