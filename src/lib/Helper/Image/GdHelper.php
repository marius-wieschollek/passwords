<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Image;

use OCP\Image;
use Throwable;

/**
 * Class GdHelper
 *
 * @package OCA\Passwords\Helper\Image
 */
class GdHelper extends AbstractImageHelper {

    /**
     * @param Image $image
     * @param int   $minWidth
     * @param int   $minHeight
     * @param int   $maxWidth
     * @param int   $maxHeight
     *
     * @return Image
     */
    public function advancedResizeImage($image, int $minWidth, int $minHeight, int $maxWidth, int $maxHeight) {

        $size = $this->getBestImageFit($image->width(), $image->height(), $minWidth, $minHeight, $maxWidth, $maxHeight);

        $image->preciseResize($size['width'], $size['height']);
        if($size['cropNeeded']) {
            if($size['cropHeight'] === 0) $size['cropHeight'] = $size['height'];
            if($size['cropWidth'] === 0) $size['cropWidth'] = $size['width'];
            $image->crop($size['cropX'], $size['cropY'], $size['cropWidth'], $size['cropHeight']);
        }

        return $image;
    }

    /**
     * @param Image $image
     * @param int   $size
     *
     * @return Image
     */
    public function simpleResizeImage($image, int $size) {

        $image->preciseResize($size, $size);

        return $image;
    }

    /**
     * @param Image $image
     *
     * @return Image
     */
    public function cropImageRectangular($image) {

        $width  = $image->width();
        $height = $image->height();

        if($width > $height) {
            $padding = ($width - $height) / 2;
            $image->crop($padding, 0, $height, $height);
        }
        if($width < $height) {
            $padding = ($height - $width) / 2;
            $image->crop(0, $padding, $width, $width);
        }

        return $image;
    }

    /**
     * @param $imageBlob
     *
     * @return Image
     * @throws Throwable
     */
    public function getImageFromBlob($imageBlob) {
        $size     = getimagesizefromstring($imageBlob);
        $mime     = substr($size['mime'], 6);
        $tempFile = $this->config->getTempDir().uniqid().'.'.$mime;

        try {
            file_put_contents($tempFile, $imageBlob);
            $image = $this->getNewImageObject();
            $image->loadFromFile($tempFile);
            unlink($tempFile);
        } catch(Throwable $e) {
            if(is_file($tempFile)) @unlink($tempFile);
            throw $e;
        }

        return $image;
    }

    /**
     * @param string $file
     *
     * @return Image
     */
    public function getImageFromFile(string $file) {
        $image = $this->getNewImageObject();
        $image->loadFromFile($file);

        return $image;
    }

    /**
     * @param Image $image
     *
     * @return bool
     */
    public function destroyImage($image): bool {
        $image->destroy();

        return true;
    }

    /**
     * @param Image $image
     *
     * @return string
     * @throws Throwable
     */
    public function exportJpeg($image) {
        $tempFile = $this->config->getTempDir().uniqid();

        try {
            $image->save($tempFile, 'image/jpeg');
            $content = file_get_contents($tempFile);
            unlink($tempFile);
        } catch(Throwable $e) {
            if(is_file($tempFile)) @unlink($tempFile);
            throw $e;
        }

        return $content;
    }

    /**
     * @param Image $image
     *
     * @return string
     * @throws Throwable
     */
    public function exportPng($image) {
        $tempFile = $this->config->getTempDir().uniqid();

        try {
            $image->save($tempFile, 'image/png');
            $content = file_get_contents($tempFile);
            unlink($tempFile);
        } catch(Throwable $e) {
            if(is_file($tempFile)) @unlink($tempFile);
            throw $e;
        }

        return $content;
    }

    /**
     * @param string $format
     *
     * @return bool
     */
    public function supportsFormat(string $format): bool {
        $format = strtolower($format);

        return in_array($format, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'x-bmp']);
    }

    /**
     * @param string $data
     *
     * @return string
     */
    public function convertIcoToPng($data) {
        return $data;
    }

    /**
     * @return Image
     */
    protected function getNewImageObject() {
        return new Image();
    }

    /**
     * @inheritdoc
     */
    public static function isAvailable(): bool {
        return true;
    }
}