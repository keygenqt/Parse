<?php

namespace yii\parse;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\Json;

class Connection extends Component
{
    const EVENT_AFTER_OPEN = 'afterOpen';
    private $_connect;
    const HOST_NAME = 'https://api.parse.com';
    const VERSION_STRING = 'yii2-0.1.0';
    public $version = 1;
    public $appId;
    public $restKey;
    public $masterKey;

    public function getIsActive()
    {
        return $this->_connect !== null;
    }

    public function open()
    {
        if ($this->_connect !== null) {
            return;
        }
        
        $this->_connect = curl_init();
        curl_setopt($this->_connect, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->_connect, CURLOPT_HTTPHEADER, $this->getHeaders());
        
        Yii::trace('Opening connection to parse.', __CLASS__);
        $this->initConnection();
    }
    
    public function getHeaders()
    {
        return array(
            'X-Parse-Application-Id: ' . $this->appId,
            'X-Parse-Client-Version: ' . self::VERSION_STRING,
            'X-Parse-Master-Key: ' . $this->masterKey,
            'X-Parse-REST-API-Key: ' . $this->restKey,
            'Expect: '
        );
    }

    public function close()
    {
        curl_close($this->_connect);
    }

    protected function initConnection()
    {
        $this->trigger(self::EVENT_AFTER_OPEN);
    }

    public function getDriverName()
    {
        return 'parse';
    }

    public function createCommand($config = [])
    {
        $this->open();
        $config['db'] = $this;
        $command = new Command($config);

        return $command;
    }

    public function getQueryBuilder()
    {
        return new QueryBuilder($this);
    }

    public function get($url, $options = [], $body = null)
    {
        $this->open();
        return $this->httpRequest('GET', $this->createUrl($url, $options), $body);
    }

    public function post($url, $options = [], $body = null)
    {
        $this->open();
        return $this->httpRequest('POST', $this->createUrl($url, $options), $body);
    }

    public function put($url, $options = [], $body = null)
    {
        $this->open();
        return $this->httpRequest('PUT', $this->createUrl($url, $options), $body);
    }

    public function delete($url, $options = [], $body = null)
    {
        $this->open();
        return $this->httpRequest('DELETE', $this->createUrl($url, $options), $body);
    }

    private function createUrl($path, $options = [])
    {
        if (!is_string($path)) {
            $url = implode('/', array_map(function ($a) {
                return urlencode(is_array($a) ? implode(',', $a) : $a);
            }, $path));
            if (!empty($options)) {
                $url .= '?' . http_build_query($options);
            }
        } else {
            $url = $path;
            if (!empty($options)) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($options);
            }
        }
        return self::HOST_NAME . '/' . $this->version . '/' . $url;
    }

    protected function httpRequest($method, $url, $requestBody = null)
    {
        $method = strtoupper($method);
        
        curl_setopt($this->_connect, CURLOPT_URL, $url);
        curl_setopt($this->_connect, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($requestBody) {
            curl_setopt($this->_connect, CURLOPT_HTTPHEADER, array_merge($this->getHeaders(), ['Content-Type: application/json']));
            curl_setopt($this->_connect, CURLOPT_POSTFIELDS, $requestBody);
        } else {
            curl_setopt($this->_connect, CURLOPT_HTTPHEADER, $this->getHeaders());
        }
        
        $response = curl_exec($this->_connect);
        $status = curl_getinfo($this->_connect, CURLINFO_HTTP_CODE);
        
        if ($status === 404) {
            throw new InvalidConfigException('Error: url 404');
        } else {
            $response = Json::decode(curl_exec($this->_connect));
        }
        if (isset($response['error'])) {
            throw new InvalidConfigException($response['error']);
        }
        
        return $response;
    }
}
