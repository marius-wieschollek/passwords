<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 11.09.17
 * Time: 23:02
 */

namespace OCA\Passwords\Helper\Image;

/**
 * Class AbstractImageHelper
 *
 * @package OCA\Passwords\Helper\Image
 */
abstract class AbstractImageHelper {

    /**
     * @param int $width
     * @param int $height
     * @param int $minWidth
     * @param int $minHeight
     * @param int $maxWidth
     * @param int $maxHeight
     *
     * @return array
     */
    public function getBestImageFit(
        int $width,
        int $height,
        int $minWidth,
        int $minHeight,
        int $maxWidth,
        int $maxHeight
    ) {
        $heightWidthRatio = $height / $width;
        $widthHeightRatio = $width / $height;

        $size = [
            'width'      => $minWidth,
            'height'     => $minWidth * $heightWidthRatio,
            'cropX'      => 0,
            'cropY'      => 0,
            'cropWidth'  => 0,
            'cropHeight' => 0,
            'cropNeeded' => false
        ];

        if($minHeight != 0 && $size['height'] < $minHeight) {
            $size['width']  = $minHeight * $widthHeightRatio;
            $size['height'] = $minHeight;

            if($maxWidth !== 0 && $size['width'] > $maxWidth) {
                $size['cropX']      = ($size['width'] - $maxWidth) / 2;
                $size['cropWidth']  = $maxWidth;
                $size['cropNeeded'] = true;
            }
        } else if($maxHeight != 0 && $size['height'] > $maxHeight) {
            $size['width'] = $minHeight * $widthHeightRatio;

            if($maxWidth !== 0 && $size['width'] > $maxWidth) {
                $size['width']      = $maxWidth;
                $size['height']     = $maxWidth * $heightWidthRatio;
                $size['cropHeight'] = $maxHeight;
                $size['cropNeeded'] = true;
            } else if($size['width'] < $minWidth) {
                $size['width']      = $minWidth;
                $size['height']     = $minWidth * $heightWidthRatio;
                $size['cropHeight'] = $maxHeight;
                $size['cropNeeded'] = true;
            }
        }

        return $size;
    }

    /**
     * @param     $image
     * @param int $minWidth
     * @param int $minHeight
     * @param int $maxWidth
     * @param int $maxHeight
     *
     * @return mixed
     */
    abstract public function advancedResizeImage($image, int $minWidth, int $minHeight, int $maxWidth, int $maxHeight);

    /**
     * @param     $image
     * @param int $size
     *
     * @return mixed
     */
    abstract public function simpleResizeImage($image, int $size);

    /**
     * @param $imageBlob
     *
     * @return mixed
     */
    abstract public function getImageFromBlob($imageBlob);

    /**
     * @param $image
     *
     * @return bool
     */
    abstract public function destroyImage($image): bool;

    /**
     * @param $image
     *
     * @return mixed
     */
    abstract public function exportJpeg($image);

    /**
     * @param $image
     *
     * @return mixed
     */
    abstract public function exportPng($image);

    /**
     * @param $blob
     *
     * @return bool
     */
    abstract public function supportsImage($blob): bool;

    /**
     * @param $data
     *
     * @return mixed
     */
    abstract public function convertIcoToPng($data);

}