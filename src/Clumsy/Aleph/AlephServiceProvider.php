<?php namespace Clumsy\Aleph;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class AlephServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->error(function(\Exception $exception, $code)
		{
		    $request = Request::instance();
		    $requestHeaders = $request->server->getHeaders();

		    $initialInfo = json_encode(array(
		        'url'    => $request->url(),
		        'method' => isset($requestHeaders['REQUEST_METHOD']) ? $requestHeaders['REQUEST_METHOD']: '',
		        'agent'  => isset($requestHeaders['USER_AGENT']) ? $requestHeaders['USER_AGENT'] : '',
		        'input'  => $request->all(),
		    ), true);

		    Log::error($initialInfo.' |o| '.$exception);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
