# laravel-workweixin

A Work Weixin Laravel Library By Guolei

# Installation

```shell
composer require guolei19850528/laravel-workwx
```

# Example

## Webhook

```php
use Guolei19850528\Laravel\Workwx\Webhook;

$webhook = new Webhook('your key',['name'],['mobile']);

$state=$webhook->sendText('your content',['name'],['mobile']);

$mediaId = $webhook->uploadMedia(
            [
                'name' => 'name',
                'filename' => 'filename',
                'contents' => fopen('your file path', 'rb'),
            ],
            'file'
        );

if (\str($mediaId)->isNotEmpty()) {
    $state=$webhook->sendFile($mediaId);
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
$state=$server->messageSend([
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
