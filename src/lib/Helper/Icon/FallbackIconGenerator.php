<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Icon;

use OCA\Passwords\Helper\Image\AbstractImageHelper;
use OCA\Passwords\Helper\Image\GdHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\HelperService;

/**
 * Class FallbackIconGenerator
 *
 * @package OCA\Passwords\Helper\Icon
 */
class FallbackIconGenerator {

    /**
     * @var array
     */
    protected $colors
        = [
            '1abc9c',
            '16a085',
            '2ecc71',
            '27ae60',
            '3498db',
            '2980b9',
            '9b59b6',
            '8e44ad',
            'f1c40f',
            'f39c12',
            'e67e22',
            'd35400',
            'e74c3c',
            'c0392b'
        ];

    /**
     * @var AbstractImageHelper
     */
    protected $imageHelper;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * AvatarService constructor.
     *
     * @param HelperService        $helperService
     * @param ConfigurationService $config
     *
     * @throws \OCP\AppFramework\QueryException
     */
    public function __construct(HelperService $helperService, ConfigurationService $config) {
        $this->imageHelper = $helperService->getImageHelper();
        $this->config      = $config;
    }

    /**
     * @param string $text
     * @param int    $size
     *
     * @return string
     * @throws \Throwable
     */
    public function createIcon(string $text, int $size): string {
        $color = $this->stringToColor($text);
        $char  = isset($text[0]) ? strtoupper($text[0]):'';

        if(get_class($this->imageHelper) === GdHelper::class || !$this->imageHelper->supportsFormat('svg')) {
            return $this->createIconWithGd($color, $char, $size);
        }

        return $this->createIconFromSvg($color, $char, $size);
    }

    /**
     * @param string $color
     * @param string $text
     * @param int    $realSize
     *
     * @return string
     */
    protected function createIconWithGd(string $color, string $text, int $realSize): string {
        $rgb = str_split(substr($color, 1), 2);

        if(!$this->hasFreeTypeSupport()) {
            $image = $this->createGdIconNoFreeType($text, $realSize, $rgb);
        } else {
            $image = $this->createGdIconFreeType($text, $realSize, $rgb);
        }

        ob_start();
        imagepng($image);

        return ob_get_clean();
    }

    /**
     * @param string $color
     * @param string $text
     * @param int    $size
     *
     * @return string
     * @throws \Throwable
     */
    protected function createIconFromSvg(string $color, string $text, int $size): string {
        $svg = file_get_contents(__DIR__.'/../../../img/default.svg');

        $svg = str_replace('#000', $color, $svg);
        $svg = str_replace('#TXT', $text, $svg);

        $tempFile = $this->config->getTempDir().uniqid().'.svg';

        try {
            file_put_contents($tempFile, $svg);
            $image = $this->imageHelper->getImageFromFile($tempFile);
            $image = $this->imageHelper->simpleResizeImage($image, $size);
            $image->setImageDepth(8);
            unlink($tempFile);
        } catch(\Throwable $e) {
            if(is_file($tempFile)) @unlink($tempFile);
            throw $e;
        }

        return $this->imageHelper->exportPng($image);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function stringToColor(string $string): string {
        $max    = count($this->colors);
        $number = array_sum(str_split(dechex(crc32($string)), 2));

        while($number >= $max) {
            $number -= $max;
        }

        return '#'.$this->colors[ $number ];
    }

    /**
     * @return bool
     */
    protected function hasFreeTypeSupport(): bool {
        return function_exists('imagettfbbox') && function_exists('imagettftext');
    }

    /**
     * @param string $text
     * @param int    $realSize
     * @param array  $rgb
     *
     * @return resource
     */
    protected function createGdIconNoFreeType(string $text, int $realSize, array $rgb) {
        $image   = imagecreatetruecolor(24, 24);
        $bgColor = imagecolorallocate($image, hexdec($rgb[0]), hexdec($rgb[1]), hexdec($rgb[2]));
        imagefill($image, 0, 0, $bgColor);

        $fgColor = imagecolorallocate($image, 255, 255, 255);
        $fontX   = 12 - round(imagefontwidth(5) / 2.5);
        $fontY   = 12 - round(imagefontheight(5) / 2);
        imagestring($image, 5, $fontX, $fontY, $text, $fgColor);

        if($realSize !== 24) {
            $tempImage = new \OC_Image($image);
            $tempImage->resize($realSize);
            $image = $tempImage->resource();
        }

        return $image;
    }

    /**
     * @param string $text
     * @param int    $realSize
     * @param array  $rgb
     *
     * @return resource
     */
    protected function createGdIconFreeType(string $text, int $realSize, array $rgb) {
        $center   = round($realSize / 2);
        $fontSize = round($realSize * 0.50);

        $image   = imagecreatetruecolor($realSize, $realSize);
        $bgColor = imagecolorallocate($image, hexdec($rgb[0]), hexdec($rgb[1]), hexdec($rgb[2]));
        imagefill($image, 0, 0, $bgColor);

        $fontFile = $this->imageHelper->getDefaultFont();
        $fontBox  = imagettfbbox($fontSize, 0, $fontFile, $text);
        $fontY    = $center + (abs($fontBox[7])) / 2;
        $fontX    = $center - (abs($fontBox[2]) + $realSize * 0.036) / 2;

        $fgColor = imagecolorallocate($image, 255, 255, 255);
        imagettftext($image, $fontSize, 0, $fontX, $fontY, $fgColor, $fontFile, $text);

        return $image;
    }
}