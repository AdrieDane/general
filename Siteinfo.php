<?php

/*
    Title: 	Siteinfo.php
    Purpose:	Siteinfo class helps to get Full URL & URL Parts
                inspired by: https://code-boxx.com/php-url-parts/
    Created:	Fri Apr 09 07:42:16 2021
    Update:	Fri Apr 09 07:42:16 2021
    Author: 	Adrie Dane
		<addane@amsterdamumc.nl>
*/

class Siteinfo
{
/*    Title: 	__construct
      Purpose:	getting and setting configuration
      Created:	Mon Mar 15 11:26:24 2021
      Author: 	Adrie Dane
*/
function __construct($start_dash=false)
{
  $this->protocol=isset($_SERVER['HTTPS']) ? "https://" : "http://";
  $this->host=$_SERVER['HTTP_HOST'];
  $this->port=$_SERVER['SERVER_PORT'];
  $this->request = $start_dash==true ? $_SERVER['REQUEST_URI'] : substr($_SERVER['REQUEST_URI'],1);
  if($this->host=='localhost')	{
    $parts = explode('/',$this->request);
    if($start_dash==true)	{
      array_shift($parts);
    }
    $this->host .= '/'.array_shift($parts);
    //    pre_r($parts);
    $this->request = $start_dash==true ? '/'.implode('/',$parts) : implode('/',$parts);
  }
  $this->query_string=$_SERVER['QUERY_STRING'];
  $this->domain=$this->protocol.$this->host;

  // SET THE PORT ONLY IF IT IS NOT HTTP/HTTPS
  if ($_SERVER['SERVER_PORT']!=80 && $_SERVER['SERVER_PORT']!=443) {
    $this->domain .= ":" . $_SERVER['SERVER_PORT'];
  }
  $this->site = $this->domain.'/';
  $this->url=$this->site.$this->request;
  $this->strip=strtok(strtok($this->url, '?'), '#');

  // PATH AND FILE NAME
  $this->filepath = strtok($this->request, '?');
  $this->filepath = strtok($this->filepath, '#');
  // FILE NAME ONLY
  $this->file = basename($this->request, '?'.$this->query_string);
  $this->file = strtok($this->file, '#');
  $this->filepath = strtok($this->request, '?');
  $this->filepath = strtok($this->filepath, '#');
  $this->path = pathinfo($this->request, PATHINFO_DIRNAME);
  $this->depth = count(explode('/',$this->path));
  if($start_dash==false)	{
    $this->path .= '/';
  }
  $this->to_site = implode('/',array_fill(0,$this->depth,'..')).'/';
  $this->hash = parse_url($this->request, PHP_URL_FRAGMENT);
  }


/*    Title: 	download_link
      Purpose:	returns downloadlink for temp file
      Created:	Thu Apr 08 17:20:09 2021
      Author: 	Adrie Dane
*/
function download_link($file)
{
  $url=$this->site."general/download.php?file=".rawurlencode($file);
  return "<a href='$url'>$file</a>";
} /* download_link */

/*    Title: 	relative_path
      Purpose:	
      Created:	Thu Apr 08 17:51:50 2021
      Author: 	Adrie Dane
*/
function relative_path($file)
{
  return $this->to_site.$file;
} /* relative_path */

/*    Title: 	request_method
      Purpose:	returns $_SERVER["REQUEST_METHOD"]
      Created:	Thu Apr 15 10:46:15 2021
      Author: 	Adrie Dane
*/
function request_method()
{
 return $_SERVER["REQUEST_METHOD"];
} /* request_method */

}


?>

