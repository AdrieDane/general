<?php
/*
  html bootstrap table interface
  $options
  - 'small' [true|false]
  use small fonts
  - 'header' true|false
  skip header row
  - 'hide_column' array('key','tooltip','td','required')
*/
class Bstable extends datatable
{
  use ValidateColumn;

  public function __construct($data,$options=[]) 
  {
    $opts=['small' => true,
           'header' => true,
           'hide_column' => [],
           'show_column' => [],
           'id' => 'table',
           'cls' => 'table-sm table-hover',
           'column_width' => [],
           'validate' => [],
           'controls' => []];
  
    parent::__construct($data);
    //    $this->ncols=count($this->data[0]);

    //  $this->_data=array();
    if(!empty($data))	{
      $hdrs=array_keys(array_values($data)[0]);
      $this->hdrs=array_combine(str_replace(' ','_',$hdrs),$hdrs);
    } else {
      $this->hdrs=array();
    }
    $this->options=useroptions($opts,$options);
    $this->init_cells();
    if(isset($this->options['controls']))	{
      $this->set_inputs($this->options['controls']);
    }
    if(isset($this->options['validate']))	{
      $this->validate=$this->options['validate'];
    }
    // init validation
    $this->validate_all();
    $this->data_only=false;
    $this->set_column_options();
  }

  /*    Title: 	init_cells
        Purpose:	
        Created:	Sat May 01 08:45:08 2021
        Author: 	Adrie Dane
  */
  function init_cells()
  {
    $opts=['hide_column' => [],
           'show_column' => [],
           'column_width' => []];
  
    $opts=useroptions($opts,$this->options);

    extract($opts);
    $cols = array_keys(reset($this->data));
    if(!empty($hide_column))	{
      $cols = array_diff($cols,$hide_column);
    }
    if(!empty($show_column))	{
      $cols = array_intersect($cols,$show_column);
    }
  
    $this->cells=array();
    foreach($this->data as $row => $values) {
      foreach($values as $key => $value) {
        $cell_options=[];
        if(!in_array($key,$cols))	{
          $cell_options['hideoutput']=true;
        }
        if(in_array($key,array_keys($column_width)))	{
          $cell_options['width']=$column_width[$key];
        }
        $this->cells[$row][$key]=new Bscell($value,$cell_options);
      }
    }
    /*  pre_r($opts,'$opts');
        pre_r($this->cells,'$this->cells');
        exit; */
  } /* init_cells */

  /*    Title: 	set_column_options
        Purpose:	
        Created:	Sun May 02 11:53:27 2021
        Author: 	Adrie Dane
  */
  function set_column_options($options=[])
  {
    if(empty($options))	{
      $options = isset($this->options) ? $this->options : [];
    }
    if(empty($options))	{
      return;
    }

    $opt=array_filter(useroptions(['hide_column' => [],
                                   'show_column' => []],$options));

    if(count($opt)>1)	{
      exit("Bscell option must either be 'hide_column' or 'show_column' not both in set_column_options");
    }

    $key=key($opt);
    $vals=current($opt);
    if(!is_array($vals))	{
      $vals=[$vals];
    }
    $columns = $key=='hide_column' ?
             array_diff($this->hdrs,$vals) :
             $this->hdrs;

    foreach($this->cells as $row => &$cell) {
      foreach($this->hdrs as $col) {
        if(in_array($col,$columns))	{
          if(isset($cell[$col]->hideoutput))	{
            unset($cell[$col]->hideoutput);
          }
        } else {
          $cell[$col]->hideoutput=true;
        }
      }
    }
    unset($cell);

  } /* set_column_options */



  /*    Title: 	set_inputs
        Purpose:	
        Created:	Sat May 01 09:00:06 2021
        Author: 	Adrie Dane
  */
  function set_inputs($column_options=[])
  {
    if(!empty($column_options) || !isset($this->options['controls']))	{
      $this->options['controls']=$column_options;
    }
    foreach($this->options['controls'] as $column => $control_type) {
      foreach($this->cells as $row => &$cells) {
        $cells[$column]->set_controltype($control_type,array_column($this->data,$column));
      }
    }
  } /* set_inputs */

