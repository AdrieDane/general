<?php
class Curlconnect
{
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
                         'info' => false],$options);
    $this->api= substr($api,-1)=='/' ? $api : $api.'/';
    if(isset($opts['cookie']) && !empty($opts['cookie']))	{
      $this->cookie_file=sys_get_temp_dir().'/'.$opts['cookie'];
    }
    $this->decode=false;
    if(isset($opts['json_decode']) && $opts['json_decode']==true)	{
      $this->decode=true;
    }
    
    if($opts['keep_json'] && $opts['keep_json']==true)	{
      $this->keep_json=true;
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
    $this->json = curl_exec($ch);
    if (curl_errno($ch)) {
      // (C1) CURL FETCH ERROR
      echo curl_error($ch);
    } else {
      // (C2) CURL FETCH OK
      // echo $this->json;
      $this->result = $this->decode==true ?
                    json_decode($this->json,true) : $this->json;
      if(!isset($this->keep_json))	{
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
