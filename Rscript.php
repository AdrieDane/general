<?php

// Does not support flag GLOB_BRACE
function rglob($pattern, $flags = 0) {
    $files = glob($pattern, $flags); 
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}
class Rscript
{
  public function __construct($rscript='')  {
    if(empty($rscript))	{
      if(isset($_SESSION['rscript']) &&
         !empty($_SESSION['rscript']))	{
        $this->rscript=$_SESSION['rscript'];
      } elseif(PHP_OS=='WINNT')	{
        $startdirs = ['C:/R','C:/Program Files/R'];
        foreach($startdirs as $startdir) {
          $result = rglob($startdir. '/Rscript.exe');
          if(!empty($result))	{
            break;
          }
        }
        if(!empty($result))	{
          $this->rscript=$result[0].' ';
        } else {
          echo("ERROR: cannot locate Rscript.exe");
        }
        
      }else	{
        $this->rscript='/usr/local/R-4.0.1/bin/Rscript ';
      }
      if(isset($_SESSION))	{
        $_SESSION['rscript']=$this->rscript;
      }
    }else	{
      $this->rscript=$rscript;
    }
  }

  /*    Title: 	e
        Purpose:	Run Rscript -e
        Created:	Fri Jan 14 15:40:09 2022
        Author: 	
  */
  function e($cmd,$options=[])
  {
    $opts = useroptions(['numeric' => true],
                        $options);
    $cmd = $this->rscript.'-e "cat('.$cmd.')"';
    //   pre_r($cmd,'$cmd');
    $output=shell_exec($cmd);

    if($opts['numeric']==true)	{
      $output=explode(' ',trim($output));
      $output=array_map('floatval',$output);
    }
    return $output;
  } /* e */

  /*    Title: 	run
        Purpose:	Run Rscript -run
        Created:	Fri Jan 14 15:40:09 2022
        Author: 	
  */
  function run($cmd,$args=[],$options=[])
  {
    if(!empty($args))	{
      $cmd .= ' '.explode(' ',$args);
    }
    $cmd = $this->rscript.'-run "cat($cmd)"';

    $output=shell_exec($cmd);

    return $output;
  } /* run */

  
}
?>
