<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\AppInfo\Application;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class LoggingService
 *
 * @package OCA\Passwords\Services
 */
class LoggingService {

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * LoggingService constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * System is unusable.
     *
     * @param string|array $message
     * @param array        $context
     *
     * @return LoggingService
     */
    public function emergency($message, array $context = []): LoggingService {
        return $this->log('emergency', $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string|array $message
     * @param array        $context
     *
     * @return LoggingService
     */
    public function alert($message, array $context = []): LoggingService {
        return $this->log('alert', $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string|array $message
     * @param array        $context
     *
     * @return LoggingService
     */
    public function critical($message, array $context = []): LoggingService {
        return $this->log('critical', $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string|array $message
     * @param array        $context
     *
     * @return LoggingService
     */
    public function error($message, array $context = []): LoggingService {
        return $this->log('error', $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string|array $message
     * @param array        $context
     *
     * @return LoggingService
     */
    public function warning($message, array $context = []): LoggingService {
        return $this->log('warning', $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string|array $message
     * @param array        $context
     *
     * @return LoggingService
     */
    public function notice($message, array $context = []): LoggingService {
        return $this->log('notice', $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string|array $message
     * @param array        $context
     *
     * @return LoggingService
     */
    public function info($message, array $context = []): LoggingService {
        return $this->log('info', $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string|array $message
     * @param array        $context
     *
     * @return LoggingService
     */
    public function debug($message, array $context = []): LoggingService {
        return $this->log('debug', $message, $context);
    }

    /**
     * Set log level based on priority
     * Priority under 1 is debug, over 1 is info
     *
     * @param string|array $message
     * @param int          $priority
     * @param array        $context
     *
     * @return LoggingService
     */
    public function debugOrInfo($message, int $priority, array $context = []): LoggingService {
        $level = $priority < 1 ? 'debug':'info';

        return $this->log($level, $message, $context);
    }

    /**
     * @param string       $level
     * @param string|array $message
     * @param array        $context
     *
     * @return LoggingService
     */
    protected function log(string $level, $message, array $context = []): LoggingService {
        if(is_array($message)) {
            $string  = array_shift($message);
            $message = sprintf($string, ...$message);
        }
        $context['app'] = Application::APP_NAME;

        $this->logger->{$level}($message, $context);

        return $this;
    }

    /**
     * @param Throwable $exception
     * @param array     $context
     *
     * @return LoggingService
     */
    public function logException(Throwable $exception, array $context = [], string $message = null): LoggingService {
        $context['app'] = Application::APP_NAME;
        $context['exception'] = $exception;
        $this->logger->emergency($message ?? $exception->getMessage(), $context);

        return $this;
    }
}