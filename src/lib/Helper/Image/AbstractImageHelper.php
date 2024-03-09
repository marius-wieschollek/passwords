<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Image;

use OC;
use OCA\Passwords\Exception\Image\ImageConversionException;
use OCA\Passwords\Exception\Image\ImageExportException;
use OCA\Passwords\Services\ConfigurationService;

/**
 * Class AbstractImageHelper
 *
 * @package OCA\Passwords\Helper\Image
 */
abstract class AbstractImageHelper {

    /**
     * AbstractImageHelper constructor.
     *
     * @param ConfigurationService $configurationService
     */
    public function __construct(protected ConfigurationService $config) {
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
        $heightWidthRatio = (int) $height / $width;
        $widthHeightRatio = (int) $width / $height;

        $size = [
            'width'      => (int) $minWidth,
            'height'     => (int) $minWidth * $heightWidthRatio,
            'cropX'      => 0,
            'cropY'      => 0,
            'cropWidth'  => 0,
            'cropHeight' => 0,
            'cropNeeded' => false
        ];

        if($minHeight !== 0 && $size['height'] < $minHeight) {
            $size['width']  = (int) $minHeight * $widthHeightRatio;
            $size['height'] = (int) $minHeight;

            if($maxWidth !== 0 && $size['width'] > $maxWidth) {
                $size['cropX']      = (int) ($size['width'] - $maxWidth) / 2;
                $size['cropWidth']  = (int) $maxWidth;
                $size['cropHeight'] = (int) $size['height'];
                $size['cropNeeded'] = true;
            }
        } else if($maxHeight !== 0 && $size['height'] > $maxHeight) {
            $size['width'] = (int) $minHeight * $widthHeightRatio;

            if($maxWidth !== 0 && $size['width'] > $maxWidth) {
                $size['width']      = (int) $maxWidth;
                $size['height']     = (int) $maxWidth * $heightWidthRatio;
                $size['cropWidth']  = (int) $size['width'];
                $size['cropHeight'] = (int) $maxHeight;
                $size['cropNeeded'] = true;
            } else if($size['width'] < $minWidth) {
                $size['width']      = (int) $minWidth;
                $size['height']     = (int) $minWidth * $heightWidthRatio;
                $size['cropWidth']  = (int) $size['width'];
                $size['cropHeight'] = (int) $maxHeight;
                $size['cropNeeded'] = true;
            }
        }

        if($size['width'] === 0 && $size['height'] === 0) {
            $size['width']  = (int) $width;
            $size['height'] = (int) $height;
        }

        return $size;
    }

    /**
     * @param $blob
     *
     * @return string
     */
    public function getImageMime($blob): string {
        $size = @getimagesizefromstring($blob);
        if(!$size || empty($size['mime'])) return 'application/octet-stream';

        return $size['mime'];
    }

    /**
     * @param $blob
     *
     * @return bool
     */
    public function supportsImage($blob): bool {
        $mime = $this->getImageMime($blob);

        [$type, $format] = explode('/', $mime);
        if($type !== 'image') return false;

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
     * @param $image
     *
     * @return bool
     */
    abstract public function destroyImage($image): bool;

    /**
     * @param $image
     *
     * @return string
     * @trows ImageExportException
     */
    abstract public function exportJpeg($image): string;

    /**
     * @param $image
     *
     * @return string
     * @trows ImageExportException
     */
    abstract public function exportPng($image): string;

    /**
     * @param string $format
     *
     * @return bool
     */
    abstract public function supportsFormat(string $format): bool;

    /**
     * @param $data
     *
     * @return string
     * @throws ImageConversionException
     */
    abstract public function convertIcoToPng($data): string;

    /**
     * Whether this service can be used in the current environment
     *
     * @return bool
     */
    abstract public function isAvailable(): bool;

    /**
     * @return string
     */
    public function getDefaultFont(): string {
        return OC::$SERVERROOT.'/core/fonts/NotoSans-Regular.ttf';
    }
}