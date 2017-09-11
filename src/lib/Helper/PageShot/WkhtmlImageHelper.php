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
     * @throws ApiException
     */
    protected function getWkHtmlBinary(): string {
        $serverPath = @exec('which wkhtmltoimage');

        if(!empty($serverPath) && is_file($serverPath)) {
            return $serverPath;
        }

        $localPath = dirname(dirname(dirname(__DIR__))).'/bin/wkhtmltoimage';

        if(!empty($localPath) && is_file($localPath)) {
            return $localPath;
        }

        \OC::$server->getLogger()->error('WKHTML binary not found or not accessible. You can install WKHTML binary from admin page');

        throw new ApiException('Icorrect PageShot API Configuration');
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