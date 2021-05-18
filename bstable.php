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
use Tablecells;
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

}
?>