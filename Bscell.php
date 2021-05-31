<?php
class Bscell
{
/*    Title: 	__construct
      Purpose:	
      Created:	Sat May 01 08:42:51 2021
      Author: 	Adrie Dane
*/
function __construct($value,$options)
{
  $this->value=$value;
  foreach($options as $key => $opt) {
    $this->$key = $opt;
  }
  $this->warning=[];
} /* __construct */

/*    Title: 	set_type
      Purpose:	
      Created:	Sat May 01 09:20:49 2021
      Author: 	Adrie Dane
*/
function set_controltype($control_type,$column=null)
{
  //pre_r($control_type,'$control_type');
  
  if(!is_array($control_type))	{
    if($control_type=='label')	{
      $this->td="th scope='col'";
      $this->td_class[] = "font-weight-bold";
      return;
    }
    $this->type = $control_type;
  } else {
    $this->type = key($control_type);
    extract(current($control_type));
    if($this->type!='select')	{
      error('Bscell array control_type is only possible for select');
    }
  }
  
  if($this->type=='select')	{
    $this->choices = isset($choices) && !empty($choices) ?
      $choices :
      array_unique($column);

    if(isset($append))	{
      if(!is_array($append))	{
	$append=[$append];
      }
      $this->choices = array_merge($this->choices,$append);	
    }
    if(isset($prepend))	{
      if(!is_array($prepend))	{
	$prepend=[$prepend];
      }
      $this->choices = array_merge($prepend,$this->choices);	
    }
    if(count($this->choices)<2)	{
      unset($this->choices);
      unset($this->type);
    }
  }
} /* set_type */

/*    Title: 	html
      Purpose:	
      Created:	Sat May 01 10:22:46 2021
      Author: 	Adrie Dane
*/
function html($key,$row='',$options=[])
{
  if(isset($this->hideoutput))	{
    return '';
  }
  $opts=useroptions(['head' => false,
		     'data_only' => false],$options);
  extract($opts);
  
  if($head==true)	{
    return "<th data-sortable='true' scope='col'>$key</th>";
  }

  
  $ctrl_class = isset($this->ctrl_class) && !empty($this->ctrl_class) ?
    " class='".implode(" ",array_unique($this->ctrl_class))."'" : "";


  $key=str_replace(' ','_',$key);
  $str = '';
  if(!isset($this->type) || $data_only==true)	{
    $str .= $this->value;
  } elseif($this->type=='select')	{
    // fix sort by comment $value
    $str = "<!---".$this->value."--->";
    $str .= "<select name='".$key."[".$row.
      "]'$ctrl_class>";
    foreach($this->choices as $value) {
      $str .= "<option value='$value'";
      $str .= $value==$this->value ?
	" selected>" : ">";
      $str .= $value."</option>";
    }
    $str .= "/select>";
  } else {
    $str .= "<input type='".$this->type.
      "' name='".$key."[".$row.
      "]' value='".$this->value."'$ctrl_class>";
  }

  $td = isset($this->td) ? $this->td : 'td';

  //  pre_r($this,"cell: $row-$key");
  
  if(isset($this->td_class) && !empty($this->td_class))	{
    $td .= " class='".implode(" ",array_unique($this->td_class))."'";
  }

  return   "<".$td.">$str</".substr($td,0,2).">";
    
  
} /* html */

/*    Title: 	set_validation
      Purpose:	
      Created:	Mon May 03 09:53:55 2021
      Author: 	Adrie Dane
*/
function set_validation($arr)
{
  //pre_r($arr,'**validation**<br>');
  //pre_r($this,'cell');
  if(isset($arr['oldvalue']) && $arr['value'] != $this->value)	{
    $this->oldvalue=$arr['oldvalue'];
    $this->value=$arr['value'];
    $this->warning[$arr['warning']][]=$arr['type'];
    $this->set_color();
  }
} /* set_validation */

/*    Title: 	set_color
      Purpose:	
      Created:	Wed May 05 11:42:44 2021
      Author: 	Adrie Dane
*/
function set_color($color='')
{
  if(empty($color))	{
    if(!isset($this->warning))	{
      return '';
    }
    $col = in_array('error',$this->warning) ?
      'danger' :
      in_array('warning',$this->warning) ?
      'warning' :
      'success';
    $this->td_class[] = "text-$col";
    $this->td_class[] = "font-weight-bold";
    $this->ctrl_class[] = "text-$col";
    $this->ctrl_class[] = "font-weight-bold";
  } else {
    $this->td_class[] = "$color";
    if(substr($color,0,4)=='text')	{
      $this->ctrl_class[] = "$color";
      $this->ctrl_class[] = "font-weight-bold";
    }
  }
} /* set_color */


}


