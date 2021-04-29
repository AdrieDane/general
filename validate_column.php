<?php

class Columnvalidation
{
  public function __construct($column,$type,$arr,$options=[])
{
  $this->column=$column;
  $this->type=$type;
  $this->vk=$arr;
  foreach($options as $field => $value) {
    $this->$field = $value;
  }
  /*
  switch($type)	{
   case 'nonnumeric':
   case 'nonunique':
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
  $doubleright="&#8658;";
  foreach($this->corrections as $after => $before) {
    $arr[]="$before $doubleright <span class='text-primary'>$after</span>\n";
  }
  // pre_r($arr,'corrections_html');
  $id=$this->type.str_replace(' ','_',$this->column);
  $str =  " (<a href='#$id' data-toggle='collapse' class='text-primary'><b>details</b></a>)".
    "<div id='$id' class='collapse'>\n";

  return $str."<ul>\n<li>".implode("\n<li>",$arr)."\n</ul>\n</div>\n";
} /* corrections_html */

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

  switch($this->type)	{
   case 'alphanumeric':
     $str .= "Only alphanumerical characters and underscores are allowed in '".
       $this->column."'.<br>";
     break;

    default :
      return $str;
  }

  if(isset($this->corrections))	{
    if($this->type=='alphanumeric')	{
      $charstr = "'<span class='text-primary'>".
	and_implode("</span>', '<span class='text-primary'>",array_unique($this->chars)).
	"</span>'";
      $charstr="These character(s): ".str_replace("> <",">SPACE<",$charstr).
	" were replaced by underscores";
      $str .= $charstr;
    }
    $str .= $this->corrections_html()."\n</div>\n";
  }
  return $str;
  
} /* html */



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
/*    Title: 	validate_column
      Purpose:	returns array with keys the non unique values and as values an array of rows
                   'value' => [row1 , row2]
      Created:	Sat Apr 03 14:33:54 2021
      Author: 	Adrie Dane
*/
function validate_unique($column)
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
  return new Columnvalidation($column,'nonunique',$non_unique);
} /* validate_column */

/*    Title: 	validate_numeric
      Purpose:	
      Created:	Mon Apr 26 15:56:42 2021
      Author: 	Adrie Dane
*/
function validate_numeric($column)
{
  $kv=array_filter(array_column($this->data,$column),
			    function($a){ return !is_numeric($a);
			    }
			    );
  $arr=[];
  foreach($kv as $k => $v) {
    $arr[$v][]=$k;
  }
  return new Columnvalidation($column,'nonnumeric',$arr);
} /* validate_numeric */

/*    Title: 	validate_alphanumeric
      Purpose:	
      Created:	Thu Apr 29 17:49:09 2021
      Author: 	Adrie Dane
*/
function validate_alphanumeric($column)
{
  return $this->validate_forbidden($column,'/\W+/','_','alphanumeric');
} /* validate_alphanumeric */



/*    Title: 	validate_forbidden
      Purpose:	
      Created:	Mon Apr 26 16:15:55 2021
      Author: 	Adrie Dane
*/
function validate_forbidden($column,$forbiddenchars='/\W+/',$correctchar='_',$forbidden='forbidden')
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
      $str=preg_replace($forbiddenchars,$correctchar,$x[$column]);
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
    return new Columnvalidation($column,$forbidden,$kv,
				['corrections' => $corrections,
				 'chars' => $chars]);
     
  } else {
    return $chars;
  }
} /* validate_forbidden */

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
      $idx=array_search(strtolower($x[$column]),$lower);
      if($idx !== false)	{
	$case['corrections'][]=$x[$column].' => '.$labels[$idx];
	$case['correction_rows'][]=$row;
	$corrections[$labels[$idx]]=$x[$column];
	$x[$column]=$labels[$idx];
      } else {
	$case['error'][]=$x[$column];
	$case['error_rows'][]=$row;
      }
      $kv[$x[$column]][]=$row;
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



  return empty($corrections) ?
    new Columnvalidation($column,'case',$kv) :
    new Columnvalidation($column,'case',$kv,['corrections' => $corrections]);
} /* validate_case */

 
}

?>
