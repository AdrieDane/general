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
  function __construct($sub_site='cfmetabolomics',$start_dash=false)
  {
    
    $this->site='';
    $this->path='';
    $this->file='';
    if(isset($_SERVER['argv']))	{
      $pth=pathinfo($_SERVER['argv'][0]);
      $this->path=$pth['dirname'];
      $this->file=$pth['basename'];
      return;
    }
    $this->query_string='';
    $this->protocol=isset($_SERVER['HTTPS']) ? "https://" : "http://";
    $this->host=$_SERVER['HTTP_HOST'];
    $this->port=$_SERVER['SERVER_PORT'];
    $this->request = $start_dash==true ? $_SERVER['REQUEST_URI'] : substr($_SERVER['REQUEST_URI'],1);
    //pre_r($_SERVER['HTTP_HOST'],'HTTP_HOST');
    //pre_r($this,'$this');
    //    if($this->host=='localhost')	{
    if(true)	{
      $parts = explode('/',$this->request);
      $nparts=count($parts);

      $idx = array_search($sub_site,$parts);
      if($idx === false)	{
        exit('ERROR Siteinfo() sub_site: $sub_site is not part of request: ' .
             $this->request);
      }

      $this->base_uri=implode('/',array_slice($parts,0,$idx+1)).'/';
      if($start_dash==true)	{
        array_shift($parts);
      }


      $this->request = $idx == $nparts-1 ? '' : implode('/',array_slice($parts,$idx+1));
      if($start_dash == true)	{
        $this->request = '/'.$this->request;
      }
    }
    $this->query_string=$_SERVER['QUERY_STRING'];
    $this->domain=$this->protocol.$this->host;

    // SET THE PORT ONLY IF IT IS NOT HTTP/HTTPS
    if ($_SERVER['SERVER_PORT']!=80 && $_SERVER['SERVER_PORT']!=443) {
      $portext =  ":" . $_SERVER['SERVER_PORT'];
      $nportext =strlen($portext);
      if(strlen($this->domain) > $nportext &&
         strcmp($portext,substr($this->domain, -$nportext)))	{
           $this->domain .= $portext;
      }
    }
    $this->site = $this->domain.'/'.$this->base_uri;
    $this->url=$this->site.$this->request;
    $this->strip=strtok(strtok($this->url, '?'), '#');
    $this->file = basename($_SERVER['SCRIPT_FILENAME']);
  
    // PATH AND FILE NAME
    $this->filepath = strtok($this->request, '?');
    $this->filepath = strtok($this->filepath, '#');
    // FILE NAME ONLY
    //  $this->file = basename($this->request, '?'.$this->query_string);
    //  $this->file = strtok($this->file, '#');
    $this->filepath = strtok($this->request, '?');
    $this->filepath = strtok($this->filepath, '#');
    if(basename($this->filepath)!=$this->file)	{
      $this->filepath .= $this->file;
    }
    $this->path = trim(pathinfo($this->filepath, PATHINFO_DIRNAME));
    //echo "Path: ".$this->path;
    if($start_dash==false)	{
      $this->path .= '/';
    }
    $expl=array_filter(explode('/',$this->path));
    //    $this->explode=pre_r($expl,'expl',true);
    $this->depth = in_array($this->path,['.','./']) ? 0 : count($expl);
    $this->to_site = $this->depth ==0 ? '' : implode('/',array_fill(0,$this->depth,'..')).'/';
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

