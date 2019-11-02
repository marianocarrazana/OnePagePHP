<?php
namespace OnePagePHP;

/**
 *
 */
class Router
{
    protected static $routes = [];

    public static function addRoute(string $route, $function_controller, array $methods = [])
    {
        $regexp = str_replace("/", '\/', $route);
        $with_names = preg_replace("/{([^\/|]+)(|[^\/]*)?}/", '(?<\1>[^\/]+)', $regexp);
        $regexp = "/^" . $with_names . "$/";
        preg_match_all('/{([^\/]+)}/', $route, $vars);
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
            "controller"                     => $function_controller,
            "methods"                        => $methods, "regexp" => $regexp];
    }

    protected static function runInSandbox(string $path,array $variables=[])
    {
        $OnePage = new OnePage;
        require_once $path;
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
                if (is_callable($route["controller"])) {
                    call_user_func($route["controller"], $vars);
                } else {
                    if (file_exists(OnePage::getControllersPath() . "${route['controller']}.php")) {
                        Router::runInSandbox(OnePage::getControllersPath() . "${route['controller']}.php",$vars);
                    }
                    if (file_exists(OnePage::getTemplatesPath() . "${route['controller']}." . OnePage::getTemplatesExtension())) {
                        OnePage::autoRender("${route['controller']}." . OnePage::getTemplatesExtension());
                    }
                }
            }
        }
        if ($noRoute) {
            $noFiles = true;
            if (OnePage::getAutomaticRender()) {
                if (file_exists(OnePage::getControllerPath())) {
                    Router::runInSandbox(OnePage::getControllerPath());
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
