<?php

namespace OCA\Passwords\Migration\DatabaseCleanup;

use OCA\Passwords\Db\PasswordMapper;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\PasswordRevisionMapper;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Services\ConfigurationService;

/**
 * Class PasswordRevisionRepairHelper
 *
 * @package OCA\Passwords\Helper\Repair
 */
class PasswordRevisionMigration extends AbstractRevisionMigration {

    /**
     * @var string
     */
    protected $objectName = 'password';

    /**
     * PasswordRevisionRepairHelper constructor.
     *
     * @param PasswordRevisionMapper $revisionMapper
     * @param PasswordMapper         $modelMapper
     * @param ConfigurationService   $config
     */
    public function __construct(PasswordRevisionMapper $revisionMapper, PasswordMapper $modelMapper, ConfigurationService $config) {
        parent::__construct($revisionMapper, $modelMapper, $config);
    }

    /**
     * @param PasswordRevision|RevisionInterface $revision
     *
     * @return bool
     */
    public function checkRevision(RevisionInterface $revision): bool {
        $changed = false;
        if($revision->getCustomFields() === null && $revision->getCseType() === 'none') {
            $revision->setCustomFields('{}');
            $this->revisionMapper->update($revision);
            $changed = true;
        }

        return $changed || parent::checkRevision($revision);
    }
}