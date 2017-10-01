<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 02:17
 */

namespace OCA\Passwords\Helper\PageShot;

use OCA\Passwords\Services\HelperService;
use OCP\Files\SimpleFS\ISimpleFile;

/**
 * Class DefaultPageShotHelper
 *
 * @package OCA\Passwords\Helper\PageShot
 */
class DefaultPageShotHelper extends AbstractPageShotHelper {

    /**
     * @var string
     */
    protected $prefix = HelperService::PAGESHOT_DEFAULT;

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