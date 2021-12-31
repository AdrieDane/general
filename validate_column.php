<?php

class Columnvalidation
{
  
  public function __construct(&$obj,$column,$type,$arr,$options=[])
  {
    $opts = useroptions(['warning' => 'warning'],$options,true);

    $this->column=$column;
    $this->type=$type;
    $this->vk=$arr;
    foreach($opts as $field => $value) {
      $this->$field = $value;
    }
    $this->color='text-warning';
    if(isset($this->corrections))	{
      $this->warning='auto';
    }
    switch($this->warning)	{
    case 'auto':
      $this->color='text-success';
      break;
    case 'warning':
      $this->color='text-warning';
      break;
    case 'error':
      $this->color='text-danger';
      break;
    default :	break;
    }

    if(isset($obj->cells) && !empty($obj->cells))	{
      // echo 'cells set!*';
      $this->update_cells($obj->cells);
      //    exit;
    }

    /*
      switch($type)	{
      case 'numeric':
      case 'unique':
      break;

      default :	break;
      }*/
  }

  /*    Title: 	rows
        Purpose:	
        Created:	Wed Apr 28 10:17:50 2021
        Author: 	Adrie Dane
  */
  function rows($k='')
  {
    if(isset($this->vk))	{
      if(empty($k))	{
        $vals=array_values($this->vk);
        return array_unique(...$vals);
      } else {
        return $this->vk[$k];
      }
    }
  } /* rows */

  /*    Title: 	values
        Purpose:	
        Created:	Wed Apr 28 10:23:43 2021
        Author: 	Adrie Dane
  */
  function values($row)
  {
    if(isset($this->vk))	{
      if(empty($row))	{
        return array_keys($this->kv);
      }
    }
  
  } /* values */

  /*    Title: 	corrections_html
        Purpose:	
        Created:	Wed Apr 28 11:26:49 2021
        Author: 	Adrie Dane
  */
  function corrections_html()
  {
    if(!isset($this->corrections) || empty($this->corrections))	{
      return '';
    }
    $arr=[];
    $color=$this->color;
    $doubleright="&#8658;";
    foreach($this->corrections as $after => $before) {
      $arr[]="$before $doubleright <span class='$color'>$after</span>\n";
    }
    // pre_r($arr,'corrections_html');
    $id=$this->type.str_replace(' ','_',$this->column);
    $str =  " (<a href='#$id' data-bs-toggle='collapse' class='$color'><b>details</b></a>)".
         "<div id='$id' class='collapse'>\n";

    return $str."<ul>\n<li>".implode("\n<li>",$arr)."\n</ul>\n</div>\n";
  } /* corrections_html */

  /*    Title: 	warning_html
        Purpose:	
        Created:	Fri Apr 30 10:10:34 2021
        Author: 	Adrie Dane
  */
  function warning_html()
  {
    $arr=[];
    $color=$this->color;
    $id=$this->type.str_replace(' ','_',$this->column);
    $str =  " (<a href='#$id' data-bs-toggle='collapse' class='$color'><b>details</b></a>)".
         "<div id='$id' class='collapse'>\n";

  
    return $str."<ul>\n<li>'<span class='$color'>".
               implode("</span>'\n<li>'<span class='$color'>",array_keys($this->vk)).
               "</span>'\n</ul>\n</div>\n";
  } /* warning_html */



  /*    Title: 	html
        Purpose:	
        Created:	Thu Apr 29 17:46:56 2021
        Author: 	Adrie Dane
  */
  function html()
  {
    $str='';
  
    if($this->ok())	{
      return $str;
    }

    $color=$this->color;
    $column="'<span class='$color'>".$this->column."</span>'";
    switch($this->type)	{
    case 'alphanumeric':
      $str .= "Only alphanumerical characters and underscores are allowed in $column.<br>";
      break;
    case 'case':
      $str .= "Case mismatch in $column.<br>";
      break;
    case 'numeric':
      $str .= "Non numerical values present in $column ";
      break;
    case 'unique':
      $str .= "Non unique values present in $column ";
      break;

    default :
      return $str;
    }

    if(isset($this->corrections))	{
      if($this->type=='alphanumeric')	{
        $charstr = "'<span class='$color'>".
                 and_implode("</span>', '<span class='$color'>",array_unique($this->chars)).
                 "</span>'";
        $charstr="These character(s): ".str_replace("> <",">SPACE<",$charstr).
                " were replaced by underscores";
        $str .= $charstr;
      } elseif($this->type='case') {
        $str .= "These mismatches were corrected ";
      }
      $str .= $this->corrections_html()."\n";
    } else {
      $str .= $this->warning_html()."\n";
    }
    return $str;
  
  } /* html */

