<?php

class Rscript
{
  public function __construct($rscript='')  {
    if(empty($rscript))	{
      if(PHP_OS=='WINNT')	{
        $this->rscript='"C:\Program Files\R\R-4.0.5\bin\Rscript.exe" ';
      }else	{
        $this->rscript='/usr/local/R-4.0.1/bin/Rscript ';
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
  function run($cmd,$args=[],$options)
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
