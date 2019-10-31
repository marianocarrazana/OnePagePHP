<?php
//custom route, $onepage is the object created with the class OnePage
$OnePage->addRoute("custom/route", function ($onepage) {
    $onepage->renderString("Custom route");
});
//#something# is a variable name(is similar ro {var} in others routers systems), you  can get the content with $vars['something']
$OnePage->addRoute("say/#something#", function ($onepage,$vars) {
    $onepage->renderString("{{something}}", $vars);
}, ["GET", "POST"]); //allow GET and POST methods
//the routes support regular expresions (sum|add) will match sum or add, also 'number' word on the right of a variable name(and a |) it will match only numbers
$OnePage->addRoute("(sum|add)/#num1|number#/#num2|number#", function ($onepage,$vars) {
    $vars["total"] = $vars["num1"] + $vars["num2"];
    $onepage->renderString("{{num1}}+{{num2}}={{total}}", $vars);
}, ["GET"]); //allow only GET method
