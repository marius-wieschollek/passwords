<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Settings;

use OCA\Passwords\Services\ConfigurationService;

/**
 * Class DomainSettingsHelper
 *
 * @package OCA\Passwords\Helper\Settings
 */
class DomainSettingsHelper {

    /**
     * @var null|string
     */
    protected ?string $userId;

    /**
     * @var ConfigurationService
     */
    protected ConfigurationService $config;

    /**
     * DomainSettingsHelper constructor.
     *
     * @param null|string          $userId
     * @param ConfigurationService $config
     */
    public function __construct(?string $userId, ConfigurationService $config) {
        $this->config       = $config;
        $this->userId       = $userId;
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function get($key) {
        switch($key) {
            case 'mapping':
                return $this->getCurrentMapping();
        }

        return null;
    }

    /**
     * @return array
     */
    public function list(): array {
        return [
            'server.domain.mapping'         => $this->get('mapping')
        ];
    }

    /**
     * @return array
     */
    public function getCurrentMapping(): array {
        $useDefault = $this->config->getAppValue('domain/mapping/default/enabled', true);
        $customSettings = $this->config->getAppValue('domain/mapping/custom', "{\"data\":[]}");
        $customSettings = json_decode(stripslashes($customSettings));
        if ($useDefault) {
            return array_merge($customSettings->data, $this->getDefaultMappings());
        }
        return $customSettings->data;
    }

    /**
     * @return array
     */
    public function getDefaultMappings(): array {
        $array = array(
            array("apple.com", "icloud.com"),
            array("bing.com hotmail.com", "live.com", "microsoft.com", "msn.com", "windows.com", "windowsazure.com", "office.com", "skype.com", "azure.com"),
            array("youtube.com", "google.com", "gmail.com"),
            array("amazon.com", "aws.com", "amazon.co.uk", "amazon.ca", "amazon.de", "amazon.fr", "amazon.es", "amazon.it", "amazon.com.au"),
            array("gotomeeting.com", "citrixonline.com", "logmein.com"),
            array("mysql.com", "oracle.com"),
            array("alibaba.com", "aliexpress.com"),
            array("t-online.de", "telekom.de", "telekom.com"),
            array("autodesk.com", "tinkercad.com"),
            array("firefox.com", "mozilla.org"),
            array("facebook.com", "messenger.com"),
            array("mymerrill.com", "ml.com", "merrilledge.com"),
            array("ea.com", "origin.com"),
            array("steampowered.com", "steamcommunity.com"),
            array("go.com", "disney.com", "disney.de", "disneyplus.com", "d23.com", "shopdisney.com"),
            array("ebay.com", "ebay.at", "ebay.be", "ebay.co.uk", "ebay.de", "ebay.es", "ebay.fr", "ebay.it", "ebay.nl", "ebay.pl"),
            array("yahoo.com", "flickr.com"),
            array("askubuntu.com", "mathoverflow.net", "serverfault.com", "stackapps.com", "stackexchange.com", "stackoverflow.com", "superuser.com")
        );
        return $array;
    }
}