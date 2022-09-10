<?php

declare(strict_types=1);

namespace Bvtterfly\Replay\Commands;

use Bvtterfly\Replay\Facades\Storage;
use Illuminate\Console\Command;

class CacheReset extends Command
{
    protected $signature = 'replay:cache-reset';

    protected $description = 'Reset the recorded response cache';

    public function handle(): int
    {
        if (Storage::flush()) {
            $this->info('Recorded response cache flushed.');

            return static::SUCCESS;
        }

        $this->error('Unable to flush cache.');

        return static::FAILURE;
    }
}
