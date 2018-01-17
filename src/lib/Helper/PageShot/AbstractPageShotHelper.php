<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 01:06
 */

namespace OCA\Passwords\Helper\PageShot;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Http\RequestHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCP\Files\SimpleFS\ISimpleFile;

/**
 * Class AbstractPageShotHelper
 *
 * @package OCA\Passwords\Helper\Pageshot
 */
abstract class AbstractPageShotHelper {

    const VIEWPORT_DESKTOP = '1280x800';
    const VIEWPORT_MOBILE  = '360x640';
    const WIDTH_DESKTOP    = 1280;
    const WIDTH_MOBILE     = 360;

    /**
     * @var string
     */
    protected $prefix = 'af';

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * BetterIdeaHelper constructor.
     *
     * @param FileCacheService     $fileCacheService
     * @param ConfigurationService $config
     */
    public function __construct(FileCacheService $fileCacheService, ConfigurationService $config) {
        $this->fileCacheService = $fileCacheService->getCacheService($fileCacheService::PAGESHOT_CACHE);
        $this->config           = $config;
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return ISimpleFile|null
     * @throws \Exception
     */
    function getPageShot(string $domain, string $view): ?ISimpleFile {
        $pageshotFile = $this->getPageShotFilename($domain, $view);

        if($this->fileCacheService->hasFile($pageshotFile)) {
            return $this->fileCacheService->getFile($pageshotFile);
        }

        $pageShotData = $this->getPageShotData($domain, $view);
        if($pageShotData === null) throw new \Exception('PageShot service returned no data');

        return $this->fileCacheService->putFile($pageshotFile, $pageShotData);
    }

    /**
     * @param string $domain
     *
     * @return ISimpleFile|null
     */
    public function getDefaultPageShot(string $domain): ?ISimpleFile {
        $number = array_sum(str_split(dechex(crc32($domain)), 2));
        while($number >= 5) {
            $number -= 5;
        }

        $fileName = "{$this->prefix}_default_{$number}.jpg";
        if($this->fileCacheService->hasFile($fileName)) {
            return $this->fileCacheService->getFile($fileName);
        }

        $path    = dirname(dirname(dirname(__DIR__))).'/img/preview/preview_'.rand(1, 5).'.jpg';
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
    public function getPageShotFilename(
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
    protected function getHttpRequest(string $url) {
        $request = new RequestHelper();
        $request->setUrl($url);
        $data = $request->sendWithRetry();

        $type = $request->getInfo()['content_type'];
        if(substr($type, 0, 5) != 'image') {
            throw new ApiException('API Request Failed', 502);
        }

        return $data;
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return mixed
     * @throws ApiException
     * @throws \Exception
     */
    protected function getPageShotData(string $domain, string $view) {
        $url          = $this->getPageShotUrl($domain, $view);
        $pageShotData = $this->getHttpRequest($url);

        return $pageShotData;
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     * @throws \Exception
     */
    protected function getPageShotUrl(string $domain, string $view): string {
        throw new \Exception('No Pageshot Url defined for '.$domain.$view, 502);
    }
}