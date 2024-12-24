# laravel-workweixin [English](https://github.com/guolei19850528/laravel-workwx/blob/main/README.md)

郭磊开发的 Laravel 企业微信类库

# 安装

```shell
composer require guolei19850528/laravel-workwx
```

# 示例

## Webhook 

```php
use Guolei19850528\Laravel\Workwx\Webhook;

$webhook = new Webhook('key','base url',['mentioned list'],['mentioned mobile list']);

$state=$webhook->send(
    $webhook->sendTextFormatter(
        'test message'
    )
);
if ($state){
    print_r('success');
}else{
    print_r('failed');
}

$mediaId = $webhook->uploadMedia(
            [
                'name' => 'name',
                'filename' => 'filename',
                'contents' => fopen('your file path', 'rb'),
            ],
            'file'
        );

if (\str($mediaId)->isNotEmpty()) {
    $state=$webhook->send($webhook->sendFileFormatter($mediaId));
    if ($state){
        print_r('success');
    }else{
        print_r('failed');
    }
}
```

## Server

```php
use Guolei19850528\Laravel\Workwx\Server;
$server=new Server(
            'your corpid',
            'your corpsecret',
            'your agentid'
        );
$state=$server->tokenWithCache()->messageSend([
    'touser'=>'',
    'msgtype'=>'text',
    'agentid'=>$server->getAgentid(),
    'text'=>[
        'content'=>'test message'
    ]
]);
if($state){
    print_r('successful');
}
```
