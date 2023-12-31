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

namespace OCA\Passwords\Provider\Favicon;

use OCA\Passwords\Services\HelperService;
use OCP\Files\SimpleFS\ISimpleFile;
use Throwable;

/**
 * Class DefaultFaviconProvider
 *
 * @package OCA\Passwords\Helper\Favicon
 */
class DefaultFaviconProvider extends AbstractFaviconProvider {

    /**
     * @var string
     */
    protected string $prefix = HelperService::FAVICON_DEFAULT;

    /**
     * @param string $domain
     *
     * @return ISimpleFile
     * @throws Throwable
     */
    public function getFavicon(string $domain): ISimpleFile {
        return $this->getDefaultFavicon($domain);
    }
}