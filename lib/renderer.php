<?php
namespace OnePagePHP;

/**
 *
 */
class Renderer
{

    private $OnePage               = null;
    private $variables             = []; //variables for twig templates
    private $scripts               = []; //scripts to execute on page load
    private $rendered              = false;
    private $paths                 = [];
    private static $twigExtensions = [];
    private $twig                  = null;
    private $sectionsFiles         = [];
    private $config                = [];

    public function __construct(Loader &$OnePage)
    {
        $this->OnePage = $OnePage;
        $this->paths   = $OnePage->getConfig("paths");
        $sections      = scandir($this->paths["sections"]);
        foreach ($sections as $i) {
            if (preg_match('/[^\.]/', $i)) {
                if (Loader::isWindows()) {
                    $this->sectionsFiles[$i] = file_get_contents("{$this->paths['sections']}\\" . $i);
                } else {
                    $this->sectionsFiles[$i] = file_get_contents("/{$this->paths['sections']}/" . $i);
                }

            }
        }
        $this->config = $OnePage->getConfig("renderer");
    }

    public function render(
        string $name,
        array $variables = null,
        bool $is_string = false
    ) {
        $errors        = "";
        $log           = "";
        $error_handler = $this->OnePage->getConfig("error_handler");
        if ($error_handler["debug_mode"]) {
            $logger = $this->OnePage->getLogger();
            $errors = join("<br>", $logger->getHtmlErrors());
            $log    = join(";", $logger->getConsoleLog());
        }
        if (!$is_string) {
            /*file render*/
            $path = $this->paths["views"] . $name;
            if (!file_exists($path)) {
                trigger_error("Template not found: {$path}", E_USER_ERROR);
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
        if (!isset($variables['title'])) {
            $variables['title'] = $this->OnePage->getConfig("default_title");
        }
        if ($this->OnePage->getFullMode()) {
            /*include headers only on get request*/
            $onepagejs    = file_get_contents(__dir__ . "/onepage.js"); //include onepagejs in library mode too
            $eval_scripts = $this->OnePage->getConfig('eval_scripts');
            $href = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $replaceState = "window.history.replaceState({'content':document.getElementById('content').innerHTML,'scripts':".json_encode(join(";", $this->scripts)).",'title':'{$variables['title']}'}, '', location.href);";
            $template = "{% extends 'base." . $this->OnePage->getConfig('templates_extension') . "' %}{% block content %}{$errors}<script>{$log}</script>{$template}{% endblock %}";
            $extension = $this->OnePage->getConfig('templates_extension');
            preg_match('/<head[^>]*>/', $this->sectionsFiles["base.{$extension}"],$headerTag);
            $headerScript = "{$headerTag[0]}<script type='text/javascript'>{$onepagejs}</script>";
            preg_match('/<\/body[^>]*>/', $this->sectionsFiles["base.{$extension}"],$bodyTag);
            $bodyScript = "<script type='text/javascript'>OnePage.site_url='{{site_url}}';OnePage.updateRoutes();OnePage.eval_scripts={$eval_scripts};\n" . join(";", $this->scripts) . ";\n{$replaceState}</script>{$bodyTag[0]}";
            $this->sectionsFiles["base.{$extension}"] = str_replace([$headerTag[0],$bodyTag[0]], [$headerScript,$bodyScript], $this->sectionsFiles["base.{$extension}"]);
        }
        $site_url                   = $this->OnePage->getConfig('site_url');
        $variables['site_url']      = $site_url;
        $this->sectionsFiles[$name] = $template;
        foreach ($this->sectionsFiles as $key => $value) {
            //convert relative links url in absolute
            $patters = [
                '/(href|src)=[\'\"](?!#|(https?:)|(\/)|(\.\.)|({{))([^\'\"]+)[\'\"]/i',
                '/route=[\"\']([^\"\']*)[\"\']/i',
            ]; //relative routes
            $replace = [
                '\1="' . $site_url . '\6"',
                'data-route="' . $site_url . '\1" href="' . $site_url . '\1"',
            ]; //absolute routes
            $this->sectionsFiles[$key] = preg_replace($patters, $replace, $value);
        }

        $loader     = new \Twig\Loader\ArrayLoader($this->sectionsFiles);
        $this->twig = new \Twig\Environment($loader);

        $this->twig->addRuntimeLoader(new class implements \Twig\RuntimeLoader\RuntimeLoaderInterface
        {
            public function load($class)
            {
                foreach (Renderer::getTwigExtensions() as $key => $extension) {
                    if ($extension['class'] === $class) {
                        return $extension['callback']();
                    }
                }
            }
        });
        foreach (Renderer::$twigExtensions as $extension) {
            $this->twig->addExtension($extension['extension']);
        }
        $this->templateString = $name;
        $this->variables      = $variables;

        $output = $this->twig->render($this->templateString, $this->variables);
        if (!$this->OnePage->getFullMode()) {
            return $this->renderJSON([
                "title"   => $this->variables["title"],
                "content" => $output,
                'scripts' => join(";", $this->scripts),
                "errors"  => $errors,
                "console" => $log,
            ]);
        }
        $this->rendered = true;
        return $output;

    }

    public function renderPlainText(string $text = ""){
      header('Content-Type: text/plain');
      $this->rendered = true;
      return $text;
    }

    public function renderJSON($object = []){
      header('Content-Type: application/json');
      if(is_array($object))$object = json_encode($object);
      $this->rendered = true;
      return $object;
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

    public function addTwigExtension($class, callable $callback, $extension)
    {
        Renderer::$twigExtensions[] = ["class" => $class, "callback" => $callback, "extension" => $extension];
    }

    public static function getTwigExtensions()
    {
        return Renderer::$twigExtensions;
    }

    public function getRendered(){return $this->rendered;}

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
        array_push($this->scripts, $script);
    }

    public function addScriptFile($path)
    {
        if (file_exists($path)) {
            $file = file_get_contents($path);
            array_push($this->scripts, $file);
        } else {
            trigger_error("Script file doesn't exist", E_USER_NOTICE);
        }
    }
}
