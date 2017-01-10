<?php
$link = mysql_connect('127.0.0.1:3306','seo_user','seo_pass');
mysql_select_db('seo',$link) or die('could not select database');
$result = mysql_query('SELECT id,url FROM `search` WHERE ttfb = 0 AND paint = 0 AND full = 0 order by query');
if($result){
 while($row = mysql_fetch_assoc($result)){
  $t = file_get_contents('http://wpt.goivvy.com/runtest.php?url='.$row['url'].'&f=xml');
  if($xml = @simplexml_load_string($t)){
  if($xml->statusCode == '200' && $xml->statusText == 'Ok'){
  $r = file_get_contents($xml->data->xmlUrl);
  if($rXml = @simplexml_load_string($r)){
  while($rXml->statusCode != '200' && $rXml->statusText != 'Ok'){
   sleep(5); 
   echo("first try ".$row['url']." ".$xml->data->xmlUrl."\n");
   $rXml = @simplexml_load_string(file_get_contents($xml->data->xmlUrl));
   if(!$rXml) break;
  }
  $ttfb = (int)$rXml->data->average->firstView->TTFB;
  $paint = (int)$rXml->data->average->firstView->firstPaint;
  $full = (int)$rXml->data->average->firstView->loadTime;
  mysql_query('UPDATE `search` SET ttfb = '.$ttfb.',paint='.$paint.',full='.$full.' WHERE id='.$row['id']);
 }
 }
 }
 }
}
