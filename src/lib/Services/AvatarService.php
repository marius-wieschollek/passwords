<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 06.01.18
 * Time: 20:34
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Image\AbstractImageHelper;
use OCA\Passwords\Helper\Image\GdHelper;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IImage;
use OCP\IUserManager;

/**
 * Class AvatarService
 *
 * @package OCA\Passwords\Services
 */
class AvatarService {

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
     * @var IUserManager
     */
    protected $userManager;

    /**
     * @var AbstractImageHelper
     */
    protected $imageHelper;

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * AvatarService constructor.
     *
     * @param IUserManager         $userManager
     * @param AbstractImageHelper  $imageHelper
     * @param FileCacheService     $fileCacheService
     * @param ConfigurationService $configurationService
     */
    public function __construct(
        IUserManager $userManager,
        AbstractImageHelper $imageHelper,
        FileCacheService $fileCacheService,
        ConfigurationService $configurationService
    ) {
        $fileCacheService->setDefaultCache($fileCacheService::AVATAR_CACHE);
        $this->userManager          = $userManager;
        $this->imageHelper          = $imageHelper;
        $this->fileCacheService     = $fileCacheService;
        $this->configurationService = $configurationService;
    }

    /**
     * @param string $userId
     * @param int    $size
     *
     * @return null|\OCP\Files\SimpleFS\ISimpleFile
     * @throws \Throwable
     */
    public function getAvatar(string $userId, int $size = 32): ?ISimpleFile {
        $size = $this->validateSize($size);

        $fileName = "{$userId}_{$size}.png";
        if($this->fileCacheService->hasFile($fileName)) {
            return $this->fileCacheService->getFile($fileName);
        }

        $user = $this->userManager->get($userId);
        if($user !== null) {
            $image = $user->getAvatarImage($size);
            if($image === null || !$image->valid()) {
                $imageData = $this->getImage($image, $user->getDisplayName(), $size);
            } else {
                $imageData = $this->getDefaultAvatar($user->getDisplayName(), $size);
            }
        } else {
            $imageData = $this->getDefaultAvatar($user->getDisplayName(), $size);
        }

        return $this->fileCacheService->putFile($fileName, $imageData);
    }

    /**
     * @param IImage $image
     * @param string $name
     *
     * @param int    $size
     *
     * @return string
     * @throws \Throwable
     */
    protected function getImage(IImage $image, string $name, int $size): string {
        ob_start();
        $a    = $image->resize($size);
        $b    = imagepng($image->resource());
        $data = ob_get_clean();

        if(!$a || !$b) return $this->getDefaultAvatar($name, $size);

        return $data;
    }

    /**
     * @param string $name
     * @param int    $size
     *
     * @return string
     * @throws \Throwable
     */
    protected function getDefaultAvatar(string $name, int $size): string {
        $color = $this->stringToColor($name);
        $text  = strtoupper($name[0]);

        if(get_class($this->imageHelper) === GdHelper::class) {
            return $this->createDefaultAvatarWithGd($color, $text, $size);
        }

        return $this->createDefaultSvgAvatar($color, $text, $size);
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
        $svg = file_get_contents(dirname(dirname(__DIR__)).'/img/Avatar.svg');

        $svg = str_replace('#000', $color, $svg);
        $svg = str_replace('#TXT', $text, $svg);

        $tempFile = $this->configurationService->getTempDir().uniqid().'.svg';

        try {
            file_put_contents($tempFile, $svg);
            $image = $this->imageHelper->getImageFromFile($tempFile);
            $image = $this->imageHelper->simpleResizeImage($image, $size);
            unlink($tempFile);
        } catch (\Throwable $e) {
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
        $number = array_sum(str_split(dechex(md5($string)), 2));

        while ($number >= $max) {
            $number -= $max;
        }

        return '#'.$this->colors[ $number ];
    }

    /**
     * @param int $size
     *
     * @return float|int
     */
    protected function validateSize(int $size): int {
        $size = round($size / 8) * 8;
        if($size < 16) {
            $size = 16;
        } else if($size > 256) {
            $size = 256;
        }

        return $size;
    }
}