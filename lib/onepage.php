<?php
namespace OnePagePHP;

require_once __dir__ . '/router.php';
require_once __dir__ . '/renderer.php';
require_once __dir__ . '/errorhandler.php';

/**
 *
 */
class OnePage
{
    
    protected $fullMode         = true;
    protected $config           = [];
    protected $controllerPath   = "";
    protected $templateFile     = "";
    protected $templateString   = "";
    protected $url              = "";
    protected $router           = null;
    protected $renderer = null;

    public function __construct(array $config = [])
    {
        new ErrorHandler();
        $errors = &ErrorHandler::$list;
        $headers = getallheaders();
        if(isset($headers["X-OnePagePHP"])){
            $x_onepagephp = json_decode($headers["X-OnePagePHP"],true);
            $this->fullMode         = $x_onepagephp["fullMode"];
        }else{
            $this->fullMode = true;
        }
        $this->config           = $config;
        if (preg_match('/[^\/]$/', $this->config['site_url'])) {
            $this->config['site_url'] .= '/';
        }
        if (!isset($_SERVER["REQUEST_URI"])) {
            trigger_error("No URL", E_USER_ERROR);
        }
        $relativePath = preg_replace('/\/\/[^\/]+(\/.*)/', '\1', $this->config['site_url']);
        $relativePath = "/^" .str_replace('/', '\/', $relativePath) ."/";
        $requestURI = preg_replace($relativePath, "", urldecode($_SERVER["REQUEST_URI"]));
        //no url,avoid open loader.php directly
        $paths     = explode("?", $requestURI); //separate url from parameters
        $this->url = $paths[0];
        if($this->url=="/")$path = "index";
        else{
            $paths     = explode("/", $paths[0]);
            foreach ($paths as $key => $value) {
                if ($value == '') {
                    $paths[$key] = "index";
                }
            }
            $path           = join("/", $paths);
        }
        foreach ($this->config["paths"] as $key => $value) {
            /* set absolute paths */
            $this->config["paths"][$key] = $this->config["root_dir"] . "/{$value}/";
        }
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            foreach ($this->config["paths"] as $key => $value) {
                $this->config["paths"][$key] = preg_replace("/\//", "\\", $value);
            }
        }
        $this->controllerPath = $this->config["paths"]["controllers"] . "{$path}.php";
        $this->templateFile   = "{$path}.{$config['templates_extension']}";
        
        if ($config["enable_router"]) {
            $this->router = new Router($this);
            $GLOBALS['router'] = $this->router;
        }
        $this->renderer = new Renderer($this);
        $GLOBALS['renderer'] = $this->renderer;
    }

    protected function checkConfig(array &$config)
    {

    }

    public static function loadJSON(string $path, bool $convert_to_array = true)
    {
        $string = file_get_contents($path);
        $json   = json_decode($string, $convert_to_array);
        return $json;
    }

    public function getConfig(string $name){
        if(!isset($this->config[$name]))trigger_error("The index {$name} doesnt exist", E_USER_WARNING); 
        else return $this->config[$name];
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
    public function getFullMode()
    {return $this->fullMode;}
    public function getRouter()
    {return $this->router;}
    public function getRenderer()
    {return $this->renderer;}

}
