<?php
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
        $migration = __DIR__.'/../migrations/versions.stub';

        $this->publishes([
            $migration => database_path(sprintf('migrations/%s_create_approvable_versions_table.php', date('Y_m_d_His'))),
        ], 'migrations');
    }
}
