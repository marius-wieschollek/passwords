<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 00:38
 */

namespace OCA\Passwords\Helper\Favicon;

use OCA\Passwords\Helper\HttpRequestHelper;
use OCA\Passwords\Services\FileCacheService;
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
     * BetterIdeaHelper constructor.
     *
     * @param FileCacheService $fileCacheService
     */
    public function __construct(FileCacheService $fileCacheService) {
        $this->fileCacheService = $fileCacheService;
    }

    /**
     * @param string $domain
     *
     * @return ISimpleFile
     */
    public function getFavicon(string $domain): ISimpleFile {
        $faviconFile = $this->getFaviconFilename($domain);

        if($this->fileCacheService->hasFile($faviconFile)) {
            return $this->fileCacheService->getFile($faviconFile);
        }

        $url         = $this->getFaviconUrl($domain);
        $faviconData = $this->getHttpRequest($url);

        if($faviconData === null) {
            return $this->getDefaultFavicon();
        }

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

        $path    = dirname(dirname(dirname(__DIR__))).'/img/app_black.png';
        $content = file_get_contents($path);

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
     * @param string $url
     *
     * @return mixed
     */
    protected function getHttpRequest(string $url) {
        $request = new HttpRequestHelper();
        $request->setUrl($url);

        return $request->sendWithRetry();
    }

    /**
     * @param string $domain
     *
     * @return string
     */
    abstract protected function getFaviconUrl(string $domain): string;
}