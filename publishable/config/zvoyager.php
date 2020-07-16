<?php
return [
    'usercenter' => [
        'host' => env('USERCENTER_HOST'),
        'api' => [
            'login_uri' => 'http://uc.test/api/v1/auth/login',
            'search_uri' => 'http://uc.test/api/v1/user',
            'fetch'=>[
                'uri' => '/api/v1/user/fetch',
                'action'=>'post'
                ],
        ],
        'fields' => ['username', 'email', 'mobile', 'wechat_id', 'account']
    ],
    'auth' => [
        'message' => [
            'user_has_not_exists' => '用户不存在'
        ]
    ]
];