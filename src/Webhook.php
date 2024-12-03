<?php
/**
 * 作者:郭磊
 * 邮箱:174000902@qq.com
 * 电话:15210720528
 * Git:https://github.com/guolei19850528/laravel-workwx
 */

namespace Guolei19850528\Laravel\Workwx;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

/**
 * 群机器人配置
 * @see https://developer.work.weixin.qq.com/document/path/91770
 */
class Webhook
{
    /**
     * Base Url
     * @var string
     */
    protected string $baseUrl = '';

    /**
     * Key
     * @var string
     */
    protected string $key = '';

    /**
     * userid的列表，提醒群中的指定成员(@某个成员)，@all表示提醒所有人，如果开发者获取不到userid，可以使用mentioned_mobile_list
     * @var array|Collection
     */
    protected array|Collection $mentionedList = [];

    /**
     * 手机号列表，提醒手机号对应的群成员(@某个成员)，@all表示提醒所有人
     * @var array|Collection
     */
    protected array|Collection $mentionedMobileList = [];

    /**
     * Base Url
     * @return string
     */
    public function getBaseUrl(): string
    {
        if (\str($this->baseUrl)->endsWith('/')) {
            $this->baseUrl = \str($this->baseUrl)->substr(0, -1)->toString();
        }
        return $this->baseUrl;
    }

    /**
     * Base Url
     * @param string $baseUrl
     * @return $this
     */
    public function setBaseUrl(string $baseUrl = ''): Webhook
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    /**
     * Key
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Key
     * @param string $key
     * @return $this
     */
    public function setKey(string $key = ''): Webhook
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return array|Collection
     */
    public function getMentionedList(): array|Collection
    {
        return $this->mentionedList;
    }

    /**
     * @param array|Collection $mentionedList
     * @return $this
     */
    public function setMentionedList(array|Collection $mentionedList = []): Webhook
    {
        $this->mentionedList = $mentionedList;
        return $this;
    }

    /**
     */
    public function getMentionedMobileList(): array|Collection
    {
        return $this->mentionedMobileList;
    }

    /**
     * @param array|Collection $mentionedMobileList
     * @return $this
     */
    public function setMentionedMobileList(array|Collection $mentionedMobileList = []): Webhook
    {
        $this->mentionedMobileList = $mentionedMobileList;
        return $this;
    }


    /**
     * Webhook Class Construct Function
     * @param string $key Key
     * @param array|Collection $mentionedList userid的列表，提醒群中的指定成员(@某个成员)，@all表示提醒所有人，如果开发者获取不到userid，可以使用mentioned_mobile_list
     * @param array|Collection $mentionedMobileList 手机号列表，提醒手机号对应的群成员(@某个成员)，@all表示提醒所有人
     * @param string $baseUrl Base Url
     */
    public function __construct(
        string           $key = '',
        array|Collection $mentionedList = [],
        array|Collection $mentionedMobileList = [],
        string           $baseUrl = 'https://qyapi.weixin.qq.com/'
    )
    {
        $this->setKey($key);
        $this->setMentionedList($mentionedList);
        $this->setMentionedMobileList($mentionedMobileList);
        $this->setBaseUrl($baseUrl);
    }

