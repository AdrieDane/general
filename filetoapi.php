<?php



/*    Title: 	filetoapi
      Purpose:	
      Created:	Mon Jun 28 15:45:33 2021
      Author: 	
*/
function filetoapi($file,$endpoint,$api)
{
  $url=$api.$endpoint;
  $ch = curl_init($url);
  if(is_array($file))	{ // file is a member of $_FILES
    $headers=['Content-Type: '.$file['type'],
              'Content-Disposition: attachment; filename="'.
              urlencode($file['name']).'"'];
    $file=$file['tmp_name'];
  } else {
    $headers=['Content-Type: '.mime_content_type($file),
              'Content-Disposition: attachment; filename="'.
              urlencode(pathinfo($file,PATHINFO_BASENAME)).'"'];
  }
  curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($file));
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $result = curl_exec($ch);
  curl_close($ch);

  return $result;
} /* filetoapi */





?>

