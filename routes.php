<?php
use OnePagePHP\OnePage;
use OnePagePHP\Router;
//custom route, $onepage is the object created with the class OnePage
Router::addRoute("custom/route", function () {
    OnePage::renderString("Custom route");
});
//#something# is a variable name(is similar ro {var} in others routers systems), you  can get the content with $vars['something']
Router::addRoute("say/#something#", function ($vars) {
    OnePage::renderString("{{something}}", $vars);
}, ["GET", "POST"]); //allow GET and POST methods
//the routes support regular expresions (sum|add) will match sum or add, also 'number' word on the right of a variable name(and a |) it will match only numbers
Router::addRoute("(sum|add)/#num1|number#/#num2|number#", function ($vars) {
    $vars["total"] = $vars["num1"] + $vars["num2"];
    OnePage::renderString("{{num1}}+{{num2}}={{total}}", $vars);
}, ["GET"]); //allow only GET method
