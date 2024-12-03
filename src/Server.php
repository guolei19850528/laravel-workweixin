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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Server Api Class
 * @see https://developer.work.weixin.qq.com/document/path/90664
 */
class Server
{
    /**
     * Base Url
     * @var string
     */
    protected string $baseUrl = '';

    /**
     * 企业ID
     * @var string
     */
    protected string $corpid = '';

    /**
     * 应用的凭证密钥
     * @var string
     */
    protected string $corpsecret = '';

    /**
     * 应用ID
     * @var string|int
     */
    protected string|int $agentid = '';

    /**
     * access token
     * @var string
     */
    protected string $accessToken = '';

    public function getBaseUrl(): string
    {
        if (\str($this->baseUrl)->endsWith('/')) {
            return \str($this->baseUrl)->substr(0, -1)->toString();
        }
        return $this->baseUrl;
    }

    public function setBaseUrl(string $baseUrl = ''): Server
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    public function getCorpid(): string
    {
        return $this->corpid;
    }

    public function setCorpid(string $corpid = ''): Server
    {
        $this->corpid = $corpid;
        return $this;
    }

    public function getCorpsecret(): string
    {
        return $this->corpsecret;
    }

    public function setCorpsecret(string $corpsecret = ''): Server
    {
        $this->corpsecret = $corpsecret;
        return $this;
    }

    public function getAgentid(): int|string
    {
        return $this->agentid;
    }

    public function setAgentid(int|string $agentid = ''): Server
    {
        $this->agentid = $agentid;
        return $this;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken = ''): Server
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
     * Server Class Construct Function
     * @param string $corpid 企业ID
     * @param string $corpsecret 应用的凭证密钥
     * @param string|int $agentid 每个应用都有唯一的agentid。在管理后台->“应用管理”->“应用”，点进某个应用，即可看到agentid。
     * @param string $baseUrl
     */
    public function __construct(
        string     $corpid = '',
        string     $corpsecret = '',
        string|int $agentid = '',
        string     $baseUrl = 'https://qyapi.weixin.qq.com/'
    )
    {
        $this->setCorpid($corpid);
        $this->setCorpsecret($corpsecret);
        $this->setAgentid($agentid);
        $this->setBaseUrl($baseUrl);
    }

    /**
     * 获取access_token
     * @see https://developer.work.weixin.qq.com/document/path/91039
     * @param array|Collection|null $options
     * @param string $url
     * @return $this
     */
    public function queryAccessToken(
        array|Collection|null $options = [],
        string                $url = '/cgi-bin/gettoken'
    ): Server
    {
        $response = Http::baseUrl($this->getBaseUrl())
            ->withOptions(\collect($options)->toArray())
            ->get(
                $url,
                [
                    'corpid' => $this->getCorpid(),
                    'corpsecret' => $this->getCorpsecret(),
                ]
            );
        if ($response->ok()) {
            $json = $response->json();
            if (Validator::make($json, ['errcode' => 'required|integer|size:0'])->messages()->isEmpty()) {
                $this->setAccessToken(\data_get($json, 'access_token', ''));
            }
        }
        return $this;
    }

    /**
     * 获取企业微信接口IP段
     * @see https://developer.work.weixin.qq.com/document/path/92520
     * @param array|Collection $options
     * @param string $url
     * @return array|null
     */
    public function queryApiDomainIp(
        array|Collection|null $options = [],
        string                $url = '/cgi-bin/get_api_domain_ip'
    ): array|null
    {
        $response = Http::baseUrl($this->getBaseUrl())
            ->withOptions(\collect($options)->toArray())
            ->get(
                $url,
                [
                    'corpid' => $this->getCorpid(),
                    'corpsecret' => $this->getCorpsecret(),
                    'access_token' => $this->getAccessToken()
                ]
            );
        if ($response->ok()) {
            $json = $response->json();
            if (Validator::make($json, ['errcode' => 'required|integer|size:0'])->messages()->isEmpty()) {
                return \data_get($json, 'ip_list', []);
            }
        }
        return null;
    }

