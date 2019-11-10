<?php
namespace OnePagePHP;
require_once __dir__ . "/interfaces/error_handler.php";
require_once __dir__ . '/logger.php';

/**
 *
 */
class ErrorHandler implements Interfaces\ErrorHandler
{

    private $debugMode = false;
    private $logger = null;
    private $displayOn = '';

    public function __construct(Loader &$OnePage)
    {
        set_error_handler([$this, 'err_handler']);
        set_exception_handler([$this, 'exc_handler']);
        ini_set('display_errors', 'Off'); //disable default log report
        $config          = $OnePage->getConfig("error_handler");
        $this->debugMode = $config["debug_mode"];
        $this->displayOn = $config["display_on"];
        $this->logger = new Logger($config["display_on"]);
    }


    public function getLogger()
    {return $this->logger;}
    public function getDebugMode()
    {return $this->debugMode;}

    public function err_handler($errno, $errstr, $errfile, $errline, $errcontext)
    {
        $l = error_reporting();
        if ($l & $errno) {

            $exit = false;
            switch ($errno) {
                case E_USER_ERROR:
                    $type = 'Fatal Error';
                    $exit = true;
                    $method = "error";
                    break;
                case E_USER_WARNING:
                case E_WARNING:
                    $type = 'Warning';
                    $method = "warn";
                    break;
                case E_USER_NOTICE:
                case E_NOTICE:
                case @E_STRICT:
                    $type = 'Notice';
                    $method = "log";
                    break;
                case @E_RECOVERABLE_ERROR:
                    $type = 'Catchable';
                    $method = "log";
                    break;
                default:
                    $type = 'Unknown Error';
                    $method = "error";
                    $exit = true;
                    break;
            }

            $exception = new \ErrorException($type . ': ' . $errstr, 0, $errno, $errfile, $errline);

            if ($exit) {
                $this->exc_handler($exception);
            } else {
                $this->logger->addHtmlError($exception);
                $this->logger->addConsoleLog($method,$exception);
            }

        }
        return false;
    }

    public function exc_handler($exception)
    {
        $message = $exception->getMessage();
        $trace   = $exception->getTraceAsString();
        $log     = $exception->getMessage() . "\r\n" . $exception->getTraceAsString() . "\r\n";
        if (ini_get('log_errors')) {
            error_log($log, 0);
        }
        http_response_code(500);
        if (!$this->debugMode) {
            die();
        }
        $this->logger->addHtmlError($exception);
        $this->logger->addConsoleLog("error",$exception);
        $headers = getallheaders();
        if (isset($headers["X-OnePagePHP"])) {
            $x_onepagephp = json_decode($headers["X-OnePagePHP"], true);
            $fullMode     = $x_onepagephp["fullMode"];
        } else {
            $fullMode = true;
        }
        $errors = join("<br>", $this->logger->getHtmlErrors());
        $log = join(";\n", $this->logger->getConsoleLog());
        if ($fullMode) {
            if($this->displayOn!=Interfaces\Log::LOG_NONE){
                echo "<!DOCTYPE html><!-- Error handler --><html><head></head><body>";
                    echo $errors;
                    echo "<script>{$log}</script>";
                echo "</body></html>";
            }
        } else {
            header('Content-Type: application/json');
            $output = json_encode([
                "title"   => "Error :{$message}",
                "content" => "",
                'scripts' => "",
                "errors" => $errors,
                "console" => $log
            ]);
            echo $output;
        }
        die();
    }

}
