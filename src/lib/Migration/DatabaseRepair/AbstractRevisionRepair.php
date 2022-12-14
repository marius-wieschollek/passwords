<?php
/*
 * @copyright 2020 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Migration\DatabaseRepair;

use Exception;
use JetBrains\PhpStorm\Pure;
use OCA\Passwords\Db\AbstractMapper;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\Object\AbstractRevisionService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\Migration\IOutput;
use Throwable;

/**
 * Class AbstractRepairHelper
 *
 * @package OCA\Passwords\Migration\DatabaseRepair
 */
abstract class AbstractRevisionRepair {

    /**
     * @var AbstractRevisionService
     */
    protected AbstractRevisionService $revisionService;

    /**
     * @var AbstractMapper
     */
    protected AbstractMapper $modelMapper;

    /**
     * @var EncryptionService
     */
    protected EncryptionService $encryption;

    /**
     * @var string
     */
    protected string $objectName = 'abstract';

    /**
     * Run more time consuming repair jobs if enabled
     *
     * @var bool
     */
    protected bool $enhancedRepair = false;

    /**
     * AbstractRevisionRepair constructor.
     *
     * @param AbstractMapper          $modelMapper
     * @param AbstractRevisionService $revisionService
     * @param EncryptionService       $encryption
     * @param EnvironmentService      $environment
     */
    #[Pure] public function __construct(AbstractMapper $modelMapper, AbstractRevisionService $revisionService, EncryptionService $encryption, EnvironmentService $environment) {
        $this->modelMapper     = $modelMapper;
        $this->revisionService = $revisionService;
        $this->encryption      = $encryption;
        $this->enhancedRepair  = $environment->getRunType() === EnvironmentService::TYPE_CRON || $environment->getRunType() === EnvironmentService::TYPE_CLI;
    }

    /**
     * @param IOutput $output
     *
     * @throws Exception
     */
    public function run(IOutput $output): void {
        $allRevisions = $this->revisionService->findAll(false);

        $fixed = 0;
        $total = count($allRevisions);
        $output->info("Checking {$total} {$this->objectName} revisions");
        $output->startProgress($total);
        foreach($allRevisions as $revision) {
            try {
                if($this->repairRevision($revision)) $fixed++;
            } catch(Throwable $e) {
                $output->warning(
                    "Failed to repair revision #{$revision->getUuid()}: {$e->getMessage()} in {$e->getFile()} line ".$e->getLine()
                );
            }
            $output->advance();
        }
        $output->finishProgress();
        $output->info("Fixed {$fixed} {$this->objectName} revisions");
    }

    /**
     * @param RevisionInterface $revision
     *
     * @return bool
     * @throws Exception
     */
    protected function repairRevision(RevisionInterface $revision): bool {
        $fixed = false;

        try {
            $this->modelMapper->findByUuid($revision->getModel());
        } catch(DoesNotExistException | MultipleObjectsReturnedException $e) {
            $revision->setDeleted(true);
            $fixed = true;
        }

        $fixed = $this->checkEmptySecretDecryption($revision, $fixed);

        /**
         * If it's not SSEv2 and can't be decrypted, delete it
         */
        if($this->enhancedRepair && $revision->getSseType() !== EncryptionService::SSE_ENCRYPTION_V2R1) {
            if(!$this->decryptOrDelete($revision)) $fixed = true;
        }

        /**
         * If it's SSEv1r1, try an upgrade
         */
        if($this->enhancedRepair && $revision->getSseType() === EncryptionService::SSE_ENCRYPTION_V1R1) {
            if($this->decryptOrDelete($revision)) {
                $revision->setSseType(EncryptionService::SSE_ENCRYPTION_V1R2);
                $fixed = true;
            }
        }

        /**
         * If it has no encryption, try adding one
         */
        if($this->enhancedRepair && $revision->getSseType() === EncryptionService::SSE_ENCRYPTION_NONE && $revision->getCseType() === EncryptionService::CSE_ENCRYPTION_NONE) {
            if($this->decryptOrDelete($revision)) {
                $revision->setSseType(EncryptionService::SSE_ENCRYPTION_V1R2);
                $fixed = true;
            }
        }

        if($fixed) $this->revisionService->save($revision);

        return $fixed;
    }

    /**
     * Decrypt the revision or mark it as deleted
     *
     * @param RevisionInterface $revision
     *
     * @return bool
     */
    protected function decryptOrDelete(RevisionInterface $revision): bool {
        try {
            $this->encryption->decrypt($revision);

            return true;
        } catch(Exception $e) {
            $revision->setDeleted(true);

            return false;
        }
    }

    /**
     * @param RevisionInterface $revision
     * @param bool              $fixed
     *
     * @return bool
     */
    protected function checkEmptySecretDecryption(RevisionInterface $revision, bool $fixed): bool {
        /**
         * Some servers may have had an empty secret
         *
         * @see https://github.com/marius-wieschollek/passwords/issues/551
         */
        if($this->enhancedRepair && !$revision->_isDecrypted() && $revision->getSseType() === EncryptionService::SSE_ENCRYPTION_V1R2) {
            try {
                $this->encryption->decrypt($revision);
            } catch(Exception $e) {
                $revision->setSseType(EncryptionService::SSE_ENCRYPTION_V1R1);
                if($this->decryptOrDelete($revision)) return true;
            }
        }

        return $fixed;
    }
}