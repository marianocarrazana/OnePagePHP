<?php
//this script is running on sandbox mode you have 2 variables:
//$OnePage: is a instance of OnePagePHP\OnePage class
//$variables: it content the variables defined in the url
//use "global $my_var" to access to global variables
if(empty($variables["string"]))$variables["string"]="where is the string?";
$OnePage->setVariables(["title"=>"alias route","string"=>$variables["string"]]);