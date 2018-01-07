<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 16:57
 */

namespace OCA\Passwords\Helper\Favicon;

use OCA\Passwords\Helper\Http\RequestHelper;
use OCA\Passwords\Helper\Image\AbstractImageHelper;
use OCA\Passwords\Services\HelperService;

/**
 * Class LocalFaviconHelper
 *
 * @package OCA\Passwords\Helper\Favicon
 */
class LocalFaviconHelper extends AbstractFaviconHelper {

    /**
     * @var string
     */
    protected $prefix = HelperService::FAVICON_LOCAL;

    /**
     * @var string
     */
    protected $icoFile;

    /**
     * @var AbstractImageHelper
     */
    protected $imageHelper;

    /**
     * @param string $domain
     *
     * @return null|string
     */
    protected function getFaviconData(string $domain): ?string {
        list($html, $url) = $this->getHttpRequest('http://'.$domain);

        if(!empty($html)) {
            $patterns = $this->getSearchPatterns();
            foreach ($patterns as $pattern) {
                $image = $this->checkForImage($html, $pattern['html'], $pattern['tag'], $domain);

                if($image !== null) return $image;
            }
        }

        list($data, , , $isIcon) = $this->getHttpRequest("http://{$domain}/favicon.png");
        if($isIcon && $data) return $data;

        list($data, , , $isIcon) = $this->getHttpRequest($url."/favicon.png");
        if($isIcon && $data) return $data;

        list($data, , , $isIcon) = $this->getHttpRequest("http://{$domain}/favicon.ico");
        if($isIcon && $data) return $this->convertIcoFile($data);

        list($data, , , $isIcon) = $this->getHttpRequest($url."/favicon.ico");
        if($isIcon && $data) return $this->convertIcoFile($data);

        return $this->getDefaultFavicon()->getContent();
    }

    /**
     * @param string $url
     *
     * @return mixed|string
     */
    protected function getHttpRequest(string $url) {
        $request = new RequestHelper();
        $request->setUrl($url);
        $data = $request->sendWithRetry();

        $url         = $request->getInfo('url');
        $contentType = $request->getInfo('content_type');
        $isIcon      = substr($contentType, 0, 5) === 'image';

        return [
            $data,
            $url,
            $contentType,
            $isIcon
        ];
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
                    list($data, , , $isIcon) = $this->getHttpRequest($url);

                    if($isIcon && $data) return $data;
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
        if(substr($url, 0, 2) === '//') {
            return 'http:'.$url;
        }
        if(substr($url, 0, 1) === '/') {
            return "http://{$domain}{$url}";
        }
        if(substr($url, 0, 4) !== 'http') {
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
                'html' => '/(meta[^>]+property[^>]+og:image[^>]+)/',
                'tag'  => '/content=[\'"](\S+)[\'"]/'
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