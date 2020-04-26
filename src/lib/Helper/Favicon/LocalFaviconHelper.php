<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Favicon;

use OCA\Passwords\Helper\Http\RequestHelper;
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
     * @param string $domain
     *
     * @return null|string
     * @throws \Throwable
     */
    protected function getFaviconData(string $domain): string {
        list($html, $url) = $this->getUrl('https://'.$domain);
        if(!$html) list($html, $url) = $this->getUrl('http://'.$domain);

        $icon = $this->getFaviconFromSourceCode($domain, $html);
        if($icon !== null) return $icon;

        $icon = $this->tryDefaultIconPaths($domain, $url);
        if($icon !== null) return $icon;

        return $this->getDefaultFavicon($domain)->getContent();
    }

    /**
     * @param string      $domain
     * @param null|string $html
     *
     * @return null|string
     */
    protected function getFaviconFromSourceCode(string $domain, ?string $html): ?string {
        if(!empty($html)) {
            $patterns = $this->getSearchPatterns();
            foreach($patterns as $pattern) {
                $image = $this->checkForImage($html, $pattern['html'], $pattern['tag'], $domain);

                if($image !== null) return $image;
            }
        }

        return null;
    }

    /**
     * @param string $domain
     * @param        $url
     *
     * @return mixed|null|string
     */
    protected function tryDefaultIconPaths(string $domain, $url): ?string {
        list($data, , , $isIcon) = $this->getUrl($url."/favicon.png");
        if($isIcon && $data) return $data;

        list($data, , , $isIcon) = $this->getUrl("http://{$domain}/favicon.png");
        if($isIcon && $data) return $data;

        list($data, , , $isIcon) = $this->getUrl($url."/favicon.ico");
        if($isIcon && $data) return $this->convertIcoFile($data);

        list($data, , , $isIcon) = $this->getUrl("http://{$domain}/favicon.ico");
        if($isIcon && $data) return $this->convertIcoFile($data);

        return null;
    }

    /**
     * @param string $url
     *
     * @return mixed|string
     */
    protected function getUrl(string $url): array {
        $request = new RequestHelper();
        $request->setUrl($url);
        $data = $request->sendWithRetry(3);

        $url         = $request->getInfo('url');
        $contentType = $request->getInfo('content_type');
        $isIcon      = substr($contentType, 0, 5) === 'image' && $this->imageHelper->supportsImage($data);

        return [
            $data,
            $url,
            $contentType,
            $isIcon
        ];
    }

    /**
     * @param string $htmlSource
     * @param string $htmlPattern
     * @param string $tagPattern
     * @param string $domain
     *
     * @return null|string
     */
    protected function checkForImage(string $htmlSource, string $htmlPattern, string $tagPattern, string $domain): ?string {

        if(preg_match_all($htmlPattern, $htmlSource, $htmlMatches)) {
            foreach($htmlMatches[1] as $tagSource) {
                if(preg_match($tagPattern, $tagSource, $tagMatches)) {
                    $url = $this->makeUrl($tagMatches[1], $domain);
                    list($data, , , $isIcon) = $this->getUrl($url);

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
     * @param string $data
     *
     * @return string|null
     */
    protected function convertIcoFile($data): ?string {
        if(empty($data)) return null;

        return $this->imageHelper->convertIcoToPng($data);
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