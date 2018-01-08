<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 01:05
 */

namespace OCA\Passwords\Helper\PageShot;

use Exception;
use OCA\Passwords\Services\HelperService;
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
    protected $prefix = HelperService::PAGESHOT_WKHTML;

    /**
     * @param string $domain
     * @param string $view
     *
     * @return ISimpleFile|null
     * @throws Exception
     */
    function getPageShot(string $domain, string $view): ?ISimpleFile {
        $pageShotFile = $this->getPageShotFilename($domain, $view);
        if($this->fileCacheService->hasFile($pageShotFile)) {
            return $this->fileCacheService->getFile($pageShotFile);
        }

        $pageShotData = $this->capturePageShot($domain, $view);

        if($pageShotData === null) {
            throw new Exception('PageShot service returned no data');
        }

        return $this->fileCacheService->putFile($pageShotFile, $pageShotData);
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return bool|string
     * @throws Exception
     */
    protected function capturePageShot(string $domain, string $view) {
        $tempFile = $this->config->getTempDir().uniqid().'.jpg';
        $cmd      = $this->getWkHtmlBinary().
                    ' --quiet --no-stop-slow-scripts --disable-smart-width --javascript-delay 1500 --format JPG --width '.
                    ($view === 'desktop' ? self::WIDTH_DESKTOP:self::WIDTH_MOBILE).
                    ' '.escapeshellarg('http://'.$domain).' '.$tempFile.' 2>&1';

        $retries = 0;
        $output  = [];
        while($retries < self::CAPTURE_MAX_RETRIES) {
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

        throw new Exception('WKHTML said: '.PHP_EOL.implode(PHP_EOL, $output));
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getWkHtmlBinary(): string {
        $path = self::getWkHtmlPath();

        if($path === null) {
            throw new Exception('WKHTML binary not found or not accessible. You can install WKHTML binary from admin page');
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