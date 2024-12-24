<?php
/**
 * 作者:郭磊
 * 邮箱:174000902@qq.com
 * 电话:15210720528
 * Git:https://github.com/guolei19850528/laravel-workwx
 */

/**
 * laravel-workwx config file
 */
return [
    //群机器人 Webhook 配置
    'webhook' => [
        'your key' => [
            'baseUrl' => '',
            'key' => '',
            'mentionedList' => [],
            'mentionedMobileList' => [],
        ],
    ],
    //Server Api 配置
    'server' => [
        'your key' => [
            'baseUrl' => 'https://qyapi.weixin.qq.com/cgi-bin/',
            'corpid' => '',
            'corpsecret' => '',
            'agentid' => '',
        ],
    ]
];
