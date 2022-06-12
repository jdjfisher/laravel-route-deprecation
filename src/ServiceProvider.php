<?php

declare(strict_types=1);

namespace Jdjfisher\LaravelRouteDeprecation;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use ReflectionClass;
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
        if (env('ROUTE_DEPRECATION_REFLECTION') === false) {
            return;
        }

        // TODO: Caching?
        $this->app->booted(function () {
            foreach (Route::getRoutes()->getRoutes() as $route) {

                // TODO: Handle closure based routes?
                if (gettype($route->action['uses']) === 'string') {

                    /** 
                     * @var class-string<Controller> $controller 
                     * @var string $action 
                     */
                    [ $controller, $action ] = Str::parseCallback($route->action['uses']);

                    $actionReflection = new ReflectionMethod($controller, $action);
                    $controllerReflection = new ReflectionClass($controller);

                    if ($this->deprecationTest($actionReflection) || $this->deprecationTest($controllerReflection)) {
                        $route->middleware('deprecated');
                    }
                }
            }
        });
    }

    /**
     * Determine whether a reflected definition is deprecated.
     * 
     * @param  ReflectionMethod|ReflectionClass  $reflection
     * @return bool
     */
    private function deprecationTest(ReflectionMethod|ReflectionClass $reflection): bool
    {
        $annotation = $reflection->getDocComment();

        if (!$annotation) {
            return false;
        }

        return str_contains($annotation, '@deprecated');
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
