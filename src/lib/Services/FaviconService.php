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
use OCA\Passwords\Provider\Favicon\FaviconProviderInterface;
use OCA\Passwords\Services\Traits\ValidatesDomainTrait;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
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
     * @var FileCacheService
     */
    protected FileCacheService $fileCacheService;

    /**
     * FaviconService constructor.
     *
     * @param FileCacheService         $fileCacheService
     * @param ValidationService        $validationService
     * @param FaviconProviderInterface $faviconProvider
     * @param LoggingService           $logger
     */
    public function __construct(
        FileCacheService $fileCacheService,
        protected ValidationService $validationService,
        protected FaviconProviderInterface $faviconProvider,
        protected LoggingService $logger
    ) {
        $this->fileCacheService  = $fileCacheService->getCacheService($fileCacheService::FAVICON_CACHE);
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
            if($domain !== 'default') {
                $domain = mb_substr($domain, 0, 1);
            } else {
                $domain = ' ';
            }
            return $this->faviconProvider->getDefaultFavicon($domain, $size);
        }

        try {
            $favicon = $this->faviconProvider->getFavicon($domain);

            return $this->resizeFavicon($favicon, $size);
        } catch(Throwable $e) {
            $this->logger->logException($e);

            try {
                return $this->faviconProvider->getDefaultFavicon($domain, $size);
            } catch(Throwable $e) {
                $this->logger->logException($e);

                throw new ApiException('Internal Favicon API Error', 502, $e);
            }
        }
    }

    /**
     * @param ISimpleFile $favicon
     * @param int         $size
     *
     * @return ISimpleFile|null
     * @throws NotFoundException
     * @throws NotPermittedException
     */
    protected function resizeFavicon(ISimpleFile $favicon, int $size): ?ISimpleFile {
        $faviconData = $favicon->getContent();
        $imageHelper = $this->helperService->getImageHelper();
        if($imageHelper->supportsImage($faviconData)) {
            $image = $imageHelper->getImageFromBlob($faviconData);
            $imageHelper->cropImageRectangular($image);
            $imageHelper->simpleResizeImage($image, $size);
            $faviconData = $imageHelper->exportPng($image);
            $imageHelper->destroyImage($image);
        }

        return $this->fileCacheService->putFile($favicon->getName(), $faviconData);
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
}
