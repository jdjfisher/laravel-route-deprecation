<?php

declare(strict_types=1);

namespace Jdjfisher\LaravelRouteDeprecation;

use Closure;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionFunction;
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

        $this->app->booted(function () {
            foreach (Route::getRoutes()->getRoutes() as $route) {
                $reflectors = [];

                $definition = $route->action['uses'];

                if ($definition instanceof Closure) {
                    $reflectors[] = new ReflectionFunction($definition);
                } else {
                    /**
                     * @var class-string<Controller> $controller
                     * @var string $action
                     */
                    [ $controller, $action ] = Str::parseCallback($definition);

                    $reflectors[] = new ReflectionMethod($controller, $action);
                    $reflectors[] = new ReflectionClass($controller);
                }

                foreach ($reflectors as $reflector) {
                    if ($this->deprecationCheck($reflector)) {
                        $route->middleware('deprecated');
                        break;
                    }
                }
            }
        });
    }

    /**
     * Determine whether a reflected definition is deprecated.
     *
     * @param  ReflectionMethod|ReflectionClass|ReflectionFunction  $reflection
     * @return bool
     */
    public function deprecationCheck(ReflectionMethod|ReflectionClass|ReflectionFunction $reflection): bool
    {
        // if (!$reflection instanceof ReflectionClass) {
        //     return $reflection->isDeprecated();
        // }

        $annotation = $reflection->getDocComment();

        if (! $annotation) {
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
