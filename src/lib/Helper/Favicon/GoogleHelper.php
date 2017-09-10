<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 02:31
 */

namespace OCA\Passwords\Helper\Favicon;

/**
 * Class GoogleHelper
 *
 * @package OCA\Passwords\Helper\Favicon
 */
class GoogleHelper extends AbstractFaviconHelper {

    /**
     * @var string
     */
    protected $prefix = 'gl';

    /**
     * @param string $domain
     *
     * @return string
     */
    protected function getFaviconUrl(string $domain): string {
        return 'https://www.google.com/s2/favicons?domain='.$domain;
    }
}