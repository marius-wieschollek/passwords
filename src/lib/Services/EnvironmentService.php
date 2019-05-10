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

    const LOGIN_NONE    = 'none';
    const LOGIN_BASIC   = 'basic';
    const LOGIN_BEARER  = 'bearer';
    const LOGIN_SESSION = 'session';

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
     * @var IUser
     */
    protected $realUser;

    /**
     * @var null|string
     */
    protected $userLogin;

    /**
     * @var null|string
     */
    protected $client = 'Unknown Client';

    /**
     * @var bool
     */
    protected $impersonating = false;

    /**
     * @var string
     */
    protected $appMode = self::MODE_PUBLIC;

    /**
     * @var string
     */
    protected $runType = self::TYPE_REQUEST;

    /**
     * @var string
     */
    protected $loginType = self::LOGIN_NONE;

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
     * @return string
     */
    public function getLoginType(): string {
        return $this->loginType;
    }

    /**
     * @return bool
     */
    public function isImpersonating(): bool {
        return $this->impersonating;
    }

    /*
     * @return null|IUser
     */
    public function getRealUser(): ?IUser {
        return $this->impersonating ? $this->realUser:$this->user;
    }

    /*
     * @return string
     */
    public function getClient(): string {
        return $this->client;
    }

    /**
     * @param IRequest $request
     */
    protected function determineRunType(IRequest $request): void {
        $this->runType = self::TYPE_REQUEST;

        if($this->isCronJob($request)) {
            $this->runType = self::TYPE_CRON;
            $this->client  = 'System Background Job';
        } else if($this->config->getSystemValue('maintenance', false)) {
            $this->runType = self::TYPE_MAINTENANCE;
            $this->client  = 'System Upgrade';
        } else if($this->isCliMode($request)) {
            $this->runType = self::TYPE_CLI;
            $this->client  = 'Server CLI';
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
    protected function isCliMode(IRequest $request): bool {
        try {
            return PHP_SAPI === 'cli' ||
                   (
                       $request->getMethod() === 'POST' &&
                       $request->getPathInfo() === '/apps/occweb/cmd' &&
                       $this->config->getAppValue('occweb', 'enabled', 'no') === 'yes'
                   );
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
        } else if($this->loadUserInformation($userId, $request)) {
            $this->appMode = self::MODE_USER;
        }
    }

    /**
     * @param null|string $userId
     * @param IRequest    $request
     *
     * @return bool
     * @throws \OC\Authentication\Exceptions\ExpiredTokenException
     * @throws \OC\Authentication\Exceptions\InvalidTokenException
     * @throws \Exception
     */
    protected function loadUserInformation(?string $userId, IRequest $request): bool {
        $authHeader = $request->getHeader('Authorization');
        if($authHeader !== '') {
            list($type, $value) = explode(' ', $authHeader, 2);

            if($type === 'Basic' && $this->loadUserFromBasicAuth($userId, $request)) return true;
            if($type === 'Bearer' && $this->loadUserFromBearerAuth($userId, $value)) return true;
        } else if($userId === null) {
            $this->client = 'Public Access';

            return false;
        } else {
            if($this->loadUserFromSession($userId)) return true;
        }

        throw new \Exception("Unable to verify user login for {$userId}");
    }

    /**
     * @param string   $userId
     * @param IRequest $request
     *
     * @return bool
     * @throws \OC\Authentication\Exceptions\ExpiredTokenException
     * @throws \OC\Authentication\Exceptions\InvalidTokenException
     */
    protected function loadUserFromBasicAuth(?string $userId, IRequest $request): bool {
        if(!isset($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_USER'])) return false;
        if(!isset($_SERVER['PHP_AUTH_PW']) || empty($_SERVER['PHP_AUTH_PW'])) return false;

        $loginName = $_SERVER['PHP_AUTH_USER'];
        if(\OC::$server->getUserSession()->isTokenPassword($_SERVER['PHP_AUTH_PW'])) {
            $token     = $this->tokenProvider->getToken($_SERVER['PHP_AUTH_PW']);
            $loginUser = $this->userManager->get($token->getUID());

            if($loginUser !== null && $token->getLoginName() === $loginName && ($userId === null || $loginUser->getUID() === $userId)) {
                $this->user      = $loginUser;
                $this->userLogin = $loginName;
                $this->client    = $token->getName();
                $this->loginType = self::LOGIN_BASIC;

                return true;
            }
        } else {
            /** @var false|\OCP\IUser $loginUser */
            $loginUser = $this->userManager->checkPasswordNoLogging($loginName, $_SERVER['PHP_AUTH_PW']);
            if($loginUser !== false && ($userId === null || $loginUser->getUID() === $userId)) {
                $client = trim($request->getHeader('USER_AGENT'));

                $this->user      = $loginUser;
                $this->userLogin = $loginName;
                $this->client    = empty($client) ? 'Unknown Client':$client;
                $this->loginType = self::LOGIN_BASIC;

                return true;
            }
        }

        return false;
    }

    /**
     * @param string $userId
     * @param string $value
     *
     * @return bool
     * @throws \OC\Authentication\Exceptions\ExpiredTokenException
     * @throws \OC\Authentication\Exceptions\InvalidTokenException
     */
    protected function loadUserFromBearerAuth(string $userId, string $value): bool {
        if(empty($value)) return false;

        $token     = $this->tokenProvider->getToken($value);
        $loginUser = $this->userManager->get($token->getUID());

        if($loginUser !== null && $loginUser->getUID() === $userId) {
            $this->user      = $loginUser;
            $this->userLogin = $token->getLoginName();
            $this->client    = $token->getName();
            $this->loginType = self::LOGIN_BEARER;

            return true;
        }

        return false;
    }

    /**
     * @param null|string $userId
     *
     * @return bool
     */
    protected function loadUserFromSession(?string $userId): bool {
        try {
            $sessionToken = $this->tokenProvider->getToken($this->session->getId());

            $uid  = $sessionToken->getUID();
            $user = $this->userManager->get($uid);
            if($user !== null) {
                if($uid === $userId) {
                    $this->user      = $user;
                    $this->userLogin = $sessionToken->getLoginName();
                    $this->client    = $sessionToken->getName();
                    $this->loginType = self::LOGIN_SESSION;

                    return true;
                } else if($this->session->get('oldUserId') === $uid && \OC_User::isAdminUser($uid)) {
                    $user = $this->userManager->get($userId);
                    if($user !== null) {
                        $this->user          = $user;
                        $this->userLogin     = $userId;
                        $this->client        = ucfirst($uid).' via Impersonate';
                        $this->loginType     = self::LOGIN_SESSION;
                        $this->impersonating = true;
                        $this->realUser      = $this->userManager->get($uid);
                        $this->logger->warning(['Detected %s impersonating %s', $uid, $userId]);

                        return true;
                    }
                }
            }
        } catch(\Throwable $e) {
            $this->logger->logException($e);
        }

        return false;
    }
}