<?php

trait Optionsarray
{
/*    Title: 	useroptions
      Purpose:	
      Created:	Sat Apr 03 14:33:54 2021
      Author: 	Adrie Dane
*/
function useroptions($options,$user)
{
  $keys=array_intersect(array_keys($options),array_keys($user));
  foreach($keys as $key) {
    $options[$key]=$user[$key];
  }
  return $options;
} /* useroptions */
 
}

?>
