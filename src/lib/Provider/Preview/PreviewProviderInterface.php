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

use Exception;
use OCP\Files\SimpleFS\ISimpleFile;

interface PreviewProviderInterface {

    /**
     * @param string $domain
     * @param string $view
     *
     * @return ISimpleFile|null
     */
    function getPreview(string $domain, string $view): ?ISimpleFile;

    /**
     * @param string $domain
     *
     * @return ISimpleFile|null
     */
    public function getDefaultPreview(string $domain): ?ISimpleFile;
}