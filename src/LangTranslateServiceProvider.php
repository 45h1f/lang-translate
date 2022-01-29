<?php

namespace Ashiful\LangTranslate;

use Ashiful\LangTranslate\Commands\LangTranslateCommand;
use Illuminate\Support\ServiceProvider;

/**
 * Class CrudServiceProvider.
 */
class LangTranslateServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                LangTranslateCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/config/lang-translate.php' => config_path('lang-translate.php')
        ], 'lang-translate');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
