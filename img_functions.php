<?php


/**
 * Returns an array of latitude and longitude from the Image file
 * @param image $file
 * @return multitype:number |boolean
 */
function read_gps_location($file){
    if (is_file($file)) {
        $info = exif_read_data($file);
        if (isset($info['GPSLatitude']) && isset($info['GPSLongitude']) &&
            isset($info['GPSLatitudeRef']) && isset($info['GPSLongitudeRef']) &&
            in_array($info['GPSLatitudeRef'], array('E','W','N','S')) && in_array($info['GPSLongitudeRef'], array('E','W','N','S'))) {

            $GPSLatitudeRef  = strtolower(trim($info['GPSLatitudeRef']));
            $GPSLongitudeRef = strtolower(trim($info['GPSLongitudeRef']));

            $lat_degrees_a = explode('/',$info['GPSLatitude'][0]);
            $lat_minutes_a = explode('/',$info['GPSLatitude'][1]);
            $lat_seconds_a = explode('/',$info['GPSLatitude'][2]);
            $lng_degrees_a = explode('/',$info['GPSLongitude'][0]);
            $lng_minutes_a = explode('/',$info['GPSLongitude'][1]);
            $lng_seconds_a = explode('/',$info['GPSLongitude'][2]);

            $lat_degrees = $lat_degrees_a[0] / $lat_degrees_a[1];
            $lat_minutes = $lat_minutes_a[0] / $lat_minutes_a[1];
            $lat_seconds = $lat_seconds_a[0] / $lat_seconds_a[1];
            $lng_degrees = $lng_degrees_a[0] / $lng_degrees_a[1];
            $lng_minutes = $lng_minutes_a[0] / $lng_minutes_a[1];
            $lng_seconds = $lng_seconds_a[0] / $lng_seconds_a[1];

            $lat = (float) $lat_degrees+((($lat_minutes*60)+($lat_seconds))/3600);
            $lng = (float) $lng_degrees+((($lng_minutes*60)+($lng_seconds))/3600);

            //If the latitude is South, make it negative. 
            //If the longitude is west, make it negative
            $GPSLatitudeRef  == 's' ? $lat *= -1 : '';
            $GPSLongitudeRef == 'w' ? $lng *= -1 : '';

            return array(
                'lat' => $lat,
                'lng' => $lng
            );
        }           
    }
    return false;
}

function scaleImageFileToBlob($file,$max_width=200,$outfile,$quality) {

    list($width, $height, $image_type) = getimagesize($file);

    switch ($image_type)
    {
        case 1: $src = imagecreatefromgif($file); break;
        case 2: $src = imagecreatefromjpeg($file);  break;
        case 3: $src = imagecreatefrompng($file); break;
        default: return '';  break;
    }

    $x_ratio = $max_width / $width;
    $y_ratio=$x_ratio;
    $max_height = $height*$y_ratio;

    //echo 'w: '.$max_width.' h: '.$max_height;
    
    
    if($max_height>$height ||
       $max_width>$width) {
      copy ( $file, $outfile);
      return NULL;
    }
    


    //    $y_ratio = $max_height / $height;

    if( ($width <= $max_width) && ($height <= $max_height) ){
        $tn_width = $width;
        $tn_height = $height;
        }elseif (($x_ratio * $height) < $max_height){
            $tn_height = ceil($x_ratio * $height);
            $tn_width = $max_width;
        }else{
            $tn_width = ceil($y_ratio * $width);
            $tn_height = $max_height;
    }

    $tmp = imagecreatetruecolor($tn_width,$tn_height);

    /* Check if this image is PNG or GIF, then set if Transparent*/
    if(($image_type == 1) OR ($image_type==3))
    {
        imagealphablending($tmp, false);
        imagesavealpha($tmp,true);
        $transparent = imagecolorallocatealpha($tmp, 255, 255, 255, 127);
        imagefilledrectangle($tmp, 0, 0, $tn_width, $tn_height, $transparent);
    }
    imagecopyresampled($tmp,$src,0,0,0,0,$tn_width, $tn_height,$width,$height);

    /*
     * imageXXX() only has two options, save as a file, or send to the browser.
     * It does not provide you the oppurtunity to manipulate the final GIF/JPG/PNG file stream
     * So I start the output buffering, use imageXXX() to output the data stream to the browser,
     * get the contents of the stream, and use clean to silently discard the buffered contents.
     */
    ob_start();

    switch ($image_type)
    {
        case 1: imagegif($tmp); break;
        case 2: imagejpeg($tmp, $outfile, $quality);  break; // best quality
        case 3: imagepng($tmp, NULL, 0); break; // no compression
        default: echo ''; break;
    }

    $final_image = ob_get_contents();

    ob_end_clean();

    return $final_image;
}

