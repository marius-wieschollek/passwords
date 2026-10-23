<?php

namespace OCA\Passwords\Compatibility\OCP;

use OC_Util;
use OCP\Server;

class ServerVersion {

    public function getMajorVersion(): int {
        if(class_exists(\OCP\ServerVersion::class)) {
            return Server::get(\OCP\ServerVersion::class)->getMajorVersion();
        }

        return OC_Util::getVersion()[0];
    }

    public function getMinorVersion(): int {
        if(class_exists(\OCP\ServerVersion::class)) {
            return Server::get(\OCP\ServerVersion::class)->getMinorVersion();
        }

        return OC_Util::getVersion()[1];
    }

    public function getPatchVersion(): int {
        if(class_exists(\OCP\ServerVersion::class)) {
            return Server::get(\OCP\ServerVersion::class)->getPatchVersion();
        }

        return OC_Util::getVersion()[2];
    }

    public function getVersion(): array {
        if(class_exists(\OCP\ServerVersion::class)) {
            return Server::get(\OCP\ServerVersion::class)->getVersion();
        }

        return OC_Util::getVersion();
    }

    public function getVersionString(): string {
        return join('.', $this->getVersion());
    }

    public function getChannel(): string {
        if(class_exists(\OCP\ServerVersion::class)) {
            return Server::get(\OCP\ServerVersion::class)->getChannel();
        }

        return OC_Util::getChannel();
    }
}