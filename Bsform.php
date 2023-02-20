<?php

use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

//class Bsform extends Bstable
class Bsform extends Bsdata
{
  use Optionsarray;
  public function __construct($file='',$options=[]) 
  {
    $opts=['header' => false,
           'small' => false,
           'accept_button' => true,
           'column_width' => [30, 70],
           'show_column' => ['title', 'control'],
           'align' => ['left', 'right'],
           'title_checks' => true,
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
    if(is_array($file))	{
      $tbl=new datatable($file);
    } else {
      $tbl = new importtable($file,$opts);
    }
    //    pre_r($tbl,'$tbl');
    // collect hidden controls (move them outside table)
    $data=[];
    $hidden=[];
    $check_present=['unit' => '','error' => '', 'warning' =>'' , 'auto' => '', 'tooltip' =>'', 'control' =>''];
    foreach($tbl->data as $k => $x) {
      foreach($check_present as $field => $value) {
        if(!isset($x[$field]))	{
          $x[$field]=$value;
        }
      }
      if($x['type']=='hidden')	{
        $hidden[]=$x;
      } else {
        if(!isset($x['input']) || empty($x['input']))	{
          $x['input']=$x['type'];
        }
        $data[]=$x;
      }
    }

    // move visible columns to the front
    $cols=$opts['show_column'];
    if(isset($cols)&& !empty($cols))	{
      $tmp_data=$data;
      $data=[];
      foreach($tmp_data as &$x) {
        $y=[];
        foreach($cols as $field) {
          $y[$field] = $x[$field];
        }
        foreach(array_diff(array_keys($x),$cols) as $field) {
          $y[$field] = $x[$field];
        }
        $data[]=$y;
      }
    }
    $tbl=new datatable($data);
    
    //    pre_r($tbl,'$tbl');
    //exit;
    $keys=array_keys($tbl->data);
    $keys=array_combine($keys,$keys);
    foreach($tbl->data as $k => &$x) {
      // if type is radio: don't change key!! Does this fix always work? It seems so!!
      if(empty($x['key']) || $x['type']=='radio')	{
        $x['td'] = 'th';
      } else {
        $x['td'] = 'td';
        $keys[$k] = $x['key'];
      }
    }
    unset($x);
    $tbl->data=array_combine($keys,$tbl->data);

    // pre_r($keys,'$keys');
    // pre_r($opts,'$opts');

    parent :: __construct( $tbl->data,$opts);
    // pre_r($this,'$this');

    $this->set_controls($opts['title_checks']);

    $this->hidden=$hidden;
    // pre_r(array_column($this->data,'control'),'Control');
    //  pre_r(array_column($this->data,'warning'),'Warning');
  
  }

  /*    Title: 	checkbox
        Purpose:	returns element
        Created:	Mon May  2 14:51:30 2022
        Author: 	
  */
  public static function checkbox($key,$title,$options=[])
  {
    return useroptions(['title' => $title, 'value' => 0,
                        'type' => 'checkbox', 'tooltip' => '',
                        'pattern' => '','control' => '',
                        'unit' => '','error' => '', 'warning' =>'' ,
                        'auto' => '','key' => $key,
                        'name' => ''],$options);
  } /* checkbox */

  /*    Title: 	radio
        Purpose:	returns element
        Created:	Mon May  2 14:51:30 2022
        Author: 	
  */
  public static function radio($key,$title,$options=[])
  {
    return useroptions(['title' => $title, 'value' => 0,
                        'type' => 'radio', 'tooltip' => '',
                        'pattern' => '','control' => '',
                        'unit' => '','error' => '', 'warning' =>'' ,
                        'auto' => '','key' => $key,
                        'name' => ''],$options);
  } /* radio */
  
  /*    Title: 	submit
        Purpose:	returns element
        Created:	Mon May  2 14:51:30 2022
        Author: 	
  */
  public static function submit($key,$title,$options=[])
  {
    return useroptions(['title' => '', 'value' => $title,
                        'type' => 'submit', 'tooltip' => '',
                        'pattern' => '','control' => '',
                        'unit' => '','error' => '', 'warning' =>'' ,
                        'auto' => '','key' => $key,
                        'name' => ''],$options);
  } /* submit */

  
  /*    Title: 	html
        Purpose:	
        Created:	Mon Oct 11 08:25:59 2021
        Author: 	
  */
  function html($field="_data")
  {
    $str = parent :: html($field);
    if(isset($this->hidden) && ! empty($this->hidden))	{
      foreach($this->hidden as $x) {
        $str .= "\n<input type='hidden' name='".$x['key']."' value='".$x['value']."'>";

      }
    }
    return $str;
  } /* html */


  
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
  function set_controls($title_checks)
  {
    foreach($this->data as &$x) {
      //      pre_r($x,'$x');
      $opts=['width' => '',
             'rows' => '',
             'default' => '',
             'choices' => '',
             'array' => 1,
             'pattern' => '',
             'title_checks' => $title_checks];

      $type=$x['input'];
      foreach(['value','error','warning','auto','value','tooltip','extracode'] as $field) {
        $opts[$field]=$x[$field];
      }
      $opts['name']=$x['key'];

      if(isset($x['array']) && !empty($x['array']))	{
        $opts['array']=intval($x['array']);
        $opts['name']=$x['key'].'[]';
        // unset($x['array']);
      }
      // Special cases add array for select
      if(is_array($x['input']))	{
        //        pre_r($x['input']);
        //        $type='select';
        $type = $x['type'];
        $opts['choices']=$x['input'];
        $opts['value'] = empty($x['value']) ? reset($opts['choices']) : $x['value'];
        $opts['key_value'] = true;
      }elseif(is_numeric($x['input'])) { // textarea
        $type='textarea';
        $opts['rows']=$x['input'];
      } elseif(substr($x['input'],0,1)=='[')	{ // multiple controls
        $parts=explode(']',substr($x['input'],1));
        list($type,$count)=$parts;
        $opts['name'] .= "[]";
        $opts['array']= $count;
      } elseif(strpos($x['input'],'|')!==false) { // select
        //        $type='select';
        $type = $x['type'];
        $opts['choices']=explode('|',$x['input']);
        $opts['value'] = empty($x['value']) ? $opts['choices'][0] : $x['value'];
      }
      //   pre_r($opts,'opts');
      // echo $type;

      // Create controls
      if($type=='section')	{ // no control and no input make bold title
        $x['title'] = "<b>". $x['title']."</b>";
      }
      if($type=='radio')	{
        $opts['title']=$x['title'];
      }
      if(($type=='checkbox' || $type=='radio' || $type=='submit') &&
         $title_checks==true && isset($x['title']))	{
        // for checkboxes and radio store title without control in pattern
        // (it is not used for other things)
        if(empty($x['pattern']))	{
          $x['pattern']=$x['title'];
        }
        $x['title'] = $this->control_str($type,$opts) . $x['pattern'];
        
      } else	{
        // pre_r([$type,$opts],'$opts');
        $x['control']=$this->control_str($type,$opts);
      } 
  
    }
    unset($x);
    // pre_r($this,'$this'); 
    // exit;
  } /* set_controls */


  /*    Title: 	update_data
        Purpose:	
        Created:	Mon Jan 18 17:27:30 2021
        Author: 	Adrie Dane
        *
        function update_data($post)
        {

        $keys=array_intersect(array_keys($post),
        array_keys($this->data));
        foreach($keys as $key) {
        $this->data[$key]['value']=$post[$key];
        }
        $this->set_controls('input','key'); 
        } * update_data */

  /*    Title: 	update_formdata
        Purpose:	
        Created:	Mon Jan 18 17:27:30 2021
        Author: 	Adrie Dane
  */
  function update_formdata(&$post)
  {

    $keys=array_intersect(array_keys($post),
                          array_keys($this->data));
    foreach($this->data as $key => &$x) {
      if($x['input']=='checkbox')	{
        $post[$key]=isset($post[$key]) ? 1 : 0;
        $x['value']=$post[$key];
      }
    }
    unset($x);
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
    //    pre_r($opts,'$opts');
    // echo "type: $type<br>\n";
    $input_types=["button", "checkbox", "color", "date", "datetime-local", "email", 
                  "file", "hidden", "image", "month", "number", "password", "radio", 
                  "range", "reset", "search", "submit", "tel", "text", "time", 
                  "url", "week","select","textarea","plaintext"];
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
      //      pre_r($opts,'select');
      //  extract($opts);
      $str ='';
      for(	$i=0;	$i<$array;	$i++)	{
        $str .= "<select class='form-select' name='$name'>\n";
        foreach($choices as $key => $choice) {
          //          if(empty($choice))	{
          //            continue;
          //          }
          if(isset($value))	{
            if(is_array($value))	{
              if(isset($value[$i]) && !empty($value[$i]))	{
                $v=$value[$i];
              }else	{
                $v='';
              }
            } else {
              $v=$value;
            }
          }
          $key_choice = isset($opts['key_value']) ? $key : $choice;
          $str .= "<option value='$key_choice'";
          $str .= isset($v) && $key_choice==$v ?
               " selected>" : ">";
          $str .= "$choice</option>\n";
        }
        $str .= "</select>\n";
      }
    } elseif($type=='textarea')	{
      if(!isset($rows))	{
        $rows=1;
      }
      $str = "<textarea class='form-control' name='".$opts['name']."' rows='$rows' style='width:100%;'>";
      $str .= isset($value) ? $value : '';
      $str .= "</textarea>\n";
    } elseif($type=='checkbox')	{
      $checked = isset($value) && $value==true ? ' checked' : '';
      //      $str = "<input type='$type' name='".$opts['name']."'$checked style='width:100%;'>";
      $str = "<input class='form-check-input' type='$type' name='".$opts['name']."'$checked> ";      
    } elseif($type=='radio')	{
      $checked = isset($value) && $value==true ? ' checked' : '';
      //      $str = "<input type='$type' name='".$opts['name']."'$checked style='width:100%;'>";
      $str = "<input class='form-check-input' type='$type' name='".$opts['name'].
           "' value='".$opts['title']."'$checked> ";          
    } elseif($type=='submit')	{
      //      $str = "<input type='$type' name='".$opts['name']."'$checked style='width:100%;'>";
      $str = "<input class='btn btn-primary' type='$type' name='".$opts['name'].
           "' value='".$opts['value']."'> ";      
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
      if($type=='text' && isset($opts['choices']))	{
        $str .= "\n<datalist id='".$opts['name']."list'>\n";
        foreach($choices as $choice) {
          $str .= "  <option value='$choice'>\n";
        }
        $str .= "</datalist>\n\n";

        unset($opts['choices']);

        $opts['list']=$opts['name']."list";
        //        pre_r($opts,'$opts***');
      }
      //      pre_r($array,$opts['name']);
      $width= $array==1 || '$type'!='date'  ? 100 : floor(98/$array);
      // $width=100;
      $str .= "\n<span><div class='input-group'>\n";
      for(	$i=0;	$i<$array;	$i++)	{
        $plain = $type== 'plaintext' ? '-plaintext' : '';
        $str .= "<input class='form-control$plain' type='$type'";
        $attributes=array_intersect(['name','list'],array_keys($opts));
        //        pre_r($attributes,'$attributes');
        foreach($attributes as $attr) {
          $str .=  " $attr='".$opts[$attr]."'";
          // unset($opts[$attr]);
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

        if(isset($extracode))	{
          //          pre_r($extracode,'$extracode');
          $str .= " $extracode";
        }
        
        //  $str .= $type == "date" ?
        //          " placeholder='dd-mm-yyyy'>" : 
        //          " style='width:$width%;'>\n";
        $str .= $type == "date" ? ">" : " style='width:$width%;'>\n";

        // $str .= ">\n";
        //        $str .= "</div>\n";
      }
      $str .= "</div></span>\n";
      // $str .=  ">";
    }

    if(isset($tooltip))	{
      // data-html='true'
      $str = "<span data-toggle='tooltip' data-placement='auto' ".
           "title='$tooltip' style='width:100%;'>\n".
           $str."</span>\n";
    } else {
      $str = $title_checks==false
           ? "<span style='width:100%;'>\n".$str."</span>\n"
           : $str;
    }

    return $str.$error.$warning.$auto;
  } /* control_str */


}

?>
