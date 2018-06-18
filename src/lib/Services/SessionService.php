<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Db\Session;
use OCA\Passwords\Db\SessionMapper;
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
     * @var ISession
     */
    protected $userSession;

    /**
     * @var \OCA\Passwords\Services\EnvironmentService
     */
    protected $environment;

    /**
     * @var array
     */
    protected $data = [];

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
    public function __construct(SessionMapper $mapper, IRequest $request, ISession $session, LoggingService $logger, EnvironmentService $environment) {
        $this->mapper      = $mapper;
        $this->environment = $environment;
        $this->request     = $request;
        $this->logger      = $logger;
        $this->userSession = $session;
    }

    /**
     * @return string
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
        if($this->has($key)) unset($this->data[ $key ]);
    }

    /**
     *
     */
    public function save(): void {
        if(!$this->modified) return;
        $this->session->setData(json_encode($this->data));

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
        $this->data    = [];
        $this->session = $this->create();
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
                    $this->data    = json_decode($session->getData(), true);

                    return;
                }
            } catch(\Throwable $e) {
                $this->logger->logException($e);
            }
        }

        $this->session = $this->create();
    }

    /**
     * @return Session
     */
    protected function create(): Session {
        $model = new Session();
        $model->setUserId($this->environment->getUserId());
        $model->setUuid($this->generateUuidV4());
        $model->setCreated(time());
        $model->setUpdated(time());
        $this->modified = true;

        return $model;
    }
}