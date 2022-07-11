<?php
class Excelcom
{
  public function __construct($file=NULL,$options=array())  {
    if(is_null($file))	{
      return [];
    }

    $opts = useroptions(['runmacros' => false,
                         'visible' => false,
                         'sheet' => 1,
                         'verbose' => false],$options);
    $this->file=$file;

    $this->verbose=$opts['verbose'];

    //starting excel
    $this->excel = new COM("excel.application") or die("Unable to instanciate excel");
    if($this->verbose==true)	{
      print "Loaded excel, version {$this->excel->Version}<br>\n";
    }

    //bring it to front
    $this->excel->Visible = $opts['visible']==true ? 1 : 0;//NOT

    //dont want alerts ... run silent
    $this->excel->DisplayAlerts = 0;

    if($opts['runmacros']==false)	{
      // save normal security setting
      $secure = $this->excel->AutomationSecurity;

      // prevent macros on opening
      $this->excel->AutomationSecurity = 3;
    }

    //create a new workbook
    $this->book = $this->excel->Workbooks->Open(str_replace('/','\\',$file));
    if($this->verbose==true)	{
      print "Opened: {$this->file}<br>\n";
    }

    $this->readonly = $this->book->ReadOnly;
    /*
      if($this->readonly==true)	{
      $obj=new COM(Scripting.FileSystemObject);
      pre_r($obj,'$obj');
      }
    */
    
    if($opts['runmacros']==false)	{
      // return to normal security setting
      $this->excel->AutomationSecurity = $secure;
    }
    $this->get_sheets();
    $this->set_sheet($opts['sheet']);
  }

  /*    Title: 	sheets
        Purpose:	returns list of sheetnames
        Created:	Thu Jun 23 10:15:44 2022
        Author: 	
  */
  function get_sheets()
  {
    $this->sheets=[];
    $i=1;
    foreach($this->book->Worksheets as $sh) {
      $this->sheets[$i]=$sh->Name;
      $i++;
    }
    return $this->sheets;
  } /* sheets */

  /*    Title: 	set_sheet
        Purpose:	sets active sheet
        Created:	Thu Jun 23 10:27:17 2022
        Author: 	
  */
  function set_sheet($sheet=1,$options=[])
  {
    $opts=useroptions(['activate' => true],
                      $options);
    if(!is_numeric($sheet))	{
      $sheets=array_flip($this->sheets);
      $sheet=$sheets[$sheet];
    }
    $this->sheet = $this->book->Worksheets($sheet);
    if($opts['activate']==true)	{
      //select the default sheet
      $this->sheet->activate;
    }
    if($this->verbose==true)	{
      print "Activated sheet: {$this->sheet->Name}<br>\n";
    }
    return $this->sheet;
  } /* set_sheet */
  
  /*    Title: 	set_data
        Purpose:	
        Created:	Thu Jun 23 17:12:34 2022
        Author: 	
  */
  function set_data($top_left,$data)
  {
    if(is_string($top_left))	{
      $arr=Excelsheet::range_to_parts($top_left);
      $row=$arr['row'];
      $col=$arr['col'];
    }  elseif(is_array($top_left) && count($top_left)>=2)	{
      $row=$top_left[0];
      $col=$top_left[1];
    }
    if(!isset($this->sheet))	{
      $this->set_sheet();
    }
    $this->sheet->Cells($row,$col)->Value=$data;
    $this->sheet->Cells($row,$col)->Activate;
  } /* set_data */

  /*    Title: 	get_data
        Purpose:	get data from range
        Created:	Thu Jun 23 12:32:17 2022
        Author: 	
  */
  function get_data($range=[],$options=[])
  {
    $opts=useroptions(['crop_empty'=>true,
                       'min_dimensions' => false,
                       'sheet'=>'',
                       'base' => 0],$options);

    $sh = empty($opts['sheet'])
        ? $this->sheet
        : $this->set_sheet($opts['sheet'],
                           ['activate' => false]);

    $rng = empty($range) ? $sh->UsedRange : $sh->Range($range);

    $nrow=$rng->Rows->Count;
    $ncol=$rng->Columns->Count;

    $arr=[];
    $row=0;
    $col=0;
    foreach($rng as $cell) {
      if($col==0)	{
        $arr[$row]=[];
      }
      $val=$cell->Value;
      $arr[$row][$col]=is_object($val) ? '' : $val;
      //  pre_r([$row,$col],'pos');
      if($col==$ncol-1)	{
        $col=0;
        $row++;
      }else	{
        $col++;
      }
    }
    if($opts['crop_empty']==true)	{
      $arr=Excelsheet::crop_empty($arr);
    }
    if($opts['min_dimensions']==true && min([$nrow,$ncol])==1)	{
      $arr=array_merge(...$arr);
      if($nrow == $ncol)	{
        $arr=array_pop($arr);
      }
    }
    
    if(is_array($arr) && $opts['base']>0)	{
      $base=$opts['base'];
      $arr=array_combine(range($base,count($arr)),array_values($arr));
      if(is_array($arr[$base]))	{
        foreach($arr as &$a) {
          $a=array_combine(range($base,count($a)),array_values($a));
        }
      }
    }
    
    return $arr;
  } /* get_data */


  /*    Title: 	Close
        Purpose:	saves file and closes connection
        Created:	Thu Jun 23 09:54:27 2022
        Author: 	
  */
  function close()
  {
    if(is_null($this->excel))	{
      return;
    }
    $this->book->Save();

    //close the book
    $this->book->Close(false);
    $this->excel->Workbooks->Close();

    //closing excel
    $this->excel->Quit();
    
    //free up the RAM
    unset($this->sheet);

    //free the object
    $this->excel = null;
  } /* Close */

}
?>
