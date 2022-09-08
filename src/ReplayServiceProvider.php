<?php

declare(strict_types=1);

namespace Bvtterfly\Replay;

use Bvtterfly\Replay\Contracts\Policy;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ReplayServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('replay')
            ->hasTranslations()
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(Policy::class, config('replay.policy'));
    }
}
