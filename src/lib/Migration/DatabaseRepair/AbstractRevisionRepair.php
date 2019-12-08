<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Migration\DatabaseRepair;

use OCA\Passwords\Db\AbstractMapper;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\Object\AbstractRevisionService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\Migration\IOutput;

/**
 * Class AbstractRepairHelper
 *
 * @package OCA\Passwords\Migration\DatabaseRepair
 */
abstract class AbstractRevisionRepair {

    /**
     * @var AbstractRevisionService
     */
    protected $revisionService;

    /**
     * @var AbstractMapper
     */
    protected $modelMapper;

    /**
     * @var EncryptionService
     */
    protected $encryption;

    /**
     * @var string
     */
    protected $objectName = 'abstract';

    /**
     * Run more time consuming repair jobs if enabled
     *
     * @var bool
     */
    protected $enhancedRepair = false;

    /**
     * AbstractRevisionRepair constructor.
     *
     * @param AbstractMapper          $modelMapper
     * @param AbstractRevisionService $revisionService
     * @param EncryptionService       $encryption
     * @param EnvironmentService      $environment
     */
    public function __construct(AbstractMapper $modelMapper, AbstractRevisionService $revisionService, EncryptionService $encryption, EnvironmentService $environment) {
        $this->modelMapper     = $modelMapper;
        $this->revisionService = $revisionService;
        $this->encryption      = $encryption;
        $this->enhancedRepair  = $environment->getRunType() === EnvironmentService::TYPE_CLI;
    }

    /**
     * @param IOutput $output
     *
     * @throws \Exception
     */
    public function run(IOutput $output): void {
        /** @var RevisionInterface[] $allRevisions */
        $allRevisions = $this->revisionService->findAll(false);

        $fixed = 0;
        $total = count($allRevisions);
        $output->info("Checking {$total} {$this->objectName} revisions");
        $output->startProgress($total);
        foreach($allRevisions as $revision) {
            try {
                if($this->repairRevision($revision)) $fixed++;
            } catch(\Throwable $e) {
                $output->warning(
                    "Failed to repair revision #{$revision->getUuid()}: {$e->getMessage()} in {$e->getFile()} line ".$e->getLine()
                );
            }
            $output->advance(1);
        }
        $output->finishProgress();
        $output->info("Fixed {$fixed} {$this->objectName} revisions");
    }

    /**
     * @param RevisionInterface $revision
     *
     * @return bool
     * @throws \Exception
     */
    protected function repairRevision(RevisionInterface $revision): bool {
        $fixed = false;

        try {
            $this->modelMapper->findByUuid($revision->getModel());
        } catch(DoesNotExistException | MultipleObjectsReturnedException $e) {
            $revision->setDeleted(true);
            $fixed = true;
        }

        if($this->enhancedRepair && $revision->getSseType() !== EncryptionService::SSE_ENCRYPTION_V2R1) {
            if(!$this->decryptOrDelete($revision)) $fixed = true;
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
        } catch(\Exception $e) {
            $revision->setDeleted(true);

            return false;
        }
    }
}