<?php

namespace OCA\Passwords\Controller;

use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OCA\Passwords\Encryption\SimpleEncryption;
use OCA\Passwords\Services\ConfigurationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;
use OCP\Util;

/**
 * Class PageController
 *
 * @package OCA\Passwords\Controller
 */
class PageController extends Controller {

    const WEBUI_TOKEN      = 'webui_token';
    const WEBUI_TOKEN_ID   = 'webui_token_id';
    const WEBUI_TOKEN_HELP = 'https://git.mdns.eu/nextcloud/passwords/wikis/Users/F.A.Q';

    /**
     * @var ISession
     */
    protected $session;

    /**
     * @var string
     */
    protected $userId;

    /**
     * @var ISecureRandom
     */
    protected $random;

    /**
     * @var SimpleEncryption
     */
    protected $encryption;

    /**
     * @var IUserManager
     */
    protected $userManager;

    /**
     * @var IL10N
     */
    protected $localisation;

    /**
     * @var IProvider
     */
    protected $tokenProvider;

    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * PageController constructor.
     *
     * @param string               $appName
     * @param null|string          $userId
     * @param IUserManager         $userManager
     * @param IRequest             $request
     * @param ISession             $session
     * @param IL10N                $localisation
     * @param ISecureRandom        $random
     * @param IProvider            $tokenProvider
     * @param SimpleEncryption     $encryption
     * @param ConfigurationService $configurationService
     */
    public function __construct(
        string $appName,
        ?string $userId,
        IRequest $request,
        ISession $session,
        IL10N $localisation,
        ISecureRandom $random,
        IProvider $tokenProvider,
        IUserManager $userManager,
        SimpleEncryption $encryption,
        ConfigurationService $configurationService
    ) {
        parent::__construct($appName, $request);
        $this->random               = $random;
        $this->userId               = $userId;
        $this->session              = $session;
        $this->encryption           = $encryption;
        $this->localisation         = $localisation;
        $this->tokenProvider        = $tokenProvider;
        $this->configurationService = $configurationService;
        $this->userManager          = $userManager;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse {

        $isSecure = $this->checkIfHttpsUsed();
        if($isSecure) {
            Util::addHeader('meta', ['pwui-token' => $this->generateToken()]);
        } else {
            $this->destroyToken();
        }

        return new TemplateResponse(
            $this->appName,
            'index',
            [
                'https'   => $isSecure,
                'version' => $this->getServerVersion()
            ]
        );
    }

    /**
     * @return bool
     */
    protected function checkIfHttpsUsed(): bool {
        $httpsParam = $this->request->getParam('https', 'true') === 'true';

        return $this->request->getServerProtocol() === 'https' && $httpsParam;
    }

    /**
     * @return null|string
     */
    protected function generateToken(): string {
        try {
            $token   = $this->configurationService->getUserValue(self::WEBUI_TOKEN, false);
            $tokenId = $this->configurationService->getUserValue(self::WEBUI_TOKEN_ID, false);
            if($token !== false && $tokenId !== false) {
                try {
                    $iToken = $this->tokenProvider->getTokenById($tokenId);

                    if($iToken->getId() == $tokenId) return $this->encryption->decrypt($token);
                } catch(InvalidTokenException $e) {
                }
            }

            $token       = $this->generateRandomDeviceToken();
            $name        = $this->localisation->t('Passwords WebUI Access (see %s)', [self::WEBUI_TOKEN_HELP]);
            $deviceToken = $this->tokenProvider->generateToken($token, $this->userId, $this->userId, null, $name, IToken::PERMANENT_TOKEN);
            $deviceToken->setScope(['filesystem' => false]);
            $this->tokenProvider->updateToken($deviceToken);
            $this->configurationService->setUserValue(self::WEBUI_TOKEN, $this->encryption->encrypt($token));
            $this->configurationService->setUserValue(self::WEBUI_TOKEN_ID, $deviceToken->getId());

            return $token;
        } catch(\Throwable $e) {
            return null;
        }
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
     *
     */
    protected function destroyToken(): void {
        $tokenId = $this->configurationService->getUserValue(self::WEBUI_TOKEN_ID, false);
        if($tokenId !== false) {
            $this->tokenProvider->invalidateTokenById(
                $this->userManager->get($this->userId),
                $tokenId
            );
            $this->configurationService->deleteUserValue(self::WEBUI_TOKEN);
            $this->configurationService->deleteUserValue(self::WEBUI_TOKEN_ID);
        }
    }

    /**
     * @return string
     */
    protected function getServerVersion(): string {
        $version = $this->configurationService->getSystemValue('version');

        return explode('.', $version, 2)[0];
    }
}
