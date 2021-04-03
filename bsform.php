<?php
/*    Title: 	control_str
      Purpose:	returns html code for a control
      Created:	Sun Mar 28 09:44:33 2021
      Author: 	Adrie Dane
*/
function control_str($type,$opts=[])
{
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

  if(isset($opts['error']) && !empty($opts['error']))	{
    $error="<br><b><span class='text-danger'>".$opts['error']."</span></b>";
  }
  if(isset($opts['warning']) && !empty($opts['warning']))	{
    $warning="<br><b><span class='text-warning'>".$opts['warning']."</span></b>";
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
  use Optionsarray;
  public function __construct($file='',$options=[]) 
{
  $opts=['header' => false,
	 'small' => false,
	 'column_width' => [30, 70],
	 'hide_column' => ['key','tooltip','td','value','input',
			   'error','warning'],
	 'form' => true,
	 'sheet' => '',
	 'skipemptyrows' => false];

  if(!is_array($options) && !empty($options))	{
    $opts['sheet']=$options;
  } else {
    $opts=$this->useroptions($opts,$options);
  }
  
  $tbl = new importtable($file,$opts);
  //  pre_r($tbl,'$tbl');
  
  $keys=array_keys($tbl->data);
  $keys=array_combine($keys,$keys);
  foreach($tbl->data as $k => &$x) {
    if(empty($x['key']))	{
      $x['td'] = 'th';
    } else {
      $x['td'] = 'td';
      $keys[$k] = $x['key'];
    }
  }
  $tbl->data=array_combine($keys,$tbl->data);

  parent :: __construct( $tbl->data,$opts);

  $this->set_controls(); 
  
}

/*    Title: 	set_controls
 Purpose:	
 Created:	Sat Apr 03 16:26:46 2021
 Author: 	Adrie Dane
*/
function set_controls()
{
  foreach($this->data as &$x) {
    $opts=['width' => '',
           'rows' => '',
           'default' => '',
           'choices' => '',
           'array' => 1];

    $type=$x['input'];
    foreach(['value','error','value','tooltip'] as $field) {
      $opts[$field]=$x[$field];
    }
    $opts['name']=$x['key'];

    if(is_numeric($x['input'])) {
      $type='textarea';
      $opts['rows']=$x['input'];
    } elseif(substr($x['input'],0,1)=='[')	{
      $parts=explode(']',substr($x['input'],1));
      list($type,$count)=$parts;
      $opts['name'] .= "[]";
      $opts['array']= $count;
    } elseif(strpos($x['input'],'|')!==false) {
      $type='select';
      $opts['choices']=explode('|',$x['input']);
      $opts['value'] = empty($x['value']) ? $opts[0] : $x['value'];
    }
    $x['control']=control_str($type,$opts);
  
  }
} /* set_controls */



/*    Title: 	set_controls
      Purpose:	
      Created:	Sat Mar 27 10:53:14 2021
      Author: 	Adrie Dane
*
function set_controls()
{
  $input_types=["button", "checkbox", "color", "date", "datetime-local", "email", 
		"file", "hidden", "image", "month", "number", "password", "radio", 
		"range", "reset", "search", "submit", "tel", "text", "time", 
		"url", "week","textarea"];
  
  $arr=array();
  $hdrs=array();

  //  $this->_data = $this->data;
  //  pre_r($this->data,'$this->data');
  
  
  foreach($this->data as &$x) {
    //    pre_r($x);
    
    // handle multiple input
    if(is_numeric($x['input']))	{
      $type='textarea';
      $opts=['name' => $x['key'],
	     'rows' => $x['input'],
	     'tooltip' => $x['tooltip'],
	     'value' => $x['value']];
      $x['control'] = control_str($type,$opts,$x);
      
    } elseif(substr($x['input'],0,1)=='[')	{
      $parts=explode(']',substr($x['input'],1));
      list($type,$count)=$parts;
      $str = control_str($type,['name' => $x['key']."[]",
				 'tooltip' => $x['tooltip'],
				'array' => $count,
				'value' => $x['value']],$x);
      $x['control']=$str;
    } elseif(strpos($x['input'],'|')!==false) {
      $opts=explode('|',$x['input']);
      $val = empty($x['value']) ? $opts[0] : $x['value'];
      $str = control_str('select',['name' => $x['key'],
				    'value' => $val,
				    'choices' => $opts,
				   'tooltip' => $x['tooltip']],$x);
      $x['control'] = $str;
    } elseif(in_array($x['input'],$input_types)) {
      $type=$x['input'];
      $x['control'] = control_str($type,['name' => $x['key'],
				      'tooltip' => $x['tooltip'],
					'value' => $x['value']],$x);
    }
  }
  ;
} * set_controls */


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