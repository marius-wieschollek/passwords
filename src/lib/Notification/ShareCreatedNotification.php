<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Notification;

use OCA\Passwords\Services\UserService;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\IManager;
use OCP\Notification\INotification;

/**
 * Class ShareCreatedNotification
 *
 * @package OCA\Passwords\Notification
 */
class ShareCreatedNotification extends AbstractNotification {

    const NAME = 'share_create';
    const TYPE = 'shares';

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * ShareCreatedNotification constructor.
     *
     * @param IFactory      $l10nFactory
     * @param UserService   $userService
     * @param IURLGenerator $urlGenerator
     * @param IManager      $notificationManager
     */
    public function __construct(
        IFactory $l10nFactory,
        UserService $userService,
        IURLGenerator $urlGenerator,
        IManager $notificationManager
    ) {
        $this->userService = $userService;

        parent::__construct($l10nFactory, $urlGenerator, $notificationManager);
    }

    /**
     * Send the notification
     *
     * @param string $userId
     * @param array  $parameters
     */
    public function send(string $userId, array $parameters = []): void {
        $notification
            = $this->createNotification($userId)
                   ->setSubject(self::NAME, $parameters)
                   ->setObject('share', 'create');

        $this->notificationManager->notify($notification);
    }

    /**
     * Process the notification for display
     *
     * @param INotification $notification
     * @param IL10N         $localisation
     *
     * @return INotification
     */
    public function process(INotification $notification, IL10N $localisation): INotification {
        $title = $this->getTitle($notification, $localisation);
        $link  = $this->urlGenerator->linkToRouteAbsolute('passwords.page.index').'#/shared/0';

        return $notification
            ->setParsedSubject($title)
            ->setLink($link);
    }

    /**
     * @param INotification $notification
     * @param IL10N         $localisation
     *
     * @return string
     */
    protected function getTitle(INotification $notification, IL10N $localisation): string {
        $owners        = $notification->getSubjectParameters()['owners'];
        $ownerCount    = count($owners);
        $passwordCount = 0;

        if($ownerCount === 1) {
            list($passwordCount, $title) = $this->getSingleOwnerTitle($localisation, $owners);
        } else {
            list($passwordCount, $title) = $this->getMultiOwnerTitle($localisation, $owners, $passwordCount, $ownerCount);
        }

        return $title
               .' '.
               $localisation->n(
                   'Open the passwords app to see it.',
                   'Open the passwords app to see them.',
                   $passwordCount
               );
    }

    /**
     * @param IL10N $localisation
     * @param array $owners
     *
     * @return array
     */
    protected function getSingleOwnerTitle(IL10N $localisation, array $owners): array {
        $ownerId       = key($owners);
        $owner         = $this->userService->getUserName($ownerId);
        $passwordCount = $owners[ $ownerId ];

        $title = $localisation->n(
            '%s shared a password with you.',
            '%s shared %s passwords with you.',
            $passwordCount,
            [$owner, $passwordCount]
        );

        return [$passwordCount, $title];
    }

    /**
     * @param IL10N $localisation
     * @param array $owners
     * @param int   $passwordCount
     * @param int   $ownerCount
     *
     * @return array
     */
    protected function getMultiOwnerTitle(IL10N $localisation, array $owners, int $passwordCount, int $ownerCount): array {
        $params = [];
        foreach($owners as $ownerId => $amount) {
            if(count($params) < 4) $params[] = $this->userService->getUserName($ownerId);
            $passwordCount += $amount;
        }
        $params = array_reverse($params);
        array_unshift($params, $passwordCount, $ownerCount - 2);

        $text = ($ownerCount > 2 ? '%5$s, %4$s':'%4$s')
                .' and '.
                ($ownerCount > 3 ? '%2$s others':'%3$s')
                .' shared %1$s passwords with you.';

        $title = $localisation->t($text, $params);

        return [$passwordCount, $title];
    }
}