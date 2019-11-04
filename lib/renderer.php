<?php
namespace OnePagePHP;

/**
 *
 */
class Renderer
{

    protected $OnePage = null;
    protected $variables        = []; //variables for twig templates
    protected $scripts          = []; //scripts to execute on page load
    protected $rendered         = false;
    protected $paths = [];
    protected static $twigExtensions   = [];
    protected $twig             = null;
    protected $sectionsFiles    = [];

    public function __construct(OnePage &$OnePage)
    {
        $this->OnePage = $OnePage;
        $this->paths = $OnePage->getConfig("paths");
        $sections             = scandir($this->paths["sections"]);
        foreach ($sections as $i) {
            if (preg_match('/[^\.]/', $i)) {
                $this->sectionsFiles[$i] = file_get_contents("/{$this->paths['sections']}/" . $i);
            }
        }
    }

    public function render(
        string $name,
        array $variables = null,
        bool $is_string = false
    ) {
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
        if ($this->OnePage->getFullMode()) {
            /*include headers only on get request*/
            $onepagejs    = file_get_contents(__dir__ . "/onepage.js"); //include onepagejs in library mode too
            $eval_scripts = $this->OnePage->getConfig('eval_scripts');
            $template .= "<script type='text/javascript'>{$onepagejs};OnePage.site_url='{{site_url}}';OnePage.updateRoutes();OnePage.eval_scripts={$eval_scripts};" . join(";", $this->scripts) . "</script>";
            $template = "{% extends 'base." . $this->OnePage->getConfig('templates_extension') . "' %}{% block content %}{$template}{% endblock %}";
        }
        $variables['site_url']      = $this->OnePage->getConfig('site_url');
        $this->sectionsFiles[$name] = $template;
        if (!isset($variables['title'])) {
            $variables['title'] = $this->OnePage->getConfig("default_title");
        }
        foreach ($this->sectionsFiles as $key => $value) {
            /*convert relative links url in absolute*/
            $this->sectionsFiles[$key] = preg_replace('/(href|src)=[\'\"](?!#|(https?:)|(\/)|(\.\.)|({{))([^\'\"]+)[\'\"]/i', '\1="' . $this->OnePage->getConfig('site_url') . '\6"', $value);
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

    public function addTwigExtension($class, callable $callback, $extension)
    {
        Renderer::$twigExtensions[] = ["class" => $class, "callback" => $callback, "extension" => $extension];
    }

    public static function getTwigExtensions(){
        return Renderer::$twigExtensions;
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
}
