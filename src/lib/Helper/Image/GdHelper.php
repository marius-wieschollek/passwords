<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 11.09.17
 * Time: 21:32
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
     * @param Image  $image
     * @param string $from
     * @param string $to
     *
     * @return Image
     */
    public function recolorImage($image, string $from, string $to) {
        $from = sscanf($from, "#%02x%02x%02x");
        $to   = sscanf($to, "#%02x%02x%02x");

        $resource = $image->resource();
        imagealphablending($resource, false);
        for ($x = 0; $x < imagesx($resource); $x++) {
            for ($y = 0; $y < imagesy($resource); $y++) {
                $index = imagecolorat($resource, $x, $y);
                $color = imagecolorsforindex($resource, $index);
                if($color['red'] == $from[0] && $color['green'] == $from[1] && $color['blue'] == $from[2]) {
                    $newColor = imagecolorallocatealpha($resource, $to[0], $to[1], $to[2], $color['alpha']);
                    imagesetpixel($resource, $x, $y, $newColor);
                }
            }
        }
        imageAlphaBlending($resource, true);
        imageSaveAlpha($resource, true);

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
            $image->load($tempFile);
            unlink($tempFile);
        } catch (Throwable $e) {
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
        $image->load($file);

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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
            if(is_file($tempFile)) @unlink($tempFile);
            throw $e;
        }

        return $content;
    }

    /**
     * @param $blob
     *
     * @return bool
     */
    public function supportsImage($blob): bool {
        $size = getimagesizefromstring($blob);

        if($size['mime'] == 'image/icon') {
            return false;
        } else if($size['mime'] == 'image/vnd.microsoft.icon') {
            return false;
        }

        return substr($size['mime'], 0, 5) == 'image';
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
}