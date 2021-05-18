<?php

function clean_formdata_old($data,$submit=0)
{
  $mailvars=array();
  foreach($data as $key => $value) {
    $value=clean_value($value);
    if ($key!='submit' || $submit!=0) 
      $mailvars[$key] = $value;
  }
  return $mailvars;
}

function clean_formdata($data,$submit=0)
{
  if(is_array($data))	{
    $mailvars=array();
    foreach($data as $key => $value) {
      $value=clean_formdata($value);
      if ($key!='submit' || $submit!=0) 
	$mailvars[$key] = $value;
    }
    return $mailvars;
  } else {
    return clean_value($data);
  }
}

/*    Title: 	clean_post
      Purpose:	
      Created:	Wed Aug 26 11:08:16 2020
      Author: 	Adrie Dane
*/
function clean_post($data,$submit=0,$verbose=FALSE)
{
  $mailvars=array();
  
  foreach($data as $key => $value) {
    if($key=='submit' && $submit==0)	{
      continue;
    }
    if(is_array($value))	{
      foreach($value as $x) {
	$mailvars[$key][]=clean_value($x);
      }
    } else {
      $mailvars[$key]=clean_value($value);
    }
  }
  if($verbose==TRUE)	{
  echo "<pre><br>before clean---<br>";
  print_r($data);
  echo "<br>---";
  echo "<br>cleaned---<br>";
  print_r($mailvars);
  echo "<br>---</pre>";    
  }

  return $mailvars;
  
} /* clean_post */

/*    Title: 	safe_post
      Purpose:	cleaning posted values preserving key information
      Created:	Sun May 02 09:13:55 2021
      Author: 	Adrie Dane
*/
function safe_post(&$post,$verbose=false)
{

  if($verbose==true)	{
    echo "<pre><br>post before clean---<br>";
    print_r($post);
  }
  
  foreach($post as $key => &$data) {
    if(is_array($data))	{
      safe_post($data);
    } else {
      $data = clean_value($data);
    }
  }
  unset($data);

  if($verbose==true)	{
    echo "<pre><br>post after clean---<br>";
    print_r($post);
  }

  return $post;
} /* safe_post */

function is_blocked($mailvars,$spam)
{
  foreach($spam as $s) {
    switch($s['Type'])	{
    case 'end':
       $length = strlen($s['Email']);
       if(substr($mailvars['email'], -$length) === $s['Email']) {
	 return TRUE;
       }
       break;
    case 'eq':	;
      if($s['Email']===$mailvars['email'])	{
	return TRUE;
      }
      break;
    case 'ip':	;
      if($s['Email']===$mailvars['ip'])	{
	return TRUE;
      }
      break;
    case 'site':
      $re='/https?:\/\/'.str_replace('/','\\/',clean_value($s['Email'])).'/';
      preg_match_all($re, 
		     join(" ",$mailvars),
		     $output_array);
      /*  preg_match_all('/'.str_replace('/','\\/',clean_value($s['Email'])).'/', 
		     join(" ",$mailvars),
		       $output_array); */
      if(!empty($output_array[0]))	{
	return TRUE;
      }
      break;
    case 'regex':
      preg_match_all('/'.$s['Email'].'/i', 
		     join(" ",$mailvars),
		     $output_array);
      if(!empty($output_array[0]))	{
	return TRUE;
      }
      break;
    case 'chars':
      $mailstr=join(" ",$mailvars);
      $pat='/'.$s['Email'].'/i';
      $outstr=preg_replace($pat,'',$mailstr);
      //      echo strlen($outstr).': '.$outstr;
      if(strlen($outstr)>30) {
	//	echo 'BLOCKED';
	return TRUE;
      }
      
      break;
    default:
      break;
    }
  }
  return FALSE;
}



function clean_value($value)
{
  
  $value = trim($value);
  $value = stripslashes($value);
  $value = htmlspecialchars($value);

  return $value;
}

?>