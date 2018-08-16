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

namespace AcMoore\Approvable;

use Illuminate\Support\ServiceProvider;


class ApprovableServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $config = __DIR__.'/../config/approvable.php';
        $migration = __DIR__.'/../database/migrations/versions.stub';

        $this->publishes([
            $config => config_path('approvable.php'),
        ], 'config');

        $this->publishes([
            $migration => database_path(sprintf('migrations/%s_create_approvable_versions_table.php', date('Y_m_d_His'))),
        ], 'migrations');

        $this->mergeConfigFrom($config, 'approvable');
    }


    public function register()
    {
    }
}
