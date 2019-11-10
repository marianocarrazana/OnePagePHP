<?php
//on access to the url "alias_url" render alias_route controller and/or view
//you can access to this with "alias_url" or "alias_route"
$router->addRoute("alias_url/{string}", "alias_route"/*files to render*/);

//custom route, $renderer is a global variable created inside OnePage::__construc
$router->addRoute("custom/route", function () {
	global $renderer;
    echo $renderer->renderString("Custom route");
});

//#something# is a variable name(is similar ro {var} in others routers systems), you  can get the content with $params['something']
$router->addRoute("say/{something}", function ($params) use ($renderer) {
    echo $renderer->renderString("{{something}}", $params);
}, ["GET", "POST"]); //allow GET and POST methods

//the routes support regular expresions (sum|add) will match sum or add, also 'number' word on the right of a variable name(and a |) it will match only numbers
$router->addRoute("(sum|add)/{num1|number}/{num2|number}", function ($params) use ($renderer) {
    $params["total"] = $params["num1"] + $params["num2"];
    echo $renderer->renderString("{{num1}}+{{num2}}={{total}}", $params);
}, ["GET"]); //allow only GET method
$router->addRoute("errors", function () use ($renderer) {
	trigger_error("Oh no! is a notice",E_USER_NOTICE);
	trigger_error("Oh no! is a warning",E_USER_WARNING);
    echo $renderer->renderString("Open the console!");
});