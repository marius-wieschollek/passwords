<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OC\Authentication\Token\IProvider;
use OCA\Passwords\AppInfo\Application;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUserManager;

/**
 * Class EnvironmentService
 *
 * @package OCA\Passwords\Services
 */
class EnvironmentService {

    /**
     * @var IConfig
     */
    protected $config;

    /**
     * @var ILogger
     */
    protected $logger;

    /**
     * @var null|string
     */
    protected $userId;

    /**
     * @var null|string
     */
    protected $userLogin;

    /**
     * @var bool
     */
    protected $isCliMode;

    /**
     * @var bool
     */
    protected $isCronJob;

    /**
     * @var bool
     */
    protected $isAppUpdate;

    /**
     * @var bool
     */
    protected $isGlobalMode;

    /**
     * @var mixed
     */
    protected $maintenanceEnabled;
    /**
     * @var IUserManager
     */
    private $userManager;

    /**
     * EnvironmentService constructor.
     *
     * @param string|null $userId
     * @param IConfig     $config
     * @param IRequest    $request
     * @param ILogger     $logger
     */
    public function __construct(string $userId = null, IUserManager $userManager, IConfig $config, IRequest $request, ILogger $logger) {
        $this->maintenanceEnabled = $config->getSystemValue('maintenance', false);
        $this->isCliMode          = PHP_SAPI === 'cli';
        $this->logger             = $logger;
        $this->config             = $config;
        $this->checkIfCronJob($request);
        $this->checkIfAppUpdate($request);
        $this->isGlobalMode = $this->maintenanceEnabled || $this->isCliMode || $this->isAppUpdate || $this->isCronJob;

        if($this->isGlobalMode) {
            // Debugging info
            $logger->debug('Passwords runs '.($request->getRequestUri() ? $request->getRequestUri():$request->getScriptName()).' in global mode', ['app' => Application::APP_NAME]);
        }
        if(!$this->isGlobalMode) $this->userId = $userId;
        $this->userManager = $userManager;
    }

    /**
     * @return null|string
     */
    public function getUserId(): ?string {
        return $this->userId;
    }

    /**
     * @return null|string
     * @throws \Exception
     */
    public function getUserLogin() {
        if($this->userId === null) return null;
        if($this->userLogin !== null) return $this->userLogin;
        $password = null;

        try {
            /** @var IProvider $tokenProvider */
            $tokenProvider = \OC::$server->query(IProvider::class);

            if(isset($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_USER'])) {
                $this->userLogin = $_SERVER['PHP_AUTH_USER'];

                $token    = $tokenProvider->getToken($_SERVER['PHP_AUTH_PW']);
                $password = $tokenProvider->getPassword($token, $_SERVER['PHP_AUTH_PW']);
            } else {
                $sessionId    = \OC::$server->getSession()->getId();
                $sessionToken = $tokenProvider->getToken($sessionId);

                if($sessionToken->getUID() === $this->userId) {
                    $password        = $tokenProvider->getPassword($sessionToken, $sessionId);
                    $loginName       = $sessionToken->getLoginName();
                    $this->userLogin = $loginName !== null ? $loginName:$this->userId;
                } else {
                    $this->logger->error('Cancelling session due to user id mismatch.', ['app' => Application::APP_NAME]);
                    $tokenProvider->invalidateTokenById($sessionToken->getUID(), $sessionToken->getId());
                }
            }
        } catch(\Throwable $e) {
            $this->logger->logException($e, ['app' => Application::APP_NAME]);
            $this->userLogin = $this->userId;
        }

        /** @var \OC\User\User|false $loginResult */
        $loginResult = $this->userManager->checkPasswordNoLogging($this->userLogin, $password);
        if($loginResult === false) {
            $loginUser = $this->userManager->get($this->userLogin);
            $loginUID  = $loginUser === null ? null:$loginUser->getUID();
        } else {
            $loginUID = $loginResult->getUid();
        }

        if($loginUID !== $this->userId) {
            $this->logger->error('User id and login name do not match. Passwords does not support impersonating.', ['app' => Application::APP_NAME]);
            throw new \Exception("Could not determine login name for {$this->userId}");
        }

        return $this->userLogin;
    }

    /**
     * @return bool
     */
    public function isCliMode(): bool {
        return $this->isCliMode;
    }

    /**
     * @return bool
     */
    public function isCronJob(): bool {
        return $this->isCronJob;
    }

    /**
     * @return bool
     */
    public function isGlobalMode(): bool {
        return $this->isGlobalMode;
    }

    /**
     * @return mixed
     */
    public function isMaintenanceEnabled(): bool {
        return $this->maintenanceEnabled;
    }

    /**
     * @param IRequest $request
     */
    protected function checkIfCronJob(IRequest $request): void {
        $requestUri = $request->getRequestUri();
        $cronMode   = $this->config->getAppValue('core', 'backgroundjobs_mode', 'ajax');

        $this->isCronJob = ($requestUri === '/index.php/apps/passwords/cron/sharing') ||
                           ($requestUri === '/cron.php' && in_array($cronMode, ['ajax', 'webcron'])) ||
                           ($this->isCliMode && $cronMode === 'cron' && strpos($request->getScriptName(), 'cron.php') !== false);
    }

    /**
     * @param IRequest $request
     */
    protected function checkIfAppUpdate(IRequest $request): void {
        $this->isAppUpdate = false;
        if($this->isCronJob || $this->isCliMode) return;

        try {
            $this->isAppUpdate = $request->getPathInfo() === '/settings/ajax/updateapp.php';
        } catch(\Exception $e) {
            $this->logger->logException($e, ['app' => Application::APP_NAME]);
        }
    }
}