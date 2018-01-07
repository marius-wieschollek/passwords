<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 28.12.17
 * Time: 12:42
 */

namespace OCA\Passwords\Migration\Legacy;

use OCA\Passwords\Db\Legacy\LegacyPassword;
use OCA\Passwords\Db\Legacy\LegacyPasswordMapper;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\PasswordTagRelationService;
use OCP\Migration\IOutput;
use stdClass;

/**
 * Class LegacyPasswordMigration
 *
 * @package OCA\Passwords\Migration
 */
class LegacyPasswordMigration {
    /**
     * @var LegacyPasswordMapper
     */
    protected $passwordMapper;

    /**
     * @var DecryptionModule
     */
    protected $decryptionModule;

    /**
     * @var PasswordService
     */
    protected $passwordService;

    /**
     * @var PasswordRevisionService
     */
    protected $passwordRevisionService;

    /**
     * @var PasswordTagRelationService
     */
    protected $passwordTagRelationService;

    /**
     * @var TagRevision[]
     */
    protected $tags;

    /**
     * LegacyDatabaseMigration constructor.
     *
     * @param PasswordService            $passwordService
     * @param DecryptionModule           $decryptionModule
     * @param LegacyPasswordMapper       $passwordMapper
     * @param PasswordRevisionService    $passwordRevisionService
     * @param PasswordTagRelationService $passwordTagRelationService
     */
    public function __construct(
        PasswordService $passwordService,
        DecryptionModule $decryptionModule,
        LegacyPasswordMapper $passwordMapper,
        PasswordRevisionService $passwordRevisionService,
        PasswordTagRelationService $passwordTagRelationService
    ) {
        $this->passwordMapper             = $passwordMapper;
        $this->decryptionModule           = $decryptionModule;
        $this->passwordService            = $passwordService;
        $this->passwordRevisionService    = $passwordRevisionService;
        $this->passwordTagRelationService = $passwordTagRelationService;
    }

    /**
     * @param IOutput $output
     * @param array   $tags
     */
    public function migratePasswords(IOutput $output, array $tags): void {
        $passwords  = $this->passwordMapper->findAll();
        $this->tags = $tags;

        $count = count($passwords);
        $output->info("Migrating Passwords (total: {$count})");
        $output->startProgress($count);
        foreach ($passwords as $password) {
            try {
                $this->migratePassword($password);
            } catch (\Throwable $e) {
                $output->warning(
                    "Failed migrating password #{$password->getId()}: {$e->getMessage()} in {$e->getFile()} line ".$e->getLine()
                );
            }
            $output->advance(1);
        }
        $output->finishProgress();
    }

    /**
     * @param LegacyPassword $password
     *
     * @throws \OCA\Passwords\Exception\ApiException
     * @throws \Exception
     */
    protected function migratePassword(LegacyPassword $password): void {
        $key = $this->decryptionModule->makeKey($password->getUserId(), $password->getWebsite());

        $prData = $this->decryptionModule->decrypt($password->getProperties(), $key);
        $pwData = $this->decryptionModule->decrypt($password->getPass(), $key);

        $properties = $this->parseProperties($prData);

        $passwordModel    = $this->passwordService->create();
        $passwordRevision = $this->passwordRevisionService->createRevision(
            $passwordModel->getUuid(),
            $pwData,
            $this->getUsername($properties),
            EncryptionService::CSE_ENCRYPTION_NONE,
            '',
            $this->getLabel($properties, $password),
            $this->getUrl($properties, $password),
            $this->getNotes($properties, $password, $key),
            FolderService::BASE_FOLDER_UUID,
            false,
            $this->getTrashed($properties),
            false
        );

        $timestamp = strtotime($properties->datechanged);

        $passwordRevision->setUserId($password->getUserId());
        $passwordRevision->setCreated($timestamp);
        $passwordRevision->setUpdated($timestamp);
        $this->passwordRevisionService->save($passwordRevision);

        $passwordModel->setUserId($password->getUserId());
        $passwordModel->setRevision($passwordRevision->getUuid());
        $passwordModel->setCreated($timestamp);
        $passwordModel->setUpdated($timestamp);
        $this->passwordService->save($passwordModel);

        $this->convertCategory($passwordRevision, $properties);
    }

