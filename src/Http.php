<?php

namespace Cole\Foundation;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;
use Hanson\Foundation\Exception\HttpException;
use Psr\Http\Message\ResponseInterface;

class Http
{
    /**
     * Http Client.
     *
     * @var HttpClient
     */
    protected $client;

    /**
     * The middlewares.
     *
     * @var array
     */
    protected $middlewares = [];

    /**
     * Guzzle client default settings.
     *
     * @var array
     */
    protected static $defaults = [
        'curl' => [
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        ],
    ];

    /**
     * Set guzzle default settions.
     *
     * @param array $defaults
     */
    public function setDefaultOptions($defaults = [])
    {
        self::$defaults = $defaults;
    }

    /**
     * Return current guzzle default settions.
     *
     * @return array
     */
    public function getDefaultOptions()
    {
        return self::$defaults;
    }

    /**
     * GET request.
     *
     * @param string $url
     * @param array  $query
     *
     * @return ResponseInterface
     *
     * @throws HttpException
     */
    public function get($url, array $query = [])
    {
        return $this->request($url, 'GET', compact('query'));
    }

    /**
     * POST request.
     *
     * @param string $url
     * @param array  $form_params
     *
     * @return ResponseInterface
     *
     * @throws HttpException
     */
    public function post($url, array $form_params = [])
    {
        return $this->request($url, 'POST', compact('form_params'));
    }

    /**
     * JSON request.
     *
     * @param string $url
     * @param array  $json
     *
     * @return ResponseInterface
     *
     * @throws HttpException
     */
    public function json($url, array $json = [])
    {
        return $this->request($url, 'POST', compact('json'));
    }

    /**
     * Upload file.
     *
     * @param string $url
     * @param array  $files
     * @param array  $form
     * @param array  $queries
     *
     * @return ResponseInterface
     */
    public function upload($url, array $queries = [], array $files = [], array $form = [])
    {
        $multipart = [];
        foreach ($files as $name => $path) {
            if (is_array($path)) {
                foreach ($path as $item) {
                    $multipart[] = [
                        'name' => $name.'[]',
                        'contents' => fopen($item, 'r'),
                    ];
                }
            } else {
                $multipart[] = [
                    'name' => $name,
                    'contents' => fopen($path, 'r'),
                ];
            }
        }
        foreach ($form as $name => $contents) {
            $multipart[] = compact('name', 'contents');
        }

        return $this->request($url, 'POST', array_merge(['query' => $queries], 'multipart'));
    }

    /**
     * Set GuzzleHttp\Client.
     *
     * @param \GuzzleHttp\Client $client
     *
     * @return Http
     */
    public function setClient(HttpClient $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Return \GuzzleHttp\Client instance.
     *
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        if (!($this->client instanceof HttpClient)) {
            $this->client = new HttpClient();
        }

        return $this->client;
    }

    public function addMiddleware(callable $middleware)
    {
        array_push($this->middlewares, $middleware);

        return $this;
    }

    /**
     * Return all middlewares.
     *
     * @return array
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    /**
     * Build a handler.
     *
     * @return HandlerStack
     */
    public function getHandler()
    {
        $stack = HandlerStack::create();
        foreach ($this->middlewares as $middleware) {
            $stack->push($middleware);
        }

        if (isset(static::$defaults['handler'])) {
            $stack->push(static::$defaults['handler']);
        }

        return $stack;
    }

    /**
     * Make a request.
     *
     * @param string $url
     * @param string $method
     * @param array  $options
     *
     * @return ResponseInterface
     */
    public function request($url, $method = 'GET', $options = [])
    {
        $method = strtoupper($method);
        $options = array_merge(self::$defaults, $options);

        Log::debug('Client Request: ', compact('url', 'method', 'options'));

        $options['handler'] = $this->getHandler();

        $response = $this->getClient()->request($method, $url, $options);

        Log::debug('API response:', [
            'Status' => $response->getStatusCode(),
            'Reason' => $response->getReasonPhrase(),
            'Headers' => $response->getHeaders(),
            'Body' => strval($response->getBody()),
        ]);

        return $response;
    }
}
