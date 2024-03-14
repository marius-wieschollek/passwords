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

namespace OCA\Passwords\Controller\Api;

use DateTime;
use Exception;
use DateTimeInterface;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Settings\UserSettingsHelper;
use OCA\Passwords\Services\AvatarService;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\FaviconService;
use OCA\Passwords\Services\PasswordChangeUrlService;
use OCA\Passwords\Services\PasswordSecurityCheckService;
use OCA\Passwords\Services\WebsitePreviewService;
use OCA\Passwords\Services\WordsService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IRequest;
use OCP\IUserManager;
use Throwable;

/**
 * Class ServiceApiController
 *
 * @package OCA\Passwords\Controller
 */
class ServiceApiController extends AbstractApiController {

    /**
     * @var string|null
     */
    protected ?string $userId;

    /**
     * @var string|null
     */
    protected ?string $userLogin;

    /**
     * ServiceApiController constructor.
     *
     * @param IRequest                 $request
     * @param IUserManager             $userManager
     * @param WordsService             $wordsService
     * @param AvatarService            $avatarService
     * @param ConfigurationService     $config
     * @param FaviconService           $faviconService
     * @param UserSettingsHelper       $userSettings
     * @param WebsitePreviewService    $previewService
     * @param EnvironmentService       $environmentService
     * @param PasswordChangeUrlService $passwordChangeUrlService
     */
    public function __construct(
        IRequest $request,
        protected IUserManager $userManager,
        protected WordsService $wordsService,
        protected AvatarService $avatarService,
        protected ConfigurationService $config,
        protected FaviconService $faviconService,
        protected UserSettingsHelper $userSettings,
        protected WebsitePreviewService $previewService,
        EnvironmentService $environmentService,
        protected PasswordChangeUrlService $passwordChangeUrlService,
        protected PasswordSecurityCheckService $securityCheckService
    ) {
        parent::__construct($request);
        $this->userId               = $environmentService->getUserId();
        $this->userLogin            = $environmentService->getUserLogin();
    }

    /**
     * @param int|null $strength
     * @param bool     $numbers
     * @param bool     $special
     *
     * @return JSONResponse
     * @throws ApiException
     * @throws Exception
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function generatePassword(?int $strength = null, ?bool $numbers = null, ?bool $special = null): JSONResponse {
        if($strength === null) $strength = $this->userSettings->get('password.generator.strength');
        if($numbers === null) $numbers = $this->userSettings->get('password.generator.numbers');
        if($special === null) $special = $this->userSettings->get('password.generator.special');

        [$password, $words, $strength] = $this->wordsService->getPassword($strength, $numbers, $special);
        if(empty($password)) throw new ApiException('Unable to generate password', Http::STATUS_SERVICE_UNAVAILABLE);

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
     * @param string $user
     * @param int    $size
     *
     * @return FileDisplayResponse
     * @throws Throwable
     */
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function getAvatar(string $user, int $size = 32): FileDisplayResponse {
        $file = $this->avatarService->getAvatar($user, $size);

        return $this->createFileDisplayResponse($file);
    }

    /**
     * @param string $domain
     * @param int    $size
     *
     * @return FileDisplayResponse
     * @throws Throwable
     */
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function getFavicon(string $domain, int $size = 32): FileDisplayResponse {
        $file = $this->faviconService->getFavicon($domain, $size);

        return $this->createFileDisplayResponse($file);
    }

    /**
     * @param string $domain
     * @param string $view
     * @param string $width
     * @param string $height
     *
     * @return FileDisplayResponse
     * @throws ApiException
     */
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function getPreview(string $domain, string $view = 'desktop', string $width = '640', string $height = '360...'): FileDisplayResponse {
        [$minWidth, $maxWidth] = $this->validatePreviewSize($width);
        [$minHeight, $maxHeight] = $this->validatePreviewSize($height);

        $file = $this->previewService->getPreview($domain, $view, $minWidth, $minHeight, $maxWidth, $maxHeight);

        return $this->createFileDisplayResponse($file);
    }

    /**
     * @param string $domain
     *
     * @return JSONResponse
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    #[UserRateLimit(limit: 2, period: 8)]
    public function passwordChangeUrl(string $domain): JSONResponse {
        $url = $this->passwordChangeUrlService->getPasswordChangeUrl($domain);

        return $this->createJsonResponse(
            ['url' => $url],
            $url === null ? Http::STATUS_NOT_FOUND:Http::STATUS_OK
        );
    }

    /**
     * @param string $range
     *
     * @return JSONResponse
     * @throws ApiException
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    #[UserRateLimit(limit: 10, period: 10)]
    public function getHashes(string $range): JSONResponse {
        if(strlen($range) < 5 || strlen($range) > 40) {
            throw new ApiException('Invalid range', Http::STATUS_BAD_REQUEST);
        }

        $hashes = $this->securityCheckService->getHashRange($range);

        return new JSONResponse($hashes, empty($hashes) ? Http::STATUS_NOT_FOUND:Http::STATUS_OK);
    }

    /**
     * @return JSONResponse
     * @throws ApiException
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function coffee(): JSONResponse {
        throw new ApiException('I’m a password manager', Http::STATUS_IM_A_TEAPOT);
    }

    /**
     * @param ISimpleFile $file
     * @param int         $statusCode
     *
     * @return FileDisplayResponse
     * @throws Exception
     */
    protected function createFileDisplayResponse(ISimpleFile $file, int $statusCode = Http::STATUS_OK): FileDisplayResponse {
        $response = new FileDisplayResponse(
            $file,
            $statusCode,
            ['Content-Type' => $file->getMimeType()]
        );

        $expires = new DateTime('@'.(time() + 604800));
        $response->addHeader('Cache-Control', 'public, immutable, max-age=604800')
                 ->addHeader('Expires', $expires->format(DateTimeInterface::RFC2822))
                 ->addHeader('Pragma', 'cache');

        $lastModified = new DateTime('@'.$file->getMTime());
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

        throw new ApiException('Invalid dimensions given', Http::STATUS_BAD_REQUEST);
    }
}