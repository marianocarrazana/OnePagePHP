<?php
namespace OnePagePHP;

require_once __dir__ . "/sandbox.php";

/**
 *
 */
class Router
{
    private $routes  = [];
    private $url     = "";
    private $OnePage = null;
    private $paths   = [];
    private $config  = [];

    public function __construct(Loader &$OnePage)
    {
        $this->url     = $OnePage->getUrl();
        $this->paths   = $OnePage->getConfig("paths");
        $this->OnePage = $OnePage;
        $this->config  = $OnePage->getConfig("router");
    }

    public function addRoute(string $route, $function_controller, array $methods = [])
    {
        $regexp     = str_replace("/", '\/', $route);
        $with_names = preg_replace("/{([^\/|]+)(|[^\/]*)?}/", '(?<\1>[^\/]+)', $regexp);
        $regexp     = "/^" . $with_names . "$/";
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
        $this->routes[] = ["variables" => $vars,
            "route"                        => $route,
            "controller"                   => $function_controller,
            "methods"                      => $methods, "regexp" => $regexp];
    }

    public function checkRoutes()
    {
        $noRoute   = true;
        $debugMode = $this->OnePage->getConfig("error_handler")["debug_mode"];
        foreach ($this->routes as $route) {
            //check if the route exist
            if (preg_match($route["regexp"], $this->url) && $noRoute) {
                if (!empty($route["methods"]) && !in_array($_SERVER['REQUEST_METHOD'], $route["methods"])) {
                    http_response_code(405);die(); //metho not implemented in router
                }
                preg_match_all($route["regexp"], $this->url, $matches);
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
                    $controller = $this->paths["controllers"] . "${route['controller']}.php";
                    if (file_exists($controller)) {
                        new Sandbox(
                            $this->paths["controllers"] . "${route['controller']}.php",$vars);
                    } else if ($debugMode) {
                        trigger_error("${controller} controller doesn't exist", 1024);
                    }
                    $view = $this->paths["views"] . "${route['controller']}." . $this->OnePage->getConfig('templates_extension');
                    if (file_exists($view)) {
                        $this->OnePage->getRenderer()->autoRender("${route['controller']}." . $this->OnePage->getConfig('templates_extension'));
                    }else if($debugMode){
                        trigger_error("${view} view doesn't exist", 1024);
                    }
                }
            }
        }

        if ($noRoute) {
            $noFiles = true;              
            if ($this->config["auto_render"]) {
                $controller = $this->OnePage->getControllerPath();
                if (file_exists($controller)) {
                    new Sandbox($controller, []);
                    $noFiles = false;
                }else if($debugMode){
                    trigger_error("${controller} view doesn't exist", 1024);
                }
                $view = $this->paths["views"] . $this->OnePage->getTemplate();
                if (file_exists($view)) {
                    $this->OnePage->getRenderer()->autoRender($this->OnePage->getTemplate());
                    $noFiles = false;
                }else if($debugMode){
                    trigger_error("${view} view doesn't exist", 1024);
                }
            }
            if ($noFiles) {
                http_response_code(404);
                if($debugMode)trigger_error("No files or route found",E_USER_ERROR);
                die();
            }
        }
    }
}
