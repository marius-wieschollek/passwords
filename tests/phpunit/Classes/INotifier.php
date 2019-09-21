<?php

namespace OCP\Notification;

interface INotifier {

    public function getID(): string;

    public function getName(): string;

    public function prepare(INotification $notification, string $languageCode): INotification;
}
