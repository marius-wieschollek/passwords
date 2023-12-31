<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Services;

use Exception;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Image\AbstractImageHelper;
use OCA\Passwords\Provider\Preview\AbstractPreviewProvider;
use OCA\Passwords\Provider\Preview\PreviewProviderInterface;
use OCA\Passwords\Services\Traits\ValidatesDomainTrait;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use Throwable;

/**
 * Class WebsitePreviewService
 *
 * @package OCA\Passwords\Services
 */
class WebsitePreviewService {

    use ValidatesDomainTrait;

    const VIEWPORT_DESKTOP = 'desktop';
    const VIEWPORT_MOBILE  = 'mobile';

    /**
     * @var AbstractImageHelper
     */
    protected AbstractImageHelper $imageService;

    /**
     * @var FileCacheService
     */
    protected FileCacheService $fileCacheService;

    /**
     * FaviconService constructor.
     *
     * @param HelperService            $helperService
     * @param FileCacheService         $fileCacheService
     * @param ValidationService        $validationService
     * @param LoggingService           $logger
     * @param PreviewProviderInterface $previewProvider
     */
    public function __construct(
        HelperService $helperService,
        FileCacheService $fileCacheService,
        protected ValidationService $validationService,
        protected LoggingService $logger,
        protected PreviewProviderInterface $previewProvider
    ) {
        $this->fileCacheService  = $fileCacheService->getCacheService($fileCacheService::PREVIEW_CACHE);
        $this->imageService      = $helperService->getImageHelper();
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
        [$domain, $minWidth, $minHeight, $maxWidth, $maxHeight]
            = $this->validateInputData($domain, $minWidth, $minHeight, $maxWidth, $maxHeight);

        try {
            return $this->getWebsitePreview($domain, $view, $minWidth, $minHeight, $maxWidth, $maxHeight);
        } catch(Throwable $e) {
            $this->logger->logException($e);

            return $this->getDefaultPreview($domain, $minWidth, $minHeight, $maxWidth, $maxHeight);
        }
    }

    /**
     * @param string $domain
     * @param string $view
     * @param int    $minWidth
     * @param int    $minHeight
     * @param int    $maxWidth
     * @param int    $maxHeight
     *
     * @return null|ISimpleFile
     * @throws Exception
     */
    protected function getWebsitePreview(string $domain, string $view, int $minWidth, int $minHeight, int $maxWidth, int $maxHeight): ?ISimpleFile {
        if(!$this->validationService->isValidDomain($domain)) {
            $websitePreview = $this->previewProvider->getDefaultPreview('default');
        } else {
            $websitePreview = $this->previewProvider->getPreview($domain, $view);
        }

        return $this->resizePreview($websitePreview, $minWidth, $minHeight, $maxWidth, $maxHeight);
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
            $websitePreview = $this->previewProvider->getDefaultPreview($domain);

            return $this->resizePreview($websitePreview, 'error.jpg', $minWidth, $minHeight, $maxWidth, $maxHeight);
        } catch(Throwable $e) {
            $this->logger->logException($e);

            throw new ApiException('Internal Website Preview API Error', 502, $e);
        }
    }

    /**
     * @param ISimpleFile $preview
     * @param int         $minWidth
     * @param int         $minHeight
     * @param int         $maxWidth
     * @param int         $maxHeight
     *
     * @return ISimpleFile|null
     * @throws NotFoundException
     * @throws NotPermittedException
     */
    protected function resizePreview(
        ISimpleFile $preview,
        int $minWidth,
        int $minHeight,
        int $maxWidth,
        int $maxHeight
    ): ?ISimpleFile {
        $image     = $this->imageService->getImageFromBlob($preview->getContent());
        $image     = $this->imageService->advancedResizeImage($image, $minWidth, $minHeight, $maxWidth, $maxHeight);
        $imageData = $this->imageService->exportJpeg($image);
        $this->imageService->destroyImage($image);

        return $this->fileCacheService->putFile($preview->getName(), $imageData);
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
        $domain = $this->validateDomain($domain);

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
        if($maximum < $minimum && $maximum !== 0.0) return $minimum;
        if($maximum > 1280) return 1280;
        if($maximum < 240 && $maximum !== 0.0) return 240;

        return $maximum;
    }
}