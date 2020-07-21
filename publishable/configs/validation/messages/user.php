<?php
return [


            'username'=>[
                [
                    'message'=>[
                        'min'=>'username最少2个字符。',
                        'max'=>'username最多不超过255个字符。',
                        'nullable' =>'username不能为null。',
                        'regex'=>'用户名的首字符不能是数字。',
                    ],
                    'action'=>[
                        'store'
                    ]
                ],
                [
                    'message'=>[
                        'required_without_all'=>'至少使用username,email,mobile,微信账号当中的一种注册方式'
                    ],
                    'action'=>[
                        'store'
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
                        'store'
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
                        'store'
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
                        'store','updatePassword'
                    ]
                ],
                [
                    'message' => [
                        'required'=>'必须输入密码。',
                    ],
                    'action'=>[
                        'updatePassword'
                    ]
                ],

            ],
            'pre_password' => [
                [
                    'message' => [
                        'min'=>'password最少6位。',
                        'max'=>'password最长255位。',
                        'required'=>'必须输入原密码。',
                    ],
                    'action'=>[
                        'updatePassword'
                    ]
                ],
            ],
            'wechat_id' => [
                [
                    'message'=>[
                        'nullable' => 'wechat_id不能为null。',
                        'min'=> 'wechat_id最少6位。',
                        'max'=> 'wechat_id最长255个字符。',
                    ],
                    'action'=>[
                        'store'
                    ]
                ]

            ],

            'uuid'=>[
                [
                    'message'=>[
                        'required'=>'必须输入用户ID',
                        'regex' => 'uuid格式不正确!',
                        'has'=>'不存在！',
                    ],
                    'action'=>[
                        'show','update','updatePassword'
                    ],
                ]

            ],


];