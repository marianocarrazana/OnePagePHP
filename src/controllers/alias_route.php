<?php
//$params['string']: it content the parameter defined in the url alias_url/{string}
var_dump($params);
if (empty($params["string"])) {
    $params["string"] = "where is the string?";
}
//Define all variables, all variables will be deleted with the new array
$renderer->setVariables(["title" => "alias route", "string" => $params["string"]]);