  /*    Title: 	update_cells
        Purpose:	
        Created:	Mon May 03 10:38:28 2021
        Author: 	Adrie Dane
  */
  function update_cells(&$cells)
  {
    foreach($this->vk as $value => &$keys) {
      foreach($keys as &$key) {
        $arr=[];
        $arr['type']=$this->type;
        $arr['value']=$value;
        $arr['warning']=$this->warning;
        if(isset($this->corrections[$value]))	{
          $arr['oldvalue']=$this->corrections[$value];
          $arr['warning']='auto';
        }
        $cells[$key][$this->column]->set_validation($arr);
        //        pre_r($arr,'$arr');
      }
      unset($key);
    }
    unset($keys);
  } /* update_cells */


  /*    Title: 	ok
        Purpose:	returns true when no errors/warnings/automatic corrections
        returns false otherwise
        Created:	Thu Apr 29 16:48:04 2021
        Author: 	Adrie Dane
  */
  function ok()
  {
    return empty($this->vk);
  } /* ok */


}

trait ValidateColumn
{
  /*    Title: 	validate_all
        Purpose:	
        Created:	Tue May 04 14:07:44 2021
        Author: 	Adrie Dane
  */
  function validate_all()
  {
    if(!isset($this->validate) || empty($this->validate))	{
      return;
    }
    foreach($this->validate as $column => $validations) {
      if($column=='html')	{
        continue;
      }
      foreach($validations as $validation_type => $args) {
        $obj=$this->$validation_type($column,...$args);
        if(is_string($obj))	{
          $this->validate[$column]['html'][] = $obj;
        }
        elseif(is_array($obj))	{
          $this->validate[$column]['html'][] = implode(', ',$obj);
        }else	{
          $this->validate[$column]['html'][] = $obj->html();
        }
      }
    }
    //  exit;
  
  } /* validate_all */




  /*    Title: 	validate_column
        Purpose:	returns array with keys the non unique values and as values an array of rows
        'value' => [row1 , row2]
        Created:	Sat Apr 03 14:33:54 2021
        Author: 	Adrie Dane
  */
  function validate_unique($column,$warning='error')
  {/*
     $values=array_column($this->data,$column);
     $unique=array_unique($values);
     return array_diff($values,$unique);
   */
    $vals = array_keys(array_filter(array_count_values(array_column($this->data,$column)),
                                    function ($a) {return $a>1;}));
    $non_unique=[];

    if(!empty($vals))	{
      foreach($vals as $v) {
        $non_unique[$v]=array_keys(array_column($this->data,$column),$v);
      }
    
    }
    $col_validation = new Columnvalidation($this,$column,'unique',$non_unique,
                                           ['warning' => $warning]);
    return $col_validation;
  } /* validate_column */

  /*    Title: 	validate_numeric
        Purpose:	
        Created:	Mon Apr 26 15:56:42 2021
        Author: 	Adrie Dane
  */
  function validate_numeric($column,$warning='error')
  {
    //  echo "validate_numeric";
  
    $kv=array_filter(array_column($this->data,$column),
                     function($a){ return !is_numeric($a);
                     }
    );
    $arr=[];
    foreach($kv as $k => $v) {
      $arr[$v][]=$k;
    }
    $col_validation = new Columnvalidation($this,$column,'numeric',$arr,['warning' => $warning]);
    return $col_validation;
  } /* validate_numeric */

  /*    Title: 	validate_alphastart
        Purpose:	
        Created:	Mon May 03 17:50:36 2021
        Author: 	Adrie Dane
  */
  function validate_alphastart($column,$correct=true)
  {
    //  echo "validate_alphastart";

    preg_match_all('/^[^a-z]/im', 
                   implode("\n",array_column($this->data,$column)), 
                   $chars);
    //  pre_r($chars);

    if(empty($chars[0]))	{
      return [];
    }

    $patterns=['/(^[^a-z]+)(.*)/i','/(.*)(\W$)/i'];
    $replacements=['$2_$1','$1'];
    if($correct==true)	{
      $kv=[];
      $corrections=[];
      foreach($this->data as $row => &$x) {
        $str=preg_replace($patterns,$replacements,$x[$column]);
        if($str!=$x[$column])	{
          $corr['corrections'][]=$x[$column]." => $str";
          $corr['correction_rows'][]=$row;
          $kv[$str][]=$row;
          $corrections[$str]=$x[$column];
          $x[$column]=$str;
        }
      }
      unset($x);
    }



    $col_validation = new Columnvalidation($this,$column,'alphastart',$kv,
                                           ['corrections' => $corrections]);
    //    pre_r($col_validation,'$col_validation');

    return $col_validation;
  } /* validate_alphastart */




