<?php

namespace OCA\Passwords\Migration\DatabaseRepair;

use OCA\Passwords\Db\PasswordMapper;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Services\Object\PasswordRevisionService;

/**
 * Class PasswordRevisionRepairHelper
 *
 * @package OCA\Passwords\Migration\DatabaseRepair
 */
class PasswordRevisionRepair extends AbstractRevisionRepair {

    /**
     * @var string
     */
    protected $objectName = 'password';

    /**
     * PasswordRevisionRepair constructor.
     *
     * @param PasswordMapper          $modelMapper
     * @param PasswordRevisionService $revisionService
     */
    public function __construct(PasswordMapper $modelMapper, PasswordRevisionService $revisionService) {
        parent::__construct($modelMapper, $revisionService);
    }

    /**
     * @param PasswordRevision|RevisionInterface $revision
     *
     * @return bool
     * @throws \Exception
     */
    public function repairRevision(RevisionInterface $revision): bool {
        $fixed = false;
        if($revision->getCustomFields() === null && $revision->getCseType() === 'none') {
            $revision->setCustomFields('{}');
            $this->revisionService->save($revision);
            $fixed = true;
        }

        return $fixed || parent::repairRevision($revision);
    }
}