<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Provider\Preview;

use Exception;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Image\AbstractImageHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\LoggingService;
use OCP\AppFramework\Http;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Http\Client\IClientService;

/**
 * Class AbstractPreviewProvider
 *
 * @package OCA\Passwords\Helper\Preview
 */
abstract class AbstractPreviewProvider implements PreviewProviderInterface{

    const VIEWPORT_DESKTOP = '1366x768';
    const VIEWPORT_MOBILE  = '360x640';
    const WIDTH_DESKTOP    = 1366;
    const WIDTH_MOBILE     = 360;

    /**
     * @var string
     */
    protected string $prefix = 'af';


    /**
     * @var AbstractImageHelper
     */
    protected AbstractImageHelper $imageHelper;

    /**
     * @var FileCacheService
     */
    protected FileCacheService $fileCacheService;

    /**
     * AbstractPreviewProvider constructor.
     *
     * @param HelperService        $helperService
     * @param ConfigurationService $config
     * @param FileCacheService     $fileCacheService
     * @param IClientService       $httpClientService
     * @param LoggingService       $loggingService
     */
    public function __construct(
        HelperService $helperService,
        FileCacheService $fileCacheService,
        protected ConfigurationService $config,
        protected IClientService $httpClientService,
        protected LoggingService $loggingService
    ) {
        $this->imageHelper       = $helperService->getImageHelper();
        $this->fileCacheService  = $fileCacheService->getCacheService($fileCacheService::PREVIEW_CACHE);
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return ISimpleFile|null
     * @throws Exception
     */
    function getPreview(string $domain, string $view): ?ISimpleFile {
        $previewFile = $this->getPreviewFilename($domain, $view);

        if($this->fileCacheService->hasFile($previewFile)) {
            return $this->fileCacheService->getFile($previewFile);
        }

        $previewData = $this->getPreviewData($domain, $view);
        if(empty($previewData)) throw new Exception('Website preview service returned no data');
        if(!$this->imageHelper->supportsImage($previewData)) throw new Exception('Preview service returned unsupported data type');

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

        $path    = __DIR__.'/../../../img/preview/preview.jpg';
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
    protected function getPreviewFilename(
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
        $client = $this->httpClientService->newClient();
        try {
            $response = $client->get($url, ['timeout' => 60]);
        } catch(Exception $e) {
            $this->loggingService->error("Invalid Preview Api Response, HTTP {$e->getCode()}");
            throw new ApiException('API Request Failed', Http::STATUS_BAD_GATEWAY, $e);
        }

        if(!str_starts_with($response->getHeader('content-type'), 'image')) {
            $this->loggingService->error("Invalid Preview Api Response, HTTP {$response->getStatusCode()}, {$response->getHeader('content-type')}");
            throw new ApiException('API Request Failed', Http::STATUS_BAD_GATEWAY);
        }

        return $response->getBody();
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     * @throws ApiException
     * @throws Exception
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
     * @throws Exception
     */
    protected function getPreviewUrl(string $domain, string $view): string {
        throw new Exception('No preview url defined for '.$domain.'@'.$view, 502);
    }
}