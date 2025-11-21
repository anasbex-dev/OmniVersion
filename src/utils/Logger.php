<?php

declare(strict_types=1);

namespace OmniVersion\utils;

class Logger {
    
    private string $logFile;
    private bool $debugMode;
    
    public function __construct(string $logFile, bool $debugMode = false) {
        $this->logFile = $logFile;
        $this->debugMode = $debugMode;
        
        // Ensure log directory exists
        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
    
    public function info(string $message): void {
        $this->log("INFO", $message);
    }
    
    public function warning(string $message): void {
        $this->log("WARNING", $message);
    }
    
    public function error(string $message): void {
        $this->log("ERROR", $message);
    }
    
    public function debug(string $message): void {
        if ($this->debugMode) {
            $this->log("DEBUG", $message);
        }
    }
    
    private function log(string $level, string $message): void {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public function setDebugMode(bool $debugMode): void {
        $this->debugMode = $debugMode;
    }
    
    public function getLogPath(): string {
        return $this->logFile;
    }
}