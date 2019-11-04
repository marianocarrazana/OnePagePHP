<?php
$result = 1+1;
//this script is running on sandbox mode you have 2 variables:
//$renderer: is a instance of OnePagePHP\Renderer class in this case it was defined in loader.php
//$variables: it content the variables defined in the url
//use "global $my_var" to access to global variables
$renderer->addVariable("result",$result);//use {{result}} to render this value
$renderer->addScript("console.log('page loaded')");//run this script on page load
