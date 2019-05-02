<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Preview;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Http\RequestHelper;
use OCA\Passwords\Helper\Image\AbstractImageHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\LoggingService;
use OCP\Files\SimpleFS\ISimpleFile;

/**
 * Class AbstractPreviewHelper
 *
 * @package OCA\Passwords\Helper\Preview
 */
abstract class AbstractPreviewHelper {

    const VIEWPORT_DESKTOP = '1366x768';
    const VIEWPORT_MOBILE  = '360x640';
    const WIDTH_DESKTOP    = 1366;
    const WIDTH_MOBILE     = 360;

    /**
     * @var string
     */
    protected $prefix = 'af';

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var AbstractImageHelper
     */
    protected $imageHelper;

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * BetterIdeaHelper constructor.
     *
     * @param HelperService        $helperService
     * @param ConfigurationService $config
     * @param FileCacheService     $fileCacheService
     * @param LoggingService       $loggingService
     *
     * @throws \OCP\AppFramework\QueryException
     */
    public function __construct(
        HelperService $helperService,
        ConfigurationService $config,
        FileCacheService $fileCacheService,
        LoggingService $loggingService
    ) {
        $this->config           = $config;
        $this->logger           = $loggingService;
        $this->imageHelper      = $helperService->getImageHelper();
        $this->fileCacheService = $fileCacheService->getCacheService($fileCacheService::PREVIEW_CACHE);
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return ISimpleFile|null
     * @throws \Exception
     */
    function getPreview(string $domain, string $view): ?ISimpleFile {
        $previewFile = $this->getPreviewFilename($domain, $view);

        if($this->fileCacheService->hasFile($previewFile)) {
            return $this->fileCacheService->getFile($previewFile);
        }

        $previewData = $this->getPreviewData($domain, $view);
        if(empty($previewData)) throw new \Exception('Website preview service returned no data');
        if(!$this->imageHelper->supportsImage($previewData)) throw new \Exception('Favicon service returned unsupported data type');

        return $this->fileCacheService->putFile($previewFile, $previewData);
    }

    /**
     * @param string $domain
     *
     * @return ISimpleFile|null
     */
    public function getDefaultPreview(string $domain): ?ISimpleFile {
        $number = array_sum(str_split(hexdec(crc32($domain)), 2)) % 5;

        $fileName = "{$this->prefix}_default_{$number}.jpg";
        if($this->fileCacheService->hasFile($fileName)) {
            return $this->fileCacheService->getFile($fileName);
        }

        $path    = __DIR__.'/../../../img/preview/preview_'.rand(1, 5).'.jpg';
        $content = file_get_contents($path);

        return $this->fileCacheService->putFile($fileName, $content);
    }

    /**
     * @param string   $domain
     * @param string   $view
     * @param int|null $minWidth
     * @param int|null $minHeight
     * @param int|null $maxWidth
     * @param int|null $maxHeight
     *
     * @return string
     */
    public function getPreviewFilename(
        string $domain,
        string $view,
        int $minWidth = null,
        int $minHeight = null,
        int $maxWidth = null,
        int $maxHeight = null
    ): string {
        if($minWidth !== null) {
            return "{$this->prefix}_{$domain}_{$view}_{$minWidth}x{$minHeight}_{$maxWidth}x{$maxHeight}.jpg";
        }

        return "{$this->prefix}_{$domain}_{$view}.jpg";
    }

    /**
     * @param string $url
     *
     * @return mixed
     * @throws ApiException
     */
    protected function executeHttpRequest(string $url): string {
        $request = $this->getHttpRequest($url);
        $data    = $request->sendWithRetry();

        $info = $request->getInfo();
        if(substr($info['content_type'], 0, 5) != 'image' || $info['http_code'] > 400) {
            $this->logger->error("Invalid Preview Api Response, HTTP {$info['http_code']}, {$info['content_type']}");
            throw new ApiException('API Request Failed', 502);
        }

        return $data;
    }

    /**
     * @param string $url
     *
     * @return RequestHelper
     */
    protected function getHttpRequest(string $url): RequestHelper {
        $request = new RequestHelper();
        $request->setUrl($url);

        return $request;
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     * @throws ApiException
     * @throws \Exception
     */
    protected function getPreviewData(string $domain, string $view): string {
        $url = $this->getPreviewUrl($domain, $view);

        return $this->executeHttpRequest($url);
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     * @throws \Exception
     */
    protected function getPreviewUrl(string $domain, string $view): string {
        throw new \Exception('No preview url defined for '.$domain.'@'.$view, 502);
    }
}