<?php
//$variables['string']: it content the variable defined in the url alias_url/{string}
if (empty($variables["string"])) {
    $variables["string"] = "where is the string?";
}
//Define all variables, all variables will be deleted with the new array
$renderer->setVariables(["title" => "alias route", "string" => $variables["string"]]);
