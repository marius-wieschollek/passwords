<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 00:27
 */

namespace OCA\Passwords\Helper\Favicon;

/**
 * Class BetterIdeaHelper
 */
class BetterIdeaHelper extends AbstractFaviconHelper {

    /**
     * @var string
     */
    protected $prefix = 'bi';

    /**
     * @param string $domain
     *
     * @return string
     */
    protected function getFaviconUrl(string $domain): string {
        return 'https://icons.better-idea.org/icon?size=32&url='.$domain;
    }
}