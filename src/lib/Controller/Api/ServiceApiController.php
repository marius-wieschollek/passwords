<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.08.17
 * Time: 22:01
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\AvatarService;
use OCA\Passwords\Services\FaviconService;
use OCA\Passwords\Services\PageShotService;
use OCA\Passwords\Services\WordsService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IRequest;

/**
 * Class ServiceApiController
 *
 * @package OCA\Passwords\Controller
 */
class ServiceApiController extends AbstractApiController {

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
     * @var PageShotService
     */
    protected $previewService;

    /**
     * ServiceApiController constructor.
     *
     * @param IRequest        $request
     * @param AvatarService   $avatarService
     * @param FaviconService  $faviconService
     * @param PageShotService $previewService
     * @param WordsService    $wordsService
     */
    public function __construct(
        IRequest $request,
        WordsService $wordsService,
        AvatarService $avatarService,
        FaviconService $faviconService,
        PageShotService $previewService
    ) {
        parent::__construct($request);
        $this->faviconService = $faviconService;
        $this->wordsService   = $wordsService;
        $this->previewService = $previewService;
        $this->avatarService  = $avatarService;
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @return JSONResponse
     * @throws \Throwable
     */
    public function generatePassword(): JSONResponse {
        list($password, $words) = $this->wordsService->getPassword(1, false, false, false);

        if(empty($password)) throw new ApiException('Unable to generate password');

        return $this->createJsonResponse(
            [
                'password' => $password,
                'words'    => $words,
                'strength' => 1,
                'numbers'  => false,
                'special'  => false,
                'smileys'  => false
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
     * @return FileDisplayResponse|JSONResponse
     * @throws \Throwable
     */
    public function getAvatar(string $user, int $size = 32) {
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
     * @return FileDisplayResponse|JSONResponse
     * @throws \Throwable
     */
    public function getFavicon(string $domain, int $size = 32) {
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
     * @return FileDisplayResponse|JSONResponse
     * @throws ApiException
     * @throws \OCP\AppFramework\QueryException
     */
    public function getPreview(string $domain, string $view = 'desktop', string $width = '550', string $height = '0') {
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
        return new FileDisplayResponse(
            $file,
            $statusCode,
            ['Content-Type' => $file->getMimeType()]
        );
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