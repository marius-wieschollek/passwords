<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Settings\UserSettingsHelper;
use OCA\Passwords\Helper\User\DeleteUserDataHelper;
use OCA\Passwords\Services\AvatarService;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\FaviconService;
use OCA\Passwords\Services\WebsitePreviewService;
use OCA\Passwords\Services\WordsService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IRequest;
use OCP\IUserManager;

/**
 * Class ServiceApiController
 *
 * @package OCA\Passwords\Controller
 */
class ServiceApiController extends AbstractApiController {

    /**
     * @var string|null
     */
    protected $userId;

    /**
     * @var string|null
     */
    protected $userLogin;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var IUserManager
     */
    protected $userManager;

    /**
     * @var WordsService
     */
    protected $wordsService;

    /**
     * @var UserSettingsHelper
     */
    protected $userSettings;

    /**
     * @var AvatarService
     */
    protected $avatarService;

    /**
     * @var WebsitePreviewService
     */
    protected $previewService;

    /**
     * @var FaviconService
     */
    protected $faviconService;

    /**
     * @var DeleteUserDataHelper
     */
    protected $deleteUserDataHelper;

    /**
     * ServiceApiController constructor.
     *
     * @param IRequest              $request
     * @param IUserManager          $userManager
     * @param WordsService          $wordsService
     * @param AvatarService         $avatarService
     * @param ConfigurationService  $config
     * @param FaviconService        $faviconService
     * @param UserSettingsHelper    $userSettings
     * @param WebsitePreviewService $previewService
     * @param EnvironmentService    $environmentService
     * @param DeleteUserDataHelper  $deleteUserDataHelper
     *
     * @throws \Exception
     */
    public function __construct(
        IRequest $request,
        IUserManager $userManager,
        WordsService $wordsService,
        AvatarService $avatarService,
        ConfigurationService $config,
        FaviconService $faviconService,
        UserSettingsHelper $userSettings,
        WebsitePreviewService $previewService,
        EnvironmentService $environmentService,
        DeleteUserDataHelper $deleteUserDataHelper
    ) {
        parent::__construct($request);
        $this->config               = $config;
        $this->userId               = $environmentService->getUserId();
        $this->userLogin            = $environmentService->getUserLogin();
        $this->userManager          = $userManager;
        $this->userSettings         = $userSettings;
        $this->wordsService         = $wordsService;
        $this->avatarService        = $avatarService;
        $this->previewService       = $previewService;
        $this->faviconService       = $faviconService;
        $this->deleteUserDataHelper = $deleteUserDataHelper;
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param int  $strength
     * @param bool $numbers
     * @param bool $special
     *
     * @return JSONResponse
     * @throws ApiException
     */
    public function generatePassword(?int $strength = null, ?bool $numbers = null, ?bool $special = null): JSONResponse {
        if($strength === null) $strength = $this->userSettings->get('password.generator.strength');
        if($numbers === null) $numbers = $this->userSettings->get('password.generator.numbers');
        if($special === null) $special = $this->userSettings->get('password.generator.special');

        list($password, $words, $strength) = $this->wordsService->getPassword($strength, $numbers, $special);
        if(empty($password)) throw new ApiException('Unable to generate password', 503);

        return $this->createJsonResponse(
            [
                'password' => $password,
                'words'    => $words,
                'strength' => $strength,
                'numbers'  => $numbers,
                'special'  => $special
            ]
        );
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $user
     * @param int    $size
     *
     * @return FileDisplayResponse
     * @throws \Throwable
     */
    public function getAvatar(string $user, int $size = 32): FileDisplayResponse {
        $file = $this->avatarService->getAvatar($user, $size);

        return $this->createFileDisplayResponse($file);
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $domain
     * @param int    $size
     *
     * @return FileDisplayResponse
     * @throws \Throwable
     */
    public function getFavicon(string $domain, int $size = 32): FileDisplayResponse {
        $file = $this->faviconService->getFavicon($domain, $size);

        return $this->createFileDisplayResponse($file);
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $domain
     * @param string $view
     * @param string $width
     * @param string $height
     *
     * @return FileDisplayResponse
     * @throws ApiException
     */
    public function getPreview(string $domain, string $view = 'desktop', string $width = '640', string $height = '360...'): FileDisplayResponse {
        list($minWidth, $maxWidth) = $this->validatePreviewSize($width);
        list($minHeight, $maxHeight) = $this->validatePreviewSize($height);

        $file = $this->previewService->getPreview($domain, $view, $minWidth, $minHeight, $maxWidth, $maxHeight);

        return $this->createFileDisplayResponse($file);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @return JSONResponse
     * @throws ApiException
     */
    public function coffee(): JSONResponse {
        throw new ApiException('I’m a password manager', 418);
    }

    /**
     * @param ISimpleFile $file
     * @param int         $statusCode
     *
     * @return FileDisplayResponse
     */
    protected function createFileDisplayResponse(ISimpleFile $file, int $statusCode = Http::STATUS_OK): FileDisplayResponse {
        $response = new FileDisplayResponse(
            $file,
            $statusCode,
            ['Content-Type' => $file->getMimeType()]
        );

        $expires = new \DateTime('@'.(time() + 604800));
        $response->addHeader('Cache-Control', 'public, immutable, max-age=604800')
                 ->addHeader('Expires', $expires->format(\DateTime::RFC2822))
                 ->addHeader('Pragma', 'cache');

        $lastModified = new \DateTime('@'.$file->getMTime());
        $response->setLastModified($lastModified);

        return $response;
    }

    /**
     * @param $size
     *
     * @return array
     * @throws ApiException
     */
    protected function validatePreviewSize($size) {
        if(is_numeric($size)) {
            return [intval($size), intval($size)];
        } else if(preg_match("/([0-9]+)?\.\.\.([0-9]+)?/", $size, $matches)) {
            if(!isset($matches[1])) $matches[1] = 0;
            if(!isset($matches[2])) $matches[2] = 0;

            return [intval($matches[1]), intval($matches[2])];
        }

        throw new ApiException('Invalid dimensions given', 400);
    }
}