<?php
$result = 1+1;
//$this is a reference to $OnePage created on loader.php
$this->addVariable("result",$result);//use {{result}} to render this value
$this->addScript("console.log('page loaded')");//run this script on page load
