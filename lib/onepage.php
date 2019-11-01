<?php
namespace OnePagePHP;

/**
 *
 */
class OnePage
{
    protected static $variables     = []; //variables for twig templates
    protected static $scripts       = []; //scripts to execute on page load
    protected static $rendered      = false;
    protected static $fullMode      = true;
    protected static $config        = [];
    protected static $phpPath       = "";
    protected static $templateFile  = "";
    protected static $templateString  = "";
    protected static $sectionsFiles = [];
    protected static $root_dir      = "";
    protected static $url           = "";
    protected static $automatic_render = false;
    public static $twig = null;

    public static function init(array $config)
    {
        OnePage::$config   = $config;
        OnePage::$fullMode = !isset($_GET["onepage"]);
        OnePage::$automatic_render = $config["automatic_render"];
        if (preg_match('/[^\/]$/', OnePage::$config['site_url'])) {
            OnePage::$config['site_url'] .= '/';
        }
        if (!isset($_GET["_url"])) {
            trigger_error("No URL", E_USER_ERROR);
        }
        //no url,avoid open loader.php directly
        $paths        = explode("?", $_GET["_url"]); //separate url from parameters
        OnePage::$url = $paths[0];
        $paths        = explode("/", $paths[0]);
        foreach ($paths as $key => $value) {
            if (preg_match('/[^\w\-\_\ ]/', $value)) {
                trigger_error("Invalid URL, remove all non alphanumeric charracters", E_USER_ERROR);
            } else if ($value == '') {
                $paths[$key] = "index";
            }
        }
        $path              = join("/", $paths);
        OnePage::$root_dir = $config["root_dir"];
        foreach (OnePage::$config["paths"] as $key => $value) {
            OnePage::$config["paths"][$key] = OnePage::$root_dir . "/{$value}/";
        }
        OnePage::$phpPath      = OnePage::$config["paths"]["php"] . "{$path}.php";
        OnePage::$templateFile = "{$path}.html";
        $sections              = scandir(OnePage::$config["paths"]["sections"]);
        foreach ($sections as $i) {
            if (preg_match('/[^\.]/', $i)) {
                OnePage::$sectionsFiles[$i] = file_get_contents(OnePage::$root_dir ."/{$config['paths']['sections']}/". $i);
            }
        }
    }

    public static function render(string $name, array $variables = null, bool $echo = true,bool $is_string = false)
    {
        if (!$is_string) {
//file render
            $path = OnePage::$config["paths"]["templates"] . $name;
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
            $variables = OnePage::$variables;
        }
        if (OnePage::$fullMode) {
            /*include headers only on get request*/
            $onepagejs    = file_get_contents(__dir__ . "/onepage.js"); //include onepagejs in library mode too
            $eval_scripts = OnePage::$config['eval_scripts'];
            $template .= "<script type='text/javascript'>{$onepagejs};OnePage.site_url='{{site_url}}';OnePage.updateLinks();OnePage.eval_scripts={$eval_scripts};" . join(";", OnePage::$scripts) . "</script>";
            $template = "{% extends 'base.html' %}{% block content %}{$template}{% endblock %}";
        }
        $variables['site_url']         = OnePage::$config['site_url'];
        OnePage::$sectionsFiles[$name] = $template;
        if (!isset($variables['title'])) {
            $variables['title'] = OnePage::$config["default_title"];
        }
        foreach (OnePage::$sectionsFiles as $key => $value) {
            /*convert relative links url in absolute*/
            OnePage::$sectionsFiles[$key] = preg_replace('/(href|src)=[\'\"](?!#|(https?:)|(\/\/)|({{))([^\'\"]+)[\'\"]/i', '\1="' . OnePage::$config['site_url'] . '\5"', $value);
        }
        $loader = new \Twig\Loader\ArrayLoader(OnePage::$sectionsFiles);
        OnePage::$twig   = new \Twig\Environment($loader);
        OnePage::$templateString = $name;
        OnePage::$variables = $variables;
        if($echo)OnePage::echoRender();
    }

    static function echoRender(){
        $output = OnePage::$twig->render(OnePage::$templateString, OnePage::$variables);
        if (OnePage::$fullMode) {
            echo $output;
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                "title"   => OnePage::$variables["title"],
                "content" => $output,
                'scripts' => join(";", OnePage::$scripts),
            ]);
        }
        OnePage::$rendered = true;
    }

    public static function autoRender(string $path)
    {
        if (OnePage::$rendered) {
            return false;
        }
        OnePage::render($path);
    }

    public function renderString(string $string, array $array = null, bool $echo = true)
    {
        OnePage::render($string, $array, $echo, true);
    }

    public static function addVariable(string $name, $value)
    {
        OnePage::$variables[$name] = $value;
    }

    public function addScript(string $script, bool $is_a_file = false)
    {
        if ($is_a_file) {
            $path = OnePage::$root_dir . "/${script}";
            if (file_exists($path)) {
                $file = file_get_contents($path);
                array_push(OnePage::$scripts, $path);
            } else {
                trigger_error("Script file doesn't exist", E_USER_NOTICE);
            }

        } else {
            array_push(OnePage::$scripts, $script);
        }

    }

    public static function loadJSON(string $path, bool $convert_to_array = false)
    {
        $string = file_get_contents($path);
        $json   = json_decode($string, $convert_to_array);
        return $json;
    }

    public static function getTemplatesPath()
    {return OnePage::$config["paths"]["templates"];}
    public static function getPhpPath()
    {return OnePage::$phpPath;}
    public static function getTemplate()
    {return OnePage::$templateFile;}
    public static function getUrl()
    {return OnePage::$url;}
    public static function getAutomaticRender()
    {return OnePage::$automatic_render;}

}
