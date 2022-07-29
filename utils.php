<?php
function randomPassword($length=16,$npasswords=1, $characters='lower_case,upper_case,numbers,underscore') {
 
  // $length - the length of the generated password
  // $npasswords - number of passwords to be generated
  // if $npasswords==1 returns a string otherwise an array of $npasswords passwords
  // $characters - types of characters to be used in the password
  
  $pat="/(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{".$length.",}/";
  //  echo $pat;
  
  // define variables used within the function    
  $symbols = array();
  $passwords = array();
  $used_symbols = '';
  $pass = '';
 
  // an array of different character types    
  $symbols["lower_case"] = 'abcdefghijklmnopqrstuvwxyz';
  $symbols["upper_case"] = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $symbols["numbers"] = '1234567890';
  $symbols["underscore"] = '_';
  $symbols["special_symbols"] = '!?~@#-+<>[]{}';
 
  $characters = explode(",",$characters); // get characters types to be used for the passsword
  foreach ($characters as $key=>$value) {
    $used_symbols .= $symbols[$value]; // build a string with all characters
  }
  $symbols_length = strlen($used_symbols) - 1; //strlen starts from 0 so to get number of characters deduct 1
     
  for ($p = 0; $p < $npasswords; $p++) {
    $pass='';
    while(!preg_match($pat,$pass))	{
      $pass = '';
      for ($i = 0; $i < $length; $i++) {
        $n = rand(0, $symbols_length); // get a random character from the string with all characters
        $pass .= $used_symbols[$n]; // add the character to the password string
      }
    }
    $passwords[] = $pass;
  }
  // return the generated passwords
  if($npasswords==1)	{
    return $passwords[0];
  }else	{
    return $passwords;
  }
}
 
/*    Title: 	pre_r
      Purpose:	print_r between <pre> and </pre> tags
      Created:	Fri Nov 13 11:04:15 2020
      Author: 	Adrie Dane
*/
function pre_r($data,$ttl='',$as_string=FALSE)
{
  /*  if(!isset($ttl) || empty($ttl))	{
    $f = new ReflectionFunction('pre_r');
    $pars=$f->getParameters();
    $ttl=$pars[0];
    }*/
  // echo '<br>ttl: '.$ttl;
  $str = $ttl=='' 
    ? "<pre>\n".print_r($data,TRUE)."\n</pre>" 
    : "<pre>\n$ttl: ".print_r($data,TRUE)."\n</pre>";
  if($as_string==FALSE)	{
    echo $str;
  } else {
    return $str;
  }
} /* pre_r */


  /*    Title: 	error_message
        Purpose:	creates formatted error message for exception
        Created:	Tue Jul 26 14:30:09 2022
        Author: 	
  */
function error_message($e,$append='')
  {
    $trace = $e->getTrace();
    $str = "Trace: <br>\n";
    foreach($trace as $x) {
      $str .= $x['file'].'('.$x['line'].')  <code>     ';
      $parts = ['class','type','function'];
      foreach($parts as $part) {
        $str .= isset($x[$part]) && !empty($x[$part])
             ? $x[$part] : '';
      }
      $str .= isset($x['function']) && !empty($x['function']) ? '()' : '';
      $str .= "</code><br>";
    }
    //error message
    $errorMsg = 'Error on line '.$e->getLine().' in '.$e->getFile()
              .': <b>'.$e->getMessage()."</b><br>".$str.$append;
    return $errorMsg;
  }

?>
