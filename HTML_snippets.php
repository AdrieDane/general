<?php
/*    Title: 	HTML_collapse
      Purpose:	
      Created:	Sun May 30 10:33:27 2021
      Author: 	
*/
function HTML_collapse($id,$intro,$lst,$options=[])
{
  $opts = useroptions(['link_ttl' => 'details',
                       'intro_tag' => 'b',
                       'color' => 'text-primary',
                       'pre_lst' => ''],$options);
  extract($opts);

  $str = "<br>\n";
  $str .= empty($intro_tag) ? "" : "<$intro_tag>";
  $str .= $intro;
  $str .= empty($intro_tag) ? "" : "</$intro_tag>\n";
  $str .= " (<a href='#$id' data-toggle='collapse' class='$color'><b>$link_ttl</b></a>)\n";
  $str .= "<div id='$id' class='collapse'>\n";
  $str .= $pre_lst;
  if(!empty($lst))	{
    $str .= "<ul>\n<li>".implode("'\n<li>'",$lst)."\n</ul>";
  }
  $str .= "<div>";
  return $str;

} /* HTML_collapse */

?>