  /*    Title: 	validate_alphanumeric
        Purpose:	
        Created:	Thu Apr 29 17:49:09 2021
        Author: 	Adrie Dane
  */
  function validate_alphanumeric($column)
  {
    //  echo "validate_alphanumeric";
  
    return $this->validate_forbidden($column,'/\W+/','_','alphanumeric');
  } /* validate_alphanumeric */



  /*    Title: 	validate_forbidden
        Purpose:	
        Created:	Mon Apr 26 16:15:55 2021
        Author: 	Adrie Dane
  */
  function validate_forbidden($column,$forbiddenchars='/\W+/',$correctchar='_',
                              $forbidden='forbidden')
  {
    preg_match_all($forbiddenchars, 
                   implode('',array_column($this->data,$column)), 
                   $chars);

    if(empty($chars[0]))	{
      return [];
    }

    $chars=$chars[0];
 
    $chars=array_unique(str_split(implode('',$chars)));
    if($correctchar!==false)	{
      $kv=[];
      $corrections=[];
      foreach($this->data as $row => &$x) {
        $str=preg_replace([$forbiddenchars,'/'.$correctchar.'+/'],$correctchar,$x[$column]);
        if($str!=$x[$column])	{
         
          $corr['corrections'][]=$x[$column]." => $str";
          $corr['correction_rows'][]=$row;
          $kv[$str][]=$row;
          $corrections[$str]=$x[$column];
          $x[$column]=$str;
        }
      }
      unset($x);
      $corr['chars']=$chars;

      $col_validation = new Columnvalidation($this,$column,$forbidden,$kv,
                                             ['corrections' => $corrections,
                                              'chars' => $chars]);
      //    pre_r($col_validation,'$col_validation');

      return $col_validation;
    } else {
      return $chars;
    }
  } /* validate_forbidden */

  /*    Title: 	validate_member
        Purpose:	checks whether value is present in array
        Created:	Mon May 31 13:05:41 2021
        Author: 	
  */
  function validate_member($column,$allowed)
  {
    $kv=array_filter(array_column($this->data,$column),
                     function($a) use($allowed) { return !in_array($a,$allowed);
                     }
    );
    $arr=[];
    foreach($kv as $k => $v) {
      $arr[$v][]=$k;
    }
    $col_validation = new Columnvalidation($this,$column,'member',$arr,['warning' => $warning]);
    return $col_validation;
  } /* validate_member */


  
  /*    Title: 	validate_case
        Purpose:	matches column against array
        Created:	Mon Apr 26 17:31:22 2021
        Author: 	Adrie Dane
  */
  function validate_case($column,$labels)
  {
    // make sure grouplabels in samples match those in groups correct case if necessary
    $labels=array_values(array_unique($labels));
    $lower=array_map("strtolower",$labels);
    $case=[];
    $kv=[];
    $corrections=[];
    foreach($this->data as $row => &$x) {
      if(!in_array($x[$column],$labels))	{
        if(isset($this->cells[$row][$column]->choices))	{
          $this->cells[$row][$column]->choices=$labels;
        }
        $idx=array_search(strtolower($x[$column]),$lower);
        // only a difference in case
        if($idx !== false)	{
          $str=$labels[$idx];
          $case['corrections'][]=$x[$column].' => '.$str;
          $case['correction_rows'][]=$row;
          $kv[$str][]=$row;
          $corrections[$str]=$x[$column];
          $x[$column]=$str;
          //pre_r($this->cells[$row][$column],"cell[$row][$column]");
        } else {
          if(isset($this->cells[$row][$column]->choices))	{
            $this->cells[$row][$column]->choices
              = array_merge([$x[$column]],$this->cells[$row][$column]->choices);
          }
          $case['error'][]=$x[$column];
          $case['error_rows'][]=$row;
          $kv[$x[$column]][]=$row;
        }
      }
    }
    unset($x);
    if(!empty($case))	{
      foreach(['corrections','error'] as $warning) {
        if(isset($case[$warning]))	{
          $case[$warning]=array_unique($case[$warning]);
        }
      }
    }

    $col_validation = empty($corrections) ?
                    new Columnvalidation($this,$column,'case',$kv) :
                    new Columnvalidation($this,$column,'case',$kv,['corrections' => $corrections]);
    // pre_r($col_validation,'$col_validation');
    //exit;
    return $col_validation;
  } /* validate_case */

  /*    Title: 	validate_bidirect
        Purpose:	Remove comparisons in two directions
        i.e. if group 1 => group 2 comparison is present
        group 2 => group 1 comparison is removed
        Created:	Fri Apr 30 11:23:57 2021
        Author: 	Adrie Dane
        *
        function validate_bidirect($column)
        {
        ;
        } * validate_bidirect */


}

?>
