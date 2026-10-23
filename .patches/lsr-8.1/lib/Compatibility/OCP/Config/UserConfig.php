<?php

namespace OCA\Passwords\Compatibility\OCP\Config;

use OCP\Server;

class UserConfig {

    public function hasKey(string $userId, string $app, string $key, ?bool $lazy = false): bool {
        if(class_exists(\OCP\Config\IUserConfig::class)) {
            return Server::get(\OCP\Config\IUserConfig::class)->hasKey($userId, $app, $key, $lazy);
        }

        return in_array(
            $key,
            Server::get(\OCP\IConfig::class)->getUserKeys($userId, $app)
        );
    }

    public function getValueString(string $userId, string $app, string $key, string $default = '', bool $lazy = false): string {
        if(class_exists(\OCP\Config\IUserConfig::class)) {
            return Server::get(\OCP\Config\IUserConfig::class)->getValueString($userId, $app, $key, $default, $lazy);
        }

        return Server::get(\OCP\IConfig::class)->getUserValue($userId, $app, $key, $default);
    }

    public function setValueString(string $userId, string $app, string $key, string $value, bool $lazy = false, int $flags = 0): bool {
        if(class_exists(\OCP\Config\IUserConfig::class)) {
            return Server::get(\OCP\Config\IUserConfig::class)->setValueString($userId, $app, $key, $value, $lazy, $flags);
        }

        Server::get(\OCP\IConfig::class)->setUserValue($userId, $app, $key, $value);

        return true;
    }

    public function deleteUserConfig(string $userId, string $app, string $key): void {
        if(class_exists(\OCP\Config\IUserConfig::class)) {
            Server::get(\OCP\Config\IUserConfig::class)->deleteUserConfig($userId, $app, $key);
            return;
        }

        Server::get(\OCP\IConfig::class)->deleteUserValue($userId, $app, $key);
    }

    public function clearCache(string $userId, bool $reload = false): void {
        if(class_exists(\OCP\Config\IUserConfig::class)) {
            Server::get(\OCP\Config\IUserConfig::class)->clearCache($userId, $reload);
        }
    }
}