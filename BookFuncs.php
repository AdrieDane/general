<?php
trait BookFuncs
{
  
  /*    Title: 	html
        Purpose:	show workbook in html
        Created:	Thu Mar 30 11:33:43 2023
        Author: 	
  */
  function html($options=[])
  {
    $opts = useroptions(['onchange' => '',
                         'selected' => ''], $options);
    $selected = empty($opts['selected'])
              ? $this->sheet
              : $opts['selected'];
    
    $sheets = $this->sheets();
    $str='';

    $str .= "<div class='input-group'>\n";
    if(count($sheets)>1)	{
      $str .= "<span class='input-group-text' id='basic-addon1'>Sheet:</span>\n";
      $i=1;
      //$str .= "<div id='shaccordion'>\n";
      foreach($sheets as $sh) {
        $tf = $sh==$selected ? 'true' : 'false';
        $checked  = $sh==$selected ? ' checked' : '';
        $id="sheet$i";
        $onchange='';
        if(!empty($opts['onchange']))	{
          $rng=implode(':',$this->range[$sh]);
          $oc = $opts['onchange'];
          $onchange = " onchange='$oc(\"$sh\",\"$rng\")'";
        }
        //    $str .= "<button class='btn btn-primary' type='button' data-bs-toggle='collapse' data-bs-target='#sheet$i' aria-expanded='$tf' aria-controls='sheet$i'>$sh</button>\n";
        $str .= "<input type='radio' class='btn-check' data-bs-toggle='collapse' ".
             "data-bs-target='#sheet$i' aria-expanded='$tf' name='setsheet' id='sheet$i' ".
             "value='$sh' aria-controls='$id'$onchange$checked>\n";
        $str .= "<label class='btn btn-outline-secondary' for='$id'>$sh</label>\n";

        $i++;
      }
    }else	{
      //      $sh = reset($sheets);
      $str .= "<input type='hidden' name='setsheet' id='setsheet' class='form-control' value='$selected' readonly>";
    }
    $rng=implode(':',$this->range[$selected]);
    $str .= "<span class='input-group-text' id='basic-addon1'>Range:</span>\n";
    $str .= "<input type='text' id='selectedrange' name='selectedrange' class='form-control' ".
         "value='$rng' placeholder='A1:Z100'>";
    $str .= "<input type='submit' value='OK' class='btn  btn-primary' name='Select Range'>";
    $str.="</div>\n";
    $i=1;
    $str .= "<div class='accordion' id='workbook'>\n";
    foreach($sheets as $sh) {
      $show = $sh==$selected ? ' show' : '';
      // $show ='';
      $str .= "  <div class='accordion-item'>\n";
      $str .= "    <div id='sheet$i' class='accordion-collapse collapse$show' data-bs-parent='#workbook'>\n";
      $str .= "      <div class='accordion-body'>\n";
      $str .= $this->sheet_html($sh);

      // $str .= "$i\n";
      $str .= "      </div>\n";
      $str .= "    </div>\n";
      $str .= "  </div>\n";
      $i++;
    }
    $str.="</div>\n";
    //$str.="</div>\n";
    return $str;
  } /* html */


  
  /*    Title: 	sheet_html
        Purpose:	show sheet
        Created:	Thu Mar 30 11:06:59 2023
        Author: 	
  */
  function sheet_html($sheet='')
  {
    $this->set_sheet($sheet);
    if(isset($this->data) && is_array($this->data))	{
      $data = $this->data[$this->sheet];
    }else	{
      $data = $this->data();
    }
  
    //    $data=[];
    //    pre_r(range(1,10),'rng1');
    //    pre_r(range(1,count(reset($data))+1),'rng');
    $hdrs = array_map('self::column_convert',range(1,count(reset($data))));
    $cls="table w-auto";
    $str='';

  
    //  <button class="btn btn-primary" type="button" data-toggle="collapse" data-target=".multi-collapse" aria-expanded="false" aria-controls="multiCollapseExample1 multiCollapseExample2">Toggle both elements</button>

    $str.= "<table class='$cls'>\n";
    $str.= "  <thead class='bg-light sticky-top'>\n";
    $str.= "    <tr>\n";
    $str.= "      <th  scope='col'></th>\n";
    $str.= "      <th  scope='col'>";
    $str.= implode("</th>\n      <th  scope='col'>",$hdrs);
    $str.= "</th>\n";
    $str.= "    </tr>\n";
    $str.= "  </thead>\n";

    $str.= "  <tbody>\n";
    $i=1;
    foreach($data as $x) {
      $str.= "    <tr>\n";
      $str.= "      <td class='bg-light'><b>$i</b></td>\n";
      $str.= "      <td>";
      $str.= implode("</td>\n      <td>",$x);
      $str.= "</td>\n";
      $str.= "    </tr>\n";
      $i++;
    }
    $str.= "  </tbody>\n";


    $str.= "</table>\n";

    return $str;

    
  } /* html */
}
?>
