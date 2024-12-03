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
     * @param array|Collection|null $mentionedList
     * @return $this
     */
    public function setMentionedList(array|Collection|null $mentionedList = []): Webhook
    {
        $this->mentionedList = \collect($mentionedList);
        return $this;
    }

    /**
     */
    public function getMentionedMobileList(): array|Collection
    {
        return $this->mentionedMobileList;
    }

    /**
     * @param array|Collection|null $mentionedMobileList
     * @return $this
     */
    public function setMentionedMobileList(array|Collection|null $mentionedMobileList = []): Webhook
    {
        $this->mentionedMobileList = \collect($mentionedMobileList);
        return $this;
    }


    /**
     * Webhook Class Construct Function
     * @param string $key Key
     * @param array|Collection|null $mentionedList userid的列表，提醒群中的指定成员(@某个成员)，@all表示提醒所有人，如果开发者获取不到userid，可以使用mentioned_mobile_list
     * @param array|Collection|null $mentionedMobileList 手机号列表，提醒手机号对应的群成员(@某个成员)，@all表示提醒所有人
     * @param string $baseUrl Base Url
     */
    public function __construct(
        string                $key = '',
        array|Collection|null $mentionedList = [],
        array|Collection|null $mentionedMobileList = [],
        string                $baseUrl = 'https://qyapi.weixin.qq.com/'
    )
    {
        $this->setKey($key);
        $this->setMentionedList(\collect($mentionedList));
        $this->setMentionedMobileList(\collect($mentionedMobileList));
        $this->setBaseUrl($baseUrl);
    }

    /**
     * Send
     * @param array|Collection|null $data Post Data
     * @param array|Collection|null $options Replace the specified options on the request
     * @param \Closure|null $closure
     * @param string $url
     * @return bool
     */
    public function send(
        array|Collection|null $data = [],
        array|Collection|null $options = [],
        \Closure              $closure = null,
        string                $url = '/cgi-bin/webhook/send?key={key}'
    ): bool
    {
        $response = Http::baseUrl($this->getBaseUrl())
            ->asJson()
            ->withOptions(\collect($options)->toArray())
            ->withUrlParameters(
                [
                    'key' => $this->key
                ]
            )->post($url, \collect($data)->toArray());
        if ($closure) {
            return call_user_func($closure, $response);
        }
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
     * @param array|Collection|null $mentionedList userid的列表，提醒群中的指定成员(@某个成员)，@all表示提醒所有人，如果开发者获取不到userid，可以使用mentioned_mobile_list
     * @param array|Collection|null $mentionedMobileList 手机号列表，提醒手机号对应的群成员(@某个成员)，@all表示提醒所有人
     * @param array|Collection|null $options Replace the specified options on the request
     * @param \Closure|null $closure
     * @param string $url
     * @return bool
     */
    public function sendText(
        string                $content = '',
        array|Collection|null $mentionedList = [],
        array|Collection|null $mentionedMobileList = [],
        array|Collection|null $options = [],
        \Closure              $closure = null,
        string                $url = '/cgi-bin/webhook/send?key={key}'
    ): bool
    {
        $data = \collect([
            "msgtype" => "text",
            "text" => [
                "content" => $content,
                "mentioned_list" => \collect($this->getMentionedList())->replaceRecursive(\collect($mentionedList)->toArray()),
                "mentioned_mobile_list" => \collect($this->getMentionedMobileList())->replaceRecursive(\collect($mentionedMobileList)->toArray()),
            ]
        ]);
        return $this->send($data, $options, $closure, $url);
    }

    /**
     * 发送文件
     * @see https://developer.work.weixin.qq.com/document/path/91770#%E6%96%87%E4%BB%B6%E7%B1%BB%E5%9E%8B
     * @param string $mediaId 文件id，通过下文的文件上传接口获取
     * @param array|Collection|null $options Replace the specified options on the request
     * @param \Closure|null $closure
     * @param string $url
     * @return bool
     */
    public function sendFile(
        string           $mediaId = '',
        array|Collection|null $options = [],
        \Closure         $closure = null,
        string           $url = '/cgi-bin/webhook/send?key={key}'
    ): bool
    {
        $data = \collect([
            "msgtype" => "file",
            "file" => [
                "media_id" => $mediaId,
            ]
        ]);
        return $this->send($data, $options, $closure, $url);
    }

    /**
     * 发送音频
     * @see https://developer.work.weixin.qq.com/document/path/91770#%E8%AF%AD%E9%9F%B3%E7%B1%BB%E5%9E%8B
     * @param string $mediaId 文件id，通过下文的文件上传接口获取
     * @param array|Collection|null $options Replace the specified options on the request
     * @param \Closure|null $closure
     * @param string $url
     * @return bool
     */
    public function sendVoice(
        string           $mediaId = '',
        array|Collection|null $options = [],
        \Closure         $closure = null,
        string           $url = '/cgi-bin/webhook/send?key={key}'
    ): bool
    {
        $data = \collect([
            "msgtype" => "voice",
            "voice" => [
                "media_id" => $mediaId,
            ]
        ]);
        return $this->send($data, $options, $closure, $url);
    }

    /**
     * 上传
     * @see https://developer.work.weixin.qq.com/document/path/91770#%E6%96%87%E4%BB%B6%E4%B8%8A%E4%BC%A0%E6%8E%A5%E5%8F%A3
     * @param array|Collection|null $attach Attach a file to the request.
     * @param string $type 文件类型，分别有语音(voice)和普通文件(file)
     * @param array|Collection|null $options Replace the specified options on the request
     * @param \Closure|null $closure
     * @param string $url
     * @return string|null
     */
    public function uploadMedia(
        array|Collection|null $attach = [],
        string                $type = 'file',
        array|Collection|null $options = [],
        \Closure              $closure = null,
        string                $url = '/cgi-bin/webhook/upload_media?key={key}&type={type}'
    ): string|null
    {
        $response = Http::baseUrl($this->getBaseUrl())
            ->asMultipart()
            ->attach(...\collect($attach)->toArray())
            ->withOptions(\collect($options)->toArray())
            ->withUrlParameters(
                [
                    'key' => $this->key,
                    'type' => $type,
                ]
            )->post($url);
        if ($closure) {
            return call_user_func($closure, $response);
        }
        if ($response->ok()) {
            $json = $response->json();
            if (Validator::make($json, ['errcode' => 'required|integer|size:0'])->messages()->isEmpty()) {
                return \str(\data_get($json, 'media_id'))->toString() ?? null;
            }
        }
        return null;
    }

}
