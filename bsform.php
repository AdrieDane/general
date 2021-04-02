<?php
/*    Title: 	control_str
      Purpose:	returns html code for a control
      Created:	Sun Mar 28 09:44:33 2021
      Author: 	Adrie Dane
*/
function control_str($type,$options=[],$err_warn=[])
{
  $opts=['name' => '',
	 'value' => '',
	 'tooltip' => '',
	 'width' => '',
	 'rows' => '',
	 'default' => '',
	 'choices' => '',
	 'array' => 1];

  if(!empty($options))	{
    $keys=array_intersect(array_keys($opts),array_keys($options));
    foreach($keys as $key) {
      $opts[$key]=$options[$key];
    }
  }

  //  pre_r($opts,"$type");
  foreach($opts as $key => $val) {
    if(empty($val))	{
      unset($opts[$key]);
    }
  }
  extract($opts);
  //echo $type;

  $error='';
  $warning='';
  if(!empty($err_warn))	{
    if(isset($err_warn['error']) && !empty($err_warn['error']))	{
      $error="<br><b><span class='text-danger'>".$err_warn['error']."</span></b>";
    }
    if(isset($err_warn['warning']) && !empty($err_warn['warning']))	{
      $warning="<br><b><span class='text-warning'>".$err_warn['warning']."</span></b>";
    }      
  }
  
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
    $str = "<textarea name='".$opts['name']."' rows='$rows' style='width:100%;'>";
    $str .= isset($value) ? $value : '';
    $str .= "</textarea>\n";
  } else {
    if($type=='date' && isset($value))	{
      $sh = new Excelsheet();
      if(is_array($value))	{
	foreach($value as &$v) {
	  $v = is_numeric($v) ? $sh->form_dateconvert($v) : $v;
	}
      }	elseif(is_numeric($value)) {
	$value=$sh->form_dateconvert($value);
      }
    }

    $str='';
    $width= $array==1 ? 100 : floor(98/$array);
    for(	$i=0;	$i<$array;	$i++)	{
      $str .= "<input type='$type'";
      $attributes=array_intersect(['name'],array_keys($opts));
      foreach($attributes as $attr) {
	$str .=  " $attr='".$opts[$attr]."'";
	unset($opts[$attr]);
      }
      if(isset($value))	{
	if(is_array($value))	{
	  if(isset($value[$i]) && !empty($value[$i]))	{
	    $str .=  is_numeric($value[$i]) ? 
	      " value=".$value[$i] : 
	      " value='".$value[$i]."'";
	  }
	} else {
	  $str .=  is_numeric($value) ? " value=".$value : " value='".$value."'";
	}
      }
    
      //  $str .= $type == "date" ? " placeholder='dd-mm-yyyy'>" : " style='width:$width%;'>\n";
      $str .= $type == "date" ? ">" : " style='width:$width%;'>\n";
      
    }
    // $str .=  ">";
  }

  if(isset($tooltip))	{
    // data-html='true'
    $str = "<span data-toggle='tooltip' data-placement='auto'  title='$tooltip' style='width:100%;'>\n".
      $str."</span>\n";
  } else {
    $str = "<span style='width:100%;'>\n".$str."</span>\n";
  }

  return $str.$error.$warning;
} /* control_str */

class Bsform extends bstable
{
  public function __construct($file='',$options=[]) 
{
 
  $sheet = [];
  if(isset($options['sheet']))	{
    $sheet =  ['sheet' => $options['sheet']];
    unset($options['sheet']);
  }
  $tbl = new importtable($file,
			 $sheet);

  $keys=array_keys($tbl->data);
  $keys=array_combine($keys,$keys);
  foreach($tbl->data as $k => &$x) {
    if(empty($x['key']))	{
      $x['td'] = 'th';
    } else {
      $x['td'] = 'td';
      $keys[$k] = $x['key'];
    }
    $x['value'] = '';
    $x['control']='';
    $x['warning'] = '';
    $x['error']='';
  }
  $tbl->data=array_combine($keys,$tbl->data);

  parent :: __construct( $tbl->data,['header' => false,
				     'small' => false,
				     'column_width' => [30, 70],
				     'hide_column' => ['key','tooltip','td','value','input',
						       'error','warning'],
				     'form' => true]);

  $this->set_controls('input','key','control'); 
  
}


/*    Title: 	set_controls
      Purpose:	
      Created:	Sat Mar 27 10:53:14 2021
      Author: 	Adrie Dane
*/
function set_controls($field, $name, $control='control')
{
  $input_types=["button", "checkbox", "color", "date", "datetime-local", "email", 
		"file", "hidden", "image", "month", "number", "password", "radio", 
		"range", "reset", "search", "submit", "tel", "text", "time", 
		"url", "week","textarea"];
  
  $arr=array();
  $hdrs=array();

  //  $this->_data = $this->data;

  
  foreach($this->data as &$x) {
    //    pre_r($x);
    
    // handle multiple input
    if(is_numeric($x[$field]))	{
      $type='textarea';
      $opts=['name' => $x[$name],
	     'rows' => $x[$field],
	     'tooltip' => $x['tooltip'],
	     'value' => $x['value']];
      $x[$control] = control_str($type,$opts,$x);
      
    } elseif(substr($x[$field],0,1)=='[')	{
      $parts=explode(']',substr($x[$field],1));
      list($type,$count)=$parts;
      $str = control_str($type,['name' => $x[$name]."[]",
				 'tooltip' => $x['tooltip'],
				'array' => $count,
				'value' => $x['value']],$x);
      $x[$control]=$str;
    } elseif(strpos($x[$field],'|')!==false) {
      $opts=explode('|',$x[$field]);
      $val = empty($x['value']) ? $opts[0] : $x['value'];
      $str = control_str('select',['name' => $x[$name],
				    'value' => $val,
				    'choices' => $opts,
				   'tooltip' => $x['tooltip']],$x);
      $x[$control] = $str;
    } elseif(in_array($x[$field],$input_types)) {
      $type=$x[$field];
      $x[$control] = control_str($type,['name' => $x[$name],
				      'tooltip' => $x['tooltip'],
					'value' => $x['value']],$x);
    }
  }
  ;
} /* set_controls */


/*    Title: 	update_data
      Purpose:	
      Created:	Mon Jan 18 17:27:30 2021
      Author: 	Adrie Dane
*/
function update_data($post)
{

  $keys=array_intersect(array_keys($post),
			array_keys($this->data));
  foreach($keys as $key) {
    $this->data[$key]['value']=$post[$key];
  }
  $this->set_controls('input','key'); 
} /* update_data */



}

?>