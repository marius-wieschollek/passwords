<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.09.17
 * Time: 20:03
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Helper\Image\AbstractImageHelper;
use OCA\Passwords\Helper\PageShot\AbstractPageShotHelper;
use OCA\Passwords\Helper\PageShot\DefaultHelper;
use OCA\Passwords\Helper\PageShot\ScreenShotApiHelper;
use OCA\Passwords\Helper\PageShot\ScreenShotLayerHelper;
use OCA\Passwords\Helper\PageShot\ScreenShotMachineHelper;
use OCA\Passwords\Helper\PageShot\WkhtmlImageHelper;
use OCP\Files\SimpleFS\ISimpleFile;

/**
 * Class PageShotService
 *
 * @package OCA\Passwords\Services
 */
class PageShotService {

    const VIEWPORT_DESKTOP = 'desktop';
    const VIEWPORT_MOBILE  = 'mobile';

    const SERVICE_SCREEN_SHOT_LAYER   = 'ssl';
    const SERVICE_SCREEN_SHOT_MACHINE = 'ssm';
    const SERVICE_SCREEN_SHOT_API     = 'ssa';
    const SERVICE_WKHTML              = 'wkhtml';
    const SERVICE_DEFAULT             = 'default';

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var AbstractImageHelper
     */
    protected $imageHelper;

    /**
     * FaviconService constructor.
     *
     * @param ImageService         $imageService
     * @param ConfigurationService $config
     * @param FileCacheService     $fileCacheService
     */
    public function __construct(ImageService $imageService, ConfigurationService $config, FileCacheService $fileCacheService) {
        $fileCacheService->setDefaultCache($fileCacheService::PAGESHOT_CACHE);
        $this->fileCacheService = $fileCacheService;
        $this->config           = $config;
        $this->imageHelper      = $imageService->getImageHelper();
    }

    /**
     * @param string $domain
     * @param string $view
     * @param int    $minWidth
     * @param int    $minHeight
     *
     * @param int    $maxWidth
     * @param int    $maxHeight
     *
     * @return ISimpleFile
     */
    public function getPreview(
        string $domain,
        string $view = self::VIEWPORT_DESKTOP,
        int $minWidth = 550,
        int $minHeight = 0,
        int $maxWidth = 550,
        int $maxHeight = 0
    ) {
        list($domain, $minWidth, $minHeight, $maxWidth, $maxHeight) = $this->validateInput($domain, $minWidth, $minHeight,
            $maxWidth, $maxHeight);

        $pageShotService = $this->getPageShotService();
        $fileName        = $pageShotService->getPageShotFilename($domain, $view, $minWidth, $minHeight, $maxWidth, $maxHeight);
        if($this->fileCacheService->hasFile($fileName)) {
            return $this->fileCacheService->getFile($fileName);
        }

        if(!preg_match("/^([\w_-]+\.){1,}\w+$/", $domain)) {
            $pageShot = $pageShotService->getDefaultPageShot();
        } else {
            $pageShot = $pageShotService->getPageShot($domain, $view);
        }

        $image     = $this->imageHelper->getImageFromBlob($pageShot->getContent());
        $image     = $this->imageHelper->advancedResizeImage($image, $minWidth, $minHeight, $maxWidth, $maxHeight);
        $imageData = $this->imageHelper->exportJpeg($image);
        $this->imageHelper->destroyImage($image);

        if($imageData === null) $imageData = $pageShot->getContent();

        return $this->fileCacheService->putFile($fileName, $imageData);
    }

    /**
     * @return AbstractPageShotHelper
     */
    protected function getPageShotService(): AbstractPageShotHelper {
        $service = $this->config->getAppValue('service/pageshot', self::SERVICE_WKHTML);

        switch ($service) {
            case self::SERVICE_WKHTML:
                return new WkhtmlImageHelper($this->fileCacheService);
            case self::SERVICE_SCREEN_SHOT_API:
                return new ScreenShotApiHelper($this->fileCacheService, $this->config);
            case self::SERVICE_SCREEN_SHOT_LAYER:
                return new ScreenShotLayerHelper($this->fileCacheService, $this->config);
            case self::SERVICE_SCREEN_SHOT_MACHINE:
                return new ScreenShotMachineHelper($this->fileCacheService, $this->config);
            case self::SERVICE_DEFAULT:
                return new DefaultHelper($this->fileCacheService);
        }

        return new DefaultHelper($this->fileCacheService);
    }

    /**
     * @param string $domain
     * @param int    $minWidth
     * @param int    $minHeight
     * @param int    $maxWidth
     * @param int    $maxHeight
     *
     * @return array
     */
    protected function validateInput(string $domain, int $minWidth, int $minHeight, int $maxWidth, int $maxHeight): array {
        if(filter_var($domain, FILTER_VALIDATE_URL)) $domain = parse_url($domain, PHP_URL_HOST);

        $minWidth  = round($minWidth, -1);
        $maxWidth  = round($maxWidth, -1);
        $minHeight = round($minHeight, -1);
        $maxHeight = round($maxHeight, -1);

        if($minWidth > 720) $minWidth = 720;
        if($minWidth < 240 && $minWidth != 0) $minWidth = 240;
        if($maxWidth < $minWidth && $maxWidth != 0) $maxWidth = $minWidth;
        if($maxWidth > 720) $maxWidth = 720;
        if($maxWidth < 240 && $maxWidth != 0) $maxWidth = 240;

        if($minHeight > 1280) $minHeight = 1280;
        if($minHeight < 240 && $minHeight != 0) $minHeight = 240;
        if($maxHeight < $minHeight && $maxHeight != 0) $maxHeight = $minHeight;
        if($maxHeight > 1280) $maxHeight = 1280;
        if($maxHeight < 240 && $maxHeight != 0) $maxHeight = 240;

        return [$domain, $minWidth, $minHeight, $maxWidth, $maxHeight];
    }
}