<?php
require_once 'vendor/autoload.php';

/*    Title: 	control_str
      Purpose:	returns html code for a control
      Created:	Sun Mar 28 09:44:33 2021
      Author: 	Adrie Dane
*/
function control_str($type,$options=[])
{
  $opts=['name' => '',
	 'value' => '',
	 'tooltip' => '',
	 'width' => '',
	 'rows' => '',
	 'default' => '',
	 'choices' => '',
	 'array' => 1];

  foreach($options as $key => $val) {
    $opts[$key]=$val;
  }
  //  pre_r($opts,"$type");
  foreach($opts as $key => $val) {
    if(empty($val))	{
      unset($opts[$key]);
    }
  }
  extract($opts);
  //echo $type;
  
  
  if($type=='select' && isset($opts['choices']))	{
    //  extract($opts);
    $str = "<select name='$name'>\n";
    foreach($choices as $choice) {
      if(empty($choice))	{
	continue;
      }
	$str .= "<option value='$choice'";
	$str .= isset($value) && $choice==$value ?
	  " selected>" : ">";
	$str .= "$choice</option>\n";
    }
    $str .= "</select>\n";
  } elseif($type=='textarea')	{
    $str = "<textarea name='".$opts['name']."' rows='$rows' style='width:100%;'></textarea>\n";
  } else {
    $str='';
    $width= $array==1 ? 100 : floor(98/$array);
    for(	$i=0;	$i<$array;	$i++)	{
      $str .= "<input type='$type'";
      $attributes=array_intersect(['name'],array_keys($opts));
      foreach($attributes as $attr) {
	$str .=  " $attr='".$opts[$attr]."'";
	unset($opts[$attr]);
      }
      if(isset($value) && !empty($value))	{
	$str .=  is_numeric($value) ? " value=$value" : " value='$value'";
      }
    
      $str .= $type == "date" ? ">" : " style='width:$width%;'>\n";

      
    }
    // $str .=  ">";
    
  }
  

  if(isset($tooltip))	{
    // data-html='true'
    return "<span data-toggle='tooltip' data-placement='auto'  title='$tooltip' style='width:100%;'>\n".
      $str."</span>\n";
  } else {
    return "<span style='width:100%;'>\n".
      $str."</span>\n";
  }
} /* control_str */


/*
 html bootstrap table interface
 $options
    - 'small' [true|false]
        use small fonts
    - 'header' true|false
        skip header row
    - 'hide_column' array('key','tooltip','td')
*/
class bstable extends datatable
{
  
  public function __construct($data,$options=[]) 
  {
    $opts=['small' => true,
	   'header' => true,
	   'hide_column' => ['key','tooltip','td'],
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

/*    Title: 	set_controls
      Purpose:	
      Created:	Sat Mar 27 10:53:14 2021
      Author: 	Adrie Dane
*/
function set_controls($field, $name, $tooltip='')
{
  $input_types=["button", "checkbox", "color", "date", "datetime-local", "email", 
		"file", "hidden", "image", "month", "number", "password", "radio", 
		"range", "reset", "search", "submit", "tel", "text", "time", 
		"url", "week","textarea"];
  
  $arr=array();
  $hdrs=array();

  $this->_data = $this->data;

  
  foreach($this->_data as &$x) {
    //    pre_r($x);
    
    // handle multiple input
    if(is_numeric($x[$field]))	{
      $type='textarea';
      $opts=['name' => $x[$name],
	     'rows' => $x[$field],
	     'tooltip' => $x['tooltip']];
      //      pre_r($opts,$type);
      //      $x[$field] = control_str($type,$opts)."<br>";
      $x[$field] = control_str($type,$opts);
      
    } elseif(substr($x[$field],0,1)=='[')	{
      $parts=explode(']',substr($x[$field],1));
      list($type,$count)=$parts;
      $str = control_str($type,['name' => $x[$name]."[]",
				 'tooltip' => $x['tooltip'],
				 'array' => $count]);
      /*      $str='';
      for(	$i=0;	$i<$count;	$i++)	{
	//	$str .= control_str($type,['name' => $x[$name]."[]",
	//				   'tooltip' => $x['tooltip']])."<br>";
	$str .= control_str($type,['name' => $x[$name]."[]",
				   'tooltip' => $x['tooltip']]);
				   }*/
      $x[$field]=$str;
    } elseif(strpos($x[$field],'|')!==false) {
      $opts=explode('|',$x[$field]);
      $str = control_str('select',['name' => $x[$name],
				    'value' => $opts[0],
				    'choices' => $opts,
				    'tooltip' => $x['tooltip']])."<br>";
      $x[$field] = $str;
    } elseif(in_array($x[$field],$input_types)) {
      $type=$x[$field];
      $x[$field] = control_str($type,['name' => $x[$name],
				      'tooltip' => $x['tooltip']]);
    }
  }
  ;
} /* set_controls */




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

  $cols = array_diff(array_keys(reset($this->$field)),$hide_column);

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