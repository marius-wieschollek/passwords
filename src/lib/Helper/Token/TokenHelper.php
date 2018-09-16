<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Token;

use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OCA\Passwords\Encryption\SimpleEncryption;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\NotificationService;
use OCP\IL10N;
use OCP\ISession;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;

/**
 * Class TokenHelper
 *
 * @package OCA\Passwords\Helper\Token
 */
class TokenHelper {

    const WEBUI_TOKEN    = 'webui_token';
    const WEBUI_TOKEN_ID = 'webui_token_id';

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
     * @var ISession
     */
    protected $session;

    /**
     * @var IUserManager
     */
    protected $userManager;

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
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * TokenHelper constructor.
     *
     * @param null|string          $userId
     * @param IL10N                $localisation
     * @param ISession             $session
     * @param ISecureRandom        $random
     * @param LoggingService       $logger
     * @param IProvider            $tokenProvider
     * @param IUserManager         $userManager
     * @param SimpleEncryption     $encryption
     * @param ConfigurationService $config
     * @param NotificationService  $notificationService
     */
    public function __construct(
        ?string $userId,
        IL10N $localisation,
        ISession $session,
        ISecureRandom $random,
        LoggingService $logger,
        IProvider $tokenProvider,
        IUserManager $userManager,
        SimpleEncryption $encryption,
        ConfigurationService $config,
        NotificationService $notificationService
    ) {
        $this->userId              = $userId;
        $this->random              = $random;
        $this->logger              = $logger;
        $this->config              = $config;
        $this->session             = $session;
        $this->encryption          = $encryption;
        $this->userManager         = $userManager;
        $this->localisation        = $localisation;
        $this->tokenProvider       = $tokenProvider;
        $this->notificationService = $notificationService;
    }

    /**
     * @return null|string
     */
    public function getWebUiToken(): string {
        try {
            $token = $this->loadWebUiToken();
            if($token !== false) return $token;

            return $this->createWebUiToken();
        } catch(\Throwable $e) {
            $this->logger->logException($e);

            return '';
        }
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function createToken(string $name): array {
        $token       = $this->generateRandomDeviceToken();
        $password    = $this->getUserPassword();
        $deviceToken = $this->tokenProvider->generateToken($token, $this->userId, $this->userId, $password, $name, IToken::PERMANENT_TOKEN);
        $deviceToken->setScope(['filesystem' => false]);
        $this->tokenProvider->updateToken($deviceToken);

        return [$token, $deviceToken];
    }

    /**
     * @param string $tokenId
     */
    public function destroyToken(string $tokenId): void {
        // @TODO remove this for 2019.1.0
        if($this->getServerVersion() === '14') {
            $this->tokenProvider->invalidateTokenById(
                $this->userId,
                $tokenId
            );
        } else {
            $this->tokenProvider->invalidateTokenById(
                $this->userManager->get($this->userId),
                $tokenId
            );
        }
    }

    /**
     * @throws \Exception
     */
    public function destroyWebUiToken(): void {
        $tokenId = $this->config->getUserValue(self::WEBUI_TOKEN_ID, false);
        if($tokenId !== false) {
            $this->destroyToken($tokenId);
            $this->config->deleteUserValue(self::WEBUI_TOKEN);
            $this->config->deleteUserValue(self::WEBUI_TOKEN_ID);
        }
    }

    /**
     * @return bool|string
     * @throws \Exception
     */
    protected function loadWebUiToken() {
        $token   = $this->config->getUserValue(self::WEBUI_TOKEN, false);
        $tokenId = $this->config->getUserValue(self::WEBUI_TOKEN_ID, false);
        if($token !== false && $tokenId !== false) {
            try {
                $iToken = $this->tokenProvider->getTokenById($tokenId);

                if($iToken->getId() == $tokenId) return $this->encryption->decrypt($token);
            } catch(InvalidTokenException $e) {
            } catch(\Throwable $e) {
                $this->notificationService->sendTokenErrorNotification($this->userId);
                $this->logger
                    ->logException($e)
                    ->error('Failed to decrypt api token for '.$this->userId);
            }
        }

        return false;
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function createWebUiToken(): string {
        $name = $this->localisation->t('Passwords WebUI Access (see Passwords F.A.Q)');
        list($token, $deviceToken) = $this->createToken($name);
        $this->config->setUserValue(self::WEBUI_TOKEN, $this->encryption->encrypt($token));
        $this->config->setUserValue(self::WEBUI_TOKEN_ID, $deviceToken->getId());

        return $token;
    }

    /**
     * @return null|string
     */
    protected function getUserPassword(): ?string {
        try {
            $sessionId    = $this->session->getId();
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

    /**
     * @return string
     * @deprecated
     * @TODO remove this for 2019.1.0
     */
    protected function getServerVersion(): string {
        $version = $this->config->getSystemValue('version');

        return explode('.', $version, 2)[0];
    }
}