  /*    Title: 	set_style
        Purpose:	set style of individual cell
        Created:	Mon Jun 14 11:01:56 2021
        Author: 	
  */
  function set_style($row,$key,$kv=[])
  {
    foreach($kv as $style => $value) {
      if($style == "warning")	{
        $this->cells[$row][$key]->warning=[$value => ''];
        $this->cells[$row][$key]->set_color();
      }
      if($style == "color")	{
        //        echo "style: $style, value: $value<br>";
        $this->cells[$row][$key]->set_color($value);
      }
      if($style == "hidevalue")	{
        $this->cells[$row][$key]->hidevalue=$value;
        if(is_string($value))	{
          $this->cells[$row][$key]->set_color($value);
        }
        
      }
    }
  } /* set_style */


  /*    Title: 	update_data
        Purpose:	
        Created:	Mon Jan 18 17:27:30 2021
        Author: 	Adrie Dane
  */
  function update_data($post)
  {
    $hdrs=$this->hdrs;
    $data_keys=array_keys($hdrs);
    foreach($post as $key => $values) {
      if(in_array($key,$data_keys))	{
        foreach($values as $row => $value) {
          $this->data[$row][$hdrs[$key]]=$value;
        }
      }
    }
    $this->init_cells();
    $this->set_inputs();
    $this->validate_all();
  
    if(isset($post['data_only']))	{
      $this->data_only=true;
    } elseif(isset($post['data_edit']))	{
      $this->data_only=false;
    }

    //  $this->_data=[];
    //  pre_r($this->data,'updated $data');
    //  exit;
  
    return $this->data;
  } /* update_data */

  /*    Title: 	htmltable
        Purpose:	
        Created:	Tue Feb 02 17:31:11 2021
        Author: 	Adrie Dane
  */
  function html()
  {
    // return pre_r(array_column($this->cells,'Sample Name'),'$this',true);
    //    pre_r($this->data,'data----');
    //    exit;
    extract($this->options);

    $str='';

    if(isset($this->validate) && !empty($this->validate))	{
      $html=[];
      foreach($this->validate as &$val) {
        if(isset($val['html']))	{
          $html=array_merge($html,$val['html']);
          unset($val['html']);
        }
      }
      $html = array_filter($html);
      if(!empty($html))	{
        $str .= "<h2>Data validation results</h2>";
        /* $str .= "<span class='text-danger'>Errors</span> and/or <span class='text-warning'>Warnings</span> were found \n".
             "and/or <span class='text-success'>Automatic Corrections</span> were carried out.<br>\n".
             "Pressing the top left Data button(s) will accept the automatic and manual corrections made in the table below. \n".
             "The errors must be fixed. In some cases that can be done in the table. In other cases the corrected data needs to be reloaded."; */
        $str .= "\n<ul>\n<li>".implode("\n<li>",$html)."\n</ul>\n<hr>";
      }
    }


    // No create the table
    $str .= $small==true ? "<small>\n" : "";
    $str .= "<div id='toolbar'>";
    if($this->data_only==true)	{
      $str .= "<input type='submit' value='Edit Data' name='data_edit' class='btn btn-secondary btn-sm'><br><br>\n";
    }
    else	{
      $str .= "<input type='submit' value='Data Only' name='data_only' class='btn btn-secondary btn-sm'>\n";
      $str .= "<input type='submit' value='Accept Changes' name='data_update' class='btn btn-secondary btn-sm'><br><br>\n";
    }
    $str .= "</div>";
  
    $str .= "<table data-toggle='table' data-toolbar='#toolbar'  data-search='true'  id='$id' class='table $cls' 
  data-show-toggle='true' data-show-columns='true'  data-silent-sort='false'
  data-show-fullscreen='true' 
  data-show-pagination-switch='true'
  data-show-toggle='true'>\n";
    //  data-sort-class='table-active'
    //  data-pagination='true'
    //  data-show-export='true'
    // return pre_r($this->cells,'$this->cells');

    //  pre_r($this->cells,'$cell');
    //  exit;
    if($header==true)	{
      $str.= "  <thead>\n";
      $str.= "    <tr>\n";
      $str.= "      ";
      $first_row = reset($this->cells);
      foreach($first_row as $key => $cell) {
        $str .= $cell->html($key,key($first_row),['head' => true]);
      }
      $str.= "    </tr>\n";
      $str.= "  </thead>\n";
    }
    $str.= "  <tbody>\n";
    foreach($this->cells as $row => $x) {
      $str.= "    <tr>\n";
      foreach($x as $key => $cell) {
        $str .= $cell->html($key,$row,['data_only' => $this->data_only]);
      }
      $str.= "    </tr>\n";
    }
    $str.= "  </tbody>\n";
    $str.= "</table>\n";


    $str .= $small==true ? "</small>\n" : "";

  
    return $str;
  }

}
?>
