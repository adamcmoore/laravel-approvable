<?php
namespace AcMoore\Approvable\Tests;

use AcMoore\Approvable\ApprovableServiceProvider;

use AcMoore\Approvable\Tests\Models\Article;
use AcMoore\Approvable\Tests\Observers\ArticleObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;
use Faker\Factory as Faker;
use Illuminate\Contracts\Config\Repository;


class ApprovableTestCase extends TestCase
{
	use RefreshDatabase;



	public function setUp(): void
	{
		$this->faker = Faker::create();

		parent::setUp();

		$this->loadMigrationsFrom(__DIR__.'/database/migrations');
		$this->withFactories(__DIR__.'/database/factories');

		Article::observe(ArticleObserver::class);
	}


	protected function defineEnvironment($app)
	{
		// Setup default database to use sqlite :memory:
		tap($app->make('config'), function (Repository $config) {
			$config->set('database.default', 'testbench');
			$config->set('database.connections.testbench', [
				'driver'   => 'sqlite',
				'database' => ':memory:',
				'prefix'   => '',
			]);
		});
	}


    protected function getPackageProviders($app)
    {
        return [
            ApprovableServiceProvider::class,
        ];
    }
}
