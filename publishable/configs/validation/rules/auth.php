<?php
return [
            'username' =>[
                [
                    'rule'=>[
                        'nullable',
                        'min:2',
                        'max:255',
                        'regex:/^[^0-9]/',
                        'required_without_all:email,mobile,account,oauth_code',
                    ],
                    'action'=>[
                        'login',
                    ],
                ],
            ],
            'email' =>[
                [
                    'rule'=>[
                        'nullable',
                        'email',
                    ],
                    'action'=>[
                        'login',
                    ],
                ],
            ],
            'mobile' => [
                [
                    'rule'=>[
                        'nullable',
                        'regex:/^(1(([3456789][0-9])|(47)|[8][01236789]))\d{8}$/' ,
                        'min:8',
                        'max:255',
                    ],
                    'action'=>[
                        'login',
                    ],
                ],
            ],
            'account'=>[
                [
                    'rule'=>[
                        'nullable',
                        'min:2',
                        'max:255',
                    ],
                    'action'=>[
                        'login'
                    ],
                ],

            ],
            'wechat_id' => [
                [
                    'rule'=>[
                        'nullable',
                        'min:6',
                        'max:255',
                    ],
                    'action'=>[
                        'login',
                    ],
                ],
            ],
            'password' => [
                [
                    'rule'=>[
                        'min:6',
                        'max:255',
                        'required_with:username,email,mobile,account',
                    ],
                    'action'=>[
                        'login',
                    ],
                ],
            ],
        'oauth_code' => [
            [
                'rule'=>[
                    'nullable',
                    'min:6',
                    'max:255',
                ],
                'action'=>[
                    'login',
                ],
            ],
        ],
        'oauth_code_type' => [
            [
                'rule'=>[
                    'in:base,userinfo',
                    'min:6',
                    'max:255',
                ],
                'action'=>[
                    'login',
                ],
            ],
        ],

    ];
