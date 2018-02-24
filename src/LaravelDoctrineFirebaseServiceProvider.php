<?php


namespace RandomState\LaravelDoctrineFirebase;


use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Lcobucci\JWT\Parser;
use RandomState\LaravelDoctrineFirebase\Http\FirebaseJwtTokenGuard;
use Illuminate\Contracts\Cache\Repository;

class LaravelDoctrineFirebaseServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->publishes([
            self::getConfigPath() => config_path(self::getConfigName() . ".php")
        ], 'laravel-api');

        $this->registerUserProvider();
        $this->registerJwtTokenGuard();
    }

    public function register()
    {
        $this->mergeConfigFrom(self::getConfigPath(), self::getConfigName());
    }

    protected function registerUserProvider()
    {
        Auth::provider('firebase', function (Container $app, array $config) {
            return new FirebaseUserProvider(
                $app->make(EntityManager::class),
                $app->make(Parser::class),
                $config['model'],
                $app->make(Client::class),
                // todo config for project ID,
                Carbon::now(),
                $app->make(Repository::class)
            );
        });
    }

    protected function registerJwtTokenGuard()
    {
        Auth::extend('verifiable-token', function ($app, $name, array $config) {
            return new FirebaseJwtTokenGuard(Auth::createUserProvider($config['provider']), $app->make(Request::class));
        });
    }
}