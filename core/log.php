<?php
class log {
    private $logDir;
    private $logName;
    private $useLog = true;
    private $hold = false;
    private $fileHandler;

    public function __construct() {
        $this->logDir = settings::rootDir() . DIRECTORY_SEPARATOR . 'log';
        $this->logName = date('Y-m-d') . '.log';
        if($this->useLog)
            $this->hold = true;
    }

    public function setLogDir($path) {
        if(!file_exists($path)) {
            @mkdir($path,0644);
        }
        $this->logDir = rtrim($path,'/');
    }

    public function getLogDir() {
        return $this->logDir;
    }

    public function getPath() {
        return $this->logDir . DIRECTORY_SEPARATOR . $this->logName;
    }

    public function isEnabled() {
        return $this->useLog;
    }

    public function disabled() {
        $this->useLog = false;
        $this->hold = false;

        if($this->fileHandler)
            fclose($this->fileHandler);
    }

    public function addMessage($message) {
        if(!$this->fileHandler)
            $this->fileHandler = fopen($this->getPath(), 'a');

        $message = is_array($message) || is_object($message) ? print_r($message,true) : $message;

        $line = date('Y-m-d h:i:s') . " - " . $message . "\r\n";
        fputs($this->fileHandler,$line);

        if($this->fileHandler && !$this->hold)
            fclose($this->fileHandler);
    }

    function __destruct(){
        if($this->fileHandler)
            fclose($this->fileHandler);
    }
}