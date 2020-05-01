<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use Exception;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
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

    const LOGIN_NONE     = 'none';
    const LOGIN_TOKEN    = 'token';
    const LOGIN_SESSION  = 'session';
    const LOGIN_PASSWORD = 'password';
    const LOGIN_EXTERNAL = 'external';

    const CLIENT_MAINTENANCE = 'CLIENT::MAINTENANCE';
    const CLIENT_UNKNOWN     = 'CLIENT::UNKNOWN';
    const CLIENT_SYSTEM      = 'CLIENT::SYSTEM';
    const CLIENT_PUBLIC      = 'CLIENT::PUBLIC';
    const CLIENT_CRON        = 'CLIENT::CRON';
    const CLIENT_CLI         = 'CLIENT::CLI';

    /**
     * @var array
     */
    protected static $protectedClients
        = [
            self::CLIENT_MAINTENANCE,
            self::CLIENT_CLI,
            self::CLIENT_CRON,
            self::CLIENT_PUBLIC,
            self::CLIENT_SYSTEM,
            self::CLIENT_UNKNOWN,
            self::CLIENT_CRON,
        ];

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
     * @var IRequest
     */
    protected $request;

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
     * @var string
     */
    protected $password;

    /**
     * @var null|string
     */
    protected $userLogin;

    /**
     * @var null|IToken
     */
    protected $loginToken;

    /**
     * @var null|string
     */
    protected $client = self::CLIENT_UNKNOWN;

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
        $this->request       = $request;
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
     * @return null|string
     */
    public function getUserPassword(): ?string {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getLoginType(): string {
        return $this->loginType;
    }

    /*
     * @return IToken|null
     */
    public function getLoginToken(): ?IToken {
        return $this->loginToken;
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
     * @return string
     */
    public function getUserAgent(): string {
        $login = $this->getAppMode() === self::MODE_PUBLIC ? 'public access':$this->getUserLogin();

        return $this->getClientFromRequest($this->request, $login);
    }

    /*
     * @return string[]
     */
    public function getProtectedClients(): array {
        return self::$protectedClients;
    }

    /**
     * @param IRequest $request
     */
    protected function determineRunType(IRequest $request): void {
        $this->runType = self::TYPE_REQUEST;

        if($this->isCronJob($request)) {
            $this->runType = self::TYPE_CRON;
            $this->client  = self::CLIENT_CRON;
        } else if($this->config->getSystemValue('maintenance', false)) {
            $this->runType = self::TYPE_MAINTENANCE;
            $this->client  = self::CLIENT_MAINTENANCE;
        } else if($this->isCliMode($request)) {
            $this->runType = self::TYPE_CLI;
            $this->client  = self::CLIENT_CLI;
        }
    }

    /**
     * @param IRequest $request
     *
     * @return bool
     */
    protected function isCronJob(IRequest $request): bool {
        $cronMode   = $this->config->getAppValue('core', 'backgroundjobs_mode', 'ajax');
        $cronScript = substr($request->getScriptName(), -8) === 'cron.php';

        if($cronScript && (in_array($cronMode, ['ajax', 'webcron']) || (PHP_SAPI === 'cli' && $cronMode === 'cron'))) {
            return true;
        }

        try {
            $requestUri    = $request->getPathInfo();
            $webroot       = $this->config->getSystemValue('overwritewebroot', '');
            $webrootLength = strlen($webroot);

            if($webrootLength !== 0 && substr($requestUri, 0, $webrootLength) === $webroot) {
                $requestUri = substr($requestUri, $webrootLength);
                if($requestUri[0] !== '/') $requestUri = '/'.$requestUri;
            }

            return $requestUri === '/apps/passwords/cron/sharing';
        } catch(Exception $e) {
            return false;
        }
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
        $authHeader   = $request->getHeader('Authorization');
        $userIdString = $userId ? $userId:'invalid user id';
        if($this->session->exists('login_credentials')) {
            if($this->loadUserFromSession($userId, $request)) return true;
            $this->logger->warning('Login attempt with invalid session for '.$userIdString);
        } else if($authHeader !== '') {
            [$type, $value] = explode(' ', $authHeader, 2);

            if($type === 'Basic' && $this->loadUserFromBasicAuth($userId, $request)) return true;
            if($type === 'Bearer' && $this->loadUserFromBearerAuth($userId, $value)) return true;
            $this->logger->warning('Login attempt with invalid authorization header for '.$userIdString);
        } else if(isset($_SERVER['PHP_AUTH_USER']) || isset($_SERVER['PHP_AUTH_PW'])) {
            if($this->loadUserFromBasicAuth($userId, $request)) return true;
            $this->logger->warning('Login attempt with invalid basic auth for '.$userIdString);
        } else if($userId !== null) {
            if($this->loadUserFromSessionToken($userId)) return true;
            $this->logger->warning('Login attempt with invalid session token for '.$userIdString);
        } else {
            $this->client = self::CLIENT_PUBLIC;

            return false;
        }

        $this->client = self::CLIENT_PUBLIC;
        if($userId !== null) throw new \Exception('Unable to verify user '.$userIdString);

        return false;
    }

    /**
     * @param string   $userId
     * @param IRequest $request
     *
     * @return bool
     */
    protected function loadUserFromBasicAuth(?string $userId, IRequest $request): bool {
        if(!isset($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_USER'])) return false;
        if(!isset($_SERVER['PHP_AUTH_PW']) || empty($_SERVER['PHP_AUTH_PW'])) return false;

        $loginName = $_SERVER['PHP_AUTH_USER'];
        try {
            if(\OC::$server->getUserSession()->isTokenPassword($_SERVER['PHP_AUTH_PW'])) {
                return $this->getUserInfoFromToken($_SERVER['PHP_AUTH_PW'], $loginName, $userId);
            } else {
                return $this->getUserInfoFromPassword($userId, $request, $loginName, $_SERVER['PHP_AUTH_PW']);
            }
        } catch(\Exception $e) {
            $this->logger->logException($e);
        }

        return false;
    }

    /**
     * @param string $userId
     * @param string $value
     *
     * @return bool
     */
    protected function loadUserFromBearerAuth(string $userId, string $value): bool {
        if(empty($value)) return false;

        try {
            $token     = $this->tokenProvider->getToken($value);
            $loginUser = $this->userManager->get($token->getUID());

            if($loginUser !== null && $loginUser->getUID() === $userId) {
                $this->user       = $loginUser;
                $this->userLogin  = $token->getLoginName();
                $this->client     = $this->getClientFromToken($token);
                $this->loginToken = $token;
                $this->loginType  = self::LOGIN_TOKEN;
                $this->password   = $this->tokenProvider->getPassword($token, $token->getId());

                return true;
            }
        } catch(\Exception $e) {
            $this->logger->logException($e);
        }

        return false;
    }

    /**
     * @param null|string $userId
     *
     * @return bool
     */
    protected function loadUserFromSessionToken(?string $userId): bool {
        try {
            $sessionToken = $this->tokenProvider->getToken($this->session->getId());

            $uid  = $sessionToken->getUID();
            $user = $this->userManager->get($uid);
            if($user !== null) {
                if($uid === $userId) {
                    $this->user       = $user;
                    $this->userLogin  = $sessionToken->getLoginName();
                    $this->client     = $this->getClientFromToken($sessionToken);
                    $this->loginToken = $sessionToken;
                    $this->loginType  = self::LOGIN_SESSION;
                    $this->password   = $this->tokenProvider->getPassword($sessionToken, $sessionToken->getId());

                    return true;
                } else if($this->session->get('oldUserId') === $uid && \OC_User::isAdminUser($uid)) {
                    return $this->impersonateByUid($userId, $uid, self::LOGIN_SESSION);
                }
            }
        } catch(\Throwable $e) {
            $this->logger->logException($e);
        }

        return false;
    }

    /**
     * @param string|null $userId
     * @param IRequest    $request
     *
     * @return bool
     */
    protected function loadUserFromSession(?string $userId, IRequest $request): bool {
        $loginCredentials = json_decode($this->session->get('login_credentials'));
        $loginName        = $this->session->get('loginname');
        $uid              = $loginCredentials->uid;

        if($uid === $userId) {
            if(isset($loginCredentials->isTokenLogin) && $loginCredentials->isTokenLogin) {
                $tokenId = $this->session->get('app_password');

                return $this->getUserInfoFromToken($tokenId, $loginName, $userId);
            } else if(isset($loginCredentials->password) && !empty($loginCredentials->password)) {
                return $this->getUserInfoFromPassword($userId, $request, $loginName, $loginCredentials->password);
            } else if(isset($loginCredentials->password) && empty($loginCredentials->password)) {
                return $this->getUserInfoFromUserId($userId, $request, $loginName);
            }
        } else if($this->session->get('oldUserId') === $uid && \OC_User::isAdminUser($uid)) {
            return $this->impersonateByUid($userId, $loginName, self::LOGIN_PASSWORD);
        }

        return false;
    }

    /**
     * @param string      $tokenId
     * @param string      $loginName
     * @param string|null $userId
     *
     * @return bool
     */
    protected function getUserInfoFromToken(string $tokenId, string $loginName, ?string $userId): bool {
        try {
            $token     = $this->tokenProvider->getToken($tokenId);
            $loginUser = $this->userManager->get($token->getUID());

            if($loginUser !== null && $token->getLoginName() === $loginName && ($userId === null || $loginUser->getUID() === $userId)) {
                $this->user       = $loginUser;
                $this->userLogin  = $loginName;
                $this->client     = $this->getClientFromToken($token);
                $this->loginToken = $token;
                $this->loginType  = self::LOGIN_TOKEN;

                return true;
            }
        } catch(\Exception $e) {
            $this->logger->logException($e);
        }

        return false;
    }

    /**
     * @param string|null $userId
     * @param IRequest    $request
     * @param string      $loginName
     * @param string      $password
     *
     * @return bool
     */
    protected function getUserInfoFromPassword(?string $userId, IRequest $request, string $loginName, string $password): bool {
        /** @var false|\OCP\IUser $loginUser */
        $loginUser = $this->userManager->checkPasswordNoLogging($loginName, $password);
        if($loginUser !== false && ($userId === null || $loginUser->getUID() === $userId)) {
            $this->user      = $loginUser;
            $this->userLogin = $loginName;
            $this->client    = $this->getClientFromRequest($request, $loginName);
            $this->loginType = self::LOGIN_PASSWORD;
            $this->password   = $password;

            return true;
        }

        return false;
    }

    /**
     * @param string|null $userId
     * @param IRequest    $request
     * @param string      $loginName
     *
     * @return bool
     */
    protected function getUserInfoFromUserId(?string $userId, IRequest $request, string $loginName): bool {
        /** @var false|\OCP\IUser $loginUser */
        $loginUser = $this->userManager->get($loginName);
        if($loginUser !== false && ($userId === null || $loginUser->getUID() === $userId)) {
            $this->user      = $loginUser;
            $this->userLogin = $loginName;
            $this->client    = $this->getClientFromRequest($request, $loginName);
            $this->loginType = self::LOGIN_EXTERNAL;

            return true;
        }

        return false;
    }

    /**
     * @param string $uid
     * @param string $realUid
     * @param string $loginType
     *
     * @return bool
     */
    protected function impersonateByUid(string $uid, string $realUid, string $loginType): bool {
        $user = $this->userManager->get($uid);
        if($user !== null) {
            $realUser            = $this->userManager->get($realUid);
            $this->user          = $user;
            $this->userLogin     = $uid;
            $this->client        = $realUser->getDisplayName().' via Impersonate';
            $this->loginType     = $loginType;
            $this->impersonating = true;
            $this->realUser      = $realUser;
            $this->logger->warning(['Detected "%s" impersonating "%s"', $realUser->getDisplayName(), $user->getDisplayName()]);

            return true;
        }

        return false;
    }

    /**
     * @param IRequest $request
     * @param string   $loginName
     *
     * @return string
     */
    protected function getClientFromRequest(IRequest $request, string $loginName): string {
        $client = trim(filter_var($request->getHeader('USER_AGENT'), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH));

        if(empty($client) ||
           in_array($client, self::$protectedClients) ||
           strpos($client, 'Passwords Session') !== false) {
            return $loginName.' via '.$request->getRemoteAddress();
        }

        if(strlen($client) > 256) return substr($client, 0, 256);

        return $client;
    }

    /**
     * @param $token
     *
     * @return mixed
     */
    protected function getClientFromToken(IToken $token): string {
        $client = trim($token->getName());

        if(empty($client) || in_array($client, self::$protectedClients)) return $token->getUID().' via Token';
        if(strlen($client) > 256) return substr($client, 0, 256);

        return $client;
    }

}