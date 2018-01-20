<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 02:10
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\AppInfo\Application;
use OCP\IConfig;

/**
 * Class ConfigurationService
 *
 * @package OCA\Passwords\Services
 */
class ConfigurationService {

    /**
     * @var IConfig
     */
    protected $config;

    /**
     * @var string
     */
    protected $userId;

    /**
     * FaviconService constructor.
     *
     * @param string  $userId
     * @param IConfig $config
     */
    public function __construct(?string $userId, IConfig $config) {
        $this->config = $config;
        $this->userId = $userId;
        if($this->config->getSystemValue('maintenance', false)) {
            $this->userId = null;
        }
    }

    /**
     * @param string $key
     * @param null   $default
     *
     * @param null   $user
     *
     * @return string
     */
    public function getUserValue(string $key, $default = null, $user = null) {
        $userId = $this->userId;
        if($userId === null) $userId = $user;

        return $this->config->getUserValue($userId, Application::APP_NAME, $key, $default);
    }

    /**
     * @param string $key
     * @param null   $default
     *
     * @return string
     */
    public function getAppValue(string $key, $default = null) {
        return $this->config->getAppValue(Application::APP_NAME, $key, $default);
    }

    /**
     * @param string $key
     * @param null   $default
     *
     * @return mixed
     */
    public function getSystemValue(string $key, $default = null) {
        return $this->config->getSystemValue($key, $default);
    }

    /**
     * @param string $key
     * @param        $value
     * @param null   $user
     *
     * @throws \OCP\PreConditionNotMetException
     */
    public function setUserValue(string $key, $value, $user = null): void {
        $userId = $this->userId;
        if($userId === null) $userId = $user;
        $this->config->setUserValue($userId, Application::APP_NAME, $key, $value);
    }

    /**
     * @param string $key
     * @param        $value
     */
    public function setAppValue(string $key, $value): void {
        $this->config->setAppValue(Application::APP_NAME, $key, $value);
    }

    /**
     * @param string $key
     * @param        $value
     */
    public function setSystemValue(string $key, $value): void {
        $this->config->setSystemValue($key, $value);
    }

    /**
     * @param string $key
     * @param null   $user
     */
    public function deleteUserValue(string $key, $user = null): void {
        $userId = $this->userId;
        if($userId === null) $userId = $user;
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
     * @return string
     */
    public function getTempDir(): string {
        return $this->getSystemValue('tempdirectory', '/tmp/');
    }
}