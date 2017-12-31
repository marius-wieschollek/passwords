<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.12.17
 * Time: 14:29
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\ModelInterface;
use OCA\Passwords\Db\Share;
use OCA\Passwords\Db\ShareMapper;

/**
 * Class ShareService
 *
 * @package      OCA\Passwords\Services\Object
 * @noinspection PhpSignatureMismatchDuringInheritanceInspection
 */
class ShareService extends AbstractModelService {

    /**
     * @var ShareMapper
     */
    protected $mapper;

    /**
     * @var string
     */
    protected $class = Share::class;

    /**
     * @param string $passwordUuid
     *
     * @return Share[]
     *
     * @throws \Exception
     */
    public function findByPassword(string $passwordUuid): array {
        return $this->mapper->findAllMatching(['password_id', $passwordUuid]);
    }

    /**
     * @param string $userId
     *
     * @param string $passwordUuid
     *
     * @return EntityInterface|Share
     *
     * @throws \Exception
     */
    public function findByReceiverAndPassword(string $userId, string $passwordUuid): Share {
        return $this->mapper->findOneMatching(
            [
                ['receiver_id', $userId],
                ['password_id', $passwordUuid]
            ]
        );
    }

    /**
     * @param string $passwordId
     * @param string $receiverId
     * @param string $type
     *
     * @return Share|ModelInterface
     * @throws \Exception
     */
    public function create(string $passwordId = '', $receiverId = '', $type = ''): ModelInterface {
        $model = $this->createModel($passwordId, $receiverId, $type);
        $this->hookManager->emit($this->class, 'postCreate', [$model]);

        return $model;
    }

    /**
     * @param string $passwordId
     * @param string $receiverId
     * @param string $type
     *
     * @return Share|ModelInterface
     * @throws \Exception
     */
    protected function createModel(string $passwordId = '', $receiverId = '', $type = ''): ModelInterface {
        if($passwordId == '') throw new \Exception('Invalid password id');
        if($type == '') throw new \Exception('Invalid share type');

        /** @var Share $model */
        $model = new Share();
        $model->setDeleted(false);
        $model->setUserId($this->userId);
        $model->setUuid($this->generateUuidV4());
        $model->setCreated(time());
        $model->setUpdated(time());

        $model->setPasswordId($passwordId);
        $model->setReceiverId($receiverId);
        $model->setType($type);

        return $model;
    }
}