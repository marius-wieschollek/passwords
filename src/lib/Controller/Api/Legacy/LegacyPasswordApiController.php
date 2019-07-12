<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller\Api\Legacy;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\Tag;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\PasswordTagRelationService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCA\Passwords\Services\Object\TagService;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class LegacyPasswordApiController
 *
 * @package OCA\Passwords\Controller\Api\Legacy
 */
class LegacyPasswordApiController extends ApiController {

    const COMPATIBLE_SSE_ENCRYPTIONS = [EncryptionService::SSE_ENCRYPTION_V1R1, EncryptionService::SSE_ENCRYPTION_V1R2];

    /**
     * @var TagService
     */
    protected $tagService;

    /**
     * @var LoggingService
     */
    protected $loggingService;

    /**
     * @var PasswordService
     */
    protected $passwordService;

    /**
     * @var TagRevisionService
     */
    protected $tagRevisionService;

    /**
     * @var PasswordRevisionService
     */
    protected $passwordRevisionService;

    /**
     * @var PasswordTagRelationService
     */
    protected $passwordTagRelationService;

    /**
     * LegacyPasswordsApiController constructor.
     *
     * @param IRequest                   $request
     * @param TagService                 $tagService
     * @param LoggingService             $loggingService
     * @param PasswordService            $passwordService
     * @param TagRevisionService         $tagRevisionService
     * @param PasswordRevisionService    $passwordRevisionService
     * @param PasswordTagRelationService $passwordTagRelationService
     */
    public function __construct(
        IRequest $request,
        TagService $tagService,
        LoggingService $loggingService,
        PasswordService $passwordService,
        TagRevisionService $tagRevisionService,
        PasswordRevisionService $passwordRevisionService,
        PasswordTagRelationService $passwordTagRelationService
    ) {
        parent::__construct(
            Application::APP_NAME,
            $request,
            'GET, POST, DELETE, PUT, PATCH',
            'Authorization, Content-Type, Accept',
            1728000
        );
        $this->tagService                 = $tagService;
        $this->loggingService             = $loggingService;
        $this->passwordService            = $passwordService;
        $this->tagRevisionService         = $tagRevisionService;
        $this->passwordRevisionService    = $passwordRevisionService;
        $this->passwordTagRelationService = $passwordTagRelationService;
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function index(): JSONResponse {
        $counter   = 0;
        $passwords = new \stdClass();
        /** @var Password[] $models */
        $models = $this->passwordService->findAll();
        foreach($models as $model) {
            try {
                $password = $this->getPasswordObject($model);
            } catch(\Exception $e) {
                $this->loggingService->logException($e);

                continue;
            }
            if($password !== null) {
                $counter++;
                $passwords->{$counter} = $password;
            }
        }

        return new JSONResponse($passwords);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param int $id
     *
     * @return JSONResponse
     * @throws \Exception
     */
    public function show($id): JSONResponse {
        /** @var Password $password */
        $password = $this->passwordService->findByIdOrUuid($id);
        if($password === null) return new JSONResponse('Entity not found', 404);
        $password = $this->getPasswordObject($password);
        if($password === null) return new JSONResponse('Entity not found', 404);

        return new JSONResponse($password);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param $pass
     * @param $loginname
     * @param $address
     * @param $notes
     * @param $category
     *
     * @return mixed
     * @throws \OCA\Passwords\Exception\ApiException
     * @throws \Exception
     */
    public function create($pass, $loginname, $address, $notes, $category): JSONResponse {
        /** @var Password $model */
        $model   = $this->passwordService->create();
        $website = parse_url($address, PHP_URL_HOST);
        /** @var PasswordRevision $revision */
        $revision = $this->passwordRevisionService->create(
            $model->getUuid(),
            $pass, strval($loginname),
            '',EncryptionService::CSE_ENCRYPTION_NONE,
             '',
             strval($website).' – '.strval($loginname),
            strval($address), strval($notes),
            '[]',
            FolderService::BASE_FOLDER_UUID,
            time(), false, false, false
        );
        $this->passwordRevisionService->save($revision);
        $this->passwordService->setRevision($model, $revision);

        if($category !== null) {
            /** @var Tag $tag */
            $tag = $this->tagService->findByIdOrUuid($category);
            if($tag !== null && !$tag->isSuspended()) {
                /** @var TagRevision $tagRevision */
                $tagRevision = $this->tagRevisionService->findByUuid($tag->getRevision());
                $this->passwordTagRelationService->create($revision, $tagRevision);
            }
        }

        return new JSONResponse($this->getPasswordObject($model));
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param $id
     * @param $pass
     * @param $loginname
     * @param $address
     * @param $notes
     * @param $category
     * @param $deleted
     *
     * @return mixed
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCA\Passwords\Exception\ApiException
     * @throws \Exception
     */
    public function update($id, $pass, $loginname, $address, $notes, $category, $deleted): JSONResponse {
        /** @var Password $model */
        $model = $this->passwordService->findByIdOrUuid($id);
        if($model === null) return new JSONResponse('Entity not found', 404);
        if(!$model->isEditable()) return new JSONResponse('Entity not writable', 405);

        /** @var PasswordRevision $revision */
        $revision = $this->passwordRevisionService->findByUuid($model->getRevision(), true);
        if($revision->getCseType() !== EncryptionService::CSE_ENCRYPTION_NONE) return new JSONResponse('Unsupported Encryption Type', 400);
        if(!in_array($revision->getSseType(), self::COMPATIBLE_SSE_ENCRYPTIONS)) return new JSONResponse('Unsupported Encryption Type', 400);

        $edited = $revision->getPassword() === $pass ? $revision->getEdited():time();
        $label  = $revision->getLabel();
        if($label === parse_url($revision->getUrl(), PHP_URL_HOST).' – '.$revision->getUsername()) {
            $label = strval(parse_url($address, PHP_URL_HOST)).' – '.strval($loginname);
        }

        /** @var PasswordRevision $newRevision */
        $newRevision = $this->passwordRevisionService->create(
            $model->getUuid(), strval($pass), strval($loginname),
            '',
            EncryptionService::CSE_ENCRYPTION_NONE,
            '',
            $label,
            strval($address),
            strval($notes),
            $revision->getCustomFields(),
            $revision->getFolder(),
            $edited,
            $revision->isHidden(),
            $deleted == true,
            $revision->isFavorite()
        );
        $this->passwordRevisionService->save($newRevision);
        $this->passwordService->setRevision($model, $newRevision);

        $this->updatePasswordCategory($category, $model, $newRevision);

        return new JSONResponse($this->getPasswordObject($model));
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param int $id
     *
     * @return JSONResponse
     * @throws \Exception
     */
    public function destroy($id): JSONResponse {
        /** @var Password $model */
        $model = $this->passwordService->findByUuid($id);
        /** @var PasswordRevision $oldRevision */
        $oldRevision = $this->passwordRevisionService->findByUuid($model->getRevision());

        if($oldRevision->isTrashed()) {
            $this->passwordService->delete($model);

            return new JSONResponse(['id' => $model->getId()]);
        }

        /** @var PasswordRevision $newRevision */
        $newRevision = $this->passwordRevisionService->clone($oldRevision, ['trashed' => true]);
        $this->passwordRevisionService->save($newRevision);
        $this->passwordService->setRevision($model, $newRevision);

        return new JSONResponse($this->getPasswordObject($model));
    }

    /**
     * @param Password $password
     *
     * @return array|null
     * @throws \Exception
     */
    protected function getPasswordObject(Password $password): ?array {
        /** @var PasswordRevision $revision */
        $revision = $this->passwordRevisionService->findByUuid($password->getRevision(), true);

        if($revision->isHidden()) {
            return null;
        }
        if($revision->getCseType() !== EncryptionService::CSE_ENCRYPTION_NONE) {
            return null;
        }
        if(!in_array($revision->getSseType(), self::COMPATIBLE_SSE_ENCRYPTIONS)) {
            return null;
        }

        $tag      = $this->findCategoryForPassword($password);
        $category = $tag === null ? 0:$tag->getId();

        $properties = [
            'loginname'   => $revision->getUsername(),
            'address'     => $revision->getUrl(),
            'strength'    => (20 - $revision->getStatus() * 10),
            'length'      => strlen($revision->getPassword()),
            'lower'       => preg_match('/[a-z]+/', $revision->getPassword()),
            'upper'       => preg_match('/[A-Z]+/', $revision->getPassword()),
            'number'      => preg_match('/[0-9]+/', $revision->getPassword()),
            'special'     => preg_match('/[^a-zA-Z0-9]+/', $revision->getPassword()),
            'category'    => $category,
            'datechanged' => date("Y-m-d", $revision->getEdited()),
            'notes'       => $revision->getNotes()
        ];

        return [
            'id'            => $password->getId(),
            'name'        => $revision->getLabel(),
            'user_id'       => $password->getUserId(),
            'loginname'     => $revision->getUsername(),
            'pass'          => $revision->getPassword(),
            'website'       => parse_url($revision->getUrl(), PHP_URL_HOST),
            'address'       => $revision->getUrl(),
            'notes'         => $revision->getNotes(),
            'deleted'       => $revision->isTrashed(),
            'creation_date' => date("Y-m-d", $password->getCreated()),
            'properties'    => json_encode($properties)
        ];
    }

    /**
     * @param Password $password
     *
     * @return Tag
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    protected function findCategoryForPassword(Password $password): ?Tag {
        $tags = $this->tagService->findByPassword($password->getUuid());

        foreach($tags as $tag) {
            if($tag->isSuspended()) continue;
            /** @var TagRevision $revision */
            $revision = $this->tagRevisionService->findByUuid($tag->getRevision());

            if($revision->isTrashed() || $revision->isHidden()) continue;
            if($revision->getCseType() !== EncryptionService::CSE_ENCRYPTION_NONE) continue;
            if(!in_array($revision->getSseType(), self::COMPATIBLE_SSE_ENCRYPTIONS)) continue;

            return $tag;
        }

        return null;
    }

    /**
     * @param $category
     * @param $model
     * @param $revision
     *
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    protected function updatePasswordCategory($category, Password $model, PasswordRevision $revision): void {
        if($category) {
            /** @var Tag $tag */
            $tag = $this->tagService->findByIdOrUuid($category);
            if($tag !== null && !$tag->isSuspended()) {
                $relation = $this->passwordTagRelationService->findByTagAndPassword($tag->getUuid(), $model->getUuid());

                if($relation === null) {
                    /** @var TagRevision $tagRevision */
                    $tagRevision = $this->tagRevisionService->findByUuid($tag->getRevision());
                    $this->passwordTagRelationService->create($revision, $tagRevision);
                }
            }
        } else {
            $tag = $this->findCategoryForPassword($model);

            if($tag !== null) {
                $relation = $this->passwordTagRelationService->findByTagAndPassword($tag->getUuid(), $model->getUuid());
                $this->passwordTagRelationService->delete($relation);
            }
        }
    }
}