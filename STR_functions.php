<?php

/*    Title: 	STR_prefix
      Purpose:	returns common part at start or end of strings
                in array $arr.
      Usage:    STR_prefix($arr);        returns common part at start
                STR_prefix($arr,false);  returns common part at start
                STR_prefix($arr,true);   returns common part at end
      Created:	Fri May 28 07:46:11 2021
      Author: 	
*/
function STR_prefix($arr,$revert=false)
{
  if($revert==true)	{
    $arr=array_map('strrev',$arr);
  }
  $length=min(array_map('strlen',$arr));
  $prefix=substr(reset($arr),0,$length);

  foreach($arr as $str) {
    $pos = strspn(substr($str,0,$length) ^ $prefix, "\0");
    if($pos < $length)	{
      $length=$pos;
      if($length==0)	{
        return '';
      }
      $prefix=substr($str,0,$length);
    }
  }
  return $revert==false ? $prefix : strrev($prefix);
} /* STR_prefix */

/*    Title: 	STR_diff
      Purpose:	removes common parts at start and end of strings in array $arr
      Created:	Fri May 28 08:09:59 2021
      Author: 	
*/
function STR_diff($arr)
{
  $nprefix=strlen(STR_prefix($arr));
  if($nprefix>0)	{
    foreach($arr as &$str) {
      $str=substr($str,$nprefix);
    }
  }
  unset($str);

  $nsuffix=strlen(STR_prefix($arr,true));
  if($nsuffix>0)	{
    foreach($arr as &$str) {
      $str=substr($str,0,strlen($str)-$nsuffix);
    }
  }
  unset($str);

  return $arr;
} /* STR_diff */


/*    Title: 	STR_overlap
      Purpose:	returns the biggest overlap between an array of strings:
      Created:	Fri May 28 09:06:23 2021
      Author: 	
*/
function STR_overlap($array)
{
  $biggest = reset($array);
  foreach ($array as $item) {
    if (($biggest = overlap($biggest, $item)) === '') {
      return '';
    }
  }
  return $biggest;
} /* STR_overlap */

/*    Title: 	overlap
      Purpose:	returns the biggest overlap between two strings
      Copied from: 
      https://stackoverflow.com/questions/15429186/php-find-biggest-overlap-between-multiple-strings
      Created:	Fri May 28 09:09:25 2021
      Author: 	
*/
function overlap($a, $b)
{
  if (!strlen($b)) {
    return '';
  }

  if (strpos($a, $b) !== false) {
    return $b;
  }

  $left = overlap($a, substr($b, 1));
  $right = overlap($a, substr($b, 0, -1));

  return strlen($left) > strlen($right) ? $left : $right;
} /* overlap */

/*    Title: 	STR_removeaccents
      Purpose:	removes accents from a string
      Created:	Fri Jul 15 13:41:05 2022
      Author: 	
*/
function STR_removeaccents($str)
{
  $unwanted_array = ['Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A',
                     'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
                     'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N',
                     'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
                     'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'ss', 'à'=>'a',
                     'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
                     'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
                     'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
                     'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b',
                     'ÿ'=>'y', 'Ğ'=>'G', 'İ'=>'I', 'Ş'=>'S', 'ğ'=>'g', 'ı'=>'i', 'ş'=>'s',
                     'ü'=>'u', 'ă'=>'a', 'Ă'=>'A', 'ș'=>'s', 'Ș'=>'S', 'ț'=>'t', 'Ț'=>'T'];
  return strtr( $str, $unwanted_array );;
} /* STR_removeaccents */

/*    Title: 	STR_posaccents
      Purpose:	as strpos ignores accents
      Created:	Fri Jul 15 14:06:03 2022
      Author: 	
*/
function STR_posaccents($haystack,$needle,$offset = 0)
{
  return strpos(STR_removeaccents($haystack),STR_removeaccents($needle),$offset);
} /* STR_posaccents */

/*    Title: 	STR_posaccents
      Purpose:	as strpos ignores accents and case
      Created:	Fri Jul 15 14:06:03 2022
      Author: 	
*/
function STR_posaccentsi($haystack,$needle,$offset = 0)
{
  return STR_posaccents(strtolower($haystack),strtolower($needle),$offset);
} /* STR_posaccents */


?>
