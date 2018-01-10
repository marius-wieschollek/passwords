<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 00:38
 */

namespace OCA\Passwords\Helper\Favicon;

use OCA\Passwords\Helper\Http\RequestHelper;
use OCA\Passwords\Helper\Icon\FallbackIconGenerator;
use OCA\Passwords\Helper\Image\AbstractImageHelper;
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
     * @var AbstractImageHelper
     */
    protected $imageHelper;

    /**
     * @var FallbackIconGenerator
     */
    protected $fallbackIconGenerator;

    /**
     * AbstractFaviconHelper constructor.
     *
     * @param AbstractImageHelper   $imageHelper
     * @param FileCacheService      $fileCacheService
     * @param FallbackIconGenerator $fallbackIconGenerator
     */
    public function __construct(
        AbstractImageHelper $imageHelper,
        FileCacheService $fileCacheService,
        FallbackIconGenerator $fallbackIconGenerator
    ) {
        $fileCacheService->setDefaultCache($fileCacheService::FAVICON_CACHE);
        $this->imageHelper           = $imageHelper;
        $this->fileCacheService      = $fileCacheService;
        $this->fallbackIconGenerator = $fallbackIconGenerator;
    }

    /**
     * @param string $domain
     * @param int    $size
     *
     * @return ISimpleFile|null
     * @throws \Exception
     */
    public function getFavicon(string $domain, int $size): ?ISimpleFile {
        $faviconFile = $this->getFaviconFilename($domain, $size);

        if($this->fileCacheService->hasFile($faviconFile)) {
            return $this->fileCacheService->getFile($faviconFile);
        }

        $faviconData = $this->getFaviconData($domain, $size);
        if($faviconData === null) throw new \Exception('Favicon service returned no data');

        return $this->fileCacheService->putFile($faviconFile, $faviconData);
    }

    /**
     * @param string $domain
     * @param int    $size
     *
     * @return ISimpleFile|null
     * @throws \Throwable
     */
    public function getDefaultFavicon(string $domain, int $size): ?ISimpleFile {
        $fileName = $this->getFaviconFilename($domain, $size);
        if($this->fileCacheService->hasFile($fileName)) {
            return $this->fileCacheService->getFile($fileName);
        }

        $domain = preg_replace('/^(m|de|www|www2|mail|email|login|signin)\./', '' , $domain);
        $content = $this->fallbackIconGenerator->createIcon($domain, $size);

        return $this->fileCacheService->putFile($fileName, $content);
    }

    /**
     * @param string   $domain
     * @param int $size
     *
     * @return string
     */
    public function getFaviconFilename(string $domain, int $size): string {
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
        $request = new RequestHelper();
        $request->setUrl($url);

        return $request->sendWithRetry();
    }

    /**
     * @param string $domain
     * @param int    $size
     *
     * @return null|string
     */
    protected function getFaviconData(string $domain, int $size): ?string {
        $url = $this->getFaviconUrl($domain, $size);

        return $this->getHttpRequest($url);
    }

    /**
     * @param string $domain
     * @param int    $size
     *
     * @return string
     */
    protected function getFaviconUrl(string $domain, int $size): string {
        return "http://{$domain}/favicon.ico";
    }
}