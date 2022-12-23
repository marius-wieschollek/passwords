<?php
/*
 * @copyright 2022 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Services;

use Exception;
use OC;
use OC\AppConfig;
use OC\SystemConfig;
use OCA\Passwords\AppInfo\Application;
use OCP\Cache\CappedMemoryCache;
use OCP\HintException;
use OCP\IConfig;
use ReflectionClass;
use ReflectionException;

/**
 * Class ConfigurationService
 *
 * @package OCA\Passwords\Services
 */
class ConfigurationService {

    /**
     * @var IConfig
     */
    protected IConfig $config;

    /**
     * @var string|null
     */
    protected ?string $userId;

    /**
     * @param IConfig            $config
     * @param EnvironmentService $environment
     */
    public function __construct(IConfig $config, EnvironmentService $environment) {
        $this->config = $config;
        $this->userId = $environment->getUserId();
    }

    /**
     * @param string      $key
     * @param null        $default
     * @param null|string $user
     * @param string      $app
     *
     * @return string|null
     * @throws Exception
     */
    public function getUserValue(string $key, $default = null, ?string $user = null, string $app = Application::APP_NAME): ?string {
        $userId = $this->getUserId($user);

        return $this->config->getUserValue($userId, $app, $key, $default);
    }

    /**
     * @param string $key
     * @param null   $default
     * @param string $app
     *
     * @return string|null
     */
    public function getAppValue(string $key, $default = null, string $app = Application::APP_NAME): ?string {
        return $this->config->getAppValue($app, $key, $default);
    }

    /**
     * @param string $key
     * @param null   $default
     *
     * @return mixed
     */
    public function getSystemValue(string $key, $default = null): mixed {
        return $this->config->getSystemValue($key, $default);
    }

    /**
     * @param string      $key
     * @param string      $value
     * @param null|string $user
     * @param string      $app
     *
     * @throws Exception
     */
    public function setUserValue(string $key, string $value, ?string $user = null, string $app = Application::APP_NAME): void {
        $userId = $this->getUserId($user);
        $this->config->setUserValue($userId, $app, $key, $value);
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function setAppValue(string $key, string $value): void {
        $this->config->setAppValue(Application::APP_NAME, $key, $value);
    }

    /**
     * @param string $key
     * @param        $value
     *
     * @throws HintException
     */
    public function setSystemValue(string $key, $value): void {
        $this->config->setSystemValue($key, $value);
    }

    /**
     * @param string      $key
     * @param null|string $user
     * @param string      $app
     *
     * @return bool
     * @throws Exception
     */
    public function hasUserValue(string $key, ?string $user = null, string $app = Application::APP_NAME): bool {
        $userId = $this->getUserId($user);

        $keys = $this->config->getUserKeys($userId, $app);

        return in_array($key, $keys);
    }

    /**
     * @param string $key
     * @param string $app
     *
     * @return bool
     */
    public function hasAppValue(string $key, string $app = Application::APP_NAME): bool {
        $keys = $this->config->getAppKeys($app);

        return in_array($key, $keys);
    }

    /**
     * @param string      $key
     * @param null|string $user
     *
     * @throws Exception
     */
    public function deleteUserValue(string $key, ?string $user = null): void {
        $userId = $this->getUserId($user);
        $this->config->deleteUserValue($userId, Application::APP_NAME, $key);
    }

    /**
     * @param string $key
     */
    public function deleteAppValue(string $key): void {
        $this->config->deleteAppValue(Application::APP_NAME, $key);
    }

    /**
     * @param string $key
     */
    public function deleteSystemValue(string $key): void {
        $this->config->deleteSystemValue($key);
    }

    /**
     * @param string $appName
     *
     * @return bool
     */
    public function isAppEnabled(string $appName): bool {
        return $this->config->getAppValue($appName, 'enabled', 'no') === 'yes';
    }

    /**
     * @return IConfig
     */
    public function getConfig(): IConfig {
        return $this->config;
    }

    /**
     * Clear the config cache
     */
    public function clearCache(): void {
        try {
            $class    = new ReflectionClass($this->config);
            $property = $class->getProperty('userCache');
            $property->setAccessible(true);
            $property->setValue($this->config, new CappedMemoryCache());
        } catch(ReflectionException $e) {
        }

        try {
            // @TODO Use container instead
            $appConfig = OC::$server->get(AppConfig::class);
            $class     = new ReflectionClass($appConfig);
            $property  = $class->getProperty('configLoaded');
            $property->setAccessible(true);
            $property->setValue($appConfig, false);
        } catch(Exception $e) {
        }

        try {
            // @TODO Use container instead
            $systemConfig = OC::$server->get(SystemConfig::class);
            $class        = new ReflectionClass($systemConfig);
            $method       = $class->getMethod('readData');
            $method->setAccessible(true);
            $method->invoke($systemConfig);
        } catch(Exception $e) {
        }
    }

    /**
     * @return string
     */
    public function getTempDir(): string {
        $tempDir = $this->getSystemValue('tempdirectory', '/tmp/');
        if(substr($tempDir, -1) !== DIRECTORY_SEPARATOR) $tempDir.=DIRECTORY_SEPARATOR;

        return $tempDir;
    }

    /**
     * @param string|null $user
     *
     * @return string|null
     * @throws Exception
     */
    protected function getUserId(string $user = null): ?string {
        if($this->userId !== null && $user !== null && $this->userId !== $user) {
            throw new Exception("Illegal user configuration access request by {$this->userId} for {$user}");
        }

        return $this->userId === null ? $user:$this->userId;
    }
}