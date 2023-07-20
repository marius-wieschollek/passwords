<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Services;

use Exception;
use OCA\Passwords\Db\Session;
use OCA\Passwords\Db\SessionMapper;
use OCA\Passwords\Encryption\Object\SimpleEncryption;
use OCA\Passwords\Exception\Encryption\InvalidEncryptionResultException;
use OCA\Passwords\Helper\Settings\UserSettingsHelper;
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IRequest;
use OCP\ISession;
use Throwable;

/**
 * Class SessionService
 *
 * @package OCA\Passwords\Services\Object
 */
class SessionService {

    const VALUE_USER_SECRET  = 'userSecret';
    const API_SESSION_HEADER = 'X-API-SESSION';
    const API_SESSION_COOKIE = 'nc_passwords';

    /**
     * @var array
     */
    protected array $data = [];

    /**
     * @var array
     */
    protected array $shadowVars = [];

    /**
     * @var Session|null
     */
    private ?Session $session = null;

    /**
     * @var bool
     */
    protected bool $modified = false;

    /**
     * @var string|null
     */
    private ?string $encryptedId = null;

    /**
     * @var string|null
     */
    private ?string $passphrase = null;

    /**
     * SessionService constructor.
     *
     * @param IRequest           $request
     * @param ISession           $userSession
     * @param SessionMapper      $mapper
     * @param UuidHelper         $uuidHelper
     * @param LoggingService     $logger
     * @param EnvironmentService $environment
     * @param SimpleEncryption   $encryption
     * @param UserSettingsHelper $userSettings
     */
    public function __construct(
        protected IRequest           $request,
        protected ISession           $userSession,
        protected SessionMapper      $mapper,
        protected UuidHelper         $uuidHelper,
        protected LoggingService     $logger,
        protected EnvironmentService $environment,
        protected SimpleEncryption   $encryption,
        protected UserSettingsHelper $userSettings
    ) {
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
    public function getEncryptedId(): ?string {
        if($this->encryptedId !== null) {
            return $this->encryptedId;
        }

        if($this->session !== null) {
            $id = $this->session->getUuid().':'.$this->getPassphrase();

            try {
                return $this->encryption->encrypt($id);
            } catch(Exception $e) {
            }
        }

        return null;
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
        $this->modified = false;
    }

    /**
     * @throws Exception
     */
    public function delete() {
        if(!empty($this->session->getId())) {
            $this->mapper->delete($this->session);
        }
        $this->data        = [];
        $this->shadowVars  = [];
        $this->session     = $this->create();
        $this->encryptedId = null;
    }

    /**
     *
     */
    public function load() {
        if($this->session !== null) return;
        [$sessionId, $passphrase] = $this->getSessionIdFromRequest();

        if(!empty($sessionId)) {
            try {
                /** @var Session $session */
                $session = $this->mapper->findByUuid($sessionId);
                if($this->environment->getUserId() !== $session->getUserId() || $this->environment->getLoginType() !== $session->getLoginType()) {
                    $this->mapper->delete($session);
                    $this->logger->error(['Unauthorized session access by %s on %s', $this->environment->getUserId(), $session->getUserId()]);
                } else if(time() > $session->getUpdated() + $this->userSettings->get('session/lifetime')) {
                    $this->mapper->delete($session);
                    $this->logger->info(['Cancelled expired session %s for %s', $session->getUuid(), $session->getUserId()]);
                } else {
                    $this->session    = $session;
                    $this->passphrase = $passphrase;
                    $this->data       = $this->decryptSessionData();

                    $shadow = $this->decryptSessionData('shadowData');
                    foreach($shadow as $name => $value) {
                        $this->userSession->set($name, $value);
                    }
                    $this->shadowVars = array_keys($shadow);

                    return;
                }
            } catch(DoesNotExistException $e) {
                $this->logger->info(['Attempt to access expired or nonexistent session %s by %s', $sessionId, $this->environment->getUserId()]);
                $this->passphrase = null;
            } catch(Throwable $e) {
                $this->logger->logException($e);
                $this->passphrase = null;
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
            $value = $this->encryption->encrypt(json_encode($data), $this->getPassphrase());
            $this->session->setProperty($property, $value);
        } catch(Exception $e) {
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
                    $this->session->getProperty($property),
                    $this->passphrase
                ),
                true);
        } catch(Exception $e) {
            return [];
        }
    }

    /**
     * @return Session
     * @throws Exception
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
        $this->modified    = true;
        $this->encryptedId = null;
        $this->passphrase  = null;

        return $model;
    }

    /**
     * @return string[]|null
     */
    protected function getSessionIdFromRequest(): ?array {
        $encryptedId = null;
        if($this->request->getCookie(SessionService::API_SESSION_COOKIE)) {
            $encryptedId = $this->request->getCookie(SessionService::API_SESSION_COOKIE);
        } else if(!empty($this->request->getHeader(self::API_SESSION_HEADER))) {
            $encryptedId = $this->request->getHeader(self::API_SESSION_HEADER);
        }

        try {
            if(!empty($encryptedId)) {
                $sessionId = $this->encryption->decrypt($encryptedId);
                if(!empty($sessionId) && str_contains($sessionId, ':')) {
                    $this->encryptedId = $encryptedId;

                    return explode(':', $sessionId, 2);
                }
            }
        } catch(Exception $e) {
        }

        return null;
    }

    /**
     * @return string
     */
    protected function getPassphrase(): string {
        if($this->passphrase === null) {
            $this->passphrase = $this->uuidHelper->generateUuid();
        }

        return $this->passphrase;
    }
}