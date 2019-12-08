<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Image\AbstractImageHelper;
use OCA\Passwords\Helper\Preview\AbstractPreviewHelper;
use OCP\Files\SimpleFS\ISimpleFile;

/**
 * Class WebsitePreviewService
 *
 * @package OCA\Passwords\Services
 */
class WebsitePreviewService {

    const VIEWPORT_DESKTOP = 'desktop';
    const VIEWPORT_MOBILE  = 'mobile';

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var AbstractImageHelper
     */
    protected $imageService;

    /**
     * @var AbstractPreviewHelper
     */
    protected $previewService;

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * @var ValidationService
     */
    protected $validationService;

    /**
     * FaviconService constructor.
     *
     * @param HelperService     $helperService
     * @param FileCacheService  $fileCacheService
     * @param ValidationService $validationService
     * @param LoggingService    $logger
     *
     * @throws \OCP\AppFramework\QueryException
     */
    public function __construct(
        HelperService $helperService,
        FileCacheService $fileCacheService,
        ValidationService $validationService,
        LoggingService $logger
    ) {
        $this->fileCacheService  = $fileCacheService->getCacheService($fileCacheService::PREVIEW_CACHE);
        $this->validationService = $validationService;
        $this->previewService    = $helperService->getWebsitePreviewHelper();
        $this->imageService      = $helperService->getImageHelper();
        $this->logger            = $logger;
    }

    /**
     * @param string $domain
     * @param string $view
     * @param int    $minWidth
     * @param int    $minHeight
     * @param int    $maxWidth
     * @param int    $maxHeight
     *
     * @return ISimpleFile
     * @throws ApiException
     */
    public function getPreview(
        string $domain,
        string $view = self::VIEWPORT_DESKTOP,
        int $minWidth = 640,
        int $minHeight = 0,
        int $maxWidth = 640,
        int $maxHeight = 0
    ): ISimpleFile {
        list($domain, $minWidth, $minHeight, $maxWidth, $maxHeight)
            = $this->validateInputData($domain, $minWidth, $minHeight, $maxWidth, $maxHeight);

        $fileName = $this->previewService->getPreviewFilename($domain, $view, $minWidth, $minHeight, $maxWidth, $maxHeight);
        if($this->fileCacheService->hasFile($fileName)) {
            return $this->fileCacheService->getFile($fileName);
        }

        try {
            return $this->getWebsitePreview($domain, $view, $fileName, $minWidth, $minHeight, $maxWidth, $maxHeight);
        } catch(\Throwable $e) {
            $this->logger->logException($e);

            return $this->getDefaultPreview($domain, $minWidth, $minHeight, $maxWidth, $maxHeight);
        }
    }

    /**
     * @param string $domain
     * @param string $view
     * @param string $fileName
     * @param int    $minWidth
     * @param int    $minHeight
     * @param int    $maxWidth
     * @param int    $maxHeight
     *
     * @return null|ISimpleFile
     * @throws \Exception
     */
    protected function getWebsitePreview(string $domain, string $view, string $fileName, int $minWidth, int $minHeight, int $maxWidth, int $maxHeight): ?ISimpleFile {
        if(!$this->validationService->isValidDomain($domain)) {
            $websitePreview = $this->previewService->getDefaultPreview('default');
        } else {
            $websitePreview = $this->previewService->getPreview($domain, $view);
        }

        return $this->resizePreview($websitePreview, $fileName, $minWidth, $minHeight, $maxWidth, $maxHeight);
    }

    /**
     * @param string $domain
     * @param int    $minWidth
     * @param int    $minHeight
     * @param int    $maxWidth
     * @param int    $maxHeight
     *
     * @return null|ISimpleFile
     * @throws ApiException
     */
    protected function getDefaultPreview(string $domain, int $minWidth, int $minHeight, int $maxWidth, int $maxHeight): ?ISimpleFile {
        try {
            $websitePreview = $this->previewService->getDefaultPreview($domain);

            return $this->resizePreview($websitePreview, 'error.jpg', $minWidth, $minHeight, $maxWidth, $maxHeight);
        } catch(\Throwable $e) {
            $this->logger->logException($e);

            throw new ApiException('Internal Website Preview API Error', 502, $e);
        }
    }

    /**
     * @param ISimpleFile $preview
     * @param string      $fileName
     * @param int         $minWidth
     * @param int         $minHeight
     * @param int         $maxWidth
     * @param int         $maxHeight
     *
     * @return ISimpleFile|null
     * @throws \OCP\Files\NotFoundException
     * @throws \OCP\Files\NotPermittedException
     */
    protected function resizePreview(
        ISimpleFile $preview,
        string $fileName,
        int $minWidth,
        int $minHeight,
        int $maxWidth,
        int $maxHeight
    ): ?ISimpleFile {

        $image     = $this->imageService->getImageFromBlob($preview->getContent());
        $image     = $this->imageService->advancedResizeImage($image, $minWidth, $minHeight, $maxWidth, $maxHeight);
        $imageData = $this->imageService->exportJpeg($image);
        $this->imageService->destroyImage($image);

        return $this->fileCacheService->putFile($fileName, $imageData);
    }

    /**
     * @param string $domain
     * @param int    $minWidth
     * @param int    $minHeight
     * @param int    $maxWidth
     * @param int    $maxHeight
     *
     * @return array
     */
    protected function validateInputData(string $domain, int $minWidth, int $minHeight, int $maxWidth, int $maxHeight): array {
        if(filter_var($domain, FILTER_VALIDATE_URL)) $domain = parse_url($domain, PHP_URL_HOST);

        $minWidth = $this->validateMinimum($minWidth);
        $maxWidth = $this->validateMaximum($minWidth, $maxWidth);

        $minHeight = $this->validateMinimum($minHeight);
        $maxHeight = $this->validateMaximum($minHeight, $maxHeight);

        return [$domain, $minWidth, $minHeight, $maxWidth, $maxHeight];
    }

    /**
     * @param int $minimum
     *
     * @return int
     */
    protected function validateMinimum(int $minimum): int {
        $minimum = round($minimum, -1);
        if($minimum > 1280) return 1280;
        if($minimum < 240) return 240;

        return $minimum;
    }

    /**
     * @param int $minimum
     * @param int $maximum
     *
     * @return int
     */
    protected function validateMaximum(int $minimum, int $maximum): int {
        $maximum = round($maximum, -1);
        if($maximum < $minimum && $maximum != 0) return $minimum;
        if($maximum > 1280) return 1280;
        if($maximum < 240 && $maximum != 0) return 240;

        return $maximum;
    }
}