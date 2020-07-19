<?php
return [
    'auth' => [
        'login' => [
            'success' => [
                'http_code' => 200,
                'code' => 'ZBASEMENT_CODE_USER_LOGIN_SUCCESS',
                'status' => true,
                'message' => '登录成功!'
            ],
            'failed' => [
                'http_code' => 401,
                'code' => 'ZBASEMENT_CODE_USER_LOGIN_FAILED',
                'status' => false,
                'message' => '登录失败!'
            ],
            'validation' => [
                'http_code' => 422,
                'code' => 'ZBASEMENT_CODE_USER_LOGIN_VALIDATION',
                'status' => false,
                'message' => '登录参数验证失败!'
            ],
            'load'=>[
                'rules'=>[
                    'http_code' => 200,
                    'code' => 'ZBASEMENT_CODE_USER_LOGIN_LOAD_RULES',
                    'status' => true,
                    'message' => '登录操作所需验证规则加载成功!'
                ],
                'messages'=>[
                    'http_code' => 200,
                    'code' => 'ZBASEMENT_CODE_USER_LOGIN_LOAD_MESSAGES',
                    'status' => true,
                    'message' => '登录操作所需验证规则的提示信息加载成功!'
                ],
            ],
        ],
    ],
    'user' => [
        'index' => [
            'success' => [
                'http_code' => 200,
                'code' => 'ZBASEMENT_CODE_USER_INDEX_SUCCESS',
                'status' => true,
                'message' => '获取用户列表成功!'
            ],
            'error' => [
                'http_code' => 403,
                'code' => 'ZBASEMENT_CODE_USER_INDEX_ERROR',
                'status' => false,
                'message' => '获取用户列表失败!'
            ],
            'validation' => [
                'http_code' => 422,
                'code' => 'ZBASEMENT_CODE_USER_INDEX_VALIDATION',
                'status' => false,
                'message' => '用户列表输入参数验证失败!'
            ],
            'load'=>[
                'rules'=>[
                    'http_code' => 200,
                    'code' => 'ZBASEMENT_CODE_USER_INDEX_LOAD_RULES',
                    'status' => true,
                    'message' => '用户列表操作所需验证规则加载成功!'
                ],
                'messages'=>[
                    'http_code' => 200,
                    'code' => 'ZBASEMENT_CODE_USER_INDEX_LOAD_MESSAGES',
                    'status' => true,
                    'message' => '用户列表操作所需验证规则的提示信息加载成功!'
                ],
            ],
        ],
        'fetch' => [
            'success' => [
                'http_code' => 200,
                'code' => 'ZBASEMENT_CODE_USER_FETCH_SUCCESS',
                'status' => true,
                'message' => '获取单个用户成功!'
            ],
            'error' => [
                'http_code' => 403,
                'code' => 'ZBASEMENT_CODE_USER_FETCH_ERROR',
                'status' => false,
                'message' => '获取单个用户失败!'
            ],
            'validation' => [
                'http_code' => 422,
                'code' => 'ZBASEMENT_CODE_USER_FETCH_VALIDATION',
                'status' => false,
                'message' => '查找单个用户输入参数验证失败!'
            ],
            'load'=>[
                'rules'=>[
                    'http_code' => 200,
                    'code' => 'ZBASEMENT_CODE_USER_FETCH_LOAD_RULES',
                    'status' => true,
                    'message' => '查找单个用户操作所需验证规则加载成功!'
                ],
                'messages'=>[
                    'http_code' => 200,
                    'code' => 'ZBASEMENT_CODE_USER_FETCH_LOAD_MESSAGES',
                    'status' => true,
                    'message' => '查找单个用户操作所需验证规则的提示信息加载成功!'
                ],
            ],
        ],
        'show' => [
            'success' => [
                'http_code' => 200,
                'code' => 'ZBASEMENT_CODE_USER_SHOW_SUCCESS',
                'status' => true,
                'message' => '获取用户信息成功!'
            ],
            'error' => [
                'http_code' => 403,
                'code' => 'ZBASEMENT_CODE_USER_SHOW_ERROR',
                'status' => false,
                'message' => '获取用户信息失败!'
            ],
            'validation' => [
                'http_code' => 422,
                'code' => 'ZBASEMENT_CODE_USER_SHOW_VALIDATION',
                'status' => false,
                'message' => '用户信息输入参数验证失败!'
            ],
            'load'=>[
                'rules'=>[
                    'http_code' => 200,
                    'code' => 'ZBASEMENT_CODE_USER_SHOW_LOAD_RULES',
                    'status' => true,
                    'message' => 'USER数据详情展示操作所需验证规则加载成功!'
                ],
                'messages'=>[
                    'http_code' => 200,
                    'code' => 'ZBASEMENT_CODE_USER_SHOW_LOAD_MESSAGES',
                    'status' => true,
                    'message' => 'USER数据详情展示操作所需验证规则的提示信息加载成功!'
                ],
            ],
        ],
        'register' => [
            'success' => [
                'http_code' => 201,
                'code' => 'ZBASEMENT_CODE_USER_REGISTER_SUCCESS',
                'status' => true,
                'message' => '注册用户信息成功!'
            ],
            'error' => [
                'http_code' => 403,
                'code' => 'ZBASEMENT_CODE_USER_REGISTER_ERROR',
                'status' => false,
                'message' => '注册用户信息失败!'
            ],
            'validation' => [
                'http_code' => 422,
                'code' => 'ZBASEMENT_CODE_USER_REGISTER_VALIDATION',
                'status' => false,
                'message' => '注册用户时输入参数验证失败!'
            ],
        ],
        'updatepassword'=>[
                'success' => [
                    'http_code' => 200,
                    'code' => 'ZBASEMENT_CODE_USER_UPDATEPASSWORD_SUCCESS',
                    'status' => true,
                    'message' => '更新用户密码成功!'
                ],
                'error' => [
                    'http_code' => 403,
                    'code' => 'ZBASEMENT_CODE_USER_UPDATEPASSWORD_ERROR',
                    'status' => false,
                    'message' => '更新用户密码失败!'
                ],
                'validation' => [
                    'http_code' => 422,
                    'code' => 'ZBASEMENT_CODE_USER_UPDATEPASSWORD_VALIDATION',
                    'status' => false,
                    'message' => '修改密码输入参数验证失败!'
                ],
            'load'=>[
                'rules'=>[
                    'http_code' => 200,
                    'code' => 'ZBASEMENT_CODE_USER_UPDATEPASSWORD_LOAD_RULES',
                    'status' => true,
                    'message' => '更新密码操作所需验证规则加载成功!'
                ],
                'messages'=>[
                    'http_code' => 200,
                    'code' => 'ZBASEMENT_CODE_USER_UPDATEPASSWORD_LOAD_MESSAGES',
                    'status' => true,
                    'message' => '更新密码操作所需验证规则的提示信息加载成功!'
                ],
            ],
        ],
        'field'=>[
            'search'=>[
                'failed'=>[
                    'http_code' => 500,
                    'code' => 'ZBASEMENT_CODE_USER_FIELD_SEARCH_FAILED',
                    'status' => false,
                    'message' => 'USER数据模型中字段丢失!'
                ],
            ],
        ],
        'store'=>[
            'submit'=>[
                'success'=>[
                    'http_code' => 201,
                    'code' => 'ZBASEMENT_CODE_USER_STORE_SUBMIT_SUCCESS',
                    'status' => true,
                    'message' => '已经接收到USER数据插入提交申请!'
                ]
            ],
            'load'=>[
                'rules'=>[
                    'http_code' => 200,
                    'code' => 'ZBASEMENT_CODE_USER_STORE_LOAD_RULES',
                    'status' => true,
                    'message' => 'USER数据插入操作所需验证规则加载成功!'
                ],
                'messages'=>[
                    'http_code' => 200,
                    'code' => 'ZBASEMENT_CODE_USER_STORE_LOAD_MESSAGES',
                    'status' => true,
                    'message' => 'USER数据插入操作所需验证规则的提示信息加载成功!'
                ],
            ],
            'validation' => [
                'http_code' => 422,
                'code' => 'ZBASEMENT_CODE_USER_STORE_VALIDATION',
                'status' => false,
                'message' => '新建用户帐户时输入参数验证失败!'
            ],
        ]
    ]
];
