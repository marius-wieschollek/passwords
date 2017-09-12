<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 01:05
 */

namespace OCA\Passwords\Helper\PageShot;

use OCA\Passwords\Exception\ApiException;
use OCP\Files\SimpleFS\ISimpleFile;

/**
 * Class WkhtmlImageHelper
 *
 * @package OCA\Passwords\Helper\Pageshot
 */
class WkhtmlImageHelper extends AbstractPageShotHelper {

    const CAPTURE_MAX_RETRIES = 5;

    /**
     * @var string
     */
    protected $prefix = 'wk';

    /**
     * @param string $domain
     * @param string $view
     *
     * @return ISimpleFile
     */
    function getPageShot(string $domain, string $view): ISimpleFile {
        $pageShotFile = $this->getPageShotFilename($domain, $view);
        if($this->fileCacheService->hasFile($pageShotFile)) {
            return $this->fileCacheService->getFile($pageShotFile);
        }

        $pageShotData = $this->capturePageShot($domain, $view);

        if($pageShotData === null) {
            return $this->getDefaultPageShot();
        }

        return $this->fileCacheService->putFile($pageShotFile, $pageShotData);
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return bool|string
     */
    protected function capturePageShot(string $domain, string $view) {
        $tempFile = \OC::$server->getConfig()->getSystemValue('tempdirectory', '/tmp/').uniqid().'.jpg';
        $cmd      = $this->getWkHtmlBinary().
                    ' --quiet --no-stop-slow-scripts --disable-smart-width --javascript-delay 1500 --format JPG --width '.
                    ($view === 'desktop' ? self::WIDTH_DESKTOP:self::WIDTH_MOBILE).
                    ' '.escapeshellarg('http://'.$domain).' '.$tempFile.' 2>&1';

        $retries = 0;
        $output  = [];
        while ($retries < self::CAPTURE_MAX_RETRIES) {
            $output = [];
            @exec($cmd, $output, $returnCode);

            if($returnCode == 0 && is_file($tempFile)) {
                $content = file_get_contents($tempFile);
                unlink($tempFile);

                return $content;
            } else {
                $retries++;
            }
        }

        \OC::$server->getLogger()->error('WKHTML said: '.PHP_EOL.implode(PHP_EOL, $output));

        return null;
    }

    /**
     * @return string
     * @throws ApiException
     */
    protected function getWkHtmlBinary(): string {
        $path = self::getWkHtmlPath();

        if($path === null) {
            \OC::$server->getLogger()->error('WKHTML binary not found or not accessible. You can install WKHTML binary from admin page');

            throw new ApiException('Incorrect PageShot API Configuration');
        }

        return $path;
    }

    /**
     * @return null|string
     */
    public static function getWkHtmlPath() {

        $localPath = dirname(dirname(dirname(__DIR__))).'/bin/wkhtmltoimage';
        if(!empty($localPath) && is_file($localPath)) return $localPath;

        $serverPath = @exec('which wkhtmltoimage');
        if(!empty($serverPath) && is_file($serverPath)) return $serverPath;

        return null;
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     */
    protected function getPageShotUrl(string $domain, string $view): string {
        return '';
    }
}