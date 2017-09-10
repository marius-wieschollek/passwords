<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 01:05
 */

namespace OCA\Passwords\Helper\PageShot;

use OCP\Files\SimpleFS\ISimpleFile;

/**
 * Class WkhtmlImageHelper
 *
 * @package OCA\Passwords\Helper\Pageshot
 */
class WkhtmlImageHelper extends AbstractPageShotHelper {

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
                    ($view === 'desktop' ? 1280:360).
                    ' '.escapeshellarg('http://'.$domain).' '.$tempFile;

        $retries = 0;
        while ($retries < 5) {
            @exec($cmd, $output, $returnCode);

            if($returnCode == 0 && is_file($tempFile)) {
                $content = file_get_contents($tempFile);
                unlink($tempFile);

                return $content;
            } else {
                $retries++;
            }
        }

        return null;
    }

    /**
     * @return string
     */
    protected function getWkHtmlBinary(): string {
        $bin = dirname(dirname(dirname(__DIR__))).'/bin/wkhtml/'.PHP_OS;
        if(PHP_INT_SIZE == 8) {
            return $bin.'64';
        } else {
            return $bin.'32';
        }
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