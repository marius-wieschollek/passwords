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
use OCA\Passwords\Services\PasswordGenerationService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class ServiceApiController
 *
 * @package OCA\Passwords\Controller
 */
class ServiceApiController extends AbstractApiController {

    /**
     * @var PasswordGenerationService
     */
    protected $passwordGenerationService;

    /**
     * @var FaviconService
     */
    protected $faviconService;
    /**
     * @var PageShotService
     */
    private $previewService;

    /**
     * PasswordApiController constructor.
     *
     * @param string                    $appName
     * @param IRequest                  $request
     * @param FaviconService            $faviconService
     * @param PageShotService           $previewService
     * @param PasswordGenerationService $passwordGenerationService
     */
    public function __construct(
        $appName,
        IRequest $request,
        FaviconService $faviconService,
        PageShotService $previewService,
        PasswordGenerationService $passwordGenerationService
    ) {
        parent::__construct(
            $appName,
            $request,
            'GET',
            'Authorization, Content-Type, Accept',
            1728000
        );

        $this->faviconService            = $faviconService;
        $this->passwordGenerationService = $passwordGenerationService;
        $this->previewService            = $previewService;
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @return JSONResponse
     */
    public function generatePassword(): JSONResponse {

        try {
            list($password, $words) = $this->passwordGenerationService->getPassword(1, false, false, false);

            if(empty($password)) {
                throw new ApiException('Unable to create password');
            }
        } catch (\Throwable $e) {
            return $this->createErrorResponse($e);
        }

        return $this->createResponse(
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
     * @return FileDisplayResponse
     */
    public function getFavicon(string $domain, int $size = 24) {
        $file = $this->faviconService->getFavicon($domain, $size);

        return new FileDisplayResponse(
            $file,
            Http::STATUS_OK,
            ['Content-Type' => $file->getMimeType()]
        );
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
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }

        return new FileDisplayResponse(
            $file,
            Http::STATUS_OK,
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

        throw new ApiException('Invalid Size Specified');
    }
}