<?php
/*
 * @copyright 2025 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Helper\Compatibility;

use OC_Util;
use OCP\Server;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ServerVersion {

    /**
     * @return int[]
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getVersion(): array {
        if(class_exists(\OCP\ServerVersion::class)) {
            return Server::get(\OCP\ServerVersion::class)->getVersion();
        }

        return OC_Util::getVersion();
    }

    /**
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getVersionString(): string {
        return implode('.', ServerVersion::getVersion());
    }

    /**
     * @return int
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getMajorVersion(): int {
        if(class_exists(\OCP\ServerVersion::class)) {
            return Server::get(\OCP\ServerVersion::class)->getMajorVersion();
        }

        return OC_Util::getVersion()[0];
    }

    /**
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getChannel(): string {
        if(class_exists(\OCP\ServerVersion::class)) {
            return Server::get(\OCP\ServerVersion::class)->getChannel();
        }

        return OC_Util::getChannel();
    }

}