<?php
require_once 'vendor/autoload.php';
require_once 'lib/onepage.php';
require_once 'lib/router.php';

use OnePagePHP\OnePage;
use OnePagePHP\Router;

//load the config.json and save it inside the $config variable
$config = OnePage::loadJSON("config.json", true);
$config["root_dir"] = __dir__;
//Initialize the class OnePage with the config
OnePage::init($config);

//load the routes, you can edit the file routes.php
require_once "routes.php";

//this is a nice place to load your data/model

//check the routes and file to render the requested page based on the URL
Router::checkRoutes();
