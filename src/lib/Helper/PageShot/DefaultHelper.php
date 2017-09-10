<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 02:17
 */

namespace OCA\Passwords\Helper\PageShot;

use OCP\Files\SimpleFS\ISimpleFile;

/**
 * Class DefaultHelper
 *
 * @package OCA\Passwords\Helper\PageShot
 */
class DefaultHelper extends AbstractPageShotHelper {

    /**
     * @param string $domain
     * @param string $view
     *
     * @return ISimpleFile
     */
    function getPageShot(string $domain, string $view): ISimpleFile {
        return $this->getDefaultPageShot();
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     */
    protected function getPageShotUrl(string $domain, string $view): string {
        return '';
    }
}