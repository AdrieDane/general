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
           'data_toolbar' => true,
           'button_toolbar' => [],
           'header' => true,
           'hide_column' => [],
           'show_column' => [],
           'data_align' => [],
           'data_sortable' => [],
           'data_formatter' => [],
           'data_title' => [],
           'id' => 'table',
           'cls' => 'table-sm table-hover',
           'column_width' => [],
           'validate' => [],
           'controls' => [],
           'data_only' =>true];

    $this->options=useroptions($opts,$options);

    // set visible columns
    $cols = array_keys(reset($data));
    if(!empty($this->options['hide_column']))	{
      $cols = array_diff($cols,$this->options['hide_column']);
    }
    if(!empty($this->options['show_column']))	{
      $cols = array_intersect($this->options['show_column'],$cols);
    }
    $this->options['visible']=$cols;
    if(!is_array($this->options['data_sortable']))	{
      $this->options['data_sortable'] = $this->options['data_sortable']==true
                                      ? array_fill(0, count($cols), true)
                                      : [];
    }

    // move visible columns to the front
    if(isset($cols)&& !empty($cols))	{
      $tmp_data=$data;
      $data=[];
      foreach($tmp_data as &$x) {
        $y=[];
        foreach($cols as $field) {
          $y[$field] = isset($x[$field]) ? $x[$field] : '';
        }
        foreach(array_diff(array_keys($x),$cols) as $field) {
          $y[$field] = $x[$field];
          //          $y[$field] = $x[$field];
        }
        $data[]=$y;
      }
      unset($x);
    }

    // make sure all columns are present in each member
    
    $tmp_cols = array_map('array_keys',$data);
    $all_cols=array_values(array_unique(array_merge(...$tmp_cols)));
    //    pre_r($all_cols,'$cols');
    foreach($data as &$x) {
      foreach($all_cols as $field) {
        if(!isset($x[$field]))	{
          $x[$field] = '';
        }
      }
    }
    unset($x);
    // exit;
    
    parent::__construct($data);
    //    $this->ncols=count($this->data[0]);

    //  $this->_data=array();
    if(!empty($data))	{
      $hdrs=array_keys(array_values($this->data)[0]);
      $this->hdrs=array_combine(str_replace(' ','_',$hdrs),$hdrs);
    } else {
      $this->hdrs=array();
    }
    
    $this->init_cells();
    if(isset($this->options['controls']))	{
      $this->set_inputs($this->options['controls']);
    }
    if(isset($this->options['validate']))	{
      $this->validate=$this->options['validate'];
    }
    // init validation
    $this->validate_all();
    $this->data_only=$this->options['data_only'];
    // $this->data_only=false;
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
    // pre_r($cols,'$cols');
    $this->cells=array();
    foreach($this->data as $row => $values) {
      foreach($values as $key => $value) {
        $cell_options=[];
        if(!in_array($key,$cols))	{
          $cell_options['hidecolumn']=true;
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
          if(isset($cell[$col]->hidecolumn))	{
            unset($cell[$col]->hidecolumn);
          }
        } else {
          $cell[$col]->hidecolumn=true;
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
      unset($cells);
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
    // exit(pre_r($this->options,'options',true));
    $str='';

    if(isset($this->validate) && !empty($this->validate))	{
      $html=[];
      foreach($this->validate as &$val) {
        if(isset($val['html']))	{
          $html=array_merge($html,$val['html']);
          unset($val['html']);
        }
      }
      unset($val);
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

    $toolbar="";
    if($data_toolbar==true)	{
      $str .= "<div id='toolbar'>";
      if($this->data_only==true)	{
        $str .= "<input type='submit' value='Edit Data' name='data_edit' class='btn btn-secondary btn-sm'><br><br>\n";
      }
      else	{
        $str .= "<input type='submit' value='Data Only' name='data_only' class='btn btn-secondary btn-sm'>\n";
        $str .= "<input type='submit' value='Accept Changes' name='data_update' class='btn btn-secondary btn-sm'><br><br>\n";
      }
      $str .= "</div>";
      $toolbar=" data-toolbar='#toolbar'";
    }
    // exit(pre_r($button_toolbar,'$button_toolbar',true));
    if(!empty($button_toolbar))	{
      $str .= "<div id='button_toolbar'>";
      foreach($button_toolbar as $button => $action) {
        $str .= "<a class='btn btn-outline-primary btn-sm' href='$action' role='button'>$button</a>";
      }
      $str .= "</div>";
      $toolbar=" data-toolbar='#button_toolbar'";
    }

    $str .= "<table data-toggle='table'$toolbar data-search='true'  id='$id' class='table $cls' 
  data-silent-sort='false'
  data-show-fullscreen='true' 
  data-show-columns='true'  
  data-show-toggle='true'
  data-show-pagination-switch='true'>\n";

    //  data-show-columns='true'  
    //  data-show-toggle='true'
    
    //  data-sort-class='table-active'
    //  data-pagination='true'
    //  data-show-export='true'
    // return pre_r($this->cells,'$this->cells');

    //  pre_r($this->cells,'$cell');
    //  exit;
    if($header==true)	{
      $str.= "  <thead>\n";
      $str.= "    <tr>";
      $first_row = reset($this->cells);
      $idx_align=0;
      $idx_sortable=0;
      $idx_title=0;
      foreach($first_row as $key => $cell) {
        $str.= "\n      ";
        
        $visible = in_array($key,$this->options['visible']);
        
        if($visible==true && !empty($data_align) && $idx_align<count($data_align))	{
          $align=$data_align[$idx_align];
          $idx_align++;
        } else {
          $align=[];
        }
        
        if($visible==true && !empty($data_title) && $idx_title<count($data_title))	{
          $title=$data_title[$idx_title];
          $idx_title++;
        } else {
          $title=[];
        }

        if($visible==true && !empty($data_sortable) && $idx_sortable<count($data_sortable))	{
          $sortable=$data_sortable[$idx_sortable];
          $idx_sortable++;
        } else {
          $sortable=[];
        }

        $formatter = in_array($key,array_keys($data_formatter)) ?
                   $data_formatter[$key] :
                   [];
        $cell_options=['head' => true,
                       'visible' => $visible,
                       'align' => $align,
                       'title' => $title,
                       'sortable' => $sortable,
                       'formatter' => $formatter];
        //        pre_r($cell_options);
        $str .= $cell->html($key,key($first_row),$cell_options);
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
