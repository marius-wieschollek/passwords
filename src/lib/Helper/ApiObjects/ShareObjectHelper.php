<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 08.01.18
 * Time: 10:34
 */

namespace OCA\Passwords\Helper\ApiObjects;

use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\Share;
use OCA\Passwords\Services\Object\PasswordService;
use OCP\AppFramework\IAppContainer;
use OCP\IUserManager;

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
     * @var IUserManager
     */
    protected $userManager;

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
     * @param IAppContainer   $container
     * @param IUserManager    $userManager
     * @param PasswordService $passwordService
     */
    public function __construct(
        ?string $userId,
        IAppContainer $container,
        IUserManager $userManager,
        PasswordService $passwordService
    ) {
        $this->userId          = $userId;
        $this->container       = $container;
        $this->userManager     = $userManager;
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
        $owner    = $this->userManager->get($share->getUserId());
        $receiver = $this->userManager->get($share->getReceiver());

        $password = $this->userId === $share->getUserId() ? $share->getSourcePassword():$share->getTargetPassword();

        return [
            'id'            => $share->getUuid(),
            'created'       => $share->getCreated(),
            'updated'       => $share->getUpdated(),
            'expires'       => $share->getExpires(),
            'editable'      => $share->isEditable(),
            'shareable'     => $share->isShareable(),
            'password'      => $password,
            'updatePending' => $share->isSourceUpdated() || $share->isTargetUpdated(),
            'owner'         => [
                'id'   => $owner->getUID(),
                'name' => $owner->getDisplayName()
            ],
            'receiver'      => [
                'id'   => $receiver->getUID(),
                'name' => $receiver->getDisplayName()
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
            $this->passwordObjectHelper = $this->container->query('PasswordObjectHelper');
        }

        return $this->passwordObjectHelper;
    }
}