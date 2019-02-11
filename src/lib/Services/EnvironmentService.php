<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OC\Authentication\Token\IProvider;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserManager;

/**
 * Class EnvironmentService
 *
 * @package OCA\Passwords\Services
 */
class EnvironmentService {

    const MODE_PUBLIC = 'public';
    const MODE_USER   = 'user';
    const MODE_GLOBAL = 'global';

    const TYPE_REQUEST     = 'request';
    const TYPE_CRON        = 'cron';
    const TYPE_CLI         = 'cli';
    const TYPE_MAINTENANCE = 'maintenance';

    /**
     * @var IConfig
     */
    protected $config;

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var ISession
     */
    protected $session;

    /**
     * @var IUserManager
     */
    protected $userManager;

    /**
     * @var IProvider
     */
    protected $tokenProvider;

    /**
     * @var IUser
     */
    protected $user;

    /**
     * @var null|string
     */
    protected $userLogin;

    /**
     * @var string
     */
    protected $appMode = self::MODE_PUBLIC;

    /**
     * @var string
     */
    protected $runType = self::TYPE_REQUEST;

    /**
     * EnvironmentService constructor.
     *
     * @param null|string    $userId
     * @param IConfig        $config
     * @param IRequest       $request
     * @param ISession       $session
     * @param LoggingService $logger
     * @param IProvider      $tokenProvider
     * @param IUserManager   $userManager
     *
     * @throws \Exception
     */
    public function __construct(
        ?string $userId,
        IConfig $config,
        IRequest $request,
        ISession $session,
        LoggingService $logger,
        IProvider $tokenProvider,
        IUserManager $userManager
    ) {
        $this->config        = $config;
        $this->logger        = $logger;
        $this->session       = $session;
        $this->userManager   = $userManager;
        $this->tokenProvider = $tokenProvider;

        $this->determineRunType($request);
        $this->determineAppMode($userId, $request);
    }

    /**
     * @return string
     */
    public function getAppMode(): string {
        return $this->appMode;
    }

    /**
     * @return string
     */
    public function getRunType(): string {
        return $this->runType;
    }

    /**
     * @return null|IUser
     */
    public function getUser(): ?IUser {
        return $this->user;
    }

    /**
     * @return null|string
     */
    public function getUserId(): ?string {
        return $this->user !== null ? $this->user->getUID():null;
    }

    /**
     * @return null|string
     */
    public function getUserLogin(): ?string {
        return $this->userLogin;
    }

    /**
     * @param IRequest $request
     */
    protected function determineRunType(IRequest $request): void {
        $this->runType = self::TYPE_REQUEST;

        if($this->isCronJob($request)) {
            $this->runType = self::TYPE_CRON;
        } else if($this->isAppUpgrade($request) || $this->config->getSystemValue('maintenance', false)) {
            $this->runType = self::TYPE_MAINTENANCE;
        } else if(PHP_SAPI === 'cli') {
            $this->runType = self::TYPE_CLI;
        }
    }

    /**
     * @param IRequest $request
     *
     * @return bool
     */
    protected function isCronJob(IRequest $request): bool {
        $requestUri = $request->getRequestUri();
        $cronMode   = $this->config->getAppValue('core', 'backgroundjobs_mode', 'ajax');

        return ($requestUri === '/index.php/apps/passwords/cron/sharing') ||
               ($requestUri === '/cron.php' && in_array($cronMode, ['ajax', 'webcron'])) ||
               (PHP_SAPI === 'cli' && $cronMode === 'cron' && strpos($request->getScriptName(), 'cron.php') !== false);
    }

    /**
     * @param IRequest $request
     *
     * @return bool
     */
    protected function isAppUpgrade(IRequest $request): bool {
        try {
            return $request->getPathInfo() === '/settings/ajax/updateapp.php';
        } catch(\Exception $e) {
            $this->logger->logException($e);
        }

        return false;
    }

    /**
     * @param null|string $userId
     * @param IRequest    $request
     *
     * @throws \Exception
     */
    protected function determineAppMode(?string $userId, IRequest $request): void {
        $this->appMode = self::MODE_PUBLIC;
        if($this->runType !== self::TYPE_REQUEST) {
            $this->appMode = self::MODE_GLOBAL;
            $this->logger->info('Passwords runs '.($request->getRequestUri() ? $request->getRequestUri():$request->getScriptName()).' in global mode');
        } else if($this->loadUserInformation($userId)) {
            $this->appMode = self::MODE_USER;
        }
    }

    /**
     * @param null|string $userId
     *
     * @return bool
     * @throws \Exception
     */
    protected function loadUserInformation(?string $userId): bool {
        if(isset($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_USER'])) {
            $loginName = $_SERVER['PHP_AUTH_USER'];
            $loginUser = $this->userManager->get($loginName);

            if($loginUser !== null && ($userId === null || $loginUser->getUID() === $userId)) {
                $this->user      = $loginUser;
                $this->userLogin = $loginName;

                return true;
            }
        } else if($userId === null) {
            return false;
        } else {
            try {
                $sessionToken = $this->tokenProvider->getToken($this->session->getId());

                $uid  = $sessionToken->getUID();
                $user = $this->userManager->get($uid);
                if($user !== null) {
                    if($uid === $userId) {
                        $this->user      = $user;
                        $this->userLogin = $sessionToken->getLoginName();

                        return true;
                    } else if($this->session->get('oldUserId') === $uid && \OC_User::isAdminUser($uid)) {
                        $this->user      = $user;
                        $this->userLogin = $userId;
                        $this->logger->warning(['Detected %s impersonating %s', $uid, $userId]);

                        return true;
                    }
                }
            } catch(\Throwable $e) {
                $this->logger->logException($e);
            }
        }

        throw new \Exception("Login {$loginUser} does not belong to {$userId}");
    }

}