<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\ApiObjects;

use Exception;
use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\ModelInterface;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\AbstractRevisionService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\IAppContainer;

/**
 * Class AbstractObjectHelper
 *
 * @package OCA\Passwords\Helper\ApiObjects
 */
abstract class AbstractObjectHelper {

    const LEVEL_MODEL     = 'model';
    const LEVEL_REVISIONS = 'revisions';

    const OPERATOR_EQUALS            = 'eq';
    const OPERATOR_NOT_EQUALS        = 'ne';
    const OPERATOR_LESS              = 'lt';
    const OPERATOR_GREATER           = 'gt';
    const OPERATOR_EQUALS_OR_LESS    = 'le';
    const OPERATOR_EQUALS_OR_GREATER = 'ge';

    /**
     * @var IAppContainer
     */
    protected $container;

    /**
     * @var AbstractRevisionService
     */
    protected $revisionService;

    /**
     * @var EncryptionService
     */
    protected $encryptionService;

    /**
     * @var array
     */
    public static $filterOperators
        = [
            self::OPERATOR_EQUALS,
            self::OPERATOR_NOT_EQUALS,
            self::OPERATOR_LESS,
            self::OPERATOR_GREATER,
            self::OPERATOR_EQUALS_OR_LESS,
            self::OPERATOR_EQUALS_OR_GREATER
        ];

    /**
     * AbstractObjectHelper constructor.
     *
     * @param IAppContainer           $container
     * @param EncryptionService       $encryptionService
     * @param AbstractRevisionService $revisionService
     */
    public function __construct(
        IAppContainer $container,
        EncryptionService $encryptionService,
        AbstractRevisionService $revisionService
    ) {
        $this->container         = $container;
        $this->revisionService   = $revisionService;
        $this->encryptionService = $encryptionService;
    }

    /**
     * @param EntityInterface $model
     * @param string          $level
     * @param array           $filter
     *
     * @return array|null
     */
    abstract public function getApiObject(
        EntityInterface $model,
        string $level = self::LEVEL_MODEL,
        $filter = []
    ): ?array;

    /**
     * @param EntityInterface $model
     * @param array           $filter
     *
     * @return bool
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function matchesFilter(EntityInterface $model, $filter = []): bool {
        $revision = $this->revisionService->findByUuid($model->getRevision());

        return $this->filter($revision, $filter);
    }

    /**
     * @param EntityInterface $revision
     * @param array           $filter
     *
     * @return bool
     */
    protected function filter(EntityInterface $revision, array $filter) {
        if(empty($filter)) return true;

        foreach($filter as $key => $value) {
            $property = $revision->getProperty($key);
            if(!is_array($value) && $property != $value || is_array($value) && !$this->valueMatchesAdvancedFilter($value, $property)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param ModelInterface $model
     * @param array          $filters
     * @param bool           $decrypt
     *
     * @return null|RevisionInterface
     * @throws Exception
     */
    protected function getRevision(ModelInterface $model, array $filters, bool $decrypt = true): ?RevisionInterface {
        $revision = $this->revisionService->findByUuid($model->getRevision());
        if(!$this->filter($revision, $filters)) return null;

        if(!$decrypt) return $revision;

        return $this->encryptionService->decrypt($revision);
    }

    /**
     * @param $value
     * @param $property
     *
     * @return bool
     */
    protected function valueMatchesAdvancedFilter(array $filter, $property): bool {
        [$operator, $value] = $filter;
        if(($operator === self::OPERATOR_EQUALS && $property != $value) ||
           ($operator === self::OPERATOR_NOT_EQUALS && $property == $value) ||
           ($operator === self::OPERATOR_LESS && $property >= $value) ||
           ($operator === self::OPERATOR_GREATER && $property <= $value) ||
           ($operator === self::OPERATOR_EQUALS_OR_LESS && $property > $value) ||
           ($operator === self::OPERATOR_EQUALS_OR_GREATER && $property < $value)) {
            return false;
        }

        return true;
    }
}