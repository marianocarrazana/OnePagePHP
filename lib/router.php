<?php
namespace OnePagePHP;

require_once __dir__ . "/sandbox.php";

/**
 * 
 */
class Router
{
    protected $routes = [];
    protected $url    = "";
    protected $OnePage = null;
    protected $paths = [];

    public function __construct(OnePage &$OnePage)
    {
        $this->url = $OnePage->getUrl();
        $this->paths = $OnePage->getConfig("paths");
        $this->OnePage = $OnePage;
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
        $noRoute = true;
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
                    if (file_exists($this->paths["controllers"] . "${route['controller']}.php")) {
                        new Sandbox(
                            $this->paths["controllers"] . "${route['controller']}.php", 
                            $vars, $this->OnePage);
                    }
                    if (file_exists($this->paths["views"] . "${route['controller']}." . $this->OnePage->getTemplatesExtension())) {
                        $this->OnePage->getRenderer()->autoRender("${route['controller']}." . $this->OnePage->getTemplatesExtension());
                    }
                }
            }
        }
        if ($noRoute) {
            $noFiles = true;
            if ($this->OnePage->getConfig("automatic_render")) {
                if (file_exists($this->OnePage->getControllerPath())) {
                    new Sandbox($this->OnePage->getControllerPath(), [], $this->OnePage);
                    $noFiles = false;
                }
                if (file_exists($this->paths["views"] . $this->OnePage->getTemplate())) {
                    $this->OnePage->getRenderer()->autoRender($this->OnePage->getTemplate());
                    $noFiles = false;
                }
            }
            if ($noFiles) {
                http_response_code(404);die();
            }
        }
    }
}