    /**
     * @param string $properties
     *
     * @return mixed
     * @throws \Exception
     */
    protected function parseProperties(string $properties): stdClass {
        $quot = '#Q#U#O#T#E#';

        $properties = substr($properties, 1, strlen($properties) - 2);

        $properties = str_replace("\\", "\\\\", $properties);
        $properties = str_replace("\n", "\\n", $properties);
        $properties = str_replace("\t", "\\t", $properties);
        $properties = str_replace('", ,', '","', $properties);

        $properties = str_replace('", "', "$quot,$quot", $properties);
        $properties = str_replace('" : "', "$quot:$quot", $properties);
        $properties = str_replace('": "', "$quot:$quot", $properties);
        $properties = str_replace('"', '\"', $properties);
        $properties = str_replace($quot, '"', $properties);

        $object = json_decode('{"'.$properties.'"}');

        if(gettype($object) !== 'object') {
            throw new \Exception('Invalid JSON data found');
        }

        return $object;
    }

    /**
     * @param stdClass $properties
     *
     * @return string
     */
    protected function getUsername(stdClass $properties): string {
        return trim($properties->loginname);
    }

    /**
     * @param stdClass       $properties
     * @param LegacyPassword $password
     *
     * @return string
     */
    protected function getUrl(stdClass $properties, LegacyPassword $password): ?string {
        $url = $properties->address;
        if(empty($url) && !empty($password->getWebsite())) {
            $url = 'http://'.$password->getWebsite();
        }
        if(filter_var($url, FILTER_VALIDATE_URL) === false) {
            return '';
        }

        return $url;
    }

    /**
     * @param stdClass       $properties
     * @param LegacyPassword $password
     *
     * @return string
     */
    protected function getLabel(stdClass $properties, LegacyPassword $password): string {
        $url = $this->getUrl($properties, $password);

        if($url === '' && !empty($password->getWebsite())) {
            return $password->getWebsite();
        }

        $host = parse_url($url, PHP_URL_HOST);
        $host = str_replace('www.', '', $host);
        $host = str_replace('www2.', '', $host);
        $host = str_replace('mail.', '', $host);
        $host = str_replace('email.', '', $host);
        $host = str_replace('login.', '', $host);
        $host = str_replace('signin.', '', $host);

        $label = $this->getUsername($properties);
        if(!empty($host)) {
            if(strpos($label, '@') !== false) {
                $label = substr($label, 0, strpos($label, '@'));
            }
            $label .= '@'.$host;
        }

        return $label;
    }

    /**
     * @param stdClass       $properties
     * @param LegacyPassword $password
     * @param string         $key
     *
     * @return bool|mixed|string
     * @throws \Exception
     */
    protected function getNotes(stdClass $properties, LegacyPassword $password, string $key): string {
        $notes = $properties->notes;
        if(empty($notes) && !empty($password->getNotes())) {
            $notes = $this->decryptionModule->decrypt($password->getNotes(), $key);
        }

        $notes = htmlspecialchars_decode($notes);
        $notes = str_replace('<br>', PHP_EOL, $notes);
        $notes = strip_tags($notes);

        return $notes;
    }

    /**
     * @param stdClass $properties
     *
     * @return bool
     */
    protected function getTrashed(stdClass $properties): bool {
        if(property_exists($properties, 'deleted')) {
            return $properties->deleted == true;
        }

        return false;
    }

    /**
     * @param PasswordRevision $passwordRevision
     * @param stdClass         $properties
     *
     * @throws \Exception
     */
    protected function convertCategory(PasswordRevision $passwordRevision, stdClass $properties) {
        if(empty($properties->category) || !isset($this->tags[ $properties->category ])) return;
        $tagRevision = $this->tags[ $properties->category ];

        if($passwordRevision->getUserId() !== $tagRevision->getUserId()) return;

        $revision = $this->passwordTagRelationService->create($passwordRevision, $tagRevision);
        $revision->setUserId($passwordRevision->getUserId());
        $this->passwordTagRelationService->save($revision);
    }
}