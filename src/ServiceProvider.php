<?php

namespace AugustPermana\MetaGenerator;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use AugustPermana\MetaGenerator\Commands\MakeMetaModel;
use AugustPermana\MetaGenerator\Commands\CleanOrphanedMeta;

/**
 * Service provider for the Meta Generator package.
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // Register commands only in console environment
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeMetaModel::class,  // Command to generate metadata system
                CleanOrphanedMeta::class,  // Command to clean orphaned metadata
            ]);
        }
    }
}
