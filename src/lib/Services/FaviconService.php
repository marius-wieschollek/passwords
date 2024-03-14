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

namespace OCA\Passwords\Services;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Favicon\ResizeFaviconHelper;
use OCA\Passwords\Provider\Favicon\FaviconProviderInterface;
use OCA\Passwords\Services\Traits\ValidatesDomainTrait;
use OCP\AppFramework\Http;
use OCP\Files\SimpleFS\ISimpleFile;
use Throwable;

/**
 * Class FaviconService
 *
 * @package OCA\Passwords\Services
 */
class FaviconService {

    use ValidatesDomainTrait;

    /**
     * FaviconService constructor.
     *
     * @param ValidationService        $validationService
     * @param FaviconProviderInterface $faviconProvider
     * @param LoggingService           $logger
     * @param ResizeFaviconHelper      $resizeHelper
     */
    public function __construct(
        protected ValidationService        $validationService,
        protected FaviconProviderInterface $faviconProvider,
        protected LoggingService           $logger,
        protected ResizeFaviconHelper      $resizeHelper
    ) {
    }

    /**
     * @param string $domain
     * @param int    $size
     *
     * @return ISimpleFile
     * @throws ApiException
     * @throws Throwable
     */
    public function getFavicon(string $domain, int $size = 32): ISimpleFile {
        [$domain, $size] = $this->validateInput($domain, $size);

        if(!$this->validationService->isValidDomain($domain)) {
            return $this->handleInvalidDomain($domain, $size);
        }

        try {
            $favicon = $this->faviconProvider->getFavicon($domain);
            if($favicon) {
                $file = $this->resizeHelper->resizeFavicon($favicon, $size);
                if($file) return $file;
            }
        } catch(Throwable $e) {
            return $this->handleFaviconFetchFailure($domain, $size, $e);
        }

        return $this->getDefaultFavicon($domain, $size);
    }

    /**
     * @param string $domain
     * @param int    $size
     *
     * @return ISimpleFile
     * @throws ApiException
     */
    protected function
    handleInvalidDomain(
        string $domain,
        int    $size
    ): ISimpleFile {
        if($domain !== 'default') {
            $domain = mb_substr($domain, 0, 1);
        } else {
            $domain = ' ';
        }

        return $this->getDefaultFavicon($domain, $size);
    }

    /**
     * @param string    $domain
     * @param int       $size
     * @param Throwable $e
     *
     * @return ISimpleFile
     * @throws ApiException
     */
    protected function handleFaviconFetchFailure(string $domain, int $size, Throwable $e): ISimpleFile {
        $this->logger->logException($e);

        return $this->getDefaultFavicon($domain, $size);
    }

    /**
     * @param string $domain
     * @param int    $size
     *
     * @return array
     */
    protected function validateInput(string $domain, int $size): array {
        $domain = $this->validateDomain($domain);

        $size = round($size / 8) * 8;
        if($size > 256) {
            $size = 256;
        } else if($size < 16) {
            $size = 16;
        }

        return [$domain, $size];
    }

    /**
     * @param string $domain
     * @param int    $size
     *
     * @return ISimpleFile|null
     * @throws ApiException
     */
    protected function getDefaultFavicon(string $domain, int $size): ?ISimpleFile {
        try {
            return $this->faviconProvider->getDefaultFavicon($domain, $size);
        } catch(Throwable $ex) {
            $this->logger->logException($ex);
            throw new ApiException('Internal Favicon API Error', Http::STATUS_BAD_GATEWAY, $ex);
        }
    }
}
