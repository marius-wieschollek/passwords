<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

/**
 * Class FolderRevision
 *
 * @method string getLabel()
 * @method void setLabel(string $title)
 * @method string getParent()
 * @method void setParent(string $parent)
 *
 * @package OCA\Passwords\Db
 */
class FolderRevision extends AbstractRevisionEntity {

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $parent;

    /**
     * Folder constructor.
     */
    public function __construct() {
        $this->addType('label', 'string');
        $this->addType('parent', 'string');

        parent::__construct();
    }
}