<?php

declare(strict_types=1);

namespace Bvtterfly\Replay\Exceptions;

use Exception;

final class InvalidConfiguration extends Exception
{
    public static function notATaggableStore(string $store): InvalidConfiguration
    {
        return new self("Configured cache store `{$store}` does not support Tagging.");
    }

    public static function notALockProvider(string $store): InvalidConfiguration
    {
        return new self("Configured cache store `{$store}` does not support Atomic Locks.");
    }
}
