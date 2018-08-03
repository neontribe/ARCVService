<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        $this->mapDataRoutes();

        $this->mapServiceRoutes();

        $this->mapStoreRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::domain('arcv-service.test')
            ->prefix('api')
            ->middleware('api')
            ->namespace($this->namespace . '\API')
            ->group(base_path('routes/api.php'));
    }

    /**
     * Define the "service" routes for the application.
     *
     * @return void
     */
    protected function mapServiceRoutes()
    {
        Route::domain('arcv-service.test')
            ->middleware(['web'])
            ->namespace($this->namespace . '\Service')
            ->group(base_path('routes/service.php'));
    }

    /**
     * Define the "data" routes for the application.
     *
     * @return void
     */
    protected function mapDataRoutes()
    {
        Route::domain('arcv-service.test')
            ->prefix('data')
            ->middleware(['web', 'isNotProduction'])
            ->namespace($this->namespace . '\Service')
            ->group(base_path('routes/data.php'));
    }

    /**
     * Define the "store" routes for the application.
     *
     * @return void
     */
    protected function mapStoreRoutes()
    {
        Route::domain('arcv-store.test')
            ->middleware(['web'])
            ->namespace($this->namespace . '\Store')
            ->group(base_path('routes/store.php'));
    }
}
