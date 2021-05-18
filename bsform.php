<?php

use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Bsform extends Oldbstable
{
  use Optionsarray;
  public function __construct($file='',$options=[]) 
{
  $opts=['header' => false,
	 'small' => false,
	 'column_width' => [30, 70],
	 'show_column' => ['title', 'control'],
	 //	 'hide_column' => ['key','tooltip','td','value','input',
	 //			   'error','warning','auto','type','cell'],
	 'form' => true,
	 'sheet' => '',
	 'skipemptyrows' => false];

  if(!is_array($options) && !empty($options))	{
    $opts['sheet']=$options;
  } else {
    $opts=$this->useroptions($opts,$options);
  }
  //pre_r($file);
  
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
  // pre_r(array_column($this->data,'control'),'Control');
  //  pre_r(array_column($this->data,'warning'),'Warning');
  
}

/*    Title: 	get_mapping
      Purpose:	return associative array ['key' => 'title']
      Created:	Thu Apr 08 09:23:34 2021
      Author: 	Adrie Dane
*/
function get_mapping()
{
  return array_column(array_filter($this->data,
				   function ($a) {return !empty($a['key']);}),'title','key');
  
} /* get_mapping */


/*    Title: 	link_excel
      Purpose:	linking a form in excel creates data member 'cell'
      Created:	Tue Apr 06 10:39:46 2021
      Author: 	Adrie Dane
*/
function read_excel($file=null,$sheet='',$title='A',$value='B')
{
  if($file instanceof Excelsheet)	{
    $this->excelfile = $file->filename;
    $xlsx = $file;
  } else {
    $this->excelfile = $file;
    $xlsx = new Excelsheet($file);
  }

  $this->exceldata = $xlsx->data();

  $map = $this->get_mapping();
  foreach($map as $key => $title) {
    $idx=array_search($title,array_column($this->exceldata,0));
    $this->data[$key]['value']=$this->exceldata[$idx][1];
    // echo '$idx: '.$idx." title: $title value: ".$this->exceldata[$idx][1]."<br>";
    
  }

  $this->set_controls(); 
  //  pre_r($data);
  
  //$this->link = new Excelsheet($file,$sheet);
  //$data = $this->link->data();
} /* link_excel */

/*    Title: 	get_spreadsheet
      Purpose:	returns Excelsheet object for $obj->excelfile
      Created:	Thu Apr 08 11:17:29 2021
      Author: 	Adrie Dane
*/
function get_spreadsheet($options=[])
{
  $opts=['template' => true];
  $opts=$this->useroptions($opts,$options);

  if($opts['template']==true)	{
    return new Excelsheet($this->excelfile,['dataonly' => false]);
  }

  //  return $xl->wb;
} /* get_spreadsheet */

/*    Title: 	update_excelfile
      Purpose:	
      Created:	Thu Apr 08 11:39:28 2021
      Author: 	Adrie Dane
*/
function update_excelfile()
{
  $xlsx=$this->get_spreadsheet();
  $map = $this->get_mapping();
  foreach($map as $key => $title) {
    $idx=array_search($title,array_column($this->exceldata,0));
    if($this->data[$key]['value']!=$this->exceldata[$idx][1])	{
      $xlsx->set_data([$idx,1], $this->data[$key]['value']);
    }
  }
  /* this is just a test
$xlsx->set_data('A2', 'Hello World !');
$xlsx->set_data([1,3], 'Hello Again World !');
$xlsx->set_data([4,0], ['Q1',   12,   15,   21]);
$xlsx->set_data([7,1], [
    [NULL, 2010, 2011, 2012],
    ['Q1',   12,   15,   21],
    ['Q2',   56,   73,   86],
    ['Q3',   52,   61,   69],
    ['Q4',   30,   32,    0],
    ]); */
  $out_file=sys_get_temp_dir().'/'.pathinfo($this->excelfile,PATHINFO_BASENAME);

  $writer = new Xlsx($xlsx->wb);
  $writer->save($out_file);
} /* update_excelfile */



/*    Title: 	update_excel
      Purpose:	
      Created:	Thu Apr 08 10:06:16 2021
      Author: 	Adrie Dane
*/
function update_excel()
{
  $map = $this->get_mapping();
  foreach($map as $key => $title) {
    $idx=array_search($title,array_column($this->exceldata,0));
    $this->exceldata[$idx][1]=$this->data[$key]['value'];
    
    echo '$idx: '.$idx." title: $title<br>";
    
  }
} /* update_excel */

/*    Title: 	write_excel
      Purpose:	
      Created:	Thu Apr 08 10:17:02 2021
      Author: 	Adrie Dane
*/
function write_excel($file)
{
  $sh = new Excelsheet();

  $spreadsheet = new Excelsheet($file,['dataonly' => false]);
  ;
} /* write_excel */


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
           'array' => 1,
	   'pattern' => ''];

    $type=$x['input'];
    foreach(['value','error','warning','auto','value','tooltip'] as $field) {
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
      $opts['value'] = empty($x['value']) ? $opts['choices'][0] : $x['value'];
    }
    //   pre_r($opts,'opts');
    
    $x['control']=$this->control_str($type,$opts);
  
  }
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


/*    Title: 	control_str
      Purpose:	returns html code for a control
      Created:	Sun Mar 28 09:44:33 2021
      Author: 	Adrie Dane
*/
function control_str($type,$opts=[])
{
  // echo "type: $type<br>\n";
  $input_types=["button", "checkbox", "color", "date", "datetime-local", "email", 
		"file", "hidden", "image", "month", "number", "password", "radio", 
		"range", "reset", "search", "submit", "tel", "text", "time", 
		"url", "week","select","textarea"];
  if(!in_array($type,$input_types)) {
    return '';
  }
  //  pre_r($opts,"$type");
  foreach($opts as $key => $val) {
    if(empty($val))	{
      unset($opts[$key]);
    }
  }
  extract($opts);

  $error='';
  $warning='';
  $auto='';

  if(isset($opts['error']) && !empty($opts['error']))	{
    $error="<br><b><span class='text-danger'>".$opts['error']."</span></b>";
  }
  if(isset($opts['warning']) && !empty($opts['warning']))	{
    $warning="<br><b><span class='text-warning'>".$opts['warning']."</span></b>";
  }      
  if(isset($opts['auto']) && !empty($opts['auto']))	{
    $auto="<br><b><span class='text-success'>".$opts['auto']."</span></b>";
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
    
      if(isset($pattern))	{
	$str .= " pattern='$pattern'";
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

  return $str.$error.$warning.$auto;
} /* control_str */


}

?>