trait Tablecells
{
/*    Title: 	init_cells
      Purpose:	
      Created:	Sat May 01 08:45:08 2021
      Author: 	Adrie Dane
*/
function init_cells()
{
  $opts=['hide_column' => [],
         'show_column' => [],
	 'column_width' => []];
  
  $opts=useroptions($opts,$this->options);

  extract($opts);
  $cols = array_keys(reset($this->data));
  if(!empty($hide_column))	{
    $cols = array_diff($cols,$hide_column);
  }
  if(!empty($show_column))	{
    $cols = array_intersect($cols,$show_column);
  }
  
  $this->cells=array();
  foreach($this->data as $row => $values) {
    foreach($values as $key => $value) {
      $cell_options=[];
      if(!in_array($key,$cols))	{
	$cell_options['hideoutput']=true;
      }
      if(in_array($key,array_keys($column_width)))	{
	$cell_options['width']=$column_width[$key];
      }
      $this->cells[$row][$key]=new Bscell($value,$cell_options);
    }
  }
  /*  pre_r($opts,'$opts');
  pre_r($this->cells,'$this->cells');
  exit; */
} /* init_cells */

/*    Title: 	set_column_options
 Purpose:	
 Created:	Sun May 02 11:53:27 2021
 Author: 	Adrie Dane
*/
function set_column_options($options=[])
{
  if(empty($options))	{
    $options = isset($this->options) ? $this->options : [];
  }
  if(empty($options))	{
    return;
  }

  $opt=array_filter(useroptions(['hide_column' => [],
				  'show_column' => []],$options));

  if(count($opt)>1)	{
    exit("Bscell option must either be 'hide_column' or 'show_column' not both in set_column_options");
  }

  $key=key($opt);
  $vals=current($opt);
  if(!is_array($vals))	{
    $vals=[$vals];
  }
  $columns = $key=='hide_column' ?
    array_diff($this->hdrs,$vals) :
    $this->hdrs;

  foreach($this->cells as $row => &$cell) {
    foreach($this->hdrs as $col) {
      if(in_array($col,$columns))	{
	if(isset($cell[$col]->hideoutput))	{
	  unset($cell[$col]->hideoutput);
	}
      } else {
	$cell[$col]->hideoutput=true;
      }
    }
  }
  unset($cell);

} /* set_column_options */



/*    Title: 	set_inputs
      Purpose:	
      Created:	Sat May 01 09:00:06 2021
      Author: 	Adrie Dane
*/
function set_inputs($column_options=[])
{
  if(!empty($column_options) || !isset($this->options['controls']))	{
    $this->options['controls']=$column_options;
  }
  foreach($this->options['controls'] as $column => $control_type) {
    foreach($this->cells as $row => &$cells) {
      $cells[$column]->set_controltype($control_type,array_column($this->data,$column));
    }
  }
  /*
  $this->_data=[];
  foreach($this->cells as $row => $cells) {
    foreach($cells as $key => $cell) {
      $this->_data[$row][$key]=$cell->html($key,$row);
    }
  }
  */
} /* set_inputs */


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
      foreach($values as $row => $value) {
	$this->data[$row][$hdrs[$key]]=$value;
      }
    }
  }
  $this->init_cells();
  $this->set_inputs();
  $this->validate_all();
  
  if(isset($post['data_only']))	{
    $this->data_only=true;
  } elseif(isset($post['data_edit']))	{
    $this->data_only=false;
  }

  //  $this->_data=[];
  //  pre_r($this->data,'updated $data');
  //  exit;
  
  return $this->data;
} /* update_data */

/*    Title: 	htmltable
      Purpose:	
      Created:	Tue Feb 02 17:31:11 2021
      Author: 	Adrie Dane
*/
function html()
{
  // return pre_r(array_column($this->cells,'Sample Name'),'$this',true);
    //    pre_r($this->data,'data----');
    //    exit;
  extract($this->options);

  $str='';

  if(isset($this->validate) && !empty($this->validate))	{
    $html=[];
    foreach($this->validate as &$val) {
      if(isset($val['html']))	{
	$html=array_merge($html,$val['html']);
	unset($val['html']);
      }
    }
    if(!empty($html))	{
      $str .= "<h2>Data validation results</h2>";
      $str .= "<span class='text-danger'>Errors</span> and/or <span class='text-warning'>Warnings</span> were found \n".
	"and/or <span class='text-success'>Automatic Corrections</span> were carried out.<br>\n".
	"Pressing the top left Data button(s) will accept the automatic and manual corrections made in the table below. \n".
	"The errors must be fixed. In some cses that can be done in the table. In other cases the corrected data needs to be reloaded.";
      $str .= "\n<ul>\n<li>".implode("\n<li>",array_filter($html))."\n</ul>\n<hr>";
    }
  }


  // No create the table
  $str .= $small==true ? "<small>\n" : "";
  $str .= "<div id='toolbar'>";
  if($this->data_only==true)	{
    $str .= "<input type='submit' value='Edit Data' name='data_edit' class='btn btn-secondary btn-sm'><br><br>\n";
  }
  else	{
    $str .= "<input type='submit' value='Data Only' name='data_only' class='btn btn-secondary btn-sm'>\n";
    $str .= "<input type='submit' value='Data Update' name='data_update' class='btn btn-secondary btn-sm'><br><br>\n";
  }
  $str .= "</div>";
  
  $str .= "<table data-toggle='table' data-toolbar='#toolbar'  data-search='true'  id='$id' class='table $cls' 
  data-show-toggle='true' data-show-columns='true'  data-silent-sort='false'
  data-show-fullscreen='true' 
  data-show-pagination-switch='true'
  data-show-toggle='true'>\n";
  //  data-sort-class='table-active'
  //  data-pagination='true'
  //  data-show-export='true'
  // return pre_r($this->cells,'$this->cells');

  //  pre_r($this->cells,'$cell');
  //  exit;
  if($header==true)	{
    $str.= "  <thead>\n";
    $str.= "    <tr>\n";
    $str.= "      ";
    $first_row = reset($this->cells);
    foreach($first_row as $key => $cell) {
      $str .= $cell->html($key,key($first_row),['head' => true]);
    }
    $str.= "    </tr>\n";
    $str.= "  </thead>\n";
  }
  $str.= "  <tbody>\n";
  foreach($this->cells as $row => $x) {
    $str.= "    <tr>\n";
    foreach($x as $key => $cell) {
      $str .= $cell->html($key,$row,['data_only' => $this->data_only]);
    }
    $str.= "    </tr>\n";
  }
  $str.= "  </tbody>\n";
  $str.= "</table>\n";


  $str .= $small==true ? "</small>\n" : "";

  
  return $str;
}


}
?>
