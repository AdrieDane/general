<?php
//require_once 'vendor/autoload.php';

/**
 * $file: character separated text file
 * returns delimiter
 */
function detectDelimiter($file)
{
  $delimiters = array(";" => 0, "," => 0, "\t" => 0, "\|" => 0);

  $str=file_get_contents($file);
  
  foreach ($delimiters as $delimiter => &$count) {

    preg_match_all("/".$delimiter."/", $str, $output_array);
    $count = count($output_array[0]);
  }
  
  return array_search(max($delimiters), $delimiters);
}

/*
  options skiprows: integer
*/


class importtable extends datatable
{
  use Optionsarray;

  public static $rename = array(); # Static class variable.
  public static $numcol = array(); # Static class variable.
  public static $reqcol = array(); # Static class variable.

  public function __construct($file=NULL,$user_options=array(),$con=NULL,$table_class=NULL) 
  {

    // print_r($con);
    
    if(is_null($file))	{
      //      $this->data=array();
      return;
    }

    // the $table_class argument is just a shortcut for setting this option
    if(!is_null($table_class))	{
      $user_options['table_class']=$table_class;
    }

    $opts=['importtableId' => 0,
           'table_class' => 'default',
           'file_type' => 'csv',
           'delim' => '',
           'skip_empty_header' => true,
           'infile_order' => false,
           'sheet' => '',
           'skiprows' => 0,
           'skipemptyrows' => true,
           'rename' => [],
           'numeric' => [],
           'required' => [],
           'datecol' =>[],
           'sql' => false];

    $opts=$this->useroptions($opts,$user_options);

    $file = $this->set_file_info($file,$opts);

    $db_opts = $this->db_options($con,$opts['table_class']);
    //    exit('test0');
    //    pre_r($opts,'$opts');


    $opts=$this->useroptions($opts,$db_opts);
    //    pre_r($opts,'$opts**');

    /*    echo "statics<br>";
          echo "numeric";
    
          print_r(static :: $numcol);
          echo "rename";
          print_r(static :: $rename);
          echo "statics<br>";
    */


    extract($opts);
    //    pre_r($opts);
    
    $this->date=$this->file_info['date'];

    //    pre_r($this->file_info,'$this->file_info');
    

    if($opts['file_type']=='xlsx')	{

      $excelsheet = new Excelsheet($file,['sheet' => $opts['sheet']]);
      $importtable = $excelsheet->data();
      // pre_r($importtable,'importtable');
      if(empty($opts['sheet']))	{
        $opts['sheet']=$excelsheet->name();
      }
    } else {
      $delim=$opts['delim'];
      if(empty($delim) | is_null($delim))	{
        $delim=detectDelimiter($file);
      }
      $importtable = array_map(function($a) use ($delim) {return str_getcsv($a,$delim);}, file($file));
      $opts['delim']=$delim;
    }
    //        $this->print('sheet');

    // remove leading and trailing spaces
    foreach($importtable as &$row) {
      $row = array_map('trim',$row);
    }
    unset($row);
    
    if(isset($opts['skiprows']) && $opts['skiprows']>0)	{
      $importtable=array_slice($importtable,$opts['skiprows']);
    }

    // header must have at least half of the cells filled
    // this skips the rows where only one or two cells are filled
    $start=0;
    foreach($importtable as &$row) {
      $hdr=array_filter($row);
      if(count($hdr)<0.5*count($row))	{
        $start +=1;
      } else {
        break;
      }
    }
    unset($row);
    for(	$i=0;	$i<$start;	$i++)	{
      array_shift($importtable);
    }

    // remove empty rows
    if($opts['skipemptyrows']==true)	{
      $importtable=array_filter($importtable, function ($a) { return !empty(array_filter($a));});
    }

    // remove columns with empty headers
    if($skip_empty_header)	{
      $empty=array_keys(array_filter($importtable[0], function ($a) { return empty($a);}));
      foreach($importtable as &$row) {
        foreach($empty as $idx) {
          unset($row[$idx]);
        }
      }
      unset($row);
    }

    // store the original header
    $this->head=$importtable[0];
    
    // check whether all required columns are present
    if(isset(static :: $reqcol))	{
      foreach(static :: $reqcol as $col) {
        if(!in_array($col,$this->head))	{
          if(!isset($this->absent))	{
            $this->absent=array();
          }
          $this->absent[]=$col;
        }
      }
    }
    if(isset($this->absent))	{
      //      $this->print('file_info');
      $nabsent=count($this->absent);
      $err_msg='<b>Error reading: '.$this->file_info['basename']."</b>";
      $err_msg .= $nabsent==1 ? 
               "<br>Column: '".$this->absent[0]."' is" :
               "<br>Columns: '".implode("', '",$this->absent) .
               "' are";
      $err_msg .= " not present.<br><br>Please correct<br>Make sure spelling and case are correct";
      exit($err_msg);
    }
    
    
    // rename headers
    if(isset(static :: $rename))	{
      foreach($importtable[0] as &$head) {
        if(array_key_exists($head,static :: $rename))	{
          $head=static :: $rename[$head];
        }
      }
      unset($head);
    }

    // determine which columns must be treated as numerical
    if(isset(static :: $numcol))	{
      $numcol=static :: $numcol;
    } else {
      $numcol=array();
    }
    
    if(isset($numeric))	{
      foreach($importtable[0] as &$head) {
        foreach($numeric as $field) {
          if(!(strpos($head,$field)===FALSE))	{
            array_push($numcol,$head);
          }
        }
      }
      unset($head);
    }


    // this turns the importtable with numerical indices into an associative array
    array_walk($importtable, function(&$a) use ($importtable) {
      $a = array_combine($importtable[0], $a);
    });
    array_shift($importtable); # remove column header;

    // walking through the complete 2 dimensional array
    $i=1;
    foreach($importtable as &$x) {
      if($opts['infile_order']==TRUE)	{
        $x['infile_order']=$i++;
      }
      foreach($numcol as $field) {
        if(isset($x[$field]))	{
          $x[$field]=(float) $x[$field];
        }
      }
      // date columns
      foreach($datecol as $field) {
        if(isset($x[$field]) && is_numeric($x[$field]) && $x[$field]>0)	{
          $x[$field]=date('Y-m-d',Excelsheet::timestamp($x[$field]));
        } elseif($opts['sql']==true) {
          $x[$field]='9999-12-31';
        }          
      }
    }
    unset($x);
    
    $this->opts=$opts;
    //    $this->nrows=count($importtable);
    $this->ncols=empty($importtable) ? 0 : count($importtable[0]);
    $this->data=$importtable;
  }

/*    Title: 	db_options
      Purpose:	read options from database
                more options when $table_class is set
      Created:	Sun Apr 11 09:01:42 2021
      Author: 	Adrie Dane
*/
function db_options($con,$table_class)
{

  if(is_null($con))	{
    return [];
  }

  $query="SELECT * FROM `importtable` WHERE `table_class`='$table_class'";
  $opts=$con->query($query);
  //  pre_r($opts,'$opts');
  $importtableId=$opts['importtableId'];


  if(empty(static :: $numcol) && !empty($importtableId))	{
      
    $query="SELECT `header`,`map` FROM `importhead` WHERE `importtableId`='$importtableId' AND NOT `header`=''";
    $result=$con->query($query);
    //    pre_r($result,'$result');
    $rename=array();
    foreach($result as $map) {
      $rename[$map['header']]=$map['map'];
    }
    if(!empty($rename))	{
      $opts['rename']=$rename;
      static :: $rename = $rename;
    }
      
    $query="SELECT `map` FROM `importhead` WHERE `importtableId`='$importtableId' AND `num_col`=TRUE";
    $result=$con->query($query,['single_row' => false]);
    $num_cols=array();
    foreach($result as $num) {
      array_push($num_cols,$num['map']);
    }
    if(!empty($num_cols))	{
      $opts['numeric']=$num_cols;
      static :: $numcol = $num_cols;
    }

    $query="SELECT `header` FROM `importhead` WHERE `importtableId`='$importtableId' AND `req_col`=TRUE";
    $result=$con->query($query,['single_row' => false]);
    $req_cols=array();
    foreach($result as $req) {
      array_push($req_cols,$req['header']);
    }
    if(!empty($req_cols))	{
      $opts['required']=$req_cols;
      static :: $reqcol = $req_cols;
    }
  }

  //  pre_r($opts,'$db opts');

  return $opts;
} /* db_options */

/*    Title: 	set_file_info
      Purpose:	sets $this->fileinfo and returns $file
      Created:	Sun Apr 11 09:46:21 2021
      Author: 	Adrie Dane
*/
 function set_file_info($file,&$opts)
{
    //    pre_r($file,'$file');
    if(is_array($file))	{
      // file was an upload
      $file_info=pathinfo($file['name']);
      $file_info['tmp_name']=$file['tmp_name'];
      $file=$file['tmp_name'];
    } else {
      $file_info=pathinfo($file);
      // file is an url
      if(filter_var($file_info['dirname'], FILTER_VALIDATE_URL))	{
	if(!filter_var($file, FILTER_VALIDATE_URL))	{
	  exit("$file is not an url");
	}
	$curl = curl_init($file);
	//don't fetch the actual page, you only want headers
	curl_setopt($curl, CURLOPT_NOBODY, true);
	//stop it from outputting stuff to stdout
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

	// attempt to retrieve the modification date
	curl_setopt($curl, CURLOPT_FILETIME, true);

	$result = curl_exec($curl);

	if ($result === false) {
	  die (curl_error($curl)); 
	}

	$file_info['size'] = curl_getinfo($curl, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
	$file_info['date'] = curl_getinfo($curl, CURLINFO_FILETIME);
	curl_close($curl);

      }	elseif(file_exists($file))	{	
	  // file is a normal file
	  $file_info['size']=filesize($file);
	  $file_info['date']=filemtime($file);
      } else {
	exit("ERROR IN importtable: <br>\nFILE: '$file' does not exist.");
      }
    }
    if(!isset($file_info['date']))	{
      if($file_info['extension']=='php')	{
	$file_info['date']=time();
      }
      else {
	$file_info['date']=filemtime($file);
      }
    }
    $file_info['datestr']=gmdate("Y-m-d H:i:s", date($file_info['date']));

    if($opts['table_class']=='default')	{
      if(in_array($file_info['extension'],array('xlsx','xlsm')))	{
	$opts['table_class']='xlsx_default';
	$opts['file_type']='xlsx';
      }
    }

    $this->file_info=$file_info;
    return $file;
} /* set_file_info */



  /*
  function append($table2) 
  {
    $this->data=array_merge($this->data,$table2->data);
    $this->nrows=count($this->data);
  }
  */

  function filter($fun,$in_place=FALSE,$reset_keys=TRUE) 
  {
    if($in_place)	{
      $a=&$this;
    } else {
      $a= clone $this;
    }
    
    $a->data=array_filter($this->data,$fun);
    if($reset_keys)	{
      $a->data=array_values($a->data);
    }
    //    $a->nrows=count($a->data);

    return $a;
  }

  /*
  function group_by($key,$by_reference=TRUE,$field='data') 
  {
    $result = array();

    if(!is_array($key))	{
      $key=array($key);
    }

    switch(count($key))	{
    case 1:	$key=$key[0];
      if($by_reference==TRUE)	{
	foreach ($this->$field as &$x) {
	  $result[$x[$key]][] = $x;
	}
      } else {
	foreach ($this->$field as &$x) {
	  $result[$x[$key]][] = $x;
	}
      }
      break;
    case 2:	;
      if($by_reference==TRUE)	{
	foreach ($this->$field as &$x) {
	  $result[$x[$key[0]]][$x[$key[1]]][] = $x;
	}
      } else {
	foreach ($this->$field as &$x) {
	  $result[$x[$key[0]]][$x[$key[1]]][] = $x;
	}
      }
      break;
    case 3:	;
      if($by_reference==TRUE)	{
	foreach ($this->$field as &$x) {
	  $result[$x[$key[0]]][$x[$key[1]]][$x[$key[2]]][] = $x;
	}
      } else {
	foreach ($this->$field as &$x) {
	  $result[$x[$key[0]]][$x[$key[1]]][$x[$key[2]]][] = $x;
	}
      }
      break;


    default: echo "ERROR importtable group_by maximum 3 levels<br>";
      $result=NULL;
      
    }
      
    return $result;
  }
  */

  function last_update()
  {
    return date("F d Y H:i:s", $this->date);
  }

  function subclass()
  {
    ;
  }
  


}

?>
