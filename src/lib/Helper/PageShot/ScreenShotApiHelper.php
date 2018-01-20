<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 15:23
 */

namespace OCA\Passwords\Helper\PageShot;

use Exception;
use OCA\Passwords\Helper\Http\RequestHelper;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\PageShotService;

/**
 * Class ScreenShotApiHelper
 *
 * @package OCA\Passwords\Helper\PageShot
 */
class ScreenShotApiHelper extends AbstractPageShotHelper {

    const CAPTURE_URL       = 'https://api.screenshotapi.io/capture';
    const RETRIEVE_URL      = 'https://api.screenshotapi.io/retrieve?key=';
    const WEBDRIVER_FIREFOX = 'firefox';
    const WEBDRIVER_CHROME  = 'chrome';
    const VIEWPORT_DESKTOP  = '1480x1037';
    const DEVICE_MOBILE     = 'google_nexus_s';

    /**
     * @var string
     */
    protected $prefix = HelperService::PAGESHOT_SCREEN_SHOT_API;

    /**
     * @param array $serviceOptions
     *
     * @return mixed
     * @throws Exception
     */
    protected function getImageData(array $serviceOptions): string {
        $request = $this->getAuthorizedRequest(self::CAPTURE_URL);
        $request->setJsonData($serviceOptions);

        $image = json_decode($request->sendWithRetry(), true);
        if($image['status'] !== 'accepted' && $image['status'] !== 'ready') {
            throw new Exception('screenshotapi.io service refused request: '.$image['message']);
        }

        $start            = time();
        $maxExecutionTime = (ini_get('max_execution_time') / 2) - 2;
        while(time() - $start < $maxExecutionTime) {
            $request = $this->getAuthorizedRequest(self::RETRIEVE_URL.$image['key']);
            $check   = json_decode($request->sendWithRetry(1), true);

            if($check['status'] === 'ready') {
                $load = new RequestHelper($check['imageUrl']);

                return $load->sendWithRetry();
            } else if($check['status'] === 'error' || isset($check['error'])) {
                $message = isset($check['msg']) ? $check['msg']:$check['message'];
                throw new Exception('screenshotapi.io said '.$message);
            }

            sleep(1);
        }

        throw new Exception('screenshotapi.io did not complete in time');
    }

    /**
     * @param string $url
     *
     * @return RequestHelper
     */
    protected function getAuthorizedRequest(string $url): RequestHelper {
        $request = new RequestHelper($url);

        return $request->setHeaderData($this->getServiceAuth())->setAcceptResponseCodes([]);
    }

    /**
     * @param string $domain
     * @param string $view
     * @param bool   $fullpage
     *
     * @return array
     */
    protected function getServiceOptions(string $domain, string $view, bool $fullpage = true): array {
        $options = [
            'url'         => 'http://'.$domain,
            'javascript'  => true,
            'waitSeconds' => 2,
            'fresh'       => false
        ];

        if($view === PageShotService::VIEWPORT_DESKTOP) {
            $options['webdriver'] = self::WEBDRIVER_FIREFOX;
            $options['viewport']  = self::VIEWPORT_DESKTOP;
            $options['fullpage']  = true;

            if(!$fullpage) {
                $options['webdriver'] = self::WEBDRIVER_CHROME;
                $options['fullpage']  = false;
            }
        } else {
            $options['webdriver'] = self::WEBDRIVER_CHROME;
            $options['viewport']  = self::VIEWPORT_MOBILE;
            $options['fullpage']  = false;
            $options['device']    = self::DEVICE_MOBILE;
        }

        return $options;
    }

    /**
     * @return array
     */
    protected function getServiceAuth(): array {
        $apiKey = $this->config->getAppValue('service/pageshot/ssa/key');

        return ['apikey' => $apiKey];
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     * @throws Exception
     */
    protected function getPageShotData(string $domain, string $view): string {
        $options = $this->getServiceOptions($domain, $view);

        try {
            return $this->getImageData($options);
        } catch(\Exception $e) {
            // If the service fails, ist often a full page issue
            $options = $this->getServiceOptions($domain, $view, false);

            return $this->getImageData($options);
        }
    }
}