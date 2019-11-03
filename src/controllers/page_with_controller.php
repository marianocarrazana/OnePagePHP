<?php
$result = 1+1;
//this script is running on sandbox mode you have 2 variables:
//$OnePage: is a instance of OnePagePHP\OnePage class in this case it was defined in loader.php
//$variables: it content the variables defined in the url
//use "global $my_var" to access to global variables
$OnePage->addVariable("result",$result);//use {{result}} to render this value
$OnePage->addScript("console.log('page loaded')");//run this script on page load
