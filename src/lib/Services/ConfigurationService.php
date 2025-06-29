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
use OCA\Passwords\AppInfo\Application;
use OCP\Cache\CappedMemoryCache;
use OCP\HintException;
use OCP\IAppConfig;
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
     * @var string|null
     */
    protected ?string $userId;

    /**
     * @param IConfig            $config
     * @param EnvironmentService $environment
     */
    public function __construct(protected IConfig $config, protected IAppConfig $appConfig, EnvironmentService $environment) {
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
        $value = $this->appConfig->getValueString($app, $key);

        return $value === '' ? $default:$value;
    }

    /**
     * @param string   $key
     * @param int $default
     * @param string   $app
     *
     * @return int
     */
    public function getAppValueInt(string $key, int $default = 0, string $app = Application::APP_NAME): int {
        return $this->appConfig->getValueInt($app, $key, $default);
    }

    /**
     * @param string   $key
     * @param bool $default
     * @param string   $app
     *
     * @return bool
     */
    public function getAppValueBool(string $key, bool $default = false, string $app = Application::APP_NAME): bool {
        return $this->appConfig->getValueBool($app, $key, $default);
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
        $this->appConfig->setValueString(Application::APP_NAME, $key, $value);
    }

    /**
     * @param string $key
     * @param int $value
     */
    public function setAppValueInt(string $key, int $value): void {
        $this->appConfig->setValueInt(Application::APP_NAME, $key, $value);
    }

    /**
     * @param string $key
     * @param bool $value
     */
    public function setAppValueBool(string $key, bool $value): void {
        $this->appConfig->setValueBool(Application::APP_NAME, $key, $value);
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
        $keys = $this->appConfig->getKeys($app);

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
        $this->appConfig->deleteKey(Application::APP_NAME, $key);
    }

    /**
     * @param string $key
     */
    public function deleteSystemValue(string $key): void {
        $this->config->deleteSystemValue($key);
    }

    /**
     * @return string|null
     */
    public function getServerSecret(): ?string {
        return $this->getSystemValue(
            'passwords.secret',
            $this->getSystemValue('secret')
        );
    }

    /**
     * @param string $secret
     *
     * @throws HintException
     */
    public function setServerSecret(string $secret): void {
        $this->setSystemValue(
            'passwords.secret',
            $secret
        );
    }

    /**
     * @param string $appName
     *
     * @return bool
     */
    public function isAppEnabled(string $appName): bool {
        return $this->appConfig->getValueString($appName, 'enabled', 'no') === 'yes';
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
            $class    = new ReflectionClass($this->appConfig);
            $property = $class->getProperty('fastLoaded');
            $property->setAccessible(true);
            $property->setValue($this->appConfig, false);
            $property = $class->getProperty('lazyLoaded');
            $property->setAccessible(true);
            $property->setValue($this->appConfig, false);
        } catch(Exception $e) {
        }

        try {
            // @TODO Use container instead
            $class     = new ReflectionClass($this->config);
            $property1 = $class->getProperty('systemConfig');
            $property1->setAccessible(true);
            $systemConfig = $property1->getValue($this->config);

            $scClass   = new ReflectionClass($systemConfig);
            $property2 = $scClass->getProperty('config');
            $property2->setAccessible(true);
            $config = $property2->getValue($systemConfig);

            $cfClass  = new ReflectionClass($config);
            $method = $cfClass->getMethod('readData');
            $method->setAccessible(true);
            $method->invoke($config);

            $property2->setValue($systemConfig, $config);
            $property1->setValue($this->config, $systemConfig);
        } catch(Exception $e) {
        }
    }

    /**
     * @return string
     */
    public function getTempDir(): string {
        $tempDir = $this->getSystemValue('tempdirectory', '/tmp/');
        if(substr($tempDir, -1) !== DIRECTORY_SEPARATOR) $tempDir .= DIRECTORY_SEPARATOR;

        return $tempDir;
    }

    /**
     * @param string|null $user
     *
     * @return string|null
     * @throws Exception
     */
    protected function getUserId(?string $user = null): ?string {
        if($this->userId !== null && $user !== null && $this->userId !== $user) {
            throw new Exception("Illegal user configuration access request by {$this->userId} for {$user}");
        }

        return $this->userId === null ? $user:$this->userId;
    }
}
