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

namespace OCA\Passwords\Helper\Preview;

use OCA\Passwords\Helper\Image\AbstractImageHelper;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\HelperService;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;

/**
 * Class ResizePreviewHelper
 *
 * This class provides functionality to resize a website preview and store it in the cache.
 */
class ResizePreviewHelper {

    /**
     * @var AbstractImageHelper
     */
    protected AbstractImageHelper $imageService;

    /**
     * @var FileCacheService
     */
    protected FileCacheService $fileCacheService;

    /**
     * ResizePreviewHelper constructor.
     *
     * @param HelperService    $helperService
     * @param FileCacheService $fileCacheService
     */
    public function __construct(
        HelperService    $helperService,
        FileCacheService $fileCacheService
    ) {
        $this->fileCacheService = $fileCacheService->getCacheService($fileCacheService::PREVIEW_CACHE);
        $this->imageService     = $helperService->getImageHelper();
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
    public function resizePreview(
        ISimpleFile $preview,
        int         $minWidth,
        int         $minHeight,
        int         $maxWidth,
        int         $maxHeight
    ): ?ISimpleFile {
        $fileName = $preview->getName().'.'.hash('crc32', $minWidth.$minHeight.$maxWidth.$maxHeight).'.jpg';

        if($this->fileCacheService->hasFile($fileName)) {
            return $this->fileCacheService->getFile($fileName);
        }

        $image     = $this->imageService->getImageFromBlob($preview->getContent());
        $image     = $this->imageService->advancedResizeImage($image, $minWidth, $minHeight, $maxWidth, $maxHeight);
        $imageData = $this->imageService->exportJpeg($image);
        $this->imageService->destroyImage($image);

        return $this->fileCacheService->putFile($fileName, $imageData);
    }
}