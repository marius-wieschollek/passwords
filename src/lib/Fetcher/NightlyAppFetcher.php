<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author    Joas Schilling <coding@schilljs.com>
 * @author    Lukas Reschke <lukas@statuscode.ch>
 * @author    Morris Jobke <hey@morrisjobke.de>
 * @author    Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license   GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Passwords\Fetcher;

use OC\App\AppStore\Fetcher\Fetcher;
use OC\App\AppStore\Version\VersionParser;
use OC\App\CompareVersion;
use OC\Files\AppData\Factory;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\ILogger;

/**
 * Class AppFetcher
 *
 * @package OC\App\AppStore\Fetcher
 */
class NightlyAppFetcher extends Fetcher {

    /**
     * @var CompareVersion
     */
    protected $compareVersion;

    /**
     * @var bool
     */
    private $ignoreMaxVersion;

    /**
     * @var bool
     */
    protected $dbUpdated;

    /**
     * @param Factory        $appDataFactory
     * @param IClientService $clientService
     * @param ITimeFactory   $timeFactory
     * @param IConfig        $config
     * @param CompareVersion $compareVersion
     * @param ILogger        $logger
     */
    public function __construct(
        Factory $appDataFactory,
        IClientService $clientService,
        ITimeFactory $timeFactory,
        IConfig $config,
        CompareVersion $compareVersion,
        ILogger $logger
    ) {
        parent::__construct(
            $appDataFactory,
            $clientService,
            $timeFactory,
            $config,
            $logger
        );

        $this->dbUpdated = false;
        $this->fileName  = 'apps_nightly.json';
        $this->setEndpoint();
        $this->compareVersion   = $compareVersion;
        $this->ignoreMaxVersion = false;
    }

    /**
     * Returns the array with the apps on the appstore server
     *
     * @return array
     */
    public function get() {
        $this->dbUpdated = false;

        $eTag   = $this->prepareAppDbForUpdate();
        $result = parent::get();
        $this->updateAppDbAfterUpdate($eTag);

        return $result;
    }

    /**
     *
     */
    public function clearDb(): void {
        try {
            $this->config->deleteAppValue('passwords', 'nightly/etag');
            $rootFolder = $this->appData->getFolder('/');
            $file       = $rootFolder->getFile($this->fileName);
            $file->delete();
            $file = $rootFolder->getFile('apps.json');
            $file->delete();
        } catch(\Exception $e) {
        }
    }

    /**
     * @return bool
     */
    public function isDbUpdated(): bool {
        return $this->dbUpdated;
    }

    /**
     * Only returns the latest compatible app release in the releases array
     *
     * @param string $ETag
     * @param string $content
     *
     * @return array
     * @throws \Exception
     */
    protected function fetch($ETag, $content) {
        $json = parent::fetch($ETag, $content);

        foreach($json['data'] as $dataKey => $app) {
            $latest = null;
            if(empty($app['releases'])) continue;
            foreach($app['releases'] as $release) {
                if(($latest === null || version_compare($latest['version'], $release['version']) < 0) &&
                   $this->releaseAllowedInChannel($release, $app['id']) &&
                   $this->checkVersionRequirements($release)) {
                    $latest = $release;
                }
            }
            if($latest !== null) {
                $json['data'][ $dataKey ]['releases'] = [$latest];
            } else {
                unset($json['data'][ $dataKey ]);
            }
        }

        $json['data'] = array_values($json['data']);

        return $json;
    }

    /**
     * @param string $version
     * @param string $fileName
     * @param bool   $ignoreMaxVersion
     */
    public function setVersion(string $version, string $fileName = 'apps_nightly.json', bool $ignoreMaxVersion = true) {
        parent::setVersion($version);

        $this->ignoreMaxVersion = $ignoreMaxVersion;
        $this->fileName         = $fileName;
        $this->setEndpoint();
    }

    /**
     * @param $release
     * @param $app
     *
     * @return bool
     */
    protected function releaseAllowedInChannel($release, $app): bool {
        $isPreRelease     = strpos($release['version'], '-');
        $allowNightly     = $this->getChannel() === 'daily' || $app === 'passwords';
        $allowPreReleases = $this->getChannel() === 'beta' || $allowNightly;

        return ($allowNightly || $release['isNightly'] === false) || ($allowPreReleases || !$isPreRelease);
    }

    /**
     *
     */
    protected function setEndpoint() {
        $this->endpointUrl = $this->getEndpoint();
    }

    /**
     * @return string
     */
    protected function getEndpoint(): string {
        return $this->config->getSystemValue('appstoreurl', 'https://apps.nextcloud.com/api/v1').'/apps.json';
    }

    /**
     * @return string
     */
    protected function prepareAppDbForUpdate(): string {
        try {
            $rootFolder = $this->appData->getFolder('/');
            $file       = $rootFolder->getFile($this->fileName);

            return $file->getETag();
        } catch(\Exception $e) {
            return '';
        }
    }

    /**
     * @param $nightlyEtag
     */
    protected function updateAppDbAfterUpdate($nightlyEtag): void {
        try {
            $appEtag    = $this->config->getAppValue('passwords', 'nightly/etag', '');
            $rootFolder = $this->appData->getFolder('/');

            $nightlyDb = $rootFolder->getFile($this->fileName);
            $appDb     = $rootFolder->getFile('apps.json');

            if($nightlyEtag !== $nightlyDb->getETag() || $appEtag !== $appDb->getETag()) {
                $json            = json_decode($nightlyDb->getContent());
                $json->timestamp = strtotime('+1 day');
                $appDb->putContent(json_encode($json));

                $this->config->setAppValue('passwords', 'nightly/etag', $appDb->getETag());

                $this->dbUpdated = true;
            }
        } catch(\Exception $e) {
        }
    }

    /**
     * @param $release
     *
     * @return bool
     */
    protected function checkVersionRequirements($release): bool {
        try {
            $versionParser = new VersionParser();
            $version       = $versionParser->getVersion($release['rawPlatformVersionSpec']);
            $ncVersion     = $this->getVersion();
            $min           = $version->getMinimumVersion();
            $max           = $version->getMaximumVersion();
            $minFulfilled  = $this->compareVersion->isCompatible($ncVersion, $min, '>=');
            $maxFulfilled  = $max !== '' &&
                             $this->compareVersion->isCompatible($ncVersion, $max, '<=');

            return $minFulfilled && ($this->ignoreMaxVersion || $maxFulfilled);
        } catch(\Throwable $e) {
            $this->logger->logException($e, ['app' => 'nightlyAppstoreFetcher', 'level' => ILogger::WARN]);
        }

        return false;
    }
}