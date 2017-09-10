<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.09.17
 * Time: 20:03
 */

namespace OCA\Passwords\Services;

use Gmagick;
use Imagick;
use OCA\Passwords\Helper\PageShot\AbstractPageShotHelper;
use OCA\Passwords\Helper\PageShot\DefaultHelper;
use OCA\Passwords\Helper\PageShot\ScreenshotLayerHelper;
use OCA\Passwords\Helper\PageShot\WkhtmlImageHelper;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Image;

/**
 * Class PreviewService
 *
 * @package OCA\Passwords\Services
 */
class PreviewService {

    const VIEWPORT_DESKTOP = 'desktop';
    const VIEWPORT_MOBILE  = 'mobile';

    const SERVICE_SCREEN_SHOT_LAYER = 'sl';
    const SERVICE_WKHTML            = 'wkhtml';
    const SERVICE_DEFAULT           = 'default';

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
        $fileCacheService->setDefaultCache($fileCacheService::PAGESHOT_CACHE);
        $this->fileCacheService = $fileCacheService;
        $this->config           = $config;
    }

    /**
     * @param string $domain
     * @param string $view
     * @param int    $width
     * @param int    $height
     *
     * @return ISimpleFile
     */
    public function getPreview(string $domain, string $view = self::VIEWPORT_DESKTOP, int $width = 550, int $height = 0) {
        list($domain, $width, $height) = $this->validateInput($domain, $width, $height);

        $pageShotService = $this->getPageShotService();
        $fileName        = $pageShotService->getPageShotFilename($domain, $view, $width, $height);
        if($this->fileCacheService->hasFile($fileName)) {
            return $this->fileCacheService->getFile($fileName);
        }

        if(!preg_match("/^([\w_-]+\.){1,}\w+$/", $domain)) {
            $pageShot = $pageShotService->getDefaultPageShot();
        } else {
            $pageShot = $pageShotService->getPageShot($domain, $view);
        }

        if(class_exists(Imagick::class) || class_exists(Gmagick::class)) {
            $imageData = $this->resizeWithImageMagick($pageShot, $width, $height);
        } else {
            $imageData = $this->resizeWithNextcloud($pageShot, $width, $height);
        }

        if($imageData === null) $imageData = $pageShot->getContent();

        return $this->fileCacheService->putFile($fileName, $imageData);
    }

    /**
     * @param ISimpleFile $file
     * @param int         $width
     * @param int         $height
     *
     * @return null|string
     */
    protected function resizeWithImageMagick(ISimpleFile $file, int $width, int $height) {
        try {
            $image = class_exists(Imagick::class) ? new Imagick():new Gmagick();
            $image->readImageBlob($file->getContent());

            $size        = $image->getSize();
            $scaleHeight = $width * ($size['rows'] / $size['colums']);
            $image->resizeImage($width, $scaleHeight, 0, 0);

            $size = $image->getSize();
            if($height != 0 && $height < $size['rows']) {
                $image->cropImage($width, $height, 0, 0);
            }

            $image->stripImage();
            $image->setImageFormat('jpg');
            $image->setImageCompressionQuality(90);

            return $image->getImageBlob();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param ISimpleFile $pageShot
     * @param int         $width
     * @param int         $height
     *
     * @return null|string
     */
    protected function resizeWithNextcloud(ISimpleFile $pageShot, int $width, int $height) {
        try {
            $image = new Image($pageShot->getContent());
            $image->fitIn($width, $image->height());

            if($height != 0 && $height < $image->height()) {
                $image->crop(0, 0, $width, $height);
            }

            return $image->data();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return AbstractPageShotHelper
     * @TODO support gnome-web-photo service
     */
    protected function getPageShotService(): AbstractPageShotHelper {
        switch ($this->config->getUserValue('service/pageshot', self::SERVICE_WKHTML)) {
            case self::SERVICE_WKHTML:
                return new WkhtmlImageHelper($this->fileCacheService);
            case self::SERVICE_SCREEN_SHOT_LAYER:
                return new ScreenshotLayerHelper($this->fileCacheService, $this->config);
            case self::SERVICE_DEFAULT:
                return new DefaultHelper($this->fileCacheService);
        }

        return new DefaultHelper($this->fileCacheService);
    }

    /**
     * @param string $domain
     * @param int    $width
     * @param int    $height
     *
     * @return array
     */
    protected function validateInput(string $domain, int $width, int $height): array {
        if(filter_var($domain, FILTER_VALIDATE_URL)) $domain = parse_url($domain, PHP_URL_HOST);
        if($width > 720) {
            $width = 720;
        } else if($width < 240) {
            $width = 240;
        }
        if($height > 1280) {
            $height = 1280;
        } else if($height < 240 && $height != 0) {
            $height = 240;
        }

        return [$domain, $width, $height];
    }
}