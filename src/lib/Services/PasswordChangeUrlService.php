<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Services\Traits\ValidatesDomainTrait;
use OCP\Http\Client\IClientService;

class PasswordChangeUrlService {

    use ValidatesDomainTrait;

    protected array $passwordChangeUrls
        = [
            '/^.+\.google\.[a-z]{2,3}$/'                              => 'https://myaccount.google.com/security',
            '/^.+\.reddit\.[a-z]{2,3}$/'                              => 'https://www.reddit.com/change_password/',
            '/^.+\.(microsoft|live|outlook|xbox|skype)\.[a-z]{2,3}$/' => 'https://account.live.com/password/change?refd=account.microsoft.com&fref=home.banner.changepwd',
            '/^.+\.(office|onenote)\.com$/'                           => 'https://account.live.com/password/change?refd=account.microsoft.com&fref=home.banner.changepwd',
            '/^.+\.twitter\.com$/'                                    => 'https://twitter.com/settings/password',
            '/^.+\.tumblr\.[a-z]{2,3}$/'                              => 'https://www.tumblr.com/settings/account',
            '/^.+\.adobe\.[a-z]{2,3}$/'                               => 'https://account.adobe.com/security?changePassword=true',
            '/^.+\.linkedin\.com$/'                                   => 'https://www.linkedin.com/mypreferences/d/change-password',
            '/^.+\.xing\.[a-z]{2,3}$/'                                => 'https://www.xing.com/preferences/account/credentials/password',
            '/^.+\.facebook\.[a-z]{2,3}$/'                            => 'https://www.facebook.com/settings/?tab=security&section=password',
            '/^.+\.gitlab\.com$/'                                     => 'https://gitlab.com/-/profile/password/edit',
            '/^.+\.ebay\.[a-z]{2,3}$/'                                => 'https://accounts.ebay.com/acctsec/security-center/chngpwd',
            '/^.+\.steampowered\.com$/'                               => 'https://store.steampowered.com/account/',
            '/^.+\.epicgames\.com$/'                                  => 'https://www.epicgames.com/account/password',
            '/^.+\.(ubisoftconnect|uplay|ubisoft)\.com$/'             => 'https://account.ubisoft.com/security-settings',
            '/^.+\.alternate\.de$/'                                   => 'https://www.alternate.de/Mein-Konto/Pers%C3%B6nliche-Daten',
            '/^.+\.conrad\.de$/'                                      => 'https://www.conrad.de/de/account.html#/profile/change-password',
            '/^.+\.(mediamarkt|saturn)\.de$/'                         => 'https://www.mediamarkt.de/de/myaccount/profile',
            '/^.+\.digitalo\.de$/'                                    => 'https://www.digitalo.de/my/user_data.html',
            '/^.+\.mindfactory\.de$/'                                 => 'https://www.mindfactory.de/account_password_change.php',
            '/^.+\.paypal\.[a-z]{2,3}$/'                              => 'https://www.paypal.com/myaccount/security/password/change',
            '/^.+\.patreon\.com$/'                                    => 'https://www.patreon.com/settings/account',
            '/^.+\.plex\.tv$/'                                        => 'https://app.plex.tv/desktop/#!/settings/account',
            '/^.+\.booking\.com$/'                                    => 'https://account.booking.com/mysettings/security'
        ];

    /**
     * @param IClientService $httpClientService
     * @param LoggingService $logger
     */
    public function __construct(protected IClientService $httpClientService, protected LoggingService $logger) {
    }

    /**
     * @param string $domain
     *
     * @return string|null
     */
    public function getPasswordChangeUrl(string $domain): ?string {
        $domain = $this->validateDomain($domain);

        $passwordChangeUrl = $this->getPasswordChangeUrlFromWellKnown($domain);

        if($passwordChangeUrl !== null) {
            return $passwordChangeUrl;
        }

        return $this->getPasswordChangeUrlFromInternal($domain);
    }

    /**
     * @param string $domain
     *
     * @return string|null
     */
    protected function getPasswordChangeUrlFromWellKnown(string $domain): ?string {
        if($this->isWellKnownReliable($domain)) {
            return $this->getUrlFromWellKnown($domain);
        }

        return null;
    }

