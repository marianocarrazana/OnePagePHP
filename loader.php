<?php
require_once 'vendor/autoload.php';
require_once 'lib/onepage.php';

use OnePagePHP\OnePage;

//load the config.json and save it inside the $config variable
$config = OnePage::loadJSON("config.json");
$config["root_dir"] = __dir__;
//Initialize the class OnePage with the config
$app = new OnePage($config);

//$router is declared globally in OnePage construct
//load the routes, you can edit the file routes.php
require_once "routes.php";

//this is a nice place to load your data/model

//check the routes and file to render the requested page based on the URL
$router->checkRoutes();//equivalent to $app->router->checkRoutes
