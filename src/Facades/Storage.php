<?php

namespace Bvtterfly\Replay\Facades;

use Bvtterfly\Replay\ReplayResponse;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Facade;

/**
 * @mixin \Bvtterfly\Replay\Storage
 *
 * @method static ReplayResponse|null get(string $key)
 * @method static void put(string $key, ReplayResponse $response)
 * @method static Lock lock(string $key)
 * @method static bool flush()
 */
class Storage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'replay-storage';
    }
}
