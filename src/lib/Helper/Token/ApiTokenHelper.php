<?php
/*
 * @copyright 2024 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Helper\Token;

use Exception;
use OCP\Authentication\Token\IProvider;
use OCP\Authentication\Token\IToken;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\SessionService;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Security\ISecureRandom;
use Throwable;

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
    protected ?string $userId;

    /**
     * ApiTokenHelper constructor.
     *
     * @param IRequest             $request
     * @param IL10N                $localisation
     * @param ISecureRandom        $random
     * @param LoggingService       $logger
     * @param SessionService       $session
     * @param IProvider            $tokenProvider
     * @param ConfigurationService $config
     * @param EnvironmentService   $environment
     */
    public function __construct(
        protected IRequest $request,
        protected IL10N $localisation,
        protected ISecureRandom $random,
        protected LoggingService $logger,
        protected SessionService $session,
        protected IProvider $tokenProvider,
        protected ConfigurationService $config,
        protected EnvironmentService $environment
    ) {
        $this->userId        = $environment->getUserId();
    }

    /**
     * @return array
     */
    public function getWebUiToken(): array {
        try {
            $token = $this->loadWebUiToken();
            if($token !== false) return $token;

            return $this->createWebUiToken();
        } catch(Throwable $e) {
            $this->logger->logException($e);

            return ['', ''];
        }
    }

    /**
     * @param string $name
     * @param bool   $permanent
     *
     * @return array
     * @throws Exception
     */
    public function createToken(string $name, bool $permanent = false): array {
        $userLogin = $this->environment->getUserLogin();
        $token     = $this->generateRandomDeviceToken();
        $password  = $this->getUserPassword();
        $type      = $permanent ? IToken::PERMANENT_TOKEN:IToken::TEMPORARY_TOKEN;

        $deviceToken = $this->tokenProvider->generateToken($token, $this->userId, $userLogin, $password, $name, $type);
        $deviceToken->setScope(['filesystem' => $this->mustHaveFileSystemPermission()]);
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
     *
     */
    public function destroyWebUiToken(): void {
        $tokenId = $this->session->get(self::WEBUI_TOKEN_ID);
        if(!empty($tokenId)) {
            $this->destroyToken($tokenId);
            $this->session->delete();
        }
    }

    /**
     * @return bool|array
     * @throws Exception
     */
    protected function loadWebUiToken() {
        if($this->environment->isImpersonating()) return false;

        $token   = $this->session->get(self::WEBUI_TOKEN);
        $tokenId = $this->session->get(self::WEBUI_TOKEN_ID);
        if($token !== null && $tokenId !== null) {
            try {
                $webToken = $this->tokenProvider->getTokenById($tokenId);

                if($webToken->getId() === $tokenId && $webToken->getUID() === $this->userId) {
                    return [$token, $webToken->getLoginName()];
                } else {
                    $this->destroyToken($tokenId);
                }
            } catch(Throwable $e) {
                $this->logger
                    ->logException($e)
                    ->error('Failed to load api token for '.$this->userId);
            }
        }

        return false;
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function createWebUiToken(): array {
        $name = $this->getTokenName();
        [$token, $deviceToken] = $this->createToken($name);
        $this->session->set(self::WEBUI_TOKEN, $token);
        $this->session->set(self::WEBUI_TOKEN_ID, $deviceToken->getId());
        $this->session->save();

        return [$token, $deviceToken->getLoginName()];
    }

    /**
     * @return null|string
     */
    protected function getUserPassword(): ?string {
        try {
            $password = $this->environment->getUserPassword();

            return empty($password) ? null:$password;
        } catch(Throwable $e) {
            $this->logger->logException($e);
        }

        return null;
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function generateRandomDeviceToken(): string {
        $groups = [];
        for($i = 0; $i < 5; $i++) {
            $groups[] = $this->random->generate(5, ISecureRandom::CHAR_HUMAN_READABLE);
        }

        $token = implode('-', $groups);
        if(strlen($token) < 29) {
            throw new Exception('Token generation failed. Did not generate enough random numbers');
        }

        return $token;
    }

    /**
     * @return string
     */
    protected function getTokenName(): string {
        if($this->environment->isImpersonating()) {
            return
                $this->localisation->t(
                    '%2$s via Impersonate %1$s - %3$s@%4$s',
                    [
                        date('d.m.y H:i'),
                        $this->environment->getRealUser()->getDisplayName(),
                        $this->environment->getRealUser()->getUID(),
                        $this->request->getRemoteAddress()
                    ]
                );
        }

        return $this->localisation->t(
            'Passwords Session %s - %s@%s',
            [
                date('d.m.y H:i'),
                $this->environment->getUserLogin(),
                $this->request->getRemoteAddress()
            ]
        );
    }

    /**
     * @return bool
     */
    protected function mustHaveFileSystemPermission(): bool {
        return $this->config->isAppEnabled('encryption') ||
               $this->config->isAppEnabled('groupfolders') ||
               $this->config->isAppEnabled('files_external');
    }
}