<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 04.09.17
 * Time: 20:27
 */

namespace OCA\Passwords\Services;

use Gmagick;
use Imagick;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Image;

/**
 * Class FaviconService
 *
 * @package OCA\Passwords\Services
 */
class FaviconService {

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * FaviconService constructor.
     *
     * @param FileCacheService $fileCacheService
     */
    public function __construct(FileCacheService $fileCacheService) {
        $fileCacheService->setDefaultCache($fileCacheService::FAVICON_CACHE);
        $this->fileCacheService = $fileCacheService;
    }

    /**
     * @param string $domain
     * @param int    $size
     *
     * @return ISimpleFile
     */
    public function getFavicon(string $domain, int $size = 24) {
        if(filter_var($domain, FILTER_VALIDATE_URL)) $domain = parse_url($domain, PHP_URL_HOST);
        if($size > 128) $size = 128;
        if($size < 16) $size = 16;

        if(!preg_match("/^([\w_-]+\.){1,}\w+$/", $domain)) return $this->getDefaultImage($size);

        $url      = $this->getServiceUrl($domain);
        $fileName = md5($url).'_'.$size.'.png';

        if($this->fileCacheService->hasFile($fileName)) {
            return $this->fileCacheService->getFile($fileName);
        }

        if(class_exists(Imagick::class) || class_exists(Gmagick::class)) {
            $imageData = $this->resizeImageMagickImage($url, $size);
        } else {
            $imageData = file_get_contents($url);
        }

        if(empty($imageData)) return $this->getDefaultImage($size);

        return $this->fileCacheService->putFile($fileName, $imageData);
    }

    /**
     * @param string $url
     * @param int    $size
     *
     * @return null
     */
    protected function resizeImageMagickImage(string $url, int $size) {
        try {
            $fileHandle = fopen($url, 'r');
            if(empty($fileHandle)) return null;
            $image = class_exists(Imagick::class) ? new Imagick():new Gmagick();
            $image->readImageFile($fileHandle);
            $image->resizeImage($size, $size, 0, 0);
            $image->stripImage();
            $image->setImageFormat('png');

            return $image->getImageBlob();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param int $size
     *
     * @return ISimpleFile
     */
    protected function getDefaultImage(int $size): ISimpleFile {
        $fileName = "default_{$size}.png";
        if($this->fileCacheService->hasFile($fileName)) {
            return $this->fileCacheService->getFile($fileName);
        }

        $sourceFile = dirname(dirname(__DIR__)).'/img/app_black.png';
        $image      = new Image($sourceFile);
        $image->resize($size);

        return $this->fileCacheService->putFile($fileName, $image->data());
    }

    /**
     * @param string $domain
     *
     * @return string
     */
    protected function getServiceUrl(string $domain): string {
        return 'https://icons.better-idea.org/icon?size=32&url='.$domain;
    }
}