/*    Title: 	scaleImageMP
      Purpose:	
      Created:	Sun Nov 03 09:17:48 2019
      Author: 	Adrie Dane
*/
function scaleImageMP($file,$outfile,$megapixels=5,$quality=100) {

  $pix=$megapixels*1000000;
  if(file_is_stream($file))	{

    $data=explode( ',', $file);
    $src=base64_decode($data[1]);
    list($width, $height, $image_type) = getimagesizefromstring($src);
  } else {
    list($width, $height, $image_type) = getimagesize($file);
  }
  
  $curpix=$width*$height;

  $factor=sqrt($pix/$curpix);
  

  if($height>$width)	{
    $max_dimension=(int)($factor*$height);
  } else {
    $max_dimension=(int)($factor*$width);
  }
    
  scaleImage($file,$outfile,$max_dimension,$quality);
} /* scaleImageMP */



function scaleImage($file,$outfile,$max_dimension=600,$quality=75) {
  //  echo file_is_stream($file);
  // echo $file;
  
  if(file_is_stream($file))	{

    $data=explode( ',', $file);
    $src=base64_decode($data[1]);
    list($width, $height, $image_type) = getimagesizefromstring($src);
    $src=imagecreatefromstring($src);
    //    echo "width $width height $height type $image_type max dim $max_dimension";
    
  } else {
    list($width, $height, $image_type) = getimagesize($file);
    switch ($image_type)
      {
      case 1: $src = imagecreatefromgif($file); break;
      case 2: $src = imagecreatefromjpeg($file);  break;
      case 3: $src = imagecreatefrompng($file); break;
      default: return '';  break;
      }

  }




  if($height>$width)	{
    $max_dimension=(int)($width*(float)($max_dimension/$height));
  }

  $x_ratio = $max_dimension / $width;
  $y_ratio=$x_ratio;
  $max_height = $height*$y_ratio;

  //  echo 'w: '.$max_dimension.' h: '.$max_height;
    
    
  if($max_height>$height ||
     $max_dimension>$width) {
    copy ( $file, $outfile);
    return NULL;
  }
    


  //    $y_ratio = $max_height / $height;

  if( ($width <= $max_dimension) && ($height <= $max_height) ){
    $tn_width = $width;
    $tn_height = $height;
  }elseif (($x_ratio * $height) < $max_height){
    $tn_height = ceil($x_ratio * $height);
    $tn_width = $max_dimension;
  }else{
    $tn_width = ceil($y_ratio * $width);
    $tn_height = $max_height;
  }

  //   echo "tn_width $tn_width tn_height $tn_height type $image_type";
  $tmp = imagecreatetruecolor($tn_width,$tn_height);

  /* Check if this image is PNG or GIF, then set if Transparent*/
  if(($image_type == 1) OR ($image_type==3))
    {
      imagealphablending($tmp, false);
      imagesavealpha($tmp,true);
      $transparent = imagecolorallocatealpha($tmp, 255, 255, 255, 127);
      imagefilledrectangle($tmp, 0, 0, $tn_width, $tn_height, $transparent);
    }
  imagecopyresampled($tmp,$src,0,0,0,0,$tn_width, $tn_height,$width,$height);

  /*
   * imageXXX() only has two options, save as a file, or send to the browser.
   * It does not provide you the oppurtunity to manipulate the final GIF/JPG/PNG file stream
   * So I start the output buffering, use imageXXX() to output the data stream to the browser,
   * get the contents of the stream, and use clean to silently discard the buffered contents.
   */
  ob_start();

  switch ($image_type)
    {
    case 1: imagegif($tmp); break;
    case 2: imagejpeg($tmp, $outfile, $quality);  break; // best quality
    case 3: imagepng($tmp, NULL, 0); break; // no compression
    default: echo ''; break;
    }

  $final_image = ob_get_contents();

  ob_end_clean();

  return $final_image;
}

/*    Title: 	is_stream
      Purpose:	
      Created:	Mon Nov 04 09:17:31 2019
      Author: 	Adrie Dane
*/
function file_is_stream($file)
{
  return !strncmp($file,'data:',5);
} /* is_stream */



?>