<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Db\Session;
use OCA\Passwords\Db\SessionMapper;
use OCA\Passwords\Encryption\SimpleEncryption;
use OCP\IRequest;
use OCP\ISession;

/**
 * Class SessionService
 *
 * @package OCA\Passwords\Services\Object
 */
class SessionService {

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
     * @param SessionMapper      $mapper
     * @param IRequest           $request
     * @param ISession           $session
     * @param LoggingService     $logger
     * @param EnvironmentService $environment
     */
    public function __construct(SessionMapper $mapper, IRequest $request, ISession $session, LoggingService $logger, EnvironmentService $environment, SimpleEncryption $encryption) {
        $this->mapper      = $mapper;
        $this->environment = $environment;
        $this->request     = $request;
        $this->logger      = $logger;
        $this->userSession = $session;
        $this->encryption  = $encryption;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function generateUuidV4(): string {
        return implode('-', [
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(chr((ord(random_bytes(1)) & 0x0F) | 0x40)).bin2hex(random_bytes(1)),
            bin2hex(chr((ord(random_bytes(1)) & 0x3F) | 0x80)).bin2hex(random_bytes(1)),
            bin2hex(random_bytes(6))
        ]);
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
        if($key === 'password') return;
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
        if($this->has($key) && $key !== 'password') {
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
     * @param string $password
     */
    public function authorizeSession(?string $password): void {
        if($this->session === null) $this->load();

        $this->session->setAuthorized(true);
        $this->modified = true;

        if($password !== null) {
            $this->data['password'] = $password;
        }
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
        if($this->userSession->exists('passwordsSessionId')) {
            $sessionId = $this->userSession->get('passwordsSessionId');
        } else {
            $sessionId = $this->request->getHeader('X-Passwords-Session');
        }

        if(!empty($sessionId)) {
            try {
                /** @var Session $session */
                $session = $this->mapper->findByUuid($sessionId);
                if($this->environment->getUserId() !== $session->getUserId()) {
                    $this->mapper->delete($session);
                    $this->logger->error('Unauthorized session access');
                } else if(time() > $session->getUpdated() + 15 * 60) {
                    $this->mapper->delete($session);
                    $this->logger->warning('Cancelled expired session');
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
            $value = $this->encryption->encrypt(
                json_encode($data)
            );
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
        $model->setUserId($this->environment->getUserId());
        $model->setUuid($this->generateUuidV4());
        $model->setAuthorized(false);
        $model->setCreated(time());
        $model->setUpdated(time());
        $this->modified = true;

        return $model;
    }
}