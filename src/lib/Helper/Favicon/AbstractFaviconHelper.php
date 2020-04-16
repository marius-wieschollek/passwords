<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
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
     * @var RequestHelper
     */
    protected $httpRequest;

    /**
     * @var AbstractImageHelper
     */
    protected $imageHelper;

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * @var FallbackIconGenerator
     */
    protected $fallbackIconGenerator;

    /**
     * AbstractFaviconHelper constructor.
     *
     * @param RequestHelper         $httpRequest
     * @param HelperService         $helperService
     * @param FileCacheService      $fileCacheService
     * @param FallbackIconGenerator $fallbackIconGenerator
     *
     * @throws \OCP\AppFramework\QueryException
     */
    public function __construct(
        RequestHelper $httpRequest,
        HelperService $helperService,
        FileCacheService $fileCacheService,
        FallbackIconGenerator $fallbackIconGenerator
    ) {
        $this->httpRequest           = $httpRequest;
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
        if(!$this->imageHelper->supportsImage($faviconData)) {
            $mime = $this->imageHelper->getImageMime($faviconData);
            throw new \Exception('Favicon service returned unsupported data type: '.$mime);
        }

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
        $fileName = $this->getFaviconFilename($domain.'_default');
        if($this->fileCacheService->hasFile($fileName)) {
            return $this->fileCacheService->getFile($fileName);
        }

        $domain  = preg_replace('/^(m|de|web|www|www2|mail|email|login|signin)\./', '', $domain);
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
        $this->httpRequest->setUrl($url);

        return $this->httpRequest->sendWithRetry();
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