<?php

namespace {
    class OC_Defaults {
        public function getTextColorPrimary() {return '';}
        public function getLogo() {return '';}
        public function getEntity() {return '';}
        public function getColorPrimary() {return '';}
        public function isUserThemingDisabled(): bool {return true;}
    }

    class OC {
        public static $server;
    }

    class OC_Util {
        public static $ncDefaultVersion = [\OCA\Passwords\AppInfo\SystemRequirements::NC_NOTIFICATION_ID,0,0,0];
        public static $ncVersion = [\OCA\Passwords\AppInfo\SystemRequirements::NC_NOTIFICATION_ID,0,0,0];
        public static function getVersion(): array {return self::$ncVersion;}
    }
}

namespace OC {
    class Server {
        function get($id) { return (object) []; }
    }
}

namespace OCP\AppFramework {
    class App {
        public static function buildAppNamespace(string $appId, string $topNamespace = 'OCA\\'): string {return 'OCA\\Passwords\\';}
        public function __construct(string $appName, array $urlParams = []) {}
        public function getContainer(): IAppContainer {return new IAppContainer();}
        public function dispatch(string $controllerName, string $methodName) {}
    }

    class Http {
        public const STATUS_OK = 200;
        public const STATUS_CREATED = 201;
        public const STATUS_ACCEPTED = 202;
        public const STATUS_NO_CONTENT = 204;
        public const STATUS_MOVED_PERMANENTLY = 301;
        public const STATUS_FOUND = 302;
        public const STATUS_NOT_MODIFIED = 304;
        public const STATUS_BAD_REQUEST = 400;
        public const STATUS_UNAUTHORIZED = 401;
        public const STATUS_PAYMENT_REQUIRED = 402;
        public const STATUS_FORBIDDEN = 403;
        public const STATUS_NOT_FOUND = 404;
        public const STATUS_METHOD_NOT_ALLOWED = 405;
        public const STATUS_NOT_ACCEPTABLE = 406;
        public const STATUS_PROXY_AUTHENTICATION_REQUIRED = 407;
        public const STATUS_REQUEST_TIMEOUT = 408;
        public const STATUS_CONFLICT = 409;
        public const STATUS_GONE = 410;
        public const STATUS_LENGTH_REQUIRED = 411;
        public const STATUS_PRECONDITION_FAILED = 412;
        public const STATUS_REQUEST_ENTITY_TOO_LARGE = 413;
        public const STATUS_REQUEST_URI_TOO_LONG = 414;
        public const STATUS_UNSUPPORTED_MEDIA_TYPE = 415;
        public const STATUS_REQUEST_RANGE_NOT_SATISFIABLE = 416;
        public const STATUS_EXPECTATION_FAILED = 417;
        public const STATUS_IM_A_TEAPOT = 418;
        public const STATUS_UNPROCESSABLE_ENTITY = 422;
        public const STATUS_LOCKED = 423;
        public const STATUS_FAILED_DEPENDENCY = 424;
        public const STATUS_UPGRADE_REQUIRED = 426;
        public const STATUS_PRECONDITION_REQUIRED = 428;
        public const STATUS_TOO_MANY_REQUESTS = 429;
        public const STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
        public const STATUS_INTERNAL_SERVER_ERROR = 500;
        public const STATUS_NOT_IMPLEMENTED = 501;
        public const STATUS_BAD_GATEWAY = 502;
        public const STATUS_SERVICE_UNAVAILABLE = 503;
        public const STATUS_GATEWAY_TIMEOUT = 504;
        public const STATUS_HTTP_VERSION_NOT_SUPPORTED = 505;
        public const STATUS_VARIANT_ALSO_NEGOTIATES = 506;
        public const STATUS_INSUFFICIENT_STORAGE = 507;
        public const STATUS_LOOP_DETECTED = 508;
        public const STATUS_BANDWIDTH_LIMIT_EXCEEDED = 509;
        public const STATUS_NOT_EXTENDED = 510;
        public const STATUS_NETWORK_AUTHENTICATION_REQUIRED = 511;
    }
}

namespace OCP\AppFramework\Bootstrap {
    interface IBootstrap {}
}

namespace OCP\L10N {
    use OCP\IUser;
    interface IFactory {
        public function get($app, $lang = null, $locale = null);
        public function findLanguage($app = null);
        public function findLocale($lang = null);
        public function findLanguageFromLocale(string $app = 'core', string $locale = null);
        public function findAvailableLanguages($app = null);
        public function findAvailableLocales();
        public function languageExists($app, $lang);
        public function localeExists($locale);
        public function createPluralFunction($string);
        public function getLanguageIterator(IUser $user = null): ILanguageIterator;
    }
}

namespace OCP {
    class IGroup {
        public function getUsers() { return  []; }
    }

    class IGroupManager {
        public function get() {return new IGroup();}
    }

    interface IUser {
        public function getUID();
    }

    class IURLGenerator {
        public function linkTo(string $appName, string $file, array $args = []): string {return '';}
        public function getAbsoluteURL(string $url): string {return '';}
        public function imagePath(string $appName, string $file): string {return '';}
        public function linkToRouteAbsolute(string $routeName, array $arguments = []): string {return '';}
        public function linkToDocs(string $key): string {return '';}
    }

