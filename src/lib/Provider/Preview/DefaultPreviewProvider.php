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

namespace OCA\Passwords\Provider\Preview;

use OCA\Passwords\Services\HelperService;
use OCP\Files\SimpleFS\ISimpleFile;

/**
 * Class DefaultPreviewProvider
 *
 * @package OCA\Passwords\Helper\Preview
 */
class DefaultPreviewProvider extends AbstractPreviewProvider {

    /**
     * @var string
     */
    protected string $prefix = HelperService::PREVIEW_DEFAULT;

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