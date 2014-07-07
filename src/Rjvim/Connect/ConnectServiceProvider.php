<?php namespace Rjvim\Connect;

use Illuminate\Support\ServiceProvider;

class ConnectServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('rjvim/connect');

	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->register('Cartalyst\Sentry\SentryServiceProvider');
		
	 	// Register 'connect'
	    $this->app['connect'] = $this->app->share(function($app)
	    {
	        // create Connect instance
        	$connect = new Connect();
			// return Connect instance
        	return $connect;
	    });

    	$this->app->booting(function()
        {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('Connect', 'Rjvim\Connect\ConnectFacade');

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
