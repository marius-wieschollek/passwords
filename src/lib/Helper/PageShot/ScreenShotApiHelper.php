<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 15:23
 */

namespace OCA\Passwords\Helper\PageShot;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\HttpRequestHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\PageShotService;

/**
 * Class ScreenShotApiHelper
 *
 * @package OCA\Passwords\Helper\PageShot
 */
class ScreenShotApiHelper extends AbstractPageShotHelper {

    /**
     * @var string
     */
    protected $prefix = 'ssa';

    /**
     * @var string
     */
    protected $domain = '';

    /**
     * @var string
     */
    protected $viewport = '';

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * ScreenShotLayerHelper constructor.
     *
     * @param FileCacheService     $fileCacheService
     * @param ConfigurationService $config
     */
    public function __construct(FileCacheService $fileCacheService, ConfigurationService $config) {
        parent::__construct($fileCacheService);
        $this->config = $config;
    }

    /**
     * @param string $url
     *
     * @return mixed
     * @throws ApiException
     */
    protected function getHttpRequest(string $url) {
        $request = new HttpRequestHelper();
        $request->setUrl($url)
                ->setPost($this->getServiceOptions())
                ->setHeader($this->getServiceAuth()());

        $imageRequest = json_decode($request->sendWithRetry(), true);

        if(!$imageRequest['status'] === 'accepted') {
            \OC::$server->getLogger()->error('PageShot service refused request');

            return null;
        }

        while (1) {
            $request = new HttpRequestHelper();
            $request->setUrl('https://api.screenshotapi.io/retrieve?key='.$imageRequest['key']);
            $readyRequest = json_decode($request->sendWithRetry(), true);

            if($readyRequest['status'] === 'ready') {
                return $readyRequest['imageUrl'];
            } else if($readyRequest['status'] === 'error') {
                \OC::$server->getLogger()->error($readyRequest['msg']);

                return null;
            }

            sleep(1);
        }

        return null;
    }

    /**
     * @return array
     */
    protected function getServiceOptions(): array {
        return [

            'url'         => $this->domain,
            'viewport'    => $this->viewport,
            'fullpage'    => true,
            'javascript'  => true,
            'webdriver'   => 'firefox',
            'device'      => '',
            'waitSeconds' => 1,
            'fresh'       => false
        ];
    }

    /**
     * @return array
     */
    protected function getServiceAuth(): array {
        $apiKey = $this->config->getAppValue('service/pageshot/screenshtoapi/apiKey');

        return [
            'apikey' => $apiKey
        ];
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     */
    protected function getPageShotUrl(string $domain, string $view): string {
        $this->domain   = 'http://'.$domain;
        $this->viewport = '360x640';

        if($view === PageShotService::VIEWPORT_DESKTOP) {
            $this->viewport = '1280x720';
        }

        return 'https://api.screenshotapi.io/capture';
    }
}