    /**
     * 通过缓存获取access_token
     * @param string $key
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param array|Collection|null $queryAccessTokenFuncArgs
     * @return Server
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function queryAccessTokenWithCache(
        string                                    $key = '',
        \DateTimeInterface|\DateInterval|int|null $ttl = 7100,
        array|Collection|null                     $queryAccessTokenFuncArgs = []
    ): Server
    {
        if (\str($key)->isEmpty()) {
            $key = \str('laravel_workwx')->append('_', 'server_access_token', '_', $this->getAgentid())->toString();
        }
        if (\cache()->has($key)) {
            $this->setAccessToken(\cache()->get($key, ''));
        }
        if (!$this->queryApiDomainIp()) {
            $this->queryAccessToken(...\collect($queryAccessTokenFuncArgs)->toArray());
            \cache()->put($key, $this->getAccessToken(), $ttl);
        }
        return $this;
    }

    /**
     * 发送应用消息
     * @see https://developer.work.weixin.qq.com/document/path/90235
     * @param array|Collection $data Post Data
     * @param array|Collection $options Replace the specified options on the request.
     * @param string $url
     * @return bool
     */
    public function messageSend(
        array|Collection|null $data = [],
        array|Collection|null $options = [],
        string                $url = '/cgi-bin/message/send?access_token={access_token}'
    ): bool
    {
        $response = Http::baseUrl($this->getBaseUrl())
            ->asJson()
            ->withOptions(\collect($options)->toArray())
            ->withUrlParameters(
                [
                    'access_token' => $this->getAccessToken(),
                ]
            )->post($url, \collect($data)->toArray());
        if ($response->ok()) {
            $json = $response->json();
            if (Validator::make($json, ['errcode' => 'required|integer|size:0'])->messages()->isEmpty()) {
                return true;
            }
        }
        return false;
    }

    /**
     * 上传临时素材
     * @see https://developer.work.weixin.qq.com/document/path/90253
     * @param array|Collection $attach Attach a file to the request.
     * @param string $type 媒体文件类型，分别有图片（image）、语音（voice）、视频（video），普通文件（file）
     * @param array|Collection $options Replace the specified options on the request.
     * @param string $url
     * @return string|null
     */
    public function mediaUpload(
        array|Collection|null $attach = [],
        string                $type = 'file',
        array|Collection|null $options = [],
        string                $url = '/cgi-bin/media/upload?access_token={access_token}&type={type}'
    ): string|null
    {
        $response = Http::baseUrl($this->getBaseUrl())
            ->asMultipart()
            ->attach(...\collect($attach)->toArray())
            ->withOptions(\collect($options)->toArray())
            ->withUrlParameters(
                [
                    'access_token' => $this->getAccessToken(),
                    'type' => $type,
                ]
            )->post($url);
        if ($response->ok()) {
            $json = $response->json();
            if (Validator::make($json, ['errcode' => 'required|integer|size:0'])->messages()->isEmpty()) {
                return \data_get($json, 'media_id', '') ?? null;
            }
        }
        return null;
    }

    /**
     * 上传图片
     * @see https://developer.work.weixin.qq.com/document/path/90256
     * @param array|Collection $attach Attach a file to the request.
     * @param array|Collection $options Replace the specified options on the request.
     * @param string $url
     * @return string|null
     */
    public function mediaUploadImg(
        array|Collection|null $attach = [],
        array|Collection|null $options = [],
        string                $url = '/cgi-bin/media/uploadimg?access_token={access_token}'
    ): string|null
    {
        $response = Http::baseUrl($this->getBaseUrl())
            ->asMultipart()
            ->attach(...\collect($attach)->toArray())
            ->withOptions(\collect($options)->toArray())
            ->withUrlParameters(
                [
                    'access_token' => $this->getAccessToken(),
                ]
            )->post($url);
        if ($response->ok()) {
            $json = $response->json();
            if (Validator::make($json, ['errcode' => 'required|integer|size:0'])->messages()->isEmpty()) {
                return \data_get($json, 'url', '') ?? null;
            }
        }
        return null;
    }
}
