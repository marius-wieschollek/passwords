<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\AppInfo\Application;
use OCP\ILogger;
use OCP\Util;

/**
 * Class LoggingService
 *
 * @package OCA\Passwords\Services
 */
class LoggingService {
    /**
     * @var ILogger
     */
    protected $logger;

    /**
     * LoggingService constructor.
     *
     * @param ILogger $logger
     */
    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    /**
     * @param string|array $message
     * @param array        $context
     */
    public function fatal($message, array $context = []): void {
        $this->log(Util::FATAL, $message, $context);
    }

    /**
     * @param string|array $message
     * @param array        $context
     */
    public function error($message, array $context = []): void {
        $this->log(Util::ERROR, $message, $context);
    }

    /**
     * @param string|array $message
     * @param array        $context
     */
    public function warning($message, array $context = []): void {
        $this->log(Util::WARN, $message, $context);
    }

    /**
     * @param string|array $message
     * @param array        $context
     */
    public function info($message, array $context = []): void {
        $this->log(Util::INFO, $message, $context);
    }

    /**
     * @param string|array $message
     * @param array        $context
     */
    public function debug($message, array $context = []): void {
        $this->log(Util::DEBUG, $message, $context);
    }

    /**
     * @param int          $level
     * @param string|array $message
     * @param array        $context
     */
    public function log(int $level, $message, array $context = []): void {
        if(is_array($message)) {
            $string  = array_shift($message);
            $message = sprintf($string, ...$message);
        }
        $context['app'] = Application::APP_NAME;

        $this->logger->log($level, $message, $context);
    }

    /**
     * @param \Throwable $exception
     * @param array      $context
     */
    public function logException(\Throwable $exception, array $context = []): void {
        $context['app'] = Application::APP_NAME;
        $this->logger->logException($exception, $context);
    }
}