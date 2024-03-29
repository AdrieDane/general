<?php
#[\AllowDynamicProperties]
class Curlconnect
{
  //  public $api, $cookie_file, $data_field, $result, $endpoint, $json;
  static $verbose = false;
  static $json_decode = true;
  static $keep_json = false;
  static $serialize = false;

  /*    Title: 	__construct
        Purpose:	
        Created:	Sat Jun 19 09:23:19 2021
        Author: 	
  */
  function __construct($api,$options=[])
  {
    
    $opts = useroptions(['cookie' => '',
                         'json_decode' => true,
                         'keep_json' => false,
                         'data' => 'data',
                         'info' => false,
                         'verbose' => false,
                         'serialize' => false],$options);
    
    $this->api= substr($api,-1)=='/' ? $api : $api.'/';
    if(isset($opts['cookie']) && $opts['cookie']==true)	{
      //      $this->cookie_file=sys_get_temp_dir().'/'.$opts['cookie'];
      $this->cookie_file=tempnam(sys_get_temp_dir(), 'cfm_');
    }
    //    self::$json_decode=false;
    if(isset($opts['json_decode']) && $opts['json_decode']==true)	{
      self::$json_decode=true;
    }
    
    if($opts['keep_json'] && $opts['keep_json']==true)	{
      self::$keep_json=true;
    }
    
    if($opts['serialize'] && $opts['serialize']==true)	{
      self::$serialize=true;
    }
    
    $this->data_field='data';
    if(isset($opts['data']) && !empty($opts['data']))	{
      $this->data_field=$opts['data'];
    }
    $this->result=[];
    
    if(isset($opts['info']) && $opts['info']==true)	{
      $this->info=[];
    }    
  } /* __construct */

  /*    Title: 	__destruct
        Purpose:	cleaning
        Created:	Mon Sep  6 13:32:48 2021
        Author: 	
  *
  function __destruct()
  {
    if(isset($this->cookie_file) && file_exists($this->cookie_file))	{
      unlink($this->cookie_file);
      unset($this->cookie_file);
    }
    
  } * __destruct */


  
  /*    Title: 	post
        Purpose:	send data via post
        Created:	Sat Jun 19 09:25:36 2021
        Author: 	
  */
  function post($endpoint,$post,$options=[])
  {
    // (A) INIT CURL
    $ch = curl_init();

    // (B) CURL OPTIONS
    curl_setopt($ch, CURLOPT_URL,$this->api.$endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if(isset($this->cookie_file))	{
      curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
      curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file); 
    }
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));

    // (C) CURL FETCH
    $curl_result = curl_exec($ch);
    if (curl_errno($ch)) {
      // (C1) CURL FETCH ERROR
      echo curl_error($ch);
    } else {
      // (C2) CURL FETCH OK
      if(self::$serialize==true)	{
        // pre_r($curl_result,'$curl_result');
        $decoded=base64_decode($curl_result);
        // pre_r($decoded,'$decoded');
        $this->json = unserialize($decoded);
      }
      if(self::$json_decode==true)	{
        $this->result = json_decode($this->json,true);
      } else	{
        $this->result = $this->json;
      }
      if(self::$keep_json==false)	{
        unset($this->json);
      }
      if(isset($this->info))	{
        $this->info = curl_getinfo($ch);
      }
    }

    // (D) CLOSE CONNECTION
    curl_close($ch);    ;
    return $this->data();
  } /* post */

  /*    Title: 	get_data
      Purpose:	returns maximally decoded and unpacked data
      Created:	Sat Jun 19 10:02:51 2021
      Author: 	
*/
function data()
{
  $field = $this->data_field;
  return isset($this->result[$field]) ? $this->result[$field] : [];
} /* get_data */


}
?>
