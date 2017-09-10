<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 16:57
 */

namespace OCA\Passwords\Helper\Favicon;

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
     * @param string $domain
     *
     * @return string
     * @TODO This should be a little bit more "advanced"
     */
    protected function getFaviconUrl(string $domain): string {
        return "http://{$domain}/favicon.ico";
    }
}