<?php
include_once '../general/my_tools.php';

if ($_SERVER["REQUEST_METHOD"] == "GET") {
  $get=clean_post($_GET);
  $out_file=rawurldecode($get['file']);

  //$out_file='19P0066 Testingtemplates.xlsx';
  $file_url=sys_get_temp_dir().'/'.$out_file;
  $mime=mime_content_type($file_url);

  if(0)	{
    echo print_r(['mime' => $mime,
                  'url' => $file_url,
                  'file' => $out_file],'parts');
  } else {
  

    header('Content-Description: File Transfer');
    header("Content-Type: $mime");
    header("Content-disposition: attachment; filename=\"".
           $out_file."\""); 
    header("Content-Transfer-Encoding: Binary"); 
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize( $file_url ) );
    readfile($file_url);
  }
}
?>
