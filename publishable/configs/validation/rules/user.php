<?php
return [

            'username' =>[
                [
                    'rule'=>[
                        'nullable',
                        'min:2',
                        'max:255',
                        'regex:/^[^0-9]/'
                    ],
                    'action'=>[
                        'store',
                    ],
                ],
                [
                    'rule'=>[
                        'required_without_all:email,mobile,wechat_id',
                    ],
                    'action'=>[
                        'store',
                    ],
                ],
                [
                    'rule'=>[
                        'new \Zijinghua\Zbasement\Rules\Unique:username,mobile',
                    ],
                    'action'=>[
                        'store',
                    ],
                ]

            ],
            'email' =>[
                [
                    'rule'=>[
                        'nullable',
                        'email',
                    ],
                    'action'=>[
                        'store',
                    ],
                ],
                [
                    'rule'=>[
                        'new \Zijinghua\Zbasement\Rules\Unique',
                    ],
                    'action'=>[
                        'store'
                    ],
                ]
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
                        'store',
                    ],
                ],
                [
                    'rule'=>[
                        'new \Zijinghua\Zbasement\Rules\Unique',
                    ],
                    'action'=>[
                        'store'
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
                        'login',
                    ],
                ],

            ],
            'password' => [
                [
                    'rule'=>[
                        'min:6',
                        'max:255',
                    ],
                    'action'=>[
                        'store',
                    ],
                ],
                [
                    'rule'=>[
                        'required_with:username,email,mobile'
                    ],
                    'action'=>[
                        'store'
                    ],
                ],
                [
                    'rule'=>[
                        'required'
                    ],
                    'action'=>[
                        'updatePassword',
                    ],
                ],
            ],
            'pre_password' => [
                [
                    'rule'=>[
                        'min:6',
                        'max:255',
                        'required'
                    ],
                    'action'=>[
                        'updatePassword',
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
                        'store',
                    ],
                ],
                [
                    'rule'=>[
                        'new \Zijinghua\Zbasement\Rules\Unique',
                    ],
                    'action'=>[
                        'store'
                    ],
                ],
            ],
            'currentPage' => [
                [
                    'rule' => [
                        'integer',
                        'min:0',
                    ],
                    'action'=>[
                        'index'
                    ],
                ]
            ],
            'pageSize' => [
                [
                    'rule' => [
                        'integer',
                        'min:0',
                    ],
                    'action'=>[
                        'index'
                    ],
                ]

            ],
//            'uuid' => [
//                [
//                    'rule' => [
//                        'required',
//                        'regex:/[A-Za-z0-9]{8}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{12}/',
//                        'new \Zijinghua\Zbasement\Rules\Has'
//                    ],
//                    'action'=>[
//                        'show','update','updatePassword'
//                    ],
//                ]
//
//
//            ],

];