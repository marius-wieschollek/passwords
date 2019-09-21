<?php
namespace OCP\Notification;

interface INotification {

	public function setApp(string $app): INotification;

	public function getApp(): string;

	public function setUser(string $user): INotification;

	public function getUser(): string;

	public function setDateTime(\DateTime $dateTime): INotification;

	public function getDateTime(): \DateTime;

	public function setObject(string $type, string $id): INotification;

	public function getObjectType(): string;

	public function getObjectId(): string;

	public function setSubject(string $subject, array $parameters = []): INotification;

	public function getSubject(): string;

	public function getSubjectParameters(): array;

	public function setParsedSubject(string $subject): INotification;

	public function getParsedSubject(): string;

	public function setRichSubject(string $subject, array $parameters = []): INotification;

	public function getRichSubject(): string;

	public function getRichSubjectParameters(): array;

	public function setMessage(string $message, array $parameters = []): INotification;

	public function getMessage(): string;

	public function getMessageParameters(): array;

	public function setParsedMessage(string $message): INotification;

	public function getParsedMessage(): string;

	public function setRichMessage(string $message, array $parameters = []): INotification;

	public function getRichMessage(): string;

	public function getRichMessageParameters(): array;

	public function setLink(string $link): INotification;

	public function getLink(): string;

	public function setIcon(string $icon): INotification;

	public function getIcon(): string;

	public function createAction(): IAction;

	public function addAction(IAction $action): INotification;

	public function getActions(): array;

	public function addParsedAction(IAction $action): INotification;

	public function getParsedActions(): array;

	public function isValid(): bool;

	public function isValidParsed(): bool;
}
