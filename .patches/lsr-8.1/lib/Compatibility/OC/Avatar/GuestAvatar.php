<?php

namespace OCA\Passwords\Compatibility\OC\Avatar;

use OC_Util;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class GuestAvatar extends \OC\Avatar\GuestAvatar {
    public function __construct(string $userDisplayName, IConfig $config, LoggerInterface $logger) {
        if(OC_Util::getVersion()[0] < 32) {
            parent::__construct($userDisplayName, $logger);
        } else {
            parent::__construct($userDisplayName, $config, $logger);
        }
    }
}