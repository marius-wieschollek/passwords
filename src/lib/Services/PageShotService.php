<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.09.17
 * Time: 20:03
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Exception\ApiException;
use OCP\Files\SimpleFS\ISimpleFile;

/**
 * Class PageShotService
 *
 * @package OCA\Passwords\Services
 */
class PageShotService {

    const VIEWPORT_DESKTOP = 'desktop';
    const VIEWPORT_MOBILE  = 'mobile';

    /**
     * @var HelperService
     */
    protected $helperService;

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * @var ValidationService
     */
    protected $validationService;

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * FaviconService constructor.
     *
     * @param HelperService     $helperService
     * @param FileCacheService  $fileCacheService
     * @param ValidationService $validationService
     * @param LoggingService    $logger
     */
    public function __construct(
        HelperService $helperService,
        FileCacheService $fileCacheService,
        ValidationService $validationService,
        LoggingService $logger
    ) {
        $fileCacheService->setDefaultCache($fileCacheService::PAGESHOT_CACHE);
        $this->fileCacheService  = $fileCacheService;
        $this->validationService = $validationService;
        $this->helperService     = $helperService;
        $this->logger            = $logger;
    }

    /**
     * @param string $domain
     * @param string $view
     * @param int    $minWidth
     * @param int    $minHeight
     *
     * @param int    $maxWidth
     * @param int    $maxHeight
     *
     * @return ISimpleFile
     * @throws ApiException
     * @throws \OCP\AppFramework\QueryException
     */
    public function getPreview(
        string $domain,
        string $view = self::VIEWPORT_DESKTOP,
        int $minWidth = 550,
        int $minHeight = 0,
        int $maxWidth = 550,
        int $maxHeight = 0
    ): ISimpleFile {
        list($domain, $minWidth, $minHeight, $maxWidth, $maxHeight)
            = $this->validateInput($domain, $minWidth, $minHeight, $maxWidth, $maxHeight);

        $pageShotService = $this->helperService->getPageShotHelper();
        $fileName        = $pageShotService->getPageShotFilename($domain, $view, $minWidth, $minHeight, $maxWidth, $maxHeight);
        if($this->fileCacheService->hasFile($fileName)) {
            return $this->fileCacheService->getFile($fileName);
        }

        try {
            if(!$this->validationService->isValidDomain($domain)) {
                $pageShot = $pageShotService->getDefaultPageShot();
            } else {
                $pageShot = $pageShotService->getPageShot($domain, $view);
            }

            return $this->resizePageShot($pageShot, $fileName, $minWidth, $minHeight, $maxWidth, $maxHeight);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());

            try {
                $pageShot = $pageShotService->getDefaultPageShot();

                return $this->resizePageShot($pageShot, 'error.jpg', $minWidth, $minHeight, $maxWidth, $maxHeight);
            } catch (\Throwable $e) {
                $this->logger->error($e->getMessage());

                throw new ApiException('Internal PageShot API Error', 502);
            }
        }
    }

    /**
     * @param ISimpleFile $pageShot
     * @param string      $fileName
     * @param int         $minWidth
     * @param int         $minHeight
     * @param int         $maxWidth
     * @param int         $maxHeight
     *
     * @return ISimpleFile|null
     * @throws \OCP\AppFramework\QueryException
     */
    protected function resizePageShot(
        ISimpleFile $pageShot,
        string $fileName,
        int $minWidth,
        int $minHeight,
        int $maxWidth,
        int $maxHeight
    ): ?ISimpleFile {

        $imageHelper = $this->helperService->getImageHelper();
        $image       = $imageHelper->getImageFromBlob($pageShot->getContent());
        $image       = $imageHelper->advancedResizeImage($image, $minWidth, $minHeight, $maxWidth, $maxHeight);
        $imageData   = $imageHelper->exportJpeg($image);
        $imageHelper->destroyImage($image);

        return $this->fileCacheService->putFile($fileName, $imageData);
    }

    /**
     * @param string $domain
     * @param int    $minWidth
     * @param int    $minHeight
     * @param int    $maxWidth
     * @param int    $maxHeight
     *
     * @return array
     */
    protected function validateInput(string $domain, int $minWidth, int $minHeight, int $maxWidth, int $maxHeight): array {
        if(filter_var($domain, FILTER_VALIDATE_URL)) $domain = parse_url($domain, PHP_URL_HOST);

        $minWidth  = round($minWidth, -1);
        $maxWidth  = round($maxWidth, -1);
        $minHeight = round($minHeight, -1);
        $maxHeight = round($maxHeight, -1);

        if($minWidth > 720) $minWidth = 720;
        if($minWidth < 240 && $minWidth != 0) $minWidth = 240;
        if($maxWidth < $minWidth && $maxWidth != 0) $maxWidth = $minWidth;
        if($maxWidth > 720) $maxWidth = 720;
        if($maxWidth < 240 && $maxWidth != 0) $maxWidth = 240;

        if($minHeight > 1280) $minHeight = 1280;
        if($minHeight < 240 && $minHeight != 0) $minHeight = 240;
        if($maxHeight < $minHeight && $maxHeight != 0) $maxHeight = $minHeight;
        if($maxHeight > 1280) $maxHeight = 1280;
        if($maxHeight < 240 && $maxHeight != 0) $maxHeight = 240;

        return [$domain, $minWidth, $minHeight, $maxWidth, $maxHeight];
    }
}