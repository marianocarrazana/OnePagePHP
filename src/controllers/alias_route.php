<?php
global $renderer;
//$params['string']: it content the parameter defined in the url alias_url/{string}
if (empty($params["string"])) {
    $params["string"] = "where is the string?";
}
//Define all variables, all variables will be deleted with the new array
$renderer->setVariables(["title" => "alias route", "string" => $params["string"]]);
