<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Token;

use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OCA\Passwords\Encryption\SimpleEncryption;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\SessionService;
use OCP\IL10N;
use OCP\Security\ISecureRandom;

/**
 * Class ApiTokenHelper
 *
 * @package OCA\Passwords\Helper\Token
 */
class ApiTokenHelper {

    const WEBUI_TOKEN    = 'webui/token';
    const WEBUI_TOKEN_ID = 'webui/token/id';

    /**
     * @var null|string
     */
    protected $userId;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var ISecureRandom
     */
    protected $random;

    /**
     * @var SessionService
     */
    protected $session;

    /**
     * @var SimpleEncryption
     */
    protected $encryption;

    /**
     * @var IL10N
     */
    protected $localisation;

    /**
     * @var IProvider
     */
    protected $tokenProvider;

    /**
     * @var EnvironmentService
     */
    protected $environment;

    /**
     * ApiTokenHelper constructor.
     *
     * @param IL10N                $localisation
     * @param ISecureRandom        $random
     * @param LoggingService       $logger
     * @param SessionService       $session
     * @param IProvider            $tokenProvider
     * @param SimpleEncryption     $encryption
     * @param ConfigurationService $config
     * @param EnvironmentService   $environment
     */
    public function __construct(
        IL10N $localisation,
        ISecureRandom $random,
        LoggingService $logger,
        SessionService $session,
        IProvider $tokenProvider,
        SimpleEncryption $encryption,
        ConfigurationService $config,
        EnvironmentService $environment
    ) {
        $this->userId        = $environment->getUserId();
        $this->random        = $random;
        $this->logger        = $logger;
        $this->config        = $config;
        $this->session       = $session;
        $this->encryption    = $encryption;
        $this->localisation  = $localisation;
        $this->tokenProvider = $tokenProvider;
        $this->environment   = $environment;
    }

    /**
     * @return array
     */
    public function getWebUiToken(): array {
        try {
            $token = $this->loadWebUiToken();
            if($token !== false) return $token;

            return $this->createWebUiToken();
        } catch(\Throwable $e) {
            $this->logger->logException($e);

            return ['', ''];
        }
    }

    /**
     * @param string $name
     * @param bool   $permanent
     *
     * @return array
     * @throws \Exception
     */
    public function createToken(string $name, bool $permanent = false): array {
        $userLogin = $this->environment->getUserLogin();
        $token     = $this->generateRandomDeviceToken();
        $password  = $this->getUserPassword();
        $type      = $permanent ? IToken::PERMANENT_TOKEN:IToken::TEMPORARY_TOKEN;

        $deviceToken = $this->tokenProvider->generateToken($token, $this->userId, $userLogin, $password, $name, $type);
        $deviceToken->setScope(['filesystem' => $this->config->isAppEnabled('encryption')]);
        $deviceToken->setExpires(time() + 7200);
        $this->tokenProvider->updateToken($deviceToken);

        return [$token, $deviceToken];
    }

    /**
     * @param string $tokenId
     */
    public function destroyToken(string $tokenId): void {
        $this->tokenProvider->invalidateTokenById(
            $this->userId,
            $tokenId
        );
    }

    /**
     * @throws \Exception
     */
    protected function destroyLegacyToken(): void {
        $tokenId = $this->config->getUserValue(self::WEBUI_TOKEN_ID, false);
        if($tokenId !== false) {
            $this->config->deleteUserValue(self::WEBUI_TOKEN);
            $this->config->deleteUserValue(self::WEBUI_TOKEN_ID);
            $this->destroyToken($tokenId);
        }
    }

    /**
     *
     */
    public function destroyWebUiToken(): void {
        $tokenId = $this->session->get(self::WEBUI_TOKEN_ID);
        if(!empty($tokenId)) {
            $this->destroyToken($tokenId);
            $this->session->unset(self::WEBUI_TOKEN);
            $this->session->unset(self::WEBUI_TOKEN_ID);
        }
    }

    /**
     * @return bool|array
     * @throws \Exception
     */
    protected function loadWebUiToken() {
        if($this->config->getUserValue('webui_token_id', false) !== false) {
            $this->destroyLegacyToken();
        }

        $token   = $this->session->get(self::WEBUI_TOKEN);
        $tokenId = $this->session->get(self::WEBUI_TOKEN_ID);
        if($token !== null && $tokenId !== null) {
            try {
                $webToken = $this->tokenProvider->getTokenById($tokenId);

                if($webToken->getLastCheck() > time() - 7200 && $webToken->getId() == $tokenId && $webToken->getUID() === $this->userId) {
                    return [$token, $webToken->getLoginName()];
                } else {
                    $this->destroyToken($tokenId);
                }
            } catch(\Throwable $e) {
                $this->logger
                    ->logException($e)
                    ->error('Failed to load api token for '.$this->userId);
            }
        }

        return false;
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function createWebUiToken(): array {
        $name = $this->localisation->t('Passwords Session %s - %s@%s', [date('d.m.y H:i'), $this->environment->getUserLogin(), \OC::$server->getRequest()->getRemoteAddress()]);
        list($token, $deviceToken) = $this->createToken($name);
        $this->session->set(self::WEBUI_TOKEN, $token);
        $this->session->set(self::WEBUI_TOKEN_ID, $deviceToken->getId());

        return [$token, $deviceToken->getLoginName()];
    }

    /**
     * @return null|string
     */
    protected function getUserPassword(): ?string {
        try {
            $sessionId    = \OC::$server->getSession()->getId();
            $sessionToken = $this->tokenProvider->getToken($sessionId);

            return $this->tokenProvider->getPassword($sessionToken, $sessionId);
        } catch(\Throwable $e) {
            $this->logger->logException($e);
        }

        return null;
    }

    /**
     * @return string
     */
    protected function generateRandomDeviceToken(): string {
        $groups = [];
        for($i = 0; $i < 5; $i++) {
            $groups[] = $this->random->generate(5, ISecureRandom::CHAR_HUMAN_READABLE);
        }

        return implode('-', $groups);
    }
}