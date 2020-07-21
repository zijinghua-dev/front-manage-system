<?php
return [

            'username'=>[
                [
                    'message'=>[
                        'min'=>'username最少2个字符。',
                        'max'=>'username最多不超过255个字符。',
                        'nullable' =>'username不能为null。',
                        'regex'=>'用户名的首字符不能是数字。',
                        'required_without_all'=>'至少使用username,email,mobile,微信账号当中的一种登录方式',
                    ],
                    'action'=>[
                        'login',
                    ]
                ],
            ],
            'email'=>[
                [
                    'message'=>[
                        'nullable' =>'email不能为null。',
                        'email'=>'必须符合Email格式要求。',
                    ],
                    'action'=>[
                        'login',
                    ]
                ]

            ],
            'mobile'=>[
                [
                    'message'=>[
                        'nullable' => 'mobile不能为null。',
                        'regex'=> '必须符合手机号码格式要求。' ,
                    ],
                    'action'=>[
                        'login',
                    ]
                ]

            ],
            'password' => [
                [
                    'message' => [
                        'min'=>'password最少6位。',
                        'max'=>'password最长255位。',
                        'required_with'=>'必须输入密码。',
                    ],
                    'action'=>[
                        'login',
                    ]
                ],
            ],


];