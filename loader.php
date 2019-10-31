<?php
require_once __dir__ . '/vendor/autoload.php';
require_once __dir__ . '/classes/onepage.php';

//load the config.json and save it inside the $config variable
$config = OnePage::loadJSON(__dir__ . "/config.json", true);

//Initialize the class OnePage with the config
$OnePage = new OnePage($config);

//load the routes, you can edit the file routes.php
require_once __dir__."/routes.php";

//this is a nice place to load your data/model

//check the routes and file to render the requested page based on the URL
$OnePage->checkRoutes();
