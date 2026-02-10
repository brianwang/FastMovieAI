<?php

namespace plugin\control\utils\yidevs;

use app\expose\helper\Config;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use plugin\control\utils\yidevs\stream\Client as StreamClient;
use plugin\control\utils\yidevs\Exception\InvalidResultException;
use plugin\control\utils\yidevs\Exception\InvalidTokenException;
use support\Log;

class Client
{
    protected $token;
    // protected $domain = 'https://api.yidevs.com';
    protected $domain = 'http://110.42.56.227:36999';
    protected $HttpClient;
    protected $channels_uid;
    public function __construct()
    {
        if ($domain = getenv('YIDEVS_DOMAIN')) {
            $this->domain = $domain;
        }
    }
    public function setChannelsUid(int $channels_uid)
    {
        $this->channels_uid = $channels_uid;
        $config = new Config('yidevs', 'control', $channels_uid);
        if (!$config->token) {
            throw new InvalidTokenException('YIDEVS_TOKEN is not set for channels_uid: ' . $channels_uid);
        }
        $this->token = $config->token;
    }
    public function client()
    {
        $this->HttpClient = new GuzzleHttpClient([
            'base_uri' => $this->domain,
            'timeout' => 600,
            'verify' => false,
            'proxy' => false
        ]);
    }
    public function request(string $method, string $url, array $options = [])
    {
        try {
            $this->client();
            $response = $this->HttpClient->request($method, $url, array_merge([
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->token,
                ]
            ], $options));
            $res = $response->getBody()->getContents();
        } catch (ClientException | RequestException $e) {
            Log::error($e->getMessage() . PHP_EOL . $e->getTraceAsString());
            throw new \Exception($e->getMessage());
        } catch (\Throwable $th) {
            Log::error($th->getMessage() . PHP_EOL . $th->getTraceAsString());
            throw new \Exception($th->getMessage());
        }
        return $res;
    }
    public function stream(string $url, array $data = [], ?callable $stream = null)
    {
        return new StreamClient($this->token, $this->domain . '/' . $url, $data, $stream);
    }
    public function get(string $url, array $query = [])
    {
        $res = $this->request('GET', $url, ['query' => $query]);
        $result = json_decode($res, true);
        if (!isset($result['code'])) {
            throw new InvalidResultException($res);
        }
        if ($result['code'] != 200) {
            throw new \Exception($result['msg']);
        }
        return $result['data'];
    }
    public function post(string $url, array $data = [], ?callable $stream = null)
    {
        if ($stream) {
            return new StreamClient($this->token, $this->domain . '/' . $url, $data, $stream);
        }
        $res = $this->request('POST', $url, ['json' => $data]);
        $result = json_decode($res, true);
        if (!isset($result['code'])) {
            throw new InvalidResultException($res);
        }
        if ($result['code'] != 200) {
            throw new \Exception($result['msg']);
        }
        return $result['data'];
    }
}
