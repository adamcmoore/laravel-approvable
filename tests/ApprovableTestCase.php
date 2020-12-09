<?php
namespace AcMoore\Approvable\Tests;

use AcMoore\Approvable\ApprovableServiceProvider;

use AcMoore\Approvable\Tests\Models\Article;
use AcMoore\Approvable\Tests\Observers\ArticleObserver;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase;
use Faker\Factory as Faker;

class ApprovableTestCase extends TestCase
{

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }


    public function setUp(): void
    {
        $this->faker = Faker::create();

        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->withFactories(__DIR__.'/database/factories');

	    Article::observe(ArticleObserver::class);
    }


    protected function getPackageProviders($app)
    {
        return [
            ApprovableServiceProvider::class,
            ConsoleServiceProvider::class,
        ];
    }
}
