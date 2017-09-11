<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 16:57
 */

namespace OCA\Passwords\Helper\Favicon;

use OCA\Passwords\Helper\Image\AbstractImageHelper;
use OCA\Passwords\Services\FileCacheService;

/**
 * Class LocalFaviconHelper
 *
 * @package OCA\Passwords\Helper\Favicon
 */
class LocalFaviconHelper extends AbstractFaviconHelper {

    /**
     * @var string
     */
    protected $prefix = 'local';

    /**
     * @var string
     */
    protected $icoFile;

    /**
     * @var AbstractImageHelper
     */
    protected $imageHelper;

    /**
     * LocalFaviconHelper constructor.
     *
     * @param FileCacheService    $fileCacheService
     * @param AbstractImageHelper $imageHelper
     */
    public function __construct(FileCacheService $fileCacheService, AbstractImageHelper $imageHelper) {
        parent::__construct($fileCacheService);
        $this->imageHelper = $imageHelper;
    }

    /**
     * @param string $domain
     *
     * @return string
     */
    protected function getFaviconUrl(string $domain): string {

        $html     = $this->getHttpRequest('http://'.$domain);
        if(!empty($html)) {
            $patterns = $this->getSearchPatterns();
            foreach ($patterns as $pattern) {
                $url = $this->checkForImage($html, $pattern['html'], $pattern['tag'], $domain);

                if($url !== null) return $url;
            }
        }

        $pngFavicon = "http://{$domain}/favicon.png";
        if(@fopen($pngFavicon, 'r')) return $pngFavicon;

        $this->icoFile = "http://{$domain}/favicon.ico";

        return 'icon';
    }

    /**
     * @param string $url
     *
     * @return mixed|string
     */
    protected function getHttpRequest(string $url) {
        if($url !== 'icon') {
            return parent::getHttpRequest($url);
        }

        $imageData = parent::getHttpRequest($this->icoFile);

        return $this->convertIcoFile($imageData);
    }

    /**
     * @param string $data
     *
     * @return string
     */
    protected function convertIcoFile($data) {
        if(empty($data)) return null;

        return $this->imageHelper->convertIcoToPng($data);
    }

    /**
     * @param string $htmlSource
     * @param string $htmlPattern
     * @param string $tagPattern
     * @param string $domain
     *
     * @return null|string
     */
    protected function checkForImage(string $htmlSource, string $htmlPattern, string $tagPattern, string $domain) {

        if(preg_match_all($htmlPattern, $htmlSource, $htmlMatches)) {
            foreach ($htmlMatches[1] as $tagSource) {
                if(preg_match($tagPattern, $tagSource, $tagMatches)) {
                    $url = $this->makeUrl($tagMatches[1], $domain);

                    if(@fopen($url, 'r')) return $url;
                }
            }
        };

        return null;
    }

    /**
     * @param string $url
     * @param string $domain
     *
     * @return string
     */
    protected function makeUrl(string $url, string $domain): string {
        if(substr($url, 0, 2) == '//') {
            return 'http:'.$url;
        }
        if(substr($url, 0, 1) == '/') {
            return "http://{$domain}/{$url}";
        }

        return $url;
    }

    /**
     * @return array
     */
    protected function getSearchPatterns(): array {

        return [
            [
                'html' => '/(meta[^>]+itemprop[^>]+image[^>]+)/',
                'tag'  => '/content=[\'"](\S+)[\'"]/'
            ],
            [
                'html' => '/(link[^>]+rel[^>]+fluid-icon[^>]+)/',
                'tag'  => '/href=[\'"](\S+)[\'"]/'
            ],
            [
                'html' => '/(link[^>]+rel[^>]+apple-touch-icon[^>]+)/',
                'tag'  => '/href=[\'"](\S+)[\'"]/'
            ],
            [
                'html' => '/(meta[^>]+property[^>]+og:image[^>]+)/',
                'tag'  => '/content=[\'"](\S+)[\'"]/'
            ],
            [
                'html' => '/(meta[^>]+property[^>]+twitter:image:src[^>]+)/',
                'tag'  => '/content=[\'"](\S+)[\'"]/'
            ],
            [
                'html' => '/(meta[^>]+name[^>]+msapplication-TileImage[^>]+)/',
                'tag'  => '/content=[\'"](\S+)[\'"]/'
            ],
            [
                'html' => '/(link[^>]+rel[^>]+apple-touch-icon-precomposed[^>]+)/',
                'tag'  => '/href=[\'"](\S+)[\'"]/'
            ],
            [
                // Just for youtube
                'html' => '/(link[^>]+rel[^>]+icon[^>]+sizes[^>]+1[0-9]+x1[0-9]+[^>]+)/',
                'tag'  => '/href=[\'"](\S+)[\'"]/'
            ],
            [
                'html' => '/(link[^>]+rel[^>]+shortcut\s+icon[^>]+)/',
                'tag'  => '/href=[\'"](\S+)[\'"]/'
            ],
            [
                'html' => '/(link[^>]+rel[^>]+icon[^>]+)/',
                'tag'  => '/href=[\'"](\S+)[\'"]/'
            ]
        ];
    }
}