<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 01:05
 */

namespace OCA\Passwords\Helper\Preview;

use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\WebsitePreviewService;

/**
 * Class WkhtmlImageHelper
 *
 * @package OCA\Passwords\Helper\Preview
 */
class WkhtmlImageHelper extends AbstractPreviewHelper {

    const CAPTURE_MAX_RETRIES = 5;

    /**
     * @var string
     */
    protected $prefix = HelperService::PREVIEW_WKHTML;

    /**
     * @param string $domain
     * @param string $view
     *
     * @return bool|string
     * @throws \Exception
     */
    protected function getPreviewData(string $domain, string $view): string {
        $tempFile = $this->config->getTempDir().uniqid().'.jpg';
        $cmd      = $this->getWkHtmlBinary().
                    ' --quiet --no-stop-slow-scripts --disable-smart-width --javascript-delay 1500 --format JPG --width '.
                    ($view === WebsitePreviewService::VIEWPORT_DESKTOP ? self::WIDTH_DESKTOP:self::WIDTH_MOBILE).
                    ' '.escapeshellarg('http://'.$domain).' '.escapeshellarg($tempFile).' 2>&1';

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

        throw new \Exception("WKHTML Error\nCommand: {$cmd}\nOutput: ".implode(' '.PHP_EOL, $output).PHP_EOL);
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function getWkHtmlBinary(): string {
        $path = self::getWkHtmlPath();
        if($path === null) throw new \Exception('WKHTML not found or not accessible');

        return $path;
    }

    /**
     * @return null|string
     */
    public static function getWkHtmlPath() {

        $serverPath = @exec('which wkhtmltoimage');
        if(!empty($serverPath) && is_readable($serverPath)) return $serverPath;

        return null;
    }
}