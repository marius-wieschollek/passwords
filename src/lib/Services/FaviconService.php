<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Exception\ApiException;
use OCP\Files\SimpleFS\ISimpleFile;

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
     * @var LoggingService
     */
    protected $logger;

    /**
     * FaviconService constructor.
     *
     * @param HelperService     $helperService
     * @param FileCacheService  $fileCacheService
     * @param ValidationService $validationService
     * @param LoggingService    $logger
     */
    public function __construct(
        HelperService $helperService,
        FileCacheService $fileCacheService,
        ValidationService $validationService,
        LoggingService $logger
    ) {
        $this->fileCacheService  = $fileCacheService->getCacheService($fileCacheService::FAVICON_CACHE);
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
     * @throws \Throwable
     */
    public function getFavicon(string $domain, int $size = 32): ISimpleFile {
        list($domain, $size) = $this->validateInput($domain, $size);

        $faviconService = $this->helperService->getFaviconHelper();
        $fileName       = $faviconService->getFaviconFilename($domain, $size);
        if($this->fileCacheService->hasFile($fileName)) {
            return $this->fileCacheService->getFile($fileName);
        }

        if(!$this->validationService->isValidDomain($domain)) {
            if($domain !== 'default') {
                $pad    = str_pad(' ', strlen($domain), ' ');
                $domain = $domain[0].$pad;
            } else {
                $domain = '      ';
            }
            $faviconService = $this->helperService->getDefaultFaviconHelper();
        }

        try {
            $favicon = $faviconService->getFavicon($domain, $size);

            return $this->resizeFavicon($favicon, $fileName, $size);
        } catch(\Throwable $e) {
            $this->logger->logException($e);

            try {
                return $faviconService->getDefaultFavicon($domain, $size);
            } catch(\Throwable $e) {
                $this->logger->logException($e);

                throw new ApiException('Internal Favicon API Error', 502, $e);
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
     * @throws \OCP\Files\NotFoundException
     * @throws \OCP\Files\NotPermittedException
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
        $size = round($size / 8) * 8;
        if($size > 256) {
            $size = 256;
        } else if($size < 16) {
            $size = 16;
        }

        return [$domain, $size];
    }
}