    /**
     * @param string $domain
     *
     * @return string|null
     */
    protected function getPasswordChangeUrlFromInternal(string $domain): ?string {
        foreach($this->passwordChangeUrls as $pattern => $url) {
            if(preg_match($pattern, $domain) === 1) {
                return $url;
            }
        }

        return null;
    }

    /**
     * @param string $domain
     *
     * @return bool
     */
    protected function isWellKnownReliable(string $domain): bool {
        $wellKnownUrl = "https://$domain/.well-known/resource-that-should-not-exist-whose-status-code-should-not-be-200";
        $request      = $this->createCurlRequest($wellKnownUrl, [CURLOPT_NOBODY => true, CURLOPT_HEADER => true]);
        curl_exec($request);
        $statusCode = curl_getinfo($request, CURLINFO_HTTP_CODE);

        return $statusCode !== 200;
    }

    /**
     * @param string $domain
     *
     * @return string|null
     */
    protected function getUrlFromWellKnown(string $domain): ?string {
        $wellKnownUrl = "https://$domain/.well-known/change-password";
        $request      = $this->createCurlRequest($wellKnownUrl);
        $body         = strtolower(curl_exec($request));

        $statusCode = curl_getinfo($request, CURLINFO_HTTP_CODE);
        if($statusCode !== 200) return null;

        $targetUrl = curl_getinfo($request, CURLINFO_EFFECTIVE_URL);
        curl_close($request);

        if(empty($body) || !str_contains($body, 'http-equiv') || !str_contains($body, 'refresh')) {
            if(!empty($targetUrl) && $targetUrl !== $wellKnownUrl) {
                return $targetUrl;
            }

            return null;
        }

        $urlFromBody = $this->analyzeWellKnownResponseBody($body, $targetUrl);

        if($urlFromBody !== null) {
            return $urlFromBody;
        }

        if(!empty($targetUrl) && $targetUrl !== $wellKnownUrl) {
            return $targetUrl;
        }

        return null;
    }

    /**
     * @param string $url
     * @param string $baseUrl
     *
     * @return string|null
     */
    protected function validateUrl(string $url, string $baseUrl): ?string {
        if(str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        if(str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        $origin = parse_url($baseUrl, PHP_URL_SCHEME).'://'.parse_url($baseUrl, PHP_URL_HOST);
        if(parse_url($baseUrl, PHP_URL_PORT) !== null) {
            $origin .= ':'.parse_url($baseUrl, PHP_URL_PORT);
        }

        if(str_starts_with($url, '/')) {
            return $origin.$url;
        }
        if(str_starts_with($url, '?')) {
            return $origin.parse_url($baseUrl, PHP_URL_PATH).$url;
        }
        if(str_starts_with($url, '#')) {
            return $origin.parse_url($baseUrl, PHP_URL_PATH).parse_url($baseUrl, PHP_URL_QUERY).$url;
        }

        if(str_contains($url, '../') || str_contains($url, './')) {
            $path     = explode('/', parse_url($baseUrl, PHP_URL_PATH).'/'.$url);
            $realpath = [];
            foreach($path as $item) {
                if(empty($item) || $item === '.') continue;
                if($item === '..') {
                    if(count($realpath) > 0) array_pop($realpath);
                    continue;
                }
                $realpath[] = $item;
            }

            return $origin.'/'.implode('/', $realpath);
        }

        return null;
    }

    /**
     * @param string     $url
     * @param array|null $opts
     *
     * @return \CurlHandle
     */
    protected function createCurlRequest(string $url, array $opts = null): \CurlHandle {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);

        if($opts !== null) {
            curl_setopt_array($curl, $opts);
        }

        return $curl;
    }

    /**
     * @param string $body
     * @param mixed  $targetUrl
     *
     * @return string|null
     */
    protected function analyzeWellKnownResponseBody(string $body, mixed $targetUrl): ?string {
        $html = new \DOMDocument();
        if(!$html->loadHTML($body)) {
            return null;
        }

        $xpath = new \DOMXPath($html);
        $nodes = $xpath->query('*/meta[@http-equiv="refresh"]');
        if($nodes->count() === 0) {
            return null;
        }

        foreach($nodes as $node) {
            if($node->hasAttribute('content') && str_contains($node->getAttribute('content'), ';url=')) {
                [, $url] = explode(';url=', $node->getAttribute('content'), 2);

                return $this->validateUrl($url, $targetUrl);
            }
        }

        return null;
    }
}