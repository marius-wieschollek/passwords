<?php

namespace OCA\Passwords\Migration\DatabaseRepair;

use OCA\Passwords\Db\AbstractMapper;
use OCA\Passwords\Db\RevisionInterface;
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
     * @var string
     */
    protected $objectName = 'abstract';

    /**
     * AbstractRevisionRepair constructor.
     *
     * @param AbstractMapper          $modelMapper
     * @param AbstractRevisionService $revisionService
     */
    public function __construct(AbstractMapper $modelMapper, AbstractRevisionService $revisionService) {
        $this->modelMapper     = $modelMapper;
        $this->revisionService = $revisionService;
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

        if($revision->getFavourite()) {
            $revision->setFavorite(true);
            $revision->setFavourite(false);
            $fixed = true;
        }

        try {
            $this->modelMapper->findByUuid($revision->getModel());
        } catch(DoesNotExistException | MultipleObjectsReturnedException $e) {
            $revision->setDeleted(true);
            $fixed = true;
        }

        if($fixed) $this->revisionService->save($revision);

        return $fixed;
    }
}