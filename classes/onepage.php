<?php

/**
 *
 */
class OnePage
{
    protected $variables = []; //variables for twig templates
    protected $scripts   = []; //scripts to execute on page load
    protected $routes       = [];
    protected $rendered  = false;
    protected $fullMode = true;
    protected $config = [];
    protected $url = "";
    protected $phpPath = "";
    protected $templateFile = "";
    protected $sectionsFiles = [];

    public function __construct(array $config){
        $this->config = $config;
        $this->fullMode = !isset($_GET["onepage"]);
        if (preg_match('/[^\/]$/', $this->config['site_url'])) {
            $this->config['site_url'].='/';
        }
        if (!isset($_GET["_url"])) {
            trigger_error("No URL", E_USER_ERROR);
        }
        //no url,avoid open loader.php directly
        $paths = explode("?", $_GET["_url"]); //separate url from parameters
        $this->url  = $paths[0];
        $paths = explode("/", $paths[0]);
        foreach ($paths as $key => $value) {
            if (preg_match('/[^\w\-\_\ ]/', $value)) {
                trigger_error("Invalid URL, remove all non alphanumeric charracters", E_USER_ERROR);
            } else if ($value == '') {
                $paths[$key] = "index";
            }
        }
        $path = join("/",$paths);
        foreach ($this->config["paths"] as $key => $value) {
            $this->config["paths"][$key] = __dir__ . "/../{$value}/";
        }
        $this->phpPath       = $this->config["paths"]["php"] . "{$path}.php";
        $this->templateFile  = "{$path}.html";
        $sections      = scandir($this->config["paths"]["sections"]);
        foreach ($sections as $i) {
            if (preg_match('/[^\.]/', $i)) {
                $this->sectionsFiles[$i] = file_get_contents(__dir__ . "/../views/sections/" . $i);
            }
        }
    }

    public function render(string $name, array $variables = null, bool $is_string = false)
    {
        if (!$is_string) {//file render
            $path = $this->config["paths"]["templates"] . $name;
            if (!file_exists($path)) {
                trigger_error("Template not found", E_USER_ERROR);
            }
            $template = file_get_contents($path);
        } else {//string render
            $template = $name;
            $name     = "string_renderer.html";
        }
        if ($variables == null) {
            $variables = $this->variables;
        }
        if ($this->fullMode) {//include headers only on get request
            $template.="<script type='text/javascript'>OnePage.site_url='{{site_url}}';OnePage.updateLinks();OnePage.eval_scripts={$this->config['eval_scripts']};".join(";",$this->scripts)."</script>";
            $template = "{% extends 'base.html' %}{% block content %}{$template}{% endblock %}";
        }
        $variables['site_url'] = $this->config['site_url'];
        $this->sectionsFiles[$name] = $template;
        if (!isset($variables['title'])) {
            $variables['title'] = $this->config["default_title"];
        }
        foreach ($this->sectionsFiles as $key => $value) {//convert relative links url in absolute
            $this->sectionsFiles[$key]  = preg_replace('/(href|src)=[\'\"](?!#|(https?:)|(\/\/)|({{))([^\'\"]+)[\'\"]/i', '\1="'.$this->config['site_url'].'\5"', $value);
        }
        $loader = new \Twig\Loader\ArrayLoader($this->sectionsFiles);
        $twig   = new \Twig\Environment($loader);
        $output = $twig->render($name, $variables);
        if ($this->fullMode) {
            echo $output;
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                "title"   => $variables["title"],
                "content" => $output,
                'scripts' => join(";", $this->scripts),
            ]);
        }
        $this->rendered = true;
    }

    public function autoRender(string $path)
    {
        if ($this->rendered) {
            return false;
        }
        $this->render($path);
    }

    public function renderString(string $string, array $array = null)
    {
        $this->render($string, $array, true);
    }

    public function addVariable(string $name, $value)
    {
        $this->variables[$name] = $value;
    }

    public function addScript(string $script, bool $is_a_file = false)
    {
        if ($is_a_file) {
            $path = __dir__ . "/../${script}";
            if (file_exists($path)) {
                $file = file_get_contents($path);
                array_push($this->scripts, $path);
            } else {
                trigger_error("Script file doesn't exist", E_USER_NOTICE);
            }

        } else {
            array_push($this->scripts, $script);
        }

    }

    public function addRoute(string $route, callable $function, array $methods = [])
    {
        $regexp = str_replace("/", '\/', $route);
        $regexp = "/^" . preg_replace("/#([^\/|]+)(|[^\/]*)?#/", '(?<\1>[^\/]+)', $regexp) . "$/";
        preg_match_all('/#([^\/]+)#/', $route, $vars);
        $vars              = $vars[1];
        foreach ($vars as $key => $value) {
            $pos = strpos($value, '|');
            if($pos){
                $regexpVar = substr($value, $pos-strlen($value)+1);
                if($regexpVar=="number")$regexpVar='\d+';
                if($regexpVar=="word")$regexpVar='\w+';
                $name = substr($value, 0, $pos);
            }else{
                $regexpVar = '.+';
                $name = $value;
            }
            $regexpVar = "/^{$regexpVar}$/i";
            $vars[$key]=["name"=>$name,"regexp"=>$regexpVar];
        }
        $this->routes[] = ["variables" => $vars, "route"       => $route,
            "callback"                        => $function, "methods" => $methods, "regexp" => $regexp];
    }

    public static function loadJSON(string $path, bool $convert_to_array = false)
    {
        $string = file_get_contents($path);
        $json   = json_decode($string, $convert_to_array);
        return $json;
    }

    public function checkRoutes(){
        $noRoute = true;
        foreach ($this->routes as $route) {
        //check if the route exist
            if (preg_match($route["regexp"], $this->url) && $noRoute) {
                if (!empty($route["methods"]) && !in_array($_SERVER['REQUEST_METHOD'], $route["methods"])) {
                    http_response_code(405);die();//metho not implemented in router
                }
                preg_match_all($route["regexp"], $this->url, $matches);
                $variables = $route["variables"];
                $vars = [];
                foreach ($variables as $variable) {
                    $value = $matches[$variable['name']][0];
                    if(!preg_match($variable['regexp'], $value))continue 2;
                    $vars[$variable['name']] = $value;
                }
                $noRoute = false;
                call_user_func($route["callback"], $this, $vars);
            }
        }
        if ($noRoute) {
            $noFiles = true;
            if (file_exists($this->phpPath)) {
                require_once $this->phpPath;
                $noFiles = false;
            }
            if (file_exists($this->config["paths"]["templates"] . $this->templateFile)) {
                $this->autoRender($this->templateFile);
                $noFiles = false;
            }
            if ($noFiles) {
                http_response_code(404);die();
            }
        }
    }

}
