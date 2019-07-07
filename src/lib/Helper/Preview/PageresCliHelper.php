<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Preview;

use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\WebsitePreviewService;

/**
 * Class PageresCliHelper
 *
 * @package OCA\Passwords\Helper\Preview
 */
class PageresCliHelper extends AbstractPreviewHelper {

    const CAPTURE_MAX_RETRIES = 3;
    const USER_AGENT_DESKTOP  = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:67.0) Gecko/20100101 Firefox/67.0';
    const USER_AGENT_MOBILE   = 'Mozilla/5.0 (Android 9; Mobile; rv:67.0) Gecko/67.0 Firefox/67.0';

    /**
     * @var string
     */
    protected $prefix = HelperService::PREVIEW_PAGERES;

    /**
     * @param string $domain
     * @param string $view
     * @param string $protocol
     *
     * @return bool|string
     * @throws \Exception
     */
    protected function getPreviewData(string $domain, string $view, string $protocol = 'https'): string {
        $tempFile = uniqid();
        $tempDir  = $this->config->getTempDir();
        $tempPath = $tempDir.$tempFile.'.png';
        $command  = $this->getPageresBinary();
        $domain   = escapeshellarg($protocol.'://'.$domain);

        $cmd = "cd {$tempDir} && {$command} {$domain} ".
               ($view === WebsitePreviewService::VIEWPORT_DESKTOP ? self::VIEWPORT_DESKTOP:self::VIEWPORT_MOBILE).
               ' --user-agent='.escapeshellarg($view === WebsitePreviewService::VIEWPORT_DESKTOP ? self::USER_AGENT_DESKTOP:self::USER_AGENT_MOBILE).
               ' --delay=4 --filename='.escapeshellarg($tempFile).' --overwrite 2>&1';

        $retries = 0;
        $output  = [];
        while($retries < self::CAPTURE_MAX_RETRIES) {
            $output = [];
            @exec($cmd, $output, $returnCode);

            if($returnCode == 0 && is_file($tempPath)) {
                $content = file_get_contents($tempPath);
                unlink($tempPath);

                return $content;
            } else {
                $retries++;
            }
        }

        if($protocol === 'https') return $this->getPreviewData($domain, $view, 'http');

        throw new \Exception("Pageres Error\nCommand: {$cmd}\nOutput: ".implode(' '.PHP_EOL, $output).PHP_EOL);
    }

    /**
     * @return null|string
     * @throws \Exception
     */
    public static function getPageresBinary(): string {
        $path = self::getPageresPath();
        if($path === null) throw new \Exception('Pageres not found or not accessible');

        return $path;
    }

    /**
     * @return null|string
     */
    public static function getPageresPath(): ?string {

        $serverPath = @exec('which pageres');
        if(!empty($serverPath) && is_readable($serverPath)) return $serverPath;

        return null;
    }
}