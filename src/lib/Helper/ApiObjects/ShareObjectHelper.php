<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\ApiObjects;

use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\Share;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\UserService;
use OCP\AppFramework\IAppContainer;
use OCP\IConfig;

/**
 * Class ShareObjectHelper
 *
 * @package Helper\ApiObjects
 */
class ShareObjectHelper extends AbstractObjectHelper {

    const LEVEL_PASSWORD = 'password';

    /**
     * @var null|string
     */
    protected $userId;

    /**
     * @var IConfig
     */
    protected $config;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * @var PasswordService
     */
    protected $passwordService;

    /**
     * @var PasswordObjectHelper
     */
    protected $passwordObjectHelper;

    /** @noinspection PhpMissingParentConstructorInspection */
    /**
     * AbstractObjectHelper constructor.
     *
     * @param null|string     $userId
     * @param IConfig         $config
     * @param UserService     $userService
     * @param IAppContainer   $container
     * @param PasswordService $passwordService
     */
    public function __construct(
        ?string $userId,
        IConfig $config,
        UserService $userService,
        IAppContainer $container,
        PasswordService $passwordService
    ) {
        $this->userId          = $userId;
        $this->config          = $config;
        $this->container       = $container;
        $this->userService     = $userService;
        $this->passwordService = $passwordService;
    }

    /**
     * @param EntityInterface|Share $share
     * @param string                $level
     * @param array                 $filter
     *
     * @return array|null
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \OCP\AppFramework\QueryException
     */
    public function getApiObject(
        EntityInterface $share,
        string $level = self::LEVEL_MODEL,
        $filter = []
    ): ?array {

        if(!$this->filter($share, $filter)) return null;

        $detailLevel = explode('+', $level);
        $object      = [];
        if(in_array(self::LEVEL_MODEL, $detailLevel)) {
            $object = $this->getModel($share);
        }
        if(in_array(self::LEVEL_PASSWORD, $detailLevel)) {
            $object = $this->getPassword($object);
        }

        return $object;
    }

    /**
     * @param Share $share
     *
     * @return array
     */
    protected function getModel(Share $share): array {
        $password = $this->userId === $share->getUserId() ? $share->getSourcePassword():$share->getTargetPassword();

        $shareable = $share->isShareable();
        if($this->userId !== $share->getUserId() && !$this->isReShareable()) $shareable = false;

        return [
            'id'            => $share->getUuid(),
            'created'       => $share->getCreated(),
            'updated'       => $share->getUpdated(),
            'expires'       => $share->getExpires(),
            'editable'      => $share->isEditable(),
            'shareable'     => $shareable,
            'password'      => $password,
            'updatePending' => $share->isSourceUpdated() || $share->isTargetUpdated(),
            'owner'         => [
                'id'   => $share->getUserId(),
                'name' => $this->userService->getUserName($share->getUserId())
            ],
            'receiver'      => [
                'id'   => $share->getReceiver(),
                'name' => $this->userService->getUserName($share->getReceiver())
            ]
        ];
    }

    /**
     * @param array $object
     *
     * @return array
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \OCP\AppFramework\QueryException
     */
    protected function getPassword(array $object): array {
        $objectHelper       = $this->getPasswordObjectHelper();
        $password           = $this->passwordService->findByUuid($object['password']);
        $object['password'] = $objectHelper->getApiObject($password);

        return $object;
    }

    /**
     * @return PasswordObjectHelper
     * @throws \OCP\AppFramework\QueryException
     */
    protected function getPasswordObjectHelper(): PasswordObjectHelper {
        if(!$this->passwordObjectHelper) {
            $this->passwordObjectHelper = $this->container->query(PasswordObjectHelper::class);
        }

        return $this->passwordObjectHelper;
    }

    /**
     * @return bool
     */
    protected function isReShareable(): bool {
        return $this->config->getAppValue('core', 'shareapi_allow_resharing', 'yes') === 'yes';
    }
}