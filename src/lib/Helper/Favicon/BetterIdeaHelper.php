<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 00:27
 */

namespace OCA\Passwords\Helper\Favicon;

use OCA\Passwords\Services\HelperService;

/**
 * Class BetterIdeaHelper
 */
class BetterIdeaHelper extends AbstractFaviconHelper {

    /**
     * @var string
     */
    protected $prefix = HelperService::FAVICON_BETTER_IDEA;

    /**
     * @param string $domain
     * @param int    $size
     *
     * @return string
     */
    protected function getFaviconUrl(string $domain, int $size): string {
        return 'https://icons.better-idea.org/icon?size=32&url='.$domain;
    }
}