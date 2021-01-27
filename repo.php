<?php

class repo
{
  public function __construct($paths=array("./"))
  {
    $this->version=array();

    if(!is_array($paths))	{
      $paths=array($paths);
    }

    $repo=array();
    foreach($paths as $pth) {

      $str=file_get_contents($pth.'.git/config');
      preg_match('/url \= (.*.git)/', $str, $output_array);
      $repo['name']=$output_array[1];

      $HEAD_hash = file($pth.'.git/logs/refs/heads/master'); // or branch x
      $HEAD_hash=explode("\t",end($HEAD_hash));
      $last=explode(' ',$HEAD_hash[0]);
      array_push($last,$HEAD_hash[1]);
      $repo['commit']=$last[1];
      $repo['by']=$last[2];
      $repo['date'] = $last[5] == '+0100' ? (int) $last[4] + 3600 : (int) $last[4];
      $repo['datestr'] = gmdate("Y-m-d H:i:s", $repo['date']);
      $repo['message']=preg_replace('/commit.*\: /', '', $last[6]);
      $this->version[]=$repo;
    }
    //    $this->ncols=count($this->data[0]);
  }
}
?>