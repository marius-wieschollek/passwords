<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

/**
 * Class PasswordRevision
 *
 * @method string getUrl()
 * @method void setUrl(string $url)
 * @method string getUsername()
 * @method void setUsername(string $username)
 * @method string getPassword()
 * @method void setPassword(string $password)
 * @method string getNotes()
 * @method void setNotes(string $notes)
 * @method string getCustomFields()
 * @method void setCustomFields(string $customFields)
 * @method string getHash()
 * @method void setHash(string $hash)
 * @method string getFolder()
 * @method void setFolder(string $folder)
 * @method int getStatus()
 * @method void setStatus(int $status)
 * @method string getStatusCode()
 * @method void setStatusCode(string $statusCode)
 *
 * @package OCA\Passwords\Db
 */
class PasswordRevision extends AbstractRevision {

    /**
     * @var string
     */
    protected string $url;

    /**
     * @var string
     */
    protected string $username;

    /**
     * @var string
     */
    protected string $password;

    /**
     * @var string
     */
    protected string $notes;

    /**
     * @var string
     */
    protected string $hash;

    /**
     * @var string
     */
    protected string $folder;

    /**
     * @var int
     */
    protected int $status;

    /**
     * @var string
     */
    protected string $statusCode;

    /**
     * @var string|null
     */
    protected ?string $customFields;

    /**
     * Password constructor.
     */
    public function __construct() {
        $this->addType('url', 'string');
        $this->addType('hash', 'string');
        $this->addType('notes', 'string');
        $this->addType('label', 'string');
        $this->addType('folder', 'string');
        $this->addType('username', 'string');
        $this->addType('password', 'string');
        $this->addType('statusCode', 'string');
        $this->addType('customFields', 'string');

        $this->addType('status', 'integer');

        parent::__construct();
    }
}