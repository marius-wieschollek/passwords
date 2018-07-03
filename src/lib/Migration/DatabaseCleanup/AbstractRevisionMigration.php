<?php

namespace OCA\Passwords\Migration\DatabaseCleanup;

use OCA\Passwords\Db\AbstractMapper;
use OCA\Passwords\Db\AbstractRevisionMapper;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Services\ConfigurationService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

/**
 * Class AbstractRepairHelper
 *
 * @package OCA\Passwords\Helper\Repair
 */
abstract class AbstractRevisionMigration {

    /**
     * @var AbstractRevisionMapper
     */
    protected $revisionMapper;

    /**
     * @var AbstractMapper
     */
    protected $modelMapper;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var string
     */
    protected $objectName = 'abstract';

    /**
     * @var null
     */
    protected $databaseVersion = null;

    /**
     * AbstractRepairHelper constructor.
     *
     * @param AbstractRevisionMapper $revisionMapper
     * @param AbstractMapper         $modelMapper
     * @param ConfigurationService   $config
     */
    public function __construct(
        AbstractRevisionMapper $revisionMapper,
        AbstractMapper $modelMapper,
        ConfigurationService $config
    ) {
        $this->revisionMapper = $revisionMapper;
        $this->modelMapper    = $modelMapper;
        $this->config         = $config;
    }

    /**
     * @param \OCP\Migration\IOutput $output
     */
    public function run(\OCP\Migration\IOutput $output): void {
        /** @var RevisionInterface[] $allRevisions */
        $allRevisions = $this->revisionMapper->findAll();

        $fixedRevisions = 0;
        $count          = count($allRevisions);
        $output->info("Checking {$count} {$this->objectName} revisions");
        $output->startProgress($count);
        foreach($allRevisions as $revision) {
            try {
                if($this->checkRevision($revision)) {
                    $fixedRevisions++;
                }
            } catch(\Throwable $e) {
                $output->warning(
                    "Failed to repair revision #{$revision->getUuid()}: {$e->getMessage()} in {$e->getFile()} line ".$e->getLine()
                );
            }
            $output->advance(1);
        }
        $output->finishProgress();
        $output->info("Fixed {$fixedRevisions} {$this->objectName} revisions");
    }

    /**
     * @param RevisionInterface $revision
     *
     * @return bool
     */
    protected function checkRevision(RevisionInterface $revision): bool {
        $changed = false;

        if($revision->getFavourite()) {
            $revision->setFavorite(true);
            $revision->setFavourite(false);
            $changed = true;
        }

        try {
            $this->modelMapper->findByUuid($revision->getModel());
        } catch(DoesNotExistException | MultipleObjectsReturnedException $e) {
            $revision->setDeleted(true);
            $changed = true;
        }

        if($changed) $this->revisionMapper->update($revision);

        return $changed;
    }
}