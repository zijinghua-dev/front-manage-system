<?php
return [
    'usercenter' => [
        'host' => env('USERCENTER_HOST'),
        'api' => [
            'login_uri' => '/api/v1/auth/login',
            'search_uri' => '/api/v1/user/search',
            'detail_uri' => '/api/v1/user',
        ],
        'fields' => ['username', 'email', 'mobile', 'wechat_id', 'account']
    ],
    'auth' => [
        'failed' => [
            'user_has_not_exists' => [
                'message' => '用户不存在'
            ],
        ],
        'validation' => [
            'message' => '用户名密码错误'
        ]
    ]
];