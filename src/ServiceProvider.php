<?php

namespace Jdjfisher\LaravelRouteDeprecation;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ReflectionMethod;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() 
    {
        $this->app->booted(function () {
            foreach (Route::getRoutes()->getRoutes() as $route) {
                if (gettype($route->action['uses']) === 'string') {
                    [ $controller, $action ] = Str::parseCallback($route->action['uses']);
                    
                    $reflection = new ReflectionMethod($controller, $action);

                    $deprecated = str_contains($reflection->getDocComment(), '@deprecated');
        
                    if ($deprecated) {
                        $route->middleware('deprecated');
                    }
                }
            }
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['router']->aliasMiddleware('deprecated', Deprecated::class);
    }
}
