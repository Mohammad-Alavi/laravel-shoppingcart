<?php

namespace MohammadAlavi\ShoppingCart;

use Illuminate\Support\ServiceProvider;
use MohammadAlavi\ShoppingCart\Models\ShoppingCart;

class ShoppingCartServiceProvider extends ServiceProvider
{
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function boot(): void
	{
		$this->publishes([
			__DIR__ . '/config/shoppingcart.php' => config_path('shoppingcart.php'),
		], 'config');

		$this->publishes([
			__DIR__ . '/migrations/' => base_path('/database/migrations'),
		], 'migrations');

		$this->loadMigrationsFrom(__DIR__ . '/migrations');

		// merge the config and stuff
		$this->setupConfig();
	}

	/**
	 * Get the Configuration
	 */
	private function setupConfig(): void
	{
		$this->mergeConfigFrom(__DIR__ . '/config/shoppingcart.php', 'shoppingcart');
	}

	public function register()
	{
		// register the facade
		$this->app->bind('shoppingcart', ShoppingCart::class);

		// merge the config and stuff
		$this->setupConfig();
	}
}
