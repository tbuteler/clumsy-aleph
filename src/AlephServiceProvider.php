<?php

namespace Clumsy\Aleph;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\ServiceProvider;

class AlephServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    protected $endpoint;

    protected $enforceHttps;

    protected $logEndpointResponse;

    protected $sensitiveKeywords;

    protected $whitelist;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config.php', 'clumsy.aleph');

        if (!$this->app['config']->get('clumsy.aleph.enabled')) {
            return;
        }

        $this->endpoint = $this->app['config']->get('clumsy.aleph.endpoint');
        $this->enforceHttps = $this->app['config']->get('clumsy.aleph.enforce-endpoint-https');
        $this->logEndpointResponse = $this->app['config']->get('clumsy.aleph.log-endpoint-response');
        $this->sensitiveKeywords = $this->app['config']->get('clumsy.aleph.sensitive-keywords');
        $this->whitelist = $this->app['config']->get('clumsy.aleph.attribute-whitelist');

        $this->app['log']->getMonolog()->pushProcessor(function ($record) {

            try {

                $request = $this->app['request'];
                $requestInfo = [
                    'ip'      => $request->ip(),
                    'url'     => $request->fullUrl(),
                    'method'  => $request->method(),
                    'isAjax'  => $request->ajax(),
                    'agent'   => $request->header('USER_AGENT'),
                    'input'   => $this->getInputData(),
                    'cookies' => $request->cookie(),
                    'user'    => $this->getUserData(),
                    'session' => [
                        'started'    => $request->session()->isStarted(),
                        'id'         => $request->session()->getId(),
                        'attributes' => $this->getSessionData(),
                        'handler'    => class_basename($request->session()->getHandler()),
                    ],
                ];

                if ($this->endpoint) {
                    $record['aleph'] = $requestInfo;
                    $requestInfo = $this->postRecord($record);
                }

                $record['message'] = json_encode($requestInfo, true).' |[o]| '.$record['message'];

                return $record;

            } catch (Exception $e) {

                return $record;
            }
        });
    }

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config.php' => config_path('clumsy/aleph.php'),
        ], 'config');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    protected function sensitive(array $data)
    {
        $arrays = array_filter($data, function ($value) {
            return is_array($value);
        });

        $data = array_filter($data, function ($value) {
            return !is_array($value);
        });

        foreach ($this->sensitiveKeywords as $keyword) {
            $replace = preg_grep("/{$keyword}/i", array_keys($data));
            foreach ($replace as $key) {
                if (!in_array($key, $this->whitelist)) {
                    array_set($data, $key, $this->redact(array_get($data, $key)));
                }
            }
        }

        $data = array_merge($data, array_map(function ($value) {
            // Process sensitive data recursively
            return $this->sensitive($value);
        }, $arrays));

        return $data;
    }

    protected function redact($content)
    {
        $type = gettype($content);
        $info = $type === 'object' ? get_class($content) : strlen($content);

        return "[redacted] {$type}($info)";
    }

    protected function getInputData()
    {
        return $this->sensitive($this->app['request']->all());
    }

    protected function getUserData()
    {
        return $this->sensitive($this->app['request']->user() ? $this->app['request']->user()->getAttributes() : []);
    }

    protected function getSessionData()
    {
        return $this->sensitive($this->app['request']->session()->all());
    }

    protected function postRecord(array $data)
    {
        $client = new Client();

        $request = new Request('POST', $this->endpoint);

        if ($request->getUri()->getScheme() !== 'https' && $this->enforceHttps) {
            return ['aleph-error' => 'Sending data over insecure connections requires explicit override.'];
        }

        $response = $client->send($request, [
            'json' => $data,
        ]);

        if ($response->getStatusCode() === 200 && $this->logEndpointResponse) {
            $data = json_decode((string)$response->getBody(), true);
        }

        return $data;
    }
}
