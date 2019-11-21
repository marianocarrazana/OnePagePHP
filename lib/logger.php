<?php
namespace OnePagePHP;

require_once __dir__ . "/interfaces/log.php";
use OnePagePHP\Interfaces\Log;
/**
 *
 */
class Logger implements Log
{

    private $htmlErrors = [];
    private $consoleLog       = [];
    private $displayOn;

    public function __construct($displayOn)
    {
        $this->displayOn = $displayOn;
    }

    public function log($message){
        $message = $this->cleanForConsole($message);
        $this->consoleLog[]="console.log({$message})";
    }
    public function warn($message){
                $message = $this->cleanForConsole($message);
        $this->consoleLog[]="console.warn({$message})";
    }
    public function error($message){
                $message = $this->cleanForConsole($message);
        $this->consoleLog[]="console.error({$message})";
    }
    public function info($message){
                $message = $this->cleanForConsole($message);
        $this->consoleLog[]="console.info({$message})";
    }

    public function cleanForConsole($message){
        if(is_string($message)){
            $message = str_replace(["\n","\r"], "", $message);
            $message = "\"".str_replace("\"", "'", $message)."\"";
        }else if(is_object($message)){
            $message = serialize($message);
        }else if(is_array($message)){
            $message = json_encode($message);
        }else if(is_bool($message)){
            $message = $message?"true":"false";
        }else if(!is_numeric($message)){
            $message = "'Type not supported by logger: ".gettype($message)."'";
        }
        return $message;
    }

    public function getHtmlErrors()
    {return $this->htmlErrors;}
    public function getConsoleLog()
    {return $this->consoleLog;}

    public function addHtmlError($exception)
    {
        if ($this->displayOn != Log::LOG_HTML && $this->displayOn != Log::LOG_ALL)return 0;
        $message = $exception->getMessage();
        $trace   = $exception->getTrace();
        if (!method_exists($exception, "getSeverity")) {
            $severity = 1024;
        } else {
            $severity = $exception->getSeverity();
        }

        if ($severity < 512) {
            $style = "background:red;color:black";
        } else if ($severity == 512) {
            $style = "background:yellow;color:black";
        } else if ($severity == 1024) {
            $style = "background:green;color:white";
        } else {
            $style = "background:white;color:black";
        }

        $out = "<table style='background:black;color:white'>
            <tr  style='{$style}'><td colspan='3'>{$message}</td></tr>
            <tr><th>File</th><th>Line</th><th>Function</th></tr>";
        foreach ($trace as $value) {
            $out .= "<tr style='font-size:80%'><td>";
            if (isset($value['file'])) {
                $out .= $value['file'];
            }

            $out .= "</td><td>";
            if (isset($value['line'])) {
                $out .= $value['line'];
            }

            $out .= "</td><td>";
            if (isset($value['function'])) {
                $out .= $value['function'];
                if (isset($value['args'])) {
                    foreach ($value['args'] as $key => $arg) {
                        $t                   = gettype($arg);
                        $value['args'][$key] = $t == "string" || $t == "integer" ||
                        $t == "boolean" || $t == "double" ? (string) $arg : $t;
                    }
                    $args = join(",", $value['args']);
                    $out .= "({$args})";
                }
            }
            $out .= "</td></tr>";
        }

        $out .= "</table>";
        $this->htmlErrors[] = $out;
    }

    public function addConsoleLog($method, $exception)
    {
        if ($this->displayOn != Log::LOG_CONSOLE && $this->displayOn != Log::LOG_ALL)return 0;
        $message = $this->cleanForConsole($exception->getMessage());
        $trace   = $exception->getTrace();
        $this->consoleLog[] = "console.{$method}({$message})";
        $this->consoleLog[] = "console.groupCollapsed('View trace')";
        $table = [];
        foreach ($trace as $value) {
            $row = [];
            if (isset($value['file'])) {
                //$this->consoleLog[] = "console.{$method}('File: {$value['file']}')";
                $row["file"] = $value['file'];
            }
            if (isset($value['line'])) {
                //$this->consoleLog[] = "console.{$method}('Line: {$value['line']}')";
                $row["line"] = $value['line'];
            }
            if (isset($value['function'])) {
                //$function = "Function: ".$value['function'];
                $function = $value['function'];
                if (isset($value['args'])) {
                    foreach ($value['args'] as $key => $arg) {
                        $t                   = gettype($arg);
                        $value['args'][$key] = $t == "string" || $t == "integer" ||
                        $t == "boolean" || $t == "double" ? (string) $arg : $t;
                    }
                    $args = join(",", $value['args']);
                    $function .= "({$args})";
                }
                //$this->consoleLog[] = "console.{$method}('{$function}')";
                $row["function"] = $function;
            }
            $table[] = $row;
        }
        $this->consoleLog[] = "console.table(".json_encode($table).")";
        $this->consoleLog[] = "console.groupEnd()";
    }

}
