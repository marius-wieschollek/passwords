<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Db\Session;
use OCA\Passwords\Db\SessionMapper;
use OCA\Passwords\Encryption\Object\SimpleEncryption;
use OCA\Passwords\Helper\Settings\UserSettingsHelper;
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IRequest;
use OCP\ISession;

/**
 * Class SessionService
 *
 * @package OCA\Passwords\Services\Object
 */
class SessionService {

    const VALUE_USER_SECRET  = 'userSecret';
    const API_SESSION_KEY    = 'passwordsSessionId';
    const API_SESSION_HEADER = 'X-API-SESSION';

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var IRequest
     */
    protected $request;

    /**
     * @var SessionMapper
     */
    protected $mapper;

    /**
     * @var UuidHelper
     */
    protected $uuidHelper;

    /**
     * @var SimpleEncryption
     */
    protected $encryption;

    /**
     * @var ISession
     */
    protected $userSession;

    /**
     * @var EnvironmentService
     */
    protected $environment;

    /**
     * @var UserSettingsHelper
     */
    protected $userSettings;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var array
     */
    protected $shadowVars = [];

    /**
     * @var Session|null
     */
    protected $session = null;

    /**
     * @var bool
     */
    protected $modified = false;

    /**
     * SessionService constructor.
     *
     * @param IRequest           $request
     * @param ISession           $session
     * @param SessionMapper      $mapper
     * @param UuidHelper         $uuidHelper
     * @param LoggingService     $logger
     * @param EnvironmentService $environment
     * @param SimpleEncryption   $encryption
     * @param UserSettingsHelper $userSettings
     */
    public function __construct(
        IRequest $request,
        ISession $session,
        SessionMapper $mapper,
        UuidHelper $uuidHelper,
        LoggingService $logger,
        EnvironmentService $environment,
        SimpleEncryption $encryption,
        UserSettingsHelper $userSettings
    ) {
        $this->mapper       = $mapper;
        $this->environment  = $environment;
        $this->request      = $request;
        $this->logger       = $logger;
        $this->userSession  = $session;
        $this->encryption   = $encryption;
        $this->userSettings = $userSettings;
        $this->uuidHelper   = $uuidHelper;
    }

    /**
     * @return bool
     */
    public function isModified(): bool {
        return $this->modified;
    }

    /**
     * @return string|null
     */
    public function getId(): ?string {
        return $this->session === null ? null:$this->session->getUuid();
    }

    /**
     * @param string $key
     * @param null   $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null) {
        return $this->has($key) ? $this->data[ $key ]:$default;
    }

    /**
     * @param string $key
     * @param        $value
     */
    public function set(string $key, $value): void {
        if($this->session === null) $this->load();

        $this->modified     = true;
        $this->data[ $key ] = $value;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool {
        if($this->session === null) $this->load();

        return isset($this->data[ $key ]);
    }

    /**
     * @param string $key
     */
    public function unset(string $key): void {
        if($this->has($key)) {
            unset($this->data[ $key ]);
            $this->modified = true;
        }
    }

    /**
     * @param $name
     */
    public function addShadow($name): void {
        if($this->session === null) $this->load();

        if(!in_array($name, $this->shadowVars)) {
            $this->shadowVars[] = $name;
            $this->modified     = true;
        }
    }

    /**
     * @param $name
     */
    public function removeShadow($name): void {
        if($this->session === null) $this->load();

        if(in_array($name, $this->shadowVars)) {
            $key = array_search($name, $this->shadowVars);
            unset($this->shadowVars[ $key ]);
            $this->modified = true;
        }
    }

    /**
     * @return bool
     */
    public function isAuthorized(): bool {
        if($this->session === null) $this->load();

        return $this->session->getAuthorized();
    }

    /**
     *
     */
    public function authorizeSession(): void {
        if($this->session === null) $this->load();

        $this->session->setAuthorized(true);
        $this->modified = true;
    }

    /**
     *
     */
    public function save(): void {
        if($this->session === null || (!$this->modified && empty($this->session->getId()))) return;

        if($this->modified) {
            $this->encryptSessionData($this->data);

            $shadowData = [];
            foreach($this->shadowVars as $name) {
                $shadowData[ $name ] = $this->userSession->get($name);
            }
            $this->encryptSessionData($shadowData, 'shadowData');
        }

        if(empty($this->session->getId())) {
            $this->session = $this->mapper->insert($this->session);
        } else {
            $this->session->setUpdated(time());
            $this->session = $this->mapper->update($this->session);
        }
        $this->userSession->set(self::API_SESSION_KEY, $this->session->getUuid());
        $this->modified = false;
    }

    /**
     *
     */
    public function delete() {
        if(!empty($this->session->getId())) {
            $this->mapper->delete($this->session);
        }
        $this->data       = [];
        $this->shadowVars = [];
        $this->session    = $this->create();
    }

    /**
     *
     */
    public function load() {
        if($this->session !== null) return;
        if($this->userSession->exists(self::API_SESSION_KEY)) {
            $sessionId = $this->userSession->get(self::API_SESSION_KEY);
        } else {
            $sessionId = $this->request->getHeader(self::API_SESSION_HEADER);
        }

        if(!empty($sessionId)) {
            try {
                /** @var Session $session */
                $session = $this->mapper->findByUuid($sessionId);
                if($this->environment->getUserId() !== $session->getUserId() || $this->environment->getLoginType() !== $session->getLoginType()) {
                    $this->mapper->delete($session);
                    $this->logger->error(['Unauthorized session access by %s on %s', $this->environment->getUserId(), $session->getUserId()]);
                } else if(time() > $session->getUpdated() + $this->userSettings->get('session/lifetime')) {
                    $this->mapper->delete($session);
                    $this->logger->warning(['Cancelled expired session %s for %s', $session->getUuid(), $session->getUserId()]);
                } else {
                    $this->session = $session;
                    $this->data    = $this->decryptSessionData();

                    $shadow = $this->decryptSessionData('shadowData');
                    foreach($shadow as $name => $value) {
                        $this->userSession->set($name, $value);
                    }
                    $this->shadowVars = array_keys($shadow);

                    return;
                }
            } catch(DoesNotExistException $e) {
                $this->logger->warning(['Attempt to access expired or nonexistent session %s by %s', $sessionId, $this->environment->getUserId()]);
            } catch(\Throwable $e) {
                $this->logger->logException($e);
            }
        }

        $this->session = $this->create();
    }

    /**
     * @param array  $data
     * @param string $property
     */
    protected function encryptSessionData(array $data, string $property = 'data'): void {
        try {
            $value = $this->encryption->encrypt(json_encode($data));
            $this->session->setProperty($property, $value);
        } catch(\Exception $e) {
            $this->session->setProperty($property, '[]');
        }
    }

    /**
     * @param string $property
     *
     * @return array|mixed
     */
    protected function decryptSessionData(string $property = 'data') {
        try {
            return json_decode(
                $this->encryption->decrypt(
                    $this->session->getProperty($property)
                ),
                true);
        } catch(\Exception $e) {
            return [];
        }
    }

    /**
     * @return Session
     * @throws \Exception
     */
    protected function create(): Session {
        $model = new Session();
        $model->setLoginType($this->environment->getLoginType());
        $model->setUserId($this->environment->getUserId());
        $model->setClient($this->environment->getClient());
        $model->setUuid($this->uuidHelper->generateUuid());
        $model->setAuthorized(false);
        $model->setDeleted(false);
        $model->setCreated(time());
        $model->setUpdated(time());
        $this->modified = true;

        return $model;
    }
}