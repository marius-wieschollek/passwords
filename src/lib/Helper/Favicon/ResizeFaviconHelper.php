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

namespace OCA\Passwords\Helper\Favicon;

use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\HelperService;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;

/**
 * Class ResizeFaviconHelper
 *
 * This class provides methods for resizing a favicon and storing it in the cache.
 */
class ResizeFaviconHelper {

    /**
     * @var FileCacheService
     */
    protected FileCacheService $fileCacheService;

    /**
     * @param FileCacheService $fileCacheService
     * @param HelperService    $helperService
     */
    public function __construct(
        FileCacheService        $fileCacheService,
        protected HelperService $helperService
    ) {
        $this->fileCacheService = $fileCacheService->getCacheService($fileCacheService::FAVICON_CACHE);
    }

    /**
     * @param ISimpleFile $favicon
     * @param int         $size
     *
     * @return ISimpleFile|null
     * @throws NotFoundException
     * @throws NotPermittedException
     */
    public function resizeFavicon(ISimpleFile $favicon, int $size): ?ISimpleFile {
        $fileName = $favicon->getName().'.'.$size.'.png';

        if($this->fileCacheService->hasFile($fileName)) {
            return $this->fileCacheService->getFile($fileName);
        }

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
}