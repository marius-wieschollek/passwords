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

use Exception;
use OC\App\AppStore\Fetcher\Fetcher;
use OC\App\AppStore\Version\VersionParser;
use OC\App\CompareVersion;
use OC\Files\AppData\Factory;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\ILogger;
use OCP\Support\Subscription\IRegistry;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class NightlyAppFetcher
 *
 * @package OCA\Passwords\Fetcher
 */
class NightlyAppFetcher extends Fetcher {

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $psrLogger;

    /**
     * @var CompareVersion
     */
    protected CompareVersion $compareVersion;

    /**
     * @var bool
     */
    protected bool $ignoreMaxVersion;

    /**
     * @var bool
     */
    protected bool $dbUpdated;

    /**
     * @param Factory         $appDataFactory
     * @param IClientService  $clientService
     * @param ITimeFactory    $timeFactory
     * @param IConfig         $config
     * @param CompareVersion  $compareVersion
     * @param LoggerInterface $logger
     */
    public function __construct(
        Factory $appDataFactory,
        IClientService $clientService,
        ITimeFactory $timeFactory,
        IConfig $config,
        CompareVersion $compareVersion,
        LoggerInterface $logger,
        IRegistry $registry
    ) {

        $parentLogger = $logger;

        parent::__construct(
            $appDataFactory,
            $clientService,
            $timeFactory,
            $config,
            $parentLogger,
            $registry
        );

        $this->dbUpdated        = false;
        $this->fileName         = 'apps_nightly.json';
        $this->endpointName     = 'apps.json';
        $this->ignoreMaxVersion = true;
        $this->psrLogger        = $logger;
        $this->compareVersion   = $compareVersion;
    }

    /**
     * Returns the array with the apps on the appstore server
     *
     * @param bool $allowUnstable
     *
     * @return array
     */
    public function get($allowUnstable = false) {
        $this->dbUpdated = false;

        $eTag   = $this->prepareAppDbForUpdate();
        $result = parent::get($allowUnstable);
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
        } catch(Exception $e) {
            $this->logException($e);
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
     * @throws Exception
     */
    protected function fetch($ETag, $content, $allowUnstable = false) {
        $json = parent::fetch($ETag, $content);

        if(!isset($json['data'])) return $json;

        foreach($json['data'] as $dataKey => $app) {
            $latest = null;
            if(empty($app['releases'])) continue;
            foreach($app['releases'] as $release) {
                if(($latest === null || version_compare($latest['version'], $release['version']) < 0) &&
                   $this->releaseAllowedInChannel($release, $app['id'], $allowUnstable) &&
                   $this->checkNextcloudRequirements($release) &&
                   $this->checkPhpRequirements($release)) {
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
    public function setVersion(string $version, string $fileName = 'apps.json', bool $ignoreMaxVersion = true) {
        parent::setVersion($version);
        $this->ignoreMaxVersion = $ignoreMaxVersion;
    }

    /**
     * @param $release
     * @param $app
     * @param $allowUnstable
     *
     * @return bool
     */
    protected function releaseAllowedInChannel($release, $app, $allowUnstable): bool {
        $isPreRelease     = strpos($release['version'], '-');
        $allowNightly     = $allowUnstable|| $this->getChannel() === 'daily' || $app === 'passwords';
        $allowPreReleases = $this->getChannel() === 'beta' || $allowNightly;

        return (!$isPreRelease && !$release['isNightly']) || ($allowNightly && $release['isNightly']) || ($allowPreReleases && $isPreRelease);
    }

    /**
     * @return string
     */
    protected function prepareAppDbForUpdate(): string {
        try {
            $rootFolder = $this->appData->getFolder('/');
            if(!$rootFolder->fileExists($this->fileName)) return '';
            $file = $rootFolder->getFile($this->fileName);

            return $file->getETag();
        } catch(Exception $e) {
            $this->logger->emergency($e, ['app' => 'nightlyAppstoreFetcher', 'level' => ILogger::WARN]);

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
            if($rootFolder->fileExists('apps.json')) {
                $appDb = $rootFolder->getFile('apps.json');
            } else {
                $appDb = $rootFolder->newFile('apps.json');
            }

            if($nightlyEtag !== $nightlyDb->getETag() || $appEtag !== $appDb->getETag()) {
                $json            = json_decode($nightlyDb->getContent());
                $json->timestamp = strtotime('+1 day');
                $appDb->putContent(json_encode($json));

                $this->config->setAppValue('passwords', 'nightly/etag', $appDb->getETag());

                $this->dbUpdated = true;
            }
        } catch(Exception $e) {
            $this->logException($e);
        }
    }

    /**
     * @param $release
     *
     * @return bool
     */
    protected function checkNextcloudRequirements($release): bool {
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
        } catch(Throwable $e) {
            $this->logException($e);
        }

        return false;
    }

    /**
     * @param $release
     *
     * @return bool
     */
    protected function checkPhpRequirements($release): bool {
        try {
            if(($release['rawPhpVersionSpec'] ?? '*') === '*') return true;
            $versionParser = new VersionParser();
            $phpVersion    = $versionParser->getVersion($release['rawPhpVersionSpec']);
            $minPhpVersion = $phpVersion->getMinimumVersion();
            $maxPhpVersion = $phpVersion->getMaximumVersion();

            $minPhpFulfilled = $minPhpVersion === '' || $this->compareVersion->isCompatible(
                    PHP_VERSION,
                    $minPhpVersion,
                    '>='
                );
            $maxPhpFulfilled = $maxPhpVersion === '' || $this->compareVersion->isCompatible(
                    PHP_VERSION,
                    $maxPhpVersion,
                    '<='
                );

            return $minPhpFulfilled && $maxPhpFulfilled;
        } catch(Throwable $e) {
            $this->logException($e);
        }

        return false;
    }

    /**
     * @param Throwable $exception
     * @param array     $context
     */
    protected function logException(Throwable $exception, array $context = []): void {
        $context['app']       = 'nightlyAppstoreFetcher';
        $context['exception'] = $exception;
        $this->psrLogger->emergency($exception->getMessage(), $context);
    }
}