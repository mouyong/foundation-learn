<?php

namespace Cole\Foundation;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FilesystemCache;

abstract class AbstractAccessToken
{
    /**
     * App ID.
     *
     * @var string
     */
    protected $appId;

    /**
     * App secret.
     *
     * @var string
     */
    protected $secret;

    /**
     * Cache key.
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * Cache.
     *
     * @var Cache
     */
    protected $cache;

    /**
     * Cache key prefix.
     *
     * @var string
     */
    protected $prefix;

    /**
     * Response Json key name of token.
     *
     * @var string
     */
    protected $tokenJsonKey;

    /**
     * Response Json key name of expires in.
     *
     * @var string
     */
    protected $expiresJsonKey;

    /**
     * @var Http
     */
    protected $http;

    /**
     * Token string.
     *
     * @var string
     */
    protected $token;

    /**
     * @param mixed $token
     * @param int   $expires default value one day
     *
     * @return $this
     */
    public function setToken($token, $expires = 86400)
    {
        if ($expires) {
            $this->getCache()->save($this->getCacheKey(), $token, $expires);
        }
        $this->token = $token;

        return $this;
    }

    /**
     * Get token from cache.
     *
     * @param bool $forceRefresh
     *
     * @return string
     */
    public function getToken($forceRefresh = false)
    {
        $cache = $this->getCache()->fetch($this->getCacheKey()) ?: $this->token;

        if ($forceRefresh || empty($cache)) {
            $response = $this->getTokenFromServer();

            $this->checkTokenResponse($response);

            $this->setToken(
                $token = $response[$this->tokenJsonKey],
                $this->expiresJsonKey ? $response[$this->expiresJsonKey] : null
            );

            return $token;
        }
    }

    /**
     * Get token from remote server.
     *
     * @return mixed
     */
    abstract public function getTokenFromServer();

    /**
     * Throw exception if token is invalid.
     *
     * @param $result
     *
     * @return mixed
     */
    abstract public function checkTokenResponse($result);

    /**
     * @param string $appId
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;
    }

    /**
     * @return mixed
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @param string $secret
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
    }

    /**
     * @return mixed
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Set cache instance.
     *
     * @param \Doctrine\Common\Cache\Cache $cache
     *
     * @return $this
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Return the cache manager.
     *
     * @return \Doctrine\Common\Cache\Cache
     */
    public function getCache()
    {
        return $this->cache ?: $this->cache = new FilesystemCache(sys_get_temp_dir());
    }

    public function getCacheKey()
    {
        if (is_null($this->cacheKey)) {
            return $this->prefix.$this->appId;
        }

        return $this->cacheKey;
    }

    /**
     * Return the Http instance.
     *
     * @return Http
     */
    public function getHttp()
    {
        return $this->http ?: $this->http = new Http();
    }

    /**
     * Set the Http instance.
     *
     * @param Http $http
     *
     * @return $this
     */
    public function setHttp(Http $http)
    {
        $this->http = $http;

        return $this;
    }
}
