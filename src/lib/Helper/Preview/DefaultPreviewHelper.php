<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 02:17
 */

namespace OCA\Passwords\Helper\Preview;

use OCA\Passwords\Services\HelperService;
use OCP\Files\SimpleFS\ISimpleFile;

/**
 * Class DefaultPreviewHelper
 *
 * @package OCA\Passwords\Helper\Preview
 */
class DefaultPreviewHelper extends AbstractPreviewHelper {

    /**
     * @var string
     */
    protected $prefix = HelperService::PREVIEW_DEFAULT;

    /**
     * @param string $domain
     * @param string $view
     *
     * @return ISimpleFile
     */
    function getPreview(string $domain, string $view): ISimpleFile {
        return $this->getDefaultPreview($domain);
    }
}