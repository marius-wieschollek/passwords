<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 04.09.17
 * Time: 20:27
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Exception\ApiException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\ILogger;

/**
 * Class FaviconService
 *
 * @package OCA\Passwords\Services
 */
class FaviconService {

    /**
     * @var HelperService
     */
    protected $helperService;

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * @var ValidationService
     */
    protected $validationService;

    /**
     * @var ILogger
     */
    protected $logger;

    /**
     * FaviconService constructor.
     *
     * @param HelperService     $helperService
     * @param FileCacheService  $fileCacheService
     * @param ValidationService $validationService
     * @param ILogger           $logger
     */
    public function __construct(
        HelperService $helperService,
        FileCacheService $fileCacheService,
        ValidationService $validationService,
        ILogger $logger
    ) {
        $fileCacheService->setDefaultCache($fileCacheService::FAVICON_CACHE);
        $this->fileCacheService  = $fileCacheService;
        $this->validationService = $validationService;
        $this->helperService     = $helperService;
        $this->logger            = $logger;
    }

    /**
     * @param string $domain
     * @param int    $size
     *
     * @return ISimpleFile
     * @throws ApiException
     * @throws \OCP\AppFramework\QueryException
     */
    public function getFavicon(string $domain, int $size = 24) {
        list($domain, $size) = $this->validateInput($domain, $size);

        $faviconService = $this->helperService->getFaviconHelper();
        $fileName       = $faviconService->getFaviconFilename($domain, $size);
        if($this->fileCacheService->hasFile($fileName)) {
            return $this->fileCacheService->getFile($fileName);
        }

        try {
            if(!$this->validationService->isValidDomain($domain)) {
                $favicon = $faviconService->getDefaultFavicon();
            } else {
                $favicon = $faviconService->getFavicon($domain);
            }

            return $this->resizeFavicon($favicon, $fileName, $size);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), ['app' => Application::APP_NAME]);

            try {
                $favicon = $faviconService->getDefaultFavicon();

                return $this->resizeFavicon($favicon, 'error.png', $size);
            } catch (\Throwable $e) {
                $this->logger->error($e->getMessage(), ['app' => Application::APP_NAME]);

                throw new ApiException('Internal Favicon API Error');
            }
        }
    }

    /**
     * @param ISimpleFile $favicon
     * @param string      $fileName
     * @param int         $size
     *
     * @return ISimpleFile|null
     * @throws \OCP\AppFramework\QueryException
     */
    protected function resizeFavicon(ISimpleFile $favicon, string $fileName, int $size): ?ISimpleFile {
        $faviconData = $favicon->getContent();
        $imageHelper = $this->helperService->getImageHelper();
        if($imageHelper->supportsImage($faviconData)) {
            $image = $imageHelper->getImageFromBlob($faviconData);
            $imageHelper->cropImageRectangular($image);
            $imageHelper->simpleResizeImage($image, $size);
            $faviconData = $imageHelper->exportPng($image);
            $imageHelper->destroyImage($image);
        }

        return $this->fileCacheService->putFile($fileName, $faviconData);
    }

    /**
     * @param string $domain
     * @param int    $size
     *
     * @return array
     */
    protected function validateInput(string $domain, int $size): array {
        if(filter_var($domain, FILTER_VALIDATE_URL)) $domain = parse_url($domain, PHP_URL_HOST);
        if($size > 128) {
            $size = 128;
        } else if($size < 16) {
            $size = 16;
        }

        return [$domain, $size];
    }
}
