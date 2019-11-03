<?php
namespace OnePagePHP;
require_once __dir__.'/router.php';

/**
 *
 */
class OnePage
{
    protected $variables        = []; //variables for twig templates
    protected $scripts          = []; //scripts to execute on page load
    protected $rendered         = false;
    protected $fullMode         = true;
    protected $config           = [];
    protected $controllerPath   = "";
    protected $templateFile     = "";
    protected $templateString   = "";
    protected $sectionsFiles    = [];
    protected $root_dir         = "";
    protected $url              = "";
    protected $automatic_render = false;
    protected $twigExtensions          = [];
    protected $twig                    = null;
    public $router = null;

    public function __construct(array $config)
    {
        $this->config           = $config;
        $this->fullMode         = !isset($_GET["onepage"]);
        $this->automatic_render = $config["automatic_render"];
        if (preg_match('/[^\/]$/', $this->config['site_url'])) {
            $this->config['site_url'] .= '/';
        }
        if (!isset($_GET["_url"])) {
            trigger_error("No URL", E_USER_ERROR);
        }
        //no url,avoid open loader.php directly
        $paths        = explode("?", $_GET["_url"]); //separate url from parameters
        $this->url = $paths[0];
        $paths        = explode("/", $paths[0]);
        foreach ($paths as $key => $value) {
            if (preg_match('/[^\w\-\_\ ]/', $value)) {
                trigger_error("Invalid URL, remove all non alphanumeric charracters", E_USER_ERROR);
            } else if ($value == '') {
                $paths[$key] = "index";
            }
        }
        $path              = join("/", $paths);
        $this->root_dir = $config["root_dir"];
        foreach ($this->config["paths"] as $key => $value) {
            /* set absolute paths */
            $this->config["paths"][$key] = $this->root_dir . "/{$value}/";
        }
        $this->controllerPath = $this->config["paths"]["controllers"] . "{$path}.php";
        $this->templateFile   = "{$path}.{$config['templates_extension']}";
        $sections                = scandir($this->config["paths"]["sections"]);
        foreach ($sections as $i) {
            if (preg_match('/[^\.]/', $i)) {
                $this->sectionsFiles[$i] = file_get_contents($this->root_dir . "/{$config['paths']['sections']}/" . $i);
            }
        }
        if($config["enable_router"]){
            $this->router = new Router($this);
        }
    }

    protected function checkConfig(array $config = [])
    {
        
    }

    public function addTwigExtension($class, callable $callback, $extension)
    {
        $this->twigExtensions[] = ["class" => $class, "callback" => $callback, "extension" => $extension];
    }

    public function render(
        string $name,
        array $variables = null,
        bool $is_string = false
    ) {
        if (!$is_string) {
            /*file render*/
            $path = $this->config["paths"]["views"] . $name;
            if (!file_exists($path)) {
                trigger_error("Template not found", E_USER_ERROR);
            }
            $template = file_get_contents($path);
        } else {
            /*string render*/
            $template = $name;
            $name     = "string_renderer.html";
        }
        if ($variables == null) {
            $variables = $this->variables;
        }
        if ($this->fullMode) {
            /*include headers only on get request*/
            $onepagejs    = file_get_contents(__dir__ . "/onepage.js"); //include onepagejs in library mode too
            $eval_scripts = $this->config['eval_scripts'];
            $template .= "<script type='text/javascript'>{$onepagejs};OnePage.site_url='{{site_url}}';OnePage.updateLinks();OnePage.eval_scripts={$eval_scripts};" . join(";", $this->scripts) . "</script>";
            $template = "{% extends 'base." . $this->config['templates_extension'] . "' %}{% block content %}{$template}{% endblock %}";
        }
        $variables['site_url']         = $this->config['site_url'];
        $this->sectionsFiles[$name] = $template;
        if (!isset($variables['title'])) {
            $variables['title'] = $this->config["default_title"];
        }
        foreach ($this->sectionsFiles as $key => $value) {
            /*convert relative links url in absolute*/
            $this->sectionsFiles[$key] = preg_replace('/(href|src)=[\'\"](?!#|(https?:)|(\/\/)|({{))([^\'\"]+)[\'\"]/i', '\1="' . $this->config['site_url'] . '\5"', $value);
        }
        $loader        = new \Twig\Loader\ArrayLoader($this->sectionsFiles);
        $this->twig = new \Twig\Environment($loader);

        $this->twig->addRuntimeLoader(new class implements \Twig\RuntimeLoader\RuntimeLoaderInterface
        {
            public function load($class)
            {
                foreach ($this->twigExtensions as $key => $extension) {
                    if ($extension['class'] === $class) {
                        return $extension['callback']();
                    }
                }
            }
        });
        foreach ($this->twigExtensions as $extension) {
            $this->twig->addExtension($extension['extension']);
        }
        $this->templateString = $name;
        $this->variables      = $variables;

        $output = $this->twig->render($this->templateString, $this->variables);
        if (!$this->fullMode) {
            header('Content-Type: application/json');
            $output = json_encode([
                "title"   => $this->variables["title"],
                "content" => $output,
                'scripts' => join(";", $this->scripts),
            ]);
        }

        $this->rendered = true;
        return $output;

    }

    public function autoRender(string $path)
    {
        if ($this->rendered) {
            return false;
        }
        echo $this->render($path);
    }

    public function renderString(string $string, array $variables = null)
    {
        return $this->render($string, $variables, true);
    }

    public function renderToFile(string $name, array $variables = null, string $output, bool $is_string = false)
    {
        $content = $this->render($name, $variables, $is_string);
        file_put_contents($output, $content);
    }

    public function addVariable(string $name, $value)
    {
        $this->variables[$name] = $value;
    }

    public function setVariables(array $vars)
    {
        $this->variables = $vars;
    }

    public function addScript(string $script, bool $is_a_file = false)
    {
        if ($is_a_file) {
            $path = $this->root_dir . "/${script}";
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

    public static function loadJSON(string $path, bool $convert_to_array = true)
    {
        $string = file_get_contents($path);
        $json   = json_decode($string, $convert_to_array);
        return $json;
    }

    public function getTemplatesPath()
    {return $this->config["paths"]["views"];}
    public function getTemplatesExtension()
    {return $this->config["templates_extension"];}
    public function getControllersPath()
    {return $this->config["paths"]["controllers"];}
    public function getControllerPath()
    {return $this->controllerPath;}
    public function getTemplate()
    {return $this->templateFile;}
    public function getUrl()
    {return $this->url;}
    public function getAutomaticRender()
    {return $this->automatic_render;}

}
