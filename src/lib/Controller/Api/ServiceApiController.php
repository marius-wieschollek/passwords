<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\User\DeleteUserDataHelper;
use OCA\Passwords\Services\AvatarService;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FaviconService;
use OCA\Passwords\Services\WebsitePreviewService;
use OCA\Passwords\Services\WordsService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserManager;

/**
 * Class ServiceApiController
 *
 * @package OCA\Passwords\Controller
 */
class ServiceApiController extends AbstractApiController {

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var WordsService
     */
    protected $wordsService;

    /**
     * @var AvatarService
     */
    protected $avatarService;

    /**
     * @var FaviconService
     */
    protected $faviconService;

    /**
     * @var WebsitePreviewService
     */
    protected $previewService;

    /**
     * @var string
     */
    protected $userId;

    /**
     * @var IUserManager
     */
    protected $userManager;

    /**
     * @var DeleteUserDataHelper
     */
    protected $deleteUserDataHelper;

    /**
     * ServiceApiController constructor.
     *
     * @param string                $userId
     * @param IRequest              $request
     * @param WordsService          $wordsService
     * @param AvatarService         $avatarService
     * @param ConfigurationService  $config
     * @param FaviconService        $faviconService
     * @param WebsitePreviewService $previewService
     * @param DeleteUserDataHelper  $deleteUserDataHelper
     * @param IUserManager          $userManager
     */
    public function __construct(
        string $userId,
        IRequest $request,
        WordsService $wordsService,
        AvatarService $avatarService,
        ConfigurationService $config,
        FaviconService $faviconService,
        WebsitePreviewService $previewService,
        IUserManager $userManager,
        DeleteUserDataHelper $deleteUserDataHelper
    ) {
        parent::__construct($request);
        $this->faviconService       = $faviconService;
        $this->wordsService         = $wordsService;
        $this->previewService       = $previewService;
        $this->avatarService        = $avatarService;
        $this->config               = $config;

        $this->userId               = $userId;
        $this->userManager          = $userManager;
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
        if($strength === null) $strength = $this->config->getUserValue('password/generator/strength', 1);
        if($numbers === null) $numbers = $this->config->getUserValue('password/generator/numbers', false);
        if($special === null) $special = $this->config->getUserValue('password/generator/special', false);

        list($password, $words) = $this->wordsService->getPassword($strength, $numbers, $special);
        if(empty($password)) throw new ApiException('Unable to generate password');

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
     * @param $password
     *
     * @return JSONResponse
     * @throws ApiException
     * @throws \OCP\PreConditionNotMetException
     * @throws \Exception
     */
    public function resetUserAccount(string $password): JSONResponse {
        if(!$this->userManager->checkPassword($this->userId, $password)) {
            throw new ApiException('Password invalid', 403);
        }

        $timeout    = $this->config->getUserValue('reset_timeout', 0);
        $time       = $this->config->getUserValue('reset_time', 0);
        $difference = time() - $timeout - $time;
        if($difference > 0 && $difference < 300) {
            $this->config->deleteUserValue('reset_time');
            $this->config->deleteUserValue('reset_timeout');
            $this->deleteUserDataHelper->deleteUserData($this->userId);

            return $this->createJsonResponse(['status' => 'ok'], 200);
        }

        $timeout = rand(5, 10);
        $time    = time();
        $this->config->setUserValue('reset_time', $time);
        $this->config->setUserValue('reset_timeout', $timeout);

        return $this->createJsonResponse(['status' => 'accepted', 'wait' => $timeout], 202);
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

        $expires = new \DateTime();
        $expires->setTimestamp(time() + 604800);
        $response->addHeader('Cache-Control', 'public, immutable, max-age=604800')
                 ->addHeader('Expires', $expires->format(\DateTime::RFC2822))
                 ->addHeader('Pragma', 'cache');

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