<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 00:38
 */

namespace OCA\Passwords\Helper\Favicon;

use OCA\Passwords\Helper\Http\RequestHelper;
use OCA\Passwords\Helper\Image\AbstractImageHelper;
use OCA\Passwords\Services\FileCacheService;
use OCA\Theming\ThemingDefaults;
use OCP\Files\SimpleFS\ISimpleFile;

/**
 * Class AbstractFaviconHelper
 *
 * @package OCA\Passwords\Helper
 */
abstract class AbstractFaviconHelper {

    /**
     * @var string
     */
    protected $prefix = 'af';

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * @var AbstractImageHelper
     */
    protected $imageHelper;

    /**
     * @var ThemingDefaults
     */
    protected $themingDefaults;

    /**
     * BetterIdeaHelper constructor.
     *
     * @param FileCacheService    $fileCacheService
     * @param AbstractImageHelper $imageHelper
     * @param \OC_Defaults        $themingDefaults
     */
    public function __construct(
        FileCacheService $fileCacheService,
        AbstractImageHelper $imageHelper,
        \OC_Defaults $themingDefaults
    ) {
        $fileCacheService->setDefaultCache($fileCacheService::FAVICON_CACHE);
        $this->fileCacheService = $fileCacheService;
        $this->imageHelper      = $imageHelper;
        $this->themingDefaults  = $themingDefaults;
    }

    /**
     * @param string $domain
     *
     * @return ISimpleFile
     * @throws \Exception
     */
    public function getFavicon(string $domain): ISimpleFile {
        $faviconFile = $this->getFaviconFilename($domain);

        if($this->fileCacheService->hasFile($faviconFile)) {
            return $this->fileCacheService->getFile($faviconFile);
        }

        $faviconData = $this->getFaviconData($domain);
        if($faviconData === null) throw new \Exception('Favicon service returned no data');

        return $this->fileCacheService->putFile($faviconFile, $faviconData);
    }

    /**
     * @return ISimpleFile
     */
    public function getDefaultFavicon(): ISimpleFile {
        $fileName = "{$this->prefix}_default.png";
        if($this->fileCacheService->hasFile($fileName)) {
            return $this->fileCacheService->getFile($fileName);
        }

        $content = $this->recolorDefaultFavicon();

        return $this->fileCacheService->putFile($fileName, $content);
    }

    /**
     * @param string   $domain
     * @param int|null $size
     *
     * @return string
     */
    public function getFaviconFilename(string $domain, int $size = null): string {
        if($size !== null) {
            return "{$this->prefix}_{$domain}_{$size}.png";
        }

        return "{$this->prefix}_{$domain}.png";
    }

    /**
     * @return mixed
     */
    protected function recolorDefaultFavicon() {
        $path  = dirname(dirname(dirname(__DIR__))).'/img/app_black.png';
        $image = $this->imageHelper->getImageFromFile($path);
        $image = $this->imageHelper->recolorImage($image, '#000000', $this->themingDefaults->getColorPrimary());

        $content = $this->imageHelper->exportPng($image);
        $this->imageHelper->destroyImage($image);

        return $content;
    }

    /**
     * @param string $url
     *
     * @return mixed
     */
    protected function getHttpRequest(string $url) {
        $request = new RequestHelper();
        $request->setUrl($url);

        return $request->sendWithRetry();
    }

    /**
     * @param string $domain
     *
     * @return null|string
     */
    protected function getFaviconData(string $domain): ?string {
        $url = $this->getFaviconUrl($domain);

        return $this->getHttpRequest($url);
    }

    /**
     * @param string $domain
     *
     * @return string
     */
    protected function getFaviconUrl(string $domain): string {
        return "http://{$domain}/favicon.ico";
    }
}