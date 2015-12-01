<?php

namespace Clumsy\Aleph;

use Exception;
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

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config.php', 'clumsy.aleph');

        $this->endpoint = config('clumsy.aleph.endpoint');

        $this->app['log']->getMonolog()->pushProcessor(function ($record) {

            try {

                $request = $this->app['request'];
                $requestInfo = [
                    'ip'      => $request->ip(),
                    'url'     => $request->fullUrl(),
                    'method'  => $request->method(),
                    'isAjax'  => $request->ajax(),
                    'agent'   => $request->header('USER_AGENT'),
                    'input'   => $request->all(),
                    'cookies' => $request->cookie(),
                    'user'    => $request->user(),
                    'session' => [
                        'started'    => $request->session()->isStarted(),
                        'id'         => $request->session()->getId(),
                        'attributes' => $request->session()->all(),
                        'handler'    => class_basename($request->session()->getHandler()),
                    ],
                ];

                if (!is_null($this->endpoint)) {
                    $record['request'] = $requestInfo;
                    $this->postRecord($record);
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

    protected function postRecord(array $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, true));
        curl_exec($ch);
        curl_close($ch);
    }
}
