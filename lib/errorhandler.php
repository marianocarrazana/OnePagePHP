<?php
namespace OnePagePHP;

/**
 *
 */
class ErrorHandler
{

    public static $list = array();

    public function __construct($log = false)
    {
        set_error_handler('OnePagePHP\ErrorHandler::err_handler');
        set_exception_handler('OnePagePHP\ErrorHandler::exc_handler');
        ini_set('display_errors','Off');//disable default log report
    }

    public static function err_handler($errno, $errstr, $errfile, $errline, $errcontext)
    {
        $l = error_reporting();
        if ($l & $errno) {

            $exit = false;
            switch ($errno) {
                case E_USER_ERROR:
                    $type = 'Fatal Error';
                    $exit = true;
                    break;
                case E_USER_WARNING:
                case E_WARNING:
                    $type = 'Warning';
                    break;
                case E_USER_NOTICE:
                case E_NOTICE:
                case @E_STRICT:
                    $type = 'Notice';
                    break;
                case @E_RECOVERABLE_ERROR:
                    $type = 'Catchable';
                    break;
                default:
                    $type = 'Unknown Error';
                    $exit = true;
                    break;
            }

            $exception = new \ErrorException($type . ': ' . $errstr, 0, $errno, $errfile, $errline);

            if ($exit) {
                ErrorHandler::exc_handler($exception);
            } else {
                ErrorHandler::$list[] = ErrorHandler::renderException($exception);
            }

        }
        return false;
    }

    public function exc_handler($exception)
    {
        $message = $exception->getMessage();
        $trace   = $exception->getTraceAsString();
        $log = $exception->getMessage() . "\r\n" . $exception->getTraceAsString() . "\r\n";
        if (ini_get('log_errors')) {
            error_log($log, 0);
        }
        http_response_code(500);
        $headers = getallheaders();
        if(isset($headers["X-OnePagePHP"])){
            $x_onepagephp = json_decode($headers["X-OnePagePHP"],true);
            $fullMode         = $x_onepagephp["fullMode"];
        }else{
            $fullMode = true;
        }
        if($fullMode){
            echo ErrorHandler::renderException($exception);
        }else{
            header('Content-Type: application/json');
            echo json_encode(["message"=>$message,"trace"=>$trace]);
        }
        die();
    }

    public static function renderException($exception){
        $message = $exception->getMessage();
                    $trace = $exception->getTrace();
                    $severity = $exception->getSeverity();
                    if($severity<512)$style = "background:red;color:black";
                    else if($severity==512)$style = "background:yellow;color:black";
                    else $style = "background:white;color:black";
        $out = "<table style='background:black;color:white'>
            <tr  style='{$style}'><td colspan='3'>{$message}</td></tr>
            <tr><th>File</th><th>Line</th><th>Function</th></tr>";
            foreach ($trace as $value) {
                $out .= "<tr style='font-size:80%'><td>";
                if(isset($value['file']))$out .= $value['file'];
                $out .="</td><td>";
                if(isset($value['line']))$out .= $value['line'];
                $out .="</td><td>";
                if(isset($value['function'])){
                    $out .= $value['function'];
                    if(isset($value['args'])){
                        foreach ($value['args'] as $key => $arg) {
                            $t = gettype($arg);
                            $value['args'][$key] = $t=="string" || $t=="integer" || 
                            $t=="boolean" || $t=="double"?(string)$arg:$t;
                        }
                    $args = join(",",$value['args']);
                    $out .= "({$args})";
                    }
                }
                $out .= "</td></tr>";
            }
            
            $out .= "</table>";
            return $out;
    }

}
