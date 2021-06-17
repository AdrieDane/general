<?php
function randomPassword($length=16,$npasswords=1, $characters='lower_case,upper_case,numbers') {
 
  // $length - the length of the generated password
  // $npasswords - number of passwords to be generated
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
     
  return $passwords; // return the generated password
}
 

?>
