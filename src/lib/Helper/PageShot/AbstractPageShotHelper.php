<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 01:06
 */

namespace OCA\Passwords\Helper\PageShot;

use OCA\Passwords\Helper\HttpRequestHelper;
use OCA\Passwords\Services\FileCacheService;
use OCP\Files\SimpleFS\ISimpleFile;

/**
 * Class AbstractPageShotHelper
 *
 * @package OCA\Passwords\Helper\Pageshot
 */
abstract class AbstractPageShotHelper {

    /**
     * @var string
     */
    protected $prefix = 'af';

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * BetterIdeaHelper constructor.
     *
     * @param FileCacheService $fileCacheService
     */
    public function __construct(FileCacheService $fileCacheService) {
        $this->fileCacheService = $fileCacheService;
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return ISimpleFile
     */
    function getPageShot(string $domain, string $view): ISimpleFile {
        $faviconFile = $this->getPageShotFilename($domain, $view);

        if($this->fileCacheService->hasFile($faviconFile)) {
            return $this->fileCacheService->getFile($faviconFile);
        }

        $url         = $this->getPageShotUrl($domain, $view);
        $apiRequest  = $this->getHttpRequest($url);
        $faviconData = $this->sendApiRequest($apiRequest);

        if($faviconData === null) {
            return $this->getDefaultPageShot();
        }

        return $this->fileCacheService->putFile($faviconFile, $faviconData);
    }

    /**
     * @return ISimpleFile
     */
    public function getDefaultPageShot(): ISimpleFile {
        $random   = rand(1, 5);
        $fileName = "{$this->prefix}_default_{$random}.jpg";
        if($this->fileCacheService->hasFile($fileName)) {
            return $this->fileCacheService->getFile($fileName);
        }

        $path    = dirname(dirname(dirname(__DIR__))).'/img/preview/preview_'.rand(1, 5).'.jpg';
        $content = file_get_contents($path);

        return $this->fileCacheService->putFile($fileName, $content);
    }

    /**
     * @param string   $domain
     * @param int|null $width
     * @param int|null $height
     *
     * @return string
     *
     */
    public function getPageShotFilename(string $domain, string $view, int $width = null, int $height = null): string {
        if($width !== null) {
            return "{$this->prefix}_{$domain}_{$view}_{$width}x{$height}.png";
        }

        return "{$this->prefix}_{$domain}_{$view}.png";
    }

    /**
     * @param $url
     *
     * @return HttpRequestHelper
     */
    protected function getHttpRequest($url): HttpRequestHelper {
        $request = new HttpRequestHelper();
        $request->setUrl($url);

        return $request;
    }

    /**
     * @param $apiRequest
     *
     * @return null
     */
    protected function sendApiRequest(HttpRequestHelper $apiRequest) {
        $retries = 0;
        while ($retries < 5) {
            $result = $apiRequest->send();

            if($result != null) return $result;
            $retries++;
        }

        return null;
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     */
    abstract protected function getPageShotUrl(string $domain, string $view): string;
}