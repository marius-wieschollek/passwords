<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Favicon;

use OCA\Passwords\Services\HelperService;
use OCP\Files\SimpleFS\ISimpleFile;
use Throwable;

/**
 * Class DefaultPreviewHelper
 *
 * @package OCA\Passwords\Helper\Favicon
 */
class DefaultFaviconHelper extends AbstractFaviconHelper {

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