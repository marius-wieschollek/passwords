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
use OCA\Passwords\Services\HelperService;
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
     * @param HelperService         $helperService
     * @param FileCacheService      $fileCacheService
     * @param FallbackIconGenerator $fallbackIconGenerator
     *
     * @throws \OCP\AppFramework\QueryException
     */
    public function __construct(
        HelperService $helperService,
        FileCacheService $fileCacheService,
        FallbackIconGenerator $fallbackIconGenerator
    ) {
        $this->imageHelper           = $helperService->getImageHelper();
        $this->fallbackIconGenerator = $fallbackIconGenerator;
        $this->fileCacheService      = $fileCacheService->getCacheService($fileCacheService::FAVICON_CACHE);
    }

    /**
     * @param string $domain
     *
     * @return ISimpleFile|null
     * @throws \Exception
     */
    public function getFavicon(string $domain): ?ISimpleFile {
        $faviconFile = $this->getFaviconFilename($domain);

        if($this->fileCacheService->hasFile($faviconFile)) {
            return $this->fileCacheService->getFile($faviconFile);
        }

        $faviconData = $this->getFaviconData($domain);
        if(empty($faviconData)) throw new \Exception('Favicon service returned no data');

        return $this->fileCacheService->putFile($faviconFile, $faviconData);
    }

    /**
     * @param string $domain
     * @param int    $size
     *
     * @return ISimpleFile|null
     * @throws \Throwable
     */
    public function getDefaultFavicon(string $domain, int $size = 256): ?ISimpleFile {
        $fileName = $this->getFaviconFilename($domain);
        if($this->fileCacheService->hasFile($fileName)) {
            return $this->fileCacheService->getFile($fileName);
        }

        $domain  = preg_replace('/^(m|de|www|www2|mail|email|login|signin)\./', '', $domain);
        $content = $this->fallbackIconGenerator->createIcon($domain, $size);

        return $this->fileCacheService->putFile($fileName, $content);
    }

    /**
     * @param string $domain
     * @param int    $size
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
     * @return string|null
     */
    protected function getHttpRequest(string $url): ?string {
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