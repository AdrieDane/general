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
class Bsdata extends datatable
{
  
  public function __construct($data,$options=[]) 
  {
    $opts=['small' => true,
	   'header' => true,
	   'hide_column' => [],
	   'show_column' => [],
	   'id' => 'table',
	   'cls' => 'table-sm table-hover',
	   'column_width' => []];

    foreach($options as $key => $value) {
      $opts[$key]=$value;
    }
    
    parent::__construct($data);
    //    $this->ncols=count($this->data[0]);
    $this->_data=array();
    if(!empty($data))	{
      $hdrs=array_keys(array_values($data)[0]);
      $this->hdrs=array_combine(str_replace(' ','_',$hdrs),$hdrs);
      //array_keys(reset($data))
    } else {
      $this->hdrs=array();
    }
    $this->cell_style=[];
    $this->options=$opts;
  }

/*    Title: 	set_style
      Purpose:	
      Created:	Wed Apr 21 10:24:17 2021
      Author: 	Adrie Dane
*/
function set_style($rkey,$ckey,$key,$value=true)
{
  $this->cell_style[$rkey][$ckey][$key]=$value;
} /* set_class */

/*    Title: 	cell_class
      Purpose:	
      Created:	Wed Apr 21 10:43:42 2021
      Author: 	Adrie Dane
*/
function cell_class($rkey,$ckey)
{
  if(!isset($this->cell_style[$rkey][$ckey]))	{
    return '';
  }
  $str= 
  $cls=[];
  foreach($this->cell_style[$rkey][$ckey] as $k => $v) {
    if($k=='error')	{
      $cls[]= $v==true ? 'bg-danger' : 'table-danger';
    } elseif($k=='warning')	{
      $cls[]= $v==true ? 'bg-warning' : 'table-warning';
    } elseif($k=='auto')	{
      $cls[]= $v==true ? 'bg-primary' : 'table-primary';
    }
  }
  return empty($cls) ? "" : " class='".implode(" ",$cls)."'";
} /* cell_class */

/*    Title: 	get_cell_control
      Purpose:	
      Created:	Thu Apr 22 17:04:43 2021
      Author: 	Adrie Dane
*/
function get_cell_empty($rkey,$ckey)
{

  if(!isset($this->cell_style[$rkey][$ckey]['empty']))	{
    return false;
  }
  
  return "<input type='hidden' name='".str_replace(' ','_',$ckey).
	    "[]' value='".$this->data[$rkey][$ckey]."'>";
} /* get_cell_control */



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
      /*foreach($values as $row => $v) {
	$this->data[$row][$hdrs[$key]]=$v;
	}*/
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

/*    Title: 	array_set_input
      Purpose:	
      Created:	Thu Apr 22 15:47:38 2021
      Author: 	Adrie Dane
*/
function array_set_input($arr)
{
  ;
} /* array_set_input */



  /*    Title: 	set_inputs
      Purpose:	bootstrap form for editting
      Created:	Mon Jan 18 15:46:49 2021
      Author: 	Adrie Dane
*/
function set_inputs($types=array())
  {
    $arr=array();
    $hdrs=array();

    $this->options['column_type']=$types;

    // create a copy in _data
    $this->_data = $this->data;

    $ctrl_choices=array();
    // create dropdown if type is select
    foreach($types as $field => $type) {
      $append=[];
      $prepend=[];
      if(is_array($type) && count($type)==1)	{
	$ctrl_opts=$type;
	$type=key($type);
	$types[$field]=$type;
	extract($ctrl_opts[$type]);
	if(isset($choices))	{
	  $ctrl_choices[$field]=$choices;
	  continue;
	}
      }
      if($type=='select')	{
	$vals=array_unique(array_column($this->_data,$field));
	$ctrl_choices[$field]=array_merge($prepend,$vals,$append);
	pre_r([$field,$type,$ctrl_choices[$field]]);
      }
      
    }
      
    foreach($this->_data as $row => &$x) {
      foreach($types as $key => $type) {
	if($type=='select')	{
	  $str = "<select name='".$key."[]'>";
	  foreach($ctrl_choices[$key] as $value) {
	    $str .= "<option value='$value'";
	    $str .= $value==$x[$key] ?
	      " selected>" : ">";
	    $str .= "$value</option>";
	  }
	  $x[$key] = $str."</select>";
	} elseif ($type=='text')	{
	  $value=$x[$key];
	  $x[$key] = "<input type='$type' name='".str_replace(' ','_',$key).
	    "[]' value='$value'>";
	}
      }
    }
    $hdrs=array_keys($types);
    $this->hdrs=array_combine(str_replace(' ','_',$hdrs),$hdrs);

    //   echo "count: ".count($this->_data);
    


    /*


      // do label columns first
      foreach($types as $field => &$type) {
	if(!is_array($type) && $type=='label')	{
	  $x[$field]="<b>".$x[$field]."</b>";
	  $hdrs[]=$field;
	//      }
	//      foreach($types as $field => &$type) {
	} elseif(is_array($type))	{
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
    pre_r($this->hdrs,'hdrs');

    for(	$i=0;	$i<count($this->_data);	$i++)	{
      echo "<br><br>$i<br><br>";
      
    pre_r($this->_data[$i],"_data[$i]");
		  
		}

    exit("<br>Now I'm here<br>");
    */

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
    $str.= "      <th scope='col'>";
    //    $str.= implode("</th>\n      <th  scope='col'>",array_keys(reset($this->$field)));
    if(!empty($width))	{
      ;
    }
    $str.= implode("</th>\n      <th scope='col'>",$cols);
    $str.= "</th>\n";
    $str.= "    </tr>\n";
    $str.= "  </thead>\n";
  }

  $str.= "  <tbody>\n";
  foreach($this->$field as $row => $x) {
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
 
    if(true) {
      $i = 0;
      foreach($y as $key => $value) {
	$style_w = isset($column_width[$i]) && !empty($column_width[$i]) ?
	  " style='width:".$column_width[$i]."%;'" : "";
	$td = isset($column_type[$key]) && $column_type[$key]=='label' ?
	  "th" : "td";
	$scope = $td=='th' ? " scope='col'" : "";
	$cls=$this->cell_class($row,$key);
	$val = $this->get_cell_empty($row,$key);
	$val = $val===false ? $value :$val;
	$str.= "      <$td$scope$cls$style_w>$val</$td>\n";
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
 */
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
//
}
?>