<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.01.18
 * Time: 16:32
 */

namespace OCA\Passwords\Controller\Api\Legacy;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;
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

    /**
     * @var TagService
     */
    protected $tagService;

    /**
     * @var PasswordService
     */
    protected $passwordService;

    /**
     * @var PasswordRevisionService
     */
    protected $passwordRevisionService;

    /**
     * LegacyPasswordsApiController constructor.
     *
     * @param IRequest                $request
     * @param TagService              $tagService
     * @param PasswordService         $passwordService
     * @param PasswordRevisionService $passwordRevisionService
     */
    public function __construct(IRequest $request, TagService $tagService, PasswordService $passwordService, PasswordRevisionService $passwordRevisionService) {
        parent::__construct(
            Application::APP_NAME,
            $request,
            'GET, POST, DELETE, PUT, PATCH',
            'Authorization, Content-Type, Accept',
            86400
        );
        $this->tagService              = $tagService;
        $this->passwordService         = $passwordService;
        $this->passwordRevisionService = $passwordRevisionService;
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function index(): JSONResponse {
        $counter = 0;
        $passwords = new \stdClass();
        /** @var Password[] $models */
        $models = $this->passwordService->findAll();
        foreach($models as $model) {
            if($model->isSuspended()) continue;
            try {
                $password = $this->getPasswordObject($model);
            } catch(\Exception $e) {
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
     * @param Password $password
     *
     * @return array|null
     * @throws \Exception
     */
    protected function getPasswordObject(Password $password): ?array {
        /** @var PasswordRevision $revision */
        $revision = $this->passwordRevisionService->findCurrentRevisionByModel($password->getUuid(), true);

        if($revision->getCseType() !== EncryptionService::CSE_ENCRYPTION_NONE) {
            return null;
        }
        if($revision->getSseType() !== EncryptionService::SSE_ENCRYPTION_V1) {
            return null;
        }

        $category = 0;
        $tags     = $this->tagService->findByPassword($password->getUuid());
        if(!empty($tags)) $category = $tags[0]->getId();

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
            'datechanged' => date("Y-m-d", $revision->getCreated()),
            'notes'       => $revision->getNotes()
        ];
        $properties = json_encode($properties);
        $properties = substr($properties, 1, strlen($properties) - 2);

        return [
            'id'            => $password->getId(),
            'loginname'     => $revision->getUsername(),
            'pass'          => $revision->getPassword(),
            'website'       => parse_url($revision->getUrl(), PHP_URL_HOST),
            'address'       => $revision->getUrl(),
            'notes'         => $revision->getNotes(),
            'deleted'       => $revision->isTrashed(),
            'creation_date' => date("Y-m-d", $password->getCreated()),
            'properties'    => $properties
        ];
    }
}