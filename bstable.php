<?php
require_once 'vendor/autoload.php';



class bstable extends datatable
{
  
  public function __construct($data) 
  {
    parent::__construct($data);
    //    $this->ncols=count($this->data[0]);
    $this->_data=$data;
  }

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
      $i=0;
      foreach($this->data as &$x) {
	$x[$hdrs[$key]]=$values[$i];
	$i++;
      }
    }
  }
  return $this->data;
} /* update_data */


  /*    Title: 	set_inputs
      Purpose:	bootstrap form for editting
      Created:	Mon Jan 18 15:46:49 2021
      Author: 	Adrie Dane
*/
  function set_inputs($types=array())
  {
    $arr=array();
    $hdrs=array();
 
    foreach($types as $field => &$type) {
      // create dropdown
      if($type=='select')	{
	$vals=array_unique(array_column($this->data,$field));
	$type=$vals;
      }
    }

    foreach($this->data as &$x) {
      // do label columns first
      foreach($types as $field => &$type) {
	if(!is_array($type) && $type=='label')	{
	  $x[$field]="<b>".$x[$field]."</b>";
	  $hdrs[]=$field;
	}
      }
      foreach($types as $field => &$type) {
	if(is_array($type))	{
	  $str = "<select name='".$field."[]'>";
	  foreach($type as $value) {
	    $str .= "<option value='$value'";
	    $str .= $value==$x[$field] ?
	      " selected>" : ">";
	    $str .= "$value</option>";
	  }
	  $x[$field] = $str;
	  $hdrs[]=$field;
	}
	elseif($type!='label')	{
	  $value=$x[$field];
	  $x[$field] = "<input type='$type' name='".$field."[]' value='$value'></input>";
	  $hdrs[]=$field;
	}
      }
    }
    $keys=str_replace(' ','_',$hdrs);
    $this->hdrs=array_combine($keys,$hdrs);
  } /* set_inputs */

  


  function bootstraptable($id="table")
  {
    $table = <<<_TABLE

<small>
<table id='$id' class="table table-sm">
  <thead>
    <tr>
_TABLE;

    foreach(array_keys($this->data[0]) as $column) {
      //      $col=str_replace(' ','_',$column);
      $col=$column;
      $table .= "\n      <th data-field='$col'>$column</th>";
    }
    $table .= <<<_TABLE
    </tr>
  </thead>
</table>
</small>
<script>

_TABLE;

    $table .= '  var $table = $('."'#$id')\n";

    $table .= <<<_TABLE

  $(function() {
    var data = 
_TABLE;
    
    $table .= $this->json(TRUE)."\n    ";
    
    $table .= '$table.bootstrapTable({data: data})';

    $table .= <<<_TABLE

  })
</script>

_TABLE;

    return $table;
  }
}
?>