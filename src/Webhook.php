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
     * @var string
     */
    protected string $baseUrl = 'https://qyapi.weixin.qq.com/';

    /**
     * @var string
     */
    protected string $key = '';

    /**
     * @var array|Collection|null
     */
    protected array|Collection|null $mentionedList = [];

    /**
     * @var array|Collection|null
     */
    protected array|Collection|null $mentionedMobileList = [];

    /**
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
     * @param string $baseUrl
     * @return $this
     */
    public function setBaseUrl(string $baseUrl): Webhook
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     * @return $this
     */
    public function setKey(string $key): Webhook
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return array|Collection|null
     */
    public function getMentionedList(): array|Collection|null
    {
        return $this->mentionedList;
    }

    /**
     * @param array|Collection|null $mentionedList
     * @return $this
     */
    public function setMentionedList(array|Collection|null $mentionedList): Webhook
    {
        $this->mentionedList = $mentionedList;
        return $this;
    }

    /**
     * @return array|Collection|null
     */
    public function getMentionedMobileList(): array|Collection|null
    {
        return $this->mentionedMobileList;
    }

    /**
     * @param array|Collection|null $mentionedMobileList
     * @return $this
     */
    public function setMentionedMobileList(array|Collection|null $mentionedMobileList): Webhook
    {
        $this->mentionedMobileList = $mentionedMobileList;
        return $this;
    }


    /**
     * @param string $baseUrl
     * @param string $key
     * @param array $mentionedList
     * @param array $mentionedMobileList
     */
    public function __construct(string $key = '', string $baseUrl = 'https://qyapi.weixin.qq.com/', array $mentionedList = [], array $mentionedMobileList = [])
    {
        $this->setBaseUrl($baseUrl);
        $this->setKey($key);
        $this->setMentionedList($mentionedList);
        $this->setMentionedMobileList($mentionedMobileList);
    }

    /**
     * @see https://developer.work.weixin.qq.com/document/path/91770
     * @param string|null $url
     * @param array|Collection|null $data
     * @param array|Collection|null $options
     * @param \Closure|null $responseHandler
     * @return bool|mixed
     */
    public function send(
        array|Collection|null $data = null,
        string|null           $url = '/cgi-bin/webhook/send?key={key}',
        array|Collection|null $urlParameters = null,
        array|Collection|null $options = null,
        \Closure|null         $responseHandler = null
    ): mixed
    {
        $data = \collect($data);
        $urlParameters = \collect($urlParameters);
        $options = \collect($options);
        \data_fill($urlParameters, 'key', $this->key);
        $response = Http::baseUrl($this->getBaseUrl())->asJson()->withOptions($options->toArray())->withUrlParameters($urlParameters->toArray())->post($url, $data->toArray());
        if ($responseHandler instanceof \Closure) {
            return value($responseHandler($response));
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
     * @see https://developer.work.weixin.qq.com/document/path/91770#%E6%96%87%E6%9C%AC%E7%B1%BB%E5%9E%8B
     * @param string $content
     * @param array|Collection|null $mentionedList
     * @param array|Collection|null $mentionedMobileList
     * @return Collection
     */
    public function sendTextFormatter(
        string                $content = '',
        array|Collection|null $mentionedList = [],
        array|Collection|null $mentionedMobileList = []
    ): Collection
    {
        return \collect([
            'msgtype' => 'text',
            'text' => [
                'content' => $content,
                'mentioned_list' => \collect($this->getMentionedList())->replaceRecursive(\collect($mentionedList)),
                'mentioned_mobile_list' => \collect($this->getMentionedMobileList())->replaceRecursive(\collect($mentionedMobileList)),
            ]
        ]);
    }

    /**
     * @see https://developer.work.weixin.qq.com/document/path/91770#markdown%E7%B1%BB%E5%9E%8B
     * @param string $content
     * @return Collection
     */
    public function sendMarkdownFormatter(
        string $content = ''
    ): Collection
    {
        return \collect([
            'msgtype' => 'markdown',
            'markdown' => [
                'content' => $content,
            ]
        ]);
    }

    /**
     * @see https://developer.work.weixin.qq.com/document/path/91770#%E5%9B%BE%E7%89%87%E7%B1%BB%E5%9E%8B
     * @param string $base64
     * @return Collection
     */
    public function sendImageFormatter(
        string $base64 = ''
    ): Collection
    {
        return \collect([
            'msgtype' => 'image',
            'image' => [
                'base64' => $base64,
                'md5' => 'MD5'
            ]
        ]);
    }

    /**
     * @see https://developer.work.weixin.qq.com/document/path/91770#%E5%9B%BE%E6%96%87%E7%B1%BB%E5%9E%8B
     * @param array|Collection|null $articles
     * @return Collection
     */
    public function sendNewsFormatter(
        array|Collection|null $articles = null
    ): Collection
    {
        return \collect([
            'msgtype' => 'news',
            'news' => [
                'articles' => \collect($articles),
            ]
        ]);
    }

    /**
     * @see https://developer.work.weixin.qq.com/document/path/91770#%E6%A8%A1%E7%89%88%E5%8D%A1%E7%89%87%E7%B1%BB%E5%9E%8B
     * @param array|Collection|null $templateCcard
     * @return Collection
     */
    public function sendTemplateCardFormatter(
        array|Collection|null $templateCcard = null
    ): Collection
    {
        return \collect([
            'msgtype' => 'template_card',
            'template_card' => \collect($templateCcard),
        ]);
    }

    /**
     * @see https://developer.work.weixin.qq.com/document/path/91770#%E6%96%87%E4%BB%B6%E7%B1%BB%E5%9E%8B
     * @param string $mediaId
     * @return Collection
     */
    public function sendFileFormatter(
        string $mediaId = '',
    ): Collection
    {
        return \collect([
            'msgtype' => 'file',
            'file' => [
                'media_id' => $mediaId,
            ]
        ]);
    }

    /**
     * @see https://developer.work.weixin.qq.com/document/path/91770#%E8%AF%AD%E9%9F%B3%E7%B1%BB%E5%9E%8B
     * @param string $mediaId
     * @return Collection
     */
    public function sendVoiceFormatter(
        string $mediaId = '',
    ): Collection
    {
        return \collect([
            'msgtype' => 'voice',
            'voice' => [
                'media_id' => $mediaId,
            ]
        ]);
    }

    /**
     * @see https://developer.work.weixin.qq.com/document/path/91770#%E6%96%87%E4%BB%B6%E4%B8%8A%E4%BC%A0%E6%8E%A5%E5%8F%A3
     * @param array|Collection|null $attach
     * @param string|null $url
     * @param string $type
     * @param array|Collection|null $urlParameters
     * @param array|Collection|null $options
     * @param \Closure|null $responseHandler
     * @return mixed
     */
    public function uploadMedia(
        array|Collection|null $attach = null,
        array|Collection|null $data = null,
        string|null           $url = '/cgi-bin/webhook/upload_media?key={key}&type={type}',
        string                $type = 'file',
        array|Collection|null $urlParameters = null,
        array|Collection|null $options = null,
        \Closure|null         $responseHandler = null
    ): mixed
    {
        $type = !in_array(strtolower($type), ['file', 'voice']) ? $type : 'file';
        $attach = \collect($attach);
        $data = \collect($data);
        $urlParameters = \collect($urlParameters);
        $options = \collect($options);
        \data_fill($urlParameters, 'key', $this->key);
        \data_fill($urlParameters, 'type', $type);
        $response = Http::baseUrl($this->getBaseUrl())
            ->asMultipart()
            ->attach(...$attach->toArray())
            ->withOptions($options->toArray())
            ->withUrlParameters($urlParameters->toArray())
            ->post($url, $data->toArray());
        if ($responseHandler instanceof \Closure) {
            return \value($responseHandler($response));
        }
        if ($response->ok()) {
            $json = $response->json();
            if (Validator::make($json, ['errcode' => 'required|integer|size:0'])->messages()->isEmpty()) {
                return \data_get($json, 'media_id', null);
            }
        }
        return null;
    }

}
