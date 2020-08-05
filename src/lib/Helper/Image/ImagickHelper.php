<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Image;

use Gmagick;
use Imagick;
use Throwable;

/**
 * Class ImagickHelper
 *
 * @package OCA\Passwords\Helper\Image
 */
class ImagickHelper extends AbstractImageHelper {

    /**
     * @param Imagick|Gmagick $image
     * @param int             $minWidth
     * @param int             $minHeight
     * @param int             $maxWidth
     * @param int             $maxHeight
     *
     * @return Imagick|Gmagick
     * @throws \GmagickException
     */
    public function advancedResizeImage($image, int $minWidth, int $minHeight, int $maxWidth, int $maxHeight) {

        $size = $this->getBestImageFit($image->getImageWidth(), $image->getImageHeight(), $minWidth, $minHeight, $maxWidth,
                                       $maxHeight);

        $image->resizeImage($size['width'], $size['height'], $image::FILTER_LANCZOS, 1);
        if($size['cropNeeded']) {
            $image->cropImage($size['cropWidth'], $size['cropHeight'], $size['cropX'], $size['cropY']);
        }

        return $image;
    }

    /**
     * @param Imagick|Gmagick $image
     * @param int             $size
     *
     * @return Imagick|Gmagick
     * @throws \GmagickException
     */
    public function simpleResizeImage($image, int $size) {
        $image->resizeImage($size, $size, $image::FILTER_LANCZOS, 1, 1);

        return $image;
    }

    /**
     * @param Imagick|Gmagick $image
     *
     * @return Imagick|Gmagick
     * @throws \GmagickException
     */
    public function cropImageRectangular($image) {
        $width  = $image->getImageWidth();
        $height = $image->getImageHeight();

        if($width > $height) {
            $padding = ($width - $height) / 2;
            $image->cropImage($height, $height, $padding, 0);
        }
        if($width < $height) {
            $padding = ($height - $width) / 2;
            $image->cropImage($width, $width, 0, $padding);
        }

        return $image;
    }

    /**
     * @param $imageBlob
     *
     * @return Imagick|Gmagick
     * @throws Throwable
     */
    public function getImageFromBlob($imageBlob) {
        $size = getimagesizefromstring($imageBlob);

        if($size && in_array($size['mime'], ['image/icon', 'image/vnd.microsoft.icon'])) {
            $imageBlob = $this->convertIcoToPng($imageBlob);
        }

        $image = $this->getNewImageObject();
        $image->readImageBlob($imageBlob);
        $image->stripImage();

        return $image;
    }

    /**
     * @param string $file
     *
     * @return Gmagick|Imagick
     * @throws \ImagickException
     * @throws \GmagickException
     */
    public function getImageFromFile(string $file) {
        $image = $this->getNewImageObject();
        $image->setFont($this->getDefaultFont());
        $image->readImage($file);
        $image->stripImage();

        return $image;
    }

    /**
     * @param Imagick|Gmagick $image
     *
     * @return bool
     * @throws \GmagickException
     */
    public function destroyImage($image): bool {
        $image->clear();

        return $image->destroy();
    }

    /**
     * @param Imagick|Gmagick $image
     *
     * @return string
     * @throws \GmagickException
     */
    public function exportJpeg($image) {

        $image->setImageFormat('jpg');
        $image->setImageCompression($image::COMPRESSION_JPEG);
        if($image instanceof Imagick) $image->setImageCompressionQuality(90);
        $image->setCompressionQuality(100);
        $image->stripImage();

        return $image->getImageBlob();
    }

    /**
     * @param Imagick|Gmagick $image
     *
     * @return string
     * @throws \GmagickException
     */
    public function exportPng($image) {

        $image->setImageFormat('png');
        if($image instanceof Imagick) $image->setImageCompressionQuality(9);
        $image->setCompressionQuality(100);
        $image->stripImage();

        return $image->getImageBlob();
    }

    /**
     * @param string $format
     *
     * @return bool
     * @throws \ImagickException
     * @throws \GmagickException
     */
    public function supportsFormat(string $format): bool {
        $image = $this->getNewImageObject();

        if($format == 'vnd.microsoft.icon') {
            $format = 'icon';
        } else if($format == 'x-bmp') {
            $format = 'bmp';
        } else if($format == 'svg+xml') $format = 'svg';
        $format = strtoupper($format);

        return !empty($image->queryFormats($format));
    }

    /**
     * @param string $data
     *
     * @return string
     * @throws Throwable
     */
    public function convertIcoToPng($data) {
        $tempFile = $this->config->getTempDir().uniqid().'.ico';

        try {
            file_put_contents($tempFile, $data);

            $image = $this->getNewImageObject();
            $image->readImage($tempFile);
            $image->setImageFormat('png');
            $content = $image->getImageBlob();

            $image->destroy();
            unlink($tempFile);
        } catch(Throwable $e) {
            if(is_file($tempFile)) @unlink($tempFile);
            throw $e;
        }

        return $content;
    }

    /**
     * @return Imagick|Gmagick
     * @throws \ImagickException
     */
    protected function getNewImageObject() {
        return class_exists(Imagick::class) ? new Imagick():new Gmagick();
    }

    /**
     * @inheritdoc
     */
    public static function isAvailable(): bool {
        return class_exists(Imagick::class) || class_exists(Gmagick::class);
    }
}