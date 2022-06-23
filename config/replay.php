<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the cache store that gets used while Replay will store the
    | information required for it to function.
    | By default, Replay will use the default cache store.
    |
    | Please see config/cache.php for the list of all available Cache Stores.
    |
     */

    'use' => env('REPLY_CACHE_STORE', config('cache.default')),

    /*
    |--------------------------------------------------------------------------
    | Reply Master Switch
    |--------------------------------------------------------------------------
    |
    | Reply is enabled by default,
    | Use this setting to enable/disable the Reply.
    |
    */

    'enabled' => env('REPLAY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Expiration Seconds
    |--------------------------------------------------------------------------
    |
    | This value controls the number of seconds until an idempotency response
    | is considered expired.
    |
    | The default is set to 1 day.
    |
    */

    'expiration' => 86400,

    /*
    |--------------------------------------------------------------------------
    | Request Header Name
    |--------------------------------------------------------------------------
    |
    | Reply will check this header name to determine
    | if a request is an Idempotency request.
    |
    */

    'header_name' => 'Idempotency-Key',

];
