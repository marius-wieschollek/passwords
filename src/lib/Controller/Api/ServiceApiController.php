<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.08.17
 * Time: 22:01
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Exception\ApiException;
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
     * @var FaviconService
     */
    protected $faviconService;

    /**
     * @var PageShotService
     */
    protected $previewService;

    /**
     * PasswordApiController constructor.
     *
     * @param string          $appName
     * @param IRequest        $request
     * @param FaviconService  $faviconService
     * @param PageShotService $previewService
     * @param WordsService    $wordsService
     */
    public function __construct(
        $appName,
        IRequest $request,
        FaviconService $faviconService,
        PageShotService $previewService,
        WordsService $wordsService
    ) {
        parent::__construct(
            $appName,
            $request,
            'GET',
            'Authorization, Content-Type, Accept',
            1728000
        );

        $this->faviconService = $faviconService;
        $this->wordsService   = $wordsService;
        $this->previewService = $previewService;
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @return JSONResponse
     */
    public function generatePassword(): JSONResponse {
        try {
            $this->checkAccessPermissions();
            list($password, $words) = $this->wordsService->getPassword(1, false, false, false);

            if(empty($password)) throw new ApiException('Unable to generate password');
        } catch (\Throwable $e) {
            return $this->createErrorResponse($e);
        }

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
     * @param string $domain
     *
     * @param int    $size
     *
     * @return FileDisplayResponse|JSONResponse
     */
    public function getFavicon(string $domain, int $size = 24) {
        try {
            $file = $this->faviconService->getFavicon($domain, $size);

            return $this->createFileDisplayResponse($file);
        } catch (\Throwable $e) {
            return $this->createErrorResponse($e);
        }
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
     */
    public function getPreview(string $domain, string $view = 'desktop', string $width = '550', string $height = '0') {
        try {
            list($minWidth, $maxWidth) = $this->validatePreviewSize($width);
            list($minHeight, $maxHeight) = $this->validatePreviewSize($height);

            $file = $this->previewService->getPreview($domain, $view, $minWidth, $minHeight, $maxWidth, $maxHeight);

            return $this->createFileDisplayResponse($file);
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
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

        throw new ApiException('Invalid Size Specified', 400);
    }
}