    /**
     * Send
     * @param array|Collection $data Post Data
     * @param array|Collection $options Replace the specified options on the request
     * @param string $url
     * @return bool
     */
    public function send(
        array|Collection $data = [],
        array|Collection $options = [],
        string           $url = '/cgi-bin/webhook/send?key={key}'
    ): bool
    {
        $response = Http::baseUrl($this->getBaseUrl())
            ->asJson()
            ->withOptions($options)
            ->withUrlParameters(
                [
                    'key' => $this->key
                ]
            )->post($url, $data);
        if ($response->ok()) {
            $json = $response->json();
            if (Validator::make($json, ['errcode' => 'required|integer|size:0'])->messages()->isEmpty()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Send Text
     * @see https://developer.work.weixin.qq.com/document/path/91770#%E6%96%87%E6%9C%AC%E7%B1%BB%E5%9E%8B
     * @param string $content 文本内容，最长不超过2048个字节，必须是utf8编码
     * @param array|Collection $mentionedList userid的列表，提醒群中的指定成员(@某个成员)，@all表示提醒所有人，如果开发者获取不到userid，可以使用mentioned_mobile_list
     * @param array|Collection $mentionedMobileList 手机号列表，提醒手机号对应的群成员(@某个成员)，@all表示提醒所有人
     * @param array|Collection $options Replace the specified options on the request
     * @param string $url
     * @return bool
     */
    public function sendText(
        string           $content = '',
        array|Collection $mentionedList = [],
        array|Collection $mentionedMobileList = [],
        array|Collection $options = [],
        string           $url = '/cgi-bin/webhook/send?key={key}'
    ): bool
    {
        $data = \collect([
            "msgtype" => "text",
            "text" => [
                "content" => $content,
                "mentioned_list" => \collect($this->getMentionedList())->replaceRecursive($mentionedList),
                "mentioned_mobile_list" => \collect($this->getMentionedMobileList())->replaceRecursive($mentionedMobileList),
            ]
        ])->toArray();
        return $this->send($data, $options, $url);
    }

    /**
     * 发送文件
     * @see https://developer.work.weixin.qq.com/document/path/91770#%E6%96%87%E4%BB%B6%E7%B1%BB%E5%9E%8B
     * @param string $mediaId 文件id，通过下文的文件上传接口获取
     * @param array|Collection $options Replace the specified options on the request
     * @param string $url
     * @return bool
     */
    public function sendFile(
        string           $mediaId = '',
        array|Collection $options = [],
        string           $url = '/send?key={key}'
    ): bool
    {
        $data = \collect([
            "msgtype" => "file",
            "file" => [
                "media_id" => $mediaId,
            ]
        ])->toArray();
        return $this->send($data, $options, $url);
    }

    /**
     * 发送音频
     * @see https://developer.work.weixin.qq.com/document/path/91770#%E8%AF%AD%E9%9F%B3%E7%B1%BB%E5%9E%8B
     * @param string $mediaId 文件id，通过下文的文件上传接口获取
     * @param array|Collection $options Replace the specified options on the request
     * @param string $url
     * @return bool
     */
    public function sendVoice(
        string           $mediaId = '',
        array|Collection $options = [],
        string           $url = '/send?key={key}'
    ): bool
    {
        $data = \collect([
            "msgtype" => "voice",
            "voice" => [
                "media_id" => $mediaId,
            ]
        ])->toArray();
        return $this->send($data, $options, $url);
    }

    /**
     * 上传
     * @see https://developer.work.weixin.qq.com/document/path/91770#%E6%96%87%E4%BB%B6%E4%B8%8A%E4%BC%A0%E6%8E%A5%E5%8F%A3
     * @param array|Collection $attach Attach a file to the request.
     * @param string $type 文件类型，分别有语音(voice)和普通文件(file)
     * @param array|Collection $options Replace the specified options on the request
     * @param string $url
     * @return string|null
     */
    public function uploadMedia(
        array|Collection $attach = [],
        string           $type = 'file',
        array|Collection $options = [],
        string           $url = '/upload_media?key={key}&type={type}'
    ): string|null
    {
        $response = Http::baseUrl($this->getBaseUrl())
            ->asMultipart()
            ->attach(...$attach)
            ->withOptions($options)
            ->withUrlParameters(
                [
                    'key' => $this->key,
                    'type' => $type,
                ]
            )->post($url);
        if ($response->ok()) {
            $json = $response->json();
            if (Validator::make($json, ['errcode' => 'required|integer|size:0'])->messages()->isEmpty()) {
                return \str(\data_get($json, 'media_id'))->toString() ?? null;
            }
        }
        return null;
    }

}
