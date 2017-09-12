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
    public function __construct($userId, IConfig $config) {
        $this->config = $config;
        $this->userId = $userId;
    }

    /**
     * @param string $key
     *
     * @param null   $default
     *
     * @return string
     */
    public function getUserValue(string $key, $default = null) {
        return $this->config->getUserValue($this->userId, Application::APP_NAME, $key, $default);
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
}