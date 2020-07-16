<?php
return [
    'usercenter' => [
        'host' => env('USERCENTER_HOST'),
        'api' => [
            'fetch'=>[
                'uri' => '/api/v1/user/fetch',
                'action'=>'post'
                ],
            'login_uri' => '/api/v1/auth/login',
            'search_uri' => '/api/v1/user/search',
            'detail_uri' => '/api/v1/user',
        ],
        'fields' => ['username', 'email', 'mobile', 'wechat_id', 'account']
    ],
    'auth' => [
        'message' => [
            'user_has_not_exists' => '用户不存在'
        ]
    ]
];