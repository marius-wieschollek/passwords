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
use OCA\Passwords\Helper\Favicon\AbstractFaviconHelper;
use OCA\Passwords\Helper\Favicon\BetterIdeaHelper;
use OCA\Passwords\Helper\Favicon\DefaultHelper;
use OCA\Passwords\Helper\Favicon\DuckDuckGoHelper;
use OCA\Passwords\Helper\Favicon\GoogleHelper;
use OCA\Passwords\Helper\Favicon\LocalFaviconHelper;
use OCP\Files\SimpleFS\ISimpleFile;

/**
 * Class FaviconService
 *
 * @package OCA\Passwords\Services
 */
class FaviconService {

    const SERVICE_BETTER_IDEA  = 'bi';
    const SERVICE_DUCK_DUCK_GO = 'ddg';
    const SERVICE_GOOGLE       = 'gl';
    const SERVICE_LOCAL        = 'local';
    const SERVICE_DEFAULT      = 'default';

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * FaviconService constructor.
     *
     * @param ConfigurationService $config
     * @param FileCacheService     $fileCacheService
     */
    public function __construct(ConfigurationService $config, FileCacheService $fileCacheService) {
        $fileCacheService->setDefaultCache($fileCacheService::FAVICON_CACHE);
        $this->fileCacheService = $fileCacheService;
        $this->config           = $config;
    }

    /**
     * @param string $domain
     * @param int    $size
     *
     * @return ISimpleFile
     */
    public function getFavicon(string $domain, int $size = 24) {
        list($domain, $size) = $this->validateInput($domain, $size);

        $faviconService = $this->getFaviconService();
        $fileName       = $faviconService->getFaviconFilename($domain, $size);
        if($this->fileCacheService->hasFile($fileName)) {
            return $this->fileCacheService->getFile($fileName);
        }

        if(!preg_match("/^([\w_-]+\.){1,}\w+$/", $domain)) {
            $favicon = $faviconService->getDefaultFavicon();
        } else {
            $favicon = $faviconService->getFavicon($domain);
        }

        $faviconData = null;
        if(class_exists(Imagick::class) || class_exists(Gmagick::class)) {
            $faviconData = $this->resizeWithImageMagick($favicon, $size);
        }

        if($faviconData === null) {
            $faviconData = $favicon->getContent();
        }

        return $this->fileCacheService->putFile($fileName, $faviconData);
    }

    /**
     * @param ISimpleFile $file
     * @param int         $size
     *
     * @return null
     * @internal param string $url
     */
    protected function resizeWithImageMagick(ISimpleFile $file, int $size) {
        try {
            $image = class_exists(Imagick::class) ? new Imagick():new Gmagick();
            $image->readImageBlob($file->getContent(), $file->getName());
            if($image->getImageWidth() != $image->getImageHeight()) {
                $image = $this->cropImage($image);
            }
            $image->stripImage();
            $image->setImageFormat('png');
            $image->resizeImage($size, $size, $image::FILTER_LANCZOS, 1);
            $image->setImageCompressionQuality(9);

            return $image->getImageBlob();
        } catch (\Throwable $e) {
            \OC::$server->getLogger()->error($e->getMessage());
            return null;
        }
    }

    /**
     * @param Imagick $image
     *
     * @return Imagick
     */
    protected function cropImage($image) {
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
     * @param string $domain
     * @param int    $size
     *
     * @return array
     */
    protected function validateInput(string $domain, int $size): array {
        if(filter_var($domain, FILTER_VALIDATE_URL)) $domain = parse_url($domain, PHP_URL_HOST);
        if($size > 128) {
            $size = 128;
        } else if($size < 16) {
            $size = 16;
        }

        return [$domain, $size];
    }

    /**
     * @return AbstractFaviconHelper
     */
    protected function getFaviconService(): AbstractFaviconHelper {
        $service = $this->config->getAppValue('service/favicon', self::SERVICE_LOCAL);

        switch ($service) {
            case self::SERVICE_BETTER_IDEA:
                return new BetterIdeaHelper($this->fileCacheService);
            case self::SERVICE_DUCK_DUCK_GO:
                return new DuckDuckGoHelper($this->fileCacheService);
            case self::SERVICE_GOOGLE:
                return new GoogleHelper($this->fileCacheService);
            case self::SERVICE_LOCAL:
                return new LocalFaviconHelper($this->fileCacheService);
            case self::SERVICE_DEFAULT:
                return new DefaultHelper($this->fileCacheService);
        }

        return new DefaultHelper($this->fileCacheService);
    }
}
