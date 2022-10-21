<?php

use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

//class Bscontrols extends Bstable
class Bscontrols extends Bsdata
{
  public function __construct($file='',$options=[]) 
  {
    $opts=['header' => false,
           'small' => false,
           'accept_button' => true,
           'column_width' => [30, 70],
           'show_column' => ['title', 'control'],
           'align' => ['left', 'right'],
           'title_checks' => true,
           //	 'hide_column' => ['key','tooltip','td','value','input',
           //			   'error','warning','auto','type','cell'],
           'form' => true,
           'sheet' => '',
           'skipemptyrows' => false];

    if(!is_array($options) && !empty($options))	{
      $opts['sheet']=$options;
    } else {
      $opts=useroptions($opts,$options);
    }
    //pre_r($file);
    if(is_array($file))	{
      $tbl=new datatable($file);
    } else {
      $tbl = new importtable($file,$opts);
    }
    //     pre_r($tbl,'$tbl');
    // collect hidden controls (move then outside table)
    $data=[];
    $hidden=[];
    $check_present=['unit' => '','error' => '', 'warning' =>'' , 'auto' => '', 'tooltip' =>'', 'control' =>''];
    foreach($tbl->data as $k => $x) {
      foreach($check_present as $field => $value) {
        if(!isset($x[$field]))	{
          $x[$field]=$value;
        }
      }
      if($x['type']=='hidden')	{
        $hidden[]=$x;
      } else {
        if(!isset($x['input']) || empty($x['input']))	{
          $x['input']=$x['type'];
        }
        $data[]=$x;
      }
    }
    
    // move visible columns to the front
    $cols=$opts['show_column'];
    if(isset($cols)&& !empty($cols))	{
      $tmp_data=$data;
      $data=[];
      foreach($tmp_data as &$x) {
        $y=[];
        foreach($cols as $field) {
          $y[$field] = $x[$field];
        }
        foreach(array_diff(array_keys($x),$cols) as $field) {
          $y[$field] = $x[$field];
        }
        $data[]=$y;
      }
    }
    $tbl=new datatable($data);
    
    // pre_r($tbl,'$tbl');
    //exit;
    $keys=array_keys($tbl->data);
    $keys=array_combine($keys,$keys);
    foreach($tbl->data as $k => &$x) {
      // if type is radio: don't change key!! Does this fix always work? It seems so!!
      if(empty($x['key']) || $x['type']=='radio')	{
        $x['td'] = 'th';
      } else {
        $x['td'] = 'td';
        $keys[$k] = $x['key'];
      }
    }
    $tbl->data=array_combine($keys,$tbl->data);
    // pre_r($keys,'$keys');
    // pre_r($opts,'$opts');

    parent :: __construct( $tbl->data,$opts);
    // pre_r($this,'$this');

    $this->set_controls($opts['title_checks']);

    pre_r($this->data,'$tbl');

    $this->hidden=$hidden;
    // pre_r(array_column($this->data,'control'),'Control');
    //  pre_r(array_column($this->data,'warning'),'Warning');
  
  }

  /*    Title: 	set_controls()
        Purpose:	
        Created:	Wed Oct  5 14:21:22 2022
        Author: 	
  */
  function set_controls()
  {
    foreach($this->data as &$x) {
      preg_match('/([\w,]+)\[?(\d*)\]?/', $x['type'], $output_array);
      pre_r($output_array,'$output_array');
      $cols = explode(',',$output_array[1]);
      $ncols=count($cols);
      if($ncols == 1)	{
        $cols = reset($cols);
      }
      pre_r($cols,'$cols');
      $nrows = empty($output_array[2]) ? 1 : intval($output_array[2]);
      
      pre_r($nrows,'$nrows');
      if($nrows==1)	{
        $x['ctrl']=$cols;
      }else	{
        $x['ctrl']=[];
        for(	$i=0;	$i<$nrows;	$i++)	{
          $x['ctrl'][]=$cols;
        }
        
      }
    }
    unset($x);
  } /* set_controls() */

}

?>