    interface IL10N{
        public function t(string $text, $parameters = []): string;
    }
}

namespace OC\User {
    use \OCP\IUser;
    class User implements IUser{
        public function getUID(): string {return '';}
        public function getDisplayName() {}
        public function setDisplayName($displayName) {}
        public function getLastLogin() {}
        public function updateLastLoginTimestamp() {}
        public function delete() {}
        public function setPassword($password, $recoveryPassword = null) {}
        public function getHome() {}
        public function getBackendClassName() {}
        public function getBackend() {}
        public function canChangeAvatar() {}
        public function canChangePassword() {}
        public function canChangeDisplayName() {}
        public function isEnabled() {}
        public function setEnabled(bool $enabled = true) {}
        public function getEMailAddress() {}
        public function getAvatarImage($size) {}
        public function getCloudId() {}
        public function setEMailAddress($mailAddress) {}
        public function getQuota() {}
        public function setQuota($quota) {}
    }
}

namespace OCP\Notification {
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

    interface INotifier {
        public function getID(): string;
        public function getName(): string;
        public function prepare(INotification $notification, string $languageCode): INotification;
    }
}

namespace OCP\Migration {
    interface IOutput {
        public function info($message);
        public function warning($message);
        public function startProgress($max = 0);
        public function advance($step = 1, $description = '');
        public function finishProgress();
    }

    interface IRepairStep {
        public function getName();
        public function run(IOutput $output);
    }
}

namespace OC\Migration {
    use OCP\Migration\IOutput;
    class SimpleOutput implements IOutput {
        public function info($message) {}
        public function warning($message) {}
        public function startProgress($max = 0) {}
        public function advance($step = 1, $description = '') {}
        public function finishProgress() {}
    }
}

namespace OCP\Files\SimpleFS {
    interface ISimpleFile {}

    interface ISimpleFolder {}
}

namespace OC\Files\SimpleFS {

    use OCP\Files\NotFoundException;
    use OCP\Files\NotPermittedException;
    use OCP\Files\SimpleFS\ISimpleFile;
    use OCP\Files\SimpleFS\ISimpleFolder;
    class SimpleFile implements ISimpleFile{
        public function getName(): string {return '';}
        public function getSize(): int {return 0;}
        public function getETag(): string {return '';}
        public function getMTime(): int {return 0;}
        public function getContent(): string {return '';}
        public function putContent($data): void {}
        public function delete(): void {}
        public function getMimeType(): string {return '';}
        public function read() {}
        public function write() {}
    }
    class SimpleFolder implements ISimpleFolder {
        public function getDirectoryListing(): array {return [];}
        public function fileExists(string $name): bool {return false;}
        public function getFile(string $name): ISimpleFile {return new SimpleFile(); }
        public function newFile(string $name, $content = null): ISimpleFile {return new SimpleFile(); }
        public function delete(): void {}
        public function getName(): string {return '';}
        public function getFolder(string $name): ISimpleFolder  {return new SimpleFolder(); }
        public function newFolder(string $path): ISimpleFolder {return new SimpleFolder(); }
    }
}

namespace OCP\Http\Client {
    class IClientService {
        public function newClient(): IClient { return new IClient(); }
    }

    class IClient {
        public function get(string $uri, array $options = []): IResponse { return new IResponse(); }
        public function head(string $uri, array $options = []): IResponse { return new IResponse(); }
        public function post(string $uri, array $options = []): IResponse { return new IResponse(); }
        public function put(string $uri, array $options = []): IResponse { return new IResponse(); }
        public function delete(string $uri, array $options = []): IResponse { return new IResponse(); }
        public function options(string $uri, array $options = []): IResponse { return new IResponse(); }
    }

    class IResponse {
        public function getBody() {}
        public function getStatusCode(): int  { return 0; }
        public function getHeader(string $key): string { return ''; }
        public function getHeaders(): array { return []; }
    }
}

namespace OCA\Unsplash\Services {
    class SettingsService {
        public function headerbackgroundLink($size): string { return ''; }
    }
}

namespace OCA\Unsplash\ProviderHandler {
    class Provider {
        const SIZE_SMALL = 0;
        const SIZE_NORMAL = 1;
        const SIZE_HIGH = 2;
        const SIZE_ULTRA = 3;
    }
}

namespace Psr\Log {
    class LoggerInterface {
        public function emergency($message, array $context = array()) {}
        public function alert($message, array $context = array()) {}
        public function critical($message, array $context = array()) {}
        public function error($message, array $context = array()) {}
        public function warning($message, array $context = array()) {}
        public function notice($message, array $context = array()) {}
        public function info($message, array $context = array()) {}
        public function debug($message, array $context = array()) {}
        public function log($level, $message, array $context = array()) {}
    }
}
namespace OCP\SetupCheck {
    interface ISetupCheck {
        public function getCategory(): string;
        public function getName(): string;
        public function run(): SetupResult;
    }

    class SetupResult {
        public static function warning(?string $description = null, ?string $linkToDoc = null): self {return new self();}
        public static function error(?string $description = null, ?string $linkToDoc = null): self {return new self();}
        public static function success(?string $description = null, ?string $linkToDoc = null): self {return new self();}
    }
}