<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\User;

use OC\Authentication\TwoFactorAuth\Manager;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\SessionService;
use OCP\ISession;
use OCP\IUser;
use ReflectionObject;

/**
 * Class UserTokenHelper
 *
 * @package OCA\Passwords\Helper\Token
 */
class UserTokenHelper {

    /**
     * @var IUser
     */
    protected $user;

    /**
     * @var ISession
     */
    protected $session;

    /**
     * @var SessionService
     */
    protected $sessionService;

    /**
     * @var Manager
     */
    protected $twoFactorManager;

    /**
     * @var null|\OCP\Authentication\TwoFactorAuth\IProvider[]
     */
    protected $providers = null;

    /**
     * UserTokenHelper constructor.
     *
     * @param Manager            $twoFactorManager
     * @param EnvironmentService $environmentService
     * @param SessionService     $sessionService
     * @param ISession           $session
     */
    public function __construct(Manager $twoFactorManager, EnvironmentService $environmentService, SessionService $sessionService, ISession $session) {
        $this->twoFactorManager = $twoFactorManager;
        $this->sessionService   = $sessionService;
        $this->session          = $session;
        $this->user             = $environmentService->getUser();
    }

    /**
     * @return bool
     */
    public function tokenRequired(): bool {
        return !empty($this->getProviders());
    }

    /**
     * @return \OCP\Authentication\TwoFactorAuth\IProvider[]
     */
    public function getProviders(): array {
        if($this->providers !== null) return $this->providers;
        $this->providers = [];

        try {
            if($this->twoFactorManager->isTwoFactorAuthenticated($this->user)) {
                $allProviders = $this->twoFactorManager->getProviderSet($this->user)->getPrimaryProviders();

                foreach($allProviders as $provider) {
                    if($provider->getId() === 'totp' || $provider->getId() === 'twofactor_nextcloud_notification' || strpos($provider->getId(), 'gateway') !== false) {
                        $this->providers[ $provider->getId() ] = $provider;
                    }
                }

                if(!empty($this->providers)) {
                    $backupProvider = $this->twoFactorManager->getProvider($this->user, 'backup_codes');
                    if($backupProvider !== null) $this->providers[ $backupProvider->getId() ] = $backupProvider;
                }
            }
        } catch(\Throwable $e) {
        }

        return $this->providers;
    }

    /**
     * @return array
     */
    public function getProvidersAsArray(): array {
        $providers = $this->getProviders();
        $array     = [];

        foreach($providers as $provider) {
            $id      = $provider->getId();
            $array[] = [
                'type'        => strpos($id, 'twofactor_nextcloud_notification') !== false ? 'request-token':'user-token',
                'id'          => $id,
                'label'       => $provider->getDisplayName(),
                'description' => $provider->getDescription(),
                'request'     => strpos($id, 'gateway') !== false || strpos($id, 'twofactor_nextcloud_notification') !== false
            ];
        }

        return $array;
    }

    /**
     * @param $id
     *
     * @return array
     */
    public function triggerProvider(string $id): array {
        $providers = $this->getProviders();
        if(isset($providers[ $id ])) {
            $template = $providers[ $id ]->getTemplate($this->user);
            $data     = [];

            if(strpos($id, 'gateway') !== false) {
                $pid = substr($id, 8);
                $this->sessionService->addShadow("twofactor_gateway_{$pid}_secret");
            }

            if($id === 'twofactor_nextcloud_notification') {
                $r = new ReflectionObject($template);
                $r = $r->getParentClass()->getParentClass();
                $p = $r->getProperty('vars');
                $p->setAccessible(true);
                $data['code'] = $p->getValue($template)['token'];
            }

            return [true, $data];
        }

        return [false];
    }

    /**
     * @param array $tokens
     *
     * @return bool
     */
    public function verifyTokens(array $tokens): bool {
        $providers = $this->getProviders();

        if(empty($tokens) && !empty($providers)) return false;

        foreach($tokens as $id => $token) {
            if(!isset($providers[ $id ]) || !$providers[ $id ]->verifyChallenge($this->user, $token)) return false;
        }

        return true;
    }
}