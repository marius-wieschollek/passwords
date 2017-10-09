<?php

namespace OCA\Passwords\AppInfo;

$application = new Application();
$application->registerRoutes($this, [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'access#index', 'url' => '/authenticate', 'verb' => 'GET'],
        ['name' => 'admin_settings#set', 'url' => '/admin/set', 'verb' => 'POST'],
        ['name' => 'admin_settings#cache', 'url' => '/admin/cache', 'verb' => 'POST'],

        ['name' => 'authorisation_api#info', 'url' => '/api/1.0/authorisation/info', 'verb' => 'GET'],
        ['name' => 'authorisation_api#request', 'url' => '/api/1.0/authorisation/request', 'verb' => 'POST'],
        ['name' => 'authorisation_api#revoke', 'url' => '/api/1.0/authorisation/revoke', 'verb' => 'POST'],

        ['name' => 'password_api#find', 'url' => '/api/1.0/password/find', 'verb' => 'POST'],
        ['name' => 'password_api#list', 'url' => '/api/1.0/password/list', 'verb' => 'GET'],
        ['name' => 'password_api#show', 'url' => '/api/1.0/password/show/{id}', 'verb' => 'GET'],
        ['name' => 'password_api#list', 'url' => '/api/1.0/password/list', 'verb' => 'POST', 'postfix' => 'POST'],
        ['name' => 'password_api#show', 'url' => '/api/1.0/password/show/{id}', 'verb' => 'POST', 'postfix' => 'POST'],
        ['name' => 'password_api#create', 'url' => '/api/1.0/password/create', 'verb' => 'POST'],
        ['name' => 'password_api#update', 'url' => '/api/1.0/password/update/{id}', 'verb' => 'PATCH'],
        ['name' => 'password_api#delete', 'url' => '/api/1.0/password/delete/{id}', 'verb' => 'DELETE'],

        ['name' => 'folder_api#find', 'url' => '/api/1.0/folder/find', 'verb' => 'POST'],
        ['name' => 'folder_api#list', 'url' => '/api/1.0/folder/list', 'verb' => 'GET'],
        ['name' => 'folder_api#show', 'url' => '/api/1.0/folder/show/{id}', 'verb' => 'GET'],
        ['name' => 'folder_api#list', 'url' => '/api/1.0/folder/list', 'verb' => 'POST', 'postfix' => 'POST'],
        ['name' => 'folder_api#show', 'url' => '/api/1.0/folder/show/{id}', 'verb' => 'POST', 'postfix' => 'POST'],
        ['name' => 'folder_api#create', 'url' => '/api/1.0/folder/create', 'verb' => 'POST'],
        ['name' => 'folder_api#update', 'url' => '/api/1.0/folder/update/{id}', 'verb' => 'PATCH'],
        ['name' => 'folder_api#delete', 'url' => '/api/1.0/folder/delete/{id}', 'verb' => 'DELETE'],

        ['name' => 'tag_api#find', 'url' => '/api/1.0/tag/find', 'verb' => 'POST'],
        ['name' => 'tag_api#list', 'url' => '/api/1.0/tag/list', 'verb' => 'GET'],
        ['name' => 'tag_api#show', 'url' => '/api/1.0/tag/show/{id}', 'verb' => 'GET'],
        ['name' => 'tag_api#list', 'url' => '/api/1.0/tag/list', 'verb' => 'POST', 'postfix' => 'POST'],
        ['name' => 'tag_api#show', 'url' => '/api/1.0/tag/show/{id}', 'verb' => 'POST', 'postfix' => 'POST'],
        ['name' => 'tag_api#create', 'url' => '/api/1.0/tag/create', 'verb' => 'POST'],
        ['name' => 'tag_api#update', 'url' => '/api/1.0/tag/update/{id}', 'verb' => 'PATCH'],
        ['name' => 'tag_api#delete', 'url' => '/api/1.0/tag/delete/{id}', 'verb' => 'DELETE'],

        ['name' => 'share_api#find', 'url' => '/api/1.0/share/find', 'verb' => 'POST'],
        ['name' => 'share_api#list', 'url' => '/api/1.0/share/list', 'verb' => 'GET'],
        ['name' => 'share_api#show', 'url' => '/api/1.0/share/show/{id}', 'verb' => 'GET'],
        ['name' => 'share_api#list', 'url' => '/api/1.0/share/list', 'verb' => 'POST', 'postfix' => 'POST'],
        ['name' => 'share_api#show', 'url' => '/api/1.0/share/show/{id}', 'verb' => 'POST', 'postfix' => 'POST'],
        ['name' => 'share_api#create', 'url' => '/api/1.0/share/create', 'verb' => 'POST'],
        ['name' => 'share_api#update', 'url' => '/api/1.0/share/update/{id}', 'verb' => 'PATCH'],
        ['name' => 'share_api#delete', 'url' => '/api/1.0/share/delete/{id}', 'verb' => 'DELETE'],

        ['name' => 'settings_api#list', 'url' => '/api/1.0/setting/list', 'verb' => 'GET'],
        ['name' => 'settings_api#get', 'url' => '/api/1.0/setting/get/{id}', 'verb' => 'GET'],
        ['name' => 'settings_api#set', 'url' => '/api/1.0/setting/set/{id}', 'verb' => 'POST'],
        ['name' => 'settings_api#reset', 'url' => '/api/1.0/setting/reset/{id}', 'verb' => 'GET'],

        ['name' => 'service_api#generate_password', 'url' => '/api/1.0/service/password', 'verb' => 'GET'],
        ['name'    => 'service_api#generate_password',
         'url'     => '/api/1.0/service/password',
         'verb'    => 'POST',
         'postfix' => 'POST'
        ],
        [
            'name'     => 'service_api#get_favicon',
            'url'      => '/api/1.0/service/icon/{domain}/{size}',
            'verb'     => 'GET',
            'defaults' => ['domain' => '', 'size' => 24]
        ],
        [
            'name'     => 'service_api#get_preview',
            'url'      => '/api/1.0/service/image/{domain}/{view}/{width}/{height}',
            'verb'     => 'GET',
            'defaults' => ['domain' => '', 'view' => 'desktop', 'width' => 550, 'height' => 0]
        ],
    ]
]);
