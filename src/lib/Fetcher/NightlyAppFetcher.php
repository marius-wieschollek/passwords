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
        $this->fileName  = 'apps.json';
        $this->setEndpoint();
        $this->compareVersion = $compareVersion;
    }

    /**
     * Returns the array with the apps on the appstore server
     *
     * @return array
     */
    public function get() {
        $this->dbUpdated = false;
        try {
            $rootFolder = $this->appData->getFolder('/');
            $file       = $rootFolder->getFile($this->fileName);

            $eTag = $this->config->getAppValue('passwords', 'nightly/etag', '');
            if($eTag !== $file->getETag()) {
                $file->delete();
            } else {
                $json = json_decode($file->getContent(), true);
                if(is_array($json)) {
                    $json['timestamp'] = $file->getMTime();
                    $file->putContent(json_encode($json));
                    $file->write();
                }
            }
        } catch(\Exception $e) {
            $eTag = '';
        }

        $result = parent::get();

        try {
            $rootFolder = $this->appData->getFolder('/');

            $file = $rootFolder->getFile($this->fileName);
            $json = json_decode($file->getContent(), true);

            $json['timestamp'] = strtotime('+1 day');
            $file->putContent(json_encode($json));
            $file->write();

            $this->config->setAppValue('passwords', 'nightly/etag', $file->getETag());
            $this->dbUpdated = $eTag !== $file->getETag();
        } catch(\Exception $e) {
        }

        return $result;
    }

    /**
     *
     */
    public function clearDb(): void {
        try {
            $rootFolder = $this->appData->getFolder('/');
            $file       = $rootFolder->getFile($this->fileName);
            $file->delete();
            $this->config->deleteAppValue('passwords', 'nightly/etag');
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
        $response = parent::fetch($ETag, $content);
        $this->config->setAppValue('passwords', 'nightly/etag', $response['ETag']);

        foreach($response['data'] as $dataKey => $app) {
            $releases = [];

            // Filter all compatible releases
            foreach($app['releases'] as $release) {
                // Exclude all nightly and pre-releases
                if($this->releaseMatchesStabilityRequirements($release, $app['id'])) {
                    // Exclude all versions not compatible with the current version
                    try {
                        $versionParser = new VersionParser();
                        $version       = $versionParser->getVersion($release['rawPlatformVersionSpec']);
                        $ncVersion     = $this->getVersion();
                        $min           = $version->getMinimumVersion();
                        $max           = $version->getMaximumVersion();
                        $minFulfilled  = $this->compareVersion->isCompatible($ncVersion, $min, '>=');
                        $maxFulfilled  = $max !== '' &&
                                         $this->compareVersion->isCompatible($ncVersion, $max, '<=');
                        if($minFulfilled && $maxFulfilled) {
                            $releases[] = $release;
                        }
                    } catch(\InvalidArgumentException $e) {
                        $this->logger->logException($e, ['app' => 'appstoreFetcher', 'level' => ILogger::WARN]);
                    }
                }
            }

            // Get the highest version
            $versions = [];
            foreach($releases as $release) {
                $versions[] = $release['version'];
            }
            usort($versions, 'version_compare');
            $versions   = array_reverse($versions);
            $compatible = false;
            if(isset($versions[0])) {
                $highestVersion = $versions[0];
                foreach($releases as $release) {
                    if((string) $release['version'] === (string) $highestVersion) {
                        $compatible                               = true;
                        $response['data'][ $dataKey ]['releases'] = [$release];
                        break;
                    }
                }
            }
            if(!$compatible) {
                unset($response['data'][ $dataKey ]);
            }
        }

        $response['data'] = array_values($response['data']);

        return $response;
    }

    /**
     * @param $release
     * @param $app
     *
     * @return bool
     */
    public function releaseMatchesStabilityRequirements($release, $app): bool {
        $nightlyApps = $this->config->getSystemValue('allowNightlyUpdates', []);

        return ($release['isNightly'] === false && strpos($release['version'], '-') === false) || in_array($app, $nightlyApps);
    }

    /**
     *
     */
    private function setEndpoint() {
        $versionArray      = explode('.', $this->getVersion());
        $this->endpointUrl = sprintf(
            'https://apps.nextcloud.com/api/v1/platform/%d.%d.%d/apps.json',
            $versionArray[0],
            $versionArray[1],
            $versionArray[2]
        );
    }

    /**
     * @param string $version
     * @param string $fileName
     */
    public function setVersion(string $version, string $fileName = 'apps.json') {
        parent::setVersion($version);
        $this->fileName = $fileName;
        $this->setEndpoint();
    }
}