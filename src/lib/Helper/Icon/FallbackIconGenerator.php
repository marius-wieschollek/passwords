<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.01.18
 * Time: 20:53
 */

namespace OCA\Passwords\Helper\Icon;

use OCA\Passwords\Helper\Image\AbstractImageHelper;
use OCA\Passwords\Helper\Image\GdHelper;
use OCA\Passwords\Services\ConfigurationService;

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
    protected $configurationService;

    /**
     * AvatarService constructor.
     *
     * @param AbstractImageHelper $imageHelper
     */
    public function __construct(AbstractImageHelper $imageHelper, ConfigurationService $configurationService) {
        $this->imageHelper          = $imageHelper;
        $this->configurationService = $configurationService;
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
        $char  = strtoupper($text[0]);

        if(get_class($this->imageHelper) === GdHelper::class) {
            return $this->createDefaultAvatarWithGd($color, $char, $size);
        }

        return $this->createDefaultSvgAvatar($color, $char, $size);
    }

    /**
     * @param string $color
     * @param string $text
     * @param int    $realSize
     *
     * @return string
     */
    protected function createDefaultAvatarWithGd(string $color, string $text, int $realSize): string {
        $rgb = str_split(substr($color, 1), 2);

        $size   = $realSize > 48 ? 48:$realSize;
        $size   = $size < 24 ? 24:$size;
        $center = round($size / 2);

        $image = imagecreatetruecolor($size, $size);
        imageantialias($image, true);

        $circleWidth = round($size * 0.9);
        $bgColor     = imagecolorallocate($image, hexdec($rgb[0]), hexdec($rgb[1]), hexdec($rgb[2]));
        imagefill($image, 0, 0, $bgColor);

        $fgColor = imagecolorallocate($image, 255, 255, 255);
        $fontX   = $center - round(imagefontwidth(5) / 2.5);
        $fontY   = $center - round(imagefontheight(5) / 2);
        imagestring($image, 5, $fontX, $fontY, $text, $fgColor);

        if($realSize !== $size) {
            $im = new \OC_Image($image);
            $im->resize($realSize);
            $image = $im->resource();
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
    protected function createDefaultSvgAvatar(string $color, string $text, int $size): string {
        $svg = file_get_contents(dirname(dirname(dirname(__DIR__))).'/img/default.svg');

        $svg = str_replace('#000', $color, $svg);
        $svg = str_replace('#TXT', $text, $svg);

        $tempFile = $this->configurationService->getTempDir().uniqid().'.svg';

        try {
            file_put_contents($tempFile, $svg);
            $image = $this->imageHelper->getImageFromFile($tempFile);
            $image = $this->imageHelper->simpleResizeImage($image, $size);
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
    protected function stringToColor(string $string): string {
        $max    = count($this->colors);
        $number = array_sum(str_split(dechex(crc32($string)), 2));

        while($number >= $max) {
            $number -= $max;
        }

        return '#'.$this->colors[ $number ];
    }
}