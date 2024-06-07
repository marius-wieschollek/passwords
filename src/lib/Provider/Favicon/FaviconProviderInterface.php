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

use OCP\Files\SimpleFS\ISimpleFile;

interface FaviconProviderInterface {

    /**
     * Get a favicon for the given domain
     *
     * @param string $domain
     *
     * @return ISimpleFile|null
     */
    public function getFavicon(string $domain): ?ISimpleFile;

    /**
     * Get the default favicon file
     *
     * @param string $domain
     * @param int    $size
     *
     * @return ISimpleFile|null
     */
    public function getDefaultFavicon(string $domain, int $size = 256): ?ISimpleFile;
}