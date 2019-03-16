<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Conversation Cache Time
    |--------------------------------------------------------------------------
    |
    | BotMan caches each started conversation. This value defines the
    | number of minutes that a conversation will remain stored in
    | the cache.
    |
    */
    'conversation_cache_time' => 30,
    'redis' => [
        'host' => env('REDIS_HOST', 'redis'),
        'port' => env('REDIS_PORT', 6379),
        'password' => env('REDIS_PASSWORD', null)
    ],
];
