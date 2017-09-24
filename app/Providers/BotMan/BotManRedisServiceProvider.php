<?php

namespace App\Providers\BotMan;

use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Cache\LaravelCache;
use BotMan\BotMan\Storages\Drivers\RedisStorage;
use Illuminate\Support\ServiceProvider;

class BotManRedisServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('botman-redis', function ($app) {
            $storage = new RedisStorage(
                        config('botman.config.redis.host', env('REDIS_HOST')),
                        config('botman.config.redis.port', env('REDIS_PORT')),
                        config('botman.config.redis.password', env('REDIS_PASSWORD'))
            );

            return BotManFactory::create(config('botman', []), new LaravelCache(), $app->make('request'),
                $storage);
        });
    }
}
