<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Image;

use OCA\Passwords\Services\ConfigurationService;

/**
 * Class AbstractImageHelper
 *
 * @package OCA\Passwords\Helper\Image
 */
abstract class AbstractImageHelper {

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * AbstractImageHelper constructor.
     *
     * @param ConfigurationService $configurationService
     */
    public function __construct(ConfigurationService $configurationService) {
        $this->config = $configurationService;
    }

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
                $size['cropHeight'] = $size['height'];
                $size['cropNeeded'] = true;
            }
        } else if($maxHeight != 0 && $size['height'] > $maxHeight) {
            $size['width'] = $minHeight * $widthHeightRatio;

            if($maxWidth !== 0 && $size['width'] > $maxWidth) {
                $size['width']      = $maxWidth;
                $size['height']     = $maxWidth * $heightWidthRatio;
                $size['cropWidth']  = $size['width'];
                $size['cropHeight'] = $maxHeight;
                $size['cropNeeded'] = true;
            } else if($size['width'] < $minWidth) {
                $size['width']      = $minWidth;
                $size['height']     = $minWidth * $heightWidthRatio;
                $size['cropWidth']  = $size['width'];
                $size['cropHeight'] = $maxHeight;
                $size['cropNeeded'] = true;
            }
        }

        if($size['width'] == 0 && $size['height'] == 0) {
            $size['width']  = $width;
            $size['height'] = $height;
        }

        return $size;
    }

    /**
     * @param $blob
     *
     * @return string
     */
    public function getImageMime($blob) {
        $size = getimagesizefromstring($blob);
        if(!$size || !isset($size['mime']) || empty($size['mime'])) return 'application/octet-stream';

        return $size['mime'];
    }

    /**
     * @param $blob
     *
     * @return bool
     */
    public function supportsImage($blob): bool {
        $mime = $this->getImageMime($blob);

        list($type, $format) = explode('/', $mime);
        if($type != 'image') return false;

        return $this->supportsFormat($format);
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
     * @param $image
     *
     * @return mixed
     */
    abstract public function cropImageRectangular($image);

    /**
     * @param $imageBlob
     *
     * @return mixed
     */
    abstract public function getImageFromBlob($imageBlob);

    /**
     * @param string $file
     *
     * @return mixed
     */
    abstract public function getImageFromFile(string $file);

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
     * @param string $format
     *
     * @return bool
     */
    abstract public function supportsFormat(string $format): bool;

    /**
     * @param $data
     *
     * @return mixed
     */
    abstract public function convertIcoToPng($data);

    /**
     * Whether or not this service can be used in the current environment
     *
     * @return bool
     */
    abstract public static function isAvailable(): bool;

    /**
     * @return string
     */
    public function getDefaultFont(): string {
        return \OC::$SERVERROOT.'/core/fonts/NotoSans-Regular.ttf';
    }
}