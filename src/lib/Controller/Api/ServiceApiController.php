<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.08.17
 * Time: 22:01
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Helper\PasswordGenerationHelper;
use OCA\Passwords\Services\FaviconService;
use OCA\Passwords\Services\PageShotService;
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
     * @var PasswordGenerationHelper
     */
    protected $passwordCreationHelper;

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
     * @param string                   $appName
     * @param IRequest                 $request
     * @param FaviconService           $faviconService
     * @param PageShotService          $previewService
     * @param PasswordGenerationHelper $passwordCreationHelper
     */
    public function __construct(
        $appName,
        IRequest $request,
        FaviconService $faviconService,
        PageShotService $previewService,
        PasswordGenerationHelper $passwordCreationHelper
    ) {
        parent::__construct(
            $appName,
            $request,
            'GET',
            'Authorization, Content-Type, Accept',
            1728000
        );

        $this->faviconService         = $faviconService;
        $this->passwordCreationHelper = $passwordCreationHelper;
        $this->previewService = $previewService;
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @return JSONResponse
     */
    public function generatePassword(): JSONResponse {

        list($password, $words) = $this->passwordCreationHelper->create(1, false, false, false);

        if(empty($password)) {
            return $this->createErrorResponse(new \Exception('Unable to create password'));
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
     * @param int    $width
     * @param int    $height
     *
     * @return FileDisplayResponse
     */
    public function getPreview(string $domain, string $view = 'desktop', int $width = 550, int $height = 0) {

        $file = $this->previewService->getPreview($domain, $view, $width, $height);
        return new FileDisplayResponse(
            $file,
            Http::STATUS_OK,
            ['Content-Type' => $file->getMimeType()]
        );
    }
}