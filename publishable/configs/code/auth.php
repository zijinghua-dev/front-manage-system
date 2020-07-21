<?php
return [

        'login' => [
            'success' => [
                'http_code' => 200,
                'code' => 'ZBASEMENT_CODE_AUTH_LOGIN_SUCCESS',
                'status' => true,
                'message' => '登录成功!'
            ],
            'failed' => [
                'http_code' => 401,
                'code' => 'ZBASEMENT_CODE_AUTH_LOGIN_FAILED',
                'status' => false,
                'message' => '登录失败!'
            ],
            'validation' => [
                'http_code' => 422,
                'code' => 'ZBASEMENT_CODE_AUTH_LOGIN_VALIDATION',
                'status' => false,
                'message' => '登录参数验证失败!'
            ],
            'load'=>[
                'rules'=>[
                    'success'=>[
                        'http_code' => 200,
                        'code' => 'ZBASEMENT_CODE_AUTH_LOGIN_LOAD_RULES_SUCCESS',
                        'status' => true,
                        'message' => '登录操作所需验证规则加载成功!'
                    ],
                    'failed'=>[
                        'http_code' => 403,
                        'code' => 'ZBASEMENT_CODE_AUTH_LOGIN_LOAD_RULES_FAILED',
                        'status' => false,
                        'message' => '登录操作所需验证规则加载失败!'
                    ],
                ],
                'messages'=>[
                    'success'=>[
                        'http_code' => 200,
                        'code' => 'ZBASEMENT_CODE_AUTH_LOGIN_LOAD_MESSAGES_SUCCESS',
                        'status' => true,
                        'message' => '登录操作所需验证规则的提示信息加载成功!'
                    ],
                    'failed'=>[
                        'http_code' => 403,
                        'code' => 'ZBASEMENT_CODE_AUTH_LOGIN_LOAD_MESSAGES_FAILED',
                        'status' => false,
                        'message' => '登录操作所需验证规则的提示信息加载失败!'
                    ],

                ],
            ],
        ],


];
