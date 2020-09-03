<?php
return [

    'submit'=>[
        'success' => [
            'http_code' => 200,
            'code' => 'ZBASEMENT_CODE_ASSIGN_SUBMIT_SUCCESS',
            'status' => true,
            'message' => '配置权限/角色成功!'
        ],
        'failed' => [
            'http_code' => 403,
            'code' => 'ZBASEMENT_CODE_ASSIGN_SUBMIT_FAILED',
            'status' => false,
            'message' => '配置权限/角色到组失败!'
        ],
    ],

    'validation' => [
        'failed'=>[
            'http_code' => 422,
            'code' => 'ZBASEMENT_CODE_ASSIGN_VALIDATION',
            'status' => false,
            'message' => '配置权限/角色输入参数验证失败!'
        ],
    ],
    'load'=>[
        'rules'=>[
            'success'=>[
                'http_code' => 200,
                'code' => 'ZBASEMENT_CODE_ASSIGN_LOAD_RULES_SUCCESS',
                'status' => true,
                'message' => '配置权限/角色操作所需验证规则加载成功!'
            ],
            'failed'=>[
                'http_code' => 403,
                'code' => 'ZBASEMENT_CODE_ASSIGN_LOAD_RULES_FAILED',
                'status' => false,
                'message' => '配置权限/角色操作所需验证规则加载失败!'
            ],

        ],
        'messages'=>[
            'success'=>[
                'http_code' => 200,
                'code' => 'ZBASEMENT_CODE_ASSIGN_LOAD_MESSAGES_SUCCESS',
                'status' => true,
                'message' => '配置权限/角色操作所需验证规则的提示信息加载成功!'
            ],
            'failed'=>[
                'http_code' => 403,
                'code' => 'ZBASEMENT_CODE_ASSIGN_LOAD_MESSAGES_FAILED',
                'status' => false,
                'message' => '配置权限/角色操作所需验证规则的提示信息加载失败!'
            ],

        ],
    ],

];