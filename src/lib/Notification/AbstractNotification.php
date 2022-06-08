<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Notification;

use DateTime;
use Exception;
use OCA\Passwords\AppInfo\Application;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\IAction;
use OCP\Notification\IManager;
use OCP\Notification\INotification;

/**
 * Class AbstractNotification
 *
 * @package OCA\Passwords\Notification
 */
abstract class AbstractNotification {

    const NAME = 'default';
    const TYPE = 'default';

    /**
     * @var IFactory
     */
    protected IFactory $l10nFactory;

    /**
     * @var IURLGenerator
     */
    protected IURLGenerator $urlGenerator;

    /**
     * @var IManager
     */
    protected IManager $notificationManager;

    /**
     * AbstractNotification constructor.
     *
     * @param IFactory      $l10nFactory
     * @param IURLGenerator $urlGenerator
     * @param IManager      $notificationManager
     */
    public function __construct(
        IFactory $l10nFactory,
        IURLGenerator $urlGenerator,
        IManager $notificationManager
    ) {
        $this->l10nFactory         = $l10nFactory;
        $this->urlGenerator        = $urlGenerator;
        $this->notificationManager = $notificationManager;
    }

    /**
     * Send the notification
     *
     * @param string $userId
     * @param array  $parameters
     */
    abstract public function send(string $userId, array $parameters = []): void;

    /**
     * Process the notification for display
     *
     * @param INotification $notification
     * @param IL10N         $localisation
     *
     * @return INotification
     */
    abstract public function process(INotification $notification, IL10N $localisation): INotification;

    /**
     * @param string $userId
     *
     * @return INotification
     * @throws Exception
     */
    protected function createNotification(string $userId): INotification {
        $icon = $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath(Application::APP_NAME, 'app-dark.svg'));

        return $this->notificationManager
            ->createNotification()
            ->setApp(Application::APP_NAME)
            ->setDateTime(new DateTime())
            ->setUser($userId)
            ->setIcon($icon);
    }

    /**
     * @param INotification $notification
     * @param string        $link
     * @param string        $name
     */
    protected function addRawLink(INotification $notification, string $link, string $name = 'link'): void {
        $linkAction = $notification->createAction();
        $linkAction->setLabel($name)
                   ->setLink($link, IAction::TYPE_WEB)
                   ->setPrimary(true);
        $notification->addAction($linkAction);
    }

    /**
     * @param INotification $notification
     * @param string        $link
     * @param string        $label
     * @param string        $name
     */
    protected function processLink(INotification $notification, string $link, string $label, string $name = 'link'): void {
        foreach ($notification->getActions() as $action) {
            if ($action->getLabel() === $name) {
                $action->setLink($link, IAction::TYPE_WEB)
                       ->setParsedLabel($label);
                $notification->addParsedAction($action);
            }
        }
    }
}
