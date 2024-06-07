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

namespace OCA\Passwords\Helper\Image;

use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\HelperService;

class AutoImageHelper extends AbstractImageHelper {

    protected AbstractImageHelper $imageHelper;

    public function __construct(
        protected GdHelper        $gdHelper,
        protected ImagickHelper   $imagickHelper,
        protected ImaginaryHelper $imaginaryHelper,
        ConfigurationService      $config
    ) {
        parent::__construct($config);
    }

    public function advancedResizeImage($image, int $minWidth, int $minHeight, int $maxWidth, int $maxHeight) {
        return $this->getImageHelper()->advancedResizeImage($image, $minWidth, $minHeight, $maxWidth, $maxHeight);
    }

    public function simpleResizeImage($image, int $size) {
        return $this->getImageHelper()->simpleResizeImage($image, $size);
    }

    public function cropImageRectangular($image) {
        return $this->getImageHelper()->cropImageRectangular($image);
    }

    public function getImageFromBlob($imageBlob) {
        return $this->getImageHelper()->getImageFromBlob($imageBlob);
    }

    public function destroyImage($image): bool {
        return $this->getImageHelper()->destroyImage($image);
    }

    public function exportJpeg($image): string {
        return $this->getImageHelper()->exportJpeg($image);
    }

    public function exportPng($image): string {
        return $this->getImageHelper()->exportPng($image);
    }

    public function supportsFormat(string $format): bool {
        return $this->getImageHelper()->supportsFormat($format);
    }

    public function convertIcoToPng($data): string {
        return $this->getImageHelper()->convertIcoToPng($data);
    }

    public function isAvailable(): bool {
        return $this->gdHelper->isAvailable() ||
               $this->imagickHelper->isAvailable() ||
               $this->imaginaryHelper->isAvailable();
    }

    public function getRealImageHelperName(): string {
        return match (get_class($this->getImageHelper())) {
            ImagickHelper::class => HelperService::IMAGES_IMAGICK,
            GdHelper::class => HelperService::IMAGES_GDLIB,
            ImaginaryHelper::class => HelperService::IMAGES_IMAGINARY,
        };
    }

    protected function getImageHelper(): AbstractImageHelper {
        if(!isset($this->imageHelper)) {
            if($this->imaginaryHelper->isAvailable()) {
                $this->imageHelper = $this->imaginaryHelper;
            } else if($this->imagickHelper->isAvailable()) {
                $this->imageHelper = $this->imagickHelper;
            } else {
                $this->imageHelper = $this->gdHelper;
            }
        }

        return $this->imageHelper;
    }
}