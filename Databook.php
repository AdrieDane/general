<?php
class Databook
{
  use BookFuncs;

  /*    Title: 	set_sheet
      Purpose:	
      Created:	Fri Mar 31 12:18:20 2023
      Author: 	
*/
function set_sheet($sheet='')
{
  if(empty($sheet))	{
    return;
  }
  if(is_numeric($sheet))	{
    $sheets=$this->sheets();
    $this->sheet=$sheets[$sheet];
  }else	{
    $this->sheet=$sheet;
  }
} /* set_sheet */

  /*    Title: 	column_convert
        Purpose:	converts between column number and column letter
        Created:	Fri Apr 02 11:15:58 2021
        Author: 	Adrie Dane
  */
  public static function column_convert($c,$options=[])
  {
    $opts=['uppercase' => true,
           'base' => 1];
    if(!empty($options))	{
      foreach($options as $key => $value) {
        $opts[$key]=$value;
      }
    }

    extract($opts);
  
    $A=ord('A');

    if(is_numeric($c))	{
      $c = intval($c)-$base+1;
    
      if ($c <= 0) return '';

      $letter = '';
      while($c != 0){
        $p = ($c - 1) % 26;
        $c = intval(($c - $p) / 26);
        $letter = chr($A + $p) . $letter;
      }
      return $uppercase==true ? $letter : strtolower($letter);
    } else {
      // subtract 1
      $A -= 1;
      $pow=0;
      $num=0;
      //pre_r($c,'$c');
      $chars=array_reverse(str_split(strtoupper($c)));
      foreach($chars as $char) {
        $num += (ord($char)-$A)*pow(26,$pow);
        $pow++;
      }
      return $num+$base-1;
    }
  

  } /* column_convert */

  /*    Title: 	range_to_parts
        Purpose:	
        Created:	Thu Jun 23 17:34:16 2022
        Author: 	
  */
  public static function range_to_parts($range,$options=[])
  {
    $opts = useroptions(['base' => 0],$options);
    $arr=['range' => $range,
          'sheet' => '',
          'col' => [],
          'row' => []];
    
    $parts=explode('!',$range);
    if(count($parts)==2)	{
      $arr['sheet']=$parts[0];
      $range=$parts[1];
    }else	{
      $arr['sheet']=$this->sheet;
    }
    preg_match_all('/[A-Z]+/i', $range, $col);
    $col=$col[0];
    preg_match_all('/\d+/', $range, $row);
    $row=$row[0];
    if($opts['base'] != 1)	{
      $correct = $opts['base']-1;
      foreach($row as &$r) {
        $r += $correct;
      }
      unset($r);
    }
    foreach($col as &$x) {
      $x=self::column_convert($x,$opts);
    }
    $arr['col'] = count($col)==1 || $col[0]==$col[1]
                ? $col[0]
                : $col;
    $arr['row'] = count($row)==1 || $row[0]==$row[1]
                ? $row[0]
                : $row;

    
    return $arr;
  } /* range_to_parts */

  /*    Title: 	sheets
        Purpose:	
        Created:	Thu Mar 30 14:14:40 2023
        Author: 	
  */
  function sheets()
  {
    return array_keys($this->data);;
  } /* sheets */

  
  public function __construct($data) 
  {
    $this->data = $data;
    $this->range = [];
    foreach($data as $sheet => $x) {
      $this->range[$sheet] = ['A1',
                              self::column_convert(count(reset($this->data[$sheet]))).
                              count($this->data[$sheet])];
    }
    $keys = array_keys($data);
    $this->sheet = reset($keys);
  }

  /*    Title: 	xlget
        Purpose:	read data using excel referencing
        Created:	Thu Mar 30 14:25:35 2023
        Author: 	
  */
  function xlget($range,$options=[])
  {
    $opts = useroptions(['header' => true],$options);
    
    $parts = self::range_to_parts($range);

    $data = $this->data[$parts['sheet']];

    $rowkeys = array_keys($data);
    $colkeys = array_keys(reset($data));

    $r=[];
    for(	$i=$parts['row'][0];	$i<$parts['row'][1]+1;	$i++)	{
      $r[$rowkeys[$i]] = [];
      for(	$j=$parts['col'][0];	$j<$parts['col'][1]+1;	$j++)	{
        $r[$rowkeys[$i]][$colkeys[$j]] = $data[$rowkeys[$i]][$colkeys[$j]];
      }
    }

    //    pre_r($parts);

    if($opts['header']==true)	{
      // this turns the importtable with numerical indices into an associative array
      array_walk($r, function(&$a) use ($r) {
        $a = array_combine(reset($r), $a);
      });
      array_shift($r); # remove column header;
    }
    //    pre_r($r,'r');

    return $r;
  } /* xlget */

}
?>
