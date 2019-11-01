<?php
use OnePagePhp\OnePage;
$result = 1+1;
//$this is a reference to $OnePage created on loader.php
OnePage::addVariable("result",$result);//use {{result}} to render this value
OnePage::addScript("console.log('page loaded')");//run this script on page load
