<?php
namespace OnePagePHP;

/**
 *
 */
class Router
{
    protected static $routes = [];

    public static function addRoute(string $route, callable $function, array $methods = [])
    {
        $regexp = str_replace("/", '\/', $route);
        $regexp = "/^" . preg_replace("/#([^\/|]+)(|[^\/]*)?#/", '(?<\1>[^\/]+)', $regexp) . "$/";
        preg_match_all('/#([^\/]+)#/', $route, $vars);
        $vars = $vars[1];
        foreach ($vars as $key => $value) {
            $pos = strpos($value, '|');
            if ($pos) {
                $regexpVar = substr($value, $pos - strlen($value) + 1);
                if ($regexpVar == "number") {
                    $regexpVar = '\d+';
                }

                if ($regexpVar == "word") {
                    $regexpVar = '\w+';
                }

                $name = substr($value, 0, $pos);
            } else {
                $regexpVar = '.+';
                $name      = $value;
            }
            $regexpVar  = "/^{$regexpVar}$/i";
            $vars[$key] = ["name" => $name, "regexp" => $regexpVar];
        }
        Router::$routes[] = ["variables" => $vars,
            "route"                          => $route,
            "callback"                       => $function,
            "methods"                        => $methods, "regexp" => $regexp];
    }

    public static function checkRoutes()
    {
        $noRoute = true;
        foreach (Router::$routes as $route) {
            //check if the route exist
            if (preg_match($route["regexp"], OnePage::getUrl()) && $noRoute) {
                if (!empty($route["methods"]) && !in_array($_SERVER['REQUEST_METHOD'], $route["methods"])) {
                    http_response_code(405);die(); //metho not implemented in router
                }
                preg_match_all($route["regexp"], OnePage::getUrl(), $matches);
                $variables = $route["variables"];
                $vars      = [];
                foreach ($variables as $variable) {
                    $value = $matches[$variable['name']][0];
                    if (!preg_match($variable['regexp'], $value)) {
                        continue 2;
                    }
                    $vars[$variable['name']] = $value;
                }
                $noRoute = false;
                call_user_func($route["callback"], $vars);
            }
        }
        if ($noRoute) {
            $noFiles = true;
            if (OnePage::getAutomaticRender()) {
                if (file_exists(OnePage::getPhpPath())) {
                    require_once OnePage::getPhpPath();
                    $noFiles = false;
                }
                if (file_exists(OnePage::getTemplatesPath() . OnePage::getTemplate())) {
                    OnePage::autoRender(OnePage::getTemplate());
                    $noFiles = false;
                }
            }
            if ($noFiles) {
                http_response_code(404);die();
            }
        }
    }
}
