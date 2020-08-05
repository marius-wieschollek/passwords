<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Favicon;

use OCA\Passwords\Exception\Favicon\FaviconRequestException;
use OCA\Passwords\Exception\Favicon\InvalidFaviconDataException;
use OCA\Passwords\Exception\Favicon\NoFaviconDataException;
use OCA\Passwords\Exception\Favicon\UnexpectedResponseCodeException;
use OCA\Passwords\Helper\Icon\FallbackIconGenerator;
use OCA\Passwords\Helper\Image\AbstractImageHelper;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\HelperService;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;

/**
 * Class AbstractFaviconHelper
 *
 * @package OCA\Passwords\Helper
 */
abstract class AbstractFaviconHelper {
    const COMMON_SUBDOMAIN_PATTERN = '/^(m|de|web|www|www2|mail|email|login|signin)\./';

    /**
     * @var string
     */
    protected $prefix = 'af';

    /**
     * @var AbstractImageHelper
     */
    protected $imageHelper;

    /**
     * @var IClientService
     */
    protected $requestService;

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
     * @param HelperService         $helperService
     * @param IClientService        $requestService
     * @param FileCacheService      $fileCacheService
     * @param FallbackIconGenerator $fallbackIconGenerator
     *
     * @throws \OCP\AppFramework\QueryException
     */
    public function __construct(
        HelperService $helperService,
        IClientService $requestService,
        FileCacheService $fileCacheService,
        FallbackIconGenerator $fallbackIconGenerator
    ) {
        $this->requestService        = $requestService;
        $this->imageHelper           = $helperService->getImageHelper();
        $this->fallbackIconGenerator = $fallbackIconGenerator;
        $this->fileCacheService      = $fileCacheService->getCacheService($fileCacheService::FAVICON_CACHE);
    }

    /**
     * @param string $domain
     *
     * @return ISimpleFile|null
     * @throws FaviconRequestException
     * @throws InvalidFaviconDataException
     * @throws NoFaviconDataException
     * @throws UnexpectedResponseCodeException
     */
    public function getFavicon(string $domain): ?ISimpleFile {
        $faviconFile = $this->getFaviconFilename($domain);

        if($this->fileCacheService->hasFile($faviconFile)) {
            return $this->fileCacheService->getFile($faviconFile);
        }

        $faviconData = $this->getFaviconData($domain);
        if(empty($faviconData)) throw new NoFaviconDataException();
        if(!$this->imageHelper->supportsImage($faviconData)) {
            $mime = $this->imageHelper->getImageMime($faviconData);
            throw new InvalidFaviconDataException('Favicon service returned unsupported data type: '.$mime);
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

        $domain  = preg_replace(self::COMMON_SUBDOMAIN_PATTERN, '', $domain);
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
     * @param string $domain
     *
     * @return null|string
     * @throws FaviconRequestException
     * @throws UnexpectedResponseCodeException
     */
    protected function getFaviconData(string $domain): ?string {
        [$uri, $options] = $this->getRequestData($domain);

        return $this->executeRequest($uri, $options);
    }

    /**
     * @return IClient
     */
    protected function createRequest(): IClient {
        $request = $this->requestService->newClient();

        return $request;
    }

    /**
     * @param string $uri
     * @param array  $options
     *
     * @return string
     * @throws FaviconRequestException
     * @throws UnexpectedResponseCodeException
     */
    protected function executeRequest(string $uri, array $options): string {
        $request = $this->createRequest();
        try {
            $response = $request->get($uri, $options);
        } catch(\Exception $e) {
            throw new FaviconRequestException($e);
        }

        if($response->getStatusCode() === 200) {
            return $response->getBody();
        }

        throw new UnexpectedResponseCodeException($response->getStatusCode());
    }

    /**
     * @param string $domain
     *
     * @return array
     */
    protected function getRequestData(string $domain): array {
        return [
            "http://{$domain}/favicon.ico",
            []
        ];
    }
}