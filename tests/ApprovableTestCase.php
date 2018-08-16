<?php
/**
 * This file is part of the Laravel Approvable package.
 *
 * @author     Adam Moore <adam@acmoore.co.uk>
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */

namespace AcMoore\Approvable\Tests;

use AcMoore\Approvable\ApprovableServiceProvider;

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


    public function setUp()
    {
        $this->faker = Faker::create();

        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->withFactories(__DIR__.'/database/factories');
    }


    protected function getPackageProviders($app)
    {
        return [
            ApprovableServiceProvider::class,
            ConsoleServiceProvider::class,
        ];
    }
}
