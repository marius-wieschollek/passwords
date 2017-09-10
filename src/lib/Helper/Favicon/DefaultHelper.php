<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 02:16
 */

namespace OCA\Passwords\Helper\Favicon;

use OCP\Files\SimpleFS\ISimpleFile;

/**
 * Class DefaultHelper
 *
 * @package OCA\Passwords\Helper\Favicon
 */
class DefaultHelper extends AbstractFaviconHelper {

    /**
     * @param string $domain
     *
     * @return ISimpleFile
     */
    public function getFavicon(string $domain): ISimpleFile {
        return $this->getDefaultFavicon();
    }

    /**
     * @param string $domain
     *
     * @return string
     */
    protected function getFaviconUrl(string $domain): string {
        return '';
    }
}