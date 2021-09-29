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
    $this->hidevalue=false;
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
  {/*
    if(isset($this->hidecolumn))	{
      return '';
      }*/
    $opts=useroptions(['head' => false,
                       'visible' => true,
                       'align' => [],
                       'title' => [],
                       'sortable' => [],
                       'formatter' => [],
                       'data_only' => false],$options);
    extract($opts);
  
    if($head==true)	{
      $data_field=preg_replace('/\W/', '', $key);
      $data_visible = $visible==true ? "" : " data-visible='false'";
      $data_align = empty($align) ? "" : " data-halign='$align'  data-align='$align'";
      $data_title = empty($title) ? "" : " data-title='$title'";
      if(!empty($sortable))	{
        $sortable == false ? 'false' : 'true';
      }
      $data_sortable = empty($sortable) ? "" : " data-sortable='$sortable'";
      $data_formatter = empty($formatter) ? "" : " data-formatter='$formatter'";
      return "<th data-field='$data_field'$data_visible$data_align$data_title$data_formatter$data_sortable>$key</th>";
      // data-sortable='true' scope='col'
    }

  
    $ctrl_class = isset($this->ctrl_class) && !empty($this->ctrl_class) ?
                " class='".implode(" ",array_unique($this->ctrl_class))."'" : "";


    $key=str_replace(' ','_',$key);
    $str = '';
    if($this->hidevalue == false)	{
      if(!isset($this->type) || $data_only==true)	{
        $str .= $this->value;
      } elseif($this->type=='select')	{
        // fix sort by comment $value
        $str .= "<!---".$this->value."--->";
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
        // fix sort by comment $value
        $str .= "<!---".$this->value."--->";
        $str .= "<input type='".$this->type.
             "' name='".$key."[".$row.
             "]' value='".$this->value."'$ctrl_class>";
      }
    }

    $td = isset($this->td) ? $this->td : 'td';

    //  pre_r($this,"cell: $row-$key");
  
    if(isset($this->td_class) && !empty($this->td_class))	{
      $td .= " class='".implode(" ",array_unique($this->td_class))."'";
    }

    if(isset($this->warning))	{
      $warn=[];
      foreach($this->warning as $warning) {
        if(is_array($warning))	{
          foreach($warning as $w) {
            $warn[]=$w;
          }
        } elseif(is_string($warning)&& $warning!='') {
          $warn[]=$warning;
        }
      }
      $warn=array_unique(array_filter($warn));
      //pre_r($tooltip,'$tooltip');
      // pre_r($warn,'$warn');
      if(!empty($warn))	{
        $tooltip = "<ul><li>".
                 implode("<li>",$warn)."</ul>";
        $this->tooltip = isset($this->tooltip) ?
                       $this->tooltip."<br>".$tooltip : $tooltip;
      }
    }
    
    
    if(isset($this->tooltip))	{
      // data-html='true'
      $str = "<span data-toggle='tooltip' data-placement='auto' data-html='true' ".
           "title='".$this->tooltip."' style='width:100%;'>".
           $str."</span>";
    } else {
      $str = "<span style='width:100%;'>\n".$str."</span>\n";
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
    $doubleright="&#8658;";
    //    pre_r($arr,'**arr validation**<br>');
    //    pre_r($this,'cell');
    if(isset($arr['oldvalue']) && $arr['value'] != $this->value)	{
      $this->oldvalue=$arr['oldvalue'];
    }
    $this->value=$arr['value'];

    switch($arr['warning'])	{
    case 'auto':	$color='text-success'; break;
    case 'warning':	$color='text-warning'; break;
    case 'error':	$color='text-danger'; break;
    default :		$color='text-primary'; break;
    }

    $str='';
    switch($arr['type'])	{
    case 'alphanumeric':
      $str .= "Non alphanumerical characters";
      break;
    case 'case':
      $str .= "Case mismatch";
      break;
    case 'numeric':
      $str .= "Non numerical value";
      break;
    case 'unique':
      $str .= "Non unique value";
      break;
    default :
      ;
    }
    $str .= isset($this->oldvalue) ?
         " corrected: <b>".$this->oldvalue."</b> ".$doubleright : 
         ':';

    $str .= " <span class=\"$color\"><b>".
         $this->value."</b></span>\n";
    
    $this->warning[$arr['warning']][]=$str;

    $this->set_color();
    //    pre_r($this,'$this');
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
      $warn_keys=array_keys($this->warning);
      //      pre_r($warn_keys,'$warn_keys');
      if(in_array('error',$warn_keys))	{
        $col = 'danger';
      } elseif(in_array('warning',$warn_keys))	{
        $col = 'warning';
      } else {
        $col = 'success';
      }
      //      pre_r($col,'$col');
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
?>
