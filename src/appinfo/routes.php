<?php

namespace OCA\Passwords\AppInfo;

$routes = [
    ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
    ['name' => 'cron#execute', 'url' => '/cron/{job}', 'verb' => 'GET'],
    ['name' => 'language#get_file', 'url' => '/l10n/{section}/{language}.json', 'verb' => 'GET'],
    ['name' => 'notification#survey', 'url' => '/notification/survey/{answer}', 'verb' => 'GET'],

    ['name' => 'session_api#request', 'url' => '/api/1.0/session/request', 'verb' => 'GET'],
    ['name' => 'session_api#open', 'url' => '/api/1.0/session/open', 'verb' => 'POST'],
    ['name' => 'session_api#close', 'url' => '/api/1.0/session/close', 'verb' => 'GET'],
    ['name' => 'session_api#keep_alive', 'url' => '/api/1.0/session/keepalive', 'verb' => 'GET'],
    ['name' => 'session_api#request_token', 'url' => '/api/1.0/token/{provider}/request', 'verb' => 'GET'],

    ['name' => 'keychain_api#list', 'url' => '/api/1.0/keychain/get', 'verb' => 'GET'],
    ['name' => 'keychain_api#update', 'url' => '/api/1.0/keychain/set', 'verb' => 'POST'],

    ['name' => 'account_api#reset', 'url' => '/api/1.0/account/reset', 'verb' => 'POST'],
    ['name' => 'account_api#get_challenge', 'url' => '/api/1.0/account/challenge/get', 'verb' => 'GET'],
    ['name' => 'account_api#set_challenge', 'url' => '/api/1.0/account/challenge/set', 'verb' => 'POST'],

    ['name' => 'password_api#list', 'url' => '/api/1.0/password/list', 'verb' => 'GET'],
    ['name' => 'password_api#list', 'url' => '/api/1.0/password/list', 'verb' => 'POST', 'postfix' => 'POST'],
    ['name' => 'password_api#show', 'url' => '/api/1.0/password/show', 'verb' => 'POST'],
    ['name' => 'password_api#find', 'url' => '/api/1.0/password/find', 'verb' => 'POST'],
    ['name' => 'password_api#create', 'url' => '/api/1.0/password/create', 'verb' => 'POST'],
    ['name' => 'password_api#update', 'url' => '/api/1.0/password/update', 'verb' => 'PATCH'],
    ['name' => 'password_api#delete', 'url' => '/api/1.0/password/delete', 'verb' => 'DELETE'],
    ['name' => 'password_api#restore', 'url' => '/api/1.0/password/restore', 'verb' => 'PATCH'],

    ['name' => 'folder_api#list', 'url' => '/api/1.0/folder/list', 'verb' => 'GET'],
    ['name' => 'folder_api#list', 'url' => '/api/1.0/folder/list', 'verb' => 'POST', 'postfix' => 'POST'],
    ['name' => 'folder_api#show', 'url' => '/api/1.0/folder/show', 'verb' => 'POST'],
    ['name' => 'folder_api#find', 'url' => '/api/1.0/folder/find', 'verb' => 'POST'],
    ['name' => 'folder_api#create', 'url' => '/api/1.0/folder/create', 'verb' => 'POST'],
    ['name' => 'folder_api#update', 'url' => '/api/1.0/folder/update', 'verb' => 'PATCH'],
    ['name' => 'folder_api#delete', 'url' => '/api/1.0/folder/delete', 'verb' => 'DELETE'],
    ['name' => 'folder_api#restore', 'url' => '/api/1.0/folder/restore', 'verb' => 'PATCH'],

    ['name' => 'tag_api#list', 'url' => '/api/1.0/tag/list', 'verb' => 'GET'],
    ['name' => 'tag_api#list', 'url' => '/api/1.0/tag/list', 'verb' => 'POST', 'postfix' => 'POST'],
    ['name' => 'tag_api#show', 'url' => '/api/1.0/tag/show', 'verb' => 'POST'],
    ['name' => 'tag_api#find', 'url' => '/api/1.0/tag/find', 'verb' => 'POST'],
    ['name' => 'tag_api#create', 'url' => '/api/1.0/tag/create', 'verb' => 'POST'],
    ['name' => 'tag_api#update', 'url' => '/api/1.0/tag/update', 'verb' => 'PATCH'],
    ['name' => 'tag_api#delete', 'url' => '/api/1.0/tag/delete', 'verb' => 'DELETE'],
    ['name' => 'tag_api#restore', 'url' => '/api/1.0/tag/restore', 'verb' => 'PATCH'],

    ['name' => 'share_api#list', 'url' => '/api/1.0/share/list', 'verb' => 'GET'],
    ['name' => 'share_api#list', 'url' => '/api/1.0/share/list', 'verb' => 'POST', 'postfix' => 'POST'],
    ['name' => 'share_api#show', 'url' => '/api/1.0/share/show', 'verb' => 'POST'],
    ['name' => 'share_api#find', 'url' => '/api/1.0/share/find', 'verb' => 'POST'],
    ['name' => 'share_api#create', 'url' => '/api/1.0/share/create', 'verb' => 'POST'],
    ['name' => 'share_api#update', 'url' => '/api/1.0/share/update', 'verb' => 'PATCH'],
    ['name' => 'share_api#delete', 'url' => '/api/1.0/share/delete', 'verb' => 'DELETE'],
    ['name' => 'share_api#partners', 'url' => '/api/1.0/share/partners', 'verb' => 'GET'],
    ['name' => 'share_api#partners', 'url' => '/api/1.0/share/partners', 'verb' => 'POST', 'postfix' => 'POST'],

    ['name' => 'settings_api#get', 'url' => '/api/1.0/settings/get', 'verb' => 'POST'],
    ['name' => 'settings_api#set', 'url' => '/api/1.0/settings/set', 'verb' => 'POST'],
    ['name' => 'settings_api#list', 'url' => '/api/1.0/settings/list', 'verb' => 'GET'],
    ['name' => 'settings_api#list', 'url' => '/api/1.0/settings/list', 'verb' => 'POST', 'postfix' => 'POST'],
    ['name' => 'settings_api#reset', 'url' => '/api/1.0/settings/reset', 'verb' => 'POST'],

    ['name' => 'service_api#generate_password', 'url' => '/api/1.0/service/password', 'verb' => 'GET'],
    [
        'name'    => 'service_api#generate_password',
        'url'     => '/api/1.0/service/password',
        'verb'    => 'POST',
        'postfix' => 'POST'
    ],
    [
        'name'     => 'service_api#get_favicon',
        'url'      => '/api/1.0/service/favicon/{domain}/{size}',
        'verb'     => 'GET',
        'defaults' => ['domain' => 'default', 'size' => 32]
    ],
    [
        'name'     => 'service_api#get_avatar',
        'url'      => '/api/1.0/service/avatar/{user}/{size}',
        'verb'     => 'GET',
        'defaults' => ['user' => '', 'size' => 32]
    ],
    [
        'name'     => 'service_api#get_preview',
        'url'      => '/api/1.0/service/preview/{domain}/{view}/{width}/{height}',
        'verb'     => 'GET',
        'defaults' => ['domain' => 'default', 'view' => 'desktop', 'width' => 640, 'height' => '360...']
    ],
    ['name' => 'service_api#coffee', 'url' => '/api/1.0/service/coffee', 'verb' => 'GET'],


    ['name' => 'connect#request', 'url' => 'link/connect/request', 'verb' => 'GET'],
    ['name' => 'connect#await', 'url' => 'link/connect/await', 'verb' => 'GET'],
    ['name' => 'connect#reject', 'url' => 'link/connect/reject', 'verb' => 'GET'],
    ['name' => 'connect#confirm', 'url' => 'link/connect/confirm', 'verb' => 'GET'],
    ['name' => 'connect#confirm', 'url' => 'link/connect/confirm', 'verb' => 'POST', 'postfix' => 'POST'],
    ['name' => 'connect#apply', 'url' => 'link/connect/apply', 'verb' => 'POST'],

    ['name' => 'tag_api#preflighted_cors', 'url' => '/api/1.0/tag/{path}', 'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']],
    ['name' => 'share_api#preflighted_cors', 'url' => '/api/1.0/share/{path}', 'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']],
    ['name' => 'folder_api#preflighted_cors', 'url' => '/api/1.0/folder/{path}', 'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']],
    ['name' => 'session_api#preflighted_cors', 'url' => '/api/1.0/session/{path}', 'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']],
    ['name' => 'account_api#preflighted_cors', 'url' => '/api/1.0/account/{path}', 'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']],
    ['name' => 'service_api#preflighted_cors', 'url' => '/api/1.0/service/{path}', 'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']],
    ['name' => 'settings_api#preflighted_cors', 'url' => '/api/1.0/setting/{path}', 'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']],
    ['name' => 'keychain_api#preflighted_cors', 'url' => '/api/1.0/keychain/{path}', 'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']],
    ['name' => 'password_api#preflighted_cors', 'url' => '/api/1.0/password/{path}', 'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']]
];

$resources = [
    'admin_settings' => ['url' => '/admin/settings'],
    'admin_caches' => ['url' => '/admin/caches'],
];

if(\OC::$server->getConfig()->getAppValue(Application::APP_NAME, 'legacy_api_enabled', true)) {
    $resources['legacy_category_api'] = ['url' => '/api/0.1/categories'];
    $resources['legacy_password_api'] = ['url' => '/api/0.1/passwords'];
    $resources['legacy_version_api'] = ['url' => '/api/0.1/version'];
    $routes[]  = ['name' => 'legacy_version_api#preflighted_cors', 'url' => '/api/0.1/version/{path}', 'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']];
    $routes[]  = ['name' => 'legacy_category_api#preflighted_cors', 'url' => '/api/1.0/category/{path}', 'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']];
    $routes[]  = ['name' => 'legacy_password_api#preflighted_cors', 'url' => '/api/1.0/passwords/{path}', 'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']];
}

return [
    'resources' => $resources,
    'routes'    => $routes
];
